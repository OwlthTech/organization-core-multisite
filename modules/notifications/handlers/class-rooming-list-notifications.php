<?php
/**
 * Rooming List Module Notifications Handler
 *
 * @package    Organization_Core
 * @subpackage Notifications/Handlers
 * @version    2.0.0
 */

if (!defined('WPINC')) {
    die;
}

class OC_Rooming_List_Notifications {
    
    public function init() {
        // Instant notifications
        add_action('oc_rooming_list_created', array($this, 'send_rooming_list_created'), 10, 2);
        add_action('oc_rooming_list_updated', array($this, 'send_rooming_list_updated'), 10, 2);
        add_action('oc_rooming_list_locked', array($this, 'send_rooming_list_locked'), 10, 2);
        add_action('oc_rooming_list_unlocked', array($this, 'send_rooming_list_unlocked'), 10, 2);
        add_action('oc_rooming_list_auto_locked', array($this, 'send_rooming_list_auto_locked'), 10, 2);
        
        // Scheduled notifications
        add_action('oc_send_rooming_list_incomplete_warning', array($this, 'send_incomplete_warning'), 10, 2);
    }
    
    /**
     * Send rooming list created notification
     */
    public function send_rooming_list_created($booking_id, $rooming_data) {
        if (!class_exists('OC_Bookings_CRUD')) return;
        
        $booking = OC_Bookings_CRUD::get_booking($booking_id);
        if (!$booking) return;
        
        $user = get_userdata($booking->user_id);
        if (!$user) return;
        
        $data = array(
            'booking_ref' => $booking->booking_reference ?? $booking_id,
            'total_rooms' => $rooming_data['total_rooms'] ?? 0,
            'total_occupants' => $rooming_data['total_occupants'] ?? 0,
            'due_date' => $rooming_data['due_date'] ?? '',
            'edit_url' => $rooming_data['edit_url'] ?? '',
            'user_name' => $user->display_name,
            'user_email' => $user->user_email
        );
        
        $handler = OC_Notification_Handler::get_instance();
        $handler->send_notification('rooming_list_created', $data);
    }
    
    /**
     * Send rooming list updated notification
     */
    public function send_rooming_list_updated($booking_id, $rooming_data) {
        if (!class_exists('OC_Bookings_CRUD')) return;
        
        $booking = OC_Bookings_CRUD::get_booking($booking_id);
        if (!$booking) return;
        
        $user = get_userdata($booking->user_id);
        if (!$user) return;
        
        $data = array(
            'booking_ref' => $booking->booking_reference ?? $booking_id,
            'update_time' => current_time('mysql'),
            'user_name' => $user->display_name,
            'user_email' => $user->user_email
        );
        
        $handler = OC_Notification_Handler::get_instance();
        $handler->send_notification('rooming_list_updated', $data);
    }
    
    /**
     * Send rooming list locked notification
     */
    public function send_rooming_list_locked($booking_id, $locked_by) {
        if (!class_exists('OC_Bookings_CRUD')) return;
        
        $booking = OC_Bookings_CRUD::get_booking($booking_id);
        if (!$booking) return;
        
        $user = get_userdata($booking->user_id);
        if (!$user) return;
        
        $data = array(
            'booking_ref' => $booking->booking_reference ?? $booking_id,
            'locked_by' => $locked_by,
            'locked_time' => current_time('mysql'),
            'user_name' => $user->display_name,
            'user_email' => $user->user_email
        );
        
        // Send to user
        $handler = OC_Notification_Handler::get_instance();
        $handler->send_notification('rooming_list_locked_user', $data);
        
        // Send to admin
        $data['recipient_type'] = 'admin';
        $handler->send_notification('rooming_list_locked_admin', $data);
    }
    
    /**
     * Send rooming list unlocked notification
     */
    public function send_rooming_list_unlocked($booking_id, $unlocked_by) {
        if (!class_exists('OC_Bookings_CRUD')) return;
        
        $booking = OC_Bookings_CRUD::get_booking($booking_id);
        if (!$booking) return;
        
        $user = get_userdata($booking->user_id);
        if (!$user) return;
        
        $data = array(
            'booking_ref' => $booking->booking_reference ?? $booking_id,
            'unlocked_by' => $unlocked_by,
            'unlocked_time' => current_time('mysql'),
            'user_name' => $user->display_name,
            'user_email' => $user->user_email
        );
        
        // Send to user
        $handler = OC_Notification_Handler::get_instance();
        $handler->send_notification('rooming_list_unlocked_user', $data);
        
        // Send to admin
        $data['recipient_type'] = 'admin';
        $handler->send_notification('rooming_list_unlocked_admin', $data);
    }
    
    /**
     * Send rooming list auto-locked notification
     */
    public function send_rooming_list_auto_locked($booking_id, $due_date) {
        if (!class_exists('OC_Bookings_CRUD')) return;
        
        $booking = OC_Bookings_CRUD::get_booking($booking_id);
        if (!$booking) return;
        
        $user = get_userdata($booking->user_id);
        if (!$user) return;
        
        $data = array(
            'booking_ref' => $booking->booking_reference ?? $booking_id,
            'due_date' => $due_date,
            'locked_time' => current_time('mysql'),
            'user_name' => $user->display_name,
            'user_email' => $user->user_email
        );
        
        // Send to user
        $handler = OC_Notification_Handler::get_instance();
        $handler->send_notification('rooming_list_auto_locked_user', $data);
        
        // Send to admin
        $data['recipient_type'] = 'admin';
        $handler->send_notification('rooming_list_auto_locked_admin', $data);
    }
    
    /**
     * Send incomplete warning (1 day before due date)
     */
    public function send_incomplete_warning($booking_id, $list_data) {
        if (!class_exists('OC_Bookings_CRUD')) return;
        
        $booking = OC_Bookings_CRUD::get_booking($booking_id);
        if (!$booking) return;
        
        $user = get_userdata($booking->user_id);
        if (!$user) return;
        
        $data = array(
            'booking_ref' => $booking->booking_reference ?? $booking_id,
            'due_date' => $list_data->due_date,
            'days_until' => 1,
            'edit_url' => admin_url('admin.php?page=rooming-lists&booking_id=' . $booking_id),
            'user_name' => $user->display_name,
            'user_email' => $user->user_email
        );
        
        $handler = OC_Notification_Handler::get_instance();
        $handler->send_notification('rooming_list_incomplete', $data);
    }
}
