<?php

if (!class_exists('WP_List_Table')) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}
if (!is_admin()) {
    return;
}

/**
 * Generic WP_List_Table wrapper for post-type based lists.
 */
class Generic_WP_List_Table extends WP_List_Table
{
    protected $config = [];

    public function __construct(array $config = [])
    {
        $defaults = [
            'singular' => __('Item', ''),
            'plural' => __('Items', ''),
            'post_type' => 'post',
            'text_domain' => '',
            'columns' => [
                'cb' => '<input type="checkbox" />'
            ],
            'sortable_columns' => [],
            'per_page_option' => 'generic_items_per_page',
            'page_slug' => null,
            'fetch_callback' => null,
            'total_callback' => null,
            'column_callbacks' => [],
            'bulk_actions' => [],
            'process_bulk_callback' => null,
        ];

        $this->config = array_merge($defaults, $config);

        parent::__construct([
            'singular' => $this->config['singular'],
            'plural' => $this->config['plural'],
            'ajax' => false
        ]);

        // Add inline scripts to admin footer for AJAX functionality
        add_action('admin_footer', array($this, 'admin_footer_scripts'));
    }

    /**
     * Utility: admin notice.
     */
    public function set_wp_admin_notice($message, $type = 'info')
    {
        if (function_exists('wp_admin_notice')) {
            wp_admin_notice($message, ['type' => $type, 'dismissible' => true]);
            return;
        }
        // fallback
        add_action('admin_notices', function () use ($message, $type) {
            printf('<div class="notice notice-%s is-dismissible"><p>%s</p></div>', esc_attr($type), esc_html($message));
        });
    }

    /**
     * Prepare items (main entry).
     */
    public function prepare_items()
    {
        $columns = $this->get_columns();
        $hidden = get_hidden_columns(get_current_screen());
        $sortable = $this->get_sortable_columns();

        $this->_column_headers = [$columns, $hidden, $sortable];

        $per_page = $this->get_items_per_page($this->config['per_page_option'], 20);
        $current_page = $this->get_pagenum();
        $total_items = $this->get_total_items();

        $search = isset($_REQUEST['s']) ? wp_unslash(trim($_REQUEST['s'])) : '';

        // Process bulk actions if any
        $this->process_bulk_action();

        // Fetch items
        if (is_callable($this->config['fetch_callback'])) {
            $this->items = call_user_func($this->config['fetch_callback'], $per_page, $current_page, $search, $this->config);
        } else {
            return $this->no_items();
        }

        $this->set_pagination_args([
            'total_items' => $total_items,
            'per_page' => $per_page,
            'total_pages' => ($per_page > 0 ? ceil($total_items / $per_page) : 0),
        ]);
    }

    protected function display_tablenav($which)
    {
        if ('top' === $which) {
            wp_nonce_field('bulk-' . $this->_args['plural']);
?>
            <div class="tablenav <?php echo esc_attr($which); ?>">
                <?php if ($this->has_items()): ?>
                    <div class="alignleft actions bulkactions">
                        <div style="margin: 10px 0">
                            <fieldset>
                                <legend>Actions:</legend>
                                <?php $this->bulk_actions($which); ?>
                            </fieldset>
                        </div>
                    </div>
                <?php
                endif;
                $this->extra_tablenav($which);
                ?>
                <br class="clear" />
            </div>
        <?php } else { ?>
            <div class="tablenav <?php echo esc_attr($which); ?>">
                <?php
                $this->pagination($which);
                ?>
                <br class="clear" />
            </div>
        <?php }
    }

    /**
     * Display filters
     */
    public function extra_tablenav($which)
    {
        if ($which == "top") {
            $selected_package = isset($_REQUEST['package_filter']) ? $_REQUEST['package_filter'] : '';
            $festival_date_from = isset($_REQUEST['festival_date_from']) ? $_REQUEST['festival_date_from'] : '';
            $festival_date_to = isset($_REQUEST['festival_date_to']) ? $_REQUEST['festival_date_to'] : '';
            $booking_date_from = isset($_REQUEST['booking_date_from']) ? $_REQUEST['booking_date_from'] : '';
            $booking_date_to = isset($_REQUEST['booking_date_to']) ? $_REQUEST['booking_date_to'] : '';
            $search = isset($_REQUEST['s']) ? wp_unslash(trim($_REQUEST['s'])) : '';

            $has_active_filters = !empty($selected_package) || !empty($festival_date_from) || !empty($festival_date_to) || !empty($booking_date_from) || !empty($booking_date_to) || !empty($search);

            $packages = get_posts(array(
                'post_type' => 'packages',
                'posts_per_page' => -1,
                'post_status' => 'publish'
            ));
        ?>
            <div class="alignleft actions">
                <div style="display: inline-flex; gap: 10px;align-items: flex-end;margin: 10px 0">
                    <fieldset>
                        <legend>Package:</legend>
                        <select name="package_filter" id="package-filter" style="float: none;">
                            <option value=""><?php _e('All Packages', 'organization-core'); ?></option>
                            <?php foreach ($packages as $package): ?>
                                <option value="<?php echo $package->ID; ?>" <?php selected($selected_package, $package->ID); ?>>
                                    <?php echo esc_html($package->post_title); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </fieldset>

                    <fieldset>
                        <legend><?php _e('Festival starts:', 'organization-core'); ?></legend>
                        <input type="date" name="festival_date_from" id="festival-date-from"
                            value="<?php echo esc_attr($festival_date_from); ?>">
                    </fieldset>
                    <fieldset>
                        <legend><?php _e('Festival ends:', 'organization-core'); ?></legend>
                        <input type="date" name="festival_date_to" id="festival-date-to"
                            value="<?php echo esc_attr($festival_date_to); ?>">
                    </fieldset>

                    <?php
                    $filter_disabled = empty($selected_package) && empty($festival_date_from) && empty($festival_date_to) && empty($booking_date_from) && empty($booking_date_to);
                    ?>
                    <div>
                        <button type="submit" name="filter_action" value="filter" class="button button-primary" <?php echo $filter_disabled ? 'disabled' : ''; ?>>
                            <?php _e('Filter', 'organization-core'); ?>
                        </button>
                        <button type="button" onclick="location.href='<?php echo admin_url('admin.php?page=organization-bookings'); ?>'" name="filter_action" value="reset" class="button <?php echo $has_active_filters ? '' : 'disabled'; ?>">
                            <?php _e('Reset', 'organization-core'); ?>
                        </button>
                    </div>
                </div>

                <script type="text/javascript">
                    jQuery(document).ready(function($) {
                        function toggleFilterButton() {
                            var packageSelected = $('#package-filter').val() !== '';
                            var festivalDateFromSelected = $('#festival-date-from').val() !== '';
                            var festivalDateToSelected = $('#festival-date-to').val() !== '';
                            var bookingDateFromSelected = $('#booking-date-from').val() !== '';
                            var bookingDateToSelected = $('#booking-date-to').val() !== '';

                            var hasSelection = packageSelected || festivalDateFromSelected || festivalDateToSelected || bookingDateFromSelected || bookingDateToSelected;
                            var filterBtn = $('button[name="filter_action"]');

                            filterBtn.prop('disabled', !hasSelection);
                        }

                        toggleFilterButton();
                        $('#package-filter, #festival-date-from, #festival-date-to, #booking-date-from, #booking-date-to').on('change', toggleFilterButton);
                    });
                </script>
            </div>
        <?php
        }
    }

    /**
     * Columns as provided in config.
     */
    public function get_columns()
    {
        return $this->config['columns'];
    }

    /**
     * Get total items count
     */
    private function get_total_items()
    {
        if (is_callable($this->config['total_callback'])) {
            $search = isset($_REQUEST['s']) ? wp_unslash(trim($_REQUEST['s'])) : '';
            return call_user_func($this->config['total_callback'], $search, $this->config);
        }
        return 0;
    }

    /**
     * Sortable columns from config.
     */
    protected function get_sortable_columns()
    {
        return $this->config['sortable_columns'];
    }

    /**
     * Checkbox column.
     */
    public function column_cb($item)
    {
        if (isset($item->id)) {
            return sprintf('<input type="checkbox" name="items[]" value="%s" />', esc_attr($item->id));
        }
        return '';
    }

    public function fetch_data($per_page, $current_page, $search, $config)
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

        if (!empty($_REQUEST['booking_date_from'])) {
            $where_conditions[] = "DATE(b.created_at) >= %s";
            $search_params[] = sanitize_text_field($_REQUEST['booking_date_from']);
        }

        if (!empty($_REQUEST['booking_date_to'])) {
            $where_conditions[] = "DATE(b.created_at) <= %s";
            $search_params[] = sanitize_text_field($_REQUEST['booking_date_to']);
        }

        if ($search) {
            $where_conditions[] = "(u.display_name LIKE %s OR u.user_email LIKE %s)";
            $like_search = '%' . $wpdb->esc_like($search) . '%';
            $search_params[] = sanitize_text_field($like_search);
            $search_params[] = sanitize_text_field($like_search);
        }

        $where_clause = ' WHERE ' . implode(' AND ', $where_conditions);

        $offset = ($current_page - 1) * $per_page;

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
     * Default column renderer fallback.
     */
    public function column_default($item, $column_name, $search = '')
    {
        // If there is a column callback for this column, call it
        if (isset($this->config['column_callbacks'][$column_name]) && is_callable($this->config['column_callbacks'][$column_name])) {
            return call_user_func($this->config['column_callbacks'][$column_name], $item, $column_name, $this->config);
        }

        return $this->get_booking_columns_data($item, $column_name, $search);
    }

    public function get_booking_columns_data($item, $column_name, $search = '')
    {
        switch ($column_name) {
            case 'id':
                $view_url = add_query_arg(array(
                    'page' => 'organization-bookings',
                    'action' => 'view',
                    'booking_id' => $item->id
                ), admin_url('admin.php'));

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
                // Improved price column with stacked layout
                $current_price = isset($item->total_amount) ? floatval($item->total_amount) : 0;

                $html = '<div class="price-container" data-booking-id="' . $item->id . '" style="min-width: 110px;">';

                // Display mode
                $html .= '<div class="price-display">';
                if ($current_price > 0) {
                    $html .= '<div><strong>$' . number_format($current_price, 2) . '</strong></div>';
                } else {
                    $html .= '<div><span style="color: #787c82;">Free</span></div>';
                }
                $html .= '<div style="margin-top: 4px;">';
                $html .= '<a href="#" class="edit-price">Edit Price</a>';
                $html .= '</div>';
                $html .= '</div>';

                // Edit mode (hidden by default)
                $html .= '<div class="price-edit" style="display:none;">';
                $html .= '<div style="margin-bottom: 5px;">';
                $html .= '<input type="number" step="0.01" min="0" class="price-input small-text" ';
                $html .= 'value="' . ($current_price > 0 ? number_format($current_price, 2, '.', '') : '') . '" ';
                $html .= 'placeholder="0.00" style="width: 90px; margin: 0;" />';
                $html .= '</div>';
                $html .= '<div>';
                $html .= '<button type="button" class="button button-small save-price">Save</button> ';
                $html .= '<a href="#" class="cancel-price">Cancel</a>';
                $html .= '</div>';
                $html .= '</div>';

                $html .= '</div>';

                return $html;

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

            default:
                return '';
        }
    }

    /**
     * Actions column with improved UI
     */
    public function column_actions($item)
    {
        $booking_id = $item->id;
        $current_status = !empty($item->status) ? $item->status : 'pending';

        $status_labels = array(
            'pending' => __('Pending', 'organization-core'),
            'confirmed' => __('Confirmed', 'organization-core'),
            'completed' => __('Completed', 'organization-core'),
            'cancelled' => __('Cancelled', 'organization-core')
        );

        $current_label = isset($status_labels[$current_status]) ? $status_labels[$current_status] : __('Pending', 'organization-core');

        // Status badge colors
        $status_colors = array(
            'pending' => '#996800',
            'confirmed' => '#00772e',
            'completed' => '#2c3338',
            'cancelled' => '#b32d2e'
        );

        $badge_color = isset($status_colors[$current_status]) ? $status_colors[$current_status] : '#996800';

        ob_start();
        ?>
        <div class="action-container" data-booking-id="<?php echo $booking_id; ?>" style="min-width: 120px;">
            <!-- Display mode -->
            <div class="action-display">
                <div>
                    <span style="display: inline-block; padding: 2px 8px; background: <?php echo $badge_color; ?>; color: white; border-radius: 3px; font-size: 11px; font-weight: 500;">
                        <?php echo esc_html($current_label); ?>
                    </span>
                </div>
                <div style="margin-top: 6px;">
                    <a href="#" class="change-status" data-booking-id="<?php echo $booking_id; ?>"
                        data-current-status="<?php echo esc_attr($current_status); ?>">
                        Change
                    </a>
                    <span style="color: #dcdcde;"> | </span>
                    <a href="#" class="delete-booking" data-booking-id="<?php echo $booking_id; ?>"
                        style="color: #b32d2e;">
                        Delete
                    </a>
                </div>
            </div>

            <!-- Edit mode (hidden by default) -->
            <div class="action-edit" style="display:none;">
                <div style="margin-bottom: 5px;">
                    <select class="status-dropdown" style="width: 100%;">
                        <option value="pending" <?php selected($current_status, 'pending'); ?>><?php _e('Pending', 'organization-core'); ?></option>
                        <option value="confirmed" <?php selected($current_status, 'confirmed'); ?>><?php _e('Confirmed', 'organization-core'); ?></option>
                        <option value="completed" <?php selected($current_status, 'completed'); ?>><?php _e('Completed', 'organization-core'); ?></option>
                        <option value="cancelled" <?php selected($current_status, 'cancelled'); ?>><?php _e('Cancelled', 'organization-core'); ?></option>
                    </select>
                </div>
                <div>
                    <button type="button" class="button button-small update-status">Update</button>
                    <a href="#" class="cancel-status" style="margin-left: 5px;">Cancel</a>
                </div>
            </div>
        </div>
    <?php
        return ob_get_clean();
    }

    public function get_total()
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

        if (!empty($_REQUEST['booking_date_from'])) {
            $where_conditions[] = "DATE(b.created_at) >= %s";
            $search_params[] = sanitize_text_field($_REQUEST['booking_date_from']);
        }

        if (!empty($_REQUEST['booking_date_to'])) {
            $where_conditions[] = "DATE(b.created_at) <= %s";
            $search_params[] = sanitize_text_field($_REQUEST['booking_date_to']);
        }

        $where_clause = ' WHERE ' . implode(' AND ', $where_conditions);

        $query = "SELECT COUNT(*) FROM {$table_name} b {$where_clause}";

        return $wpdb->get_var($wpdb->prepare($query, $search_params));
    }

    public function no_items()
    {
        _e('No items found.', $this->config['text_domain']);
    }

    /**
     * Bulk actions from config.
     */
    public function get_bulk_actions()
    {
        return $this->config['bulk_actions'];
    }

    /**
     * Process bulk actions
     */
    public function process_bulk_action()
    {
        $blog_id = get_current_blog_id();
        $action = $this->current_action();
        $request_ids = isset($_POST['items']) ? wp_parse_id_list(wp_unslash($_POST['items'])) : array();

        if (empty($action) || empty($request_ids)) {
            return;
        }

        // nonce check for safety
        check_admin_referer('bulk-' . $this->_args['plural']);

        if (is_callable($this->config['process_bulk_callback'])) {
            call_user_func($this->config['process_bulk_callback'], $action, $request_ids, $this->config);
            return;
        }

        // Default: delete action
        if ('delete' === $action || 'bulk-delete' === $action) {
            $count = 0;
            $failures = 0;
            foreach ($request_ids as $id) {
                if (OC_Bookings_CRUD::delete_booking($id, $blog_id)) {
                    ++$count;
                } else {
                    ++$failures;
                }
            }

            if ($count > 0) {
                $this->set_wp_admin_notice(
                    sprintf(_n('%d booking deleted successfully.', '%d bookings deleted successfully.', $count, $this->config['text_domain']), $count),
                    'success'
                );
            }
            if ($failures > 0) {
                $this->set_wp_admin_notice(
                    sprintf(_n('%d booking delete failed.', '%d bookings not deleted.', $failures, $this->config['text_domain']), $failures),
                    'error'
                );
            }
        }
    }

    /**
     * Search box
     */
    public function search_box($text, $input_id)
    {
        if (empty($_REQUEST['s']) && !$this->has_items()) {
            return;
        }

        // preserve other query vars
        foreach ($_REQUEST as $key => $value) {
            if ('s' === $key || 'paged' === $key) {
                continue;
            }
            if (!is_array($value)) {
                echo '<input type="hidden" name="' . esc_attr($key) . '" value="' . esc_attr($value) . '" />';
            } else {
                foreach ($value as $subk => $subv) {
                    echo '<input type="hidden" name="' . esc_attr($key) . '[' . esc_attr($subk) . ']" value="' . esc_attr($subv) . '" />';
                }
            }
        }

        $input_id = $input_id . '-search-input';
    ?>
        <p class="search-box">
            <label class="screen-reader-text" for="<?php echo esc_attr($input_id); ?>"><?php echo esc_html($text); ?>:</label>
            <input type="search" id="<?php echo esc_attr($input_id); ?>" name="s" value="<?php _admin_search_query(); ?>" />
            <?php submit_button($text, '', '', false, array('id' => 'search-submit')); ?>
        </p>
    <?php
    }

    public function admin_footer_scripts()
    {
    ?>
        <script type="text/javascript">
            jQuery(document).ready(function($) {
                // ============ PRICE EDIT FUNCTIONALITY ============

                // Show price edit form
                $(document).on('click', '.edit-price', function(e) {
                    e.preventDefault();
                    var container = $(this).closest('.price-container');
                    container.find('.price-display').hide();
                    container.find('.price-edit').show();
                    container.find('.price-input').focus().select();
                });

                // Cancel price edit
                $(document).on('click', '.cancel-price', function(e) {
                    e.preventDefault();
                    var container = $(this).closest('.price-container');
                    container.find('.price-edit').hide();
                    container.find('.price-display').show();
                });

                // Save price via AJAX
                $(document).on('click', '.save-price', function(e) {
                    e.preventDefault();
                    var container = $(this).closest('.price-container');
                    var bookingId = container.data('booking-id');
                    var priceInput = container.find('.price-input');
                    var price = parseFloat(priceInput.val());

                    if (isNaN(price)) {
                        price = 0;
                    }

                    if (price < 0) {
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
                                // Update display with new price
                                var displayHtml = price > 0 ?
                                    '<div><strong>$' + price.toFixed(2) + '</strong></div>' :
                                    '<div><span style="color: #787c82;">Free</span></div>';
                                displayHtml += '<div style="margin-top: 4px;"><a href="#" class="edit-price">Edit Price</a></div>';

                                container.find('.price-display').html(displayHtml);
                                container.find('.price-edit').hide();
                                container.find('.price-display').show();

                                // Show success message briefly
                                var successMsg = $('<span class="dashicons dashicons-yes" style="color: #00a32a; margin-left: 5px;"></span>');
                                container.find('.price-display').append(successMsg);
                                setTimeout(function() {
                                    successMsg.fadeOut(function() {
                                        $(this).remove();
                                    });
                                }, 2000);
                            } else {
                                alert(response.data || 'Failed to update price.');
                                container.find('.price-edit').hide();
                                container.find('.price-display').show();
                            }
                        },
                        error: function() {
                            alert('Network error. Please try again.');
                            container.find('.price-edit').hide();
                            container.find('.price-display').show();
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
                        $(this).closest('.price-edit').find('.save-price').click();
                    }
                });

                // ============ STATUS CHANGE FUNCTIONALITY ============

                // Show status change form
                $(document).on('click', '.change-status', function(e) {
                    e.preventDefault();
                    var container = $(this).closest('.action-container');
                    container.find('.action-display').hide();
                    container.find('.action-edit').show();
                    container.find('.status-dropdown').focus();
                });

                // Cancel status change
                $(document).on('click', '.cancel-status', function(e) {
                    e.preventDefault();
                    var container = $(this).closest('.action-container');
                    container.find('.action-edit').hide();
                    container.find('.action-display').show();
                });

                // Update status via AJAX
                $(document).on('click', '.update-status', function(e) {
                    e.preventDefault();
                    var container = $(this).closest('.action-container');
                    var bookingId = container.data('booking-id');
                    var newStatus = container.find('.status-dropdown').val();

                    var statusNames = {
                        'pending': 'Pending',
                        'confirmed': 'Confirmed',
                        'completed': 'Completed',
                        'cancelled': 'Cancelled'
                    };

                    if (!confirm('Change status to ' + statusNames[newStatus] + '?')) {
                        return;
                    }

                    var updateBtn = $(this);
                    var originalText = updateBtn.text();
                    updateBtn.prop('disabled', true).text('Updating...');

                    $.ajax({
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
                                location.reload();
                            } else {
                                alert('Error: ' + (response.data ? response.data.message : 'Unknown error'));
                                container.find('.action-edit').hide();
                                container.find('.action-display').show();
                            }
                        },
                        error: function() {
                            alert('Network error');
                            container.find('.action-edit').hide();
                            container.find('.action-display').show();
                        },
                        complete: function() {
                            updateBtn.prop('disabled', false).text(originalText);
                        }
                    });
                });

                // Delete booking
                $(document).on('click', '.delete-booking', function(e) {
                    e.preventDefault();
                    var bookingId = $(this).data('booking-id');

                    if (!confirm('DELETE this booking permanently? This CANNOT be undone!')) {
                        return;
                    }

                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'delete_booking',
                            booking_id: bookingId,
                            nonce: '<?php echo wp_create_nonce('delete_booking_nonce'); ?>'
                        },
                        success: function(response) {
                            if (response.success) {
                                location.reload();
                            } else {
                                alert('Delete failed');
                            }
                        },
                        error: function() {
                            alert('Network error');
                        }
                    });
                });
            });
        </script>
<?php
    }
}

// Initialize the table with configuration
$columns = [
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
];

$sortable = [
    'id' => array('id', true),
    'festival_date' => array('festival_date', false),
    'booking_date' => array('booking_date', false),
];

$table = new Generic_WP_List_Table([
    'singular' => __('Booking', 'organization-core'),
    'plural' => __('Bookings', 'organization-core'),
    'post_type' => 'bookings',
    'text_domain' => 'organization-core',
    'columns' => $columns,
    'sortable_columns' => $sortable,
    'per_page_option' => 'bookings_per_page',
    'page_slug' => 'organization-bookings',
    'bulk_actions' => ['bulk-delete' => __('Delete', 'organization-core')],
    'fetch_callback' => ['Generic_WP_List_Table', 'fetch_data'],
    'total_callback' => ['Generic_WP_List_Table', 'get_total'],
    'column_callbacks' => [
        'cb' => ['Generic_WP_List_Table', 'column_cb'],
        'location_name' => function ($item, $column_name, $config) {
            return !empty($item->location_id) ? esc_html(OC_Bookings_CRUD::get_location_name($item->location_id)) : __('N/A', 'organization-core');
        },
        'actions' => ['Generic_WP_List_Table', 'column_actions']
    ]
]);

echo '<div class="wrap"><h1>' . esc_html__('Bookings', 'organization-core') . '</h1>';
?>
<form method="post">
    <input type="hidden" name="page" value="organization-bookings" />
    <?php
    $table->prepare_items();
    $table->search_box(__('Search Bookings', 'organization-core'), 'bookings-search');
    $table->display();
    ?>
</form>
</div>