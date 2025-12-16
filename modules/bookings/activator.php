<?php

/**
 * Bookings Activator
 * 
 * CRITICAL: Registers rewrite rules both on activation AND on every page load
 */

if (!defined('ABSPATH')) {
    exit;
}

class OC_Bookings_Activator
{

    /**
     * Run on plugin activation
     */
    public static function activate()
    {
        self::create_bookings_table();
        self::add_rewrite_rules();
        self::schedule_cron_events();
        flush_rewrite_rules(false);
    }

    /**
     * Create bookings table
     */
    public static function create_bookings_table()
    {
        global $wpdb;
        $table_name = $wpdb->base_prefix . 'bookings';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id BIGINT(20) NOT NULL AUTO_INCREMENT,
            blog_id BIGINT(20) NOT NULL,
            user_id bigint(20) NOT NULL DEFAULT 0,
            package_id bigint(20) DEFAULT NULL,
            booking_data JSON NOT NULL,
            school_id varchar(255) DEFAULT NULL,
            location_id bigint(20) DEFAULT NULL,
            date_selection date DEFAULT NULL,
            parks_selection text DEFAULT NULL,
            other_park_name varchar(255) DEFAULT NULL,
            total_students int(11) DEFAULT 0,
            total_chaperones int(11) DEFAULT 0,
            meal_vouchers tinyint(1) DEFAULT 0,
            meals_per_day int(2) DEFAULT 0,
            park_meal_options text DEFAULT NULL,
            transportation varchar(50) DEFAULT 'own',
            lodging_dates varchar(255) DEFAULT NULL,
            special_notes text DEFAULT NULL,
            ensembles JSON DEFAULT NULL,
            hotel_data LONGTEXT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'pending',
            total_amount decimal(10,2) DEFAULT 0.00,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            modified_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY blog_id (blog_id),
            KEY user_id (user_id),
            KEY package_id (package_id),
            KEY school_id (school_id),
            KEY location_id (location_id),
            KEY status (status),
            KEY date_selection (date_selection),
            KEY created_at (created_at)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * ✅ CRITICAL: Add rewrite rules
     * This method is called BOTH on activation AND on init hook
     */
    public static function add_rewrite_rules()
    {
        add_rewrite_rule(
            '^book-now/([0-9]+)/?$',
            'index.php?booking_page_type=book-now&booking_package_id=$matches[1]',
            'top'
        );

        add_rewrite_rule(
            '^my-account/bookings/?$',
            'index.php?account_page=bookings',
            'top'
        );
    }

    /**
     * Schedule cron events
     * Called during plugin activation
     * 
     * ✅ MULTISITE COMPATIBLE: Schedules cron for current site only
     * Cron events in WordPress Multisite are site-specific, not network-wide
     * 
     * ✅ SITE-SPECIFIC CONTROL: Can be enabled/disabled per site using wp-config.php
     * Add to wp-config.php: define('OC_ENABLE_ROOMING_LIST_CRON', array(1, 3, 5));
     * This will enable cron only on sites with blog_id 1, 3, and 5
     */
    public static function schedule_cron_events()
    {
        // ✅ SITE-SPECIFIC CONTROL: Check if cron is enabled for this site
        if (defined('OC_ENABLE_ROOMING_LIST_CRON')) {
            $enabled_sites = OC_ENABLE_ROOMING_LIST_CRON;
            $current_blog_id = get_current_blog_id();
            
            // If constant is defined but this site is not in the list, skip scheduling
            if (is_array($enabled_sites) && !in_array($current_blog_id, $enabled_sites)) {
                error_log(sprintf(
                    'OC_Bookings_Activator: Cron NOT enabled for blog_id %d (enabled sites: %s)',
                    $current_blog_id,
                    implode(', ', $enabled_sites)
                ));
                return; // Skip scheduling for this site
            }
        }
        // If constant is not defined, cron will be enabled on all sites (default behavior)
        
        // ✅ CRITICAL: Load the cron class BEFORE trying to use it
        $cron_file = plugin_dir_path(__FILE__) . 'class-bookings-cron.php';
        
        if (!file_exists($cron_file)) {
            error_log('OC_Bookings_Activator: Cron handler file not found at: ' . $cron_file);
            return;
        }
        
        // Load the cron class
        if (!class_exists('OC_Bookings_Cron')) {
            require_once $cron_file;
        }
        
        // Now schedule the events
        if (class_exists('OC_Bookings_Cron')) {
            OC_Bookings_Cron::schedule_events();
            error_log('OC_Bookings_Activator: Cron events scheduled successfully for blog_id: ' . get_current_blog_id());
        } else {
            error_log('OC_Bookings_Activator: OC_Bookings_Cron class not found after loading file');
        }
    }

    /**
     * ✅ NEW: Network activation support for WordPress Multisite
     * This is called when plugin is network-activated
     * 
     * @param bool $network_wide Whether the plugin is being network-activated
     */
    public static function activate_multisite($network_wide = false)
    {
        if (is_multisite() && $network_wide) {
            // Get all sites in the network
            $sites = get_sites(array('number' => 10000));
            
            foreach ($sites as $site) {
                // Switch to each site and run activation
                switch_to_blog($site->blog_id);
                self::activate();
                restore_current_blog();
            }
        } else {
            // Single site or per-site activation
            self::activate();
        }
    }
}

// ✅ CRITICAL: Register rewrite rules on EVERY page load!
// This is what was missing from your new structure!
add_action('init', array('OC_Bookings_Activator', 'add_rewrite_rules'), 10);
