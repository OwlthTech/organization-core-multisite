<?php

/**
 * Schools Module - AJAX Handler (FIXED)
 * 
 * Handles AJAX requests for school CRUD operations
 * 
 * @package Organization_Core
 * @subpackage Modules/Schools
 * @since 1.0.0
 */

if (! defined('WPINC')) {
    die;
}

class OC_Schools_AJAX
{
    private $module_id;

    public function __construct($module_id)
    {
        $this->module_id = $module_id;
    }

    public function init()
    {
        // Register AJAX handlers
        add_action('wp_ajax_mus_save_school', [$this, 'handle_save_school']);
        add_action('wp_ajax_nopriv_mus_save_school', [$this, 'handle_save_school']);

        add_action('wp_ajax_mus_update_school', [$this, 'handle_update_school']);
        add_action('wp_ajax_nopriv_mus_update_school', [$this, 'handle_update_school']);

        add_action('wp_ajax_mus_delete_school', [$this, 'handle_delete_school']);
        add_action('wp_ajax_nopriv_mus_delete_school', [$this, 'handle_delete_school']);
    }

    /**
     * Handle save school
     */
    public function handle_save_school()
    {

        // ✅ Verify nonce
        if (! oc_verify_ajax_nonce('mus_nonce', 'nonce')) {
            wp_send_json_error(['message' => 'Security check failed']);
        }

        // ✅ Check authentication
        if (! is_user_logged_in()) {
            wp_send_json_error(['message' => 'You must be logged in']);
        }

        $user_id = get_current_user_id();

        // ✅ Sanitize POST data
        $data = $this->sanitize_school_post_data($_POST);

        // ✅ Validate data
        $validation = OC_Schools_CRUD::validate_school_data($data);
        if (! $validation['valid']) {
            wp_send_json_error(['errors' => $validation['errors']]);
        }

        // ✅ Create school
        $school_id = OC_Schools_CRUD::create_school($user_id, $data);

        if ($school_id) {
            wp_send_json_success([
                'school_id' => $school_id,
                'message' => 'School created successfully',
                'redirect' => home_url('/my-account/schools')
            ]);
        } else {
            wp_send_json_error(['message' => 'Failed to create school']);
        }
    }

    /**
     * Handle update school
     */
    public function handle_update_school()
    {

        // ✅ Verify nonce
        if (! oc_verify_ajax_nonce('mus_nonce', 'nonce')) {
            wp_send_json_error(['message' => 'Security check failed']);
        }

        // ✅ Check authentication
        if (! is_user_logged_in()) {
            wp_send_json_error(['message' => 'You must be logged in']);
        }

        $user_id = get_current_user_id();
        $school_id = intval($_POST['school_id'] ?? 0);

        // ✅ Validate school_id
        if (empty($school_id)) {
            wp_send_json_error(['message' => 'School ID is required']);
        }

        // ✅ Check if school exists
        if (! OC_Schools_CRUD::school_exists($school_id, $user_id)) {
            wp_send_json_error(['message' => 'School not found']);
        }

        // ✅ Sanitize POST data
        $data = $this->sanitize_school_post_data($_POST);

        // ✅ Validate data
        $validation = OC_Schools_CRUD::validate_school_data($data);
        if (! $validation['valid']) {
            wp_send_json_error(['errors' => $validation['errors']]);
        }

        // ✅ Update school
        if (OC_Schools_CRUD::update_school($school_id, $user_id, $data)) {
            wp_send_json_success([
                'school_id' => $school_id,
                'message' => 'School updated successfully'
            ]);
        } else {
            wp_send_json_error(['message' => 'Failed to update school']);
        }
    }
    /**
     * Handle delete school AJAX request
     */
    public function handle_delete_school()
    {
        // ✅ Verify nonce
        if (! oc_verify_ajax_nonce('mus_nonce', 'nonce')) {
            wp_send_json_error([
                'message' => 'Security check failed - Invalid nonce'
            ]);
            return;
        }
        // ✅ Check authentication
        if (!is_user_logged_in()) {
            wp_send_json_error([
                'message' => 'You must be logged in'
            ]);
            return;
        }

        $user_id = get_current_user_id();

        // ✅ Get and validate school_id
        if (!isset($_POST['school_id'])) {
            wp_send_json_error([
                'message' => 'School ID is missing'
            ]);
            return;
        }

        $school_id = intval($_POST['school_id']);

        if (empty($school_id)) {
            wp_send_json_error([
                'message' => 'School ID invalid'
            ]);
            return;
        }
        // ✅ Call delete
        $deleted = delete_user_school($user_id, $school_id);

        if ($deleted) {
            wp_send_json_success([
                'message' => 'School deleted successfully!',
                'school_id' => $school_id
            ]);
        } else {
            wp_send_json_error([
                'message' => 'Failed to delete school'
            ]);
        }
    }

    /**
     * Sanitize POST data
     */
    private function sanitize_school_post_data($post)
    {
        return [
            'school_name' => sanitize_text_field($post['school_name'] ?? ''),
            'school_address' => sanitize_text_field($post['school_address'] ?? ''),
            'school_address_2' => sanitize_text_field($post['school_address_2'] ?? ''),
            'school_city' => sanitize_text_field($post['school_city'] ?? ''),
            'school_state' => sanitize_text_field($post['school_state'] ?? ''),
            'school_zip' => sanitize_text_field($post['school_zip'] ?? ''),
            'school_country' => sanitize_text_field($post['school_country'] ?? ''),
            'school_phone' => sanitize_text_field($post['school_phone'] ?? ''),
            'school_website' => esc_url_raw($post['school_website'] ?? ''),
            'director_prefix' => sanitize_text_field($post['director_prefix'] ?? ''),
            'director_first_name' => sanitize_text_field($post['director_first_name'] ?? ''),
            'director_last_name' => sanitize_text_field($post['director_last_name'] ?? ''),
            'director_email' => sanitize_email($post['director_email'] ?? ''),
            'director_cell_phone' => sanitize_text_field($post['director_cell_phone'] ?? ''),
            'principal_prefix' => sanitize_text_field($post['principal_prefix'] ?? ''),
            'principal_first_name' => sanitize_text_field($post['principal_first_name'] ?? ''),
            'principal_last_name' => sanitize_text_field($post['principal_last_name'] ?? ''),
            'school_enrollment' => intval($post['school_enrollment'] ?? 0),
            'school_notes' => sanitize_textarea_field($post['school_notes'] ?? '')
        ];
    }
}
