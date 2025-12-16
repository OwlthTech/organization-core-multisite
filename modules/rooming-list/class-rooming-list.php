<?php

/**
 * Rooming List Module - Main Class
 */

if (!defined('WPINC')) {
    die;
}

class OC_Rooming_List_Module extends OC_Abstract_Module
{
    private $admin;
    private $ajax;

    public function __construct()
    {
        parent::__construct();
        $config = $this->get_config();
        $this->module_id = $config['id'];
        $this->module_name = $config['name'];
        $this->version = $config['version'];
    }

    /**
     * Initialize module
     */
    public function init()
    {
        // Validate if module should run
        $validator = new OC_Module_Validator();

        if (!$validator->is_module_enabled_for_site($this->get_module_id())) {
            return;
        }

        if (!$validator->validate_dependencies($this->get_module_id(), get_current_blog_id())) {
            return;
        }

        // Load module components
        $this->load_dependencies();

        // Initialize components based on context
        if (is_admin()) {
            $this->init_admin();
        } else {
            $this->init_public();
        }

        // Load AJAX handlers
        if (defined('DOING_AJAX') && DOING_AJAX) {
            $this->init_ajax();
        }
    }

    /**
     * Load all module dependencies
     */
    private function load_dependencies()
    {
        $module_path = plugin_dir_path(__FILE__);

        // Core components
        require_once $module_path . 'crud.php';

        // Admin components
        if (is_admin()) {
            require_once $module_path . 'activator.php';
            require_once $module_path . 'admin/class-rooming-list-admin.php';
        } else {
            // Public components
            require_once $module_path . 'public/class-rooming-list-public.php';
        }
    }

    /**
     * Initialize admin-specific functionality
     */
    private function init_admin()
    {
        $this->admin = new OC_Rooming_List_Admin(
            $this->module_id,
            $this->version,
            $this->template_loader
        );
        $this->admin->init();
    }

    /**
     * Initialize public-specific functionality
     */
    private function init_public()
    {
        $public = new OC_Rooming_List_Public(
            $this->module_id,
            $this->version,
            $this->template_loader
        );
        $public->init();
    }

    /**
     * Initialize AJAX functionality
     */
    private function init_ajax()
    {
        require_once plugin_dir_path(__FILE__) . 'ajax.php';
        $this->ajax = new OC_Rooming_List_AJAX($this->module_id);
    }

    public function get_module_id()
    {
        return 'rooming-list';
    }

    public function get_config()
    {
        return require plugin_dir_path(__FILE__) . 'config.php';
    }
}

// Register module
add_action('organization_core_register_modules', function () {
    $config = require plugin_dir_path(__FILE__) . 'config.php';
    OC_Module_Registry::register_module($config['id'], $config);
});
