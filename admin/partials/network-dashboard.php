<div class="wrap">
    <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
    
    <div class="card">
        <h2><?php _e( 'Plugin Information', 'organization-core' ); ?></h2>
        <table class="form-table">
            <tr>
                <th><?php _e( 'Version:', 'organization-core' ); ?></th>
                <td><?php echo esc_html( ORGANIZATION_CORE_VERSION ); ?></td>
            </tr>
            <tr>
                <th><?php _e( 'Network Sites:', 'organization-core' ); ?></th>
                <td><?php echo count( get_sites() ); ?></td>
            </tr>
            <tr>
                <th><?php _e( 'Registered Modules:', 'organization-core' ); ?></th>
                <td><?php echo count( OC_Module_Registry::get_all_modules() ); ?></td>
            </tr>
        </table>
    </div>

    <div class="card">
        <h2><?php _e( 'Quick Links', 'organization-core' ); ?></h2>
        <p>
            <a href="<?php echo esc_url( network_admin_url( 'admin.php?page=organization-core-modules' ) ); ?>" class="button button-primary">
                <?php _e( 'Manage Modules', 'organization-core' ); ?>
            </a>
        </p>
    </div>
</div>
