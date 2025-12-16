<?php

/**
 * Network User Sync Settings Page
 * Network admin interface for user sync configuration
 * 
 * @package OrganizationCore
 * @subpackage Admin\Network
 */

class Network_User_Sync_Page
{

    private $page_hook;
    private $option_group = 'organization_network_sync_settings';

    public function __construct()
    {
        // Register hooks
        add_action('network_admin_menu', [$this, 'add_network_menu']);

        // ✅ FIXED: Use network admin hook (not regular admin_post)
        add_action('network_admin_init', [$this, 'register_settings']);

        add_action('admin_post_save_network_sync_settings', [$this, 'save_settings_handler']);

    }

    /**
     * Add network admin menu
     */
    public function add_network_menu()
    {
        // Add submenu under Settings
        $this->page_hook = add_submenu_page(
            'settings.php',
            __('User Sync Settings', 'organization-core'),
            __('User Sync', 'organization-core'),
            'manage_network_options',
            'user-sync-settings',
            [$this, 'render_settings_page']
        );
    }

    /**
     * Register settings for sanitization
     * ✅ Use network_admin_init hook
     */
    public function register_settings()
    {
        // Register settings for sanitization (not storing here - done in save_settings_handler)
        register_setting(
            $this->option_group,
            'mus_auto_sync_enabled',
            [
                'type' => 'boolean',
                'sanitize_callback' => [$this, 'sanitize_checkbox'],
                'show_in_rest' => false,
            ]
        );

        register_setting(
            $this->option_group,
            'mus_sync_sites',
            [
                'type' => 'array',
                'sanitize_callback' => [$this, 'sanitize_sync_sites'],
                'show_in_rest' => false,
            ]
        );

        register_setting(
            $this->option_group,
            'mus_default_role',
            [
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'show_in_rest' => false,
            ]
        );

        register_setting(
            $this->option_group,
            'mus_sync_on_profile_update',
            [
                'type' => 'boolean',
                'sanitize_callback' => [$this, 'sanitize_checkbox'],
                'show_in_rest' => false,
            ]
        );
    }

    /**
     * Sanitize checkbox value
     */
    public function sanitize_checkbox($input)
    {
        return $input ? 1 : 0;
    }

    /**
     * Sanitize sync sites array
     */
    public function sanitize_sync_sites($input)
    {
        if (!is_array($input)) {
            return [];
        }

        return array_map('intval', $input);
    }

    /**
     * Render settings page - MAIN PAGE DISPLAY
     * ✅ FIXED: Proper form action and nonce
     */
    public function render_settings_page()
    {
        // Check permissions
        if (!current_user_can('manage_network_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'organization-core'));
        }

        // Get current settings
        $auto_sync_enabled = get_site_option('mus_auto_sync_enabled', 0);
        $sync_sites = get_site_option('mus_sync_sites', []);
        $default_role = get_site_option('mus_default_role', 'subscriber');
        $sync_on_update = get_site_option('mus_sync_on_profile_update', 0);

        // Get all sites for checkboxes
        $sites = get_sites(['number' => 100]);
        $current_site_id = get_current_blog_id();

        // Get any success/error messages
        $settings_updated = isset($_GET['settings-updated']) && $_GET['settings-updated'] === 'true';
        $sync_error = isset($_GET['sync-error']) ? sanitize_text_field($_GET['sync-error']) : '';

?>
        <div class="wrap">
            <h1><?php _e('User Synchronization Settings', 'organization-core'); ?></h1>

            <!-- Success Message -->
            <?php if ($settings_updated): ?>
                <div class="notice notice-success is-dismissible">
                    <p><?php _e('Settings saved successfully!', 'organization-core'); ?></p>
                    <button type="button" class="notice-dismiss">
                        <span class="screen-reader-text"><?php _e('Dismiss this notice.', 'organization-core'); ?></span>
                    </button>
                </div>
            <?php endif; ?>

            <!-- Error Message -->
            <?php if ($sync_error): ?>
                <div class="notice notice-error is-dismissible">
                    <p><?php echo wp_kses_post($sync_error); ?></p>
                    <button type="button" class="notice-dismiss">
                        <span class="screen-reader-text"><?php _e('Dismiss this notice.', 'organization-core'); ?></span>
                    </button>
                </div>
            <?php endif; ?>

            <!-- Settings Form -->
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="user-sync-settings-form">
                <!-- ✅ FIXED: Nonce field inside form -->
                <?php wp_nonce_field('mus_save_network_sync_settings', 'mus_sync_nonce'); ?>

                <!-- ✅ FIXED: Hidden action field -->
                <input type="hidden" name="action" value="save_network_sync_settings">

                <table class="form-table">
                    <!-- Auto-Sync Toggle -->
                    <tr>
                        <th scope="row">
                            <label for="mus_auto_sync_enabled">
                                <?php _e('Enable Auto-Sync', 'organization-core'); ?>
                            </label>
                        </th>
                        <td>
                            <fieldset>
                                <legend class="screen-reader-text">
                                    <span><?php _e('Enable automatic user synchronization', 'organization-core'); ?></span>
                                </legend>
                                <label for="mus_auto_sync_enabled">
                                    <input type="checkbox"
                                        id="mus_auto_sync_enabled"
                                        name="mus_auto_sync_enabled"
                                        value="1"
                                        <?php checked($auto_sync_enabled, 1); ?>>
                                    <?php _e('Automatically sync new users to selected sites', 'organization-core'); ?>
                                </label>
                                <p class="description">
                                    <?php _e('When enabled, newly registered users will be automatically added to all selected sites below.', 'organization-core'); ?>
                                </p>
                            </fieldset>
                        </td>
                    </tr>

                    <!-- Sync Sites Selection -->
                    <tr>
                        <th scope="row">
                            <label><?php _e('Sync to Sites', 'organization-core'); ?></label>
                        </th>
                        <td>
                            <fieldset>
                                <legend class="screen-reader-text">
                                    <span><?php _e('Select sites for user synchronization', 'organization-core'); ?></span>
                                </legend>

                                <?php if (empty($sites)): ?>
                                    <p><?php _e('No other sites available for syncing.', 'organization-core'); ?></p>
                                <?php else: ?>
                                    <?php foreach ($sites as $site): ?>
                                        <?php
                                        // Skip current site
                                        if ($site->blog_id == $current_site_id) {
                                            continue;
                                        }

                                        $site_details = get_blog_details($site->blog_id);
                                        $site_name = isset($site_details->blogname) ? $site_details->blogname : 'Unknown Site';
                                        $site_url = isset($site_details->siteurl) ? $site_details->siteurl : '';
                                        $is_checked = in_array($site->blog_id, (array) $sync_sites);
                                        ?>
                                        <label style="display: block; margin-bottom: 8px;">
                                            <input type="checkbox"
                                                name="mus_sync_sites[]"
                                                value="<?php echo esc_attr($site->blog_id); ?>"
                                                <?php checked($is_checked); ?>>
                                            <strong><?php echo esc_html($site_name); ?></strong>
                                            <?php if ($site_url): ?>
                                                <span class="description">(<?php echo esc_url($site_url); ?>)</span>
                                            <?php endif; ?>
                                        </label>
                                    <?php endforeach; ?>
                                <?php endif; ?>

                                <p class="description" style="margin-top: 10px;">
                                    <?php _e('Select which sites new users should be automatically added to.', 'organization-core'); ?>
                                </p>
                            </fieldset>
                        </td>
                    </tr>

                    <!-- Default Role -->
                    <tr>
                        <th scope="row">
                            <label for="mus_default_role">
                                <?php _e('Default Role for Synced Users', 'organization-core'); ?>
                            </label>
                        </th>
                        <td>
                            <select name="mus_default_role" id="mus_default_role" class="regular-text">
                                <option value="subscriber" <?php selected($default_role, 'subscriber'); ?>>
                                    <?php _e('Subscriber', 'organization-core'); ?>
                                </option>
                                <option value="contributor" <?php selected($default_role, 'contributor'); ?>>
                                    <?php _e('Contributor', 'organization-core'); ?>
                                </option>
                                <option value="author" <?php selected($default_role, 'author'); ?>>
                                    <?php _e('Author', 'organization-core'); ?>
                                </option>
                                <option value="editor" <?php selected($default_role, 'editor'); ?>>
                                    <?php _e('Editor', 'organization-core'); ?>
                                </option>
                                <option value="administrator" <?php selected($default_role, 'administrator'); ?>>
                                    <?php _e('Administrator', 'organization-core'); ?>
                                </option>
                            </select>
                            <p class="description">
                                <?php _e('The role assigned to synced users on target sites.', 'organization-core'); ?>
                            </p>
                        </td>
                    </tr>

                    <!-- Sync Profile Updates -->
                    <tr>
                        <th scope="row">
                            <label for="mus_sync_on_profile_update">
                                <?php _e('Sync Profile Updates', 'organization-core'); ?>
                            </label>
                        </th>
                        <td>
                            <fieldset>
                                <legend class="screen-reader-text">
                                    <span><?php _e('Sync profile updates across sites', 'organization-core'); ?></span>
                                </legend>
                                <label for="mus_sync_on_profile_update">
                                    <input type="checkbox"
                                        id="mus_sync_on_profile_update"
                                        name="mus_sync_on_profile_update"
                                        value="1"
                                        <?php checked($sync_on_update, 1); ?>>
                                    <?php _e('Sync user profile changes across all sites', 'organization-core'); ?>
                                </label>
                                <p class="description">
                                    <?php _e('When a user updates their profile, changes will be synced to all sites they belong to.', 'organization-core'); ?>
                                </p>
                            </fieldset>
                        </td>
                    </tr>
                </table>

                <!-- ✅ FIXED: Proper submit button -->
                <?php submit_button(__('Save Settings', 'organization-core'), 'primary', 'submit', true); ?>
            </form>

            <hr style="margin: 30px 0;">

            <!-- Manual Sync Section -->
            <h2><?php _e('Manual User Synchronization', 'organization-core'); ?></h2>
            <p><?php _e('Use this section to manually sync existing users to selected sites.', 'organization-core'); ?></p>

            <div class="user-sync-actions" style="margin-top: 20px;">
                <button type="button"
                    class="button button-primary"
                    id="sync-all-users-btn"
                    data-nonce="<?php echo wp_create_nonce('network_sync_nonce'); ?>">
                    <?php _e('Sync All Users Now', 'organization-core'); ?>
                </button>
                <span class="spinner" id="sync-spinner" style="display: none; float: left; margin-right: 10px;"></span>
                <span id="sync-status" style="display: none; color: #23282d; margin-left: 10px;"></span>
            </div>

            <!-- Sync Progress Bar -->
            <div id="sync-progress-container" style="display: none; margin-top: 20px;">
                <h3><?php _e('Sync Progress', 'organization-core'); ?></h3>
                <div class="progress-bar" style="width: 100%; background: #f0f0f0; height: 30px; border-radius: 5px; overflow: hidden;">
                    <div id="progress-fill" style="width: 0%; background: #0073aa; height: 100%; transition: width 0.3s; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold;"></div>
                </div>
                <p id="progress-text" style="margin-top: 10px;">0 / 0 <?php _e('users synced', 'organization-core'); ?></p>
            </div>
        </div>
        
        <?php 
            $disable_inline_js = true ;
            if($disable_inline_js):
                return;
            endif;
        ?>
        <script>
            jQuery(document).ready(function($) {
                // Sync all users button
                $('#sync-all-users-btn').on('click', function(e) {
                    e.preventDefault();

                    if (!confirm('<?php _e('Are you sure? This will sync all existing users. This may take several minutes.', 'organization-core'); ?>')) {
                        return;
                    }

                    var button = $(this);
                    var spinner = $('#sync-spinner');
                    var status = $('#sync-status');
                    var progressContainer = $('#sync-progress-container');

                    // Show progress
                    button.prop('disabled', true);
                    spinner.show();
                    status.show().text('<?php _e('Starting...', 'organization-core'); ?>');
                    progressContainer.show();

                    // Make AJAX request
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'bulk_sync_all_users',
                            nonce: button.data('nonce')
                        },
                        success: function(response) {
                            if (response.success) {
                                $('#progress-fill').css('width', '100%');
                                $('#progress-text').text(response.data.message);
                                status.text('✓ <?php _e('Complete!', 'organization-core'); ?>').css('color', 'green');
                                alert('<?php _e('All users synced successfully!', 'organization-core'); ?>');
                            } else {
                                status.text('✗ ' + response.data.message).css('color', 'red');
                                alert('<?php _e('Sync error: ', 'organization-core'); ?>' + response.data.message);
                            }
                        },
                        error: function() {
                            status.text('✗ <?php _e('AJAX error', 'organization-core'); ?>').css('color', 'red');
                            alert('<?php _e('An error occurred during sync.', 'organization-core'); ?>');
                        },
                        complete: function() {
                            button.prop('disabled', false);
                            spinner.hide();
                        }
                    });
                });
            });
        </script>
        <?php
    }

    /**
     * ✅ FIXED: Handle form submission from admin-post.php
     * This is where the submit button data is processed
     */
    public function save_settings_handler()
    {
        // ✅ CRITICAL: Check nonce
        if (!isset($_POST['mus_sync_nonce']) || !wp_verify_nonce($_POST['mus_sync_nonce'], 'mus_save_network_sync_settings')) {
            wp_die(__('Security check failed', 'organization-core'));
        }

        // ✅ Check permissions
        if (!current_user_can('manage_network_options')) {
            wp_die(__('You do not have sufficient permissions to perform this action.', 'organization-core'));
        }

        // ✅ Save auto-sync setting
        $auto_sync = isset($_POST['mus_auto_sync_enabled']) ? 1 : 0;
        update_site_option('mus_auto_sync_enabled', $auto_sync);

        // ✅ Save sync sites
        $sync_sites = isset($_POST['mus_sync_sites']) ? array_map('intval', (array) $_POST['mus_sync_sites']) : [];
        update_site_option('mus_sync_sites', $sync_sites);

        // ✅ Save default role
        $allowed_roles = ['subscriber', 'contributor', 'author', 'editor', 'administrator'];
        $default_role = isset($_POST['mus_default_role']) ? sanitize_text_field($_POST['mus_default_role']) : 'subscriber';

        if (!in_array($default_role, $allowed_roles)) {
            $default_role = 'subscriber';
        }

        update_site_option('mus_default_role', $default_role);

        // ✅ Save sync on profile update
        $sync_on_update = isset($_POST['mus_sync_on_profile_update']) ? 1 : 0;
        update_site_option('mus_sync_on_profile_update', $sync_on_update);

        // ✅ Redirect back with success message
        $redirect_url = add_query_arg(
            ['page' => 'user-sync-settings', 'settings-updated' => 'true'],
            network_admin_url('admin.php')
        );

        wp_redirect($redirect_url);
        exit;
    }

}

new Network_User_Sync_Page();