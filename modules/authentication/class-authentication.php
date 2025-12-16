<?php

/**
 * Authentication Module
 */

if (! defined('WPINC')) {
    die;
}

class OC_Authentication_Module extends OC_Abstract_Module
{

    private $admin;
    private $public;
    private $ajax;

    public function __construct()
    {

        parent::__construct();

        $config            = $this->get_config();
        $this->module_id   = $config['id'];
        $this->module_name = $config['name'];
        $this->version     = $config['version'];
    }

    /**
     * Initialize module
     */
    public function init()
    {

        $validator = new OC_Module_Validator();

        if (! $validator->is_module_enabled_for_site($this->get_module_id())) {
            return;
        }

        if (! $validator->validate_dependencies($this->get_module_id(), get_current_blog_id())) {
            add_action('admin_notices', array($this, 'dependency_notice'));
            return;
        }

        // Load dependencies
        $this->load_dependencies();

        // Initialize based on context
        if (is_admin()) {
            $this->init_admin();
        }

        if (! is_admin()) {
            $this->init_public();
        }

        if (defined('DOING_AJAX') && DOING_AJAX) {
            $this->init_ajax();
        }
    }

    /**
     * Load dependencies
     */
    private function load_dependencies()
    {
        $module_path = plugin_dir_path(__FILE__);

        // Core files
        require_once $module_path . 'activator.php';
        require_once $module_path . 'crud.php';

        // ✅ Load SMTP configuration ONCE (works globally via hooks)
        if (file_exists($module_path . 'includes/class-authentication-email-smtp.php')) {
            require_once $module_path . 'includes/class-authentication-email-smtp.php';
        }

        // Public components
        if (!is_admin()) {
            $public_file = $module_path . 'public/class-authentication-public.php';
            if (file_exists($public_file)) {
                require_once $public_file;
            }
        }

        // AJAX handling
        if (defined('DOING_AJAX') && DOING_AJAX) {
            $ajax_file = $module_path . 'ajax.php';
            if (file_exists($ajax_file)) {
                require_once $ajax_file;
            }
        }
    }


    /**
     * Initialize admin
     */
    private function init_admin()
    {
        //error_log('AUTH MODULE: Admin interface (not implemented yet)');
    }

    /**
     * Initialize public
     */
    private function init_public()
    {

        if (! class_exists('OC_Authentication_Public')) {
            return;
        }

        $this->public = new OC_Authentication_Public(
            $this->module_id,
            $this->version,
            $this->template_loader
        );

        $this->public->init();
    }

    /**
     * Initialize AJAX
     */
    private function init_ajax()
    {
        if (! class_exists('OC_Authentication_Ajax')) {
            error_log('❌ AUTH MODULE: AJAX class NOT FOUND!');
            return;
        }

        $this->ajax = new OC_Authentication_Ajax($this->module_id);
        $this->ajax->init();
    }

    public function get_module_id()
    {
        return 'authentication';
    }

    public function get_config()
    {
        $config_file = plugin_dir_path(__FILE__) . 'config.php';

        if (! file_exists($config_file)) {
            error_log('❌ AUTH MODULE: CONFIG FILE NOT FOUND!');
        }

        return require $config_file;
    }

    public function dependency_notice()
    {
?>
        <div class="notice notice-error">
            <p><?php esc_html_e('The Authentication module requires other modules to be enabled.', 'organization-core'); ?></p>
        </div>
<?php
    }
}

// Register module
add_action(
    'organization_core_register_modules',
    function () {
        $config = require plugin_dir_path(__FILE__) . 'config.php';
        OC_Module_Registry::register_module($config['id'], $config);
    }
);
