<?php
/**
 * Authentication Module Notifications Handler
 *
 * @package    Organization_Core
 * @subpackage Notifications/Handlers
 * @version    2.0.0
 */

if (!defined('WPINC')) {
    die;
}

class OC_Authentication_Notifications {
    
    public function init() {
        // Instant notifications
        add_action('oc_user_registered', array($this, 'send_account_created'), 10, 2);
        add_action('wp_login', array($this, 'send_login_success'), 10, 2);
        add_action('oc_profile_updated', array($this, 'send_profile_updated'), 10, 2);
        add_action('password_reset', array($this, 'send_password_changed'), 10, 2);
        
        // Scheduled notifications
        add_action('oc_send_account_inactive_notice', array($this, 'send_account_inactive'), 10, 1);
    }
    
    /**
     * Send account created notification
     */
    public function send_account_created($user_id, $user_data) {
        $user = get_userdata($user_id);
        if (!$user) return;
        
        $data = array(
            'user_name' => $user->display_name,
            'user_email' => $user->user_email,
            'login_url' => wp_login_url(),
            'site_name' => get_bloginfo('name')
        );
        
        $handler = OC_Notification_Handler::get_instance();
        $handler->send_notification('account_created', $data);
    }
    
    /**
     * Send login success notification
     */
    public function send_login_success($user_login, $user) {
        $data = array(
            'user_name' => $user->display_name,
            'user_email' => $user->user_email,
            'login_time' => current_time('mysql'),
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'Unknown',
            'device' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
        );
        
        $handler = OC_Notification_Handler::get_instance();
        $handler->send_notification('login_success', $data);
    }
    
    /**
     * Send profile updated notification
     */
    public function send_profile_updated($user_id, $updated_fields) {
        $user = get_userdata($user_id);
        if (!$user) return;
        
        $data = array(
            'user_name' => $user->display_name,
            'user_email' => $user->user_email,
            'updated_fields' => implode(', ', $updated_fields),
            'update_time' => current_time('mysql')
        );
        
        $handler = OC_Notification_Handler::get_instance();
        $handler->send_notification('profile_updated', $data);
    }
    
    /**
     * Send password changed notification
     */
    public function send_password_changed($user, $new_pass) {
        $data = array(
            'user_name' => $user->display_name,
            'user_email' => $user->user_email,
            'change_time' => current_time('mysql'),
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'Unknown'
        );
        
        $handler = OC_Notification_Handler::get_instance();
        $handler->send_notification('password_changed', $data);
    }
    
    /**
     * Send account inactive notification
     */
    public function send_account_inactive($user_id) {
        $user = get_userdata($user_id);
        if (!$user) return;
        
        $data = array(
            'user_name' => $user->display_name,
            'user_email' => $user->user_email,
            'inactive_days' => 90,
            'login_url' => wp_login_url()
        );
        
        $handler = OC_Notification_Handler::get_instance();
        $handler->send_notification('account_inactive', $data);
    }
}
