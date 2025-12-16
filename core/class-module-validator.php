<?php

/**
 * Validates which sites have access to which modules
 */
class OC_Module_Validator
{

    /**
     * Check if module is enabled for specific site
     */
    public function is_module_enabled_for_site($module_id, $site_id = null)
    {
        if (is_null($site_id)) {
            $site_id = get_current_blog_id();
        }

        $settings = OC_Network_Settings::get_module_settings($module_id);

        if (!$settings) {
            return false;
        }

        $scope = isset($settings['scope']) ? $settings['scope'] : 'disabled';

        switch ($scope) {
            case 'all_sites':
                return true;

            case 'selected_sites':
                $sites = isset($settings['sites']) ? $settings['sites'] : array();
                return in_array((int) $site_id, array_map('intval', $sites));

            case 'disabled':
            default:
                return false;
        }
    }

    /**
     * Get module scope
     */
    public function get_module_scope($module_id)
    {
        $settings = OC_Network_Settings::get_module_settings($module_id);
        return isset($settings['scope']) ? $settings['scope'] : 'disabled';
    }

    /**
     * Get all sites where module is enabled
     */
    public function get_enabled_sites_for_module($module_id)
    {
        $settings = OC_Network_Settings::get_module_settings($module_id);

        if (isset($settings['scope']) && $settings['scope'] === 'all_sites') {
            return $this->get_all_site_ids();
        }

        return isset($settings['sites']) ? $settings['sites'] : array();
    }

    /**
     * Validate module dependencies
     */
    public function validate_dependencies($module_id, $site_id)
    {
        $dependencies = OC_Module_Registry::get_dependencies($module_id);

        if (empty($dependencies)) {
            return true;
        }
        foreach ($dependencies as $index => $module_id) {
            if (!$this->is_module_enabled_for_site($module_id, $site_id)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get all site IDs in network
     */
    private function get_all_site_ids()
    {
        $sites = get_sites(array('number' => 10000, 'fields' => 'ids'));
        return $sites;
    }
}
