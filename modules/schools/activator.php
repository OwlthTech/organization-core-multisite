<?php

/**
 * Schools Module - Activation Handler
 */

if (!defined('ABSPATH')) {
    exit;
}

class OC_Schools_Activator
{
    /**
     * Activate module
     */
    public static function activate()
    {
        self::create_schools_table();
        self::add_rewrite_rules();
        flush_rewrite_rules(false);  // false = don't hard-flush .htaccess
    }

    /**
     * Create global schools table
     */
    public static function create_schools_table()
    {
        global $wpdb;

        $table_name = $wpdb->base_prefix . 'schools';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id BIGINT(20) NOT NULL AUTO_INCREMENT,
            blog_id BIGINT(20) NOT NULL,
            user_id BIGINT(20) NOT NULL,
            school_name VARCHAR(100) NOT NULL,
            school_address VARCHAR(150) NOT NULL,
            school_address_2 VARCHAR(150) DEFAULT NULL,
            school_city VARCHAR(80) NOT NULL,
            school_state VARCHAR(50) NOT NULL,
            school_zip VARCHAR(20) NOT NULL,
            school_country VARCHAR(50) NOT NULL,
            school_phone VARCHAR(20) NOT NULL,
            school_website VARCHAR(200) DEFAULT NULL,
            school_enrollment INT(11) DEFAULT 0,
            school_notes LONGTEXT DEFAULT NULL,
            director_prefix VARCHAR(10) DEFAULT NULL,
            director_first_name VARCHAR(80) NOT NULL,
            director_last_name VARCHAR(80) NOT NULL,
            director_email VARCHAR(100) NOT NULL,
            director_cell_phone VARCHAR(20) DEFAULT NULL,
            principal_prefix VARCHAR(10) DEFAULT NULL,
            principal_first_name VARCHAR(80) DEFAULT NULL,
            principal_last_name VARCHAR(80) DEFAULT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'active',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            modified_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY blog_id (blog_id),
            KEY user_id (user_id),
            KEY school_name (school_name),
            KEY director_email (director_email),
            KEY status (status),
            KEY created_at (created_at),
            KEY user_blog (user_id, blog_id),
            UNIQUE KEY user_school (user_id, school_name(50), blog_id)
        ) $charset_collate;";

        if (!function_exists('dbDelta')) {
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        }
        dbDelta($sql);
    }

    /**

     * Register rewrite rules
     */
    public static function add_rewrite_rules()
    {
        add_rewrite_rule(
            '^my-account/schools/add/?$',
            'index.php?account_page=schools&school_action=add',
            'top'
        );
        add_rewrite_rule(
            '^my-account/schools/?$',
            'index.php?account_page=schools',
            'top'
        );
    }
}
add_action('init', array('OC_Schools_Activator', 'add_rewrite_rules'), 10);
