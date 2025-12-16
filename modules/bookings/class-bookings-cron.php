<?php

/**
 * Bookings Cron Handler
 * 
 * Handles all scheduled tasks for the Bookings module
 * - Auto-lock rooming lists based on due dates
 * - Future: Email reminders, notifications, etc.
 * 
 * @package    Organization_Core
 * @subpackage Bookings
 */

if (!defined('ABSPATH')) {
    exit;
}

class OC_Bookings_Cron
{
    /**
     * Cron event hook name for checking due dates
     */
    const CRON_HOOK_CHECK_DUE_DATES = 'oc_check_rooming_list_due_dates';

    /**
     * Initialize cron handler
     */
    public static function init()
    {
        // Register the cron event handler
        add_action(self::CRON_HOOK_CHECK_DUE_DATES, array(__CLASS__, 'check_rooming_list_due_dates'));

        // Register custom cron schedules if needed
        add_filter('cron_schedules', array(__CLASS__, 'register_cron_schedules'));
    }

    /**
     * Register custom cron schedules
     * 
     * @param array $schedules Existing schedules
     * @return array Modified schedules
     */
    public static function register_cron_schedules($schedules)
    {
        // Add a custom schedule for every 6 hours (optional, for future use)
        if (!isset($schedules['every_six_hours'])) {
            $schedules['every_six_hours'] = array(
                'interval' => 6 * HOUR_IN_SECONDS,
                'display'  => __('Every 6 Hours', 'organization-core')
            );
        }

        return $schedules;
    }

    /**
     * Schedule cron events
     * Called during plugin activation
     */
    public static function schedule_events()
    {
        // Schedule daily check for rooming list due dates
        if (!wp_next_scheduled(self::CRON_HOOK_CHECK_DUE_DATES)) {
            // Schedule to run daily at midnight (server time)
            wp_schedule_event(strtotime('tomorrow midnight'), 'daily', self::CRON_HOOK_CHECK_DUE_DATES);
            
            self::log_cron_activity('Scheduled daily cron event: ' . self::CRON_HOOK_CHECK_DUE_DATES);
        }
    }

    /**
     * Unschedule cron events
     * Called during plugin deactivation
     */
    public static function unschedule_events()
    {
        $timestamp = wp_next_scheduled(self::CRON_HOOK_CHECK_DUE_DATES);
        if ($timestamp) {
            wp_unschedule_event($timestamp, self::CRON_HOOK_CHECK_DUE_DATES);
            self::log_cron_activity('Unscheduled cron event: ' . self::CRON_HOOK_CHECK_DUE_DATES);
        }

        // Alternative: Clear all instances of the hook
        wp_clear_scheduled_hook(self::CRON_HOOK_CHECK_DUE_DATES);
    }

    /**
     * Main cron job: Check rooming list due dates and auto-lock
     * 
     * This method is called by WP-Cron (triggered by UptimeRobot)
     * It queries all bookings with due dates and locks rooming lists when due
     */
    public static function check_rooming_list_due_dates()
    {
        self::log_cron_activity('Starting due date check...');

        try {
            // Get all bookings with due dates that need to be checked
            $bookings = OC_Bookings_CRUD::get_bookings_with_due_dates();

            if (empty($bookings)) {
                self::log_cron_activity('No bookings with due dates found.');
                return;
            }

            $current_date = current_time('Y-m-d'); // WordPress current date
            $locked_count = 0;
            $skipped_count = 0;
            $error_count = 0;

            self::log_cron_activity(sprintf('Found %d bookings with due dates', count($bookings)));

            foreach ($bookings as $booking) {
                $booking_id = $booking['id'];
                $due_date = $booking['due_date'];

                /*
                // ============================================================
                // AUTO-LOCK LOGIC - DISABLED FOR PRODUCTION DEPLOYMENT
                // ============================================================
                // TO ENABLE: Uncomment this block when ready to go live
                
                // Check if current date is >= due date
                if ($current_date >= $due_date) {
                    // Lock the rooming list
                    $result = self::lock_rooming_list($booking_id);

                    if ($result) {
                        $locked_count++;
                        self::log_cron_activity(sprintf(
                            'Locked rooming list for Booking #%d (Due: %s, Current: %s)',
                            $booking_id,
                            $due_date,
                            $current_date
                        ));
                    } else {
                        $error_count++;
                        self::log_cron_activity(sprintf(
                            'Failed to lock rooming list for Booking #%d',
                            $booking_id
                        ), 'error');
                    }
                } else {
                    // Due date not reached yet
                    $skipped_count++;
                    self::log_cron_activity(sprintf(
                        'Skipped Booking #%d - Due date not reached (Due: %s, Current: %s)',
                        $booking_id,
                        $due_date,
                        $current_date
                    ));
                }
                // ============================================================
                */

                // Log booking found (monitoring only - auto-lock disabled)
                self::log_cron_activity(sprintf(
                    'Booking #%d - Due: %s, Current: %s (Auto-lock disabled)',
                    $booking_id,
                    $due_date,
                    $current_date
                ));
            }

            // Summary log
            self::log_cron_activity(sprintf(
                'Due date check completed. Total: %d, Locked: %d, Skipped: %d, Errors: %d',
                count($bookings),
                $locked_count,
                $skipped_count,
                $error_count
            ));

        } catch (Exception $e) {
            self::log_cron_activity('EXCEPTION: ' . $e->getMessage(), 'error');
        }
    }

    /**
     * Lock all items in a rooming list for a specific booking
     * 
     * This method mimics the "Save & Lock List" button behavior:
     * 1. Fetches current rooming list data
     * 2. Sets all items to locked state
     * 3. Saves the updated data to database
     * 
     * @param int $booking_id Booking ID
     * @return bool True on success, false on failure
     */
    public static function lock_rooming_list($booking_id)
    {
        global $wpdb;

        try {
            // Get all rooming list items for this booking
            $rooming_list = OC_Rooming_List_CRUD::get_rooming_list($booking_id);

            if (empty($rooming_list)) {
                self::log_cron_activity(sprintf(
                    'No rooming list items found for Booking #%d',
                    $booking_id
                ), 'warning');
                return false;
            }

            $locked_items = 0;
            $already_locked = 0;
            $updated_list = array();

            // Prepare updated list with all items locked
            foreach ($rooming_list as $item) {
                // Track already locked items
                if ($item['is_locked'] == 1) {
                    $already_locked++;
                } else {
                    $locked_items++;
                }

                // Add to updated list with locked status
                $updated_list[] = array(
                    'id'            => $item['id'],
                    'room_number'   => $item['room_number'],
                    'occupant_name' => $item['occupant_name'],
                    'occupant_type' => $item['occupant_type'],
                    'is_locked'     => 1  // Force lock
                );
            }

            // If all items are already locked, skip
            if ($already_locked === count($rooming_list)) {
                self::log_cron_activity(sprintf(
                    'All items already locked for Booking #%d (skipped)',
                    $booking_id
                ), 'info');
                return true; // Not an error, just already done
            }

            // Save the updated list (this performs the save + lock operation)
            $result = OC_Rooming_List_CRUD::update_rooming_list($booking_id, $updated_list);

            if ($result === false) {
                self::log_cron_activity(sprintf(
                    'Failed to save and lock rooming list for Booking #%d',
                    $booking_id
                ), 'error');
                return false;
            }

            self::log_cron_activity(sprintf(
                'Locked %d items for Booking #%d (%d already locked, %d total)',
                $locked_items,
                $booking_id,
                $already_locked,
                count($rooming_list)
            ));

            // Optional: Update booking metadata to track auto-lock
            $booking = OC_Bookings_CRUD::get_booking($booking_id, get_current_blog_id());
            if ($booking) {
                $hotel_data = is_string($booking['hotel_data']) 
                    ? json_decode($booking['hotel_data'], true) 
                    : $booking['hotel_data'];

                if (is_array($hotel_data)) {
                    $hotel_data['auto_locked_at'] = current_time('mysql');
                    $hotel_data['auto_locked_by'] = 'cron';
                    $hotel_data['auto_locked_items'] = $locked_items;

                    OC_Bookings_CRUD::update_booking(
                        $booking_id,
                        get_current_blog_id(),
                        array('hotel_data' => json_encode($hotel_data))
                    );
                }
            }

            return $locked_items > 0;

        } catch (Exception $e) {
            self::log_cron_activity(sprintf(
                'ERROR locking rooming list for Booking #%d: %s',
                $booking_id,
                $e->getMessage()
            ), 'error');
            return false;
        }
    }

    /**
     * Log cron activity for debugging and audit trail
     * 
     * @param string $message Log message
     * @param string $level Log level (info, warning, error)
     */
    public static function log_cron_activity($message, $level = 'info')
    {
        // Option 1: Use WordPress error_log
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log(sprintf('[OC Bookings Cron] [%s] %s', strtoupper($level), $message));
        }

        // Option 2: Store in database for admin viewing (optional)
        // You can create a custom log table or use WordPress options
        $log_option_key = 'oc_bookings_cron_log';
        $logs = get_option($log_option_key, array());

        // Keep only last 100 log entries
        if (count($logs) >= 100) {
            $logs = array_slice($logs, -99);
        }

        $logs[] = array(
            'timestamp' => current_time('mysql'),
            'level'     => $level,
            'message'   => $message
        );

        update_option($log_option_key, $logs, false);
    }

    /**
     * Get cron logs for admin viewing
     * 
     * @param int $limit Number of logs to retrieve
     * @return array Log entries
     */
    public static function get_cron_logs($limit = 50)
    {
        $logs = get_option('oc_bookings_cron_log', array());
        return array_slice(array_reverse($logs), 0, $limit);
    }

    /**
     * Clear cron logs
     */
    public static function clear_cron_logs()
    {
        delete_option('oc_bookings_cron_log');
        self::log_cron_activity('Cron logs cleared');
    }

    /**
     * Get next scheduled run time
     * 
     * @return string|false Formatted date/time or false if not scheduled
     */
    public static function get_next_run_time()
    {
        $timestamp = wp_next_scheduled(self::CRON_HOOK_CHECK_DUE_DATES);
        
        if ($timestamp) {
            return date('Y-m-d H:i:s', $timestamp);
        }

        return false;
    }

    /**
     * Manually trigger the cron job (for testing)
     * Only accessible by administrators
     */
    public static function manual_trigger()
    {
        if (!current_user_can('manage_options')) {
            return new WP_Error('forbidden', 'You do not have permission to trigger cron jobs.');
        }

        self::log_cron_activity('Manual cron trigger initiated by user #' . get_current_user_id());
        self::check_rooming_list_due_dates();

        return array(
            'success' => true,
            'message' => 'Cron job executed successfully. Check logs for details.'
        );
    }
}

// Initialize the cron handler
OC_Bookings_Cron::init();
