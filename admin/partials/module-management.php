<div class="wrap">
    <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
    
    <?php settings_errors( 'organization_core_messages' ); ?>
    
    <?php
    // DEBUG: Check what's actually saved
    // $all_settings = OC_Network_Settings::get_all_settings();
    // echo '<div class="notice notice-info"><p><strong>Database Settings:</strong></p>';
    // echo '<pre>';
    // print_r( $all_settings );
    // echo '</pre></div>';
    ?>

    <form method="post" action="">
        <?php wp_nonce_field( 'organization_core_modules', 'organization_core_modules_nonce' ); ?>
        
        <?php if ( empty( $modules ) ) : ?>
            <div class="notice notice-warning">
                <p><?php _e( 'No modules registered yet. Modules will appear here once registered.', 'organization-core' ); ?></p>
            </div>
        <?php else : ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th style="width: 20%;"><?php _e( 'Module', 'organization-core' ); ?></th>
                        <th style="width: 30%;"><?php _e( 'Description', 'organization-core' ); ?></th>
                        <th style="width: 15%;"><?php _e( 'Status', 'organization-core' ); ?></th>
                        <th style="width: 25%;"><?php _e( 'Apply To Sites', 'organization-core' ); ?></th>
                        <th style="width: 10%;"><?php _e( 'Actions', 'organization-core' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $modules as $module_id => $module ) : 
                        $settings = OC_Network_Settings::get_module_settings( $module_id );
                        $scope = isset( $settings['scope'] ) ? $settings['scope'] : 'disabled';
                        $selected_sites = isset( $settings['sites'] ) ? $settings['sites'] : array();
                    ?>
                        <tr>
                            <td>
                                <strong><?php echo esc_html( $module['name'] ); ?></strong>
                                <br><small>v<?php echo esc_html( $module['version'] ); ?></small>
                            </td>
                            <td><?php echo esc_html( $module['description'] ); ?></td>
                            <td>
                                <select name="module_scope[<?php echo esc_attr( $module_id ); ?>]" 
                                        class="module-scope-selector" 
                                        data-module="<?php echo esc_attr( $module_id ); ?>">
                                    <option value="disabled" <?php selected( $scope, 'disabled' ); ?>>
                                        <?php _e( 'Disabled', 'organization-core' ); ?>
                                    </option>
                                    <option value="all_sites" <?php selected( $scope, 'all_sites' ); ?>>
                                        <?php _e( 'All Sites', 'organization-core' ); ?>
                                    </option>
                                    <option value="selected_sites" <?php selected( $scope, 'selected_sites' ); ?>>
                                        <?php _e( 'Selected Sites', 'organization-core' ); ?>
                                    </option>
                                </select>
                            </td>
                            <td>
                                <div class="site-selector" 
                                     id="sites-<?php echo esc_attr( $module_id ); ?>" 
                                     style="<?php echo $scope === 'selected_sites' ? '' : 'display:none;'; ?>">
                                    <div style="max-height: 150px; overflow-y: auto; border: 1px solid #ddd; padding: 10px;">
                                        <?php foreach ( $network_sites as $site ) : ?>
                                            <label style="display: block; margin-bottom: 5px;">
                                                <input type="checkbox" 
                                                       name="module_sites[<?php echo esc_attr( $module_id ); ?>][]" 
                                                       value="<?php echo esc_attr( $site['blog_id'] ); ?>"
                                                       <?php checked( in_array( $site['blog_id'], $selected_sites ) ); ?> />
                                                <?php echo esc_html( $site['blogname'] ); ?>
                                                <small style="color: #666;">(<?php echo esc_html( $site['siteurl'] ); ?>)</small>
                                            </label>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                <div class="all-sites-notice" 
                                     id="notice-<?php echo esc_attr( $module_id ); ?>" 
                                     style="<?php echo $scope === 'all_sites' ? '' : 'display:none;'; ?>">
                                    <em><?php _e( 'This module will be enabled on all network sites.', 'organization-core' ); ?></em>
                                </div>
                            </td>
                            <td>
                                <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
                                    <input type="hidden" name="action" value="run_module_activator">
                                    <input type="hidden" name="module_id" value="<?php echo esc_attr($module_id); ?>">
                                    <?php wp_nonce_field('run_module_activator_' . $module_id, 'run_module_activator_nonce'); ?>
                                    <?php submit_button(__('Run Activator', 'organization-core'), 'secondary', 'run_activator', false); ?>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <?php submit_button( __( 'Save Module Settings', 'organization-core' ), 'primary', 'organization_core_save_modules' ); ?>
        <?php endif; ?>
    </form>
</div>

<script>
jQuery(document).ready(function($) {
    $('.module-scope-selector').on('change', function() {
        var moduleId = $(this).data('module');
        var scope = $(this).val();
        var $siteSelector = $('#sites-' + moduleId);
        var $notice = $('#notice-' + moduleId);
        
        if (scope === 'selected_sites') {
            $siteSelector.show();
            $notice.hide();
        } else if (scope === 'all_sites') {
            $siteSelector.hide();
            $notice.show();
        } else {
            $siteSelector.hide();
            $notice.hide();
        }
    });
});
</script>
