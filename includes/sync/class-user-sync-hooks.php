<?php

/**
 * User Sync Hooks
 * Integrates sync functionality with WordPress hooks
 * 
 * @package OrganizationCore
 * @subpackage Sync
 */

class User_Sync_Hooks
{

    private $sync_manager;

    public function __construct()
    {
        $this->sync_manager = new User_Sync_Manager();
        $this->register_hooks();
    }

    /**
     * Register WordPress hooks
     */
    public function register_hooks()
    {
        // ✅ FIXED: Sync on user registration (immediate, not async)
        add_action('user_register', [$this, 'on_user_register'], 10, 1);

        // Sync on profile update (optional)
        add_action('profile_update', [$this, 'on_profile_update'], 10, 2);

        // ✅ FIXED: Register the async scheduled event handler
        add_action('sync_new_user_async', [$this, 'handle_async_sync'], 10, 1);

        // ✅ FIXED: Correct AJAX action names
        add_action('wp_ajax_sync_single_user', [$this, 'ajax_sync_single_user']);
        add_action('wp_ajax_bulk_sync_users', [$this, 'ajax_bulk_sync_users']);
        add_action('wp_ajax_bulk_sync_all_users', [$this, 'ajax_bulk_sync_all_users']); // NEW: For sync all button
    }

    /**
     * Handle user registration
     * ✅ FIXED: Now syncs immediately instead of scheduling
     * 
     * @param int $user_id New user ID
     */
    public function on_user_register($user_id)
    {
        if (!is_multisite()) {
            return;
        }

        // ✅ FIXED: Sync immediately on registration
        $this->sync_manager->sync_new_user($user_id);
    }

    /**
     * ✅ NEW: Handle async scheduled sync (if you want to use wp_schedule_single_event)
     * 
     * @param int $user_id User ID to sync
     */
    public function handle_async_sync($user_id)
    {
        if (!is_multisite()) {
            return;
        }
        $this->sync_manager->sync_new_user($user_id);
    }

    /**
     * Handle profile update
     * 
     * @param int $user_id User ID
     * @param WP_User $old_user_data Old user data
     */
    public function on_profile_update($user_id, $old_user_data)
    {
        // Check if sync on update is enabled
        $sync_on_update = get_site_option('mus_sync_on_profile_update', 0);

        if (!$sync_on_update || !is_multisite()) {
            return;
        }

        // Sync profile changes across network
        $this->sync_manager->sync_existing_user($user_id);
    }

    /**
     * AJAX: Sync single user manually
     */
    public function ajax_sync_single_user()
    {
        check_ajax_referer('network_sync_nonce', 'nonce');

        if (!current_user_can('manage_network_users')) {
            wp_send_json_error(['message' => __('Insufficient permissions', 'organization-core')]);
        }

        $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;

        if (!$user_id) {
            wp_send_json_error(['message' => __('Invalid user ID', 'organization-core')]);
        }

        $result = $this->sync_manager->sync_existing_user($user_id);

        if ($result['status'] === 'success') {
            wp_send_json_success([
                'message' => sprintf(__('User #%d synced to %d sites', 'organization-core'), $user_id, $result['synced_count']),
                'synced_count' => $result['synced_count'],
                'failed_count' => $result['failed_count']
            ]);
        } else {
            wp_send_json_error(['message' => $result['message']]);
        }
    }

    /**
     * AJAX: Bulk sync selected users
     */
    public function ajax_bulk_sync_users()
    {
        check_ajax_referer('network_sync_nonce', 'nonce');

        if (!current_user_can('manage_network_users')) {
            wp_send_json_error(['message' => __('Insufficient permissions', 'organization-core')]);
        }

        $user_ids = isset($_POST['user_ids']) ? array_map('intval', $_POST['user_ids']) : [];

        if (empty($user_ids)) {
            wp_send_json_error(['message' => __('No users selected', 'organization-core')]);
        }

        $results = [];
        $total_synced = 0;
        $total_failed = 0;

        foreach ($user_ids as $user_id) {
            $result = $this->sync_manager->sync_existing_user($user_id);
            $results[$user_id] = $result;

            if ($result['status'] === 'success') {
                $total_synced += $result['synced_count'];
                $total_failed += $result['failed_count'];
            }
        }

        wp_send_json_success([
            'message' => sprintf(
                __('Processed %d users: %d synced, %d failed', 'organization-core'),
                count($user_ids),
                $total_synced,
                $total_failed
            ),
            'results' => $results
        ]);
    }

    /**
     * ✅ NEW: AJAX: Bulk sync ALL network users
     * This is called from the "Sync All Users" button
     */
    public function ajax_bulk_sync_all_users()
    {
        check_ajax_referer('network_sync_nonce', 'nonce');

        if (!current_user_can('manage_network_users')) {
            wp_send_json_error(['message' => __('Insufficient permissions', 'organization-core')]);
        }

        // ✅ Get ALL users from the network (not site-specific)
        $users = get_users([
            'blog_id' => 0,        // 0 = get users from entire network
            'number' => -1,         // -1 = no limit, get all users
            'fields' => 'ID'        // Only get user IDs for performance
        ]);

        if (empty($users)) {
            wp_send_json_error(['message' => __('No users found in network', 'organization-core')]);
        }

        $total_users = count($users);
        $total_synced = 0;
        $total_failed = 0;
        $results = [];

        // Sync each user
        foreach ($users as $user_id) {
            $result = $this->sync_manager->sync_existing_user($user_id);

            if ($result['status'] === 'success') {
                $total_synced += $result['synced_count'];
                $total_failed += $result['failed_count'];
                $results[] = [
                    'user_id' => $user_id,
                    'status' => 'success',
                    'sites' => $result['synced_count']
                ];
            } else {
                $total_failed++;
                $results[] = [
                    'user_id' => $user_id,
                    'status' => 'failed',
                    'error' => $result['message']
                ];
            }
        }

        wp_send_json_success([
            'message' => sprintf(
                __('Synced %d users to sites successfully (%d operations failed)', 'organization-core'),
                $total_synced,
                $total_failed
            ),
            'total_users' => $total_users,
            'total_synced' => $total_synced,
            'total_failed' => $total_failed,
            'results' => $results
        ]);
    }
}
