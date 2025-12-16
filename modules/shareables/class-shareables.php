<?php

/**
 * The core module class.
 *
 * @package    Organization_Core
 * @subpackage Organization_Core/modules/shareables
 */
class OC_Shareables_Module extends OC_Abstract_Module {

    /**
     * The admin controller.
     *
     * @var OC_Shareables_Admin
     */
    private $admin;

    /**
     * The public controller.
     *
     * @var OC_Shareables_Public
     */
    private $public;

    /**
     * The ajax controller.
     *
     * @var OC_Shareables_Ajax
     */
    private $ajax;

    /**
     * Initialize the module.
     */
    public function __construct() {
        parent::__construct();
    }

    /**
     * Initialize the module functionality.
     */
    public function init() {
        // Run validation to check if module should be enabled
        $validator = new OC_Module_Validator();
        if (!$validator->is_module_enabled_for_site($this->get_module_id())) {
            return;
        }

        $this->load_dependencies();

        if (is_admin()) {
            $this->init_admin();
        } else {
            $this->init_public();
        }

        if (defined('DOING_AJAX') && DOING_AJAX) {
            $this->init_ajax();
        }
    }

    /**
     * Load the required dependencies for this module.
     */
    private function load_dependencies() {
        $path = plugin_dir_path(__FILE__);

        // CRUD is always required
        require_once $path . 'crud.php';

        if (is_admin()) {
            require_once $path . 'activator.php';
            require_once $path . 'deactivator.php';
            require_once $path . 'admin/class-shareables-admin.php';
        }

        if (!is_admin() || (defined('DOING_AJAX') && DOING_AJAX)) {
            require_once $path . 'public/class-shareables-public.php';
        }

        if (defined('DOING_AJAX') && DOING_AJAX) {
            require_once $path . 'ajax.php';
        }
    }

    /**
     * Initialize the admin functionality.
     */
    private function init_admin() {
        $this->admin = new OC_Shareables_Admin($this->module_id, $this->version, $this->template_loader);
        $this->admin->init();
    }

    /**
     * Initialize the public functionality.
     */
    private function init_public() {
        $this->public = new OC_Shareables_Public($this->module_id, $this->version, $this->template_loader);
        $this->public->init();
    }

    /**
     * Initialize the ajax functionality.
     */
    private function init_ajax() {
        $this->ajax = new OC_Shareables_Ajax($this->module_id);
    }

    /**
     * Get the module ID.
     */
    public function get_module_id() {
        return 'shareables';
    }

    /**
     * Get the module configuration.
     */
    public function get_config() {
        return require plugin_dir_path(__FILE__) . 'config.php';
    }
}

/**
 * Register the module.
 */
add_action('organization_core_register_modules', function () {
    $config = require plugin_dir_path(__FILE__) . 'config.php';
    OC_Module_Registry::register_module($config['id'], $config);
});
