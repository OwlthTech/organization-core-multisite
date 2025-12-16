<?php
/**
 * Action Scheduler Integration
 * Professional implementation for all scheduled notifications
 *
 * @package    Organization_Core
 * @subpackage Notifications
 * @version    2.0.0
 */

if (!defined('WPINC')) {
    die;
}

class OC_Action_Scheduler {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Initialize Action Scheduler
     */
    public function init() {
        // Register all recurring scheduled actions
        add_action('init', array($this, 'register_scheduled_actions'), 20);
        
        // Register action handlers
        $this->register_action_handlers();
        
        error_log('[ACTION SCHEDULER] Initialized');
    }
    
    /**
     * Register all recurring scheduled actions
     */
    public function register_scheduled_actions() {
        if (!function_exists('as_next_scheduled_action')) {
            error_log('[ACTION SCHEDULER] Action Scheduler not available');
            return;
        }
        
        // Daily checks at 10:00 AM
        $this->schedule_daily_checks();
        
        // Weekly checks (Monday 9:00 AM)
        $this->schedule_weekly_checks();
        
        // Monthly checks (1st day 9:00 AM)
        $this->schedule_monthly_checks();
    }
    
    /**
     * Schedule daily checks
     */
    private function schedule_daily_checks() {
        $daily_actions = array(
            'oc_check_booking_reminders' => 'Check booking reminders (7 days before)',
            'oc_check_rooming_list_due_dates' => 'Check rooming list due dates (3 days before)',
            'oc_check_rooming_list_incomplete' => 'Check incomplete rooming lists (1 day before)',
            'oc_check_quote_followups' => 'Check quote follow-ups (48 hours)',
            'oc_check_quote_reminders_admin' => 'Check quote reminders for admin (24 hours)',
            'oc_send_shareable_digest' => 'Send shareable access digest',
            'oc_check_draft_bookings' => 'Check draft bookings expiring (7 days old)',
        );
        
        foreach ($daily_actions as $hook => $description) {
            if (!as_next_scheduled_action($hook)) {
                as_schedule_recurring_action(
                    strtotime('tomorrow 10:00am'),
                    DAY_IN_SECONDS,
                    $hook,
                    array(),
                    'oc-notifications-daily'
                );
                error_log('[ACTION SCHEDULER] Registered daily: ' . $hook);
            }
        }
    }
    
    /**
     * Schedule weekly checks
     */
    private function schedule_weekly_checks() {
        $weekly_actions = array(
            'oc_send_hotel_availability_report' => 'Send hotel availability report',
        );
        
        foreach ($weekly_actions as $hook => $description) {
            if (!as_next_scheduled_action($hook)) {
                as_schedule_recurring_action(
                    strtotime('next Monday 9:00am'),
                    WEEK_IN_SECONDS,
                    $hook,
                    array(),
                    'oc-notifications-weekly'
                );
                error_log('[ACTION SCHEDULER] Registered weekly: ' . $hook);
            }
        }
    }
    
    /**
     * Schedule monthly checks
     */
    private function schedule_monthly_checks() {
        $monthly_actions = array(
            'oc_check_school_expiring' => 'Check schools expiring (1 year old)',
            'oc_check_account_inactive' => 'Check inactive accounts (90 days)',
        );
        
        foreach ($monthly_actions as $hook => $description) {
            if (!as_next_scheduled_action($hook)) {
                as_schedule_recurring_action(
                    strtotime('first day of next month 9:00am'),
                    MONTH_IN_SECONDS,
                    $hook,
                    array(),
                    'oc-notifications-monthly'
                );
                error_log('[ACTION SCHEDULER] Registered monthly: ' . $hook);
            }
        }
    }
    
    /**
     * Register action handlers
     */
    private function register_action_handlers() {
        // Daily checks
        add_action('oc_check_booking_reminders', array($this, 'check_booking_reminders'));
        add_action('oc_check_rooming_list_due_dates', array($this, 'check_rooming_list_due_dates'));
        add_action('oc_check_rooming_list_incomplete', array($this, 'check_rooming_list_incomplete'));
        add_action('oc_check_quote_followups', array($this, 'check_quote_followups'));
        add_action('oc_check_quote_reminders_admin', array($this, 'check_quote_reminders_admin'));
        add_action('oc_send_shareable_digest', array($this, 'send_shareable_digest'));
        add_action('oc_check_draft_bookings', array($this, 'check_draft_bookings'));
        
        // Weekly checks
        add_action('oc_send_hotel_availability_report', array($this, 'send_hotel_availability_report'));
        
        // Monthly checks
        add_action('oc_check_school_expiring', array($this, 'check_school_expiring'));
        add_action('oc_check_account_inactive', array($this, 'check_account_inactive'));
    }
    
    /**
     * Schedule a one-time notification
     */
    public function schedule_single_notification($hook, $args = array(), $delay_seconds = 0) {
        if (!function_exists('as_schedule_single_action')) {
            return false;
        }
        
        as_schedule_single_action(
            time() + $delay_seconds,
            $hook,
            $args,
            'oc-notifications-single'
        );
        
        return true;
    }
    
    // ========================================
    // Daily Check Handlers
    // ========================================
    
    /**
     * Check booking reminders (7 days before festival date)
     */
    public function check_booking_reminders() {
        error_log('[ACTION SCHEDULER] Running booking reminders check...');
        
        global $wpdb;
        $table = $wpdb->prefix . 'bookings';
        
        if ($wpdb->get_var("SHOW TABLES LIKE '$table'") != $table) {
            return;
        }
        
        $seven_days_from_now = date('Y-m-d', strtotime('+7 days'));
        
        $bookings = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table 
            WHERE festival_date = %s 
            AND status = 'confirmed'
            AND blog_id = %d",
            $seven_days_from_now,
            get_current_blog_id()
        ));
        
        foreach ($bookings as $booking) {
            do_action('oc_send_booking_reminder', $booking->id, $booking);
        }
        
        error_log('[ACTION SCHEDULER] Processed ' . count($bookings) . ' booking reminders');
    }
    
    /**
     * Check rooming list due dates (3 days before)
     */
    public function check_rooming_list_due_dates() {
        error_log('[ACTION SCHEDULER] Running rooming list due date check...');
        
        global $wpdb;
        $table = $wpdb->prefix . 'rooming_lists';
        
        if ($wpdb->get_var("SHOW TABLES LIKE '$table'") != $table) {
            return;
        }
        
        $three_days_from_now = date('Y-m-d', strtotime('+3 days'));
        
        $lists = $wpdb->get_results($wpdb->prepare(
            "SELECT DISTINCT booking_id, due_date 
            FROM $table 
            WHERE due_date = %s 
            AND status != 'locked'
            AND blog_id = %d",
            $three_days_from_now,
            get_current_blog_id()
        ));
        
        foreach ($lists as $list) {
            do_action('oc_send_rooming_list_due_reminder', $list->booking_id, $list->due_date);
        }
        
        error_log('[ACTION SCHEDULER] Processed ' . count($lists) . ' rooming list reminders');
    }
    
    /**
     * Check incomplete rooming lists (1 day before due date)
     */
    public function check_rooming_list_incomplete() {
        error_log('[ACTION SCHEDULER] Running incomplete rooming list check...');
        
        global $wpdb;
        $table = $wpdb->prefix . 'rooming_lists';
        
        if ($wpdb->get_var("SHOW TABLES LIKE '$table'") != $table) {
            return;
        }
        
        $tomorrow = date('Y-m-d', strtotime('+1 day'));
        
        $lists = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table 
            WHERE due_date = %s 
            AND status != 'locked'
            AND status != 'complete'
            AND blog_id = %d",
            $tomorrow,
            get_current_blog_id()
        ));
        
        foreach ($lists as $list) {
            do_action('oc_send_rooming_list_incomplete_warning', $list->booking_id, $list);
        }
        
        error_log('[ACTION SCHEDULER] Processed ' . count($lists) . ' incomplete warnings');
    }
    
    /**
     * Check quote follow-ups (48 hours after submission)
     */
    public function check_quote_followups() {
        error_log('[ACTION SCHEDULER] Running quote follow-ups check...');
        
        global $wpdb;
        $table = $wpdb->prefix . 'quotes';
        
        if ($wpdb->get_var("SHOW TABLES LIKE '$table'") != $table) {
            return;
        }
        
        $two_days_ago = date('Y-m-d H:i:s', strtotime('-48 hours'));
        
        $quotes = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table 
            WHERE created_at <= %s 
            AND status = 'pending'
            AND blog_id = %d",
            $two_days_ago,
            get_current_blog_id()
        ));
        
        foreach ($quotes as $quote) {
            do_action('oc_send_quote_followup', $quote->id, $quote);
        }
        
        error_log('[ACTION SCHEDULER] Processed ' . count($quotes) . ' quote follow-ups');
    }
    
    /**
     * Check quote reminders for admin (24 hours)
     */
    public function check_quote_reminders_admin() {
        error_log('[ACTION SCHEDULER] Running admin quote reminders check...');
        
        global $wpdb;
        $table = $wpdb->prefix . 'quotes';
        
        if ($wpdb->get_var("SHOW TABLES LIKE '$table'") != $table) {
            return;
        }
        
        $one_day_ago = date('Y-m-d H:i:s', strtotime('-24 hours'));
        
        $quotes = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table 
            WHERE created_at <= %s 
            AND status = 'pending'
            AND blog_id = %d",
            $one_day_ago,
            get_current_blog_id()
        ));
        
        if (count($quotes) > 0) {
            do_action('oc_send_quote_reminder_admin', $quotes);
        }
        
        error_log('[ACTION SCHEDULER] Processed ' . count($quotes) . ' admin quote reminders');
    }
    
    /**
     * Send shareable access digest
     */
    public function send_shareable_digest() {
        error_log('[ACTION SCHEDULER] Sending shareable digest...');
        
        // Get yesterday's shareable views
        global $wpdb;
        $table = $wpdb->prefix . 'shareable_views';
        
        if ($wpdb->get_var("SHOW TABLES LIKE '$table'") != $table) {
            return;
        }
        
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        
        $views = $wpdb->get_results($wpdb->prepare(
            "SELECT shareable_id, COUNT(*) as view_count 
            FROM $table 
            WHERE DATE(viewed_at) = %s 
            AND blog_id = %d
            GROUP BY shareable_id",
            $yesterday,
            get_current_blog_id()
        ));
        
        if (count($views) > 0) {
            do_action('oc_send_shareable_access_digest', $views, $yesterday);
        }
        
        error_log('[ACTION SCHEDULER] Sent shareable digest with ' . count($views) . ' items');
    }
    
    /**
     * Check draft bookings expiring (7 days old)
     */
    public function check_draft_bookings() {
        error_log('[ACTION SCHEDULER] Running draft bookings check...');
        
        global $wpdb;
        $table = $wpdb->prefix . 'bookings';
        
        if ($wpdb->get_var("SHOW TABLES LIKE '$table'") != $table) {
            return;
        }
        
        $seven_days_ago = date('Y-m-d H:i:s', strtotime('-7 days'));
        
        $drafts = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table 
            WHERE created_at <= %s 
            AND status = 'draft'
            AND blog_id = %d",
            $seven_days_ago,
            get_current_blog_id()
        ));
        
        foreach ($drafts as $draft) {
            do_action('oc_send_draft_expiring_notice', $draft->id, $draft);
        }
        
        error_log('[ACTION SCHEDULER] Processed ' . count($drafts) . ' draft expiring notices');
    }
    
    // ========================================
    // Weekly Check Handlers
    // ========================================
    
    /**
     * Send hotel availability report
     */
    public function send_hotel_availability_report() {
        error_log('[ACTION SCHEDULER] Sending hotel availability report...');
        
        global $wpdb;
        $table = $wpdb->prefix . 'hotels';
        
        if ($wpdb->get_var("SHOW TABLES LIKE '$table'") != $table) {
            return;
        }
        
        $hotels = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table WHERE blog_id = %d",
            get_current_blog_id()
        ));
        
        do_action('oc_send_hotel_availability_report', $hotels);
        
        error_log('[ACTION SCHEDULER] Sent hotel availability report');
    }
    
    // ========================================
    // Monthly Check Handlers
    // ========================================
    
    /**
     * Check schools expiring (1 year old)
     */
    public function check_school_expiring() {
        error_log('[ACTION SCHEDULER] Running school expiring check...');
        
        global $wpdb;
        $table = $wpdb->prefix . 'schools';
        
        if ($wpdb->get_var("SHOW TABLES LIKE '$table'") != $table) {
            return;
        }
        
        $one_year_ago = date('Y-m-d H:i:s', strtotime('-1 year'));
        
        $schools = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table 
            WHERE modified_at <= %s 
            AND blog_id = %d",
            $one_year_ago,
            get_current_blog_id()
        ));
        
        foreach ($schools as $school) {
            do_action('oc_send_school_expiring_notice', $school->id, $school);
        }
        
        error_log('[ACTION SCHEDULER] Processed ' . count($schools) . ' school expiring notices');
    }
    
    /**
     * Check inactive accounts (90 days)
     */
    public function check_account_inactive() {
        error_log('[ACTION SCHEDULER] Running inactive account check...');
        
        global $wpdb;
        
        $ninety_days_ago = date('Y-m-d H:i:s', strtotime('-90 days'));
        
        $users = $wpdb->get_results($wpdb->prepare(
            "SELECT ID FROM {$wpdb->users} u
            LEFT JOIN {$wpdb->usermeta} um ON u.ID = um.user_id AND um.meta_key = 'last_login'
            WHERE um.meta_value <= %s OR um.meta_value IS NULL",
            $ninety_days_ago
        ));
        
        foreach ($users as $user) {
            do_action('oc_send_account_inactive_notice', $user->ID);
        }
        
        error_log('[ACTION SCHEDULER] Processed ' . count($users) . ' inactive account notices');
    }
    
    /**
     * Clear all scheduled actions (for deactivation)
     */
    public function clear_all_scheduled_actions() {
        if (!function_exists('as_unschedule_all_actions')) {
            return;
        }
        
        $groups = array('oc-notifications-daily', 'oc-notifications-weekly', 'oc-notifications-monthly');
        
        foreach ($groups as $group) {
            as_unschedule_all_actions('', array(), $group);
        }
        
        error_log('[ACTION SCHEDULER] Cleared all scheduled actions');
    }
}
