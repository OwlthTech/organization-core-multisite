<?php
/**
 * Central Notification Handler
 * All modules use this to send notifications
 *
 * @package    Organization_Core
 * @subpackage Notifications
 * @version    1.0.0
 */

if (!defined('WPINC')) {
    die;
}

class OC_Notification_Handler {
    
    private static $instance = null;
    private $email_sender;

    // Configuration
    private $max_email_attempts = 3; // number of retries before permanent failure
    private $force_async = true; // send emails through Action Scheduler when true
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        require_once plugin_dir_path(__FILE__) . 'class-email-sender.php';
        $this->email_sender = new OC_Email_Sender();

        // Read runtime config
        $opt = get_option('mus_notifications_async');
        if (is_null($opt)) {
            // default to true
            $this->force_async = true;
        } else {
            $this->force_async = (bool) $opt;
        }
    }
    
    /**
     * Initialize notification hooks
     */
    public function init() {
        // Generic notification trigger hook (recommended for new code)
        add_action('oc_send_notification', array($this, 'handle_do_action_trigger'), 10, 3);
        
        // Authentication hooks
        add_action('oc_user_registered', array($this, 'send_account_created'), 10, 2);
        
        // Bookings hooks (migrate existing)
        add_action('org_core_booking_created', array($this, 'send_booking_confirmation'), 10, 4);
        
        // Hotels hooks
        add_action('oc_hotel_assigned_to_booking', array($this, 'send_hotel_assigned'), 10, 3);
        
        // Rooming List hooks
        add_action('oc_rooming_list_created', array($this, 'send_rooming_list_created'), 10, 2);
        
        // Action Scheduler hooks (for scheduled notifications)
        add_action('oc_send_scheduled_notification', array($this, 'send_scheduled_notification'), 10, 2);

        // Register email job processor (canonical email job hook)
        add_action('oc_send_email_job', array($this, 'process_email_job'), 10, 1);

        error_log('[NOTIFICATIONS] Handler initialized');
    }
    
    /**
     * Handle do_action trigger for notifications
     * Allows modules to use: do_action('oc_send_notification', 'notification_type', $data, $delay);
     */
    public function handle_do_action_trigger($notification_type, $data = array(), $delay = 0) {
        $this->send_notification($notification_type, $data, $delay);
    }

    
    /**
     * Static method to trigger notifications from anywhere
     * This is the recommended way to send notifications from other modules
     * 
     * Usage: OC_Notification_Handler::trigger('notification_type', $data, $delay);
     * Or via do_action: do_action('oc_send_notification', 'notification_type', $data, $delay);
     */
    public static function trigger($notification_type, $data = array(), $delay = 0) {
        $instance = self::get_instance();
        $instance->send_notification($notification_type, $data, $delay);
    }
    
    /**
     * Send notification (instant or scheduled)
     */
    public function send_notification($notification_type, $data, $delay = 0) {
        // If force_async is enabled, route all sends through Action Scheduler for robustness
        if ($delay > 0 || $this->force_async) {
            $this->schedule_notification($notification_type, $data, $delay);
        } else {
            // Send immediately
            $this->send_email($notification_type, $data);
        }
    }

    
    /**
     * Schedule notification using Action Scheduler
     */
    private function schedule_notification($notification_type, $data, $delay) {
        if (!function_exists('as_schedule_single_action')) {
            error_log('[NOTIFICATIONS] Action Scheduler not available');
            return;
        }

        // Canonical email job payload
        $payload = array(
            'notification_type' => $notification_type,
            'data' => $data,
            'attempt' => 0,
        );

        as_schedule_single_action(
            time() + $delay,
            'oc_send_email_job',
            array($payload),
            'oc-notifications-email'
        );

        error_log('[NOTIFICATIONS] Scheduled email job: ' . $notification_type . ' for ' . $delay . ' seconds from now');
    }
    
    /**
     * Send email notification
     */
    private function send_email($notification_type, $data) {
        error_log('[NOTIFICATIONS] Sending instant email: ' . $notification_type);
        
        // Attempt to send via email sender
        $success = (bool) $this->email_sender->send($notification_type, $data);
        $error_message = '';
        if (!$success && method_exists($this->email_sender, 'get_last_error')) {
            $error_message = $this->email_sender->get_last_error();
        }

        // Log result
        if ($success) {
            error_log('[NOTIFICATIONS] ✓ Email sent successfully: ' . $notification_type);
            OC_Notification_Logger::log($notification_type, $data, true, $error_message);
        } else {
            error_log('[NOTIFICATIONS] ✗ Email failed: ' . $notification_type . ' Error: ' . $error_message);
            OC_Notification_Logger::log($notification_type, $data, false, $error_message);
        }
        
        return $success;
    }
    
    /**
     * Handler for scheduled notifications
     */
    public function send_scheduled_notification($notification_type, $data) {
        // Support legacy positional params or the new payload object
        if (is_array($notification_type) && isset($notification_type['notification_type'])) {
            $payload = $notification_type;
            $this->process_email_job($payload);
            return;
        }

        // Legacy: two positional arguments
        $payload = array(
            'notification_type' => $notification_type,
            'data' => $data,
            'attempt' => 0,
        );

        $this->process_email_job($payload);
    }

    /**
     * Process email job invoked by Action Scheduler
     * Payload: array('notification_type'=>..., 'data'=>..., 'attempt'=>int)
     */
    public function process_email_job($payload) {
        if (!is_array($payload) || empty($payload['notification_type'])) {
            error_log('[NOTIFICATIONS] Invalid email job payload');
            return;
        }

        $notification_type = $payload['notification_type'];
        $data = $payload['data'] ?? array();
        $attempt = isset($payload['attempt']) ? (int) $payload['attempt'] : 0;

        error_log('[NOTIFICATIONS] Processing email job: ' . $notification_type . ' (attempt ' . $attempt . ')');

        // Augment data for logging and templates
        $data['__attempt'] = $attempt;

        $success = $this->send_email($notification_type, $data);

        if ($success) {
            error_log('[NOTIFICATIONS] Email job succeeded: ' . $notification_type . ' (attempt ' . $attempt . ')');
            return;
        }

        // On failure, schedule retry if attempts remain
        $attempt++;
        if ($attempt <= $this->max_email_attempts) {
            $backoff_seconds = (int) (pow(2, $attempt) * 60); // exponential backoff (minutes)
            $payload['attempt'] = $attempt;

            // If there is an active SMTP auth failure transient, avoid noisy retries and queue the job for admin review
            $smtp_auth_failure = get_transient('mus_smtp_auth_failure');
            if (!empty($smtp_auth_failure)) {
                error_log('[NOTIFICATIONS] SMTP auth failure detected; pausing retries for this job. Job: ' . $notification_type . ' attempt ' . $attempt);

                // Store job payload in the unsent queue for admin review
                $unsent = get_option('mus_unsent_emails', array());
                $unsent[] = array(
                    'time' => current_time('timestamp'),
                    'notification_type' => $notification_type,
                    'data' => $data,
                    'attempt' => $attempt,
                    'reason' => 'smtp_auth_failure',
                    'smtp_test' => $smtp_auth_failure,
                );
                update_option('mus_unsent_emails', $unsent);

                OC_Notification_Logger::log($notification_type, $data, false, 'Paused due to SMTP auth failure');
                return;
            }

            if (function_exists('as_schedule_single_action')) {
                as_schedule_single_action(
                    time() + $backoff_seconds,
                    'oc_send_email_job',
                    array($payload),
                    'oc-notifications-email-retry'
                );
                error_log('[NOTIFICATIONS] Rescheduled email job: ' . $notification_type . ' attempt ' . $attempt . ' in ' . $backoff_seconds . 's');
            } else {
                error_log('[NOTIFICATIONS] Cannot reschedule email job because Action Scheduler is not available');
            }
        } else {
            error_log('[NOTIFICATIONS] Email job permanently failed after ' . ($attempt - 1) . ' attempts: ' . $notification_type);
            OC_Notification_Logger::log($notification_type, $data, false, 'Permanently failed after retries');
        }
    }
    
    // ========================================
    // Individual Notification Methods
    // ========================================
    
    /**
     * Send account created notification
     * Hook: oc_user_registered
     */
    public function send_account_created($user_id, $user_data) {
        $user = get_userdata($user_id);
        if (!$user) {
            return;
        }
        
        $data = array(
            'user_name' => $user->display_name,
            'user_email' => $user->user_email,
            'login_url' => wp_login_url(),
            'site_name' => get_bloginfo('name')
        );
        
        $this->send_notification('account_created', $data);
    }
    
    /**
     * Send booking confirmation
     * Hook: org_core_booking_created
     */
    public function send_booking_confirmation($booking_id, $booking_data, $user, $blog_id) {
        if (!$booking_id || !$user) {
            error_log('[NOTIFICATIONS] Invalid parameters for booking confirmation');
            return;
        }
        
        // Prepare booking data
        $email_data = $this->prepare_booking_data($booking_id, $booking_data, $user);
        
        // Send to user
        $user_data = array_merge($email_data, array('recipient_type' => 'user'));
        $this->send_notification('booking_confirmation_user', $user_data);
        
        // Send to admin
        $admin_data = array_merge($email_data, array('recipient_type' => 'admin'));
        $this->send_notification('booking_confirmation_admin', $admin_data);
    }
    
    /**
     * Send hotel assigned notification
     * Hook: oc_hotel_assigned_to_booking
     */
    public function send_hotel_assigned($booking_id, $hotel_id, $hotel_data) {
        // Get booking details
        if (!class_exists('OC_Bookings_CRUD')) {
            return;
        }
        
        $booking = OC_Bookings_CRUD::get_booking($booking_id, get_current_blog_id());
        if (!$booking) {
            return;
        }
        
        $user = get_userdata($booking->user_id);
        if (!$user) {
            return;
        }
        
        $data = array(
            'booking_ref' => $booking->booking_reference ?? $booking_id,
            'hotel_name' => $hotel_data['hotel_name'] ?? '',
            'hotel_address' => $hotel_data['hotel_address'] ?? '',
            'check_in' => $hotel_data['check_in'] ?? '',
            'check_out' => $hotel_data['check_out'] ?? '',
            'user_name' => $user->display_name,
            'user_email' => $user->user_email
        );
        
        // Send to user
        $user_data = array_merge($data, array('recipient_type' => 'user'));
        $this->send_notification('hotel_assigned_user', $user_data);
        
        // Send to admin
        $admin_data = array_merge($data, array('recipient_type' => 'admin'));
        $this->send_notification('hotel_assigned_admin', $admin_data);
    }
    
    /**
     * Send rooming list created notification
     * Hook: oc_rooming_list_created
     */
    public function send_rooming_list_created($booking_id, $rooming_data) {
        // Get booking details
        if (!class_exists('OC_Bookings_CRUD')) {
            return;
        }
        
        $booking = OC_Bookings_CRUD::get_booking($booking_id, get_current_blog_id());
        if (!$booking) {
            return;
        }
        
        $user = get_userdata($booking->user_id);
        if (!$user) {
            return;
        }
        
        $data = array(
            'booking_ref' => $booking->booking_reference ?? $booking_id,
            'total_rooms' => $rooming_data['total_rooms'] ?? 0,
            'total_occupants' => $rooming_data['total_occupants'] ?? 0,
            'due_date' => $rooming_data['due_date'] ?? '',
            'edit_url' => $rooming_data['edit_url'] ?? '',
            'user_name' => $user->display_name,
            'user_email' => $user->user_email
        );
        
        $this->send_notification('rooming_list_created', $data);
    }
    
    /**
     * Prepare booking data for email templates
     * (Migrated from bookings module)
     */
    private function prepare_booking_data($booking_id, $booking_data, $user) {
        $data = array();
        
        $data['booking_id'] = $booking_id;
        $data['user_email'] = $user->user_email;
        $data['director_name'] = $user->display_name;
        
        // Package
        $package = get_post($booking_data['package_id'] ?? 0);
        $data['package_title'] = $package ? $package->post_title : '';
        
        // Parks
        $data['parks'] = $this->get_parks_names($booking_data);
        
        // Meals
        $data['include_meals'] = !empty($booking_data['meal_vouchers']) ? 'Yes' : 'No';
        $data['meals_per_day'] = $booking_data['meals_per_day'] ?? 0;
        
        // Transportation
        $data['transportation'] = $this->format_transportation($booking_data['transportation'] ?? 'own');
        
        // Location
        $location = get_term($booking_data['location_id'] ?? 0, 'location');
        $data['festival_location'] = ($location && !is_wp_error($location)) ? $location->name : '';
        
        // Date
        if (!empty($booking_data['date_selection'])) {
            try {
                $date_obj = new DateTime($booking_data['date_selection']);
                $data['festival_date'] = $date_obj->format('F j, Y');
                $data['festival_date_day'] = $date_obj->format('l');
            } catch (Exception $e) {
                $data['festival_date'] = $booking_data['date_selection'];
                $data['festival_date_day'] = '';
            }
        } else {
            $data['festival_date'] = '';
            $data['festival_date_day'] = '';
        }
        
        // Lodging
        $data['lodging_dates'] = $booking_data['lodging_dates'] ?? '';
        
        // Director contact
        $data['director_phone'] = get_user_meta($user->ID, 'phone', true) ?: '';
        $data['director_cellphone'] = get_user_meta($user->ID, 'cell_number', true) ?: '';
        
        // School details
        if (!empty($booking_data['school_id'])) {
            $school_data = $this->get_school_details($user->ID, $booking_data['school_id']);
            $data = array_merge($data, $school_data);
        } else {
            $data['school_name'] = '';
            $data['school_address_1'] = '';
            $data['school_city'] = '';
            $data['school_state'] = '';
            $data['school_zip'] = '';
            $data['school_phone'] = '';
        }
        
        // Group details
        $data['total_students'] = $booking_data['total_students'] ?? 0;
        $data['total_chaperones'] = $booking_data['total_chaperones'] ?? 0;
        
        // Notes
        $data['notes'] = $booking_data['special_notes'] ?? '';
        
        return $data;
    }
    
    /**
     * Get school details from CRUD
     */
    private function get_school_details($user_id, $school_id) {
        $details = array(
            'school_name' => '',
            'school_address_1' => '',
            'school_city' => '',
            'school_state' => '',
            'school_zip' => '',
            'school_phone' => ''
        );
        
        if (class_exists('OC_Bookings_CRUD')) {
            $schools = OC_Bookings_CRUD::get_user_schools($user_id, get_current_blog_id());
            
            if (is_array($schools)) {
                foreach ($schools as $school) {
                    if ($school['id'] == $school_id) {
                        $details['school_name'] = $school['school_name'] ?? '';
                        $details['school_address_1'] = $school['school_address'] ?? '';
                        $details['school_city'] = $school['school_city'] ?? '';
                        $details['school_state'] = $school['school_state'] ?? '';
                        $details['school_zip'] = $school['school_zip'] ?? '';
                        $details['school_phone'] = $school['school_phone'] ?? '';
                        break;
                    }
                }
            }
        }
        
        return $details;
    }
    
    /**
     * Get park names
     */
    private function get_parks_names($booking_data) {
        $park_names = array();
        
        if (!empty($booking_data['parks_selection'])) {
            $parks_array = is_string($booking_data['parks_selection'])
                ? json_decode($booking_data['parks_selection'], true)
                : $booking_data['parks_selection'];
            
            if (is_array($parks_array)) {
                foreach ($parks_array as $park_id) {
                    if ($park_id === 'other') {
                        if (!empty($booking_data['other_park_name'])) {
                            $park_names[] = $booking_data['other_park_name'];
                        }
                    } else {
                        $park = get_term($park_id, 'parks');
                        if ($park && !is_wp_error($park)) {
                            $park_names[] = $park->name;
                        }
                    }
                }
            }
        }
        
        return !empty($park_names) ? '<br>' . implode('<br>', $park_names) : '';
    }
    
    /**
     * Format transportation
     */
    private function format_transportation($transport) {
        $options = array(
            'own' => 'We Will Provide Our Own Transportation',
            'quote' => 'Please Provide A Quote For Transportation'
        );
        return $options[$transport] ?? 'We Will Provide Our Own Transportation';
    }
}
