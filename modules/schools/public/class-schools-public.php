<?php

/**
 * Schools Public Class
 * Handles all public-facing functionality
 * Following the Bookings module pattern - SIMPLE & CLEAN
 */

if (!defined('WPINC')) {
    die;
}

class OC_Schools_Public extends OC_Abstract_Module
{
    // These properties are defined in the abstract class
    protected $module_id;
    protected $version;
    protected $template_loader;

    // Module specific properties
    private $crud;
    private $cache_prefix = 'school_';

    public function __construct($module_id, $version, $template_loader)
    {
        $this->module_id = $module_id;
        $this->version = $version;
        $this->template_loader = $template_loader;

        require_once plugin_dir_path(__FILE__) . '../crud.php';
        $this->crud = 'OC_Schools_CRUD';
    }

    /**
     * Initialize public hooks
     */
    public function init()
    {
        add_action('init', array($this, 'start_session'), 1);
        add_filter('query_vars', array($this, 'add_school_query_vars'), 10, 1);
        add_filter('template_include', array($this, 'include_school_template'), 99, 1);
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'), 10);

        add_shortcode('schools_list', array($this, 'render_schools_list'));
        add_shortcode('add_school_form', array($this, 'render_add_school_form'));
    }

    /**
     * Start session for redirects
     */
    public function start_session()
    {
        if (!session_id()) {
            session_start();
        }
    }

    /**
     * Add custom query vars for routing
     */
    public function add_school_query_vars($vars)
    {
        if (!is_array($vars)) {
            $vars = array();
        }

        $vars[] = 'account_page';
        $vars[] = 'school_action';
        $vars[] = 'edit';

        return $vars;
    }

    /**
     * Include custom template based on query vars
     */
    public function include_school_template($template)
    {
        $account_page = get_query_var('account_page');
        $school_action = get_query_var('school_action');

        // Handle /my-account/schools/ route
        if (!empty($account_page) && $account_page === 'schools') {
            return $this->load_account_template($account_page, $template);
        }
        return $template;
    }

    /**
     * Load account schools template
     */
    private function load_account_template($account_page, $template)
    {
        if (!is_user_logged_in()) {
            $this->store_redirect_and_login();
            return $template;
        }

        if ($account_page === 'schools') {
            $school_action = get_query_var('school_action');

            if ($school_action === 'add') {
                // Load add school template with args
                $this->get_template('public/page-add-school.php', array(
                    'edit_id' => isset($_GET['edit']) ? sanitize_text_field($_GET['edit']) : '',
                    'back_url' => isset($_GET['ref']) ? esc_url_raw($_GET['ref']) : home_url('/my-account')
                ));
                return false;
            } else {
                // Load schools list template with args
                $this->get_template('public/page-schools-list.php', array(
                    'user_id' => get_current_user_id(),
                    'blog_id' => get_current_blog_id()
                ));
                return false;
            }
        }

        return $template;
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
            'id' => 'schools',
            'name' => 'Schools Module',
            'version' => $this->version
        );
    }

    /**
     * Store redirect URL and redirect to login
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

    /**
     * Enqueue assets - CSS and JS files
     * FIXED: Enqueue on ALL frontend pages (not just schools pages)
     * AJAX requests need the JS loaded globally
     */
    public function enqueue_assets()
    {
        if (!wp_script_is('jquery', 'enqueued')) {
            wp_enqueue_script('jquery');
        }

        // ALWAYS enqueue schools list JS for AJAX calls
        $this->enqueue_schools_list_scripts();

        // Get query variables for page-specific enqueuing
        $account_page = get_query_var('account_page');
        $school_action = get_query_var('school_action');

        // Only do page-specific enqueuing if we're on schools pages
        if ($account_page !== 'schools') {
            return;
        }

        // Enqueue common styles
        $this->enqueue_school_styles();

        // ================================================
        // DETECT PAGE & ENQUEUE APPROPRIATE JS
        // ================================================

        // 📋 LIST PAGE: /my-account/schools/
        if (empty($school_action)) {
            error_log('[Schools Public] LIST PAGE - schools-list.js already enqueued');
        }

        // ➕ ADD/EDIT PAGE: /my-account/schools/add/
        elseif ($school_action === 'add') {
            $this->enqueue_school_scripts();
        }

        // 📝 EDIT MODE: /my-account/schools/add/?edit=123
        elseif ($school_action === 'add' && isset($_GET['edit'])) {
            $this->enqueue_school_scripts();
        }
    }

    /**
     * Enqueue CSS file
     */
    private function enqueue_school_styles()
    {
        $plugin_url = plugin_dir_url(dirname(__FILE__));

        wp_enqueue_style(
            'schools-public-css',
            $plugin_url . 'assets/css/schools-public.css',
            array(),
            $this->version,
            'all'
        );
    }

    /**
     * Enqueue JS file with localized data
     */
    private function enqueue_school_scripts()
    {
        $plugin_url = plugin_dir_url(dirname(__FILE__));

        // Enqueue the external JS file
        wp_enqueue_script(
            'school-form-js',
            $plugin_url . 'assets/js/school-form.js',
            array('jquery'),
            $this->version,
            true
        );

        // ================================================
        // GET CURRENT PAGE DATA
        // ================================================

        $school_id = isset($_GET['edit']) ? sanitize_text_field($_GET['edit']) : '';
        $back_url = isset($_GET['ref']) ? esc_url_raw($_GET['ref']) : home_url('/my-account');

        // ================================================
        // GET SAVED SCHOOL DATA IF EDITING
        // ================================================

        $saved_state = '';
        $saved_country = '';

        if ($school_id) {
            $user_id = get_current_user_id();

            // USE THE GLOBAL HELPER FUNCTION FROM CRUD
            $school_data = get_user_school($user_id, $school_id);

            if ($school_data && is_array($school_data)) {
                $saved_state = $school_data['school_state'] ?? '';
                $saved_country = $school_data['school_country'] ?? '';
            }
        }

        // ================================================
        // LOCALIZE SCRIPT WITH PHP DATA
        // ================================================

        wp_localize_script('school-form-js', 'schoolFormData', array(
            'ajax_url'        => admin_url('admin-ajax.php'),
            'nonce'           => wp_create_nonce('mus_nonce'),
            'home_url'        => home_url(),
            'is_edit'         => $school_id ? '1' : '0',
            'school_id'       => $school_id,
            'back_url'        => $back_url,
            'saved_state'     => $saved_state,
            'saved_country'   => $saved_country,
            'user_id'         => get_current_user_id(),
            'blog_id'         => get_current_blog_id(),
        ));
    }

    /**
     * Enqueue schools list JS with localized data
     * FIXED: Always enqueue (for AJAX support)
     */
    private function enqueue_schools_list_scripts()
    {
        $plugin_url = plugin_dir_url(dirname(__FILE__));

        // Enqueue the external JS file
        wp_enqueue_script(
            'schools-list-js',
            $plugin_url . 'assets/js/schools-list.js',
            array('jquery'),
            $this->version,
            true
        );

        // Get current URL for back navigation
        $current_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http')
            . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

        // FIXED: Include user_id and blog_id for AJAX verification
        wp_localize_script('schools-list-js', 'schoolsListData', array(
            'ajax_url'     => admin_url('admin-ajax.php'),
            'nonce'        => wp_create_nonce('mus_nonce'),
            'home_url'     => home_url(),
            'current_url'  => $current_url,
            'user_id'      => get_current_user_id(),  // ← Now included
            'blog_id'      => get_current_blog_id(),  // ← Now included
        ));
    }

    /**
     * Render schools list shortcode
     */
    public function render_schools_list()
    {
        if (!is_user_logged_in()) {
            return '<p>' . __('Please login to view schools.', 'organization-core') . '</p>';
        }

        ob_start();
        $this->get_template('public/page-schools-list.php', array(
            'user_id' => get_current_user_id(),
            'blog_id' => get_current_blog_id()
        ));
        return ob_get_clean();
    }

    /**
     * Render add school form shortcode using abstract module's template loader
     */
    public function render_add_school_form()
    {
        if (!is_user_logged_in()) {
            return '<p>' . __('Please login to add schools.', 'organization-core') . '</p>';
        }

        ob_start();
        $this->get_template('public/page-add-school.php', array(
            'edit_id' => isset($_GET['edit']) ? sanitize_text_field($_GET['edit']) : '',
            'back_url' => isset($_GET['ref']) ? esc_url_raw($_GET['ref']) : home_url('/my-account')
        ));
        return ob_get_clean();
    }
}
