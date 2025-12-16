<?php
/**
 * Central registry for all modules
 * Stores module metadata and configuration
 */
class OC_Module_Registry {
    
    private static $modules = array();

    /**
     * Register a new module
     */
    public static function register_module( $module_id, $args ) {
        $defaults = array(
            'id' => $module_id,
            'name' => '',
            'description' => '',
            'version' => '1.0.0',
            'author' => '',
            'default_enabled' => false,
            'network_only' => false,
            'required' => false,
            'dependencies' => array(),
            'supports' => array(),
            'template_paths' => array(),
            'assets' => array(),
            'class' => ''
        );

        self::$modules[ $module_id ] = wp_parse_args( $args, $defaults );
        
        do_action( 'organization_core_module_registered', $module_id, self::$modules[ $module_id ] );
        
        return true;
    }

    /**
     * Get specific module
     */
    public static function get_module( $module_id ) {
        return isset( self::$modules[ $module_id ] ) ? self::$modules[ $module_id ] : false;
    }

    /**
     * Get all registered modules
     */
    public static function get_all_modules() {
        return self::$modules;
    }

    /**
     * Check if module exists
     */
    public static function module_exists( $module_id ) {
        return isset( self::$modules[ $module_id ] );
    }

    /**
     * Get module dependencies
     */
    public static function get_dependencies( $module_id ) {
        $module = self::get_module( $module_id );
        return $module ? $module['dependencies'] : array();
    }
}
