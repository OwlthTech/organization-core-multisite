<?php

/**
 * Quotes Module Admin Handler
 * Class: OC_Quotes_Admin
 */

if (!defined('WPINC')) {
    die;
}

class OC_Quotes_Admin
{
    protected $module_id;
    protected $version;
    protected $template_loader;

    public function __construct($module_id, $version, $template_loader)
    {
        $this->module_id = $module_id;
        $this->version = $version;
        $this->template_loader = $template_loader;
    }

    /**
     * Initialize admin functionality
     */
    public function init()
    {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
    }

    /**
     * Add admin menu for quotes table
     */
    public function add_admin_menu()
    {
        add_menu_page(
            __('Quotes', 'organization-core'),
            __('Quotes', 'organization-core'),
            'manage_options',
            'quotes-table',
            array($this, 'render_quotes_table'),
            'dashicons-format-quote',
            25
        );
    }

    /**
     * Render quotes table page
     */
    public function render_quotes_table()
    {
        $this->template_loader->get_template(
            'class-quotes-table.php',
            array(),
            $this->module_id,
            plugin_dir_path(__FILE__) . '../templates/admin/'
        );
    }

    /**
     * Enqueue admin scripts
     */
    public function enqueue_scripts($hook)
    {
        if (strpos($hook, 'quotes') !== false) {
            wp_enqueue_style(
                'quotes-admin-css',
                plugin_dir_url(__FILE__) . '../assets/css/admin.css',
                array(),
                $this->version
            );

            wp_enqueue_script(
                'quotes-admin-js',
                plugin_dir_url(__FILE__) . '../assets/js/admin.js',
                array('jquery'),
                $this->version,
                true
            );

            wp_localize_script('quotes-admin-js', 'quotesAdmin', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('quotes_admin_nonce'),
            ));
        }
    }
}
