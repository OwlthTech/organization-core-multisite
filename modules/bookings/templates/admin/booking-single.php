<?php
if (!defined('WPINC')) {
    die;
}

$booking_id = intval($_GET['booking_id']);
$blog_id = get_current_blog_id();

// Load booking using CRUD
$booking = $args['booking_crud']->get_booking($booking_id, $blog_id);

if (!$booking) {
echo '<div class="wrap">';
    echo '<h1>' . __('Booking Not Found', 'organization-core') . '</h1>';
    echo '<div class="notice notice-error">
        <p>' . __('The requested booking could not be found.', 'organization-core') . '</p>
    </div>';
    echo '<a href="' . admin_url('admin.php?page=organization-bookings') . '" class="button">' . __('← Back to All
        Bookings', 'organization-core') . '</a>';
    echo '</div>';
return;
}
// Use the actual current screen id for metabox registration/rendering
$screen_obj = function_exists('get_current_screen') ? get_current_screen() : null;
$screen = $screen_obj ? $screen_obj->id : 'toplevel_page_organization-bookings';
$user = get_userdata($booking['user_id']);

// Metaboxes are already registered in register_screen_options() hook
// No need to register again here
// Render page with metaboxes
?>
<div class="wrap">
    <h1 class="wp-heading-inline">
        <?php printf(__('Booking #%d', 'organization-core'), $booking_id); ?>

    </h1>

    <hr class="wp-header-end">

    <form method="post" action="">
        <?php wp_nonce_field('save_booking_detail', 'booking_detail_nonce'); ?>
        <?php wp_nonce_field('closedpostboxes', 'closedpostboxesnonce', false); ?>
        <?php wp_nonce_field('meta-box-order', 'meta-box-order-nonce', false); ?>

        <div id="poststuff">
            <div id="post-body" class="metabox-holder columns-<?php echo 1 == get_current_screen()->get_columns() ? '1' : '2'; ?>">

                <!-- Main column -->
                <!-- Normal metaboxes (left column) -->
                <div id="post-body-content">
                    <?php do_meta_boxes($screen, 'normal', null); ?>
                </div>
                <!-- Side metaboxes (right column) -->
                <div id="postbox-container-1" class="postbox-container">
                    <?php do_meta_boxes($screen, 'side', null); ?>
                </div>

            </div><!-- #post-body -->
        </div><!-- #poststuff -->
    </form>
</div>

<style>
    .booking-status-badge {
        display: inline-block;
        border-radius: 4px;
        padding: 2px 4px;
        border: 1px solid;
    }

    .status-pending {
        color: #f0ad4e;
        border-color: #f0ad4e;
    }

    .status-confirmed {
        color: #5cb85c;
        border-color: #5cb85c;
    }

    .status-completed {
        color: #0073aa;
        border-color: #0073aa;
    }

    .status-cancelled {
        color: #dc3545;
        border-color: #dc3545;
    }

    .form-table th {
        width: 200px;
        font-weight: 600;
        padding: 15px 10px;
    }

    .form-table td {
        padding: 15px 10px;
    }
</style>

<script>
    jQuery(document).ready(function ($) {
        // Activate metabox toggling
        if (typeof postboxes !== 'undefined') {
            postboxes.add_postbox_toggles('<?php echo esc_js($screen); ?>');
        }

        // Status change handler
        $('#booking-status-select').on('change', function () {
            var newStatus = $(this).val();
            var bookingId = $('.status-change-container').data('booking-id');

            if (!confirm('Are you sure you want to change status to "' + newStatus + '"?')) {
                location.reload();
                return;
            }

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'update_booking_status',
                    booking_id: bookingId,
                    new_status: newStatus,
                    nonce: '<?php echo wp_create_nonce('update_booking_status_nonce'); ?>'
                },
                success: function (response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert('Error: ' + response.data.message);
                        location.reload();
                    }
                }
            });
        });

        // Price update handler
        $('#update-price-btn').on('click', function () {
            var price = parseFloat($('#booking-price-input').val());
            var bookingId = $('.price-change-container').data('booking-id');

            if (isNaN(price) || price < 0) {
                alert('Please enter a valid price.');
                return;
            }

            var btn = $(this);
            btn.prop('disabled', true).text('Updating...');

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'save_booking_price',
                    booking_id: bookingId,
                    price: price,
                    nonce: '<?php echo wp_create_nonce('booking_price_nonce'); ?>'
                },
                success: function (response) {
                    if (response.success) {
                        alert('✓ ' + response.data.message);
                        location.reload();
                    } else {
                        alert('Error: ' + response.data);
                    }
                },
                complete: function () {
                    btn.prop('disabled', false).text('Update Price');
                }
            });
        });
    });
</script>