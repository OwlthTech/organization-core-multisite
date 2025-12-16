<?php

/**
 * Fired during plugin activation
 *
 * @package    Organization_Core
 * @subpackage Organization_Core/modules/shareables
 */

class OC_Shareables_Activator {

    /**
     * Activate the module.
     */
    public static function activate() {
        self::create_tables();
        self::add_rewrite_rules();
        flush_rewrite_rules();
    }

    /**
     * Create the necessary database tables.
     */
    public static function create_tables() {
        global $wpdb;

        $table_name = $wpdb->base_prefix . 'shareables';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            blog_id bigint(20) NOT NULL,
            uuid varchar(64) DEFAULT NULL,
            title varchar(255) NOT NULL,
            items longtext NOT NULL,
            status varchar(20) NOT NULL DEFAULT 'draft',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY blog_id (blog_id),
            KEY uuid (uuid)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Add rewrite rules for the module.
     */
    public static function add_rewrite_rules() {
        add_rewrite_rule('^share/([^/]+)/?$', 'index.php?shareables_uuid=$matches[1]', 'top');
    }
}

// Ensure rewrite rules are added on init as well, in case activation hook doesn't cover all cases or for persistent rules
add_action('init', array('OC_Shareables_Activator', 'add_rewrite_rules'));
