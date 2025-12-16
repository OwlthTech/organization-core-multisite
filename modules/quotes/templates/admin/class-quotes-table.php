<?php

/**
 * Quotes Admin Table Template
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('WP_List_Table')) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class OC_Quotes_Table extends WP_List_Table
{
    public function __construct()
    {
        parent::__construct(array(
            'singular' => 'quote',
            'plural'   => 'quotes',
            'ajax'     => false,
        ));
    }

    public function get_columns()
    {
        return array(
            'cb'           => '<input type="checkbox" />',
            'id'           => __('ID', 'organization-core'),
            'educator'     => __('Educator', 'organization-core'),
            'email'        => __('Email', 'organization-core'),
            'destination'  => __('Destination', 'organization-core'),
            'school'       => __('School', 'organization-core'),
            'meal'         => __('Meal', 'organization-core'),
            'transport'    => __('Transport', 'organization-core'),
            'submitted'    => __('Submitted', 'organization-core'),
        );
    }

    public function get_sortable_columns()
    {
        return array(
            'id'        => array('id', false),
            'educator'  => array('educator_name', false),
            'submitted' => array('created_at', false),
        );
    }

    public function prepare_items()
    {
        global $wpdb;
        $table_name = $wpdb->base_prefix . 'quotes';

        $per_page = 20;
        $current_page = $this->get_pagenum();
        $offset = ($current_page - 1) * $per_page;

        $orderby = isset($_GET['orderby']) ? sanitize_text_field($_GET['orderby']) : 'created_at';
        $order = isset($_GET['order']) && strtoupper($_GET['order']) === 'ASC' ? 'ASC' : 'DESC';

        // Build WHERE clause with filters
        $where = "blog_id = %d";
        $prepare_args = array(get_current_blog_id());

        if (isset($_GET['meal_filter']) && in_array($_GET['meal_filter'], array('yes', 'no'))) {
            $meal_value = $_GET['meal_filter'] === 'yes' ? 1 : 0;
            $where .= " AND meal_quote = %d";
            $prepare_args[] = $meal_value;
        }

        if (isset($_GET['transport_filter']) && in_array($_GET['transport_filter'], array('yes', 'no'))) {
            $transport_value = $_GET['transport_filter'] === 'yes' ? 1 : 0;
            $where .= " AND transportation_quote = %d";
            $prepare_args[] = $transport_value;
        }

        if (!empty($_GET['date_from'])) {
            $date_from = sanitize_text_field($_GET['date_from']);
            $where .= " AND DATE(created_at) >= %s";
            $prepare_args[] = $date_from;
        }

        if (!empty($_GET['date_to'])) {
            $date_to = sanitize_text_field($_GET['date_to']);
            $where .= " AND DATE(created_at) <= %s";
            $prepare_args[] = $date_to;
        }

        $quotes = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $table_name WHERE $where ORDER BY $orderby $order LIMIT %d OFFSET %d",
                array_merge($prepare_args, array($per_page, $offset))
            )
        );

        $total_items = $wpdb->get_var(
            $wpdb->prepare("SELECT COUNT(*) FROM $table_name WHERE $where", $prepare_args)
        );

        $this->items = $quotes;
        $this->set_pagination_args(array(
            'total_items' => $total_items,
            'per_page'    => $per_page,
            'total_pages' => ceil($total_items / $per_page),
        ));

        $this->_column_headers = array(
            $this->get_columns(),
            array(),
            $this->get_sortable_columns(),
        );
    }

    public function column_cb($quote)
    {
        return sprintf('<input type="checkbox" name="quote_id[]" value="%d" />', intval($quote->id));
    }

    public function column_id($quote)
    {
        $actions = array(
            'view' => sprintf(
                '<a href="%s">%s</a>',
                esc_url(admin_url('admin.php?page=quotes-table&action=view&id=' . intval($quote->id))),
                __('View', 'organization-core')
            ),
            'delete' => sprintf(
                '<a href="%s" class="submitdelete">%s</a>',
                wp_nonce_url(admin_url('admin.php?page=quotes-table&action=delete&id=' . intval($quote->id)), 'delete_quote'),
                __('Delete', 'organization-core')
            ),
        );

        return sprintf('%s %s', '<strong>#' . intval($quote->id) . '</strong>', $this->row_actions($actions));
    }

    public function column_educator($quote)
    {
        return esc_html($quote->educator_name);
    }

    public function column_email($quote)
    {
        return '<a href="mailto:' . esc_attr($quote->email) . '">' . esc_html($quote->email) . '</a>';
    }

    public function column_destination($quote)
    {
        return esc_html($quote->destination_name ?? '--');
    }

    public function column_school($quote)
    {
        return esc_html($quote->school_name ?? '--');
    }

    public function column_meal($quote)
    {
        return intval($quote->meal_quote) ? __('Yes', 'organization-core') : __('No', 'organization-core');
    }

    public function column_transport($quote)
    {
        return intval($quote->transportation_quote) ? __('Yes', 'organization-core') : __('No', 'organization-core');
    }

    public function column_submitted($quote)
    {
        return esc_html(date('M j, Y', strtotime($quote->created_at)));
    }

    public function no_items()
    {
        _e('No quotes found.', 'organization-core');
    }
}

// Render the admin page
global $wpdb;

// Handle view
if (isset($_REQUEST['action']) && $_REQUEST['action'] === 'view') {
    $quote_id = intval($_REQUEST['id']);
    $quote = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->base_prefix}quotes WHERE id = %d AND blog_id = %d",
        $quote_id,
        get_current_blog_id()
    ));

    if (!$quote) {
        wp_die(__('Quote not found.', 'organization-core'));
    }

?>
    <div class="wrap">
        <h1><?php _e('Quote Request #', 'organization-core');
            echo intval($quote->id); ?></h1>


        <table class="widefat striped">
            <tbody>
                <tr>
                    <td><strong><?php _e('Educator Name', 'organization-core'); ?></strong></td>
                    <td><?php echo esc_html($quote->educator_name); ?></td>
                </tr>
                <tr>
                    <td><strong><?php _e('Position', 'organization-core'); ?></strong></td>
                    <td><?php echo esc_html($quote->position); ?></td>
                </tr>
                <tr>
                    <td><strong><?php _e('Email', 'organization-core'); ?></strong></td>
                    <td><a href="mailto:<?php echo esc_attr($quote->email); ?>"><?php echo esc_html($quote->email); ?></a></td>
                </tr>
                <tr>
                    <td><strong><?php _e('School Phone', 'organization-core'); ?></strong></td>
                    <td><?php echo esc_html($quote->school_phone ?? '--'); ?></td>
                </tr>
                <tr>
                    <td><strong><?php _e('Cell Phone', 'organization-core'); ?></strong></td>
                    <td><?php echo esc_html($quote->cell_phone ?? '--'); ?></td>
                </tr>
                <tr>
                    <td><strong><?php _e('Best Time to Reach', 'organization-core'); ?></strong></td>
                    <td><?php echo esc_html($quote->best_time_to_reach ?? '--'); ?></td>
                </tr>
                <tr>
                    <td><strong><?php _e('School Name', 'organization-core'); ?></strong></td>
                    <td><?php echo esc_html($quote->school_name); ?></td>
                </tr>
                <tr>
                    <td><strong><?php _e('School Address', 'organization-core'); ?></strong></td>
                    <td><?php echo nl2br(esc_html($quote->school_address)); ?></td>
                </tr>
                <tr>
                    <td><strong><?php _e('Destination', 'organization-core'); ?></strong></td>
                    <td><?php echo esc_html($quote->destination_name); ?></td>
                </tr>
                <tr>
                    <td><strong><?php _e('Meal Quote', 'organization-core'); ?></strong></td>
                    <td><?php echo intval($quote->meal_quote) ? __('Yes', 'organization-core') : __('No', 'organization-core'); ?></td>
                </tr>
                <tr>
                    <td><strong><?php _e('Transportation Quote', 'organization-core'); ?></strong></td>
                    <td><?php echo intval($quote->transportation_quote) ? __('Yes', 'organization-core') : __('No', 'organization-core'); ?></td>
                </tr>
                <tr>
                    <td><strong><?php _e('How Did You Hear About Us', 'organization-core'); ?></strong></td>
                    <td><?php echo esc_html($quote->hear_about_us ?? '--'); ?></td>
                </tr>
                <tr>
                    <td><strong><?php _e('Special Requests', 'organization-core'); ?></strong></td>
                    <td><?php echo nl2br(esc_html($quote->special_requests ?? __('None', 'organization-core'))); ?></td>
                </tr>
                <tr>
                    <td><strong><?php _e('Submitted Date', 'organization-core'); ?></strong></td>
                    <td><?php echo esc_html(date('F j, Y \a\t g:i A', strtotime($quote->created_at))); ?></td>
                </tr>
            </tbody>
        </table>

        <div style="margin-top: 20px;">
            <a href="<?php echo esc_url(admin_url('admin.php?page=quotes-table')); ?>" class="button">
                &larr; <?php _e('Back to List', 'organization-core'); ?>
            </a>
            <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=quotes-table&action=delete&id=' . intval($quote->id)), 'delete_quote'); ?>"
                class="button button-link-delete"
                onclick="return confirm('<?php esc_js(_e('Are you sure you want to delete this quote?', 'organization-core')); ?>')">
                <?php _e('Delete Quote', 'organization-core'); ?>
            </a>
        </div>
    </div>
<?php
    return;
}

// Handle delete
if (isset($_REQUEST['action']) && $_REQUEST['action'] === 'delete') {
    if (wp_verify_nonce($_REQUEST['_wpnonce'], 'delete_quote') && current_user_can('manage_options')) {
        $wpdb->delete(
            $wpdb->base_prefix . 'quotes',
            array('id' => intval($_REQUEST['id']), 'blog_id' => get_current_blog_id()),
            array('%d', '%d')
        );
        wp_redirect(admin_url('admin.php?page=quotes-table&updated=1'));
        exit;
    }
}

// Show table list
$table = new OC_Quotes_Table();
$table->prepare_items();

?>
<div class="wrap">
    <h1><?php _e('Quote Requests', 'organization-core'); ?></h1>

    <?php if (isset($_GET['updated']) && $_GET['updated'] === '1'): ?>
        <div class="notice notice-success is-dismissible">
            <p><?php _e('Quote deleted successfully.', 'organization-core'); ?></p>
        </div>
    <?php endif; ?>

    <!-- FILTERS -->
    <div class="tablenav top">
        <form method="get" style="float: left;">
            <input type="hidden" name="page" value="quotes-table">

            <label><?php _e('Meal Quote:', 'organization-core'); ?></label>
            <select name="meal_filter">
                <option value=""><?php _e('All', 'organization-core'); ?></option>
                <option value="yes" <?php selected($_GET['meal_filter'] ?? '', 'yes'); ?>>
                    <?php _e('Yes', 'organization-core'); ?>
                </option>
                <option value="no" <?php selected($_GET['meal_filter'] ?? '', 'no'); ?>>
                    <?php _e('No', 'organization-core'); ?>
                </option>
            </select>

            <label style="margin-left: 15px;"><?php _e('Transport Quote:', 'organization-core'); ?></label>
            <select name="transport_filter">
                <option value=""><?php _e('All', 'organization-core'); ?></option>
                <option value="yes" <?php selected($_GET['transport_filter'] ?? '', 'yes'); ?>>
                    <?php _e('Yes', 'organization-core'); ?>
                </option>
                <option value="no" <?php selected($_GET['transport_filter'] ?? '', 'no'); ?>>
                    <?php _e('No', 'organization-core'); ?>
                </option>
            </select>

            <label style="margin-left: 15px;"><?php _e('From:', 'organization-core'); ?></label>
            <input type="date" name="date_from" value="<?php echo esc_attr($_GET['date_from'] ?? ''); ?>">

            <label style="margin-left: 15px;"><?php _e('To:', 'organization-core'); ?></label>
            <input type="date" name="date_to" value="<?php echo esc_attr($_GET['date_to'] ?? ''); ?>">

            <input type="submit" class="button" value="<?php _e('Filter', 'organization-core'); ?>">
            <a href="<?php echo esc_url(admin_url('admin.php?page=quotes-table')); ?>" class="button">
                <?php _e('Reset', 'organization-core'); ?>
            </a>
        </form>
        <div class="clear"></div>
    </div>

    <!-- TABLE -->
    <form method="post" id="quotes-filter">
        <?php
        wp_nonce_field('quotes_admin_nonce');
        $table->display();
        ?>
    </form>
</div>