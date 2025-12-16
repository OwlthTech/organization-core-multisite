<?php
/**
 * Abstract base class that all modules must extend
 * Provides standardized structure and template loading
 */
abstract class OC_Abstract_Module {
    
    protected $module_id;
    protected $module_name;
    protected $version;
    protected $template_loader;

    /**
     * Initialize module (must be implemented by child)
     */
    abstract public function init();
    
    /**
     * Get module ID (must be implemented by child)
     */
    abstract public function get_module_id();
    
    /**
     * Get module configuration (must be implemented by child)
     */
    abstract public function get_config();

    /**
     * Constructor
     */
    public function __construct() {
        $this->template_loader = new OC_Template_Loader();
    }

    /**
     * Load a template with theme override support
     */
    protected function get_template( $template_name, $args = array(), $template_path = '', $default_path = '' ) {
        $this->template_loader->get_template( 
            $template_name, 
            $args, 
            $this->get_module_id(), 
            $default_path 
        );
    }

    /**
     * Load a template part
     */
    protected function get_template_part( $slug, $name = '', $args = array() ) {
        $this->template_loader->get_template_part( 
            $slug, 
            $name, 
            $this->get_module_id(), 
            $args 
        );
    }
}
