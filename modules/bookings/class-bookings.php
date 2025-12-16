<?php

/**
 * Bookings Module - Main Orchestrator
 * This class loads all module components and coordinates them
 */

if (!defined('WPINC')) {
    die;
}

class OC_Bookings_Module extends OC_Abstract_Module
{
    private $admin;
    private $public;
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
     * Initialize module - Load all components
     */
    public function init()
    {
        // Validate if module should run
        $validator = new OC_Module_Validator();

        if (!$validator->is_module_enabled_for_site($this->get_module_id())) {
            return;
        }

        if (!$validator->validate_dependencies($this->get_module_id(), get_current_blog_id())) {
            add_action('admin_notices', array($this, 'dependency_notice'));
            return;
        }

        // Load module components
        $this->load_dependencies();

        // ✅ Initialize email handler EARLY (before AJAX)
        $this->init_email_handler();

        // Initialize components based on context
        if (is_admin()) {
            $this->init_admin();
        }

        if (!is_admin()) {
            $this->init_public();
        }

        // Load AJAX handlers (works for both admin and public)
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

        // Core components (always loaded)
        require_once $module_path . 'crud.php';
        require_once $module_path . 'api.php';
        
        // ✅ ALWAYS load email handler (needed for hooks)
        require_once $module_path . 'class-bookings-email.php';
        
        // ✅ Load cron handler (for scheduled tasks)
        require_once $module_path . 'class-bookings-cron.php';
        
        // Admin components
        if (is_admin()) {
            require_once $module_path . 'activator.php';
            require_once $module_path . 'deactivator.php';
            require_once $module_path . 'metaboxes.php';
            require_once $module_path . 'admin/class-bookings-admin.php';
        }

        // Public components
        if (!is_admin()) {
            require_once $module_path . 'public/class-bookings-public.php';
        }

        // AJAX handlers
        if (defined('DOING_AJAX') && DOING_AJAX) {
            require_once $module_path . 'ajax.php';
        }
    }

    /**
     * ✅ NEW: Initialize email handler and register hooks
     */
    private function init_email_handler()
    {
        if (class_exists('OC_Bookings_Email')) {
            OC_Bookings_Email::init();
        } else {
            error_log('[BOOKINGS MODULE] ❌ Email handler class not found!');
        }
    }

    /**
     * Initialize admin-specific functionality
     */
    private function init_admin()
    {
        // Initialize admin interface
        $this->admin = new OC_Bookings_Admin(
            $this->module_id,
            $this->version,
            $this->template_loader
        );
        $this->admin->init();
    }

    /**
     * Initialize public-facing functionality
     */
    private function init_public()
    {
        // Initialize public interface
        $this->public = new OC_Bookings_Public(
            $this->module_id,
            $this->version,
            $this->template_loader
        );
        $this->public->init();
    }

    /**
     * Initialize AJAX handlers
     */
    private function init_ajax()
    {
        $this->ajax = new OC_Bookings_Ajax(
            $this->module_id
        );
    }

    public function get_module_id()
    {
        return 'bookings';
    }

    public function get_config()
    {
        return require plugin_dir_path(__FILE__) . 'config.php';
    }

    public function dependency_notice()
    {
?>
        <div class="notice notice-error">
            <p><?php _e('The Bookings module requires other modules to be enabled.', 'organization-core'); ?></p>
        </div>
<?php
    }
}

// Register module
add_action('organization_core_register_modules', function () {
    $config = require plugin_dir_path(__FILE__) . 'config.php';
    OC_Module_Registry::register_module($config['id'], $config);
});
