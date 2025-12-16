<?php

/**
 * User Sync Handler
 * Handles actual user creation/update on target sites
 * 
 * @package OrganizationCore
 * @subpackage Sync
 */

class User_Sync_Handler
{

    /**
     * Add user to a specific site with role
     * 
     * @param int $user_id User ID (network-wide)
     * @param int $site_id Target site ID
     * @param string $role Role to assign (optional, uses default if not provided)
     * @return bool Success status
     */
    public function add_user_to_site($user_id, $site_id, $role = null)
    {
        // Check if user exists in network
        $user = get_userdata($user_id);

        if (!$user) {
            return false;
        }

        // Get role from parameter or use default from settings
        if ($role === null) {
            $role = get_site_option('mus_default_role', 'subscriber');
        }

        // Check if user already exists on target site
        if ($this->user_exists_on_site($user_id, $site_id)) {

            // ✅ FIX: Update role even if user exists
            $user_obj = new WP_User($user_id);

            // Set the correct role
            $user_obj->set_role($role);

            return true;
        }

        // Add user to blog with specified role
        $result = add_user_to_blog($site_id, $user_id, $role);

        if (is_wp_error($result)) {
            return false;
        }

        return true;
    }

    /**
     * Check if user exists on a specific site
     * 
     * @param int $user_id User ID
     * @param int $site_id Site ID
     * @return bool
     */
    private function user_exists_on_site($user_id, $site_id)
    {
        return is_user_member_of_blog($user_id, $site_id);
    }

    /**
     * Remove user from site
     * 
     * @param int $user_id User ID
     * @param int $site_id Site ID
     * @return bool Success status
     */
    public function remove_user_from_site($user_id, $site_id)
    {
        if (!$this->user_exists_on_site($user_id, $site_id)) {
            return true; // Already removed
        }

        $result = remove_user_from_blog($user_id, $site_id);

        return !is_wp_error($result);
    }

    /**
     * Update user role on site
     * 
     * @param int $user_id User ID
     * @param int $site_id Site ID
     * @param string $role New role
     * @return bool Success status
     */
    public function update_user_role($user_id, $site_id, $role)
    {
        if (!$this->user_exists_on_site($user_id, $site_id)) {
            return false;
        }

        switch_to_blog($site_id);

        $user = new WP_User($user_id);
        $user->set_role($role);

        restore_current_blog();
        return true;
    }
}
