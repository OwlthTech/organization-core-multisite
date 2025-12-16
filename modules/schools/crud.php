<?php

/**
 * Schools Module - Database CRUD Operations
 * 
 * Handles ALL Create, Read, Update, Delete operations using wp_schools table
 * NO user_meta - Pure database operations for multisite
 * 
 * Features:
 * - Direct wpdb queries
 * - blog_id field for multisite
 * - user_id as foreign key
 * - Proper error handling
 * - Validation & sanitization
 * - Action hooks for extensibility
 * 
 * @package Organization_Core
 * @subpackage Modules/Schools
 * @since 1.0.0
 */

if (! defined('WPINC')) {
    die;
}

class OC_Schools_CRUD
{
    // ================================================
    // CREATE OPERATIONS
    // ================================================

    /**
     * Create new school
     * 
     * @param int   $user_id User ID
     * @param array $school_data School information
     * @return int|false School ID on success, false on failure
     */
    public static function create_school($user_id, $school_data)
    {
        global $wpdb;


        // Validate inputs
        if (empty($user_id) || empty($school_data) || ! is_array($school_data)) {
            return false;
        }

        // Validate school data
        $validation = self::validate_school_data($school_data);
        if (! $validation['valid']) {
            return false;
        }

        // Sanitize data
        $sanitized = self::sanitize_school_data($school_data);

        // Prepare insert data
        $insert_data = [
            'blog_id' => get_current_blog_id(),
            'user_id' => $user_id,
            'school_name' => $sanitized['school_name'],
            'school_address' => $sanitized['school_address'],
            'school_address_2' => $sanitized['school_address_2'],
            'school_city' => $sanitized['school_city'],
            'school_state' => $sanitized['school_state'],
            'school_zip' => $sanitized['school_zip'],
            'school_country' => $sanitized['school_country'],
            'school_phone' => $sanitized['school_phone'],
            'school_website' => $sanitized['school_website'],
            'school_enrollment' => $sanitized['school_enrollment'],
            'school_notes' => $sanitized['school_notes'],
            'director_prefix' => $sanitized['director_prefix'],
            'director_first_name' => $sanitized['director_first_name'],
            'director_last_name' => $sanitized['director_last_name'],
            'director_email' => $sanitized['director_email'],
            'director_cell_phone' => $sanitized['director_cell_phone'],
            'principal_prefix' => $sanitized['principal_prefix'],
            'principal_first_name' => $sanitized['principal_first_name'],
            'principal_last_name' => $sanitized['principal_last_name'],
            'status' => 'active',
            'created_at' => current_time('mysql')
        ];

        // Define formats
        $format = array_fill(0, count($insert_data), '%s');
        $format[array_search('blog_id', array_keys($insert_data), true)] = '%d';
        $format[array_search('user_id', array_keys($insert_data), true)] = '%d';
        $format[array_search('school_enrollment', array_keys($insert_data), true)] = '%d';

        // Insert into database
        $table = $wpdb->base_prefix . 'schools';
        $result = $wpdb->insert($table, $insert_data, $format);

        if ($result) {
            $school_id = $wpdb->insert_id;
            do_action('mus_school_created', $user_id, $school_id, $insert_data);
            return $school_id;
        }
        return false;
    }

    // ================================================
    // READ OPERATIONS
    // ================================================

    /**
     * Get single school by ID
     * 
     * @param int $school_id School ID
     * @param int $user_id User ID (for verification)
     * @return array|false School data or false
     */
    public static function get_school($school_id, $user_id = 0)
    {
        global $wpdb;

        if (empty($school_id)) {
            return false;
        }

        $table = $wpdb->base_prefix . 'schools';

        if (! empty($user_id)) {
            // Verify ownership
            $query = $wpdb->prepare(
                "SELECT * FROM $table WHERE id = %d AND user_id = %d",
                $school_id,
                $user_id
            );
        } else {
            $query = $wpdb->prepare(
                "SELECT * FROM $table WHERE id = %d",
                $school_id
            );
        }

        $school = $wpdb->get_row($query, ARRAY_A);

        if ($school) {
            return self::format_school($school);
        }
        return false;
    }

    /**
     * Get all schools for user
     * 
     * @param int $user_id User ID
     * @return array Schools array
     */
    public static function get_user_schools($user_id)
    {
        global $wpdb;

        if (empty($user_id)) {
            return [];
        }

        $table = $wpdb->base_prefix . 'schools';

        $query = $wpdb->prepare(
            "SELECT * FROM $table WHERE user_id = %d AND blog_id = %d ORDER BY created_at DESC",
            $user_id,
            get_current_blog_id()
        );

        $schools = $wpdb->get_results($query, ARRAY_A);

        if (! $schools) {
            return [];
        }

        // Format each school
        $formatted = [];
        foreach ($schools as $school) {
            $formatted[$school['id']] = self::format_school($school);
        }

        return $formatted;
    }

    /**
     * Get paginated schools
     * 
     * @param int $user_id User ID
     * @param int $page Page number
     * @param int $per_page Items per page
     * @return array Result with items, total, pages
     */
    public static function get_user_schools_paginated($user_id, $page = 1, $per_page = 10)
    {
        global $wpdb;

        if (empty($user_id)) {
            return ['items' => [], 'total' => 0, 'pages' => 0, 'current_page' => 1];
        }

        $table = $wpdb->base_prefix . 'schools';
        $offset = ($page - 1) * $per_page;

        // Get total count
        $total = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table WHERE user_id = %d AND blog_id = %d",
            $user_id,
            get_current_blog_id()
        ));

        // Get paginated results
        $items = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table WHERE user_id = %d AND blog_id = %d ORDER BY created_at DESC LIMIT %d OFFSET %d",
            $user_id,
            get_current_blog_id(),
            $per_page,
            $offset
        ), ARRAY_A);

        // Format items
        $formatted = [];
        foreach ($items as $item) {
            $formatted[$item['id']] = self::format_school($item);
        }

        return [
            'items' => $formatted,
            'total' => intval($total),
            'pages' => ceil($total / $per_page),
            'current_page' => $page
        ];
    }

    // ================================================
    // UPDATE OPERATIONS
    // ================================================

    /**
     * Update school
     * 
     * @param int   $school_id School ID
     * @param int   $user_id User ID
     * @param array $school_data Updated data
     * @return bool True on success
     */
    public static function update_school($school_id, $user_id, $school_data)
    {
        global $wpdb;

        if (empty($school_id) || empty($user_id) || empty($school_data)) {
            return false;
        }

        // Validate
        $validation = self::validate_school_data($school_data);
        if (! $validation['valid']) {
            return false;
        }

        // Sanitize
        $sanitized = self::sanitize_school_data($school_data);

        // Prepare update data
        $update_data = [
            'school_name' => $sanitized['school_name'],
            'school_address' => $sanitized['school_address'],
            'school_address_2' => $sanitized['school_address_2'],
            'school_city' => $sanitized['school_city'],
            'school_state' => $sanitized['school_state'],
            'school_zip' => $sanitized['school_zip'],
            'school_country' => $sanitized['school_country'],
            'school_phone' => $sanitized['school_phone'],
            'school_website' => $sanitized['school_website'],
            'school_enrollment' => $sanitized['school_enrollment'],
            'school_notes' => $sanitized['school_notes'],
            'director_prefix' => $sanitized['director_prefix'],
            'director_first_name' => $sanitized['director_first_name'],
            'director_last_name' => $sanitized['director_last_name'],
            'director_email' => $sanitized['director_email'],
            'director_cell_phone' => $sanitized['director_cell_phone'],
            'principal_prefix' => $sanitized['principal_prefix'],
            'principal_first_name' => $sanitized['principal_first_name'],
            'principal_last_name' => $sanitized['principal_last_name'],
            'modified_at' => current_time('mysql')
        ];

        // Define formats
        $format = array_fill(0, count($update_data), '%s');
        $format[array_search('school_enrollment', array_keys($update_data), true)] = '%d';

        // Update database
        $table = $wpdb->base_prefix . 'schools';
        $result = $wpdb->update(
            $table,
            $update_data,
            ['id' => $school_id, 'user_id' => $user_id],
            $format,
            ['%d', '%d']
        );

        if (false !== $result) {
            do_action('mus_school_updated', $user_id, $school_id, $update_data);
            return true;
        }
        return false;
    }

    // ================================================
    // DELETE OPERATIONS
    // ================================================

    /**
     * Delete school
     * 
     * @param int $school_id School ID
     * @param int $user_id User ID
     * @return bool True on success
     */
    public static function delete_school($school_id, $user_id)
    {
        global $wpdb;

        if (empty($school_id) || empty($user_id)) {
            return false;
        }

        $table = $wpdb->base_prefix . 'schools';
        $blog_id = get_current_blog_id();

        // ✅ CRITICAL: Build delete query with logging
        $where = [
            'id' => $school_id,
            'user_id' => $user_id,
            'blog_id' => $blog_id
        ];
        $where_format = ['%d', '%d', '%d'];

        // ✅ Execute delete
        $result = $wpdb->delete($table, $where, $where_format);

        // ✅ CRITICAL: Check for errors
        if ($wpdb->last_error) {
            return false;
        }

        if (false !== $result && $result >= 0) {
            do_action('mus_school_deleted', $user_id, $school_id);
            return true;
        }

        return false;
    }

    /**
     * Delete all user schools
     * 
     * @param int $user_id User ID
     * @return bool True on success
     */
    public static function delete_all_user_schools($user_id)
    {
        global $wpdb;

        if (empty($user_id)) {
            return false;
        }

        $table = $wpdb->base_prefix . 'schools';

        $result = $wpdb->delete(
            $table,
            ['user_id' => $user_id],
            ['%d']
        );

        if (false !== $result) {
            do_action('mus_all_schools_deleted', $user_id);
            return true;
        }

        return false;
    }

    // ================================================
    // SEARCH & FILTER
    // ================================================

    /**
     * Search schools
     * 
     * @param int    $user_id User ID
     * @param string $search Search term
     * @return array Matching schools
     */
    public static function search_schools($user_id, $search)
    {
        global $wpdb;

        if (empty($user_id) || empty($search)) {
            return [];
        }

        $table = $wpdb->base_prefix . 'schools';
        $search_term = '%' . $wpdb->esc_like($search) . '%';

        $query = $wpdb->prepare(
            "SELECT * FROM $table 
             WHERE user_id = %d AND blog_id = %d 
             AND (
                school_name LIKE %s 
                OR school_city LIKE %s 
                OR school_state LIKE %s 
                OR director_first_name LIKE %s 
                OR director_last_name LIKE %s 
                OR director_email LIKE %s
             )
             ORDER BY created_at DESC",
            $user_id,
            get_current_blog_id(),
            $search_term,
            $search_term,
            $search_term,
            $search_term,
            $search_term,
            $search_term
        );

        $results = $wpdb->get_results($query, ARRAY_A);

        $formatted = [];
        foreach ($results as $item) {
            $formatted[$item['id']] = self::format_school($item);
        }

        return $formatted;
    }

    // ================================================
    // STATISTICS
    // ================================================

    /**
     * Count user schools
     * 
     * @param int $user_id User ID
     * @return int Count
     */
    public static function count_user_schools($user_id)
    {
        global $wpdb;

        $table = $wpdb->base_prefix . 'schools';

        return intval($wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table WHERE user_id = %d AND blog_id = %d",
            $user_id,
            get_current_blog_id()
        )));
    }

    /**
     * Check if school exists
     * 
     * @param int $school_id School ID
     * @param int $user_id User ID
     * @return bool True if exists
     */
    public static function school_exists($school_id, $user_id)
    {
        global $wpdb;

        $table = $wpdb->base_prefix . 'schools';

        $result = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table WHERE id = %d AND user_id = %d",
            $school_id,
            $user_id
        ));

        return ! empty($result);
    }

    // ================================================
    // VALIDATION
    // ================================================

    /**
     * Validate school data
     * 
     * @param array $data School data
     * @return array ['valid' => bool, 'errors' => array]
     */
    public static function validate_school_data($data)
    {
        $errors = [];

        $required = [
            'school_name' => 'School name is required',
            'school_address' => 'School address is required',
            'school_city' => 'City is required',
            'school_state' => 'State is required',
            'school_zip' => 'Zip code is required',
            'school_country' => 'Country is required',
            'school_phone' => 'Phone is required',
            'director_first_name' => 'Director first name is required',
            'director_last_name' => 'Director last name is required',
            'director_email' => 'Director email is required',
        ];

        foreach ($required as $field => $message) {
            if (empty($data[$field])) {
                $errors[] = $message;
            }
        }

        if (! empty($data['director_email']) && ! is_email($data['director_email'])) {
            $errors[] = 'Director email is invalid';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    // ================================================
    // SANITIZATION
    // ================================================

    /**
     * Sanitize school data
     * 
     * @param array $data Raw data
     * @return array Sanitized data
     */
    private static function sanitize_school_data($data)
    {
        return [
            'school_name' => sanitize_text_field($data['school_name'] ?? ''),
            'school_address' => sanitize_text_field($data['school_address'] ?? ''),
            'school_address_2' => sanitize_text_field($data['school_address_2'] ?? ''),
            'school_city' => sanitize_text_field($data['school_city'] ?? ''),
            'school_state' => sanitize_text_field($data['school_state'] ?? ''),
            'school_zip' => sanitize_text_field($data['school_zip'] ?? ''),
            'school_country' => sanitize_text_field($data['school_country'] ?? ''),
            'school_phone' => sanitize_text_field($data['school_phone'] ?? ''),
            'school_website' => esc_url_raw($data['school_website'] ?? ''),
            'director_prefix' => sanitize_text_field($data['director_prefix'] ?? ''),
            'director_first_name' => sanitize_text_field($data['director_first_name'] ?? ''),
            'director_last_name' => sanitize_text_field($data['director_last_name'] ?? ''),
            'director_email' => sanitize_email($data['director_email'] ?? ''),
            'director_cell_phone' => sanitize_text_field($data['director_cell_phone'] ?? ''),
            'principal_prefix' => sanitize_text_field($data['principal_prefix'] ?? ''),
            'principal_first_name' => sanitize_text_field($data['principal_first_name'] ?? ''),
            'principal_last_name' => sanitize_text_field($data['principal_last_name'] ?? ''),
            'school_enrollment' => intval($data['school_enrollment'] ?? 0),
            'school_notes' => sanitize_textarea_field($data['school_notes'] ?? ''),
        ];
    }

    // ================================================
    // FORMATTING
    // ================================================

    /**
     * Format school for display
     * 
     * @param array $school School data from database
     * @return array Formatted school
     */
    private static function format_school($school)
    {
        return [
            'id' => intval($school['id']),
            'school_name' => $school['school_name'],
            'school_address' => $school['school_address'],
            'school_address_2' => $school['school_address_2'],
            'school_city' => $school['school_city'],
            'school_state' => $school['school_state'],
            'school_zip' => $school['school_zip'],
            'school_country' => $school['school_country'],
            'school_phone' => $school['school_phone'],
            'school_website' => $school['school_website'],
            'director_prefix' => $school['director_prefix'],
            'director_first_name' => $school['director_first_name'],
            'director_last_name' => $school['director_last_name'],
            'director_email' => $school['director_email'],
            'director_cell_phone' => $school['director_cell_phone'],
            'principal_prefix' => $school['principal_prefix'],
            'principal_first_name' => $school['principal_first_name'],
            'principal_last_name' => $school['principal_last_name'],
            'school_enrollment' => intval($school['school_enrollment']),
            'school_notes' => $school['school_notes'],
            'status' => $school['status'],
            'created_at' => $school['created_at'],
            'modified_at' => $school['modified_at'],
            'full_address' => self::build_full_address($school),
            'director_name' => self::build_name($school, 'director'),
            'principal_name' => self::build_name($school, 'principal')
        ];
    }

    /**
     * Build full address
     * 
     * @param array $school School data
     * @return string Full address
     */
    private static function build_full_address($school)
    {
        $parts = array_filter([
            $school['school_address'],
            $school['school_address_2'],
            $school['school_city'],
            $school['school_state'],
            $school['school_zip']
        ]);

        return implode(', ', $parts);
    }

    /**
     * Build full name
     * 
     * @param array  $school School data
     * @param string $type Person type
     * @return string Full name
     */
    private static function build_name($school, $type)
    {
        $parts = array_filter([
            $school[$type . '_prefix'],
            $school[$type . '_first_name'],
            $school[$type . '_last_name']
        ]);

        return implode(' ', $parts);
    }
}

// ================================================
// GLOBAL HELPER FUNCTIONS
// ================================================

function get_user_schools_for_display($user_id)
{
    return OC_Schools_CRUD::get_user_schools($user_id);
}

function get_user_school($user_id, $school_id)
{
    return OC_Schools_CRUD::get_school($school_id, $user_id);
}

function create_user_school($user_id, $data)
{
    return OC_Schools_CRUD::create_school($user_id, $data);
}

function update_user_school($user_id, $school_id, $data)
{
    return OC_Schools_CRUD::update_school($school_id, $user_id, $data);
}

function delete_user_school($user_id, $school_id)
{
    return OC_Schools_CRUD::delete_school($school_id, $user_id);
}
