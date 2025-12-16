<?php

/**
 * User Meta Sync
 * Syncs user metadata across sites
 * 
 * @package OrganizationCore
 * @subpackage Sync
 */

class User_Meta_Sync
{

    /**
     * Meta keys to sync across network
     * 
     * @var array
     */
    private $sync_meta_keys = [
        'first_name',
        'last_name',
        'user_prefix',
        'phone_number',
        'cell_number',
        'best_time_contact',
        'user_organization'
    ];

    /**
     * Sync user meta from source site to current site
     * 
     * @param int $user_id User ID
     * @param int $source_site_id Source site ID (optional)
     * @return bool Success status
     */
    public function sync_user_meta($user_id, $source_site_id = null)
    {
        if (!$source_site_id) {
            $source_site_id = get_main_site_id();
        }

        $current_site_id = get_current_blog_id();

        // Switch to source site to get meta
        switch_to_blog($source_site_id);

        $meta_data = [];

        foreach ($this->sync_meta_keys as $meta_key) {
            $value = get_user_meta($user_id, $meta_key, true);

            if ($value !== '') {
                $meta_data[$meta_key] = $value;
            }
        }

        restore_current_blog();

        // Save meta on current site
        foreach ($meta_data as $key => $value) {
            update_user_meta($user_id, $key, $value);
        }

        return true;
    }

    /**
     * Add custom meta key to sync list
     * 
     * @param string $meta_key Meta key
     */
    public function add_sync_meta_key($meta_key)
    {
        if (!in_array($meta_key, $this->sync_meta_keys)) {
            $this->sync_meta_keys[] = $meta_key;
        }
    }

    /**
     * Get all sync meta keys
     * 
     * @return array
     */
    public function get_sync_meta_keys()
    {
        return $this->sync_meta_keys;
    }
}
