<?php

/**
 * Hotels CRUD Operations
 */

if (!defined('ABSPATH')) {
    exit;
}

class OC_Hotels_CRUD
{
    /**
     * Create a new hotel
     * 
     * @param array $data Hotel data
     * @return int|false Inserted ID or false on failure
     */
    public static function create_hotel($data)
    {
        global $wpdb;
        $table_name = $wpdb->base_prefix . 'hotels';

        $defaults = array(
            'name' => '',
            'address' => '',
            'number_of_person' => 0,
            'number_of_rooms' => 0
        );

        $data = wp_parse_args($data, $defaults);

        $format = array(
            '%s', // name
            '%s', // address
            '%d', // number_of_person
            '%d'  // number_of_rooms
        );

        $result = $wpdb->insert($table_name, $data, $format);

        if ($result) {
            return $wpdb->insert_id;
        }

        return false;
    }

    /**
     * Get a single hotel by ID
     * 
     * @param int $id Hotel ID
     * @return object|null Hotel object or null
     */
    public static function get_hotel_by_id($id)
    {
        global $wpdb;
        $table_name = $wpdb->base_prefix . 'hotels';

        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE id = %d",
            $id
        ));
    }

    /**
     * Update a hotel
     * 
     * @param int $id Hotel ID
     * @param array $data Hotel data
     * @return bool True on success, false on failure
     */
    public static function update_hotel($id, $data)
    {
        global $wpdb;
        $table_name = $wpdb->base_prefix . 'hotels';

        $defaults = array(
            'name' => '',
            'address' => '',
            'number_of_person' => 0,
            'number_of_rooms' => 0
        );

        $data = wp_parse_args($data, $defaults);

        $format = array(
            '%s', // name
            '%s', // address
            '%d', // number_of_person
            '%d'  // number_of_rooms
        );

        return $wpdb->update(
            $table_name,
            $data,
            array('id' => $id),
            $format,
            array('%d')
        );
    }

    /**
     * Get hotels
     * 
     * @param array $args Query arguments
     * @return array List of hotels
     */
    public static function get_hotels($args = array())
    {
        global $wpdb;
        $table_name = $wpdb->base_prefix . 'hotels';

        $defaults = array(
            'limit' => 20,
            'offset' => 0,
            'orderby' => 'id',
            'order' => 'DESC',
            'search' => '',
            'rooms_filter' => '',
            'capacity_filter' => ''
        );

        $args = wp_parse_args($args, $defaults);

        $query = "SELECT * FROM $table_name";
        $where = array();
        $params = array();

        if (!empty($args['search'])) {
            $where[] = "name LIKE %s";
            $params[] = '%' . $wpdb->esc_like($args['search']) . '%';
        }

        if (!empty($args['rooms_filter'])) {
            $where[] = "number_of_rooms = %d";
            $params[] = intval($args['rooms_filter']);
        }

        if (!empty($args['capacity_filter'])) {
            $where[] = "number_of_person = %d";
            $params[] = intval($args['capacity_filter']);
        }

        if (!empty($where)) {
            $query .= " WHERE " . implode(' AND ', $where);
        }

        $query .= " ORDER BY " . esc_sql($args['orderby']) . " " . esc_sql($args['order']);
        
        if ($args['limit'] > 0) {
            $query .= " LIMIT %d OFFSET %d";
            $params[] = $args['limit'];
            $params[] = $args['offset'];
        }

        if (!empty($params)) {
            return $wpdb->get_results($wpdb->prepare($query, $params));
        }

        return $wpdb->get_results($query);
    }

    /**
     * Get total hotels count
     * 
     * @param array $args Query arguments
     * @return int Total count
     */
    public static function get_total_hotels($args = array())
    {
        global $wpdb;
        $table_name = $wpdb->base_prefix . 'hotels';

        $defaults = array(
            'search' => '',
            'rooms_filter' => '',
            'capacity_filter' => ''
        );

        $args = wp_parse_args($args, $defaults);

        $query = "SELECT COUNT(*) FROM $table_name";
        $where = array();
        $params = array();

        if (!empty($args['search'])) {
            $where[] = "name LIKE %s";
            $params[] = '%' . $wpdb->esc_like($args['search']) . '%';
        }

        if (!empty($args['rooms_filter'])) {
            $where[] = "number_of_rooms = %d";
            $params[] = intval($args['rooms_filter']);
        }

        if (!empty($args['capacity_filter'])) {
            $where[] = "number_of_person = %d";
            $params[] = intval($args['capacity_filter']);
        }

        if (!empty($where)) {
            $query .= " WHERE " . implode(' AND ', $where);
        }

        if (!empty($params)) {
            return $wpdb->get_var($wpdb->prepare($query, $params));
        }

        return $wpdb->get_var($query);
    }

    /**
     * Delete a hotel
     * 
     * @param int $id Hotel ID
     * @return bool True on success, false on failure
     */
    public static function delete_hotel($id)
    {
        global $wpdb;
        $table_name = $wpdb->base_prefix . 'hotels';

        return $wpdb->delete($table_name, array('id' => $id), array('%d'));
    }
}
