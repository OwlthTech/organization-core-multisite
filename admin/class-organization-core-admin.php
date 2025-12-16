<?php
/**
 * Admin-specific functionality
 */
class OC_Admin
{

    private $plugin_name;
    private $version;

    public function __construct($plugin_name, $version)
    {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        $screen_id = 'toplevel_page_organization-bookings';
    }

    /**
     * Enqueue admin styles
     */
    public function enqueue_styles($hook = '')
    {
        // Only load Tailwind utilities on our plugin admin pages to avoid affecting core/admin UI.
        // Hook examples: 'toplevel_page_organization-bookings', 'organization_page_*'
        if (empty($hook) && function_exists('get_current_screen')) {
            $screen = get_current_screen();
            $hook = $screen ? $screen->id : '';
        }

        $allowed_substrings = array(
            'organization-bookings',
            'organization-core', // general slug placeholder
            'organization_' // submenu pages pattern
        );

        $should_enqueue = false;
        foreach ($allowed_substrings as $needle) {
            if (false !== strpos($hook, $needle)) {
                $should_enqueue = true;
                break;
            }
        }

        if (!$should_enqueue) {
            return; // Bail: don't load our utility CSS globally.
        }

        wp_enqueue_style(
            $this->plugin_name,
            ORGANIZATION_CORE_PLUGIN_URL . 'admin/assets/css/organization-core-admin.css',
            array(),
            $this->version,
            'all'
        );
    }

    /**
     * Enqueue admin scripts
     */
    public function enqueue_scripts()
    {
        wp_enqueue_script(
            $this->plugin_name,
            ORGANIZATION_CORE_PLUGIN_URL . 'admin/assets/js/organization-core-admin.js',
            array('jquery'),
            $this->version
        );

        // Example: determine current page slug and current user's roles/capabilities
        $current_user = wp_get_current_user();
        $roles = (array) $current_user->roles;

        // Build an "actions" section — each action describes the AJAX action name,
        // a nonce, endpoint, method and any required params.
        $actions = array(
            'bulk_sync_all_users' => array(
                'action' => 'bulk_sync_all_users',
                'nonce' => wp_create_nonce('network_sync_nonce'),
                'url' => admin_url('admin-ajax.php'),
                'method' => 'POST',
                'requires' => array('nonce'), // declarative
            ),

            // Add other actions here...
            'another_action' => array(
                'action' => 'another_action',
                'nonce' => wp_create_nonce('another_action'),
                'url' => admin_url('admin-ajax.php'),
                'method' => 'POST',
                'requires' => array('nonce', 'some_other_field'),
            ),
        );

        // Role-level flags (so JS can easily check capability)
        $role_flags = array(
            'can_sync' => current_user_can('manage_options') || current_user_can('edit_users'),
            'is_admin' => current_user_can('manage_options'),
            // add more capability flags as needed
        );

        // Strings and messages (i18n)
        $strings = array(
            'confirm_sync' => __('Are you sure? This will sync all existing users. This may take several minutes.', 'organization-core'),
            'starting' => __('Starting...', 'organization-core'),
            'complete' => __('Complete!', 'organization-core'),
            'success_alert' => __('All users synced successfully!', 'organization-core'),
            'sync_error_prefix' => __('Sync error: ', 'organization-core'),
            'ajax_error' => __('AJAX error', 'organization-core'),
            'ajax_error_alert' => __('An error occurred during sync.', 'organization-core'),
        );

        // Page-level config (if you need multiple pages, pass keyed by page slug)
        $page = array(
            'slug' => 'organization_core_admin', // your page id
            // any other page-level flags or values
        );

        $config = array(
            'page' => $page,
            'roles' => $roles,
            'roleFlags' => $role_flags,
            'actions' => $actions,
            'strings' => $strings,
            // you can add additional schema keys such as 'schemaVersion' for future upgrades
            'schemaVersion' => '1.0',
        );

        // Localize the whole structured config
        wp_localize_script($this->plugin_name, 'organizationCore', $config);

    }

    /**
     * Render test module status page
     */
    public function render_test_module_status_page()
    {
        require_once ORGANIZATION_CORE_PLUGIN_DIR . 'test-module-status.php';
    }
}
