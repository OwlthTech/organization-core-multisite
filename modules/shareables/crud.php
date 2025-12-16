<?php

/**
 * CRUD Operations for Shareables
 *
 * @package    Organization_Core
 * @subpackage Organization_Core/modules/shareables
 */

if (!defined('ABSPATH')) {
    exit;
}

class OC_Shareables_CRUD {

    /**
     * Get the table name.
     */
    public static function get_table_name() {
        global $wpdb;
        return $wpdb->base_prefix . 'shareables';
    }

    /**
     * Create a new shareable item.
     *
     * @param array $data Column data.
     * @return int|false Inserted ID or false on error.
     */
    public static function create_item($data) {
        global $wpdb;
        
        $defaults = array(
            'blog_id' => get_current_blog_id(),
            'status' => 'draft',
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql'),
        );

        $data = wp_parse_args($data, $defaults);

        $result = $wpdb->insert(self::get_table_name(), $data);

        return $result ? $wpdb->insert_id : false;
    }

    /**
     * Update a shareable item.
     *
     * @param int $id ID of the item.
     * @param array $data Column data.
     * @return int|false Number of rows affected or false on error.
     */
    public static function update_item($id, $data) {
        global $wpdb;

        $data['updated_at'] = current_time('mysql');
        
        return $wpdb->update(
            self::get_table_name(),
            $data,
            array('id' => $id),
        );
    }

    /**
     * Delete a shareable item.
     *
     * @param int $id ID of the item.
     * @return int|false Number of rows affected or false on error.
     */
    public static function delete_item($id) {
        global $wpdb;
        return $wpdb->delete(
            self::get_table_name(),
            array('id' => $id)
        );
    }

    /**
     * Get a single item by ID.
     *
     * @param int $id The ID.
     * @return object|null Row object or null.
     */
    public static function get_item($id) {
        global $wpdb;
        $table = self::get_table_name();
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $id));
    }

    /**
     * Get a single item by UUID.
     *
     * @param string $uuid The UUID.
     * @return object|null Row object or null.
     */
    public static function get_item_by_uuid($uuid) {
        global $wpdb;
        $table = self::get_table_name();
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE uuid = %s", $uuid));
    }

    /**
     * Get items for the current blog.
     *
     * @param int $limit Limit results.
     * @param int $offset Offset.
     * @param string $search Search term.
     * @return array List of objects.
     */
    public static function get_items($limit = 20, $offset = 0, $search = '') {
        global $wpdb;
        $table = self::get_table_name();
        $blog_id = get_current_blog_id();

        $where = "WHERE blog_id = %d";
        $args = array($blog_id);

        if (!empty($search)) {
            $where .= " AND title LIKE %s";
            $args[] = '%' . $wpdb->esc_like($search) . '%';
        }

        $query = "SELECT * FROM $table $where ORDER BY created_at DESC LIMIT %d OFFSET %d";
        $args[] = $limit;
        $args[] = $offset;

        return $wpdb->get_results($wpdb->prepare($query, $args));
    }

    /**
     * Get total count of items.
     * 
     * @param string $search Search term.
     * @return int Count.
     */
    public static function get_total_count($search = '') {
        global $wpdb;
        $table = self::get_table_name();
        $blog_id = get_current_blog_id();

        $where = "WHERE blog_id = %d";
        $args = array($blog_id);

        if (!empty($search)) {
            $where .= " AND title LIKE %s";
            $args[] = '%' . $wpdb->esc_like($search) . '%';
        }

        $query = "SELECT COUNT(*) FROM $table $where";
        
        return $wpdb->get_var($wpdb->prepare($query, $args));
    }
}
