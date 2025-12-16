<?php

/**
 * Authentication Module - AJAX Handler
 * 
 * Handles all AJAX requests for authentication
 * Uses OC_Authentication_CRUD for business logic
 * Uses OC_Email_Handler for email sending
 * 
 * @package Organization_Core
 * @subpackage Modules/Authentication
 */

if (!defined('WPINC')) die;
require_once plugin_dir_path(__FILE__) . 'crud.php';


class OC_Authentication_Ajax
{
    public function __construct()
    {
        // Email handler and SMTP are loaded on-demand
    }

    /**
     * Register all AJAX actions
     */
    public function init()
    {
        // ================================================
        // PUBLIC ACTIONS (no login required)
        // ================================================
        add_action('wp_ajax_nopriv_mus_process_login', array($this, 'handle_login'));
        add_action('wp_ajax_nopriv_mus_process_registration', array($this, 'handle_register'));
        add_action('wp_ajax_nopriv_mus_process_password_reset', array($this, 'handle_forgot_password'));
        add_action('wp_ajax_nopriv_mus_process_new_password', array($this, 'handle_reset_password'));

        // ================================================
        // PRIVATE ACTIONS (login required)
        // ================================================
        add_action('wp_ajax_mus_process_new_password', array($this, 'handle_reset_password'));
        add_action('wp_ajax_mus_update_profile', array($this, 'handle_update_profile'));
        add_action('wp_ajax_mus_schools_delete', array($this, 'handle_delete_schools'));
        add_action('wp_ajax_mus_cancel_booking', array($this, 'handle_cancel_booking'));
        add_action('wp_ajax_mus_process_logout', array($this, 'handle_logout'));

        //  Public fallbacks
        add_action('wp_ajax_nopriv_mus_schools_delete', array($this, 'handle_delete_schools'));
        add_action('wp_ajax_nopriv_mus_cancel_booking', array($this, 'handle_cancel_booking'));
        add_action('wp_ajax_nopriv_mus_process_logout', array($this, 'handle_logout'));
    }

    // ================================================
    // AUTHENTICATION HANDLERS
    // ================================================

    /**
     * Handle Login - AJAX Action: mus_process_login
     */
    public function handle_login()
    {
        $username = sanitize_user($_POST['username'] ?? '');
        $password = isset($_POST['password']) ? wp_unslash($_POST['password']) : '';
        $blog_id = intval($_POST['blog_id'] ?? get_current_blog_id());

        if (empty($username) || empty($password)) {
            wp_send_json_error(['message' => 'Username and password required']);
        }

        $current_blog_id = get_current_blog_id();
        if (is_multisite() && $blog_id != $current_blog_id) {
            switch_to_blog($blog_id);
        }

        $user = wp_signon([
            'user_login' => $username,
            'user_password' => $password,
            'remember' => isset($_POST['remember'])
        ]);

        if (is_multisite() && $blog_id != $current_blog_id) {
            restore_current_blog();
        }

        if (is_wp_error($user)) {
            $error_msg = $user->get_error_message();
            $clean_msg = wp_strip_all_tags($error_msg);

            $forgot_password_url = esc_url(get_site_url(null, 'forgot-password'));
            $forgot_password_link = sprintf(
                '<a href="%s" style="color: #0073aa; text-decoration: underline;">Lost your password?</a>',
                $forgot_password_url
            );

            $message_with_link = str_replace('Lost your password?', $forgot_password_link, $clean_msg);
            wp_send_json_error(['message' => $message_with_link]);
        }

        if (is_multisite() && !is_user_member_of_blog($user->ID, $blog_id)) {
            add_user_to_blog($blog_id, $user->ID, 'subscriber');
        }

        // ✅ TRIGGER LOGIN SUCCESS NOTIFICATION
        do_action('wp_login', $user->user_login, $user);

        wp_send_json_success([
            'message' => 'Login successful!',
            'redirect_url' => home_url('/my-account'),
            'user_id' => $user->ID
        ]);
    }

    /**
     * Handle Registration - AJAX Action: mus_process_registration
     */
    public function handle_register()
    {
        $username = sanitize_user($_POST['username'] ?? '');
        $email = sanitize_email($_POST['email'] ?? '');
        $password = isset($_POST['password']) ? wp_unslash($_POST['password']) : '';
        $confirm = isset($_POST['confirm_password']) ? wp_unslash($_POST['confirm_password']) : '';

        if (empty($username) || empty($email) || empty($password)) {
            wp_send_json_error(['message' => 'All fields are required']);
        }

        if ($password !== $confirm) {
            wp_send_json_error(['message' => 'Passwords do not match']);
        }

        if (strlen($password) < 6) {
            wp_send_json_error(['message' => 'Password must be at least 6 characters']);
        }

        if (username_exists($username)) {
            wp_send_json_error(['message' => 'Username already exists']);
        }

        if (email_exists($email)) {
            wp_send_json_error(['message' => 'Email already registered']);
        }

        $user_id = wp_create_user($username, $password, $email);

        if (is_wp_error($user_id)) {
            $clean_msg = wp_strip_all_tags($user_id->get_error_message());
            wp_send_json_error(['message' => $clean_msg]);
        }

        wp_signon(['user_login' => $username, 'user_password' => $password, 'remember' => true]);

        // ✅ TRIGGER REGISTRATION EMAIL NOTIFICATION
        $user_info = get_userdata($user_id);
        do_action('oc_user_registered', $user_id, array(
            'user_name' => $user_info->display_name,
            'user_email' => $user_info->user_email
        ));

        wp_send_json_success([
            'message' => 'Account created successfully!',
            'redirect_url' => home_url('/my-account'),
            'user_id' => $user_id
        ]);
    }



    /**
     * Handle Forgot Password - AJAX Action: mus_process_password_reset
     */
    public function handle_forgot_password()
    {
        check_ajax_referer('mus_nonce', 'nonce');

        $user_login = sanitize_text_field($_POST['user_login'] ?? '');

        if (empty($user_login)) {
            wp_send_json_error(['message' => 'Please enter username or email']);
        }

        $user = get_user_by('login', $user_login);
        if (!$user) {
            $user = get_user_by('email', $user_login);
        }

        if (!$user) {
            // For security, always show same message whether user exists or not
            wp_send_json_success(['message' => 'If your account exists, check your email for reset instructions']);
            return;
        }

        // Generate reset key
        $key = get_password_reset_key($user);
        if (is_wp_error($key)) {
            wp_send_json_error(['message' => 'Error generating password reset link']);
            return;
        }

        // Build reset URL using network site URL for multisite support
        $reset_url = add_query_arg(
            [
                'key' => $key,
                'login' => rawurlencode($user->user_login)
            ],
            network_site_url('reset-password')
        );

        // Prepare email data
        $site_name = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);
        $subject = sprintf('[%s] Password Reset Request', $site_name);

        // Template data
        $template_data = array(
            'user_data' => $user,
            'reset_url' => $reset_url,
            'site_name' => $site_name,
            'expires_in' => '24 hours'
        );

        // Load core email handler
        if (!class_exists('OC_Email_Handler')) {
            require_once ORGANIZATION_CORE_PLUGIN_DIR . 'includes/email/class-email-handler.php';
        }

        // Send email using core email handler
        $sent = OC_Email_Handler::send_with_template(
            $user->user_email,
            $subject,
            'password-reset.php',
            'authentication',
            $template_data
        );

        if ($sent) {
            error_log(' [AUTH] Password reset email sent successfully to: ' . $user->user_email);
        } else {
            error_log('❌ [AUTH] Failed to send password reset email to: ' . $user->user_email);
        }

        // For security, always show same message whether email sent or not
        wp_send_json_success(['message' => 'If your account exists, check your email for reset instructions']);
    }

    /**
     * Handle Password Reset - AJAX Action: mus_process_new_password
     */
    public function handle_reset_password()
    {
        $key = sanitize_text_field($_POST['key'] ?? '');
        $login = sanitize_text_field($_POST['login'] ?? '');
        $password = isset($_POST['password']) ? wp_unslash($_POST['password']) : '';
        $confirm = isset($_POST['confirm_password']) ? wp_unslash($_POST['confirm_password']) : '';

        if (empty($key) || empty($login) || empty($password)) {
            wp_send_json_error(['message' => 'Missing required information']);
        }

        if ($password !== $confirm) {
            wp_send_json_error(['message' => 'Passwords do not match']);
        }

        if (strlen($password) < 6) {
            wp_send_json_error(['message' => 'Password must be at least 6 characters']);
        }

        $user = check_password_reset_key($key, $login);

        if (is_wp_error($user)) {
            $clean_msg = wp_strip_all_tags($user->get_error_message());
            wp_send_json_error(['message' => $clean_msg]);
        }

        reset_password($user, $password);

        // ✅ TRIGGER PASSWORD CHANGED NOTIFICATION
        do_action('password_reset', $user, $password);

        wp_send_json_success([
            'message' => 'Password reset successfully! Redirecting to login...',
            'redirect_url' => home_url('/login')
        ]);
    }

    // ================================================
    // MY-ACCOUNT HANDLERS
    // ================================================

    /**
     * Handle Profile Update - AJAX Action: mus_update_profile
     */
    public function handle_update_profile()
    {
        // Verify nonce to protect against CSRF
        check_ajax_referer('mus_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('You must be logged in.', 'organization-core')]);
        }

        $user_id = get_current_user_id();

        $first_name = sanitize_text_field($_POST['first_name'] ?? '');
        $last_name = sanitize_text_field($_POST['last_name'] ?? '');
        $user_email = sanitize_email($_POST['user_email'] ?? '');
        $user_prefix = sanitize_text_field($_POST['user_prefix'] ?? '');
        $phone_number = sanitize_text_field($_POST['phone_number'] ?? '');
        $cell_number = sanitize_text_field($_POST['cell_number'] ?? '');
        $best_time_contact = sanitize_text_field($_POST['best_time_contact'] ?? '');

        if (empty($first_name) || empty($last_name) || empty($user_email) || empty($phone_number)) {
            wp_send_json_error(['message' => __('Required fields: First Name, Last Name, Email, and Contact Phone.', 'organization-core')]);
        }

        if (!is_email($user_email)) {
            wp_send_json_error(['message' => __('Please enter a valid email address.', 'organization-core')]);
        }

        $user_data = array(
            'ID' => $user_id,
            'first_name' => $first_name,
            'last_name' => $last_name,
            'user_email' => $user_email,
            'display_name' => $first_name . ' ' . $last_name
        );

        $result = wp_update_user($user_data);

        if (is_wp_error($result)) {
            wp_send_json_error(['message' => __('Failed to update profile: ', 'organization-core') . $result->get_error_message()]);
        }

        if (class_exists('OC_Authentication_CRUD')) {
            OC_Authentication_CRUD::update_user_additional_meta($user_id, array(
                'user_prefix' => $user_prefix,
                'phone_number' => $phone_number,
                'cell_number' => $cell_number,
                'best_time_contact' => $best_time_contact
            ));
        }

        // ✅ TRIGGER PROFILE UPDATED NOTIFICATION
        do_action('oc_profile_updated', $user_id, array(
            'first_name', 'last_name', 'email', 'phone_number'
        ));

        wp_send_json_success([
            'message' => __(' Profile updated successfully!', 'organization-core'),
            'user_id' => $user_id,
            'updated_at' => current_time('mysql')
        ]);
    }

    public function handle_delete_schools()
    {
        // Verify nonce to protect this action
        check_ajax_referer('mus_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => 'You must be logged in']);
        }

        $user_id = get_current_user_id();
        $school_id = intval($_POST['school_id'] ?? 0);

        if (empty($school_id)) {
            wp_send_json_error(['message' => 'School ID is required']);
        }

        require_once plugin_dir_path(__FILE__) . 'crud.php';

        if (!class_exists('OC_Authentication_CRUD')) {
            wp_send_json_error(['message' => 'CRUD class not available']);
        }

        $result = OC_Authentication_CRUD::delete_user_school($school_id, $user_id);


        if ($result) {
            wp_send_json_success([
                'message' => 'School deleted successfully',
                'school_id' => $school_id
            ]);
        } else {
            wp_send_json_error(['message' => 'Failed to delete school']);
        }
    }


    /**
     * Handle Cancel Booking - AJAX Action: mus_cancel_booking
     */
    public function handle_cancel_booking()
    {
        // Verify nonce to protect this action
        check_ajax_referer('mus_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => 'You must be logged in']);
        }

        $user_id = get_current_user_id();
        $blog_id = get_current_blog_id();
        $booking_id = intval($_POST['booking_id'] ?? 0);

        if (empty($booking_id)) {
            wp_send_json_error(['message' => 'Booking ID is required']);
        }

        require_once plugin_dir_path(__FILE__) . 'crud.php';

        if (!class_exists('OC_Authentication_CRUD')) {
            wp_send_json_error(['message' => 'CRUD class not available']);
        }

        if (OC_Authentication_CRUD::cancel_user_booking($booking_id, $user_id, $blog_id)) {
            wp_send_json_success([
                'message' => 'Booking cancelled successfully',
                'booking_id' => $booking_id
            ]);
        } else {
            wp_send_json_error(['message' => 'Failed to cancel booking']);
        }
    }

    /**
     * Handle Logout - AJAX Action: mus_process_logout
     */
    public function handle_logout()
    {
        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('You are not logged in.', 'organization-core')]);
        }

        wp_logout();

        wp_send_json_success([
            'message' => __(' Logged out successfully!', 'organization-core'),
            'redirect_url' => home_url('/login')
        ]);
    }
}
