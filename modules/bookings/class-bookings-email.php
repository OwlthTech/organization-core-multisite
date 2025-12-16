<?php

/**
 * Bookings Email Handler - Production Ready
 * Hooked to: org_core_booking_created
 * 
 * @package    Organization_Core
 * @subpackage Bookings/Email
 * @version    1.0.0
 */

if (!defined('WPINC')) {
    die;
}

class OC_Bookings_Email
{
    /**
     * Initialize email handlers
     * Called from module init or activator
     */
    public static function init()
    {
        // Hook into custom booking created action
        add_action(
            'org_core_booking_created',
            array(__CLASS__, 'handle_booking_created'),
            10,
            4
        );
    }

    /**
     * Main hook callback - fires after booking is created
     * 
     * Hook: org_core_booking_created
     * Called from: ajax.php handle_create_booking()
     */
    public static function handle_booking_created($booking_id, $booking_data, $user, $blog_id)
    {
        if (!$booking_id || !$user) {
            error_log('[BOOKINGS EMAIL]  Invalid parameters for booking emails');
            return;
        }

        // Send user confirmation email
        $user_sent = self::send_user_confirmation($booking_id, $booking_data, $user);

        // Send admin notification email
        $admin_sent = self::send_admin_notification($booking_id, $booking_data, $user);
        $admin_sent = "";

        // Log result
        if ($user_sent && $admin_sent) {
            error_log('[BOOKINGS EMAIL] Both emails sent for booking #' . $booking_id);
        } else {
            error_log('[BOOKINGS EMAIL]  Partial email failure for booking #' . $booking_id);
        }
    }

    /**
     * Send user booking confirmation email
     */
    public static function send_user_confirmation($booking_id, $booking_data, $user)
    {
        try {
            // Prepare data for template
            $email_data = self::prepare_booking_data($booking_id, $booking_data, $user);

            // Get template
            $template_path = plugin_dir_path(__FILE__) . 'templates/emails/booking-confirmation-user.php';
            if (!file_exists($template_path)) {
                return false;
            }

            // Build email HTML
            ob_start();
            include $template_path;
            $message = ob_get_clean();

            // Send email
            $subject = 'Booking Confirmation #' . $booking_id . ' - FORUM Music Festivals';
            $to = $user->user_email;

            return self::send_email($to, $subject, $message);
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Send admin booking notification email
     */
    public static function send_admin_notification($booking_id, $booking_data, $user)
    {
        try {
            // Get admin email
            $admin_email = get_site_option('admin_email') ?: get_option('admin_email');
            if (!$admin_email) {
                return false;
            }

            // Prepare data
            $email_data = self::prepare_booking_data($booking_id, $booking_data, $user);

            // Get template
            $template_path = plugin_dir_path(__FILE__) . 'templates/emails/booking-confirmation-admin.php';
            if (!file_exists($template_path)) {
                return false;
            }

            // Build email HTML
            ob_start();
            include $template_path;
            $message = ob_get_clean();

            // Send email
            $subject = 'New Booking Received #' . $booking_id . ' - FORUM Music Festivals';

            return self::send_email($admin_email, $subject, $message);
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Prepare booking data for email templates
     */
    private static function prepare_booking_data($booking_id, $booking_data, $user)
    {
        $data = array();

        $data['booking_id'] = $booking_id;
        $data['user_email'] = $user->user_email;
        $data['director_name'] = $user->display_name;

        // Package
        $package = get_post($booking_data['package_id'] ?? 0);
        $data['package_title'] = $package ? $package->post_title : '';

        // Parks
        $data['parks'] = self::get_parks_names($booking_data);

        // Meals
        $data['include_meals'] = !empty($booking_data['meal_vouchers']) ? 'Yes' : 'No';
        $data['meals_per_day'] = $booking_data['meals_per_day'] ?? 0;

        // Transportation
        $data['transportation'] = self::format_transportation($booking_data['transportation'] ?? 'own');

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
            $school_data = self::get_school_details($user->ID, $booking_data['school_id']);
            $data = array_merge($data, $school_data);
        } else {
            $data['school_name'] = '';
            $data['school_address_1'] = '';
            $data['school_address_2'] = '';
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
    private static function get_school_details($user_id, $school_id)
    {
        $details = array(
            'school_name' => '',
            'school_address_1' => '',
            'school_address_2' => '',
            'school_city' => '',
            'school_state' => '',
            'school_zip' => '',
            'school_phone' => ''
        );

        if (class_exists('OC_Bookings_CRUD')) {
            $schools = OC_Bookings_CRUD::get_user_schools(
                $user_id,
                get_current_blog_id()
            );

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
    private static function get_parks_names($booking_data)
    {
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
    private static function format_transportation($transport)
    {
        $options = array(
            'own' => 'We Will Provide Our Own Transportation',
            'quote' => 'Please Provide A Quote For Transportation'
        );
        return $options[$transport] ?? 'We Will Provide Our Own Transportation';
    }

    /**
     * Generic email sender
     */
    private static function send_email($to, $subject, $message)
    {
        $headers = array('Content-Type: text/html; charset=UTF-8');
        $result = wp_mail($to, $subject, $message, $headers);

        if ($result) {
            error_log('[BOOKINGS EMAIL] Email sent to: ' . $to);
        } else {
            error_log('[BOOKINGS EMAIL]  Email failed for: ' . $to);
        }

        return $result;
    }
}
