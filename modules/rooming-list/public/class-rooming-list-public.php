<?php

/**
 * Rooming List Public Class
 * Handles public-facing functionality for rooming lists
 * 
 * @package    Organization_Core
 * @subpackage Rooming_List/Public
 */

if (!defined('ABSPATH')) {
    exit;
}

class OC_Rooming_List_Public
{
    private $module_id;
    private $version;
    private $template_loader;

    public function __construct($module_id, $version, $template_loader)
    {
        $this->module_id = $module_id;
        $this->version = $version;
        $this->template_loader = $template_loader;
    }

    /**
     * Initialize public functionality
     */
    public function init()
    {
        add_filter('query_vars', array($this, 'add_query_vars'), 10, 1);
        add_action('template_redirect', array($this, 'handle_rooming_list_page'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));
    }

    /**
     * Add custom query vars
     */
    public function add_query_vars($vars)
    {
        $vars[] = 'account_page';
        $vars[] = 'booking_id';
        return $vars;
    }

    /**
     * Handle rooming list page template
     */
    public function handle_rooming_list_page()
    {
        $account_page = get_query_var('account_page');
        
        if ($account_page === 'rooming-list') {
            // Check if user is logged in
            if (!is_user_logged_in()) {
                wp_redirect(home_url('/login'));
                exit;
            }

            $booking_id = get_query_var('booking_id');
            
            if (!$booking_id) {
                wp_die('Invalid booking ID');
            }

            // Verify user owns this booking
            if (!$this->user_owns_booking($booking_id)) {
                wp_die('You do not have permission to access this rooming list.');
            }

            // Load template
            $this->load_rooming_list_template($booking_id);
            exit;
        }
    }

    /**
     * Check if current user owns the booking
     */
    private function user_owns_booking($booking_id)
    {
        global $wpdb;
        $table_name = $wpdb->base_prefix . 'bookings';
        $user_id = get_current_user_id();
        $blog_id = get_current_blog_id();

        $booking = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE id = %d AND blog_id = %d AND user_id = %d",
            $booking_id,
            $blog_id,
            $user_id
        ));

        return !empty($booking);
    }

    /**
     * Load rooming list template
     */
    private function load_rooming_list_template($booking_id)
    {
        $template_path = plugin_dir_path(dirname(__FILE__)) . 'templates/public/page-rooming-list.php';
        
        if (file_exists($template_path)) {
            // Make booking_id available to template
            set_query_var('rooming_list_booking_id', $booking_id);
            include $template_path;
        } else {
            wp_die('Template not found');
        }
    }

    /**
     * Enqueue public assets
     */
    public function enqueue_assets()
    {
        $account_page = get_query_var('account_page');
        
        if ($account_page === 'rooming-list') {
            $module_url = plugin_dir_url(dirname(__FILE__));
            
            // Enqueue CSS
            wp_enqueue_style(
                'rooming-list-public',
                $module_url . 'assets/css/rooming-list-public.css',
                array(),
                $this->version
            );

            // Enqueue Core JS (Shared logic)
            wp_enqueue_script(
                'rooming-list-core',
                $module_url . 'assets/js/rooming-list-core.js',
                array('jquery'),
                $this->version,
                true
            );

            // Enqueue Public JS
            wp_enqueue_script(
                'rooming-list-public',
                $module_url . 'assets/js/rooming-list-public.js',
                array('jquery', 'rooming-list-core'),
                $this->version,
                true
            );

            // Localize script
            $booking_id = get_query_var('booking_id');
            
            // Get rooming list data
            require_once plugin_dir_path(dirname(__FILE__)) . 'crud.php';
            $rooming_list = OC_Rooming_List_CRUD::get_rooming_list($booking_id);
            
            // Get booking to extract hotel data
            $bookings_crud_path = WP_PLUGIN_DIR . '/organization-core-new/modules/bookings/crud.php';
            if (file_exists($bookings_crud_path)) {
                require_once $bookings_crud_path;
                $booking = OC_Bookings_CRUD::get_booking($booking_id, get_current_blog_id());
                
                $hotel_data = is_string($booking['hotel_data']) 
                    ? json_decode($booking['hotel_data'], true) 
                    : $booking['hotel_data'];
                
                $max_per_room = isset($hotel_data['count_per_room']) ? intval($hotel_data['count_per_room']) : 4;
                $rooms_allotted = isset($hotel_data['rooms_allotted']) ? intval($hotel_data['rooms_allotted']) : 0;
                if ($max_per_room < 1) $max_per_room = 4;
            } else {
                $max_per_room = 4;
                $rooms_allotted = 0;
            }
            
            wp_localize_script('rooming-list-public', 'roomingListPublic', array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('rooming_list_public_nonce'),
                'booking_id' => $booking_id,
                'rooming_list' => $rooming_list,
                'max_per_room' => $max_per_room,
                'rooms_allotted' => $rooms_allotted
            ));
        }
    }
}
