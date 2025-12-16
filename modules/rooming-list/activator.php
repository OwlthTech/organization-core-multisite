<?php

/**
 * Rooming List Activator
 */

if (!defined('ABSPATH')) {
    exit;
}

class OC_Rooming_List_Activator
{

    /**
     * Run on plugin activation
     */
    public static function activate()
    {
        self::create_rooming_list_table();
        self::add_rewrite_rules();
        flush_rewrite_rules(false);
    }

    /**
     * Create rooming list table
     */
    public static function create_rooming_list_table()
    {
        global $wpdb;
        $table_name = $wpdb->base_prefix . 'rooming_list';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id BIGINT(20) NOT NULL AUTO_INCREMENT,
            booking_id BIGINT(20) NOT NULL,
            room_number VARCHAR(50) NOT NULL,
            occupant_name VARCHAR(255) NOT NULL,
            occupant_type VARCHAR(50) DEFAULT 'Student',
            is_locked TINYINT(1) DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            modified_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY booking_id (booking_id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Add rewrite rules for public rooming list page
     * This method is called BOTH on activation AND on init hook
     */
    public static function add_rewrite_rules()
    {
        add_rewrite_rule(
            '^my-account/rooming-list/([0-9]+)/?$',
            'index.php?account_page=rooming-list&booking_id=$matches[1]',
            'top'
        );
    }

}

// Register rewrite rules on every page load
add_action('init', array('OC_Rooming_List_Activator', 'add_rewrite_rules'), 10);
