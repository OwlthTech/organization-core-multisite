<div class="wrap">
    <h1 class="wp-heading-inline"><?php _e('Shareables', 'organization-core'); ?></h1>
    <a href="<?php echo admin_url('admin.php?page=shareables&action=add'); ?>" class="page-title-action"><?php _e('Add New', 'organization-core'); ?></a>
    <hr class="wp-header-end">
    
    <form method="post">
        <?php
        $args['list_table']->search_box(__('Search', 'organization-core'), 'shareables');
        $args['list_table']->display(); 
        ?>
    </form>
</div>
