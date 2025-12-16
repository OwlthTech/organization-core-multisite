<?php

/**
 * Bookings Module Deactivator
 * 
 * Cleanup tasks when module is deactivated
 * 
 * @package    Organization_Core
 * @subpackage Bookings
 */

if (!defined('ABSPATH')) {
    exit;
}

class OC_Bookings_Deactivator
{
    /**
     * Deactivation tasks
     * 
     * Note: We don't delete tables or data on deactivation
     * Only on uninstall
     */
    public static function deactivate()
    {
        // Flush rewrite rules
        flush_rewrite_rules();

        // Clear any cached data
        wp_cache_flush();

        // Remove scheduled crons
        wp_clear_scheduled_hook('org_core_bookings_daily_cleanup');
        
        // ✅ Unschedule rooming list cron events
        self::unschedule_cron_events();
    }

    /**
     * Unschedule cron events
     * Called during plugin deactivation
     */
    public static function unschedule_cron_events()
    {
        // Delegate to cron handler
        if (class_exists('OC_Bookings_Cron')) {
            OC_Bookings_Cron::unschedule_events();
        }
    }

    /**
     * Cleanup temporary data (optional)
     */
    public static function cleanup_temp_data()
    {
        global $wpdb;

        // Delete transients related to bookings
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_booking_%'");
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_booking_%'");
    }
}
