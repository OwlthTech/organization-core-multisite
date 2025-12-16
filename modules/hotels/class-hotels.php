<?php
class OC_Hotels_Module extends OC_Abstract_Module {
    
    public function __construct() {
        parent::__construct();
        $config = $this->get_config();
        $this->module_id = $config['id'];
        $this->module_name = $config['name'];
        $this->version = $config['version'];

        $this->load_dependencies();
    }

    private function load_dependencies() {
        require_once plugin_dir_path( __FILE__ ) . 'crud.php';
        require_once plugin_dir_path( __FILE__ ) . 'admin/class-hotels-admin.php';
        require_once plugin_dir_path( __FILE__ ) . 'activator.php';
        require_once plugin_dir_path( __FILE__ ) . 'deactivator.php';
    }

    public function init() {
        $validator = new OC_Module_Validator();
        
        if ( ! $validator->is_module_enabled_for_site( $this->get_module_id() ) ) {
            return;
        }

        // Initialize Admin
        if ( is_admin() ) {
            $admin = new OC_Hotels_Admin( $this->get_module_id(), $this->version, $this->template_loader );
            $admin->init();
        }
    }

    public function get_module_id() {
        return 'hotels';
    }

    public function get_config() {
        return require plugin_dir_path( __FILE__ ) . 'config.php';
    }
}

// Register module
add_action( 'organization_core_register_modules', function() {
    $config = require plugin_dir_path( __FILE__ ) . 'config.php';
    OC_Module_Registry::register_module( $config['id'], $config );
});

// Activation Hook
register_activation_hook( __FILE__, array( 'OC_Hotels_Activator', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'OC_Hotels_Deactivator', 'deactivate' ) );
