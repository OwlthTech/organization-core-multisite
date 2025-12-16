<?php
/**
 * Template: Rooming List - List View
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('WP_List_Table')) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class OC_Rooming_List_Table extends WP_List_Table
{
    public function __construct()
    {
        parent::__construct(array(
            'singular' => 'rooming_list',
            'plural'   => 'rooming_lists',
            'ajax'     => false
        ));
    }

    public function get_columns()
    {
        return array(
            'id' => __('ID', 'organization-core'),
            'school_name' => __('School / Group', 'organization-core'),
            'hotel_assigned' => __('Hotel Assigned', 'organization-core'),
            'status' => __('Status', 'organization-core'),
            'booking' => __('Booking', 'organization-core'),
            'actions' => __('Actions', 'organization-core')
        );
    }

    public function get_sortable_columns()
    {
        return array(
            'id' => array('id', true),
            'status' => array('status', false)
        );
    }

    public function prepare_items()
    {
        $columns = $this->get_columns();
        $hidden = array();
        $sortable = $this->get_sortable_columns();

        $this->_column_headers = array($columns, $hidden, $sortable);

        $per_page = $this->get_items_per_page('rooming_lists_per_page', 20);
        $current_page = $this->get_pagenum();
        $search = isset($_REQUEST['s']) ? wp_unslash(trim($_REQUEST['s'])) : '';
        $hotel_filter = isset($_REQUEST['hotel_filter']) ? sanitize_text_field($_REQUEST['hotel_filter']) : '';
        $status_filter = isset($_REQUEST['status_filter']) ? sanitize_text_field($_REQUEST['status_filter']) : '';

        global $wpdb;
        $bookings_table = $wpdb->base_prefix . 'bookings';

        // Build query
        $where = array();
        $params = array();

        if (!empty($search)) {
            $where[] = "school_id LIKE %s";
            $params[] = '%' . $wpdb->esc_like($search) . '%';
        }

        // Filter by hotel assignment
        if ($hotel_filter === 'assigned') {
            $where[] = "hotel_data IS NOT NULL AND hotel_data != '' AND hotel_data != '[]'";
        } elseif ($hotel_filter === 'not_assigned') {
            $where[] = "(hotel_data IS NULL OR hotel_data = '' OR hotel_data = '[]')";
        }

        // Filter by status
        if (!empty($status_filter)) {
            $where[] = "status = %s";
            $params[] = $status_filter;
        }

        $where_clause = !empty($where) ? ' WHERE ' . implode(' AND ', $where) : '';

        // Get orderby
        $orderby = isset($_REQUEST['orderby']) ? sanitize_text_field($_REQUEST['orderby']) : 'id';
        $order = isset($_REQUEST['order']) && $_REQUEST['order'] === 'asc' ? 'ASC' : 'DESC';

        // Count total items
        $count_query = "SELECT COUNT(*) FROM $bookings_table" . $where_clause;
        if (!empty($params)) {
            $total_items = $wpdb->get_var($wpdb->prepare($count_query, $params));
        } else {
            $total_items = $wpdb->get_var($count_query);
        }

        // Get items
        $offset = ($current_page - 1) * $per_page;
        $query = "SELECT * FROM $bookings_table" . $where_clause . " ORDER BY $orderby $order LIMIT %d OFFSET %d";
        $query_params = array_merge($params, array($per_page, $offset));

        $this->items = $wpdb->get_results($wpdb->prepare($query, $query_params), ARRAY_A);

        $this->set_pagination_args(array(
            'total_items' => $total_items,
            'per_page'    => $per_page,
            'total_pages' => ceil($total_items / $per_page)
        ));
    }

    public function column_default($item, $column_name)
    {
        switch ($column_name) {
            case 'id':
                return $item['id'];
            case 'status':
                return ucfirst($item['status']);
            default:
                return '';
        }
    }

    public function column_school_name($item)
    {
        $school_name = 'N/A';
        if (!empty($item['school_id'])) {
            if (class_exists('OC_Bookings_CRUD')) {
                $school = OC_Bookings_CRUD::get_school($item['school_id']);
                if ($school && !empty($school['school_name'])) {
                    $school_name = $school['school_name'];
                } else {
                    $school_name = 'School ID: ' . $item['school_id'];
                }
            }
        }

        return esc_html($school_name);
    }

    public function column_hotel_assigned($item)
    {
        $hotel_data = !empty($item['hotel_data']) ? json_decode($item['hotel_data'], true) : array();
        $has_hotel = !empty($hotel_data['hotel_id']);

        if ($has_hotel) {
            return '<span class="dashicons dashicons-building" style="color: #46b450;"></span> ' . __('Yes', 'organization-core');
        } else {
            return '<span class="dashicons dashicons-minus" style="color: #ccc;"></span>';
        }
    }

    public function column_booking($item)
    {
        $booking_url = admin_url('admin.php?page=organization-bookings&action=view&booking_id=' . $item['id']);
        return sprintf(
            '<a href="%s" class="button">%s</a>',
            esc_url($booking_url),
            __('View Booking', 'organization-core')
        );
    }

    public function column_actions($item)
    {
        $hotel_data = !empty($item['hotel_data']) ? json_decode($item['hotel_data'], true) : array();
        $has_hotel = !empty($hotel_data['hotel_id']);

        if ($has_hotel) {
            $manage_url = admin_url('admin.php?page=organization-rooming-list&action=edit&booking_id=' . $item['id']);
            return sprintf(
                '<a href="%s" class="button button-primary">%s</a>',
                esc_url($manage_url),
                __('Manage List', 'organization-core')
            );
        } else {
            return sprintf(
                '<button class="button" disabled title="%s">%s</button>',
                esc_attr(__('Assign a hotel first', 'organization-core')),
                __('Manage List', 'organization-core')
            );
        }
    }

    protected function extra_tablenav($which)
    {
        if ($which === 'top') {
            $hotel_filter = isset($_REQUEST['hotel_filter']) ? sanitize_text_field($_REQUEST['hotel_filter']) : '';
            $status_filter = isset($_REQUEST['status_filter']) ? sanitize_text_field($_REQUEST['status_filter']) : '';
            ?>
            <div class="alignleft actions">
                <select name="hotel_filter" id="hotel-filter" style="float: none;">
                    <option value=""><?php _e('All Hotels', 'organization-core'); ?></option>
                    <option value="assigned" <?php selected($hotel_filter, 'assigned'); ?>><?php _e('Hotel Assigned', 'organization-core'); ?></option>
                    <option value="not_assigned" <?php selected($hotel_filter, 'not_assigned'); ?>><?php _e('No Hotel', 'organization-core'); ?></option>
                </select>

                <select name="status_filter" id="status-filter" style="float: none;">
                    <option value=""><?php _e('All Statuses', 'organization-core'); ?></option>
                    <option value="pending" <?php selected($status_filter, 'pending'); ?>><?php _e('Pending', 'organization-core'); ?></option>
                    <option value="confirmed" <?php selected($status_filter, 'confirmed'); ?>><?php _e('Confirmed', 'organization-core'); ?></option>
                    <option value="cancelled" <?php selected($status_filter, 'cancelled'); ?>><?php _e('Cancelled', 'organization-core'); ?></option>
                </select>

                <?php
                $filter_disabled = empty($hotel_filter) && empty($status_filter);
                $has_active_filters = !empty($hotel_filter) || !empty($status_filter);
                ?>
                <button type="submit" name="filter_action" value="filter" class="button" <?php echo $filter_disabled ? 'disabled' : ''; ?>>
                    <?php _e('Filter', 'organization-core'); ?>
                </button>
                <?php if ($has_active_filters): ?>
                    <a href="<?php echo admin_url('admin.php?page=organization-rooming-list'); ?>" class="button">
                        <?php _e('Reset', 'organization-core'); ?>
                    </a>
                <?php endif; ?>

                <script type="text/javascript">
                    jQuery(document).ready(function($) {
                        function toggleFilterButton() {
                            var hotelSelected = $('#hotel-filter').val() !== '';
                            var statusSelected = $('#status-filter').val() !== '';
                            var hasSelection = hotelSelected || statusSelected;
                            var filterBtn = $('button[name="filter_action"][value="filter"]');
                            filterBtn.prop('disabled', !hasSelection);
                        }

                        toggleFilterButton();
                        $('#hotel-filter, #status-filter').on('change', toggleFilterButton);
                    });
                </script>
            </div>
            <?php
        }
    }

    public function no_items()
    {
        _e('No bookings found.', 'organization-core');
    }
}

$rooming_list_table = new OC_Rooming_List_Table();
$rooming_list_table->prepare_items();
?>

<div class="wrap">
    <h1 class="wp-heading-inline"><?php _e('Rooming Lists', 'organization-core'); ?></h1>
    <hr class="wp-header-end">

    <form method="get">
        <input type="hidden" name="page" value="<?php echo esc_attr($_REQUEST['page']); ?>">
        <?php
        $rooming_list_table->search_box(__('Search Bookings', 'organization-core'), 'rooming_list');
        $rooming_list_table->display();
        ?>
    </form>
</div>
