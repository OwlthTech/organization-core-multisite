<?php

/**
 * Network Admin functionality - manages module settings across all sites
 */
class OC_Network_Admin
{

    private $plugin_name;
    private $version;
    private $notices = array();

    public function __construct($plugin_name, $version)
    {
        $this->plugin_name = $plugin_name;
        $this->version = $version;

        // require_once ORGANIZATION_CORE_PLUGIN_DIR . 'admin/class-network-user-sync-page.php';
        // $this->user_sync_page = new Network_User_Sync_Page();
    }

    /**
     * Add network admin menu
     */
    public function add_network_menu()
    {
        add_menu_page(
            __('Organization Core', 'organization-core'),
            __('Organization Core', 'organization-core'),
            'manage_network',
            'organization-core-network',
            array($this, 'render_dashboard_page'),
            'dashicons-admin-multisite',
            30
        );

        add_submenu_page(
            'organization-core-network',
            __('Dashboard', 'organization-core'),
            __('Dashboard', 'organization-core'),
            'manage_network',
            'organization-core-network',
            array($this, 'render_dashboard_page')
        );

        add_submenu_page(
            'organization-core-network',
            __('Module Management', 'organization-core'),
            __('Modules', 'organization-core'),
            'manage_network',
            'organization-core-modules',
            array($this, 'render_module_management_page')
        );
    }

    /**
     * Handle form submissions
     */
    public function handle_form_submissions()
    {
        // Handle module settings save
        if (
            isset($_POST['organization_core_save_modules']) &&
            check_admin_referer('organization_core_modules', 'organization_core_modules_nonce')
        ) {
            $this->save_module_settings();
        }
    }

    /**
     * Save module settings
     */
    private function save_module_settings()
    {
        if (!isset($_POST['module_scope'])) {
            return;
        }
        $saved_count = 0;

        foreach ($_POST['module_scope'] as $module_id => $scope) {
            $sites = array();

            if ($scope === 'selected_sites' && isset($_POST['module_sites'][$module_id])) {
                $sites = array_map('intval', $_POST['module_sites'][$module_id]);
            }

            $settings = array(
                'scope' => sanitize_text_field($scope),
                'sites' => $sites,
                'settings' => array()
            );

            if (OC_Network_Settings::update_module_settings($module_id, $settings)) {
                $saved_count++;
            }
        }

        $this->notices[] = array(
            'type' => 'success',
            'message' => sprintf(
                __('Successfully saved settings for %d modules.', 'organization-core'),
                $saved_count
            )
        );
    }

    /**
     * Show admin notices
     */
    public function show_admin_notices()
    {
        foreach ($this->notices as $notice) {
            printf(
                '<div class="notice notice-%s is-dismissible"><p>%s</p></div>',
                esc_attr($notice['type']),
                esc_html($notice['message'])
            );
        }
    }

    /**
     * Render dashboard page
     */
    public function render_dashboard_page()
    {
        require_once ORGANIZATION_CORE_PLUGIN_DIR . 'admin/partials/network-dashboard.php';
    }

    /**
     * Render module management page
     */
    public function render_module_management_page()
    {
        $modules = OC_Module_Registry::get_all_modules();
        $network_sites = OC_Network_Settings::get_all_network_sites();
        require_once ORGANIZATION_CORE_PLUGIN_DIR . 'admin/partials/module-management.php';
    }
}
