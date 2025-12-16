<?php
/**
 * Manages module loading and initialization
 */
class OC_Module_Manager
{

    private $loaded_modules = array();

    /**
     * Load modules that are enabled for current site
     */
    public function load_modules()
    {
        if (!is_multisite()) {
            return;
        }

        $current_site_id = get_current_blog_id();
        $enabled_modules = $this->get_enabled_modules_for_site($current_site_id);

        foreach ($enabled_modules as $module_id) {
            $this->load_module($module_id);
        }

        do_action('organization_core_modules_loaded', $this->loaded_modules);
    }


    /**
     * Initialize all loaded modules
     */
    public function init_modules()
    {
        foreach ($this->loaded_modules as $module_id => $module_instance) {
            if (method_exists($module_instance, 'init')) {
                $module_instance->init();
            }
        }

        do_action('organization_core_modules_initialized', $this->loaded_modules);
    }


    /**
     * Get modules enabled for specific site
     */
    private function get_enabled_modules_for_site($site_id)
    {
        $validator = new OC_Module_Validator();
        $all_modules = OC_Module_Registry::get_all_modules();
        $enabled = array();

        foreach ($all_modules as $module_id => $module_data) {
            if ($validator->is_module_enabled_for_site($module_id, $site_id)) {
                // Check dependencies
                if ($validator->validate_dependencies($module_id, $site_id)) {
                    $enabled[] = $module_id;
                }
            }
        }

        return $enabled;
    }

    /**
     * Load a single module
     */
    private function load_module($module_id)
    {

        $module = OC_Module_Registry::get_module($module_id);

        if (!$module || empty($module['class'])) {
            return false;
        }

        $module_file = ORGANIZATION_CORE_PLUGIN_DIR . 'modules/' . $module_id . '/' . $module['class'];

        if (file_exists($module_file)) {
            require_once $module_file;

            $class_name = $this->get_module_class_name($module_id);

            if (class_exists($class_name)) {
                $this->loaded_modules[$module_id] = new $class_name();
                do_action('organization_core_module_loaded', $module_id, $this->loaded_modules[$module_id]);
                return true;
            }
        }

        return false;
    }

    /**
     * Generate module class name from module ID
     * Example: 'bookings' => 'OC_Bookings_Module'
     */
    private function get_module_class_name($module_id)
    {
        $parts = explode('-', $module_id);
        $parts = array_map('ucfirst', $parts);
        return 'OC_' . implode('_', $parts) . '_Module';
    }

    /**
     * Get loaded modules
     */
    public function get_loaded_modules()
    {
        return $this->loaded_modules;
    }
}
