<?php
if (!defined('WPINC')) die;

/**
 * Authentication Module CRUD Operations
 * Handles all database operations for authentication module
 */

class OC_Authentication_CRUD
{
    /**
     * Get user quotes with limit (for dashboard display)
     * 
     * @param int $user_id User ID
     * @param int $limit Number of quotes to return (default 3)
     * @return array Array of quote objects
     */
    public static function get_user_quotes_limited($user_id, $limit = 3)
    {
        global $wpdb;
        $table_name = $wpdb->base_prefix . 'quotes';

        $quotes = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$table_name} 
            WHERE user_id = %d 
            ORDER BY created_at DESC 
            LIMIT %d",
            $user_id,
            $limit
        ));

        return $quotes ? $quotes : array();
    }

    /**
     * Get total quotes count for user
     * 
     * @param int $user_id User ID
     * @return int Total quotes count
     */
    public static function get_user_quotes_count($user_id)
    {
        global $wpdb;
        $table_name = $wpdb->base_prefix . 'quotes';

        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table_name} WHERE user_id = %d",
            $user_id
        ));

        return intval($count);
    }

    /**
     * Count user quotes by status
     * 
     * @param int $user_id User ID
     * @param int $blog_id Blog ID
     * @return array Quote status counts
     */
    public static function count_user_quotes_by_status($user_id, $blog_id = null)
    {
        global $wpdb;
        $table_name = $wpdb->base_prefix . 'quotes';

        $statuses = array('pending', 'approved', 'declined', 'completed');
        $counts = array();

        foreach ($statuses as $status) {
            $count = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$table_name} 
                WHERE user_id = %d AND status = %s",
                $user_id,
                $status
            ));
            $counts[$status] = intval($count);
        }

        return $counts;
    }

    /**
     * Get user's bookings for my-account page
     * 
     * @param int $user_id User ID
     * @param int $blog_id Blog ID
     * @param array $args Query arguments
     * @return array Array of bookings
     */
    public static function get_user_bookings($user_id, $blog_id = null, $args = array())
    {
        global $wpdb;

        $table_name = $wpdb->base_prefix . 'bookings';

        $defaults = array(
            'number' => 20,
            'offset' => 0,
            'orderby' => 'created_at',
            'order' => 'DESC',
            'status' => '',
        );

        $args = wp_parse_args($args, $defaults);

        // ✅ Use 'user_id' NOT 'blog_id' for filtering by user
        $sql = "SELECT * FROM {$table_name} WHERE user_id = %d";
        $params = array($user_id);

        // ✅ Filter by status if provided (use 'status' not 'booking_status')
        if (!empty($args['status'])) {
            $sql .= " AND status = %s";
            $params[] = $args['status'];
        }

        $allowed_orderby = array('id', 'school_name', 'location_name', 'date_selection', 'status', 'created_at', 'total_amount');
        $orderby = in_array($args['orderby'], $allowed_orderby) ? $args['orderby'] : 'created_at';
        $order = strtoupper($args['order']) === 'ASC' ? 'ASC' : 'DESC';

        $sql .= " ORDER BY {$orderby} {$order}";
        $sql .= " LIMIT %d OFFSET %d";

        $params[] = $args['number'];
        $params[] = $args['offset'];

        $bookings = $wpdb->get_results($wpdb->prepare($sql, $params), ARRAY_A);

        if (empty($bookings)) {
            return array();
        }

        // Decode JSON fields
        foreach ($bookings as &$booking) {
            if (!empty($booking['booking_data'])) {
                $booking['booking_data'] = json_decode($booking['booking_data'], true);
            }
            if (!empty($booking['parks_selection'])) {
                $booking['parks_selection'] = json_decode($booking['parks_selection'], true);
            }
            if (!empty($booking['park_meal_options'])) {
                $booking['park_meal_options'] = json_decode($booking['park_meal_options'], true);
            }
        }

        return $bookings;
    }

    /**
     * Get user bookings with limit (for dashboard display)
     * Returns booking objects instead of arrays for compatibility
     * 
     * @param int $user_id User ID
     * @param int $limit Number of bookings to return (default 3)
     * @return array Array of booking objects
     */
    public static function get_user_bookings_limited($user_id, $limit = 3)
    {
        global $wpdb;

        $table_name = $wpdb->base_prefix . 'bookings';

        $bookings = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$table_name} 
            WHERE user_id = %d 
            ORDER BY created_at DESC 
            LIMIT %d",
            $user_id,
            $limit
        ));

        return $bookings ? $bookings : array();
    }

    /**
     * Get total booking count for user
     * 
     * @param int $user_id User ID
     * @return int Total bookings count
     */
    public static function get_user_bookings_count($user_id)
    {
        global $wpdb;

        $table_name = $wpdb->base_prefix . 'bookings';

        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table_name} WHERE user_id = %d",
            $user_id
        ));

        return intval($count);
    }

    /**
     * Get single booking by ID for user
     * 
     * @param int $booking_id Booking ID
     * @param int $user_id User ID (for security verification)
     * @param int $blog_id Blog ID
     * @return array|null Booking data or null
     */
    public static function get_user_booking($booking_id, $user_id, $blog_id = null)
    {
        global $wpdb;

        $table_name = $wpdb->base_prefix . 'bookings';

        // ✅ Check if booking belongs to this user
        $booking = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table_name} WHERE id = %d AND user_id = %d",
            $booking_id,
            $user_id
        ), ARRAY_A);

        if (!$booking) {
            return null;
        }

        // Decode JSON fields
        if (!empty($booking['booking_data'])) {
            $booking['booking_data'] = json_decode($booking['booking_data'], true);
        }
        if (!empty($booking['parks_selection'])) {
            $booking['parks_selection'] = json_decode($booking['parks_selection'], true);
        }
        if (!empty($booking['park_meal_options'])) {
            $booking['park_meal_options'] = json_decode($booking['park_meal_options'], true);
        }

        return $booking;
    }

    /**
     * Count user bookings by status
     * 
     * @param int $user_id User ID
     * @param int $blog_id Blog ID
     * @return array Booking status counts
     */
    public static function count_user_bookings_by_status($user_id, $blog_id = null)
    {
        global $wpdb;

        $table_name = $wpdb->base_prefix . 'bookings';

        $statuses = array('pending', 'confirmed', 'cancelled', 'completed');
        $counts = array();

        foreach ($statuses as $status) {
            // ✅ Use 'status' column not 'booking_status'
            $count = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$table_name} WHERE user_id = %d AND status = %s",
                $user_id,
                $status
            ));
            $counts[$status] = $count;
        }

        return $counts;
    }

    /**
     * Cancel user booking
     * 
     * @param int $booking_id Booking ID
     * @param int $user_id User ID
     * @param int $blog_id Blog ID
     * @return bool Success
     */
    public static function cancel_user_booking($booking_id, $user_id, $blog_id = null)
    {
        global $wpdb;

        $table_name = $wpdb->base_prefix . 'bookings';

        // Verify booking belongs to user
        $booking = self::get_user_booking($booking_id, $user_id, $blog_id);
        if (!$booking || $booking['status'] !== 'pending') {
            return false;
        }

        // ✅ Update 'status' column
        $result = $wpdb->update(
            $table_name,
            array(
                'status' => 'cancelled',
                'modified_at' => current_time('mysql'),
            ),
            array('id' => $booking_id, 'user_id' => $user_id),
            array('%s', '%s'),
            array('%d', '%d')
        );

        return $result !== false;
    }

    /**
     * Get user profile data
     * 
     * @param int $user_id User ID
     * @return array|false User profile data
     */
    public static function get_user_profile($user_id)
    {
        $user = get_userdata($user_id);

        if (!$user) {
            return false;
        }

        return array(
            'ID' => $user->ID,
            'username' => $user->user_login,
            'email' => $user->user_email,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'display_name' => $user->display_name,
            'phone' => get_user_meta($user_id, 'phone', true),
            'company' => get_user_meta($user_id, 'company', true),
        );
    }

    /**
     * Get user's additional profile meta
     * 
     * @param int $user_id User ID
     * @return array Additional profile data
     */
    public static function get_user_additional_meta($user_id)
    {
        return array(
            'user_prefix' => get_user_meta($user_id, 'user_prefix', true),
            'phone_number' => get_user_meta($user_id, 'phone_number', true),
            'cell_number' => get_user_meta($user_id, 'cell_number', true),
            'best_time_contact' => get_user_meta($user_id, 'best_time_contact', true),
        );
    }

    /**
     * Update user profile
     * 
     * @param int $user_id User ID
     * @param array $data Profile data to update
     * @return bool Success
     */
    public static function update_user_profile($user_id, $data = array())
    {
        if (empty($data)) {
            return false;
        }

        if (isset($data['first_name'])) {
            update_user_meta($user_id, 'first_name', sanitize_text_field($data['first_name']));
        }
        if (isset($data['last_name'])) {
            update_user_meta($user_id, 'last_name', sanitize_text_field($data['last_name']));
        }
        if (isset($data['phone'])) {
            update_user_meta($user_id, 'phone', sanitize_text_field($data['phone']));
        }

        return true;
    }

    /**
     * Update user's additional profile meta
     * 
     * @param int $user_id User ID
     * @param array $data Meta data to update
     * @return bool Success
     */
    public static function update_user_additional_meta($user_id, $data)
    {
        $updated = false;

        if (isset($data['user_prefix'])) {
            update_user_meta($user_id, 'user_prefix', sanitize_text_field($data['user_prefix']));
            $updated = true;
        }

        if (isset($data['phone_number'])) {
            update_user_meta($user_id, 'phone_number', sanitize_text_field($data['phone_number']));
            $updated = true;
        }

        if (isset($data['cell_number'])) {
            update_user_meta($user_id, 'cell_number', sanitize_text_field($data['cell_number']));
            $updated = true;
        }

        if (isset($data['best_time_contact'])) {
            update_user_meta($user_id, 'best_time_contact', sanitize_text_field($data['best_time_contact']));
            $updated = true;
        }

        return $updated;
    }

    /**
     * Get user schools from wp_schools table
     * 
     * @param int $user_id User ID
     * @param int $blog_id Blog ID (optional)
     * @return array Schools data
     */
    public static function get_user_schools($user_id, $blog_id = null)
    {
        global $wpdb;

        if (empty($blog_id)) {
            $blog_id = get_current_blog_id();
        }

        $table_name = $wpdb->base_prefix . 'schools';

        $schools = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$table_name} 
        WHERE user_id = %d 
        AND blog_id = %d 
        AND status = 'active'
        ORDER BY created_at DESC",
            $user_id,
            $blog_id
        ), ARRAY_A);

        return $schools ? $schools : array();
    }

    /**
     * Get user schools formatted for display
     * 
     * @param int $user_id User ID
     * @param int $blog_id Blog ID (optional)
     * @return array Array of schools with formatted data
     */
    public static function get_user_schools_for_display($user_id, $blog_id = null)
    {
        return self::get_user_schools($user_id, $blog_id);
    }

    /**
     * Get limited schools for dashboard display
     * 
     * @param int $user_id User ID
     * @param int $limit Number of schools to return
     * @param int $blog_id Blog ID (optional)
     * @return array Limited schools array
     */
    public static function get_dashboard_schools($user_id, $limit = 2, $blog_id = null)
    {
        global $wpdb;

        if (empty($blog_id)) {
            $blog_id = get_current_blog_id();
        }

        $table_name = $wpdb->base_prefix . 'schools';

        $schools = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$table_name} 
        WHERE user_id = %d 
        AND blog_id = %d 
        AND status = 'active'
        ORDER BY created_at DESC
        LIMIT %d",
            $user_id,
            $blog_id,
            $limit
        ), ARRAY_A);

        return $schools ? $schools : array();
    }

    /**
     * Get total schools count for user
     * 
     * @param int $user_id User ID
     * @param int $blog_id Blog ID (optional)
     * @return int Total schools count
     */
    public static function get_user_schools_count($user_id, $blog_id = null)
    {
        global $wpdb;

        if (empty($blog_id)) {
            $blog_id = get_current_blog_id();
        }

        $table_name = $wpdb->base_prefix . 'schools';

        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table_name} 
        WHERE user_id = %d 
        AND blog_id = %d 
        AND status = 'active'",
            $user_id,
            $blog_id
        ));

        return intval($count);
    }

    /**
     * Get single school by ID
     * 
     * @param int $school_id School ID
     * @param int $user_id User ID (for security check)
     * @return array|null School data or null
     */
    public static function get_user_school($school_id, $user_id)
    {
        global $wpdb;

        $table_name = $wpdb->base_prefix . 'schools';

        $school = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table_name} 
        WHERE id = %d 
        AND user_id = %d",
            $school_id,
            $user_id
        ), ARRAY_A);

        return $school;
    }

    /**
     * Save user school to wp_schools table
     * 
     * @param int $user_id User ID
     * @param array $school_data School data
     * @param int $blog_id Blog ID (optional)
     * @return int|false School ID or false on failure
     */
    public static function save_user_school($user_id, $school_data, $blog_id = null)
    {
        global $wpdb;

        if (empty($blog_id)) {
            $blog_id = get_current_blog_id();
        }

        $table_name = $wpdb->base_prefix . 'schools';

        $insert_data = array(
            'blog_id' => $blog_id,
            'user_id' => $user_id,
            'school_name' => sanitize_text_field($school_data['school_name'] ?? ''),
            'school_address' => sanitize_text_field($school_data['school_address'] ?? ''),
            'school_address_2' => sanitize_text_field($school_data['school_address_2'] ?? ''),
            'school_city' => sanitize_text_field($school_data['school_city'] ?? ''),
            'school_state' => sanitize_text_field($school_data['school_state'] ?? ''),
            'school_zip' => sanitize_text_field($school_data['school_zip'] ?? ''),
            'school_country' => sanitize_text_field($school_data['school_country'] ?? 'USA'),
            'school_phone' => sanitize_text_field($school_data['school_phone'] ?? ''),
            'school_website' => esc_url_raw($school_data['school_website'] ?? ''),
            'school_enrollment' => intval($school_data['school_enrollment'] ?? 0),
            'school_notes' => wp_kses_post($school_data['school_notes'] ?? ''),
            'director_prefix' => sanitize_text_field($school_data['director_prefix'] ?? ''),
            'director_first_name' => sanitize_text_field($school_data['director_first_name'] ?? ''),
            'director_last_name' => sanitize_text_field($school_data['director_last_name'] ?? ''),
            'director_email' => sanitize_email($school_data['director_email'] ?? ''),
            'director_cell_phone' => sanitize_text_field($school_data['director_cell_phone'] ?? ''),
            'principal_prefix' => sanitize_text_field($school_data['principal_prefix'] ?? ''),
            'principal_first_name' => sanitize_text_field($school_data['principal_first_name'] ?? ''),
            'principal_last_name' => sanitize_text_field($school_data['principal_last_name'] ?? ''),
            'status' => 'active',
            'created_at' => current_time('mysql'),
            'modified_at' => current_time('mysql'),
        );

        $result = $wpdb->insert($table_name, $insert_data);

        return $result ? $wpdb->insert_id : false;
    }

    /**
     * Update user school
     * 
     * @param int $school_id School ID
     * @param int $user_id User ID (for security)
     * @param array $school_data Updated school data
     * @return bool Success
     */
    public static function update_user_school($school_id, $user_id, $school_data)
    {
        global $wpdb;

        $table_name = $wpdb->base_prefix . 'schools';

        // Verify school belongs to user
        $school = self::get_user_school($school_id, $user_id);
        if (!$school) {
            return false;
        }

        $update_data = array(
            'school_name' => sanitize_text_field($school_data['school_name'] ?? ''),
            'school_address' => sanitize_text_field($school_data['school_address'] ?? ''),
            'school_address_2' => sanitize_text_field($school_data['school_address_2'] ?? ''),
            'school_city' => sanitize_text_field($school_data['school_city'] ?? ''),
            'school_state' => sanitize_text_field($school_data['school_state'] ?? ''),
            'school_zip' => sanitize_text_field($school_data['school_zip'] ?? ''),
            'school_country' => sanitize_text_field($school_data['school_country'] ?? 'USA'),
            'school_phone' => sanitize_text_field($school_data['school_phone'] ?? ''),
            'school_website' => esc_url_raw($school_data['school_website'] ?? ''),
            'school_enrollment' => intval($school_data['school_enrollment'] ?? 0),
            'school_notes' => wp_kses_post($school_data['school_notes'] ?? ''),
            'director_prefix' => sanitize_text_field($school_data['director_prefix'] ?? ''),
            'director_first_name' => sanitize_text_field($school_data['director_first_name'] ?? ''),
            'director_last_name' => sanitize_text_field($school_data['director_last_name'] ?? ''),
            'director_email' => sanitize_email($school_data['director_email'] ?? ''),
            'director_cell_phone' => sanitize_text_field($school_data['director_cell_phone'] ?? ''),
            'principal_prefix' => sanitize_text_field($school_data['principal_prefix'] ?? ''),
            'principal_first_name' => sanitize_text_field($school_data['principal_first_name'] ?? ''),
            'principal_last_name' => sanitize_text_field($school_data['principal_last_name'] ?? ''),
            'modified_at' => current_time('mysql'),
        );

        $result = $wpdb->update(
            $table_name,
            $update_data,
            array('id' => $school_id, 'user_id' => $user_id),
            array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s'),
            array('%d', '%d')
        );

        return $result !== false;
    }

    /**
     * Delete user school (soft delete by status)
     * 
     * @param int $school_id School ID
     * @param int $user_id User ID (for security)
     * @return bool Success
     */
    public static function delete_user_school($school_id, $user_id)
    {
        global $wpdb;
        $table_name = $wpdb->base_prefix . 'schools';

        // Verify school belongs to user
        $school = self::get_user_school($school_id, $user_id);
        if (!$school) {
            return false;
        }

        // ✅ HARD DELETE - Actually remove the record
        $result = $wpdb->delete(
            $table_name,
            array('id' => $school_id, 'user_id' => $user_id),
            array('%d', '%d')
        );

        // ✅ Check for DB errors
        if ($wpdb->last_error) {
            return false;
        }

        if ($result === false) {
            return false;
        }

        do_action('mus_school_deleted', $user_id, $school_id);
        return true;
    }
}
