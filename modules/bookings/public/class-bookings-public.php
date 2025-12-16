<?php

/**
 * Bookings Public Handler - Now extends abstract module
 */
class OC_Bookings_Public extends OC_Abstract_Module
{

    // ========================================
    // PROPERTIES - MUST USE protected (not private!)
    // ========================================

    // These properties are defined in the abstract class as protected
    // Child class CANNOT reduce visibility, so we use protected
    protected $module_id;
    protected $version;
    protected $template_loader;

    // Template map (module-specific - this is private, OK)
    private $template_map = array(
        'book-now' => 'public/page-book-now.php',
        'bookings-list' => 'public/page-bookings-list.php',
    );

    // Cache prefix (module-specific - this is private, OK)
    private $cache_prefix = 'booking_';

    // CRUD class (module-specific - this is private, OK)
    private $crud;

    // ========================================
    // CONSTRUCTOR & INITIALIZATION
    // ========================================

    /**
     * Constructor - Initialize with module data
     */
    public function __construct($module_id, $version, $template_loader)
    {
        // Call parent constructor (initializes template_loader)
        parent::__construct();

        $this->module_id = $module_id;
        $this->version = $version;
        $this->template_loader = $template_loader;

        require_once plugin_dir_path(__FILE__) . '../crud.php';
        $this->crud = 'OC_Bookings_CRUD';
    }


    /**
     * Get module ID (required by abstract)
     */
    public function get_module_id()
    {
        return $this->module_id;
    }


    /**
     * Get module configuration (required by abstract)
     */
    public function get_config()
    {
        return array(
            'id' => 'bookings',
            'name' => 'Bookings Module',
            'version' => $this->version,
        );
    }


    /**
     * Initialize public module
     */
    public function init()
    {
        add_action('init', array($this, 'start_session'), 1);
        add_filter('query_vars', array($this, 'add_booking_query_vars'), 10, 1);
        add_filter('template_include', array($this, 'include_booking_template'), 99, 1);
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'), 10);
    }


    // ========================================
    // SESSION MANAGEMENT
    // ========================================

    /**
     * Start session on init
     */
    public function start_session()
    {
        if (!session_id()) {
            session_start();
        }
    }


    // ========================================
    // ROUTING & QUERY VARS
    // ========================================

    /**
     * Register custom query variables
     */
    public function add_booking_query_vars($vars)
    {
        if (!is_array($vars)) {
            $vars = array();
        }

        $vars[] = 'booking_page_type';
        $vars[] = 'booking_package_id';
        $vars[] = 'booking_package_slug';
        $vars[] = 'account_page';
        $vars[] = 'school_action';

        return $vars;
    }


    /**
     * Include booking templates via template filter
     * Uses parent class template_loader
     */
    public function include_booking_template($template)
    {
        $page_type = get_query_var('booking_page_type');
        $package_id = get_query_var('booking_package_id');
        $account_page = get_query_var('account_page');

        if (!empty($page_type) && $page_type === 'book-now') {
            return $this->load_book_now_template($package_id, $template);
        }

        if (!empty($account_page)) {
            return $this->load_account_template($account_page, $template);
        }

        return $template;
    }


    /**
     * Load book-now template with validation
     */
    private function load_book_now_template($package_id, $template)
    {
        if (!is_user_logged_in()) {
            $this->store_redirect_and_login();
            return $template;
        }

        if (!empty($package_id)) {
            $package = get_post($package_id);
            if (!$package || $package->post_type !== 'packages' || $package->post_status !== 'publish') {
                wp_redirect(home_url('/packages/'));
                exit;
            }
            $_SESSION['booking_package_id'] = $package_id;
        } else {
            wp_redirect(home_url('/packages/'));
            exit;
        }

        // Use abstract module's template loading
        $this->get_template('public/page-book-now.php', array(
            'package_id' => $package_id,
            'package' => $package
        ));

        return false; // Prevent further template loading
    }


    /**
     * Load account template
     */
    private function load_account_template($account_page, $template)
    {
        if (!is_user_logged_in()) {
            $this->store_redirect_and_login();
            return $template;
        }

        if ($account_page === 'bookings') {
            // Use abstract module's template loading
            $this->get_template('public/page-bookings-list.php', array(
                'user_id' => get_current_user_id(),
                'blog_id' => get_current_blog_id()
            ));
            return false;
        }

        return $template;
    }


    // Template loading is now handled by the abstract module's methods


    /**
     * Store redirect URL for login flow
     */
    private function store_redirect_and_login()
    {
        $current_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http')
            . "://{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}";

        $_SESSION['redirect_url'] = $current_url;

        $session_id = session_id();
        if ($session_id) {
            set_transient('redirect_' . $session_id, $current_url, 15 * MINUTE_IN_SECONDS);
        }

        wp_redirect(home_url('/login/'));
        exit;
    }


    // ========================================
    // ASSET ENQUEUING
    // ========================================

    /**
     * Main asset enqueuing method
     */
    public function enqueue_assets()
    {
        if (!wp_script_is('jquery', 'enqueued')) {
            wp_enqueue_script('jquery');
        }

        $this->enqueue_common_styles();
        $this->enqueue_common_scripts();

        $page_type = get_query_var('booking_page_type');
        if ($page_type === 'book-now') {
            $this->enqueue_book_now_assets();
        }
    }


    /**
     * Enqueue common styles
     */
    private function enqueue_common_styles()
    {
        $plugin_url = plugin_dir_url(dirname(__FILE__));

        wp_enqueue_style(
            'bookings-public-main',
            $plugin_url . 'assets/css/bookings-public.css',
            array(),
            $this->version,
            'all'
        );

        wp_enqueue_style(
            'bookings-sw',
            $plugin_url . 'assets/css/sw.css',
            array(),
            $this->version,
            'all'
        );

        wp_enqueue_style(
            'bookings-page',
            $plugin_url . 'assets/css/page.css',
            array('bookings-public-main'),
            $this->version,
            'all'
        );

        wp_enqueue_style(
            'bookings-extra',
            $plugin_url . 'assets/css/extra.css',
            array('bookings-public-main'),
            $this->version,
            'all'
        );
    }


    /**
     * Enqueue common scripts
     */
    private function enqueue_common_scripts()
    {
        $plugin_url = plugin_dir_url(dirname(__FILE__));

        // wp_enqueue_script(
        //     'bookings-public',
        //     $plugin_url . 'assets/js/oltwh-multisite-user-sync-public.js',
        //     array('jquery'),
        //     $this->version,
        //     true
        // );

        wp_localize_script('bookings-public', 'mus_params', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('bookings_nonce'),
            'homeurl' => home_url(),
            'blogid' => get_current_blog_id(),
            'ismultisite' => is_multisite()
        ));
    }


    /**
     * Enqueue book-now specific assets
     */
    private function enqueue_book_now_assets()
    {
        $plugin_url = plugin_dir_url(dirname(__FILE__));

        // SmartWizard library
        wp_enqueue_style(
            'smartwizard',
            'https://cdn.jsdelivr.net/npm/smartwizard@6/dist/css/smart_wizard_all.min.css',
            array(),
            '6.0.0',
            'all'
        );

        wp_enqueue_script(
            'smartwizard',
            'https://cdn.jsdelivr.net/npm/smartwizard@6/dist/js/jquery.smartWizard.min.js',
            array('jquery'),
            '6.0.0',
            true
        );

        // Booking main scripts
        wp_enqueue_script(
            'booking-main',
            $plugin_url . 'assets/js/booking-main.js',
            array('jquery', 'smartwizard'),
            $this->version,
            true
        );

        wp_enqueue_script(
            'booking-location-handler',
            $plugin_url . 'assets/js/booking-location-handler.js',
            array('jquery', 'booking-main'),
            $this->version,
            true
        );

        // Package data localization
        $package_data = $this->get_current_package_data();

        wp_localize_script('booking-main', 'bookingServerConfig', array(
            'packageId' => $package_data['package_id'] ?? 0,
            'packageTitle' => $package_data['package_title'] ?? '',
            'packageSlug' => $package_data['package_slug'] ?? '',
            'totalSteps' => $package_data['total_steps'] ?? 5,
            'schoolRequired' => $package_data['school_required'] ?? false,
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('bookings_nonce'),
            'homeurl' => home_url()
        ));

        // Combined data localization
        $user_id = get_current_user_id();
        $blog_id = get_current_blog_id();

        $combined_data = array(
            'schools' => $this->get_user_schools_data_from_table($user_id, $blog_id),
            'locations' => $this->get_package_locations_data($package_data['package_id']),
            'parks' => $this->get_all_parks_data(),
            'user' => array(
                'id' => $user_id,
                'name' => wp_get_current_user()->display_name,
                'email' => wp_get_current_user()->user_email
            ),
        );

        wp_localize_script('booking-main', 'bookingData', $combined_data);
    }


    // ========================================
    // DATA RETRIEVAL METHODS (WITH CACHING)
    // ========================================

    /**
     * Get user schools from database with caching
     */
    private function get_user_schools_data_from_table($user_id, $blog_id)
    {
        $cache_key = $this->cache_prefix . 'schools_' . $blog_id . '_' . $user_id;
        $cached = wp_cache_get($cache_key);

        if ($cached !== false) {
            return $cached;
        }

        $schools = $this->crud::get_user_schools($user_id, $blog_id);

        if (empty($schools)) {
            wp_cache_set($cache_key, array(), '', 5 * MINUTE_IN_SECONDS);
            return array();
        }

        $formatted = array();
        foreach ($schools as $school) {
            $formatted[] = array(
                'id' => $school['id'],
                'school_name' => $school['school_name'],
                'school_address' => $school['school_address'],
                'school_city' => $school['school_city'],
                'school_state' => $school['school_state'],
                'school_zip' => $school['school_zip'],
                'school_phone' => $school['school_phone'],
                'director_email' => $school['director_email'],
                'director_first_name' => $school['director_first_name'],
                'director_last_name' => $school['director_last_name'],
            );
        }

        wp_cache_set($cache_key, $formatted, '', 5 * MINUTE_IN_SECONDS);
        return $formatted;
    }


    /**
     * Get package locations with caching
     */
    private function get_package_locations_data($package_id)
    {
        if (!$package_id) {
            return array();
        }

        $cache_key = $this->cache_prefix . 'locations_' . $package_id;
        $cached = wp_cache_get($cache_key);

        if ($cached !== false) {
            return $cached;
        }

        $locations = wp_get_post_terms($package_id, 'location', array('fields' => 'all'));

        if (is_wp_error($locations)) {
            wp_cache_set($cache_key, array(), '', 1 * HOUR_IN_SECONDS);
            return array();
        }

        $locations_data = array();
        foreach ($locations as $location) {
            $locations_data[] = array(
                'id' => $location->term_id,
                'name' => $location->name,
                'slug' => $location->slug,
                'description' => $location->description
            );
        }

        wp_cache_set($cache_key, $locations_data, '', 1 * HOUR_IN_SECONDS);
        return $locations_data;
    }


    /**
     * Get all parks with caching
     */
    private function get_all_parks_data()
    {
        $cache_key = $this->cache_prefix . 'parks_all';
        $cached = wp_cache_get($cache_key);

        if ($cached !== false) {
            return $cached;
        }

        $parks = get_terms(array(
            'taxonomy' => 'parks',
            'hide_empty' => false,
        ));

        if (is_wp_error($parks)) {
            wp_cache_set($cache_key, array(), '', 1 * HOUR_IN_SECONDS);
            return array();
        }

        $parks_data = array();
        $excluded_parks = array('medieval-times-dinner-tournament', 'pirates-dinner-adventure');

        foreach ($parks as $park) {
            if (in_array($park->slug, $excluded_parks)) {
                continue;
            }

            $meal_options = get_term_meta($park->term_id, 'park_meal_options', true);
            if (empty($meal_options) || !is_array($meal_options)) {
                $meal_options = array(
                    '$20 + 10% handling fee' => '20',
                    '$15 + 10% handling fee' => '15',
                    'Other - please contact Forum with amount +10% handling fee' => 'other'
                );
            }

            $parks_data[] = array(
                'id' => $park->term_id,
                'name' => $park->name,
                'slug' => $park->slug,
                'options' => $meal_options
            );
        }

        wp_cache_set($cache_key, $parks_data, '', 1 * HOUR_IN_SECONDS);
        return $parks_data;
    }


    /**
     * Get current package data
     */
    private function get_current_package_data()
    {
        $data = array(
            'package_id' => 0,
            'package_title' => '',
            'package_slug' => '',
            'total_steps' => 5,
            'school_required' => false
        );

        $package_id = get_query_var('booking_package_id', 0);

        if (!$package_id) {
            return $data;
        }

        $package = get_post($package_id);

        if (!$package || $package->post_type !== 'packages') {
            return $data;
        }

        $school_required_meta = get_post_meta($package_id, '_school_package', true);
        $total_steps_meta = get_post_meta($package_id, '_total_steps', true);

        return array(
            'package_id' => $package_id,
            'package_title' => $package->post_title,
            'package_slug' => $package->post_name,
            'school_required' => ($school_required_meta === 'true' || $school_required_meta === '1'),
            'total_steps' => $total_steps_meta ? intval($total_steps_meta) : 5
        );
    }
}
