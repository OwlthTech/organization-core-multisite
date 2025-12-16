<?php

/**
 * Bookings AJAX Handler Class
 * Updated for Schools Database Table
 * 
 * @package    Organization_Core
 * @subpackage Bookings
 */

if (!defined('ABSPATH')) {
    exit;
}

class OC_Bookings_AJAX
{
    private $module_id;

    /**
     * Register all AJAX actions
     */
    public function __construct($module_id)
    {
        $this->module_id = $module_id;

        // Create booking actions
        add_action('wp_ajax_create_booking', array($this, 'handle_create_booking'));
        add_action('wp_ajax_nopriv_create_booking', array($this, 'handle_create_booking'));

        // Get bookings actions
        add_action('wp_ajax_get_bookings', array($this, 'handle_get_bookings'));
        
        // Cancel booking actions
        add_action('wp_ajax_cancel_booking', array($this, 'handle_cancel_booking'));

        // School management actions
        add_action('wp_ajax_save_user_school', array($this, 'handle_save_school'));
        add_action('wp_ajax_load_user_schools', array($this, 'handle_get_schools_of_current_user'));
        add_action('wp_ajax_delete_user_school', array($this, 'handle_delete_school'));
        add_action('wp_ajax_update_user_school', array($this, 'handle_update_school'));
        add_action('wp_ajax_get_user_schools', array($this, 'handle_get_schools'));

        // Package datahandle_save_booking_draft
        add_action('wp_ajax_get_package_data', array($this, 'handle_get_package_data'));
        add_action('wp_ajax_nopriv_get_package_data', array($this, 'handle_get_package_data'));

        // ✅ NEW: Draft handlers (FIXED - now in class)
        add_action('wp_ajax_save_booking_draft', array($this, 'handle_save_booking_draft'));
        add_action('wp_ajax_get_booking_draft', array($this, 'handle_get_booking_draft'));

        // Register AJAX handler for price updates
        add_action('wp_ajax_save_booking_price', array($this, 'save_booking_price'));

        // Register AJAX handler for email sending
        add_action('wp_ajax_send_booking_email', array($this, 'handle_send_booking_email'));

        add_action('wp_ajax_update_booking_status', array($this, 'ajax_update_booking_status'));
        add_action('wp_ajax_delete_booking', array($this, 'ajax_delete_booking'));
    }

    // ========================================
    // BOOKING HANDLERS
    // ========================================

    /**
     * Handle create booking request
     */
    /**
     * ✅ COMPLETELY FIXED: Handle create booking with draft confirmation
     */
    public function handle_create_booking()
    {
        try {
            check_ajax_referer('bookings_nonce', 'nonce');

            if (!is_user_logged_in()) {
                wp_send_json_error(
                    array('message' => __('You must be logged in to make a booking.', 'organization-core')),
                    401
                );
            }

            $user_id = get_current_user_id();
            $blog_id = get_current_blog_id();
            $user = get_userdata($user_id);

            // ✅ Check if this is a draft confirmation
            $draft_id = isset($_POST['draft_id']) ? intval($_POST['draft_id']) : 0;

            if ($draft_id > 0) {
                // Get draft data for validation
                global $wpdb;
                $table_name = $wpdb->base_prefix . 'bookings';
                $draft = $wpdb->get_row($wpdb->prepare(
                    "SELECT * FROM $table_name WHERE id = %d AND user_id = %d AND status = 'draft'",
                    $draft_id,
                    $user_id
                ));

                if (!$draft) {
                    wp_send_json_error(array('message' => __('Draft not found or already submitted.', 'organization-core')));
                    return;
                }

                // ✅ FIXED: Decode booking_data to extract ALL fields
                $booking_data = json_decode($draft->booking_data, true);

                // Sanitize any final input (like ensembles)
                $final_input = $this->sanitize_booking_input($_POST);

                // ✅ CRITICAL FIX: Extract and save lodging_dates and special_notes to table columns
                $update_data = array(
                    'status' => 'pending',
                    'modified_at' => current_time('mysql'),
                    'lodging_dates' => isset($booking_data['lodging_dates']) ? $booking_data['lodging_dates'] : '', // ✅ ADDED
                    'special_notes' => isset($booking_data['special_notes']) ? $booking_data['special_notes'] : '', // ✅ ADDED
                );

                // ✅ Also extract other fields from booking_data to table columns
                if (isset($booking_data['meal_vouchers'])) {
                    $update_data['meal_vouchers'] = intval($booking_data['meal_vouchers']);
                }
                if (isset($booking_data['meals_per_day'])) {
                    $update_data['meals_per_day'] = intval($booking_data['meals_per_day']);
                }
                if (isset($booking_data['parks_selection'])) {
                    $update_data['parks_selection'] = wp_json_encode($booking_data['parks_selection']);
                }
                if (isset($booking_data['other_park_name'])) {
                    $update_data['other_park_name'] = $booking_data['other_park_name'];
                }
                if (isset($booking_data['park_meal_options'])) {
                    $update_data['park_meal_options'] = wp_json_encode($booking_data['park_meal_options']);
                }

                // If ensembles are provided in final submission, update them
                if (isset($final_input['ensembles']) && !empty($final_input['ensembles'])) {
                    $update_data['ensembles'] = wp_json_encode($final_input['ensembles']);
                    $booking_data['ensembles'] = $final_input['ensembles']; // Update for response
                }

                // ✅ FIXED: Define format array for wpdb->update
                $format = array(
                    '%s', // status
                    '%s', // modified_at
                    '%s', // lodging_dates
                    '%s', // special_notes
                );

                // Add format specifiers for optional fields
                if (isset($update_data['meal_vouchers']))
                    $format[] = '%d';
                if (isset($update_data['meals_per_day']))
                    $format[] = '%d';
                if (isset($update_data['parks_selection']))
                    $format[] = '%s';
                if (isset($update_data['other_park_name']))
                    $format[] = '%s';
                if (isset($update_data['park_meal_options']))
                    $format[] = '%s';
                if (isset($update_data['ensembles']))
                    $format[] = '%s';

                $result = $wpdb->update(
                    $table_name,
                    $update_data,
                    array(
                        'id' => $draft_id,
                        'user_id' => $user_id
                    ),
                    $format, // ✅ ADDED: Format for update data
                    array('%d', '%d') // Format for WHERE clause
                );

                if ($result !== false) {

                    // Fire custom hook for emails
                    do_action('org_core_booking_created', $draft_id, $booking_data, $user, $blog_id);

                    // Prepare response
                    $response_data = $this->prepare_booking_response($draft_id, $booking_data, $final_input);

                    wp_send_json_success($response_data);
                } else {
                    wp_send_json_error(array('message' => __('Failed to confirm booking.', 'organization-core')));
                }
            } else {
                // Sanitize input
                $input_data = $this->sanitize_booking_input($_POST);

                // Validate input
                $validation = $this->validate_booking_input($input_data);
                if (is_wp_error($validation)) {
                    wp_send_json_error(array('message' => $validation->get_error_message()));
                }

                // Prepare booking data
                $booking_data = $this->prepare_booking_data($input_data, $user_id, $blog_id);
                $booking_data['status'] = 'pending'; // ✅ Direct to pending if no draft

                // Create booking
                require_once plugin_dir_path(__FILE__) . 'crud.php';
                $booking_id = OC_Bookings_CRUD::create_booking($blog_id, $booking_data);

                if (!$booking_id) {
                    wp_send_json_error(
                        array('message' => __('Failed to save booking. Please try again.', 'organization-core'))
                    );
                }

                // Fire custom hook
                do_action('org_core_booking_created', $booking_id, $booking_data, $user, $blog_id);

                // Prepare response
                $response_data = $this->prepare_booking_response($booking_id, $booking_data, $input_data);

                wp_send_json_success($response_data);
            }
        } catch (Exception $e) {
            wp_send_json_error(array('message' => __('An error occurred. Please try again.', 'organization-core')));
        }
    }


    /**
     * Handle get bookings request
     */
    public function handle_get_bookings()
    {
        try {
            check_ajax_referer('bookings_nonce', 'nonce');

            if (!is_user_logged_in()) {
                wp_send_json_error(
                    array('message' => __('Authentication required', 'organization-core')),
                    401
                );
            }

            $blog_id = get_current_blog_id();
            $user_id = get_current_user_id();
            $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
            $per_page = isset($_POST['per_page']) ? intval($_POST['per_page']) : 10;
            $offset = ($page - 1) * $per_page;

            require_once plugin_dir_path(__FILE__) . 'crud.php';

            $args = array(
                'user_id' => $user_id,
                'number' => $per_page,
                'offset' => $offset,
            );

            if (!empty($_POST['status'])) {
                $args['status'] = sanitize_text_field($_POST['status']);
            }

            $bookings = OC_Bookings_CRUD::get_bookings($blog_id, $args);
            $processed_bookings = $this->process_bookings_for_display($bookings);

            $total_items = OC_Bookings_CRUD::count_bookings($blog_id);
            $total_pages = ceil($total_items / $per_page);

            wp_send_json_success(array(
                'bookings' => $processed_bookings,
                'pagination' => array(
                    'current_page' => $page,
                    'total_pages' => $total_pages,
                    'total_items' => $total_items,
                    'per_page' => $per_page,
                ),
            ));
        } catch (Exception $e) {
            wp_send_json_error(array('message' => __('Failed to retrieve bookings.', 'organization-core')));
        }
    }

    /**
     * Handle cancel booking request
     */
    public function handle_cancel_booking()
    {
        try {
            check_ajax_referer('bookings_nonce', 'nonce');

            if (!is_user_logged_in()) {
                wp_send_json_error(array('message' => __('Authentication required', 'organization-core')), 401);
            }

            $blog_id = get_current_blog_id();
            $user_id = get_current_user_id();
            $booking_id = isset($_POST['booking_id']) ? intval($_POST['booking_id']) : 0;

            if (!$booking_id) {
                wp_send_json_error(array('message' => __('Invalid booking ID.', 'organization-core')));
            }

            require_once plugin_dir_path(__FILE__) . 'crud.php';

            $booking = OC_Bookings_CRUD::get_booking($booking_id, $blog_id);

            if (!$booking) {
                wp_send_json_error(array('message' => __('Booking not found.', 'organization-core')));
            }

            if ($booking['user_id'] != $user_id) {
                wp_send_json_error(array('message' => __('Access denied.', 'organization-core')), 403);
            }

            if ($booking['status'] !== 'pending') {
                wp_send_json_error(array('message' => __('Only pending bookings can be cancelled.', 'organization-core')));
            }

            $result = OC_Bookings_CRUD::update_booking($booking_id, $blog_id, array(), 'cancelled');

            if ($result !== false) {
                wp_send_json_success(array('message' => __('Booking cancelled successfully.', 'organization-core')));
            } else {
                wp_send_json_error(array('message' => __('Failed to cancel booking.', 'organization-core')));
            }
        } catch (Exception $e) {
            wp_send_json_error(array('message' => __('An error occurred.', 'organization-core')));
        }
    }

    // ========================================
    // SCHOOL MANAGEMENT
    // ========================================

    /**
     * Handle save school
     */
    public function handle_save_school()
    {
        try {
            check_ajax_referer('bookings_nonce', 'nonce');

            if (!is_user_logged_in()) {
                wp_send_json_error(array('message' => __('You must be logged in.', 'organization-core')));
            }

            $user_id = get_current_user_id();
            $blog_id = get_current_blog_id();
            $school_data = json_decode(stripslashes($_POST['school_data'] ?? '{}'), true);

            if (!$school_data || !is_array($school_data)) {
                wp_send_json_error(array('message' => __('Invalid school data.', 'organization-core')));
            }

            // Sanitize data
            $sanitized_data = $this->sanitize_school_data($school_data);

            require_once plugin_dir_path(__FILE__) . 'crud.php';

            // Add school to database table
            $school_id = OC_Bookings_CRUD::add_school($user_id, $blog_id, $sanitized_data);

            if ($school_id) {
                wp_send_json_success(array(
                    'message' => __('School added successfully!', 'organization-core'),
                    'school_id' => $school_id,
                    'school_data' => $sanitized_data,
                ));
            } else {
                wp_send_json_error(array('message' => __('Failed to save school.', 'organization-core')));
            }
        } catch (Exception $e) {
            wp_send_json_error(array('message' => __('An error occurred.', 'organization-core')));
        }
    }

    /**
     * Handles an AJAX request to retrieve the schools associated with the currently logged-in user.
     *
     * ### Example Usage:
     * Can be called via JavaScript using:
     * ```js
     * jQuery.post(ajaxurl, {
     *     action: 'get_user_schools',
     *     nonce: myLocalizedScript.bookings_nonce
     * }, function(response) {
     *     if (response.success) {
     *         console.log(response.data.schools);
     *     } else {
     *         alert(response.data.message);
     *     }
     * });
     * ```
     *
     * @since 1.0.0
     * @return void Sends a JSON response and exits.
     */
    public function handle_get_schools_of_current_user()
    {
        try {
            check_ajax_referer('bookings_nonce', 'nonce');

            if (!is_user_logged_in()) {
                wp_send_json_error(array('message' => __('You must be logged in.', 'organization-core')));
            }

            $user_id = get_current_user_id();
            $blog_id = get_current_blog_id();

            require_once plugin_dir_path(__FILE__) . 'crud.php';

            $schools = OC_Bookings_CRUD::get_user_schools($user_id, $blog_id);

            wp_send_json_success(array(
                'schools' => $schools,
                'total' => count($schools),
            ));
        } catch (Exception $e) {
            wp_send_json_error(array('message' => __('Failed to load schools.', 'organization-core')));
        }
    }

    /**
     * Handle delete school
     */
    public function handle_delete_school()
    {
        try {
            check_ajax_referer('bookings_nonce', 'nonce');

            if (!is_user_logged_in()) {
                wp_send_json_error(array('message' => __('You must be logged in.', 'organization-core')));
            }

            $user_id = get_current_user_id();
            $blog_id = get_current_blog_id();
            $school_id = isset($_POST['school_id']) ? intval($_POST['school_id']) : 0;

            if (empty($school_id)) {
                wp_send_json_error(array('message' => __('School ID is required.', 'organization-core')));
            }

            require_once plugin_dir_path(__FILE__) . 'crud.php';

            // Verify ownership
            $school = OC_Bookings_CRUD::get_school($school_id, $blog_id);

            if (!$school || $school['user_id'] != $user_id) {
                wp_send_json_error(array('message' => __('School not found.', 'organization-core')));
            }

            // Delete school (soft delete)
            $result = OC_Bookings_CRUD::delete_school($school_id, $blog_id);

            if ($result) {
                wp_send_json_success(array('message' => __('School deleted!', 'organization-core')));
            } else {
                wp_send_json_error(array('message' => __('Failed to delete school.', 'organization-core')));
            }
        } catch (Exception $e) {
            wp_send_json_error(array('message' => __('An error occurred.', 'organization-core')));
        }
    }

    /**
     * Handle update school
     */
    public function handle_update_school()
    {
        try {
            check_ajax_referer('bookings_nonce', 'nonce');

            if (!is_user_logged_in()) {
                wp_send_json_error(array('message' => __('You must be logged in.', 'organization-core')));
            }

            $user_id = get_current_user_id();
            $blog_id = get_current_blog_id();
            $school_id = isset($_POST['school_id']) ? intval($_POST['school_id']) : 0;
            $school_data = json_decode(stripslashes($_POST['school_data'] ?? '{}'), true);

            if (empty($school_id) || !$school_data || !is_array($school_data)) {
                wp_send_json_error(array('message' => __('Invalid data.', 'organization-core')));
            }

            require_once plugin_dir_path(__FILE__) . 'crud.php';

            // Verify ownership
            $school = OC_Bookings_CRUD::get_school($school_id, $blog_id);

            if (!$school || $school['user_id'] != $user_id) {
                wp_send_json_error(array('message' => __('School not found.', 'organization-core')));
            }

            // Sanitize data
            $sanitized_data = $this->sanitize_school_data($school_data);

            // Update school
            $result = OC_Bookings_CRUD::update_school($school_id, $blog_id, $sanitized_data);

            if ($result) {
                wp_send_json_success(array(
                    'message' => __('School updated successfully!', 'organization-core'),
                    'updated_school' => $sanitized_data,
                ));
            } else {
                wp_send_json_error(array('message' => __('Failed to update school.', 'organization-core')));
            }
        } catch (Exception $e) {
            wp_send_json_error(array('message' => __('An error occurred.', 'organization-core')));
        }
    }

    /**
     * Handle get schools
     */
    public function handle_get_schools()
    {
        try {
            check_ajax_referer('bookings_nonce', 'nonce');

            $user_id = get_current_user_id();
            $blog_id = get_current_blog_id();

            require_once plugin_dir_path(__FILE__) . 'crud.php';

            $schools = OC_Bookings_CRUD::get_user_schools($user_id, $blog_id);

            wp_send_json_success(array('schools' => $schools));
        } catch (Exception $e) {
            wp_send_json_error(array('message' => __('Failed to retrieve schools.', 'organization-core')));
        }
    }

    // ========================================
    // PACKAGE HANDLER
    // ========================================

    /**
     * Handle get package data
     */
    public function handle_get_package_data()
    {
        try {
            check_ajax_referer('bookings_nonce', 'nonce');

            $package_id = intval($_POST['package_id'] ?? 0);

            if (!$package_id) {
                wp_send_json_error(array('message' => __('Package ID required', 'organization-core')));
            }

            $package = get_post($package_id);

            if (!$package || $package->post_type !== 'packages') {
                wp_send_json_error(array('message' => __('Package not found', 'organization-core')));
            }

            $package_data = array(
                'title' => $package->post_title,
                'content' => $package->post_content,
                'excerpt' => $package->post_excerpt,
                'locations' => wp_get_post_terms($package_id, 'location'),
                'parks' => wp_get_post_terms($package_id, 'parks'),
                'meta' => get_post_meta($package_id),
            );

            wp_send_json_success($package_data);
        } catch (Exception $e) {
            wp_send_json_error(array('message' => __('An error occurred.', 'organization-core')));
        }
    }

    // ========================================
    // HELPER METHODS - INPUT
    // ========================================

    /**
     * Sanitize booking input
     */
    private function sanitize_booking_input($post_data)
    {
        // ✅ CRITICAL FIX: Handle parks
        $parks_selection = array();
        if (isset($post_data['parks']) && is_array($post_data['parks'])) {
            $parks_selection = array_map('intval', $post_data['parks']);
        } elseif (isset($post_data['parks_selection'])) {
            if (is_array($post_data['parks_selection'])) {
                $parks_selection = array_map('intval', $post_data['parks_selection']);
            } elseif (is_string($post_data['parks_selection'])) {
                $decoded = json_decode(stripslashes($post_data['parks_selection']), true);
                $parks_selection = is_array($decoded) ? array_map('intval', $decoded) : array();
            }
        }

        // ✅ CRITICAL FIX: Handle park_meal_options
        $park_meal_options = array();
        if (isset($post_data['park_meal_options'])) {
            if (is_array($post_data['park_meal_options'])) {
                $park_meal_options = $post_data['park_meal_options'];
            } elseif (is_string($post_data['park_meal_options'])) {
                $decoded = json_decode(stripslashes($post_data['park_meal_options']), true);
                $park_meal_options = is_array($decoded) ? $decoded : array();
            }
        }

        // ✅ CRITICAL FIX: Handle ensembles
        $ensembles = array();
        if (isset($post_data['ensembles'])) {
            if (is_array($post_data['ensembles'])) {
                $ensembles = $post_data['ensembles'];
            } elseif (is_string($post_data['ensembles'])) {
                $decoded = json_decode(stripslashes($post_data['ensembles']), true);
                $ensembles = is_array($decoded) ? $decoded : array();
            }
        }

        // ✅ FIXED: Handle both include_meal_vouchers AND meal_vouchers
        $meal_vouchers = 0;
        if (isset($post_data['include_meal_vouchers'])) {
            $meal_vouchers = intval($post_data['include_meal_vouchers']);
        } elseif (isset($post_data['meal_vouchers'])) {
            $meal_vouchers = intval($post_data['meal_vouchers']);
        }

        return array(
            'package_id' => isset($post_data['package_id']) ? intval($post_data['package_id']) : 0,
            'school_id' => isset($post_data['school_id']) ? intval($post_data['school_id']) : 0,
            'location_id' => isset($post_data['location_id']) ? intval($post_data['location_id']) : 0,
            'date_selection' => isset($post_data['date_selection']) ? sanitize_text_field($post_data['date_selection']) : '',
            'parks_selection' => $parks_selection,
            'other_park_name' => isset($post_data['other_park_name']) ? sanitize_text_field($post_data['other_park_name']) : '',
            'total_students' => isset($post_data['total_students']) ? intval($post_data['total_students']) : 0,
            'total_chaperones' => isset($post_data['total_chaperones']) ? intval($post_data['total_chaperones']) : 0,
            'transportation' => isset($post_data['transportation']) ? sanitize_text_field($post_data['transportation']) : 'own',
            'meal_vouchers' => $meal_vouchers, // ✅ FIXED: Now handles both field names
            'meals_per_day' => isset($post_data['meals_per_day']) ? intval($post_data['meals_per_day']) : 0,
            'lodging_dates' => isset($post_data['lodging_dates']) ? sanitize_text_field($post_data['lodging_dates']) : '', // ✅ FIXED
            'special_notes' => isset($post_data['special_notes']) ? sanitize_textarea_field($post_data['special_notes']) : '', // ✅ FIXED
            'park_meal_options' => $park_meal_options,
            'ensembles' => $ensembles,
        );
    }



    /**
     * Validate booking input
     */
    private function validate_booking_input($input)
    {
        if (empty($input['package_id'])) {
            return new WP_Error('invalid_package', __('Package is required.', 'organization-core'));
        }

        if (empty($input['location_id'])) {
            return new WP_Error('invalid_location', __('Location is required.', 'organization-core'));
        }

        if (empty($input['date_selection'])) {
            return new WP_Error('invalid_date', __('Date is required.', 'organization-core'));
        }

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $input['date_selection'])) {
            return new WP_Error('invalid_date_format', __('Invalid date format.', 'organization-core'));
        }

        if (empty($input['total_students'])) {
            return new WP_Error('invalid_students', __('Total students is required.', 'organization-core'));
        }

        return true;
    }

    /**
     * Prepare booking data for database
     */
    private function prepare_booking_data($input, $user_id, $blog_id)
    {
        require_once plugin_dir_path(__FILE__) . 'crud.php';

        $package = get_post($input['package_id']);

        // Get school name from schools table
        $school = null;
        if (!empty($input['school_id'])) {
            $school = OC_Bookings_CRUD::get_school($input['school_id'], $blog_id);
        }

        return array(
            'user_id' => $user_id,
            'package_id' => $input['package_id'],
            'school_id' => $input['school_id'],
            'location_id' => $input['location_id'],
            'date_selection' => $input['date_selection'],
            'parks_selection' => $input['parks_selection'],
            'other_park_name' => $input['other_park_name'],
            'total_students' => $input['total_students'],
            'total_chaperones' => $input['total_chaperones'],
            'transportation' => $input['transportation'],
            'meal_vouchers' => $input['meal_vouchers'],
            'meals_per_day' => $input['meals_per_day'],
            'park_meal_options' => $input['park_meal_options'],
            'lodging_dates' => $input['lodging_dates'],
            'special_notes' => $input['special_notes'],
            'ensembles' => $input['ensembles'],
            'created_at' => current_time('mysql'),
            'ip_address' => isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field($_SERVER['REMOTE_ADDR']) : '',
            'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field($_SERVER['HTTP_USER_AGENT']) : '',
        );
    }


    /**
     * Prepare response data
     */
    private function prepare_booking_response($booking_id, $booking_data, $input)
    {
        require_once plugin_dir_path(__FILE__) . 'crud.php';

        $package = get_post($booking_data['package_id']);
        $park_names = OC_Bookings_CRUD::get_park_names(
            $input['parks_selection'],
            $input['other_park_name']
        );

        return array(
            'success' => true,
            'message' => __('Booking confirmed successfully!', 'organization-core'),
            'booking_id' => $booking_id,
            'booking_reference' => 'FORUM-' . str_pad($booking_id, 6, '0', STR_PAD_LEFT),
            'redirect_url' => home_url('/my-account/bookings'),
            'booking_data' => array(
                'school_name' => $booking_data['school_name'],
                'location_name' => $booking_data['location_name'],
                'package_title' => $package ? $package->post_title : 'N/A',
                'date' => date('F j, Y', strtotime($booking_data['date_selection'])),
                'park_names' => !empty($park_names) ? implode(', ', $park_names) : 'None',
                'total_students' => $booking_data['total_students'],
                'total_chaperones' => $booking_data['total_chaperones'],
                'transportation' => ($booking_data['transportation'] === 'own') ?
                    'We Will Provide Our Own Transportation' : 'Please Provide A Quote',
            ),
        );
    }

    /**
     * Process bookings for display
     */
    private function process_bookings_for_display($bookings)
    {
        $processed = array();

        foreach ($bookings as $booking) {
            $processed[] = array(
                'id' => $booking['id'],
                'package_title' => get_the_title($booking['package_id']),
                'school_name' => $booking['school_name'],
                'location_name' => $booking['location_name'],
                'date_selection' => $booking['date_selection'],
                'status' => $booking['status'],
                'total_amount' => $booking['total_amount'],
                'created_at' => $booking['created_at'],
            );
        }

        return $processed;
    }

    /**
     * Sanitize school data
     */
    private function sanitize_school_data($school_data)
    {
        $sanitized = array();

        foreach ($school_data as $key => $value) {
            if ($key === 'director_email' || $key === 'principal_email') {
                $sanitized[$key] = sanitize_email($value);
            } elseif (in_array($key, array('school_enrollment'))) {
                $sanitized[$key] = intval($value);
            } elseif ($key === 'school_website') {
                $sanitized[$key] = sanitize_url($value);
            } elseif (strpos($key, 'notes') !== false) {
                $sanitized[$key] = sanitize_textarea_field($value);
            } else {
                $sanitized[$key] = sanitize_text_field($value);
            }
        }

        return $sanitized;
    }

    /**
     * ✅ Handle save booking draft
     */
    public function handle_save_booking_draft()
    {
        try {
            check_ajax_referer('bookings_nonce', 'nonce');

            if (!is_user_logged_in()) {
                wp_send_json_error(
                    array('message' => __('You must be logged in to save progress.', 'organization-core')),
                    401
                );
            }

            $user_id = get_current_user_id();
            $blog_id = get_current_blog_id();
            $package_id = isset($_POST['package_id']) ? intval($_POST['package_id']) : 0;
            $draft_id = isset($_POST['draft_id']) ? intval($_POST['draft_id']) : 0;
            $step_data = isset($_POST['step_data']) ? $_POST['step_data'] : array();

            if (!$package_id) {
                wp_send_json_error(array('message' => __('Invalid package.', 'organization-core')));
                return;
            }

            // ✅ FIXED: Sanitize step data properly
            $sanitized_data = $this->sanitize_booking_input($step_data);

            require_once plugin_dir_path(__FILE__) . 'crud.php';

            // Save draft using CRUD
            $result_id = OC_Bookings_CRUD::save_booking_draft(
                $user_id,
                $package_id,
                $sanitized_data,
                $draft_id > 0 ? $draft_id : null
            );

            if ($result_id) {
                wp_send_json_success(array(
                    'message' => __('Progress saved', 'organization-core'),
                    'draft_id' => $result_id
                ));
            } else {
                wp_send_json_error(array('message' => __('Failed to save progress', 'organization-core')));
            }
        } catch (Exception $e) {
            wp_send_json_error(array('message' => __('Failed to save progress', 'organization-core')));
        }
    }

    public function handle_get_booking_draft()
    {
        check_ajax_referer('bookings_nonce', 'nonce'); // ✅ FIXED: bookings_nonce (with 's')

        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => __('Please log in', 'organization-core')));
            return;
        }

        $user_id = get_current_user_id();
        $package_id = isset($_POST['package_id']) ? intval($_POST['package_id']) : 0;

        if (!$package_id) {
            wp_send_json_error(array('message' => __('Invalid package', 'organization-core')));
            return;
        }

        require_once plugin_dir_path(__FILE__) . 'crud.php';

        $draft = OC_Bookings_CRUD::get_user_draft($user_id, $package_id);

        if ($draft) {
            // Decode JSON fields
            $draft_data = json_decode($draft->booking_data, true);

            wp_send_json_success(array(
                'draft_id' => $draft->id,
                'data' => $draft_data
            ));
        } else {
            wp_send_json_error(array('message' => __('No draft found', 'organization-core')));
        }
    }

    /**
     * Handle AJAX price updates
     * EXACT logic from old class-owlth-booking-admin-page.php
     */
    public function save_booking_price()
    {
        // Security checks
        check_ajax_referer('booking_price_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('You do not have sufficient permissions to perform this action.');
        }

        $booking_id = intval($_POST['booking_id']);
        $price = isset($_POST['price']) ? floatval($_POST['price']) : 0;

        if (!$booking_id) {
            wp_send_json_error('Invalid booking ID provided.');
        }

        // Validate price (must be 0 or positive)
        if ($price < 0) {
            wp_send_json_error('Price cannot be negative.');
        }

        // Update the total_amount in the bookings table
        global $wpdb;
        $table_name = $wpdb->base_prefix . 'bookings';
        $blog_id = get_current_blog_id();

        // First check if the booking exists
        $booking_exists = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table_name} WHERE id = %d AND blog_id = %d",
            $booking_id,
            $blog_id
        ));

        if (!$booking_exists) {
            wp_send_json_error('Booking not found.');
        }

        // Update the price
        $result = $wpdb->update(
            $table_name,
            array('total_amount' => $price),
            array('id' => $booking_id, 'blog_id' => $blog_id),
            array('%f'),
            array('%d', '%d')
        );

        if ($result !== false) {
            // Format the price for display
            if ($price > 0) {
                $display_price = '$' . number_format($price, 2);
            } else {
                $display_price = '<em style="color: #666;">Free</em>';
            }

            wp_send_json_success(array(
                'message' => 'Price updated successfully!',
                'display_price' => $display_price,
                'raw_price' => $price
            ));
        } else {
            wp_send_json_error('Failed to update the price. Please try again.');
        }
    }

    
    /**
     * Handle AJAX request to send booking emails
     */
    public function handle_send_booking_email()
    {
        check_ajax_referer('send_booking_email_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission denied');
            return;
        }

        $booking_id = isset($_POST['booking_id']) ? intval($_POST['booking_id']) : 0;
        $email_type = isset($_POST['email_type']) ? sanitize_text_field($_POST['email_type']) : '';
        $blog_id = get_current_blog_id();

        if (!$booking_id || !in_array($email_type, ['user', 'admin'])) {
            wp_send_json_error('Invalid parameters');
            return;
        }

        // Get booking data
        $booking = $this->booking_crud->get_booking($booking_id, $blog_id);
        if (!$booking) {
            wp_send_json_error('Booking not found');
            return;
        }

        // Get user data
        $user = get_userdata($booking['user_id']);
        if (!$user) {
            wp_send_json_error('User not found');
            return;
        }

        // Use the main email handler
        do_action('org_core_booking_created', $booking_id, $booking, $user, $blog_id);
        wp_send_json_success('Email sent successfully');
    }


    /**
     * AJAX handler for status update
     * EXACT logic from old system
     */
    public function ajax_update_booking_status()
    {
        check_ajax_referer('update_booking_status_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Permission denied'));
        }

        $booking_id = isset($_POST['booking_id']) ? intval($_POST['booking_id']) : 0;
        $new_status = isset($_POST['new_status']) ? sanitize_text_field($_POST['new_status']) : '';

        if (!$booking_id || !$new_status) {
            wp_send_json_error(array('message' => 'Invalid parameters'));
        }

        global $wpdb;
        $table_name = $wpdb->base_prefix . 'bookings';
        $blog_id = get_current_blog_id();

        $result = $wpdb->update(
            $table_name,
            array('status' => $new_status),
            array('id' => $booking_id, 'blog_id' => $blog_id),
            array('%s'),
            array('%d', '%d')
        );

        if ($result !== false) {
            wp_send_json_success(array('message' => 'Status updated successfully'));
        } else {
            wp_send_json_error(array('message' => 'Database error'));
        }
    }

    /**
     * AJAX handler for delete booking
     * EXACT logic from old system
     */
    public function ajax_delete_booking()
    {
        check_ajax_referer('delete_booking_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Permission denied'));
        }

        $booking_id = isset($_POST['booking_id']) ? intval($_POST['booking_id']) : 0;

        if (!$booking_id) {
            wp_send_json_error(array('message' => 'Invalid booking ID'));
        }

        global $wpdb;
        $table_name = $wpdb->base_prefix . 'bookings';
        $blog_id = get_current_blog_id();

        $result = $wpdb->delete(
            $table_name,
            array('id' => $booking_id, 'blog_id' => $blog_id),
            array('%d', '%d')
        );

        if ($result) {
            wp_send_json_success(array('message' => 'Booking deleted successfully'));
        } else {
            wp_send_json_error(array('message' => 'Failed to delete booking'));
        }
    }
}
