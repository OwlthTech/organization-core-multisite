<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @package    Organization_Core
 * @subpackage Organization_Core/modules/shareables/admin
 */

class OC_Shareables_Admin {

    private $module_id;
    private $version;
    private $template_loader;

    public function __construct($module_id, $version, $template_loader) {
        $this->module_id = $module_id;
        $this->version = $version;
        $this->template_loader = $template_loader;
    }

    public function init() {
        add_action('admin_menu', array($this, 'add_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_assets'));
        add_action('admin_init', array($this, 'handle_actions'));
    }

    public function add_menu() {
        add_menu_page(
            __('Shareables', 'organization-core'),
            __('Shareables', 'organization-core'),
            'manage_options',
            'shareables',
            array($this, 'render_page'),
            'dashicons-share',
            26
        );
    }

    public function enqueue_assets($hook) {
        if ($hook !== 'toplevel_page_shareables') {
            return;
        }

        wp_enqueue_media();

        wp_enqueue_style($this->module_id . '-admin-css', plugin_dir_url(__DIR__) . 'assets/admin/css/shareables-admin.css', array(), $this->version, 'all');
        wp_enqueue_script($this->module_id . '-admin-js', plugin_dir_url(__DIR__) . 'assets/admin/js/shareables-admin.js', array('jquery'), $this->version, false);

        wp_localize_script($this->module_id . '-admin-js', 'oc_shareables', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('shareables_nonce'),
            'strings' => array(
                'confirm_delete' => __('Are you sure you want to delete this item?', 'organization-core'),
                'saved' => __('Saved successfully!', 'organization-core'),
                'error' => __('Something went wrong.', 'organization-core')
            )
        ));
    }

    public function handle_actions() {
        // Only handle on our admin page
        if (!isset($_GET['page']) || $_GET['page'] !== 'shareables') {
            return;
        }

        // Handle single delete
        if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
            $id = intval($_GET['id']);
            
            // Verify nonce
            if (!wp_verify_nonce($_GET['_wpnonce'], 'delete_shareable_' . $id)) {
                wp_die(__('Security check failed.', 'organization-core'));
            }

            // Check permissions
            if (!current_user_can('manage_options')) {
                wp_die(__('You do not have permission to delete shareables.', 'organization-core'));
            }

            // Delete item
            $result = OC_Shareables_CRUD::delete_item($id);
            
            if ($result) {
                wp_redirect(admin_url('admin.php?page=shareables&deleted=1'));
                exit;
            } else {
                wp_redirect(admin_url('admin.php?page=shareables&error=delete_failed'));
                exit;
            }
        }

        // Handle bulk delete
        if (isset($_POST['action']) && $_POST['action'] === 'delete' && isset($_POST['shareables'])) {
            // Verify nonce
            check_admin_referer('bulk-shareables');

            // Check permissions
            if (!current_user_can('manage_options')) {
                wp_die(__('You do not have permission to delete shareables.', 'organization-core'));
            }

            $ids = array_map('intval', $_POST['shareables']);
            $deleted_count = 0;

            foreach ($ids as $id) {
                if (OC_Shareables_CRUD::delete_item($id)) {
                    $deleted_count++;
                }
            }

            wp_redirect(admin_url('admin.php?page=shareables&deleted=' . $deleted_count));
            exit;
        }
    }

    public function render_page() {
        $action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : 'list';
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;

        $template_path = plugin_dir_path(__DIR__) . 'templates/admin/';

        if ($action === 'add' || ($action === 'edit' && $id > 0)) {
            $item = null;
            if ($id > 0) {
                $item = OC_Shareables_CRUD::get_item($id);
            }
            
            // Generate a UUID for new items if not present (though normally generated on save, useful to have for UI if needed, but for now we generate on save)
            
            $this->template_loader->get_template('shareables-single.php', array('item' => $item), $this->module_id, $template_path);
        } else {
            // Include List Table Class
            require_once plugin_dir_path(__DIR__) . 'templates/admin/shareables-table.php';
            
            $list_table = new OC_Shareables_List_Table();
            $list_table->prepare_items();
            
            $this->template_loader->get_template('shareables-list-wrapper.php', array('list_table' => $list_table), $this->module_id, $template_path);
        }
    }


}
