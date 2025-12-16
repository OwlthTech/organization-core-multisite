<?php

/**
 * Hotels Activator
 */

if (!defined('ABSPATH')) {
    exit;
}

class OC_Hotels_Activator
{

    /**
     * Run on plugin activation
     */
    public static function activate()
    {
        self::create_hotels_table();
    }

    /**
     * Create hotels table
     */
    public static function create_hotels_table()
    {
        global $wpdb;
        $table_name = $wpdb->base_prefix . 'hotels';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id BIGINT(20) NOT NULL AUTO_INCREMENT,
            name VARCHAR(255) NOT NULL,
            address TEXT DEFAULT NULL,
            number_of_person INT(11) DEFAULT 0,
            number_of_rooms INT(11) DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            modified_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
}
