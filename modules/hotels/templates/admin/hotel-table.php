<?php
if (!class_exists('WP_List_Table')) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class OC_Hotels_List_Table extends WP_List_Table
{
    public function __construct()
    {
        parent::__construct(array(
            'singular' => 'hotel',
            'plural'   => 'hotels',
            'ajax'     => false
        ));
    }

    public function get_columns()
    {
        return array(
            'cb' => '<input type="checkbox" />',
            'name' => __('Name', 'organization-core'),
            'address' => __('Address', 'organization-core'),
            'number_of_person' => __('Capacity (Persons)', 'organization-core'),
            'number_of_rooms' => __('Rooms', 'organization-core'),
            'created_at' => __('Date Created', 'organization-core')
        );
    }

    public function get_bulk_actions()
    {
        return array(
            'bulk-delete' => __('Delete', 'organization-core')
        );
    }

    public function prepare_items()
    {
        $columns = $this->get_columns();
        $hidden = array();
        $sortable = array('name' => array('name', false), 'created_at' => array('created_at', false));

        $this->_column_headers = array($columns, $hidden, $sortable);

        $per_page = $this->get_items_per_page('hotels_per_page', 20);
        $current_page = $this->get_pagenum();
        $search = isset($_REQUEST['s']) ? wp_unslash(trim($_REQUEST['s'])) : '';
        $rooms_filter = isset($_REQUEST['rooms_filter']) ? sanitize_text_field($_REQUEST['rooms_filter']) : '';
        $capacity_filter = isset($_REQUEST['capacity_filter']) ? sanitize_text_field($_REQUEST['capacity_filter']) : '';

        $filter_args = array(
            'search' => $search,
            'rooms_filter' => $rooms_filter,
            'capacity_filter' => $capacity_filter
        );

        $total_items = OC_Hotels_CRUD::get_total_hotels($filter_args);

        $this->items = OC_Hotels_CRUD::get_hotels(array(
            'limit' => $per_page,
            'offset' => ($current_page - 1) * $per_page,
            'search' => $search,
            'rooms_filter' => $rooms_filter,
            'capacity_filter' => $capacity_filter,
            'orderby' => isset($_REQUEST['orderby']) ? $_REQUEST['orderby'] : 'id',
            'order' => isset($_REQUEST['order']) ? $_REQUEST['order'] : 'DESC'
        ));

        $this->set_pagination_args(array(
            'total_items' => $total_items,
            'per_page'    => $per_page,
            'total_pages' => ceil($total_items / $per_page)
        ));
    }

    protected function extra_tablenav($which)
    {
        if ($which === 'top') {
            $rooms_filter = isset($_REQUEST['rooms_filter']) ? sanitize_text_field($_REQUEST['rooms_filter']) : '';
            $capacity_filter = isset($_REQUEST['capacity_filter']) ? sanitize_text_field($_REQUEST['capacity_filter']) : '';

            // Get unique room and capacity values for filters
            global $wpdb;
            $table_name = $wpdb->base_prefix . 'hotels';
            $rooms = $wpdb->get_col("SELECT DISTINCT number_of_rooms FROM $table_name ORDER BY number_of_rooms ASC");
            $capacities = $wpdb->get_col("SELECT DISTINCT number_of_person FROM $table_name ORDER BY number_of_person ASC");
            ?>
            <div class="alignleft actions">
                <select name="rooms_filter" id="rooms-filter" style="float: none;">
                    <option value=""><?php _e('All Rooms', 'organization-core'); ?></option>
                    <?php foreach ($rooms as $room): ?>
                        <option value="<?php echo esc_attr($room); ?>" <?php selected($rooms_filter, $room); ?>>
                            <?php echo esc_html($room) . ' ' . _n('Room', 'Rooms', $room, 'organization-core'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <select name="capacity_filter" id="capacity-filter" style="float: none;">
                    <option value=""><?php _e('All Capacities', 'organization-core'); ?></option>
                    <?php foreach ($capacities as $capacity): ?>
                        <option value="<?php echo esc_attr($capacity); ?>" <?php selected($capacity_filter, $capacity); ?>>
                            <?php echo esc_html($capacity) . ' ' . _n('Person', 'Persons', $capacity, 'organization-core'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <?php
                $filter_disabled = empty($rooms_filter) && empty($capacity_filter);
                $has_active_filters = !empty($rooms_filter) || !empty($capacity_filter);
                ?>
                <button type="submit" name="filter_action" value="filter" class="button" <?php echo $filter_disabled ? 'disabled' : ''; ?>>
                    <?php _e('Filter', 'organization-core'); ?>
                </button>
                <?php if ($has_active_filters): ?>
                    <a href="<?php echo admin_url('admin.php?page=hotels'); ?>" class="button">
                        <?php _e('Reset', 'organization-core'); ?>
                    </a>
                <?php endif; ?>

                <script type="text/javascript">
                    jQuery(document).ready(function($) {
                        function toggleFilterButton() {
                            var roomsSelected = $('#rooms-filter').val() !== '';
                            var capacitySelected = $('#capacity-filter').val() !== '';
                            var hasSelection = roomsSelected || capacitySelected;
                            var filterBtn = $('button[name="filter_action"][value="filter"]');
                            filterBtn.prop('disabled', !hasSelection);
                        }

                        toggleFilterButton();
                        $('#rooms-filter, #capacity-filter').on('change', toggleFilterButton);
                    });
                </script>
            </div>
            <?php
        }
    }

    public function column_default($item, $column_name)
    {
        switch ($column_name) {
            case 'address':
            case 'number_of_person':
            case 'number_of_rooms':
            case 'created_at':
                return $item->$column_name;
            default:
                return print_r($item, true);
        }
    }

    public function column_cb($item)
    {
        return sprintf(
            '<input type="checkbox" name="hotel[]" value="%s" />',
            $item->id
        );
    }

    public function column_name($item)
    {
        $edit_url = admin_url('admin.php?page=hotels&action=edit&hotel_id=' . $item->id);
        $delete_url = wp_nonce_url(admin_url('admin.php?page=hotels&action=delete&hotel_id=' . $item->id), 'delete_hotel_' . $item->id);

        $actions = array(
            'edit' => sprintf(
                '<a href="%s">%s</a>',
                esc_url($edit_url),
                __('Edit', 'organization-core')
            ),
            'delete' => sprintf(
                '<a href="%s" onclick="return confirm(\'Are you sure?\')">%s</a>',
                esc_url($delete_url),
                __('Delete', 'organization-core')
            ),
        );

        return sprintf('%1$s %2$s', $item->name, $this->row_actions($actions));
    }
}

$hotels_table = new OC_Hotels_List_Table();
$hotels_table->prepare_items();
?>

<div class="wrap">
    <h1 class="wp-heading-inline"><?php _e('Hotels', 'organization-core'); ?></h1>
    <a href="<?php echo admin_url('admin.php?page=hotels-add-new'); ?>" class="page-title-action"><?php _e('Add New', 'organization-core'); ?></a>
    <hr class="wp-header-end">

    <?php if (isset($_GET['message']) && $_GET['message'] === 'created') : ?>
        <div class="notice notice-success is-dismissible">
            <p><?php _e('Hotel added successfully.', 'organization-core'); ?></p>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['message']) && $_GET['message'] === 'updated') : ?>
        <div class="notice notice-success is-dismissible">
            <p><?php _e('Hotel updated successfully.', 'organization-core'); ?></p>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['message']) && $_GET['message'] === 'deleted') : ?>
        <div class="notice notice-success is-dismissible">
            <p><?php _e('Hotel deleted successfully.', 'organization-core'); ?></p>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['message']) && $_GET['message'] === 'bulk_deleted') : ?>
        <div class="notice notice-success is-dismissible">
            <p><?php printf(__('%d hotel(s) deleted successfully.', 'organization-core'), intval($_GET['count'])); ?></p>
        </div>
    <?php endif; ?>

    <form method="post">
        <?php
        $hotels_table->search_box(__('Search Hotels', 'organization-core'), 'hotel');
        $hotels_table->display();
        ?>
    </form>
</div>
