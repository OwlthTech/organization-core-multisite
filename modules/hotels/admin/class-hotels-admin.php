<?php

/**
 * Hotels Admin Interface
 */

if (!defined('ABSPATH')) {
    exit;
}

class OC_Hotels_Admin
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
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_post_save_hotel', array($this, 'save_hotel'));
        add_action('admin_post_update_hotel', array($this, 'update_hotel'));
        add_action('load-toplevel_page_hotels', array($this, 'register_screen_options'));
        add_filter('set-screen-option', array($this, 'set_screen_option'), 10, 3);
    }

    public function add_admin_menu()
    {
        add_menu_page(
            __('Hotels', 'organization-core'),
            __('Hotels', 'organization-core'),
            'manage_options',
            'hotels',
            array($this, 'render_admin_page'),
            'dashicons-building',
            25
        );

        add_submenu_page(
            'hotels',
            __('All Hotels', 'organization-core'),
            __('All Hotels', 'organization-core'),
            'manage_options',
            'hotels',
            array($this, 'render_admin_page')
        );

        add_submenu_page(
            'hotels',
            __('Add New', 'organization-core'),
            __('Add New', 'organization-core'),
            'manage_options',
            'hotels-add-new',
            array($this, 'render_form_page')
        );
    }

    public function render_admin_page()
    {
        // Handle edit page
        if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['hotel_id'])) {
            $this->render_form_page();
            return;
        }

        // Handle actions like delete
        if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['hotel_id'])) {
            $this->handle_delete_action();
        }

        // Handle bulk delete
        if (isset($_POST['action']) && $_POST['action'] === 'bulk-delete') {
            $this->handle_bulk_delete_action();
        }

        $this->template_loader->get_template(
            'hotel-table.php',
            array(),
            $this->module_id,
            plugin_dir_path(__FILE__) . '../templates/admin/'
        );
    }

    public function render_form_page()
    {
        $hotel = null;
        
        // Check if we're editing
        if (isset($_GET['hotel_id'])) {
            $hotel_id = intval($_GET['hotel_id']);
            $hotel = OC_Hotels_CRUD::get_hotel_by_id($hotel_id);

            if (!$hotel) {
                wp_die(__('Hotel not found.', 'organization-core'));
            }
        }

        $this->template_loader->get_template(
            'add-hotel.php',
            array('hotel' => $hotel),
            $this->module_id,
            plugin_dir_path(__FILE__) . '../templates/admin/'
        );
    }

    public function save_hotel()
    {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }

        check_admin_referer('save_hotel_action', 'save_hotel_nonce');

        $name = sanitize_text_field($_POST['hotel_name']);
        $address = sanitize_textarea_field($_POST['hotel_address']);
        $number_of_person = intval($_POST['number_of_person']);
        $number_of_rooms = intval($_POST['number_of_rooms']);

        $data = array(
            'name' => $name,
            'address' => $address,
            'number_of_person' => $number_of_person,
            'number_of_rooms' => $number_of_rooms
        );

        $result = OC_Hotels_CRUD::create_hotel($data);

        if ($result) {
            wp_redirect(admin_url('admin.php?page=hotels&message=created'));
        } else {
            wp_redirect(admin_url('admin.php?page=hotels-add-new&message=error'));
        }
        exit;
    }

    public function update_hotel()
    {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }

        check_admin_referer('update_hotel_action', 'update_hotel_nonce');

        $hotel_id = intval($_POST['hotel_id']);
        $name = sanitize_text_field($_POST['hotel_name']);
        $address = sanitize_textarea_field($_POST['hotel_address']);
        $number_of_person = intval($_POST['number_of_person']);
        $number_of_rooms = intval($_POST['number_of_rooms']);

        $data = array(
            'name' => $name,
            'address' => $address,
            'number_of_person' => $number_of_person,
            'number_of_rooms' => $number_of_rooms
        );

        $result = OC_Hotels_CRUD::update_hotel($hotel_id, $data);

        if ($result !== false) {
            wp_redirect(admin_url('admin.php?page=hotels&message=updated'));
        } else {
            wp_redirect(admin_url('admin.php?page=hotels-edit&hotel_id=' . $hotel_id . '&message=error'));
        }
        exit;
    }

    private function handle_delete_action()
    {
        if (!isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'delete_hotel_' . $_GET['hotel_id'])) {
            wp_die('Security check failed');
        }

        $hotel_id = intval($_GET['hotel_id']);
        OC_Hotels_CRUD::delete_hotel($hotel_id);

        wp_redirect(admin_url('admin.php?page=hotels&message=deleted'));
        exit;
    }

    private function handle_bulk_delete_action()
    {
        if (!isset($_POST['hotel']) || !is_array($_POST['hotel'])) {
            return;
        }

        check_admin_referer('bulk-hotels');

        $hotel_ids = array_map('intval', $_POST['hotel']);
        $deleted_count = 0;

        foreach ($hotel_ids as $hotel_id) {
            if (OC_Hotels_CRUD::delete_hotel($hotel_id)) {
                $deleted_count++;
            }
        }

        wp_redirect(admin_url('admin.php?page=hotels&message=bulk_deleted&count=' . $deleted_count));
        exit;
    }

    /**
     * Register screen options for the hotels list table
     */
    public function register_screen_options()
    {
        $screen = get_current_screen();

        if ($screen->id !== 'toplevel_page_hotels') {
            return;
        }

        // Don't add screen options for edit page
        if (isset($_GET['action']) && $_GET['action'] === 'edit') {
            return;
        }

        // Add help tabs
        $screen->add_help_tab(array(
            'id' => 'hotels_overview',
            'title' => __('Overview', 'organization-core'),
            'content' => '<p>' . __('This screen displays all hotels in your system. You can filter hotels by number of rooms or capacity.', 'organization-core') . '</p>' .
                '<p>' . __('Use the search box to find specific hotels by name.', 'organization-core') . '</p>',
        ));

        $screen->add_help_tab(array(
            'id' => 'hotels_actions',
            'title' => __('Available Actions', 'organization-core'),
            'content' => '<p>' . __('<strong>Edit:</strong> Click on a hotel name or the Edit link to modify hotel details.', 'organization-core') . '</p>' .
                '<p>' . __('<strong>Delete:</strong> Remove hotels individually or use bulk delete for multiple hotels.', 'organization-core') . '</p>' .
                '<p>' . __('<strong>Filter:</strong> Use the filter options to narrow down hotels by room count or capacity.', 'organization-core') . '</p>',
        ));

        $screen->add_help_tab(array(
            'id' => 'hotels_screen_content',
            'title' => __('Screen Content', 'organization-core'),
            'content' => '<p>' . __('You can customize the display of this screen using Screen Options at the top right.', 'organization-core') . '</p>' .
                '<p>' . __('<strong>Columns:</strong> Show or hide specific columns in the table.', 'organization-core') . '</p>' .
                '<p>' . __('<strong>Per Page:</strong> Adjust how many hotels are displayed per page.', 'organization-core') . '</p>',
        ));

        // Add help sidebar
        $screen->set_help_sidebar(
            '<p><strong>' . __('For more information:', 'organization-core') . '</strong></p>' .
                '<p><a href="#" target="_blank">' . __('Documentation', 'organization-core') . '</a></p>' .
                '<p><a href="#" target="_blank">' . __('Support Forum', 'organization-core') . '</a></p>'
        );

        // Add screen option for per page
        $option = 'per_page';
        $args = array(
            'label' => __('Hotels per page', 'organization-core'),
            'default' => 20,
            'option' => 'hotels_per_page'
        );

        add_screen_option($option, $args);
    }

    /**
     * Save screen option value
     */
    public function set_screen_option($status, $option, $value)
    {
        if ('hotels_per_page' === $option) {
            return $value;
        }
        return $status;
    }
}
