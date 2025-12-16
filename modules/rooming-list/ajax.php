<?php

/**
 * Rooming List AJAX Handler Class
 * 
 * @package    Organization_Core
 * @subpackage Rooming_List
 */

if (!defined('ABSPATH')) {
    exit;
}

class OC_Rooming_List_AJAX
{
    private $module_id;

    public function __construct($module_id)
    {
        $this->module_id = $module_id;

        // Register AJAX Handlers for both admin and logged-in users
        add_action('wp_ajax_save_rooming_list', array($this, 'handle_save_rooming_list'));
        add_action('wp_ajax_lock_rooming_list_item', array($this, 'handle_lock_rooming_list_item'));
        add_action('wp_ajax_export_rooming_list', array($this, 'handle_export_rooming_list'));
        add_action('wp_ajax_import_rooming_list', array($this, 'handle_import_rooming_list'));
        
        // Public AJAX handlers (for logged-in users on frontend)
        add_action('wp_ajax_save_rooming_list_public', array($this, 'handle_save_rooming_list_public'));
    }

    /**
     * Verify user owns the booking
     */
    private function verify_booking_ownership($booking_id, $user_id)
    {
        global $wpdb;
        $table_name = $wpdb->base_prefix . 'bookings';
        $blog_id = get_current_blog_id();

        $booking = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE id = %d AND blog_id = %d AND user_id = %d",
            $booking_id,
            $blog_id,
            $user_id
        ));

        return !empty($booking);
    }

    /**
     * Handle Save Rooming List (Admin)
     */
    public function handle_save_rooming_list()
    {
        check_ajax_referer('booking_rooming_list_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission denied');
        }

        $booking_id = isset($_POST['booking_id']) ? intval($_POST['booking_id']) : 0;
        $rooming_list = isset($_POST['rooming_list']) ? $_POST['rooming_list'] : [];

        if (!$booking_id) {
            wp_send_json_error('Invalid booking ID');
        }

        require_once plugin_dir_path(__FILE__) . 'crud.php';
        $result = OC_Rooming_List_CRUD::update_rooming_list($booking_id, $rooming_list);

        if ($result) {
            wp_send_json_success(['message' => 'Rooming list saved successfully']);
        } else {
            wp_send_json_error('Failed to save rooming list');
        }
    }

    /**
     * Handle Lock/Unlock Item
     */
    public function handle_lock_rooming_list_item()
    {
        check_ajax_referer('booking_rooming_list_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission denied');
        }

        $item_id = isset($_POST['item_id']) ? intval($_POST['item_id']) : 0;
        $is_locked = isset($_POST['is_locked']) ? intval($_POST['is_locked']) : 0;

        if (!$item_id) {
            wp_send_json_error('Invalid item ID');
        }

        require_once plugin_dir_path(__FILE__) . 'crud.php';
        $result = OC_Rooming_List_CRUD::toggle_lock_rooming_list_item($item_id, $is_locked);

        if ($result !== false) {
            wp_send_json_success(['message' => 'Item lock status updated']);
        } else {
            wp_send_json_error('Failed to update lock status');
        }
    }

    /**
     * Handle Export Rooming List (CSV)
     */
    public function handle_export_rooming_list()
    {
        // Verify nonce using the correct action name shared with JS
        check_ajax_referer('booking_rooming_list_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_die('Permission denied');
        }

        $booking_id = isset($_GET['booking_id']) ? intval($_GET['booking_id']) : 0;
        if (!$booking_id) {
            wp_die('Invalid Booking ID');
        }

        require_once plugin_dir_path(__FILE__) . 'crud.php';
        $list = OC_Rooming_List_CRUD::get_rooming_list($booking_id);
        
        // Filename: rooming-list-{booking_id}-{date}.csv
        $filename = 'rooming-list-' . $booking_id . '-' . date('Y-m-d') . '.csv';

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $output = fopen('php://output', 'w');
        
        // CSV Headers
        fputcsv($output, ['Room Number', 'Occupant Name', 'Occupant Type']);

        $last_room_number = '';

        foreach ($list as $row) {
            $current_room_number = $row['room_number'];
            
            // If room number is same as last one, leave it empty for visual grouping
            $display_room_number = ($current_room_number === $last_room_number) ? '' : $current_room_number;
            
            fputcsv($output, [
                $display_room_number,
                $row['occupant_name'],
                $row['occupant_type']
            ]);

            $last_room_number = $current_room_number;
        }

        fclose($output);
        exit;
    }

    /**
     * Handle Import Rooming List
     */
    public function handle_import_rooming_list()
    {
        check_ajax_referer('booking_rooming_list_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission denied');
        }

        $booking_id = isset($_POST['booking_id']) ? intval($_POST['booking_id']) : 0;
        if (!$booking_id) {
            wp_send_json_error('Invalid Booking ID');
        }

        if (empty($_FILES['import_file'])) {
            wp_send_json_error('No file uploaded');
        }

        $file = $_FILES['import_file'];
        if ($file['error'] !== UPLOAD_ERR_OK) {
            wp_send_json_error('File upload error');
        }

        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        if (strtolower($ext) !== 'csv') {
            wp_send_json_error('Please upload a CSV file');
        }

        $handle = fopen($file['tmp_name'], 'r');
        if ($handle === false) {
            wp_send_json_error('Could not open file');
        }

        // Read Header
        $header = fgetcsv($handle);
        
        $new_list = [];
        $current_room_number = ''; // State to track the current room context
        
        while (($row = fgetcsv($handle)) !== false) {
            // Skip empty rows
            if (empty($row) || (count($row) === 1 && empty($row[0]))) continue;

            // Map columns
            $room_col = isset($row[0]) ? trim(sanitize_text_field($row[0])) : '';
            $occupant_name = isset($row[1]) ? trim(sanitize_text_field($row[1])) : '';
            $occupant_type = isset($row[2]) ? trim(sanitize_text_field($row[2])) : 'Student';

            // If room column is not empty, update current room context
            if ($room_col !== '') {
                $current_room_number = $room_col;
            }

            // If we still don't have a room number (e.g. first row empty), skip or handle?
            // Let's skip if no room number context exists yet.
            if ($current_room_number === '') continue;

            // Validate Occupant Type
            $allowed_types = ['Student', 'Chaperone', 'Director', 'Other'];
            if (!in_array($occupant_type, $allowed_types)) {
                $occupant_type = 'Student'; // Default fallback
            }

            // Skip if name is empty (unless we want to allow empty slots? User usually imports names)
            if ($occupant_name === '') continue; 

            $new_list[] = [
                'room_number' => $current_room_number,
                'occupant_name' => $occupant_name,
                'occupant_type' => $occupant_type,
                'is_locked' => 0 // Reset lock on import
            ];
        }
        fclose($handle);

        if (empty($new_list)) {
            wp_send_json_error('No valid data found in CSV');
        }

        require_once plugin_dir_path(__FILE__) . 'crud.php';
        
        // Update (Replace) Rooming List
        $result = OC_Rooming_List_CRUD::update_rooming_list($booking_id, $new_list);

        if ($result) {
            wp_send_json_success(['message' => 'Import successful', 'count' => count($new_list)]);
        } else {
            wp_send_json_error('Import failed to save data');
        }
    }

    /**
     * Handle Save Rooming List (Public - for booking owners)
     */
    public function handle_save_rooming_list_public()
    {
        check_ajax_referer('rooming_list_public_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error('You must be logged in');
        }

        $booking_id = isset($_POST['booking_id']) ? intval($_POST['booking_id']) : 0;
        $rooming_list = isset($_POST['rooming_list']) ? $_POST['rooming_list'] : [];
        $user_id = get_current_user_id();

        if (!$booking_id) {
            wp_send_json_error('Invalid booking ID');
        }

        // Verify user owns this booking
        if (!$this->verify_booking_ownership($booking_id, $user_id)) {
            wp_send_json_error('You do not have permission to modify this rooming list');
        }

        require_once plugin_dir_path(__FILE__) . 'crud.php';
        $result = OC_Rooming_List_CRUD::update_rooming_list($booking_id, $rooming_list);

        if ($result) {
            wp_send_json_success(['message' => 'Rooming list saved successfully']);
        } else {
            wp_send_json_error('Failed to save rooming list');
        }
    }
}
