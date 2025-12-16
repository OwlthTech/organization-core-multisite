<?php
/**
 * Notification Logger
 * Logs notification activity
 *
 * @package    Organization_Core
 * @subpackage Notifications
 * @version    1.0.0
 */

if (!defined('WPINC')) {
    die;
}

class OC_Notification_Logger {
    
    /**
     * Log notification activity
     * 
     * @param string $notification_type Type of notification
     * @param array $data Notification data
     * @param bool $result Success/failure
     */
    public static function log($notification_type, $data, $result, $error_message = '') {
        $log_entry = array(
            'timestamp' => current_time('mysql'),
            'type' => $notification_type,
            'recipient' => $data['user_email'] ?? 'admin',
            'status' => $result ? 'sent' : 'failed',
            'error' => $error_message,
            'blog_id' => get_current_blog_id()
        );
        
        // Log to WordPress error log (include error message when present)
        $status_icon = $result ? '✓' : '✗';
        $msg = sprintf(
            '[NOTIFICATION LOG] %s %s | To: %s | Type: %s',
            $status_icon,
            $log_entry['status'],
            $log_entry['recipient'],
            $notification_type
        );
        if (!empty($error_message)) {
            $msg .= ' | Error: ' . $error_message;
        }
        error_log($msg);
        
        // TODO: Store in database table for admin interface
        // This can be implemented later when building the admin logs page
    }
}
