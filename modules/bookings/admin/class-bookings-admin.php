<?php

/**
 * Bookings Admin Interface
 * Migrated from class-owlth-booking-admin-page.php with EXACT logic preserved
 */

if (!defined('ABSPATH')) {
    exit;
}

class OC_Bookings_Admin
{

    private $module_id;
    private $version;
    private $template_loader;

    private $booking_crud;

    private $screen_id;

    private $booking_details_metaboxes;

    public function __construct($module_id, $version, $template_loader)
    {
        $this->module_id = $module_id;
        $this->version = $version;
        $this->template_loader = $template_loader;
        $this->screen_id = 'toplevel_page_organization-bookings';

        $this->booking_crud = new OC_Bookings_CRUD();
        $this->booking_details_metaboxes = new Booking_Details_Metaboxes();
    }

    public function init()
    {
        // Enqueue assets
        add_action('admin_enqueue_scripts', array($this, 'enqueue_assets'));

        // Hook into admin_menu to add the booking page
        add_action('admin_menu', array($this, 'add_booking_menu'));

        // Register screen options for bookings list table
        add_action("load-{$this->screen_id}", array($this, 'register_screen_options'));
// ✅ NEW: Handle hotel assignment save
    add_action("load-{$this->screen_id}", array($this, 'handle_save_hotel_assignment'));
        // Save screen options
        add_filter('set-screen-option', array($this, 'set_screen_option'), 10, 3);

        // Handle saving booking details
        add_action('admin_post_save_booking_detail', array($this, 'save_booking_details'));
    }

    /**
     * Save booking details (Hotel, etc.)
     */
    public function save_booking_details()
    {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }

        check_admin_referer('save_booking_detail', 'booking_detail_nonce');

        $booking_id = isset($_POST['booking_id']) ? intval($_POST['booking_id']) : 0;
        if (!$booking_id) {
            wp_redirect(admin_url('admin.php?page=organization-bookings&message=error'));
            exit;
        }

        $data = array();

        // Save Hotel Assignment
        if (isset($_POST['hotel_id'])) {
            $data['hotel_id'] = intval($_POST['hotel_id']);
        }

        // Save Count Per Room (into booking_data JSON)
        if (isset($_POST['count_per_room'])) {
            $data['count_per_room'] = intval($_POST['count_per_room']);
        }

        // Update booking
        $result = $this->booking_crud->update_booking($booking_id, get_current_blog_id(), $data);

        // Redirect back
        wp_redirect(add_query_arg(array(
            'page' => 'organization-bookings',
            'action' => 'view',
            'booking_id' => $booking_id,
            'message' => 'saved'
        ), admin_url('admin.php')));
        exit;
    }


    /**
     * Enqueue admin assets
     */
    public function enqueue_assets($hook)
    {
        if (strpos($hook, 'organization-booking') === false) {
            return;
        }

        $module_url = plugin_dir_url(dirname(__FILE__));

        // wp_enqueue_style(
        //     'bookings-admin-css',
        //     $module_url . 'assets/css/bookings-admin.css',
        //     array(),
        //     $this->version
        // );

        wp_enqueue_script(
            'bookings-admin-js',
            $module_url . 'assets/js/bookings-admin.js',
            array('jquery'),
            $this->version,
            true
        );

        // Enqueue postbox script for metabox collapsing on single booking view
        if (isset($_GET['action']) && $_GET['action'] === 'view' && isset($_GET['booking_id'])) {
            wp_enqueue_script('postbox');
            wp_enqueue_style('common');
            wp_enqueue_style('wp-admin');
        }

        // This is not needed since we have inline scripts in table class
        // But keeping for future single-page functionality
        wp_localize_script('bookings-admin-js', 'bookingsAdmin', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('booking_price_nonce'),
        ));
    }


    /**
     * Add booking menu to WordPress admin
     * EXACT from old system
     */
    public function add_booking_menu()
    {
        add_menu_page(
            __('Bookings', 'organization-core'),
            __('Bookings', 'organization-core'),
            'manage_options',
            'organization-bookings',
            array($this, 'bookings_page'),
            'dashicons-calendar-alt',
            1
        );

        // Submenu page to add new booking by Staff and share to customer to claim their booking
        add_submenu_page(
            'organization-bookings',
            __('Add New Booking', 'organization-core'),
            __('Add New Booking', 'organization-core'),
            'manage_options',
            'organization-add-booking',
            array($this, 'add_new_booking_page')
        );
    }


    /**
     * Display bookings page
     * 1. Booking table view (booking-table.php)
     * 2. Single booking detail view (booking-single.php)
     */
    public function bookings_page()
    {
        // Process bulk actions FIRST, before any output
        // $this->handle_bulk_delete_action();

        // Show messages
        if (isset($_GET['message'])) {
            $message = sanitize_text_field($_GET['message']);
            $messages = array(
                'deleted' => __('Booking deleted successfully.', 'organization-core'),
                'confirmed' => __('Booking confirmed successfully.', 'organization-core'),
                'completed' => __('Booking marked as completed.', 'organization-core'),
                'cancelled' => __('Booking cancelled successfully.', 'organization-core'),
            );

            if (isset($messages[$message])) {
                echo '<div class="notice notice-success is-dismissible"><p>' . $messages[$message] . '</p></div>';
            }
        }

        // Check if table exists
        if (!$this->check_table_exists()) {
            echo '<div class="wrap">';
            echo '<h1>' . __('Bookings', 'organization-core') . '</h1>';
            echo '<div class="notice notice-error"><p>';
            echo __('Bookings table does not exist. Please activate the plugin properly.', 'organization-core');
            echo '</p></div>';
            echo '</div>';
            return;
        }

        // ✅ CHECK IF VIEWING SINGLE BOOKING
        if (isset($_GET['action']) && $_GET['action'] === 'view' && isset($_GET['booking_id'])) {
            $this->template_loader->get_template(
                'booking-single.php',
                array(
                    'booking_crud' => $this->booking_crud,
                ),
                $this->module_id,
                plugin_dir_path(__FILE__) . '../templates/admin/'
            );
            return;
        }

        // Otherwise show table
        // require_once ORGANIZATION_CORE_PLUGIN_DIR . 'modules/bookings/templates/admin/new-table.php';
        $this->template_loader->get_template(
            'booking-table.php',
            array(),
            $this->module_id,
            plugin_dir_path(__FILE__) . '../templates/admin/'
        );
    }

    /**
     * Display add new booking page
     * TODO: Implement the actual form and logic, unique share URL generation, validate claim booking by user email verification
     */
    public function add_new_booking_page()
    {
        echo '<div class="wrap">';
        echo '<h1>' . __('Add New Booking', 'organization-core') . '</h1>';
        echo '<p>' . __('This feature is under development. Please check back later.', 'organization-core') . '</p><hr/>';
        echo '<h2>Future concept:</h2>';
        echo '<ol>
            <li>Staff fills out booking form with customer details.</li>
            <li>Generate unique shareable URL for customer to claim booking.</li>
            <li>Send email to customer with claim URL.</li>
            <li>Customer clicks URL, verifies email, and completes booking details.</li>
        </ol>';
        // 1. Staff fills out booking form with customer details
        // 2. Generate unique shareable URL for customer to claim booking
        // 3. Send email to customer with claim URL
        // 4. Customer clicks URL, verifies email, and completes booking details

        echo '</div>';
    }


    /**
     * Register screen options for the bookings list table
     */
    public function register_screen_options()
    {

        $screen = get_current_screen();

        if ($screen->id !== 'toplevel_page_organization-bookings') {
            return;
        }

        // Single booking view - add metabox help and screen options
        if (isset($_GET['action']) && $_GET['action'] === 'view') {
            // Register metaboxes FIRST so they appear in Screen Options
            $this->booking_details_metaboxes->init($this->screen_id, $this->booking_crud);

            // Add help tabs for single booking
            $screen->add_help_tab(array(
                'id' => 'booking_overview',
                'title' => __('Booking Overview', 'organization-core'),
                'content' => '<p>' . __('This page shows detailed information about a single booking including customer details, group information, hotel assignments, and rooming lists.', 'organization-core') . '</p>' .
                    '<p>' . __('<strong>Status:</strong> You can change the booking status using the dropdown in the sidebar.', 'organization-core') . '</p>' .
                    '<p>' . __('<strong>Price:</strong> Update the total amount directly from the sidebar.', 'organization-core') . '</p>',
            ));

            $screen->add_help_tab(array(
                'id' => 'booking_metaboxes',
                'title' => __('Metaboxes', 'organization-core'),
                'content' => '<p>' . __('You can drag and drop metaboxes to rearrange them on the page.', 'organization-core') . '</p>' .
                    '<p>' . __('Click the title bar of any metabox to expand or collapse it.', 'organization-core') . '</p>' .
                    '<p>' . __('Use the Screen Options tab at the top right to show or hide specific metaboxes.', 'organization-core') . '</p>',
            ));

            $screen->add_help_tab(array(
                'id' => 'rooming_list',
                'title' => __('Rooming List', 'organization-core'),
                'content' => '<p>' . __('The rooming list shows how attendees are assigned to hotel rooms.', 'organization-core') . '</p>' .
                    '<p>' . __('<strong>Add Room:</strong> Create a new room assignment.', 'organization-core') . '</p>' .
                    '<p>' . __('<strong>Generate Rooming List:</strong> Automatically assign attendees to rooms based on capacity.', 'organization-core') . '</p>' .
                    '<p>' . __('<strong>Save & Lock List:</strong> Finalize the rooming list to prevent further changes.', 'organization-core') . '</p>',
            ));

            // Add help sidebar
            $screen->set_help_sidebar(
                '<p><strong>' . __('For more information:', 'organization-core') . '</strong></p>' .
                    '<p><a href="#" target="_blank">' . __('Documentation', 'organization-core') . '</a></p>' .
                    '<p><a href="#" target="_blank">' . __('Support Forum', 'organization-core') . '</a></p>'
            );

            // Enable metabox screen options for single booking view
            add_screen_option('layout_columns', array(
                'max' => 2,
                'default' => 2
            ));

            return;
        }

        // List view - add help tabs for table
        $screen->add_help_tab(array(
            'id' => 'bookings_overview',
            'title' => __('Overview', 'organization-core'),
            'content' => '<p>' . __('This screen displays all bookings in your system. You can filter bookings by package, festival date, or booking date.', 'organization-core') . '</p>' .
                '<p>' . __('Use the search box to find specific bookings by customer name, email, or booking ID.', 'organization-core') . '</p>',
        ));

        $screen->add_help_tab(array(
            'id' => 'bookings_actions',
            'title' => __('Available Actions', 'organization-core'),
            'content' => '<p>' . __('<strong>View:</strong> Click on a booking to see detailed information.', 'organization-core') . '</p>' .
                '<p>' . __('<strong>Delete:</strong> Remove bookings individually or use bulk delete for multiple bookings.', 'organization-core') . '</p>' .
                '<p>' . __('<strong>Filter:</strong> Use the filter options to narrow down bookings by package, date range, or other criteria.', 'organization-core') . '</p>',
        ));

        $screen->add_help_tab(array(
            'id' => 'bookings_screen_content',
            'title' => __('Screen Content', 'organization-core'),
            'content' => '<p>' . __('You can customize the display of this screen using Screen Options at the top right.', 'organization-core') . '</p>' .
                '<p>' . __('<strong>Columns:</strong> Show or hide specific columns in the table.', 'organization-core') . '</p>' .
                '<p>' . __('<strong>Per Page:</strong> Adjust how many bookings are displayed per page.', 'organization-core') . '</p>',
        ));

        // Add help sidebar for list view
        $screen->set_help_sidebar(
            '<p><strong>' . __('For more information:', 'organization-core') . '</strong></p>' .
                '<p><a href="#" target="_blank">' . __('Documentation', 'organization-core') . '</a></p>' .
                '<p><a href="#" target="_blank">' . __('Support Forum', 'organization-core') . '</a></p>'
        );

        // Process bulk actions BEFORE any output
        // $this->handle_bulk_delete_action();

        $option = 'per_page';
        $args = array(
            'label' => __('Bookings per page', 'organization-core'),
            'default' => 20,
            'option' => 'bookings_per_page'
        );

        add_screen_option($option, $args);

        // Add columns filter for this screen
        add_filter("manage_{$screen->id}_columns", array($this, 'get_table_columns'));
    }

/**
 * Handle hotel assignment save
 */
public function handle_save_hotel_assignment()
{
    // Check if form submitted
    if (!isset($_POST['save_hotel_assignment'])) {
        return;
    }

    // Get booking ID
    $booking_id = isset($_GET['booking_id']) ? absint($_GET['booking_id']) : 0;
    if (!$booking_id) {
        return;
    }

    // ✅ FIXED: Match the nonce name from metabox
    if (!isset($_POST['hotel_nonce']) || !wp_verify_nonce($_POST['hotel_nonce'], 'save_hotel_assignment_' . $booking_id)) {
        wp_die(__('Security check failed', 'organization-core'));
    }

    // Check permissions
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have permission to perform this action', 'organization-core'));
    }

    // ✅ FIXED: Match field names from metabox
    $hotel_id = isset($_POST['booking_hotel_id']) ? intval($_POST['booking_hotel_id']) : 0;
    
    if (!$hotel_id) {
        add_action('admin_notices', function() {
            echo '<div class="notice notice-error is-dismissible"><p>' . 
                 __('Please select a hotel', 'organization-core') . 
                 '</p></div>';
        });
        return;
    }

    // Get hotel name from hotels
    $hotels = OC_Hotels_CRUD::get_hotels(array('limit' => -1));
    $hotel_name = '';
    
    foreach ($hotels as $h) {
        if ($h->id == $hotel_id) {
            $hotel_name = $h->name;
            break;
        }
    }

    // ✅ FIXED: Match all field names from metabox
    $hotel_data = array(
        'hotel_id'       => $hotel_id,
        'hotel_name'     => $hotel_name,
        'hotel_address'  => isset($_POST['booking_hotel_address']) ? sanitize_text_field(wp_unslash($_POST['booking_hotel_address'])) : '',
        'count_per_room' => isset($_POST['booking_hotel_count_per_room']) ? intval($_POST['booking_hotel_count_per_room']) : 0,
        'rooms_allotted' => isset($_POST['booking_hotel_rooms_allotted']) ? intval($_POST['booking_hotel_rooms_allotted']) : 0,
        'checkin_date'   => isset($_POST['booking_hotel_checkin']) ? sanitize_text_field(wp_unslash($_POST['booking_hotel_checkin'])) : '',
        'checkout_date'  => isset($_POST['booking_hotel_checkout']) ? sanitize_text_field(wp_unslash($_POST['booking_hotel_checkout'])) : '',
        'due_date'       => isset($_POST['booking_hotel_due_date']) ? sanitize_text_field(wp_unslash($_POST['booking_hotel_due_date'])) : '',
    );

    // Update booking via CRUD
    $result = $this->booking_crud->update_booking(
        $booking_id,
        get_current_blog_id(),
        array('hotel_data' => $hotel_data),
        ''
    );

    if ($result) {
        add_action('admin_notices', function() {
            echo '<div class="notice notice-success is-dismissible"><p>' . 
                 __('Hotel assignment saved successfully!', 'organization-core') . 
                 '</p></div>';
        });
    } else {
        add_action('admin_notices', function() {
            echo '<div class="notice notice-error is-dismissible"><p>' . 
                 __('Failed to save hotel assignment', 'organization-core') . 
                 '</p></div>';
        });
    }
}


    /**
     * Register metaboxes for booking detail screen
     */
    public function register_booking_metaboxes()
    {
        $this->booking_details_metaboxes->init($this->screen_id, $this->booking_crud);
    }


    /**
     * Add metabox visibility settings to Screen Options
     */
    public function add_metabox_screen_settings($settings, $screen)
    {
        if ($screen->id !== 'toplevel_page_organization-bookings') {
            return $settings;
        }

        // Only for single booking view
        if (!isset($_GET['action']) || $_GET['action'] !== 'view') {
            return $settings;
        }

        // The postboxes.js automatically handles metabox visibility
        // We just need to ensure metaboxes are properly registered
        return $settings;
    }


    /**
     * Handle bulk delete action before any output
     */
    private function handle_bulk_delete_action()
    {
        // Only process bulk-delete action
        if (
            !isset($_REQUEST['action']) ||
            ($_REQUEST['action'] !== 'bulk-delete' && (!isset($_REQUEST['action2']) || $_REQUEST['action2'] !== 'bulk-delete'))
        ) {
            return;
        }

        // Verify nonce
        if (!isset($_REQUEST['_wpnonce']) || !wp_verify_nonce($_REQUEST['_wpnonce'], 'bulk-Bookings')) {
            return;
        }

        // Get selected items
        $request_ids = isset($_REQUEST['items']) ? wp_parse_id_list(wp_unslash($_REQUEST['items'])) : array();

        if (empty($request_ids)) {
            return;
        }

        // Perform deletions
        global $wpdb;
        $blog_id = get_current_blog_id();
        $count = 0;
        $failures = 0;

        foreach ($request_ids as $id) {
            if (OC_Bookings_CRUD::delete_booking($id, $blog_id)) {
                ++$count;
            } else {
                ++$failures;
            }
        }

        // Build clean redirect URL
        $redirect_args = array('page' => $_REQUEST['page']);
        $safe_params = ['s', 'paged', 'package_filter', 'festival_date_from', 'festival_date_to', 'booking_date_from', 'booking_date_to'];
        foreach ($safe_params as $param) {
            if (!empty($_REQUEST[$param])) {
                $redirect_args[$param] = sanitize_text_field($_REQUEST[$param]);
            }
        }

        // Add success/error messages
        if ($count > 0) {
            $redirect_args['deleted'] = $count;
        }
        if ($failures > 0) {
            $redirect_args['delete_failed'] = $failures;
        }

        // Redirect to clean URL (POST-Redirect-GET pattern)
        wp_redirect(admin_url('/wp-admin/admin.php?page=organization-bookings'));
        exit;
    }

    /**
     * Save screen option value
     */
    public function set_screen_option($status, $option, $value)
    {
        // Handle bookings per page option
        if ('bookings_per_page' === $option) {
            return $value;
        }

        // Handle metabox layout columns option
        if ('screen_layout_' . $this->screen_id === $option) {
            return $value;
        }

        return $status;
    }


    /**
     * Get table columns for screen options
     */
    public function get_table_columns($columns)
    {
        return array(
            'cb' => '<input type="checkbox" />',
            'id' => __('ID', 'organization-core'),
            'customer_name' => __('Customer', 'organization-core'),
            'package_name' => __('Package', 'organization-core'),
            'price' => __('Price', 'organization-core'),
            'location_name' => __('Location', 'organization-core'),
            'parks_selection' => __('Parks', 'organization-core'),
            'festival_date' => __('Festival Date', 'organization-core'),
            'booking_date' => __('Booking Date', 'organization-core'),
            'actions' => __('Actions', 'organization-core')
        );
    }





    /**
     * Check if bookings table exists
     */
    private function check_table_exists()
    {
        global $wpdb;
        $table_name = $wpdb->base_prefix . 'bookings';
        return $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
    }
}
