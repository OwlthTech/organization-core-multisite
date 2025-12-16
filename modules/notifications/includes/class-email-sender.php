<?php
/**
 * Email Sender
 * Handles email template loading and sending
 *
 * @package    Organization_Core
 * @subpackage Notifications
 * @version    1.0.0
 */

if (!defined('WPINC')) {
    die;
}

class OC_Email_Sender {
    
    private $template_loader;
    private $last_error = '';
    
    public function __construct() {
        require_once plugin_dir_path(__FILE__) . 'class-template-loader.php';
        $this->template_loader = new OC_Notification_Template_Loader();
    }
    
    /**
     * Send email - USING BOOKINGS MODULE PATTERN
     * 
     * @param string $notification_type Notification type
     * @param array $data Email data
     * @return bool Success
     */
    public function send($notification_type, $data) {
        // Ensure Authentication SMTP hooks are initialized so PHPMailer is configured in any execution context
        if (class_exists('Authentication_Email_SMTP')) {
            try {
                Authentication_Email_SMTP::get_instance();
            } catch (Exception $e) {
                error_log('[EMAIL SENDER] Warning: Authentication_Email_SMTP initialization failed: ' . $e->getMessage());
            }
        }
        // Get template path
        $template = $this->get_template_path($notification_type);
        $template_subpath = $this->get_template_subpath($notification_type);
        
        if (!file_exists($template)) {
            error_log('[EMAIL SENDER] Template not found: ' . $template);
            return false;
        }
        
        // Get recipient
        $to = $this->get_recipient($notification_type, $data);
        
        if (!$to) {
            error_log('[EMAIL SENDER] No recipient for: ' . $notification_type);
            return false;
        }
        
        // Get subject
        $subject = $this->get_subject($notification_type, $data);
        
        // Merge sensible defaults so templates don't trigger undefined variable warnings
        $defaults = array(
            'site_name' => get_bloginfo('name'),
            'login_url' => wp_login_url(),
            'user_name' => $data['user_name'] ?? '',
            'user_email' => $data['user_email'] ?? '',
        );
        $data = array_merge($defaults, (array) $data);

        // Try using the central OC_Email_Handler if available. This ensures we use
        // the same template discovery and sending pipeline (including SMTP hooks)
        // and makes it easier to switch to async sending later.
        if (class_exists('OC_Email_Handler') && !empty($template_subpath)) {
            // Validate template exists through the central loader
            require_once ORGANIZATION_CORE_PLUGIN_DIR . 'includes/email/class-email-template-loader.php';
            if (OC_Email_Template_Loader::email_template_exists($template_subpath, 'notifications')) {
                $sent = OC_Email_Handler::send_with_template($to, $subject, $template_subpath, 'notifications', $data);
                if ($sent) {
                    error_log('[EMAIL SENDER] ✓ Email sent via OC_Email_Handler: ' . $notification_type . ' to ' . $to);
                    $this->last_error = '';
                    return true;
                }
                error_log('[EMAIL SENDER] ✗ OC_Email_Handler failed for: ' . $notification_type . ' to ' . $to);
                $this->last_error = 'OC_Email_Handler returned false';
                // fallback to legacy sending below
            }
        }

        // Load template content using bookings module pattern (legacy fallback)
        // Make data variables available in the template scope to avoid undefined vars
        $email_data = $data; // Keep for backward compatibility
        extract($data, EXTR_SKIP);
        ob_start();
        include $template;
        $email_body = ob_get_clean();

        // Wrap with header/footer
        $message = $this->template_loader->load($template, $data);

        // Send email - EXACT SAME AS BOOKINGS MODULE
        $headers = array('Content-Type: text/html; charset=UTF-8');
        $result = wp_mail($to, $subject, $message, $headers);
        
        if ($result) {
            error_log('[EMAIL SENDER] ✓ Email sent: ' . $notification_type . ' to ' . $to);
            $this->last_error = '';
            return true;
        }

        // First attempt failed — inspect PHPMailer for auth errors and optionally retry without SMTP
        global $phpmailer;
        $error = isset($phpmailer) && is_object($phpmailer) ? ($phpmailer->ErrorInfo ?? 'Unknown PHPMailer error') : 'Unknown error';
        error_log('[EMAIL SENDER] ✗ Email failed: ' . $notification_type . ' to ' . $to . ' - Error: ' . $error);

        // Detect authentication failure (common with Gmail bad credentials)
        $auth_error_keywords = array('Could not authenticate', 'Username and Password not accepted', 'Authentication');
        $is_auth_error = false;
        foreach ($auth_error_keywords as $kw) {
            if (stripos($error, $kw) !== false) {
                $is_auth_error = true;
                break;
            }
        }

        if ($is_auth_error && class_exists('Authentication_Email_SMTP')) {
            // When authentication fails, do NOT attempt automatic host/port probing or switch credentials.
            // These actions can mask the real issue (invalid app password / blocked sign-in). Instead, run a single SMTP test
            // and write clear actionable diagnostics to the logs so the admin can fix credentials or provider config.
            try {
                $auth_inst = Authentication_Email_SMTP::get_instance();

                // Run a single dedicated SMTP test to gather debug info
                $smtp_test = $auth_inst->test_smtp_credentials();
                error_log('[EMAIL SENDER] SMTP test result: ' . json_encode($smtp_test));

                // Provide a clear admin-level log message recommending how to fix it
                error_log('[EMAIL SENDER] ✗ Authentication failure detected. Please verify the SMTP username and App Password in Settings → Mail. If using Gmail, ensure 2-Step Verification is enabled and you are using a 16-character App Password (no spaces).');

                // If the SMTP test indicates bad credentials or suspicious password length, queue the unsent message
                $is_bad_credentials = !empty($smtp_test['bad_credentials']) || (isset($smtp_test['password_length']) && $smtp_test['password_length'] !== 16);

                if ($is_bad_credentials) {
                    // Record unsent email for admin review
                    $queue = get_option('mus_unsent_emails', array());
                    $queue[] = array(
                        'time' => current_time('timestamp'),
                        'to' => $to,
                        'subject' => $subject,
                        'message' => $message,
                        'headers' => $headers ?? array(),
                        'error' => $smtp_test,
                    );
                    update_option('mus_unsent_emails', $queue);

                    // Also ensure there is a transient set to pause retries for a reasonable window
                    if (!get_transient('mus_smtp_auth_failure')) {
                        set_transient('mus_smtp_auth_failure', array('time' => time(), 'message' => 'SMTP authentication failure detected', 'details' => $smtp_test), HOUR_IN_SECONDS);
                    }

                    error_log('[EMAIL SENDER] ✗ Message queued in unsent list due to SMTP auth failure. Admin should review Settings → Mail.');

                    $this->last_error = $error;
                    return false;
                }

            } catch (Exception $e) {
                error_log('[EMAIL SENDER] ✗ SMTP test exception: ' . $e->getMessage());
            }
        }

        // Provide actionable suggestion in logs for admins
        error_log('[EMAIL SENDER] Suggestion: Run the SMTP test in Authentication module (admin -> Tools -> Test SMTP) and verify credentials or use an app-password / dedicated SMTP service (Mailgun, SendGrid).');

        $this->last_error = $error;
        return false;
    }
    
    /**
     * Get template path for notification type
     */
    private function get_template_path($notification_type) {
        $base_path = plugin_dir_path(dirname(__FILE__)) . 'templates/emails/';
        
        // Map notification types to template paths
        $template_map = array(
            // Authentication
            'account_created' => 'authentication/account-created.php',
            'login_success' => 'authentication/login-success.php',
            'profile_updated' => 'authentication/profile-updated.php',
            'password_changed' => 'authentication/password-changed.php',
            
            // Bookings
            'booking_confirmation_user' => 'bookings/booking-confirmation-user.php',
            'booking_confirmation_admin' => 'bookings/booking-confirmation-admin.php',
            'rooming_list_due_reminder' => 'bookings/rooming-list-due-reminder.php',
            
            // Hotels
            'hotel_assigned_user' => 'hotels/hotel-assigned-user.php',
            'hotel_assigned_admin' => 'hotels/hotel-assigned-admin.php',
            
            // Rooming List
            'rooming_list_created' => 'rooming-list/rooming-list-created.php',
            'rooming_list_due_reminder_3days' => 'rooming-list/rooming-list-due-reminder.php',
        );
        
        if (isset($template_map[$notification_type])) {
            return $base_path . $template_map[$notification_type];
        }
        
        return '';
    }

    /**
     * Return the relative template sub-path used by the central email loader
     */
    private function get_template_subpath($notification_type) {
        $template_map = array(
            'account_created' => 'authentication/account-created.php',
            'login_success' => 'authentication/login-success.php',
            'profile_updated' => 'authentication/profile-updated.php',
            'password_changed' => 'authentication/password-changed.php',
            'booking_confirmation_user' => 'bookings/booking-confirmation-user.php',
            'booking_confirmation_admin' => 'bookings/booking-confirmation-admin.php',
            'rooming_list_due_reminder' => 'bookings/rooming-list-due-reminder.php',
            'hotel_assigned_user' => 'hotels/hotel-assigned-user.php',
            'hotel_assigned_admin' => 'hotels/hotel-assigned-admin.php',
            'rooming_list_created' => 'rooming-list/rooming-list-created.php',
            'rooming_list_due_reminder_3days' => 'rooming-list/rooming-list-due-reminder.php',
        );

        return $template_map[$notification_type] ?? '';
    }
    
    /**
     * Get recipient email address
     */
    private function get_recipient($notification_type, $data) {
        // Check if recipient_type is specified (for dual notifications)
        $recipient_type = $data['recipient_type'] ?? 'user';
        
        // For admin notifications
        if ($recipient_type === 'admin') {
            return get_option('admin_email');
        }
        
        // For user notifications
        if (isset($data['user_email'])) {
            return $data['user_email'];
        }
        
        // Fallback to admin
        return get_option('admin_email');
    }
    
    /**
     * Get email subject
     */
    private function get_subject($notification_type, $data) {
        $site_name = get_bloginfo('name');
        
        $subjects = array(
            // Authentication
            'account_created' => 'Welcome to ' . $site_name,
            
            // Bookings
            'booking_confirmation_user' => 'Booking Confirmation #' . ($data['booking_id'] ?? '') . ' - ' . $site_name,
            'booking_confirmation_admin' => 'New Booking Received #' . ($data['booking_id'] ?? '') . ' - ' . $site_name,
            'rooming_list_due_reminder' => 'Rooming List Due Soon - Booking #' . ($data['booking_ref'] ?? ''),
            
            // Hotels
            'hotel_assigned_user' => 'Hotel Assigned to Your Booking - ' . ($data['booking_ref'] ?? ''),
            'hotel_assigned_admin' => 'Hotel Assigned to Booking #' . ($data['booking_ref'] ?? ''),
            
            // Rooming List
            'rooming_list_created' => 'Rooming List Created - Booking #' . ($data['booking_ref'] ?? ''),
            'rooming_list_due_reminder_3days' => 'Rooming List Due in 3 Days - Booking #' . ($data['booking_ref'] ?? ''),
        );
        
        return $subjects[$notification_type] ?? 'Notification from ' . $site_name;
    }

    /**
     * Get last error message
     */
    public function get_last_error() {
        return $this->last_error;
    }
}

