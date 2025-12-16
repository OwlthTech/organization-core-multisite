<?php

/**
 * User Sync Manager
 * Orchestrates user synchronization across multisite network
 * 
 * @package OrganizationCore
 * @subpackage Sync
 */

class User_Sync_Manager
{

    private $sync_handler;
    private $meta_sync;

    /**
     * Roles that should NEVER be changed during sync
     * These are protected for security reasons
     * 
     * @var array
     */
    private $protected_roles = ['administrator', 'editor']; // Will also check super admin separately

    public function __construct()
    {
        $this->sync_handler = new User_Sync_Handler();
        $this->meta_sync = new User_Meta_Sync();
    }

    /**
     * ✅ NEW: Check if user should be protected from role changes
     * 
     * @param int $user_id User ID
     * @return bool True if user should be protected
     */
    private function is_protected_user($user_id)
    {
        // ✅ CRITICAL: Protect Super Admins
        if (is_super_admin($user_id)) {
            return true;
        }

        // ✅ Check if user has protected role on ANY site
        $user = new WP_User($user_id);

        foreach ($this->protected_roles as $protected_role) {
            if (in_array($protected_role, $user->roles)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Sync user across network on registration
     * Triggered by 'user_register' hook
     * 
     * @param int $user_id Newly created user ID
     * @return array Sync results
     */
    public function sync_new_user($user_id)
    {
        // ✅ PROTECTION: Check if user is protected
        if ($this->is_protected_user($user_id)) {
            return [
                'status' => 'skipped',
                'message' => 'User has protected role (Super Admin/Administrator)'
            ];
        }

        // Check if auto-sync is enabled
        $auto_sync_enabled = get_site_option('mus_auto_sync_enabled', 0);

        if (!$auto_sync_enabled) {
            return ['status' => 'disabled', 'message' => 'Auto-sync is disabled'];
        }

        // Get user data
        $user = get_userdata($user_id);

        if (!$user) {
            return ['status' => 'error', 'message' => 'User not found'];
        }

        $user_data = [
            'user_login' => $user->user_login,
            'user_email' => $user->user_email,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'display_name' => $user->display_name
        ];

        return $this->sync_user_to_network($user_id, $user_data);
    }

    /**
     * Sync user to all enabled sites in network
     * 
     * @param int $user_id User ID to sync
     * @param array $user_data User data
     * @param array $target_sites Optional specific sites
     * @return array Sync results
     */
    public function sync_user_to_network($user_id, $user_data = [], $target_sites = [])
    {
        if (!is_multisite()) {
            return ['status' => 'error', 'message' => 'Not a multisite installation'];
        }

        // ✅ PROTECTION: Double-check protection
        if ($this->is_protected_user($user_id)) {
            return [
                'status' => 'skipped',
                'message' => 'User has protected role'
            ];
        }

        // Get target sites
        if (empty($target_sites)) {
            $target_sites = get_site_option('mus_sync_sites', []);
        }

        if (empty($target_sites)) {
            return ['status' => 'error', 'message' => 'No sync sites configured'];
        }

        $current_blog_id = get_current_blog_id();
        $default_role = get_site_option('mus_default_role', 'subscriber');

        $results = [];
        $success_count = 0;
        $fail_count = 0;

        // Update role on CURRENT site (where user registered)
        if (in_array($current_blog_id, $target_sites)) {

            $user = new WP_User($user_id);

            // Set the role from settings
            $user->set_role($default_role);

            $user = new WP_User($user_id); // Reload to verify

            $results[$current_blog_id] = [
                'success' => true,
                'site_id' => $current_blog_id,
                'role' => $default_role,
                'action' => 'role_updated'
            ];
            $success_count++;
        }

        // Then sync to OTHER sites
        foreach ($target_sites as $site_id) {
            // Skip current site (already handled above)
            if ($site_id == $current_blog_id) {
                continue;
            }

            // Sync to site
            $result = $this->sync_to_single_site($user_id, $site_id, $user_data, $default_role);
            $results[$site_id] = $result;

            if ($result['success']) {
                $success_count++;
            } else {
                $fail_count++;
            }
        }

        return [
            'status' => 'success',
            'synced_count' => $success_count,
            'failed_count' => $fail_count,
            'results' => $results
        ];
    }

    /**
     * Sync user to a single site
     * 
     * @param int $user_id User ID
     * @param int $site_id Target site ID
     * @param array $user_data User data
     * @param string $default_role Role to assign
     * @return array Result
     */
    private function sync_to_single_site($user_id, $site_id, $user_data, $default_role = null)
    {
        if ($default_role === null) {
            $default_role = get_site_option('mus_default_role', 'subscriber');
        }

        switch_to_blog($site_id);

        try {
            // Add user to site with the configured role
            $result = $this->sync_handler->add_user_to_site($user_id, $site_id, $default_role);

            if (!$result) {
                throw new Exception('Failed to add user to site');
            }

            // Sync meta data
            $this->meta_sync->sync_user_meta($user_id);

            // Verify role assignment
            $user = new WP_User($user_id);
            $actual_roles = $user->roles;

            restore_current_blog();

            return [
                'success' => true,
                'site_id' => $site_id,
                'role' => $default_role,
                'actual_roles' => $actual_roles
            ];
        } catch (Exception $e) {
            restore_current_blog();

            return [
                'success' => false,
                'site_id' => $site_id,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Sync existing user manually (for bulk operations)
     * ✅ UPDATED: Now protects Super Admins and Administrators
     * 
     * @param int $user_id User ID
     * @return array Result
     */
    public function sync_existing_user($user_id)
    {
        // ✅ PROTECTION: Check if user is protected
        if ($this->is_protected_user($user_id)) {
            return [
                'status' => 'skipped',
                'message' => 'User has protected role (Super Admin/Administrator)',
                'synced_count' => 0,
                'failed_count' => 0
            ];
        }

        $user = get_userdata($user_id);

        if (!$user) {
            return ['status' => 'error', 'message' => 'User not found'];
        }

        $user_data = [
            'user_login' => $user->user_login,
            'user_email' => $user->user_email,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'display_name' => $user->display_name
        ];

        return $this->sync_user_to_network($user_id, $user_data);
    }
}
