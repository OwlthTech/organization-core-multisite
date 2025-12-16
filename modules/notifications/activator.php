<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

class Notifications_Activator {

    public static function activate() {
        self::create_notifications_table();
    }

    public static function create_notifications_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'notifications';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id BIGINT(20) NOT NULL AUTO_INCREMENT,
            blog_id BIGINT(20) NOT NULL,
            notification_data JSON NOT NULL,
            assigned_to BIGINT(20) NOT NULL,
            collaborators_ids JSON NOT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'pending',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            modified_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY blog_id (blog_id)
        ) $charset_collate;";

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta( $sql );
    }
}
