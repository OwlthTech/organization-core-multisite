<?php

/**
 * Rooming List Admin
 */

if (!defined('ABSPATH')) {
    exit;
}

class OC_Rooming_List_Admin
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

    public function init()
    {
        // Add Top-Level Menu Page
        add_action('admin_menu', array($this, 'add_admin_menu'));

        // Register screen options
        add_action("load-toplevel_page_organization-rooming-list", array($this, 'register_screen_options'));
        add_filter('set-screen-option', array($this, 'set_screen_option'), 10, 3);

        // Enqueue Scripts
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
    }

    /**
     * Enqueue Scripts and Styles
     */
    public function enqueue_scripts($hook)
    {
        // Check if we are on the rooming list page
        if (strpos($hook, 'organization-rooming-list') === false) {
            return;
        }

        // CSS
        wp_enqueue_style(
            'oc-rooming-list-admin-css',
            plugin_dir_url(dirname(__FILE__)) . 'assets/css/admin-rooming-list.css',
            array(),
            $this->version,
            'all'
        );

        // Core JS (Shared logic)
        wp_register_script(
            'oc-rooming-list-core-js',
            plugin_dir_url(dirname(__FILE__)) . 'assets/js/rooming-list-core.js',
            array('jquery'),
            $this->version,
            true
        );

        // Admin JS
        wp_register_script(
            'oc-rooming-list-admin-js',
            plugin_dir_url(dirname(__FILE__)) . 'assets/js/admin-rooming-list.js',
            array('jquery', 'oc-rooming-list-core-js'),
            $this->version,
            true
        );
    }

    /**
     * Add Admin Menu
     */
    public function add_admin_menu()
    {
        add_menu_page(
            __('Rooming List', 'organization-core'),
            __('Rooming List', 'organization-core'),
            'manage_options',
            'organization-rooming-list',
            array($this, 'render_main_page'),
            'dashicons-groups',
            26
        );
    }

    /**
     * Register Screen Options
     */
    public function register_screen_options()
    {
        $screen = get_current_screen();

        if ($screen->id !== 'toplevel_page_organization-rooming-list') {
            return;
        }

        // Don't add screen options for edit page
        if (isset($_GET['action']) && $_GET['action'] === 'edit') {
            return;
        }

        // Add help tabs
        $screen->add_help_tab(array(
            'id' => 'rooming_list_overview',
            'title' => __('Overview', 'organization-core'),
            'content' => '<p>' . __('This screen displays all bookings that can have rooming lists managed.', 'organization-core') . '</p>' .
                '<p>' . __('Only bookings with assigned hotels can have rooming lists managed.', 'organization-core') . '</p>',
        ));

        $screen->add_help_tab(array(
            'id' => 'rooming_list_actions',
            'title' => __('Available Actions', 'organization-core'),
            'content' => '<p>' . __('<strong>Manage List:</strong> Click to view and edit the rooming list for a booking.', 'organization-core') . '</p>' .
                '<p>' . __('<strong>Search:</strong> Use the search box to find bookings by school ID.', 'organization-core') . '</p>',
        ));

        $screen->add_help_tab(array(
            'id' => 'rooming_list_screen_content',
            'title' => __('Screen Content', 'organization-core'),
            'content' => '<p>' . __('You can customize the display of this screen using Screen Options at the top right.', 'organization-core') . '</p>' .
                '<p>' . __('<strong>Per Page:</strong> Adjust how many bookings are displayed per page.', 'organization-core') . '</p>',
        ));

        // Add help sidebar
        $screen->set_help_sidebar(
            '<p><strong>' . __('For more information:', 'organization-core') . '</strong></p>' .
                '<p><a href="#" target="_blank">' . __('Documentation', 'organization-core') . '</a></p>' .
                '<p><a href="#" target="_blank">' . __('Support Forum', 'organization-core') . '</a></p>'
        );

        $option = 'per_page';
        $args = array(
            'label' => __('Rooming Lists per page', 'organization-core'),
            'default' => 20,
            'option' => 'rooming_lists_per_page'
        );
        add_screen_option($option, $args);
    }

    /**
     * Set Screen Option
     */
    public function set_screen_option($status, $option, $value)
    {
        if ('rooming_lists_per_page' === $option) {
            return $value;
        }
        return $status;
    }

    /**
     * Main Page Router
     */
    public function render_main_page()
    {
        $action = isset($_GET['action']) ? $_GET['action'] : 'list';

        if ($action === 'edit' && isset($_GET['booking_id'])) {
            $this->render_edit_page();
        } else {
            $this->render_list_page();
        }
    }

    /**
     * Render List View
     */
    public function render_list_page()
    {
        if (!class_exists('OC_Bookings_CRUD')) {
            echo '<div class="wrap"><p>' . __('Bookings module is required.', 'organization-core') . '</p></div>';
            return;
        }

        $this->template_loader->get_template(
            'list-rooming-list.php',
            array(),
            $this->module_id,
            plugin_dir_path(__FILE__) . '../templates/admin/'
        );
    }

    /**
     * Render Edit View
     */
    public function render_edit_page()
    {
        $booking_id = isset($_GET['booking_id']) ? absint($_GET['booking_id']) : 0;

        if (!$booking_id) {
            echo '<div class="wrap"><h1>' . __('Rooming List', 'organization-core') . '</h1><p>' . __('Invalid Booking ID.', 'organization-core') . '</p></div>';
            return;
        }

        if (!class_exists('OC_Bookings_CRUD')) {
            echo '<div class="wrap"><p>' . __('Bookings module is required.', 'organization-core') . '</p></div>';
            return;
        }
        $booking_crud = new OC_Bookings_CRUD();
        $booking = $booking_crud->get_booking($booking_id, get_current_blog_id());

        if (!$booking) {
            echo '<div class="wrap"><h1>' . __('Rooming List', 'organization-core') . '</h1><p>' . __('Booking not found.', 'organization-core') . '</p></div>';
            return;
        }

        $hotel_data = array();
        if (!empty($booking['hotel_data'])) {
            $hotel_data = is_string($booking['hotel_data'])
                ? json_decode($booking['hotel_data'], true)
                : $booking['hotel_data'];
        }

        $max_per_room = isset($hotel_data['count_per_room']) ? intval($hotel_data['count_per_room']) : 4;
        $rooms_allotted = isset($hotel_data['rooms_allotted']) ? intval($hotel_data['rooms_allotted']) : 0;

        if ($max_per_room < 1) $max_per_room = 4;

        $rooming_list = OC_Rooming_List_CRUD::get_rooming_list($booking_id);

        // Enqueue and Localize Script
        wp_enqueue_script('oc-rooming-list-admin-js');
        wp_localize_script('oc-rooming-list-admin-js', 'ocRoomingListConfig', array(
            'booking_id' => $booking_id,
            'rooming_list' => $rooming_list,
            'max_per_room' => $max_per_room,
            'rooms_allotted' => $rooms_allotted,
            'nonce' => wp_create_nonce('booking_rooming_list_nonce')
        ));

        $this->template_loader->get_template(
            'edit-rooming-list.php',
            array(
                'booking_id' => $booking_id,
                'hotel_data' => $hotel_data,
                'max_per_room' => $max_per_room,
                'rooms_allotted' => $rooms_allotted
            ),
            $this->module_id,
            plugin_dir_path(__FILE__) . '../templates/admin/'
        );
    }
}
