<?php

/**
 * Authentication Public Class - Complete Working Version
 * 
 * Adapted from old owlth-multisite-user-sync-public.php
 * All working logic preserved and integrated
 *
 * @package Organization_Core
 * @subpackage Modules/Authentication
 */

if (!defined('WPINC')) {
    die;
}

class OC_Authentication_Public extends OC_Abstract_Module
{
    private $authentication_crud;

    public function __construct($module_id, $version)
    {
        parent::__construct();
        $this->module_id = $module_id;
        $this->version = $version;

        // Initialize Booking CRUD
        $this->authentication_crud = new OC_Authentication_CRUD();
    }

    /**
     * Get module ID
     */
    public function get_module_id()
    {
        return $this->module_id;
    }

    /**
     * Get module configuration
     */
    public function get_config()
    {
        return array(
            'id' => $this->module_id,
            'version' => $this->version
        );
    }

    /**
     * Initialize public hooks
     */
    public function init()
    {

        // Session management
        add_action('init', array($this, 'start_session'), 1);

        // Enqueue assets
        add_action('wp_enqueue_scripts', array($this, 'enqueue_styles'), 10);
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'), 10);

        // Register shortcodes
        $this->register_shortcodes();
    }

    /**
     * Start session
     */
    public function start_session()
    {
        if (!session_id()) {
            session_start();
        }

        if (is_user_logged_in()) {
            $user_id = get_current_user_id();
            $blog_id = get_current_blog_id();

            if (isset($_SESSION['redirect_url'])) {
                update_user_meta($user_id, "redirect_url_blog_{$blog_id}", $_SESSION['redirect_url']);
            }
        }
    }

    /**
     * Register all shortcodes
     */
    private function register_shortcodes()
    {

        add_shortcode('mus_login_form', array($this, 'render_login_form'));

        add_shortcode('mus_registration_form', array($this, 'render_registration_form'));

        add_shortcode('mus_restore_password', array($this, 'render_restore_password_form'));

        add_shortcode('mus_reset_password_form', array($this, 'render_reset_password_form'));

        add_shortcode('mus_my_account', array($this, 'render_my_account'));
    }


    /**
     * Enqueue CSS styles
     */
    public function enqueue_styles()
    {
        $css_url = plugin_dir_url(dirname(__FILE__)) . '/assets/css/authentication.css';


        wp_enqueue_style(
            'mus-auth-public',
            $css_url,
            array(),
            $this->version,
            'all'
        );
    }

    /**
     * Enqueue JavaScript
     */
    public function enqueue_scripts()
    {
        if (!wp_script_is('jquery', 'enqueued')) {
            wp_enqueue_script('jquery');
        }

        $js_base_url = plugin_dir_url(dirname(__FILE__)) . 'assets/js/';

        wp_enqueue_script(
            'mus-auth-public',
            $js_base_url . 'authentication.js',
            array('jquery'),
            $this->version,
            true
        );

        // ✅ Conditional script loading
        global $post;
        if (is_a($post, 'WP_Post')) {
            $content = $post->post_content;
            if (has_shortcode($content, 'mus_my_account')) {
                wp_enqueue_script(
                    'mus-auth-my-account',
                    $js_base_url . 'my-account.js',
                    array('jquery', 'mus-auth-public'),
                    $this->version,
                    true
                );
            }

            if (has_shortcode($content, 'mus_registration_form')) {
                wp_enqueue_script(
                    'mus-auth-signup',
                    $js_base_url . 'signup.js',
                    array('jquery', 'mus-auth-public'),
                    $this->version,
                    true
                );
            }

            if (has_shortcode($content, 'mus_restore_password')) {
                wp_enqueue_script(
                    'mus-auth-forgot-password',
                    $js_base_url . 'forgot-password.js',
                    array('jquery', 'mus-auth-public'),
                    $this->version,
                    true
                );
            }

            if (has_shortcode($content, 'mus_reset_password_form')) {
                wp_enqueue_script(
                    'mus-auth-reset-password',
                    $js_base_url . 'reset-password.js',
                    array('jquery', 'mus-auth-public'),
                    $this->version,
                    true
                );
            }
        }

        // ✅ CRITICAL: Localize with correct nonce key
        wp_localize_script('mus-auth-public', 'mus_params', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'home_url' => home_url(),
            'redirect_url' => isset($_GET['redirect_to']) ? esc_url($_GET['redirect_to']) : home_url(),
            'nonce' => wp_create_nonce('mus_nonce')  // ✅ Match AJAX action check
        ));
    }


    // ============================================
    // SHORTCODE RENDERERS
    // ============================================

    /**
     * Render login form - [mus_login_form]
     */
    public function render_login_form()
    {
        if (is_user_logged_in()) {
            return '<div class="mus-message mus-info">' . esc_html__('You are already logged in.', 'organization-core') . '</div>';
        }

        return $this->load_template('login.php', array());
    }

    /**
     * Render registration form - [mus_registration_form]
     */
    public function render_registration_form()
    {
        if (is_user_logged_in()) {
            return '<div class="mus-message mus-info">' . esc_html__('You are already registered.', 'organization-core') . '</div>';
        }

        return $this->load_template('signup.php', array());
    }

    /**
     * Render forgot password form - [mus_restore_password]
     */
    public function render_restore_password_form()
    {
        return $this->load_template('forgot-password.php', array());
    }

    /**
     * Render reset password form - [mus_reset_password_form]
     */
    public function render_reset_password_form()
    {
        $reset_key = isset($_GET['key']) ? sanitize_text_field(wp_unslash($_GET['key'])) : '';
        $reset_login = isset($_GET['login']) ? sanitize_text_field(wp_unslash($_GET['login'])) : '';

        return $this->load_template('reset-password.php', array(
            'reset_key' => $reset_key,
            'reset_login' => $reset_login
        ));
    }
    /**
     * Render my account page - [mus_my_account]
     */
    public function render_my_account($atts = array(), $content = '', $tag = '')
    {
        if (!is_user_logged_in()) {
            return do_shortcode('[mus_login_form]');
        }

        $user_id = get_current_user_id();
        $blog_id = get_current_blog_id();

        // ✅ Import CRUD class
        require_once plugin_dir_path(dirname(__FILE__)) . 'crud.php';

        // Get user profile data
        $user_profile = $this->authentication_crud->get_user_profile($user_id);

        // Get additional user meta
        $additional_meta = $this->authentication_crud->get_user_additional_meta($user_id);

        // Merge additional meta with profile
        $user_profile = array_merge($user_profile, $additional_meta);

        // Get user quotes (limited for dashboard)
        $dashboard_quotes = $this->authentication_crud->get_user_quotes_limited($user_id, 3);

        // Get total quotes count
        $total_quotes = $this->authentication_crud->get_user_quotes_count($user_id);

        // Get quote status counts
        $quote_counts = $this->authentication_crud->count_user_quotes_by_status($user_id, $blog_id);

        // Get user schools from wp_schools table
        $user_schools = $this->authentication_crud->get_user_schools_for_display($user_id, $blog_id);
        $total_schools = $this->authentication_crud->get_user_schools_count($user_id, $blog_id);

        // Get limited schools for dashboard
        $dashboard_schools = $this->authentication_crud->get_dashboard_schools($user_id, 2, $blog_id);
        $dashboard_bookings = $this->authentication_crud->get_user_bookings($user_id, $blog_id);
        // Prepare template args
        $args = array(
            'user_profile' => $user_profile,
            'dashboard_quotes' => $dashboard_quotes,
            'total_quotes' => $total_quotes,
            'quote_counts' => $quote_counts,
            'user_schools' => $user_schools,
            'total_schools' => $total_schools,
            'dashboard_schools' => $dashboard_schools,
            'dashboard_bookings' => $dashboard_bookings,
            'user_id' => $user_id,
            'blog_id' => $blog_id,
        );

        return $this->load_template('my-account.php', $args);
    }


    // ============================================
    // TEMPLATE LOADER
    // ============================================

    /**
     * Load and render template file
     * 
     * @param string $template_name Template filename
     * @param array $args Variables to pass to template
     * @return string Rendered HTML
     */
    private function load_template($template_name, $args = array())
    {
        ob_start();

        // Look for template in theme first
        $theme_template = locate_template(array(
            'organization-core/authentication/public/' . $template_name,
            'travel/organization-core/authentication/public/' . $template_name
        ));

        if ($theme_template) {
            // Extract args to make them available in template
            if (!empty($args) && is_array($args)) {
                extract($args);
            }
            include $theme_template;
        } else {
            // Fallback to module template using parent class method
            $this->get_template(
                $template_name,
                $args,
                'authentication',  // module id
                plugin_dir_path(dirname(__FILE__)) . 'templates/public/'  // default path
            );
        }

        $output = ob_get_clean();
        if (!$output) {
            return '<p class="mus-error">' . esc_html__('Error loading template', 'organization-core') . '</p>';
        }
        return $output;
    }

    // ============================================
    // AJAX HANDLERS - AUTHENTICATION
    // ============================================

    /**
     * Process login - AJAX action: mus_process_login
     */
    public function process_login()
    {
        check_ajax_referer('mus_nonce', 'nonce');

        $username = sanitize_user($_POST['username'] ?? '');
        $password = isset($_POST['password']) ? wp_unslash($_POST['password']) : '';
        $remember = isset($_POST['remember']) ? true : false;
        $blog_id = isset($_POST['blog_id']) ? intval($_POST['blog_id']) : get_current_blog_id();

        // Validate input
        if (empty($username) || empty($password)) {
            wp_send_json_error(array(
                'message' => __('Username and password are required.', 'organization-core')
            ));
        }

        // Multisite handling
        if (is_multisite()) {
            switch_to_blog($blog_id);
        }

        // Attempt login
        $user = wp_signon(array(
            'user_login' => $username,
            'user_password' => $password,
            'remember' => $remember
        ));

        // Handle error
        if (is_wp_error($user)) {
            if (is_multisite()) {
                restore_current_blog();
            }
            wp_send_json_error(array(
                'message' => $user->get_error_message()
            ));
        }

        // Add user to blog if multisite
        if (is_multisite() && !is_wp_error($user)) {
            if (!is_user_member_of_blog($user->ID, $blog_id)) {
                add_user_to_blog($blog_id, $user->ID, 'subscriber');
            }
            restore_current_blog();
        }

        // Determine redirect URL
        $redirect_url = home_url('/my-account');

        if (!empty($_POST['redirect_to'])) {
            $redirect_url = esc_url_raw(wp_unslash($_POST['redirect_to']));
        } elseif (!empty($_SESSION['redirect_url'])) {
            $redirect_url = esc_url_raw($_SESSION['redirect_url']);
            unset($_SESSION['redirect_url']);
        }


        wp_send_json_success(array(
            'message' => __('Login successful! Redirecting...', 'organization-core'),
            'redirect_url' => $redirect_url,
            'user_id' => $user->ID,
            'user_name' => $user->display_name
        ));
    }

    /**
     * Process registration - AJAX action: mus_process_registration
     */
    public function process_registration()
    {
        check_ajax_referer('mus_nonce', 'nonce');

        $username = sanitize_user($_POST['username'] ?? '');
        $email = sanitize_email($_POST['email'] ?? '');
        $password = isset($_POST['password']) ? wp_unslash($_POST['password']) : '';

        // Validate input
        if (empty($username) || empty($email) || empty($password)) {
            wp_send_json_error(array(
                'message' => __('All required fields must be filled.', 'organization-core')
            ));
        }

        // Validate email
        if (!is_email($email)) {
            wp_send_json_error(array(
                'message' => __('Please enter a valid email address.', 'organization-core')
            ));
        }

        // Register user
        $user_id = register_new_user($username, $email);

        if (is_wp_error($user_id)) {
            wp_send_json_error(array(
                'message' => $user_id->get_error_message()
            ));
        }

        // Get user object
        $user = get_user_by('id', $user_id);

        // Set user role
        $user->set_role('subscriber');

        // Set password
        wp_set_password($password, $user_id);

        // Auto-login
        wp_signon(array(
            'user_login' => $username,
            'user_password' => $password,
            'remember' => true
        ));

        // Determine redirect URL
        $redirect_url = home_url('/my-account');

        if (!empty($_POST['redirect_to'])) {
            $redirect_url = esc_url_raw(wp_unslash($_POST['redirect_to']));
        } elseif (!empty($_SESSION['redirect_url'])) {
            $redirect_url = esc_url_raw($_SESSION['redirect_url']);
            unset($_SESSION['redirect_url']);
        }

        wp_send_json_success(array(
            'message' => __('Registration successful! Redirecting...', 'organization-core'),
            'redirect_url' => $redirect_url,
            'user_id' => $user_id
        ));
    }

    /**
     * Process logout - AJAX action: mus_process_logout
     */
    public function process_logout()
    {
        check_ajax_referer('mus_nonce', 'nonce');
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => __('You are not logged in.', 'your-plugin-textdomain')));
        }
        wp_logout();
        wp_send_json_success(array('message' => __('Logout successful.', 'your-plugin-textdomain')));
    }


    /**
     * Process new password after reset - AJAX action: mus_process_new_password
     */
    public function process_new_password()
    {
        check_ajax_referer('mus_nonce', 'nonce');

        $reset_key = sanitize_text_field($_POST['key'] ?? '');
        $reset_login = sanitize_text_field($_POST['login'] ?? '');
        $password = isset($_POST['password']) ? wp_unslash($_POST['password']) : '';
        $password_confirm = isset($_POST['password_confirm']) ? wp_unslash($_POST['password_confirm']) : '';


        if (empty($password) || empty($password_confirm)) {
            wp_send_json_error(array(
                'message' => __('Please enter and confirm your new password.', 'organization-core')
            ));
        }

        if ($password !== $password_confirm) {
            wp_send_json_error(array(
                'message' => __('Passwords do not match.', 'organization-core')
            ));
        }

        // Verify reset key
        $user = check_password_reset_key($reset_key, $reset_login);

        if (is_wp_error($user)) {
            wp_send_json_error(array(
                'message' => __('Your password reset link appears to be invalid or has expired.', 'organization-core')
            ));
        }

        // Reset password
        reset_password($user, $password);

        wp_send_json_success(array(
            'message' => __('Your password has been reset successfully. You can now log in.', 'organization-core'),
            'redirect_url' => home_url('/login')
        ));
    }

    /**
     * Update user profile - AJAX action: mus_update_profile
     */
    public function update_profile()
    {
        check_ajax_referer('mus_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(array(
                'message' => __('You must be logged in.', 'organization-core')
            ));
        }

        $user_id = get_current_user_id();

        $first_name = sanitize_text_field($_POST['first_name'] ?? '');
        $last_name = sanitize_text_field($_POST['last_name'] ?? '');
        $user_email = sanitize_email($_POST['user_email'] ?? '');

        // Validate email
        if (empty($user_email) || !is_email($user_email)) {
            wp_send_json_error(array(
                'message' => __('Please enter a valid email address.', 'organization-core')
            ));
        }

        // Update user
        $result = wp_update_user(array(
            'ID' => $user_id,
            'first_name' => $first_name,
            'last_name' => $last_name,
            'user_email' => $user_email,
            'display_name' => $first_name . ' ' . $last_name
        ));

        if (is_wp_error($result)) {
            wp_send_json_error(array(
                'message' => __('Failed to update profile.', 'organization-core')
            ));
        }

        wp_send_json_success(array(
            'message' => __('Profile updated successfully!', 'organization-core')
        ));
    }

    /**
     * Change password - AJAX action: mus_change_password
     */
    public function change_password()
    {
        check_ajax_referer('mus_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(array(
                'message' => __('You must be logged in.', 'organization-core')
            ));
        }

        $user_id = get_current_user_id();
        $current = isset($_POST['current_password']) ? wp_unslash($_POST['current_password']) : '';
        $new = isset($_POST['new_password']) ? wp_unslash($_POST['new_password']) : '';
        $confirm = isset($_POST['confirm_password']) ? wp_unslash($_POST['confirm_password']) : '';

        if (empty($current) || empty($new) || empty($confirm)) {
            wp_send_json_error(array(
                'message' => __('All password fields are required.', 'organization-core')
            ));
        }

        if ($new !== $confirm) {
            wp_send_json_error(array(
                'message' => __('New passwords do not match.', 'organization-core')
            ));
        }

        // Verify current password
        $user = get_user_by('id', $user_id);

        if (!wp_check_password($current, $user->user_pass, $user_id)) {
            wp_send_json_error(array(
                'message' => __('Current password is incorrect.', 'organization-core')
            ));
        }

        // Set new password
        wp_set_password($new, $user_id);

        // Re-authenticate user
        wp_set_current_user($user_id);
        wp_set_auth_cookie($user_id);

        wp_send_json_success(array(
            'message' => __('Password changed successfully!', 'organization-core')
        ));
    }

    // ============================================
    // UTILITY METHODS
    // ============================================

    /**
     * Send password reset email using core handler
     */
    private function send_password_reset_email($user_data, $reset_url)
    {
        if (!class_exists('OC_Email_Handler')) {
            require_once ORGANIZATION_CORE_PLUGIN_DIR . 'includes/email/class-email-handler.php';
        }

        return OC_Email_Handler::send_with_template(
            $user_data->user_email,
            sprintf(__('[%s] Password Reset', 'organization-core'), get_bloginfo('name')),
            'password-reset.php',
            'authentication',
            array(
                'user_name' => $user_data->display_name ?: $user_data->user_login,
                'site_name' => get_bloginfo('name'),
                'reset_url' => $reset_url,
                'expires_in' => '24 hours'
            )
        );
    }
    // }

    /**
     * Check user login status
     */
    public function check_user_login_status()
    {
        check_ajax_referer('mus_nonce', 'nonce');

        wp_send_json_success(array(
            'logged_in' => is_user_logged_in(),
            'user_id' => get_current_user_id()
        ));
    }

    /**
     * Store redirect URL
     */
    public function store_redirect_url()
    {
        check_ajax_referer('mus_nonce', 'nonce');

        $redirect_url = sanitize_url($_POST['redirect_url'] ?? '');

        if ($redirect_url) {
            $_SESSION['redirect_url'] = $redirect_url;
        }

        wp_send_json_success(array(
            'message' => 'Redirect URL stored'
        ));
    }

    /**
     * Check auth status
     */
    private function check_auth_status()
    {
        $is_logged_in = is_user_logged_in();
        $response = array('is_logged_in' => $is_logged_in);

        if ($is_logged_in) {
            $user = wp_get_current_user();
            $response['user'] = array(
                'display_name' => $user->display_name,
                'email' => $user->user_email,
                'id' => $user->ID
            );
        }

        return $response;
    }
}
