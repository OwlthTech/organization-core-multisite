<?php

/**
 * Access Control
 * Restricts admin access to administrators only
 * 
 * @package OrganizationCore
 * @subpackage Utilities
 */

class Access_Control
{

    /**
     * Roles that are blocked from wp-admin
     * 
     * @var array
     */
    private $blocked_roles = ['subscriber', 'customer', 'school_manager'];

    public function __construct()
    {
        add_action('admin_init', [$this, 'restrict_admin_access']);
        add_filter('show_admin_bar', [$this, 'hide_admin_bar'], 10, 1);
    }

    /**
     * Restrict wp-admin access to administrators only
     */
    public function restrict_admin_access()
    {
        // Allow for AJAX requests
        if (defined('DOING_AJAX') && DOING_AJAX) {
            return;
        }

        // Get current user
        $user = wp_get_current_user();

        if (!$user || !$user->exists()) {
            return;
        }

        // Allow administrators and network admins
        if (in_array('administrator', $user->roles) || is_super_admin()) {
            return;
        }

        // Check if user has blocked role
        $has_blocked_role = false;

        foreach ($this->blocked_roles as $role) {
            if (in_array($role, $user->roles)) {
                $has_blocked_role = true;
                break;
            }
        }

        // Redirect to frontend
        if ($has_blocked_role) {
            wp_redirect(home_url('/my-account/'));
            exit;
        }
    }

    /**
     * Hide admin bar for non-administrators
     * 
     * @param bool $show_admin_bar
     * @return bool
     */
    public function hide_admin_bar($show_admin_bar)
    {
        // Get current user
        $user = wp_get_current_user();

        if (!$user || !$user->exists()) {
            return $show_admin_bar;
        }

        // Show admin bar only for administrators
        if (in_array('administrator', $user->roles) || is_super_admin()) {
            return true;
        }

        return false;
    }

    /**
     * Add role to blocked list
     * 
     * @param string $role Role name
     */
    public function add_blocked_role($role)
    {
        if (!in_array($role, $this->blocked_roles)) {
            $this->blocked_roles[] = $role;
        }
    }

    /**
     * Check if user can access admin
     * 
     * @param int $user_id User ID
     * @return bool
     */
    public function can_access_admin($user_id = null)
    {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }

        $user = get_userdata($user_id);

        if (!$user) {
            return false;
        }

        // Allow administrators
        if (in_array('administrator', $user->roles) || is_super_admin($user_id)) {
            return true;
        }

        // Block all others
        return false;
    }
}
