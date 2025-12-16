<?php

/**
 * Quotes Public Class
 * Handles all public-facing functionality
 * UPDATED: Proper asset enqueuing and CRUD loading
 */

if (!defined('WPINC')) {
    die;
}

class OC_Quotes_Public
{
    private $module_id;
    private $version;
    private $template_loader;
    private $crud;

    private $template_map = array(
        'request-a-quote' => 'public/page-request-a-quote.php',
        'quotes-list'     => 'public/page-quotes-list.php',
    );

    private $cache_prefix = 'quote_';

    public function __construct($module_id, $version, $template_loader)
    {
        $this->module_id = $module_id;
        $this->version = $version;
        $this->template_loader = $template_loader;

        // Load CRUD class
        require_once plugin_dir_path(__FILE__) . '../crud.php';
        $this->crud = 'OC_Quotes_CRUD';
    }

    /**
     * Initialize public hooks
     */
    public function init()
    {
        add_action('init', array($this, 'start_session'), 1);
        add_filter('query_vars', array($this, 'add_quote_query_vars'), 10, 1);
        add_filter('template_include', array($this, 'include_quote_template'), 99, 1);
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'), 10);
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
    public function add_quote_query_vars($vars)
    {
        if (!is_array($vars)) {
            $vars = array();
        }

        $vars[] = 'quote_page_type';
        $vars[] = 'destination_slug';
        $vars[] = 'account_page';

        return $vars;
    }

    /**
     * Include custom template based on query vars
     */
    public function include_quote_template($template)
    {
        $page_type = get_query_var('quote_page_type');
        $destination_slug = get_query_var('destination_slug');
        $account_page = get_query_var('account_page');

        // Handle /request-a-quote/ route
        if (!empty($page_type) && $page_type === 'request-a-quote') {
            return $this->load_request_quote_template($template, $destination_slug);
        }

        // Handle /my-account/quotes/ route
        if (!empty($account_page) && $account_page === 'quotes') {
            return $this->load_account_template($account_page, $template);
        }

        return $template;
    }

    /**
     * Load request-a-quote template
     */
    private function load_request_quote_template($template, $destination_slug = '')
    {
        $custom_template = $this->get_template_path('request-a-quote');
        if ($custom_template) {
            if (!empty($destination_slug)) {
                global $quote_destination_slug;
                $quote_destination_slug = sanitize_text_field($destination_slug);
            } else {
                error_log('Loading generic quote template');
            }

            return $custom_template;
        }

        return $template;
    }

    /**
     * Load account quotes list template
     */
    private function load_account_template($account_page, $template)
    {
        if (!is_user_logged_in()) {
            $this->store_redirect_and_login();
            return $template;
        }

        if ($account_page === 'quotes') {
            $custom_template = $this->get_template_path('quotes-list');
            if ($custom_template) {
                return $custom_template;
            }
        }

        return $template;
    }

    /**
     * Get template path
     */
    private function get_template_path($template_name)
    {
        if (!isset($this->template_map[$template_name])) {
            return null;
        }

        $relative_path = $this->template_map[$template_name];
        $full_path = plugin_dir_path(__FILE__) . '../templates/' . $relative_path;

        if (!file_exists($full_path)) {
            error_log('❌ Template not found: ' . $full_path);
            return null;
        }

        return $full_path;
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
     */
    public function enqueue_assets()
    {
        if (!wp_script_is('jquery', 'enqueued')) {
            wp_enqueue_script('jquery');
        }

        $page_type = get_query_var('quote_page_type');
        $account_page = get_query_var('account_page');

        // Load assets only on quote pages
        if ($page_type === 'request-a-quote' || $account_page === 'quotes') {
            $this->enqueue_quote_styles();
            $this->enqueue_quote_scripts();
        }
    }

    /**
     * Enqueue CSS file
     */
    private function enqueue_quote_styles()
    {
        $plugin_url = plugin_dir_url(dirname(__FILE__));

        wp_enqueue_style(
            'quotes-public-css',
            $plugin_url . 'assets/css/quotes-public.css',
            array(),
            $this->version,
            'all'
        );
    }

    /**
     * Enqueue JS file with localized data
     */
    private function enqueue_quote_scripts()
    {
        $plugin_url = plugin_dir_url(dirname(__FILE__));

        wp_enqueue_script(
            'quotes-public-js',
            $plugin_url . 'assets/js/quotes-public.js',
            array('jquery'),
            $this->version,
            true
        );

        // Localize script with configuration
        wp_localize_script('quotes-public-js', 'quotesPublicConfig', array(
            'ajaxUrl'     => admin_url('admin-ajax.php'),
            'nonce'       => wp_create_nonce('quotes_nonce'),
            'homeUrl'     => home_url(),
            'blogId'      => get_current_blog_id(),
            'isMultisite' => is_multisite(),
            'userId'      => get_current_user_id(),
        ));
    }
}
