<?php
/**
 * Bookings Module Notifications Handler
 *
 * @package    Organization_Core
 * @subpackage Notifications/Handlers
 * @version    2.0.0
 */

if (!defined('WPINC')) {
    die;
}

class OC_Bookings_Notifications {
    
    public function init() {
        // Instant notifications
        add_action('org_core_booking_created', array($this, 'send_booking_confirmation'), 10, 4);
        add_action('oc_booking_status_changed', array($this, 'send_booking_status_changed'), 10, 3);
        add_action('oc_payment_received', array($this, 'send_payment_received'), 10, 2);
        add_action('oc_booking_updated', array($this, 'send_booking_updated'), 10, 2);
        add_action('oc_booking_cancelled', array($this, 'send_booking_cancelled'), 10, 2);
        
        // Scheduled notifications
        add_action('oc_send_booking_reminder', array($this, 'send_booking_reminder'), 10, 2);
        add_action('oc_send_rooming_list_due_reminder', array($this, 'send_rooming_list_due_reminder'), 10, 2);
        add_action('oc_send_draft_expiring_notice', array($this, 'send_draft_expiring'), 10, 2);
    }
    
    /**
     * Send booking confirmation
     */
    public function send_booking_confirmation($booking_id, $booking_data, $user, $blog_id) {
        if (!$booking_id || !$user) return;
        
        $email_data = $this->prepare_booking_data($booking_id, $booking_data, $user);
        
        // Send to user
        $user_data = array_merge($email_data, array('recipient_type' => 'user'));
        $handler = OC_Notification_Handler::get_instance();
        $handler->send_notification('booking_confirmation_user', $user_data);
        
        // Send to admin
        $admin_data = array_merge($email_data, array('recipient_type' => 'admin'));
        $handler->send_notification('booking_confirmation_admin', $admin_data);
    }
    
    /**
     * Send booking status changed notification
     */
    public function send_booking_status_changed($booking_id, $old_status, $new_status) {
        if (!class_exists('OC_Bookings_CRUD')) return;
        
        $booking = OC_Bookings_CRUD::get_booking($booking_id);
        if (!$booking) return;
        
        $user = get_userdata($booking->user_id);
        if (!$user) return;
        
        $data = array(
            'booking_ref' => $booking->booking_reference ?? $booking_id,
            'old_status' => ucfirst($old_status),
            'new_status' => ucfirst($new_status),
            'status_message' => $this->get_status_message($new_status),
            'user_name' => $user->display_name,
            'user_email' => $user->user_email
        );
        
        $handler = OC_Notification_Handler::get_instance();
        $handler->send_notification('booking_status_changed', $data);
    }
    
    /**
     * Send payment received notification
     */
    public function send_payment_received($booking_id, $payment_data) {
        if (!class_exists('OC_Bookings_CRUD')) return;
        
        $booking = OC_Bookings_CRUD::get_booking($booking_id);
        if (!$booking) return;
        
        $user = get_userdata($booking->user_id);
        if (!$user) return;
        
        $data = array(
            'booking_ref' => $booking->booking_reference ?? $booking_id,
            'amount' => $payment_data['amount'] ?? '0.00',
            'payment_method' => $payment_data['method'] ?? 'Unknown',
            'receipt_url' => $payment_data['receipt_url'] ?? '',
            'user_name' => $user->display_name,
            'user_email' => $user->user_email
        );
        
        $handler = OC_Notification_Handler::get_instance();
        $handler->send_notification('payment_received', $data);
    }
    
    /**
     * Send booking updated notification
     */
    public function send_booking_updated($booking_id, $updated_fields) {
        if (!class_exists('OC_Bookings_CRUD')) return;
        
        $booking = OC_Bookings_CRUD::get_booking($booking_id);
        if (!$booking) return;
        
        $user = get_userdata($booking->user_id);
        if (!$user) return;
        
        $data = array(
            'booking_ref' => $booking->booking_reference ?? $booking_id,
            'updated_fields' => implode(', ', $updated_fields),
            'update_time' => current_time('mysql'),
            'user_name' => $user->display_name,
            'user_email' => $user->user_email
        );
        
        // Send to user
        $handler = OC_Notification_Handler::get_instance();
        $handler->send_notification('booking_updated_user', $data);
        
        // Send to admin
        $data['recipient_type'] = 'admin';
        $handler->send_notification('booking_updated_admin', $data);
    }
    
    /**
     * Send booking cancelled notification
     */
    public function send_booking_cancelled($booking_id, $reason) {
        if (!class_exists('OC_Bookings_CRUD')) return;
        
        $booking = OC_Bookings_CRUD::get_booking($booking_id);
        if (!$booking) return;
        
        $user = get_userdata($booking->user_id);
        if (!$user) return;
        
        $data = array(
            'booking_ref' => $booking->booking_reference ?? $booking_id,
            'cancellation_reason' => $reason,
            'cancellation_date' => current_time('mysql'),
            'user_name' => $user->display_name,
            'user_email' => $user->user_email
        );
        
        // Send to user
        $handler = OC_Notification_Handler::get_instance();
        $handler->send_notification('booking_cancelled_user', $data);
        
        // Send to admin
        $data['recipient_type'] = 'admin';
        $handler->send_notification('booking_cancelled_admin', $data);
    }
    
    /**
     * Send booking reminder (7 days before)
     */
    public function send_booking_reminder($booking_id, $booking) {
        $user = get_userdata($booking->user_id);
        if (!$user) return;
        
        $data = array(
            'booking_ref' => $booking->booking_reference ?? $booking_id,
            'festival_date' => date('F j, Y', strtotime($booking->festival_date)),
            'days_until' => 7,
            'checklist_items' => array('Confirm attendance', 'Review rooming list', 'Check transportation'),
            'user_name' => $user->display_name,
            'user_email' => $user->user_email
        );
        
        $handler = OC_Notification_Handler::get_instance();
        $handler->send_notification('booking_reminder_7days', $data);
    }
    
    /**
     * Send rooming list due reminder
     */
    public function send_rooming_list_due_reminder($booking_id, $due_date) {
        if (!class_exists('OC_Bookings_CRUD')) return;
        
        $booking = OC_Bookings_CRUD::get_booking($booking_id);
        if (!$booking) return;
        
        $user = get_userdata($booking->user_id);
        if (!$user) return;
        
        $data = array(
            'booking_ref' => $booking->booking_reference ?? $booking_id,
            'due_date' => date('F j, Y', strtotime($due_date)),
            'days_until' => 3,
            'rooming_list_url' => admin_url('admin.php?page=rooming-lists&booking_id=' . $booking_id),
            'user_name' => $user->display_name,
            'user_email' => $user->user_email
        );
        
        $handler = OC_Notification_Handler::get_instance();
        $handler->send_notification('rooming_list_due_reminder_3days', $data);
    }
    
    /**
     * Send draft expiring notice
     */
    public function send_draft_expiring($booking_id, $booking) {
        $user = get_userdata($booking->user_id);
        if (!$user) return;
        
        $data = array(
            'booking_ref' => $booking->booking_reference ?? $booking_id,
            'created_date' => date('F j, Y', strtotime($booking->created_at)),
            'days_old' => 7,
            'edit_url' => admin_url('admin.php?page=bookings&action=edit&id=' . $booking_id),
            'user_name' => $user->display_name,
            'user_email' => $user->user_email
        );
        
        $handler = OC_Notification_Handler::get_instance();
        $handler->send_notification('draft_expiring', $data);
    }
    
    /**
     * Prepare booking data for emails
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
        }
        
        // School details
        if (!empty($booking_data['school_id']) && class_exists('OC_Bookings_CRUD')) {
            $schools = OC_Bookings_CRUD::get_user_schools($user->ID, get_current_blog_id());
            foreach ($schools as $school) {
                if ($school['id'] == $booking_data['school_id']) {
                    $data['school_name'] = $school['school_name'] ?? '';
                    $data['school_address_1'] = $school['school_address'] ?? '';
                    $data['school_city'] = $school['school_city'] ?? '';
                    $data['school_state'] = $school['school_state'] ?? '';
                    $data['school_zip'] = $school['school_zip'] ?? '';
                    $data['school_phone'] = $school['school_phone'] ?? '';
                    break;
                }
            }
        }
        
        // Group details
        $data['total_students'] = $booking_data['total_students'] ?? 0;
        $data['total_chaperones'] = $booking_data['total_chaperones'] ?? 0;
        
        return $data;
    }
    
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
    
    private function format_transportation($transport) {
        $options = array(
            'own' => 'We Will Provide Our Own Transportation',
            'quote' => 'Please Provide A Quote For Transportation'
        );
        return $options[$transport] ?? 'We Will Provide Our Own Transportation';
    }
    
    private function get_status_message($status) {
        $messages = array(
            'confirmed' => 'Your booking has been confirmed!',
            'pending' => 'Your booking is pending review.',
            'cancelled' => 'Your booking has been cancelled.',
            'completed' => 'Your booking is complete. Thank you!'
        );
        return $messages[$status] ?? 'Your booking status has been updated.';
    }
}
