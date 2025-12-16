<?php

/**
 * Rooming List CRUD Operations
 */

if (!defined('ABSPATH')) {
    exit;
}

class OC_Rooming_List_CRUD
{
    /**
     * Get rooming list table name
     */
    public static function get_table_name()
    {
        global $wpdb;
        return $wpdb->base_prefix . 'rooming_list';
    }

    /**
     * Get rooming list for a booking
     */
    public static function get_rooming_list($booking_id)
    {
        global $wpdb;
        $table_name = self::get_table_name();

        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_name WHERE booking_id = %d ORDER BY room_number ASC, id ASC",
            $booking_id
        ), ARRAY_A);
    }

    /**
     * Update rooming list (Sync)
     * 
     * @param int $booking_id
     * @param array $rooming_list Array of ['room_number', 'occupant_name', 'occupant_type', 'is_locked']
     */
    public static function update_rooming_list($booking_id, $rooming_list)
    {
        global $wpdb;
        $table_name = self::get_table_name();

        // 1. Get existing items to track what to keep/delete
        $existing_items = self::get_rooming_list($booking_id);
        $existing_ids = wp_list_pluck($existing_items, 'id');
        
        $kept_ids = [];

        foreach ($rooming_list as $item) {
            $data = [
                'booking_id' => $booking_id,
                'room_number' => sanitize_text_field($item['room_number']),
                'occupant_name' => sanitize_text_field($item['occupant_name']),
                'occupant_type' => sanitize_text_field($item['occupant_type']),
                'is_locked' => isset($item['is_locked']) ? intval($item['is_locked']) : 0,
            ];

            if (isset($item['id']) && in_array($item['id'], $existing_ids)) {
                // Update existing
                $wpdb->update($table_name, $data, ['id' => $item['id']], ['%d', '%s', '%s', '%s', '%d'], ['%d']);
                $kept_ids[] = $item['id'];
            } else {
                // Insert new
                $wpdb->insert($table_name, $data, ['%d', '%s', '%s', '%s', '%d']);
                $kept_ids[] = $wpdb->insert_id;
            }
        }

        // 2. Delete removed items
        $ids_to_delete = array_diff($existing_ids, $kept_ids);
        if (!empty($ids_to_delete)) {
            $ids_str = implode(',', array_map('intval', $ids_to_delete));
            $wpdb->query("DELETE FROM $table_name WHERE id IN ($ids_str)");
        }

        return true;
    }

    /**
     * Lock/Unlock a specific rooming list item
     */
    public static function toggle_lock_rooming_list_item($item_id, $is_locked)
    {
        global $wpdb;
        $table_name = self::get_table_name();

        return $wpdb->update(
            $table_name,
            ['is_locked' => $is_locked ? 1 : 0],
            ['id' => $item_id],
            ['%d'],
            ['%d']
        );
    }
}
