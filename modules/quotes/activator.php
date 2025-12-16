<?php

/**
 * Quotes Module Activator
 * Handles table creation and rewrite rules
 */

if (!defined('WPINC')) {
    die;
}

class OC_Quotes_Activator
{
    /**
     * ✅ Main activation method
     * Called during plugin/module activation
     */
    public static function activate()
    {
        self::create_quotes_table();
        self::add_rewrite_rules();
        flush_rewrite_rules(); // ✅ CRITICAL: Flush on activation
    }

    /**
     * ✅ Create quotes table
     */
    public static function create_quotes_table()
    {
        global $wpdb;
        $table_name = $wpdb->base_prefix . 'quotes';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id BIGINT(20) NOT NULL AUTO_INCREMENT,
            blog_id BIGINT(20) NOT NULL,
            user_id BIGINT(20) NOT NULL DEFAULT 0,
            
            -- Educator Information
            educator_name VARCHAR(255) NOT NULL,
            school_name VARCHAR(255) NOT NULL,
            school_address TEXT NOT NULL,
            position VARCHAR(100) DEFAULT NULL,
            email VARCHAR(100) NOT NULL,
            school_phone VARCHAR(20) DEFAULT NULL,
            cell_phone VARCHAR(20) DEFAULT NULL,
            best_time_to_reach VARCHAR(50) DEFAULT NULL,
            
            -- Destination Information
            destination_id BIGINT(20) DEFAULT NULL,
            destination_name VARCHAR(255) DEFAULT NULL,
            destination_slug VARCHAR(255) DEFAULT NULL,
            
            -- ✅ REMOVED: student_count, travel_dates, trip_duration, budget_range
            
            -- Additional Information
            hear_about_us VARCHAR(100) DEFAULT NULL,
            meal_quote TINYINT(1) DEFAULT 0,
            transportation_quote TINYINT(1) DEFAULT 0,
            special_requests LONGTEXT DEFAULT NULL,
            
            -- System Fields
            quote_data JSON DEFAULT NULL,
            status VARCHAR(50) NOT NULL DEFAULT 'pending',
            notes LONGTEXT DEFAULT NULL,
            admin_notes LONGTEXT DEFAULT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            
            PRIMARY KEY (id),
            KEY blog_id (blog_id),
            KEY user_id (user_id),
            KEY destination_id (destination_id),
            KEY destination_slug (destination_slug),
            KEY status (status),
            KEY email (email),
            KEY created_at (created_at)
        ) $charset_collate;";


        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        $result = dbDelta($sql);
    }

    /**
     * ✅ CRITICAL: Add rewrite rules
     * This method is called BOTH on activation AND on init hook
     */
    public static function add_rewrite_rules()
    {
        // Route: /request-a-quote/
        add_rewrite_rule(
            '^request-a-quote/?$',
            'index.php?quote_page_type=request-a-quote',
            'top'
        );

        //  Route 2: Destination-specific request-a-quote
        // URL: /destination/{destination-slug}/request-a-quote
        add_rewrite_rule(
            '^destination/([^/]+)/request-a-quote/?$',
            'index.php?quote_page_type=request-a-quote&destination_slug=$matches[1]',
            'top'
        );

        // Route: /quotes/ (for user's quote list in my-account)
        add_rewrite_rule(
            '^my-account/quotes/?$',
            'index.php?account_page=quotes',
            'top'
        );
    }
}

// ✅ CRITICAL: Register rewrite rules on EVERY page load!
// This ensures rules persist across all requests
add_action('init', array('OC_Quotes_Activator', 'add_rewrite_rules'), 10);
