<?php

/**
 * Bookings WP_List_Table Implementation
 * Migrated from Owlth_Booking_Reports_Table with EXACT logic preserved
 */

if (!class_exists('WP_List_Table')) {
    require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}
class OC_Bookings_Table extends WP_List_Table
{
    private $booking_crud;

    public function __construct()
    {
        parent::__construct(array(
            'singular' => 'booking',
            'plural' => 'bookings',
            'ajax' => false,
        ));
        $this->booking_crud = new OC_Bookings_CRUD();

        // Add inline scripts to admin footer - EXACT from old system
        add_action('admin_footer', array($this, 'admin_footer_scripts'));
    }

    /**
     * Define the columns
     * EXACT from old system
     */
    public function get_columns()
    {
        return array(
            'cb' => '<input type="checkbox" />',
            'id' => __('ID', 'organization-core'),
            'customer_name' => __('Customer', 'organization-core'),
            'package_name' => __('Package', 'organization-core'),
            'price' => __('Price', 'organization-core'),
            'location_name' => __('Location', 'organization-core'),
            'parks_selection' => __('Parks', 'organization-core'),
            'festival_date' => __('Festival Date', 'organization-core'),
            'booking_date' => __('Booking Date', 'organization-core'),
            'actions' => __('Actions', 'organization-core')
        );
    }

    /**
     * Define which columns are sortable
     * EXACT from old system
     */
    protected function get_sortable_columns()
    {
        return array(
            'id' => array('id', true),
            'festival_date' => array('festival_date', false),
            'booking_date' => array('booking_date', false),
        );
    }

    /**
     * Prepare table items with filters
     * EXACT logic from old system
     */
    public function prepare_items()
    {
        $columns = $this->get_columns();
        $hidden = array();
        $sortable = $this->get_sortable_columns();

        $this->_column_headers = array($columns, $hidden, $sortable);

        $per_page = 10;
        $current_page = $this->get_pagenum();
        $total_items = $this->get_total_items();

        $this->items = $this->fetch_bookings_data($per_page, $current_page);

        $this->set_pagination_args(array(
            'total_items' => $total_items,
            'per_page' => $per_page,
            'total_pages' => ceil($total_items / $per_page)
        ));
    }

    /**
     * Display filter section
     * EXACT from old system
     */
    public function extra_tablenav($which)
    {
        if ($which == "top") {
            $selected_package = isset($_REQUEST['package_filter']) ? $_REQUEST['package_filter'] : '';
            $festival_date_from = isset($_REQUEST['festival_date_from']) ? $_REQUEST['festival_date_from'] : '';
            $festival_date_to = isset($_REQUEST['festival_date_to']) ? $_REQUEST['festival_date_to'] : '';

            $has_active_filters = !empty($selected_package) || !empty($festival_date_from) || !empty($festival_date_to);

            $packages = get_posts(array(
                'post_type' => 'packages',
                'posts_per_page' => -1,
                'post_status' => 'publish'
            ));
?>
            <div class="alignleft actions">

                <label for="package-filter"><?php _e('Package:', 'organization-core'); ?></label>
                <select name="package_filter" id="package-filter">
                    <option value=""><?php _e('All Packages', 'organization-core'); ?></option>
                    <?php foreach ($packages as $package): ?>
                        <option value="<?php echo $package->ID; ?>" <?php selected($selected_package, $package->ID); ?>>
                            <?php echo esc_html($package->post_title); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <label for="festival-date-from"><?php _e('Festival Date From:', 'organization-core'); ?></label>
                <input type="date" name="festival_date_from" id="festival-date-from" value="<?php echo esc_attr($festival_date_from); ?>">

                <label for="festival-date-to"><?php _e(' To:', 'organization-core'); ?></label>
                <input type="date" name="festival_date_to" id="festival-date-to" value="<?php echo esc_attr($festival_date_to); ?>">

                <?php
                $filter_disabled = empty($selected_package) && empty($festival_date_from) && empty($festival_date_to);
                ?>
                <button type="submit" name="filter_action" value="filter" class="button button-primary" <?php echo $filter_disabled ? 'disabled' : ''; ?>>
                    <?php _e('Filter', 'organization-core'); ?>
                </button>

                <?php if ($has_active_filters): ?>
                    <a href="<?php echo admin_url('admin.php?page=organization-bookings'); ?>" class="button">
                        <?php _e('Reset', 'organization-core'); ?>
                    </a>
                <?php endif; ?>

                <script type="text/javascript">
                    jQuery(document).ready(function($) {
                        function toggleFilterButton() {
                            var packageSelected = $('#package-filter').val() !== '';
                            var dateFromSelected = $('#festival-date-from').val() !== '';
                            var dateToSelected = $('#festival-date-to').val() !== '';

                            var hasSelection = packageSelected || dateFromSelected || dateToSelected;
                            var filterBtn = $('button[name="filter_action"]');

                            filterBtn.prop('disabled', !hasSelection);
                        }

                        toggleFilterButton();
                        $('#package-filter, #festival-date-from, #festival-date-to').on('change', toggleFilterButton);
                    });
                </script>
            </div>
        <?php
        }
    }

    /**
     * Fetch data for the table with proper filtering
     * EXACT from old system with blog_id support added
     */
    public function fetch_bookings_data($per_page, $page_number)
    {
        global $wpdb;
        $table_name = $wpdb->base_prefix . 'bookings';
        $blog_id = get_current_blog_id();

        $orderby_mapping = array(
            'id' => 'b.id',
            'festival_date' => 'b.date_selection',
            'booking_date' => 'b.created_at'
        );

        $requested_orderby = isset($_REQUEST['orderby']) ? sanitize_text_field($_REQUEST['orderby']) : 'id';
        $orderby = isset($orderby_mapping[$requested_orderby]) ? $orderby_mapping[$requested_orderby] : 'b.id';
        $order = isset($_REQUEST['order']) && $_REQUEST['order'] === 'asc' ? 'ASC' : 'DESC';

        $where_conditions = array("b.blog_id = %d");
        $search_params = array($blog_id);

        if (!empty($_REQUEST['package_filter'])) {
            $where_conditions[] = "b.package_id = %d";
            $search_params[] = intval($_REQUEST['package_filter']);
        }

        if (!empty($_REQUEST['festival_date_from'])) {
            $where_conditions[] = "DATE(b.date_selection) >= %s";
            $search_params[] = sanitize_text_field($_REQUEST['festival_date_from']);
        }

        if (!empty($_REQUEST['festival_date_to'])) {
            $where_conditions[] = "DATE(b.date_selection) <= %s";
            $search_params[] = sanitize_text_field($_REQUEST['festival_date_to']);
        }

        $where_clause = ' WHERE ' . implode(' AND ', $where_conditions);

        $offset = ($page_number - 1) * $per_page;

        $query = "SELECT b.*, u.display_name, u.user_email, p.post_title as package_name 
                  FROM {$table_name} b 
                  LEFT JOIN {$wpdb->users} u ON b.user_id = u.ID 
                  LEFT JOIN {$wpdb->posts} p ON b.package_id = p.ID 
                  {$where_clause} 
                  ORDER BY {$orderby} {$order} 
                  LIMIT %d OFFSET %d";

        $params = array_merge($search_params, array($per_page, $offset));

        return $wpdb->get_results($wpdb->prepare($query, $params));
    }

    /**
     * Get total number of bookings
     * EXACT from old system with blog_id support added
     */
    public function get_total_items()
    {
        global $wpdb;
        $table_name = $wpdb->base_prefix . 'bookings';
        $blog_id = get_current_blog_id();

        $where_conditions = array("b.blog_id = %d");
        $search_params = array($blog_id);

        if (!empty($_REQUEST['package_filter'])) {
            $where_conditions[] = "b.package_id = %d";
            $search_params[] = intval($_REQUEST['package_filter']);
        }

        if (!empty($_REQUEST['festival_date_from'])) {
            $where_conditions[] = "DATE(b.date_selection) >= %s";
            $search_params[] = sanitize_text_field($_REQUEST['festival_date_from']);
        }

        if (!empty($_REQUEST['festival_date_to'])) {
            $where_conditions[] = "DATE(b.date_selection) <= %s";
            $search_params[] = sanitize_text_field($_REQUEST['festival_date_to']);
        }

        $where_clause = ' WHERE ' . implode(' AND ', $where_conditions);

        $query = "SELECT COUNT(*) FROM {$table_name} b {$where_clause}";

        return $wpdb->get_var($wpdb->prepare($query, $search_params));
    }

    /**
     * Render the checkbox column
     */
    public function column_cb($item)
    {
        return sprintf('<input type="checkbox" name="booking[]" value="%s" />', $item->id);
    }

    /**
     * Render columns
     * EXACT from old system
     */
    public function column_default($item, $column_name)
    {
        switch ($column_name) {
            case 'id':
                // Create view URL
                $view_url = add_query_arg(array(
                    'page' => 'organization-bookings',
                    'action' => 'view',
                    'booking_id' => $item->id
                ), admin_url('admin.php'));

                // Return ID with view link and hover actions
                $actions = array(
                    'view' => sprintf('<a href="%s">%s</a>', esc_url($view_url), __('View Details', 'organization-core'))
                );

                return sprintf(
                    '<strong><a href="%s">#%d</a></strong>%s',
                    esc_url($view_url),
                    $item->id,
                    $this->row_actions($actions)
                );

            case 'customer_name':
                return '<strong>' . esc_html($item->display_name) . '</strong><br><span class="description">' . esc_html($item->user_email) . '</span>';

            case 'package_name':
                return $item->package_name ? esc_html($item->package_name) : __('N/A', 'organization-core');

            case 'price':
                // EXACT price column from old system
                $current_price = isset($item->total_amount) ? floatval($item->total_amount) : 0;

                $html = '<div class="price-container" data-booking-id="' . $item->id . '">';

                $html .= '<div class="price-display">';
                if ($current_price > 0) {
                    $html .= '$' . number_format($current_price, 2);
                } else {
                    $html .= '<em style="color: #666;">Free</em>';
                }
                $html .= '</div>';

                $html .= '<div class="price-edit" style="display:none;">';
                $html .= '<input type="number" step="0.01" min="0" class="price-input" ';
                $html .= 'value="' . ($current_price > 0 ? number_format($current_price, 2, '.', '') : '') . '" ';
                $html .= 'placeholder="0.00" style="width:70px;" />';
                $html .= '<button type="button" class="button button-small save-price" style="margin-left:3px;">Save</button>';
                $html .= '<button type="button" class="button button-small cancel-price" style="margin-left:3px;">Cancel</button>';
                $html .= '</div>';

                $html .= '<div class="price-actions" style="margin-top:3px;">';
                $html .= '<button type="button" class="button button-small edit-price">Edit Price</button>';
                $html .= '</div>';

                $html .= '</div>';

                return $html;

            case 'location_name':
                return !empty($item->location_id) ? esc_html($this->booking_crud->get_location_name($item->location_id)) : __('N/A', 'organization-core');

            case 'parks_selection':
                if (!empty($item->parks_selection)) {
                    $parks_array = json_decode($item->parks_selection, true);
                    if (is_array($parks_array) && !empty($parks_array)) {
                        $park_names = array();
                        foreach ($parks_array as $park_id) {
                            if ($park_id === 'other' && !empty($item->other_park_name)) {
                                $park_names[] = 'Other: ' . $item->other_park_name;
                            } else {
                                $term = get_term($park_id, 'parks');
                                if ($term && !is_wp_error($term)) {
                                    $park_names[] = $term->name;
                                }
                            }
                        }

                        if (!empty($park_names)) {
                            $display_parks = array_slice($park_names, 0, 2);
                            $parks_text = implode(', ', $display_parks);
                            if (count($park_names) > 2) {
                                $parks_text .= '<br><span class="description">(+' . (count($park_names) - 2) . ' more)</span>';
                            }
                            return $parks_text;
                        }
                    }
                }
                return __('N/A', 'organization-core');

            case 'festival_date':
                return !empty($item->date_selection) ? date('M j, Y', strtotime($item->date_selection)) : __('N/A', 'organization-core');

            case 'booking_date':
                return date('M j, Y \a\t g:i A', strtotime($item->created_at));

            case 'actions':
                return $this->column_actions($item);

            default:
                return '';
        }
    }

    /**
     * Render Actions column - WITH PENDING option added
     */
    public function column_actions($item)
    {
        $booking_id = $item->id;
        $current_status = !empty($item->status) ? $item->status : 'pending';

        ?>
        <select class="booking-action-select"
            data-booking-id="<?php echo $booking_id; ?>"
            onchange="handleBookingAction(this)"
            style="padding: 6px 12px; 
                   font-size: 13px; 
                   border: 1px solid #2271b1; 
                   border-radius: 3px; 
                   background-color: white;
                   cursor: pointer;
                   min-width: 120px;">

            <option value="pending" <?php selected($current_status, 'pending'); ?>>Pending</option>
            <option value="confirmed" <?php selected($current_status, 'confirmed'); ?>>Confirm</option>
            <option value="completed" <?php selected($current_status, 'completed'); ?>>Complete</option>
            <option value="cancelled" <?php selected($current_status, 'cancelled'); ?>>Cancel</option>
            <option value="delete" style="color: #dc3545;">Delete</option>
        </select>
    <?php
    }


    /**
     * Add JavaScript for dropdown actions and price editing
     * UPDATED with Pending status support
     */
    public function admin_footer_scripts()
    {
    ?>
        <script type="text/javascript">
            // ============================================
            // STATUS CHANGE FUNCTIONALITY (WITH PENDING)
            // ============================================
            function handleBookingAction(selectElement) {
                const action = selectElement.value;
                const bookingId = selectElement.getAttribute('data-booking-id');

                if (action === 'delete') {
                    deleteBooking(bookingId, selectElement);
                } else {
                    updateBookingStatus(bookingId, action, selectElement);
                }
            }

            function updateBookingStatus(bookingId, newStatus, selectElement) {
                const statusNames = {
                    'pending': 'Pending',
                    'confirmed': 'Confirmed',
                    'completed': 'Completed',
                    'cancelled': 'Cancelled'
                };

                if (!confirm('Change status to "' + statusNames[newStatus] + '"?')) {
                    location.reload();
                    return;
                }

                jQuery.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'update_booking_status',
                        booking_id: bookingId,
                        new_status: newStatus,
                        nonce: '<?php echo wp_create_nonce('update_booking_status_nonce'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            alert('✅ Status updated to ' + statusNames[newStatus]);
                            location.reload();
                        } else {
                            alert('❌ Error: ' + (response.data ? response.data.message : 'Unknown error'));
                            location.reload();
                        }
                    },
                    error: function() {
                        alert('❌ Network error');
                        location.reload();
                    }
                });
            }

            function deleteBooking(bookingId, selectElement) {
                if (!confirm('⚠️ DELETE this booking permanently?\n\nThis CANNOT be undone!')) {
                    location.reload();
                    return;
                }

                jQuery.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'delete_booking',
                        booking_id: bookingId,
                        nonce: '<?php echo wp_create_nonce('delete_booking_nonce'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            alert('✅ Booking deleted');
                            location.reload();
                        } else {
                            alert('❌ Delete failed');
                            location.reload();
                        }
                    },
                    error: function() {
                        alert('❌ Network error');
                        location.reload();
                    }
                });
            }

            // ============================================
            // PRICE EDIT FUNCTIONALITY
            // ============================================
            jQuery(document).ready(function($) {
                // Show price edit form
                $(document).on('click', '.edit-price', function(e) {
                    e.preventDefault();
                    var container = $(this).closest('.price-container');
                    container.find('.price-display').hide();
                    container.find('.price-actions').hide();
                    container.find('.price-edit').show();
                    container.find('.price-input').focus().select();
                });

                // Cancel price edit
                $(document).on('click', '.cancel-price', function(e) {
                    e.preventDefault();
                    var container = $(this).closest('.price-container');
                    container.find('.price-edit').hide();
                    container.find('.price-display').show();
                    container.find('.price-actions').show();
                });

                // Save price via AJAX
                $(document).on('click', '.save-price', function(e) {
                    e.preventDefault();

                    var container = $(this).closest('.price-container');
                    var bookingId = container.data('booking-id');
                    var priceInput = container.find('.price-input');
                    var price = parseFloat(priceInput.val());

                    if (isNaN(price) || price < 0) {
                        alert('Please enter a valid price (0 or greater).');
                        return;
                    }

                    var saveBtn = $(this);
                    var originalText = saveBtn.text();
                    saveBtn.prop('disabled', true).text('Saving...');

                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'save_booking_price',
                            nonce: '<?php echo wp_create_nonce('booking_price_nonce'); ?>',
                            booking_id: bookingId,
                            price: price
                        },
                        success: function(response) {
                            if (response.success) {
                                container.find('.price-display').html(response.data.display_price);
                                container.find('.price-edit').hide();
                                container.find('.price-display').show();
                                container.find('.price-actions').show();
                                alert('✅ ' + response.data.message);
                            } else {
                                alert('❌ ' + (response.data || 'Failed to update price.'));
                            }
                        },
                        error: function() {
                            alert('❌ Network error. Please try again.');
                        },
                        complete: function() {
                            saveBtn.prop('disabled', false).text(originalText);
                        }
                    });
                });

                // Allow Enter key to save price
                $(document).on('keypress', '.price-input', function(e) {
                    if (e.which === 13) {
                        e.preventDefault();
                        $(this).siblings('.save-price').click();
                    }
                });
            });
        </script>

        <style>
            .price-container {
                min-width: 100px;
            }

            .price-edit {
                white-space: nowrap;
            }

            .booking-action-select:focus {
                outline: none;
                box-shadow: 0 0 0 1px #2271b1;
            }
        </style>
<?php
    }
}
