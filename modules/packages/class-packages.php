<?php

/**
 * Quotes Module - Main Orchestrator
 * This class loads all module components and coordinates them.
 */

if (!defined('WPINC')) {
    die;
}

class OC_Packages_Module extends OC_Abstract_Module
{
    protected $admin;
    protected $public;
    protected $ajax;

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

        
    }

    /**
     * Load all module dependencies
     */
    private function load_dependencies()
    {
        $module_path = plugin_dir_path(__FILE__);

        // Core components (always loaded)
        // require_once $module_path . 'logics.php';

    }
    

    public function get_module_id()
    {
        return 'packages';
    }

    public function get_config()
    {
        return require plugin_dir_path(__FILE__) . 'config.php';
    }

    public function dependency_notice()
    {
?>
        <div class="notice notice-error">
            <p><?php _e('Packages Module requires dependencies to be enabled.', 'organization-core'); ?></p>
        </div>
<?php
    }
}

// ✅ Register module
add_action('organization_core_register_modules', function () {
    $config = require plugin_dir_path(__FILE__) . 'config.php';
    OC_Module_Registry::register_module($config['id'], $config);
});
