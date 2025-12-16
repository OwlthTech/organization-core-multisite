<?php

/**
 * Bookings CRUD - Database Operations Layer
 * @package    Organization_Core
 * @subpackage Bookings
 */

if (!defined('ABSPATH')) {
    exit;
}

class OC_Bookings_CRUD
{
    /**
     * Get the bookings table name
     */
    public static function get_bookings_table_name()
    {
        global $wpdb;
        return $wpdb->base_prefix . 'bookings';
    }

    /**
     * Get the schools table name
     */
    public static function get_schools_table_name()
    {
        global $wpdb;
        return $wpdb->base_prefix . 'schools';
    }

    // ========================================
    // BOOKING CRUD OPERATIONS
    // ========================================

    /**
     * Create a new booking
     * 
     * @param int   $blog_id Blog ID
     * @param array $booking_data Booking data
     * @return int|false Booking ID or false on failure
     */
    public static function create_booking($blog_id, $booking_data)
    {
        global $wpdb;
        $table_name = self::get_bookings_table_name();

        $insert_data = array(
            'blog_id'           => $blog_id,
            'user_id'           => isset($booking_data['user_id']) ? intval($booking_data['user_id']) : get_current_user_id(),
            'package_id'        => isset($booking_data['package_id']) ? intval($booking_data['package_id']) : 0,
            'booking_data'      => wp_json_encode($booking_data),
            'school_id'         => isset($booking_data['school_id']) ? intval($booking_data['school_id']) : 0,
            // 'school_name'       => isset($booking_data['school_name']) ? sanitize_text_field($booking_data['school_name']) : '',
            'location_id'       => isset($booking_data['location_id']) ? intval($booking_data['location_id']) : 0,
            // 'location_name'     => isset($booking_data['location_name']) ? sanitize_text_field($booking_data['location_name']) : '',
            'date_selection'    => isset($booking_data['date_selection']) ? sanitize_text_field($booking_data['date_selection']) : '',
            'parks_selection'   => isset($booking_data['parks_selection']) ? wp_json_encode($booking_data['parks_selection']) : wp_json_encode(array()),
            'other_park_name'   => isset($booking_data['other_park_name']) ? sanitize_text_field($booking_data['other_park_name']) : '',
            'total_students'    => isset($booking_data['total_students']) ? intval($booking_data['total_students']) : 0,
            'total_chaperones'  => isset($booking_data['total_chaperones']) ? intval($booking_data['total_chaperones']) : 0,
            'meal_vouchers'     => isset($booking_data['meal_vouchers']) ? intval($booking_data['meal_vouchers']) : 0,
            'meals_per_day'     => isset($booking_data['meals_per_day']) ? intval($booking_data['meals_per_day']) : 0,
            'park_meal_options' => isset($booking_data['park_meal_options']) ? wp_json_encode($booking_data['park_meal_options']) : wp_json_encode(array()),
            'transportation'    => isset($booking_data['transportation']) ? sanitize_text_field($booking_data['transportation']) : 'own',
            'lodging_dates'     => isset($booking_data['lodging_dates']) ? sanitize_text_field($booking_data['lodging_dates']) : '',
            'special_notes'     => isset($booking_data['special_notes']) ? sanitize_textarea_field($booking_data['special_notes']) : '',
            'ensembles' => isset($booking_data['ensembles']) ? $booking_data['ensembles'] : wp_json_encode(array()),
            'status'            => 'pending',
            'total_amount'      => 0.00,
            'created_at'        => current_time('mysql'),
        );

        $format = array('%d', '%d', '%d', '%s', '%d', '%d', '%s', '%s', '%s', '%d', '%d', '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%f', '%s');

        $result = $wpdb->insert($table_name, $insert_data, $format);

        return $result ? $wpdb->insert_id : false;
    }

    /**
     * Get a single booking by ID
     */
    public static function get_booking($id, $blog_id)
    {
        global $wpdb;
        $table_name = self::get_bookings_table_name();

        $booking = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE id = %d AND blog_id = %d",
            $id,
            $blog_id
        ), ARRAY_A);

        if ($booking) {
            // Decode booking_data JSON
            $booking['booking_data'] = json_decode($booking['booking_data'], true);

            // Decode parks_selection JSON
            if (!empty($booking['parks_selection'])) {
                $booking['parks_selection'] = json_decode($booking['parks_selection'], true);
            }

            // Decode park_meal_options JSON
            if (!empty($booking['park_meal_options'])) {
                $booking['park_meal_options'] = json_decode($booking['park_meal_options'], true);
            }

            // Decode ensembles JSON
            if (!empty($booking['ensembles'])) {
                $booking['ensembles'] = json_decode($booking['ensembles'], true);
            } else {
                $booking['ensembles'] = array();
            }

            //  NEW: Decode hotel_data JSON
            if (!empty($booking['hotel_data'])) {
                $booking['hotel_data'] = json_decode($booking['hotel_data'], true);
            }
        }

        return $booking;
    }
    /**
     * Get bookings with filters
     */
    public static function get_bookings($blog_id, $args = array())
    {
        global $wpdb;
        $table_name = self::get_bookings_table_name();

        $defaults = array(
            'number'  => 20,
            'offset'  => 0,
            'orderby' => 'created_at',
            'order'   => 'DESC',
            'status'  => '',
            'user_id' => 0,
            'search'  => '',
        );
        $args = wp_parse_args($args, $defaults);

        $where_clauses = array();

        // Always filter by blog_id
        $where_clauses[] = $wpdb->prepare("blog_id = %d", $blog_id);

        // Filter by status
        if (!empty($args['status'])) {
            $where_clauses[] = $wpdb->prepare("status = %s", $args['status']);
        }

        // Filter by user_id
        if (!empty($args['user_id'])) {
            $where_clauses[] = $wpdb->prepare("user_id = %d", $args['user_id']);
        }

        // Filter by search
        if (!empty($args['search'])) {
            $search_term = '%' . $wpdb->esc_like($args['search']) . '%';
            $where_clauses[] = $wpdb->prepare(
                "(school_name LIKE %s OR location_name LIKE %s OR special_notes LIKE %s)",
                $search_term,
                $search_term,
                $search_term
            );
        }

        $where = implode(" AND ", $where_clauses);

        // Validate orderby
        $allowed_orderby = array('id', 'school_name', 'location_name', 'date_selection', 'status', 'created_at');
        $orderby = in_array($args['orderby'], $allowed_orderby) ? $args['orderby'] : 'created_at';
        $order = strtoupper($args['order']) === 'ASC' ? 'ASC' : 'DESC';

        $offset = intval($args['offset']);
        $limit = intval($args['number']);

        $sql = "SELECT * FROM {$table_name} WHERE {$where} ORDER BY {$orderby} {$order}";

        if ($limit > 0) {
            $sql .= " LIMIT {$limit} OFFSET {$offset}";
        } elseif ($limit === -1) {
            // -1 means get all results
        } else {
            $sql .= " LIMIT 20 OFFSET {$offset}";
        }

        $results = $wpdb->get_results($sql, ARRAY_A);

        // Decode JSON fields
        foreach ($results as &$result) {
            if (!empty($result['booking_data'])) {
                $result['booking_data'] = json_decode($result['booking_data'], true);
            }
            if (!empty($result['parks_selection'])) {
                $result['parks_selection'] = json_decode($result['parks_selection'], true);
            }
            if (!empty($result['park_meal_options'])) {
                $result['park_meal_options'] = json_decode($result['park_meal_options'], true);
            }
            if (!empty($result['ensembles'])) {
                $result['ensembles'] = json_decode($result['ensembles'], true);
            } else {
                $result['ensembles'] = array();
            }

            //  NEW: Decode hotel_data
            if (!empty($result['hotel_data'])) {
                $result['hotel_data'] = json_decode($result['hotel_data'], true);
            }
        }

        return $results;
    }


    /**
     * Update a booking
     */
    public static function update_booking($id, $blog_id, $booking_data = array(), $status = '')
    {
        global $wpdb;
        $table_name = self::get_bookings_table_name();

        $data = array();
        $format = array();

        //  Handle hotel_data as a separate column (JSON)
        if (isset($booking_data['hotel_data'])) {
            $data['hotel_data'] = wp_json_encode($booking_data['hotel_data']);
            $format[] = '%s';
            unset($booking_data['hotel_data']); // Remove from main booking_data to avoid duplication
        }

        //  Update main booking_data if there's remaining data
        if (!empty($booking_data)) {
            // Fetch existing booking_data to merge
            $existing = self::get_booking($id, $blog_id);
            if ($existing && !empty($existing['booking_data'])) {
                $merged_data = array_merge($existing['booking_data'], $booking_data);
                $data['booking_data'] = wp_json_encode($merged_data);
            } else {
                $data['booking_data'] = wp_json_encode($booking_data);
            }
            $format[] = '%s';
        }

        //  Update status if provided
        if (!empty($status)) {
            $data['status'] = $status;
            $format[] = '%s';
        }

        //  Nothing to update
        if (empty($data)) {
            return false;
        }

        //  Always update modified_at timestamp
        $data['modified_at'] = current_time('mysql');
        $format[] = '%s';

        $result = $wpdb->update(
            $table_name,
            $data,
            array('id' => $id, 'blog_id' => $blog_id),
            $format,
            array('%d', '%d')
        );

        return $result !== false;
    }


    /**
     * Delete a booking
     */
    public static function delete_booking($id, $blog_id)
    {
        global $wpdb;
        $table_name = self::get_bookings_table_name();

        $result = $wpdb->delete(
            $table_name,
            array('id' => $id, 'blog_id' => $blog_id),
            array('%d', '%d')
        );

        return $result !== false;
    }

    /**
     * Get booking statistics
     */
    public static function get_statistics($blog_id = null)
    {
        global $wpdb;

        if (empty($blog_id)) {
            $blog_id = get_current_blog_id();
        }

        $table = self::get_bookings_table_name();

        $total = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table} WHERE blog_id = %d",
            $blog_id
        ));

        $statuses = array('pending', 'confirmed', 'cancelled', 'completed');
        $counts = array();

        foreach ($statuses as $status) {
            $counts[$status] = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$table} WHERE blog_id = %d AND status = %s",
                $blog_id,
                $status
            ));
        }

        return array_merge(array('total' => $total), $counts);
    }

    /**
     * Count total bookings
     */
    public static function count_bookings($blog_id = null)
    {
        global $wpdb;

        if (empty($blog_id)) {
            $blog_id = get_current_blog_id();
        }

        $table = self::get_bookings_table_name();

        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table} WHERE blog_id = %d",
            $blog_id
        ));
    }

    // ========================================
    // SCHOOLS MANAGEMENT (FIXED VERSION)
    // ========================================

    /**
     * Get user's schools from database table
     *  FIXED: Proper error handling & logging
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

        $schools_table = self::get_schools_table_name();

        //  STEP 1: Validate table exists
        $table_exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $schools_table));

        if (!$table_exists) {
            return array();
        }

        //  STEP 2: Query schools
        $query = $wpdb->prepare(
            "SELECT * FROM $schools_table 
             WHERE user_id = %d AND blog_id = %d AND status = 'active' 
             ORDER BY created_at DESC",
            $user_id,
            $blog_id
        );

        $schools = $wpdb->get_results($query, ARRAY_A);

        //  STEP 3: Check for errors
        if ($wpdb->last_error) {
            return array();
        }

        return is_array($schools) ? $schools : array();
    }

    /**
     * Get single school by ID
     *  FIXED: Proper validation
     * 
     * @param int $school_id School ID
     * @param int $blog_id Blog ID
     * @return array|null School data
     */
    public static function get_school($school_id, $blog_id = null)
    {
        global $wpdb;

        if (empty($blog_id)) {
            $blog_id = get_current_blog_id();
        }

        $schools_table = self::get_schools_table_name();

        $query = $wpdb->prepare(
            "SELECT * FROM $schools_table WHERE id = %d AND blog_id = %d",
            $school_id,
            $blog_id
        );

        $school = $wpdb->get_row($query, ARRAY_A);

        if ($wpdb->last_error) {
            return null;
        }

        return $school;
    }

    /**
     * Add a new school
     *  FIXED: Full validation & error handling
     * 
     * @param int   $user_id User ID
     * @param int   $blog_id Blog ID
     * @param array $school_data School data
     * @return int|false School ID or false
     */
    public static function add_school($user_id, $blog_id, $school_data)
    {
        global $wpdb;
        $schools_table = self::get_schools_table_name();

        //  Validate required fields
        if (empty($school_data['school_name'])) {
            return false;
        }

        $insert_data = array(
            'blog_id'               => intval($blog_id),
            'user_id'               => intval($user_id),
            'school_name'           => sanitize_text_field($school_data['school_name'] ?? ''),
            'school_address'        => sanitize_text_field($school_data['school_address'] ?? ''),
            'school_address_2'      => sanitize_text_field($school_data['school_address_2'] ?? ''),
            'school_city'           => sanitize_text_field($school_data['school_city'] ?? ''),
            'school_state'          => sanitize_text_field($school_data['school_state'] ?? ''),
            'school_zip'            => sanitize_text_field($school_data['school_zip'] ?? ''),
            'school_country'        => sanitize_text_field($school_data['school_country'] ?? 'USA'),
            'school_phone'          => sanitize_text_field($school_data['school_phone'] ?? ''),
            'school_website'        => sanitize_url($school_data['school_website'] ?? ''),
            'school_enrollment'     => intval($school_data['school_enrollment'] ?? 0),
            'school_notes'          => sanitize_textarea_field($school_data['school_notes'] ?? ''),
            'director_prefix'       => sanitize_text_field($school_data['director_prefix'] ?? ''),
            'director_first_name'   => sanitize_text_field($school_data['director_first_name'] ?? ''),
            'director_last_name'    => sanitize_text_field($school_data['director_last_name'] ?? ''),
            'director_email'        => sanitize_email($school_data['director_email'] ?? ''),
            'director_cell_phone'   => sanitize_text_field($school_data['director_cell_phone'] ?? ''),
            'principal_prefix'      => sanitize_text_field($school_data['principal_prefix'] ?? ''),
            'principal_first_name'  => sanitize_text_field($school_data['principal_first_name'] ?? ''),
            'principal_last_name'   => sanitize_text_field($school_data['principal_last_name'] ?? ''),
            'status'                => 'active',
        );

        $format = array(
            '%d',
            '%d',
            '%s',
            '%s',
            '%s',
            '%s',
            '%s',
            '%s',
            '%s',
            '%s',
            '%s',
            '%d',
            '%s',
            '%s',
            '%s',
            '%s',
            '%s',
            '%s',
            '%s',
            '%s',
            '%s',
            '%s',
        );

        $result = $wpdb->insert($schools_table, $insert_data, $format);

        if (!$result) {
            return false;
        }

        $school_id = $wpdb->insert_id;
        return $school_id;
    }

    /**
     * Update school
     *  FIXED: Better error handling
     */
    public static function update_school($school_id, $blog_id, $school_data)
    {
        global $wpdb;
        $schools_table = self::get_schools_table_name();

        $update_data = array(
            'school_name'           => sanitize_text_field($school_data['school_name'] ?? ''),
            'school_address'        => sanitize_text_field($school_data['school_address'] ?? ''),
            'school_address_2'      => sanitize_text_field($school_data['school_address_2'] ?? ''),
            'school_city'           => sanitize_text_field($school_data['school_city'] ?? ''),
            'school_state'          => sanitize_text_field($school_data['school_state'] ?? ''),
            'school_zip'            => sanitize_text_field($school_data['school_zip'] ?? ''),
            'school_country'        => sanitize_text_field($school_data['school_country'] ?? ''),
            'school_phone'          => sanitize_text_field($school_data['school_phone'] ?? ''),
            'school_website'        => sanitize_url($school_data['school_website'] ?? ''),
            'school_enrollment'     => intval($school_data['school_enrollment'] ?? 0),
            'school_notes'          => sanitize_textarea_field($school_data['school_notes'] ?? ''),
        );

        $format = array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s');

        $result = $wpdb->update(
            $schools_table,
            $update_data,
            array('id' => $school_id, 'blog_id' => $blog_id),
            $format,
            array('%d', '%d')
        );

        if ($wpdb->last_error) {
            return false;
        }
        return $result !== false;
    }

    /**
     * Delete school (soft delete)
     *  FIXED: Proper soft delete
     */
    public static function delete_school($school_id, $blog_id)
    {
        global $wpdb;
        $schools_table = self::get_schools_table_name();

        $result = $wpdb->update(
            $schools_table,
            array('status' => 'inactive'),
            array('id' => $school_id, 'blog_id' => $blog_id),
            array('%s'),
            array('%d', '%d')
        );

        if ($wpdb->last_error) {
            return false;
        }

        return $result !== false;
    }

    // ========================================
    // HELPER METHODS
    // ========================================

    /**
     * Get location name by ID
     */
    public static function get_location_name($location_id)
    {
        if (!$location_id) {
            return '';
        }

        $location = get_term($location_id, 'location');
        if ($location && !is_wp_error($location)) {
            return $location->name;
        }

        return '';
    }

    /**
     * Get park names from park IDs
     */
    public static function get_park_names($parks, $other_park_name = '')
    {
        if (empty($parks) || !is_array($parks)) {
            return array();
        }

        $park_names = array();

        foreach ($parks as $park_id) {
            if ($park_id === 'other' || strtolower($park_id) === 'other') {
                if (!empty($other_park_name)) {
                    $park_names[] = 'Other: ' . $other_park_name;
                }
                continue;
            }

            $park_term = get_term($park_id, 'parks');
            if ($park_term && !is_wp_error($park_term)) {
                $park_names[] = $park_term->name;
            }
        }

        return $park_names;
    }

    /**
     *  NEW: Save or update booking draft
     * 
     * @param int $user_id User ID
     * @param int $package_id Package ID
     * @param array $booking_data Sanitized booking data
     * @param int|null $draft_id Existing draft ID (if updating)
     * @return int|false Draft ID or false on failure
     */
    public static function save_booking_draft($user_id, $package_id, $booking_data, $draft_id = null)
    {
        global $wpdb;
        $table_name = self::get_bookings_table_name();
        $blog_id = get_current_blog_id();

        // Prepare insert/update data
        $db_data = array(
            'blog_id' => $blog_id,
            'user_id' => $user_id,
            'package_id' => $package_id,
            'booking_data' => wp_json_encode($booking_data), // Store full data as JSON backup
            'status' => 'draft',
            'modified_at' => current_time('mysql'),
        );

        // Extract individual fields for easier querying
        if (isset($booking_data['school_id'])) {
            $db_data['school_id'] = $booking_data['school_id'];
        }
        if (isset($booking_data['location_id'])) {
            $db_data['location_id'] = $booking_data['location_id'];
        }
        if (isset($booking_data['date_selection'])) {
            $db_data['date_selection'] = $booking_data['date_selection'];
        }
        //  ADDED: Handle lodging_dates and special_notes
        if (isset($booking_data['lodging_dates'])) {
            $db_data['lodging_dates'] = sanitize_text_field($booking_data['lodging_dates']);
        }
        if (isset($booking_data['special_notes'])) {
            $db_data['special_notes'] = sanitize_textarea_field($booking_data['special_notes']);
        }
        if (isset($booking_data['transportation'])) {
            $db_data['transportation'] = sanitize_text_field($booking_data['transportation']);
        }
        if (isset($booking_data['meal_vouchers'])) {
            $db_data['meal_vouchers'] = intval($booking_data['meal_vouchers']);
        }
        if (isset($booking_data['meals_per_day'])) {
            $db_data['meals_per_day'] = intval($booking_data['meals_per_day']);
        }

        //  CRITICAL: Handle parks_selection (must be JSON string)
        if (isset($booking_data['parks_selection']) && is_array($booking_data['parks_selection'])) {
            $db_data['parks_selection'] = wp_json_encode($booking_data['parks_selection']);
        }

        if (isset($booking_data['other_park_name'])) {
            $db_data['other_park_name'] = $booking_data['other_park_name'];
        }
        if (isset($booking_data['total_students'])) {
            $db_data['total_students'] = $booking_data['total_students'];
        }
        if (isset($booking_data['total_chaperones'])) {
            $db_data['total_chaperones'] = $booking_data['total_chaperones'];
        }

        //  CRITICAL: Handle park_meal_options (must be JSON string)
        if (isset($booking_data['park_meal_options']) && !empty($booking_data['park_meal_options'])) {
            $db_data['park_meal_options'] = wp_json_encode($booking_data['park_meal_options']);
        }

        //  CRITICAL: Handle ensembles (must be JSON string)
        if (isset($booking_data['ensembles']) && !empty($booking_data['ensembles'])) {
            $db_data['ensembles'] = wp_json_encode($booking_data['ensembles']);
        }

        if ($draft_id) {
            // Update existing draft
            $result = $wpdb->update(
                $table_name,
                $db_data,
                array(
                    'id' => $draft_id,
                    'user_id' => $user_id,
                    'status' => 'draft'
                ),
                null,
                array('%d', '%d', '%s')
            );

            if ($result !== false) {
                return $draft_id;
            } else {
                return false;
            }
        } else {
            // Create new draft
            $db_data['created_at'] = current_time('mysql');

            $result = $wpdb->insert($table_name, $db_data);

            if ($result) {
                $new_id = $wpdb->insert_id;
                return $new_id;
            } else {
                return false;
            }
        }
    }

    /**
     *  NEW: Get user's draft for a package
     * 
     * @param int $user_id User ID
     * @param int $package_id Package ID
     * @return object|null Draft object or null
     */
    public static function get_user_draft($user_id, $package_id)
    {
        global $wpdb;
        $table_name = self::get_bookings_table_name();

        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name 
         WHERE user_id = %d 
         AND package_id = %d 
         AND status = 'draft' 
         ORDER BY modified_at DESC 
         LIMIT 1",
            $user_id,
            $package_id
        ));
    }


    /**
     * Convert draft to pending (final confirmation)
     */
    public static function confirm_booking_draft($draft_id, $user_id, $final_data = array())
    {
        global $wpdb;
        $table_name = $wpdb->base_prefix . 'bookings';

        $update_data = array(
            'status' => 'pending',
            'modified_at' => current_time('mysql')
        );

        // Update any final data if provided
        if (!empty($final_data)) {
            if (isset($final_data['ensembles'])) {
                $update_data['ensembles'] = wp_json_encode($final_data['ensembles']);
            }
        }

        $result = $wpdb->update(
            $table_name,
            $update_data,
            array(
                'id' => $draft_id,
                'user_id' => $user_id,
                'status' => 'draft'
            ),
            null,
            array('%d', '%d', '%s')
        );

        if ($result !== false) {
            return $draft_id;
        }

        return false;
    }

    /**
     * Clean old drafts (use with WP Cron)
     */
    public static function clean_old_drafts($days = 7)
    {
        global $wpdb;
        $table_name = $wpdb->base_prefix . 'bookings';

        $deleted = $wpdb->query($wpdb->prepare(
            "DELETE FROM $table_name 
             WHERE status = 'draft' 
             AND modified_at < DATE_SUB(NOW(), INTERVAL %d DAY)",
            $days
        ));

        if ($deleted > 0) {
            error_log("Cleaned $deleted old draft bookings");
        }

        return $deleted;
    }

    /**
     * Get bookings with due dates for cron processing
     * 
     * Returns bookings that have:
     * - Hotel data assigned
     * - Due date set in hotel_data
     * - Status is not 'cancelled' or 'completed'
     * 
     * @return array Array of bookings with due dates
     */
    public static function get_bookings_with_due_dates()
    {
        global $wpdb;
        $table_name = self::get_bookings_table_name();

        // Get all bookings that have hotel_data
        $results = $wpdb->get_results(
            "SELECT id, blog_id, hotel_data, status, created_at 
             FROM {$table_name} 
             WHERE hotel_data IS NOT NULL 
             AND hotel_data != '' 
             AND hotel_data != 'null'
             AND status NOT IN ('cancelled', 'completed')
             ORDER BY created_at DESC",
            ARRAY_A
        );

        if (empty($results)) {
            return array();
        }

        $bookings_with_due_dates = array();

        foreach ($results as $booking) {
            // Decode hotel_data
            $hotel_data = json_decode($booking['hotel_data'], true);

            // Check if due_date exists and is valid
            if (
                is_array($hotel_data) &&
                isset($hotel_data['due_date']) &&
                !empty($hotel_data['due_date'])
            ) {
                // Add to results with extracted data
                $bookings_with_due_dates[] = array(
                    'id'          => $booking['id'],
                    'blog_id'     => $booking['blog_id'],
                    'status'      => $booking['status'],
                    'due_date'    => $hotel_data['due_date'],
                    'hotel_id'    => $hotel_data['hotel_id'] ?? null,
                    'hotel_name'  => $hotel_data['hotel_name'] ?? null,
                    'checkin_date' => $hotel_data['checkin_date'] ?? null,
                    'checkout_date' => $hotel_data['checkout_date'] ?? null,
                );
            }
        }

        return $bookings_with_due_dates;
    }
}
