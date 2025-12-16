<?php
/**
 * Hotels Module Notifications Handler
 *
 * @package    Organization_Core
 * @subpackage Notifications/Handlers
 * @version    2.0.0
 */

if (!defined('WPINC')) {
    die;
}

class OC_Hotels_Notifications {
    
    public function init() {
        // Instant notifications
        add_action('oc_hotel_created', array($this, 'send_hotel_created'), 10, 2);
        add_action('oc_hotel_updated', array($this, 'send_hotel_updated'), 10, 2);
        add_action('oc_hotel_deleted', array($this, 'send_hotel_deleted'), 10, 2);
        add_action('oc_hotel_assigned_to_booking', array($this, 'send_hotel_assigned'), 10, 3);
        
        // Scheduled notifications
        add_action('oc_send_hotel_availability_report', array($this, 'send_availability_report'), 10, 1);
    }
    
    /**
     * Send hotel created notification
     */
    public function send_hotel_created($hotel_id, $hotel_data) {
        $data = array(
            'hotel_name' => $hotel_data['hotel_name'] ?? '',
            'hotel_address' => $hotel_data['hotel_address'] ?? '',
            'total_rooms' => $hotel_data['total_rooms'] ?? 0,
            'capacity' => $hotel_data['capacity'] ?? 0
        );
        
        $handler = OC_Notification_Handler::get_instance();
        $handler->send_notification('hotel_created_admin', $data);
    }
    
    /**
     * Send hotel updated notification
     */
    public function send_hotel_updated($hotel_id, $hotel_data) {
        $data = array(
            'hotel_name' => $hotel_data['hotel_name'] ?? '',
            'hotel_id' => $hotel_id,
            'updated_fields' => $hotel_data['updated_fields'] ?? array()
        );
        
        $handler = OC_Notification_Handler::get_instance();
        $handler->send_notification('hotel_updated_admin', $data);
    }
    
    /**
     * Send hotel deleted notification
     */
    public function send_hotel_deleted($hotel_id, $hotel_data) {
        $data = array(
            'hotel_name' => $hotel_data['hotel_name'] ?? '',
            'hotel_id' => $hotel_id
        );
        
        $handler = OC_Notification_Handler::get_instance();
        $handler->send_notification('hotel_deleted_admin', $data);
    }
    
    /**
     * Send hotel assigned notification
     */
    public function send_hotel_assigned($booking_id, $hotel_id, $hotel_data) {
        if (!class_exists('OC_Bookings_CRUD')) return;
        
        $booking = OC_Bookings_CRUD::get_booking($booking_id);
        if (!$booking) return;
        
        $user = get_userdata($booking->user_id);
        if (!$user) return;
        
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
        $handler = OC_Notification_Handler::get_instance();
        $handler->send_notification('hotel_assigned_user', $data);
        
        // Send to admin
        $data['recipient_type'] = 'admin';
        $handler->send_notification('hotel_assigned_admin', $data);
    }
    
    /**
     * Send hotel availability report
     */
    public function send_availability_report($hotels) {
        $data = array(
            'hotels' => $hotels,
            'report_date' => current_time('mysql'),
            'total_hotels' => count($hotels)
        );
        
        $handler = OC_Notification_Handler::get_instance();
        $handler->send_notification('hotel_availability_report', $data);
    }
}
