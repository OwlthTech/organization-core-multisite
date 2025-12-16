<?php

if (!class_exists('WP_List_Table')) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class OC_Shareables_List_Table extends WP_List_Table {

    public function __construct() {
        parent::__construct(array(
            'singular' => __('Shareable', 'organization-core'),
            'plural'   => __('Shareables', 'organization-core'),
            'ajax'     => false
        ));
    }

    public function get_columns() {
        return array(
            'cb'        => '<input type="checkbox" />',
            'title'     => __('Title', 'organization-core'),
            'status'    => __('Status', 'organization-core'),
            'uuid'      => __('Public URL', 'organization-core'),
            'date'      => __('Date', 'organization-core')
        );
    }

    public function get_bulk_actions() {
        return array(
            'delete' => __('Delete', 'organization-core')
        );
    }

    public function column_cb($item) {
        return sprintf(
            '<input type="checkbox" name="shareables[]" value="%d" />',
            $item->id
        );
    }

    public function column_title($item) {
        $edit_url = admin_url('admin.php?page=shareables&action=edit&id=' . $item->id);
        $delete_url = wp_nonce_url(admin_url('admin.php?page=shareables&action=delete&id=' . $item->id), 'delete_shareable_' . $item->id);

        $actions = array(
            'edit'   => sprintf('<a href="%s">%s</a>', $edit_url, __('Edit', 'organization-core')),
            'delete' => sprintf('<a href="%s" class="delete-shareable" data-id="%d">%s</a>', '#', $item->id, __('Delete', 'organization-core')),
        );

        return sprintf(
            '<strong><a class="row-title" href="%s">%s</a></strong>%s',
            $edit_url,
            esc_html($item->title),
            $this->row_actions($actions)
        );
    }

    public function column_status($item) {
        $status_labels = array(
            'publish' => __('Published', 'organization-core'),
            'draft'   => __('Draft', 'organization-core')
        );
        $label = isset($status_labels[$item->status]) ? $status_labels[$item->status] : ucfirst($item->status);
        return sprintf('<span class="post-state-display">%s</span>', esc_html($label));
    }

    public function column_uuid($item) {
        if (empty($item->uuid)) {
            return '<span class="text-muted">' . __('No link (Draft)', 'organization-core') . '</span>';
        }
        $url = home_url('/share/' . $item->uuid);
        return sprintf('<a href="%s" target="_blank">%s <span class="dashicons dashicons-external"></span></a>', esc_url($url), esc_html($item->uuid));
    }

    public function column_date($item) {
        return date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($item->created_at));
    }

    public function prepare_items() {
        $columns = $this->get_columns();
        $hidden = array();
        $sortable = array();

        $this->_column_headers = array($columns, $hidden, $sortable);

        $per_page = 20;
        $current_page = $this->get_pagenum();
        $search = isset($_REQUEST['s']) ? $_REQUEST['s'] : '';

        $total_items = OC_Shareables_CRUD::get_total_count($search);
        $data = OC_Shareables_CRUD::get_items($per_page, ($current_page - 1) * $per_page, $search);

        $this->items = $data;

        $this->set_pagination_args(array(
            'total_items' => $total_items,
            'per_page'    => $per_page,
            'total_pages' => ceil($total_items / $per_page)
        ));
    }
}
