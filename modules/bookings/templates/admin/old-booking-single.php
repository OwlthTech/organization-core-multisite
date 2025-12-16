<?php
$booking = $args['booking'];
$booking_id = $args['booking_id'];
// Get additional data
$user = get_userdata($booking['user_id']);
$package = $booking['package_id'] ? get_post($booking['package_id']) : null;

// Parse parks
$parks_array = !empty($booking['parks_selection']) ? $booking['parks_selection'] : array();
$park_names = array();

$booking_crud = new OC_Bookings_CRUD();

if (is_array($parks_array)) {
    foreach ($parks_array as $park_id) {
        if ($park_id === 'other' && !empty($booking['other_park_name'])) {
            $park_names[] = __('Other:', 'organization-core') . ' ' . $booking['other_park_name'];
        } else {
            $term = get_term($park_id, 'parks');
            if ($term && !is_wp_error($term)) {
                $park_names[] = $term->name;
            }
        }
    }
}

// Parse meal options
$meal_options = !empty($booking['park_meal_options']) ? $booking['park_meal_options'] : array();

?>
<div class="wrap">
    <h1 class="wp-heading-inline">
        <?php printf(__('Booking #%d', 'organization-core'), $booking_id); ?>
        <span class="booking-status-badge status-<?php echo esc_attr($booking['status']); ?>">
            <?php echo esc_html(ucfirst($booking['status'])); ?>
        </span>
    </h1>

    <a href="<?php echo admin_url('admin.php?page=organization-bookings'); ?>" class="page-title-action">
        <?php _e('← Back to All Bookings', 'organization-core'); ?>
    </a>

    <hr class="wp-header-end">

    <div class="booking-details-wrapper">

        <!-- Main Content -->
        <div class="booking-main-content">

            <!-- Customer Information -->
            <div class="postbox">
                <div class="postbox-header">
                    <h2><?php _e('Customer Information', 'organization-core'); ?></h2>
                </div>
                <div class="inside">
                    <table class="form-table">
                        <tr>
                            <th><?php _e('Name', 'organization-core'); ?>:</th>
                            <td><strong><?php echo $user ? esc_html($user->display_name) : __('N/A', 'organization-core'); ?></strong></td>
                        </tr>
                        <tr>
                            <th><?php _e('Email', 'organization-core'); ?>:</th>
                            <td>
                                <?php if ($user): ?>
                                    <a href="mailto:<?php echo esc_attr($user->user_email); ?>">
                                        <?php echo esc_html($user->user_email); ?>
                                    </a>
                                <?php else: ?>
                                    <?php _e('N/A', 'organization-core'); ?>
                                <?php endif; ?>
                            </td>
                        </tr>

                    </table>
                </div>
            </div>

            <!-- Package & School Details -->
            <div class="postbox">
                <div class="postbox-header">
                    <h2><?php _e('Booking Details', 'organization-core'); ?></h2>
                </div>
                <div class="inside">
                    <table class="form-table">
                        <tr>
                            <th><?php _e('Package', 'organization-core'); ?>:</th>
                            <td>
                                <?php if ($package): ?>
                                    <strong><?php echo esc_html($package->post_title); ?></strong>
                                    <a href="<?php echo get_edit_post_link($package->ID); ?>" target="_blank" class="button button-small" style="margin-left:10px;">
                                        <?php _e('View Package', 'organization-core'); ?>
                                    </a>
                                <?php else: ?>
                                    <?php _e('N/A', 'organization-core'); ?>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <th><?php _e('School', 'organization-core'); ?>:</th>
                            <td>
                                <?php echo !empty($booking['school_id']) ? esc_html($booking_crud->get_school($booking['school_id'])['school_name']) : __('N/A', 'organization-core'); ?>
                                <?php if (!empty($booking['school_id'])): ?>
                                    <br><small style="color:#666;"><?php _e('School ID:', 'organization-core'); ?> <?php echo esc_html($booking['school_id']); ?></small>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <th><?php _e('Location', 'organization-core'); ?>:</th>
                            <td><?php echo !empty($booking['location_id']) ? esc_html($booking_crud->get_location_name($booking['location_id'])) : __('N/A', 'organization-core'); ?></td>
                        </tr>
                        <tr>
                            <th><?php _e('Festival Date', 'organization-core'); ?>:</th>
                            <td>
                                <?php
                                if (!empty($booking['date_selection'])) {
                                    echo '<strong>' . date('F j, Y', strtotime($booking['date_selection'])) . '</strong>';
                                } else {
                                    _e('N/A', 'organization-core');
                                }
                                ?>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- Parks Selection -->
            <div class="postbox">
                <div class="postbox-header">
                    <h2><?php _e('Parks Selection', 'organization-core'); ?></h2>
                </div>
                <div class="inside">
                    <?php if (!empty($park_names)): ?>
                        <ol>
                            <?php foreach ($park_names as $park): ?>
                                <li><?php echo esc_html($park); ?></li>
                            <?php endforeach; ?>
                        </ol>
                    <?php else: ?>
                        <p><?php _e('No parks selected', 'organization-core'); ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Group Details -->
            <div class="postbox">
                <div class="postbox-header">
                    <h2><?php _e('Group Information', 'organization-core'); ?></h2>
                </div>
                <div class="inside">
                    <table class="form-table">
                        <tr>
                            <th><?php _e('Total Students', 'organization-core'); ?>:</th>
                            <td><strong style="font-size:18px;color:#2271b1;"><?php echo intval($booking['total_students']); ?></strong></td>
                        </tr>
                        <tr>
                            <th><?php _e('Total Chaperones', 'organization-core'); ?>:</th>
                            <td><strong style="font-size:18px;color:#2271b1;"><?php echo intval($booking['total_chaperones']); ?></strong></td>
                        </tr>
                        <tr>
                            <th><?php _e('Total Attendees', 'organization-core'); ?>:</th>
                            <td><strong style="font-size:20px;color:#00a32a;"><?php echo intval($booking['total_students']) + intval($booking['total_chaperones']); ?></strong></td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- Meal Information -->
            <?php if ($booking['meal_vouchers']): ?>
                <div class="postbox">
                    <div class="postbox-header">
                        <h2><?php _e('Meal Information', 'organization-core'); ?></h2>
                    </div>
                    <div class="inside">
                        <table class="form-table">
                            <tr>
                                <th><?php _e('Meal Vouchers', 'organization-core'); ?>:</th>
                                <td><span style="color:#00a32a;font-weight:600;">✓ <?php _e('Yes', 'organization-core'); ?></span></td>
                            </tr>
                            <tr>
                                <th><?php _e('Meals Per Day', 'organization-core'); ?>:</th>
                                <td><strong><?php echo intval($booking['meals_per_day']); ?></strong></td>
                            </tr>
                            <?php if (!empty($meal_options) && is_array($meal_options)): ?>
                                <tr>
                                    <th><?php _e('Park Meal Options', 'organization-core'); ?>:</th>
                                    <td>
                                        <ul style="margin:0;padding-left:20px;">
                                            <?php foreach ($meal_options as $park_id => $meals): ?>
                                                <?php
                                                $park_term = get_term($park_id, 'parks');
                                                $park_name = ($park_term && !is_wp_error($park_term)) ? $park_term->name : $park_id;
                                                ?>
                                                <li><strong><?php echo esc_html($park_name); ?>:</strong> <?php echo intval($meals); ?> <?php _e('meals', 'organization-core'); ?></li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </table>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Transportation & Lodging -->
            <div class="postbox">
                <div class="postbox-header">
                    <h2><?php _e('Transportation & Lodging', 'organization-core'); ?></h2>
                </div>
                <div class="inside">
                    <table class="form-table">
                        <tr>
                            <th><?php _e('Transportation', 'organization-core'); ?>:</th>
                            <td>
                                <?php
                                if (!empty($booking['transportation'])) {
                                    echo $booking['transportation'] === 'own'
                                        ? __('We Will Provide Our Own Transportation', 'organization-core')
                                        : __('Please Provide A Quote', 'organization-core');
                                } else {
                                    _e('Own', 'organization-core');
                                }
                                ?>
                            </td>
                        </tr>
                        <?php if (!empty($booking['lodging_dates'])): ?>
                            <tr>
                                <th><?php _e('Lodging Dates', 'organization-core'); ?>:</th>
                                <td><?php echo esc_html($booking['lodging_dates']); ?></td>
                            </tr>
                        <?php endif; ?>
                    </table>
                </div>
            </div>

            <!-- Special Notes -->
            <?php if (!empty($booking['special_notes'])): ?>
                <div class="postbox">
                    <div class="postbox-header">
                        <h2><?php _e('Special Notes', 'organization-core'); ?></h2>
                    </div>
                    <div class="inside">
                        <div style="background:#f9f9f9;padding:15px;border-left:4px solid #2271b1;border-radius:3px;">
                            <?php echo nl2br(esc_html($booking['special_notes'])); ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

        </div>

        <!-- Sidebar -->
        <div class="booking-sidebar">

            <!-- Send Email Actions -->
            <div class="postbox">
                <div class="postbox-header">
                    <h2><?php _e('Email Actions', 'organization-core'); ?></h2>
                </div>
                <div class="inside">
                    <p>
                        <button type="button" id="send-user-email" class="button button-secondary" style="width:100%;margin-bottom:8px;"
                            data-booking-id="<?php echo esc_attr($booking_id); ?>"
                            data-nonce="<?php echo wp_create_nonce('send_booking_email_nonce'); ?>">
                            <?php _e('📧 Send Email to Customer', 'organization-core'); ?>
                        </button>
                    </p>
                    <p>
                        <button type="button" id="send-admin-email" class="button button-secondary" style="width:100%;"
                            data-booking-id="<?php echo esc_attr($booking_id); ?>"
                            data-nonce="<?php echo wp_create_nonce('send_booking_email_nonce'); ?>">
                            <?php _e('📧 Send Email to Admin', 'organization-core'); ?>
                        </button>
                    </p>
                    <div id="email-status-message" style="display:none;margin-top:10px;padding:10px;border-radius:4px;"></div>
                </div>
            </div>
            <!-- Status & Price -->
            <div class="postbox">
                <div class="postbox-header">
                    <h2><?php _e('Booking Status', 'organization-core'); ?></h2>
                </div>
                <div class="inside">
                    <div class="status-change-container" data-booking-id="<?php echo $booking_id; ?>">
                        <label for="booking-status-select"><strong><?php _e('Current Status:', 'organization-core'); ?></strong></label>
                        <select id="booking-status-select" class="widefat" style="margin-top:8px;padding:8px;">
                            <option value="pending" <?php selected($booking['status'], 'pending'); ?>><?php _e('Pending', 'organization-core'); ?></option>
                            <option value="confirmed" <?php selected($booking['status'], 'confirmed'); ?>><?php _e('Confirmed', 'organization-core'); ?></option>
                            <option value="completed" <?php selected($booking['status'], 'completed'); ?>><?php _e('Completed', 'organization-core'); ?></option>
                            <option value="cancelled" <?php selected($booking['status'], 'cancelled'); ?>><?php _e('Cancelled', 'organization-core'); ?></option>
                        </select>
                    </div>

                    <hr style="margin:20px 0;">

                    <div class="price-change-container" data-booking-id="<?php echo $booking_id; ?>">
                        <label for="booking-price-input"><strong><?php _e('Total Amount:', 'organization-core'); ?></strong></label>
                        <div style="margin-top:8px;">
                            <input type="number"
                                id="booking-price-input"
                                class="widefat"
                                step="0.01"
                                min="0"
                                value="<?php echo number_format((float)$booking['total_amount'], 2, '.', ''); ?>"
                                placeholder="0.00"
                                style="padding:8px;">
                            <button type="button" id="update-price-btn" class="button button-primary" style="margin-top:8px;width:100%;">
                                <?php _e('Update Price', 'organization-core'); ?>
                            </button>
                        </div>
                        <p class="description"><?php _e('Set the total booking amount', 'organization-core'); ?></p>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="postbox">
                <div class="postbox-header">
                    <h2><?php _e('Quick Actions', 'organization-core'); ?></h2>
                </div>
                <div class="inside">
                    <?php
                    $status = $booking['status'];

                    if ($status === 'pending'):
                        $confirm_url = wp_nonce_url(
                            add_query_arg([
                                'page' => 'organization-bookings',
                                'action' => 'confirm',
                                'booking_id' => $booking_id
                            ], admin_url('admin.php')),
                            'confirm_booking_' . $booking_id
                        );
                    ?>
                        <p>
                            <a href="<?php echo esc_url($confirm_url); ?>" class="button button-primary" style="width:100%;">
                                <?php _e('✓ Confirm Booking', 'organization-core'); ?>
                            </a>
                        </p>
                    <?php endif; ?>

                    <?php if ($status === 'confirmed'):
                        $complete_url = wp_nonce_url(
                            add_query_arg([
                                'page' => 'organization-bookings',
                                'action' => 'complete',
                                'booking_id' => $booking_id
                            ], admin_url('admin.php')),
                            'complete_booking_' . $booking_id
                        );
                    ?>
                        <p>
                            <a href="<?php echo esc_url($complete_url); ?>" class="button" style="width:100%;background:#46b450;color:white;border-color:#46b450;">
                                <?php _e('✓ Mark as Completed', 'organization-core'); ?>
                            </a>
                        </p>
                    <?php endif; ?>

                    <?php if (in_array($status, ['pending', 'confirmed'])):
                        $cancel_url = wp_nonce_url(
                            add_query_arg([
                                'page' => 'organization-bookings',
                                'action' => 'cancel',
                                'booking_id' => $booking_id
                            ], admin_url('admin.php')),
                            'cancel_booking_' . $booking_id
                        );
                    ?>
                        <p>
                            <a href="<?php echo esc_url($cancel_url); ?>" class="button" style="width:100%;">
                                <?php _e('✕ Cancel Booking', 'organization-core'); ?>
                            </a>
                        </p>
                    <?php endif; ?>

                    <?php
                    $delete_url = wp_nonce_url(
                        add_query_arg([
                            'page' => 'organization-bookings',
                            'action' => 'delete',
                            'booking_id' => $booking_id
                        ], admin_url('admin.php')),
                        'delete_booking_' . $booking_id
                    );
                    ?>
                    <p>
                        <a href="<?php echo esc_url($delete_url); ?>" class="button button-link-delete" style="width:100%;" onclick="return confirm('<?php _e('Are you sure you want to delete this booking? This action cannot be undone.', 'organization-core'); ?>');">
                            <?php _e('🗑 Delete Booking', 'organization-core'); ?>
                        </a>
                    </p>
                </div>
            </div>

            <!-- Booking Meta -->
            <div class="postbox">
                <div class="postbox-header">
                    <h2><?php _e('Booking Information', 'organization-core'); ?></h2>
                </div>
                <div class="inside">
                    <p>
                        <strong><?php _e('Booking ID:', 'organization-core'); ?></strong><br>
                        #<?php echo $booking_id; ?>
                    </p>
                    <p>
                        <strong><?php _e('Created:', 'organization-core'); ?></strong><br>
                        <?php echo date('M j, Y \a\t g:i A', strtotime($booking['created_at'])); ?>
                    </p>
                    <p>
                        <strong><?php _e('Last Modified:', 'organization-core'); ?></strong><br>
                        <?php echo date('M j, Y \a\t g:i A', strtotime($booking['modified_at'])); ?>
                    </p>
                    <p>
                        <strong><?php _e('Blog ID:', 'organization-core'); ?></strong><br>
                        <?php echo intval($booking['blog_id']); ?>
                    </p>
                </div>
            </div>

        </div>

    </div>

</div>

<style>
    .booking-status-badge {
        display: inline-block;
        padding: 6px 14px;
        border-radius: 4px;
        font-size: 14px;
        font-weight: 500;
        margin-left: 10px;
    }

    .status-pending {
        background: #f0ad4e;
        color: #fff;
    }

    .status-confirmed {
        background: #5cb85c;
        color: #fff;
    }

    .status-completed {
        background: #0073aa;
        color: #fff;
    }

    .status-cancelled {
        background: #dc3545;
        color: #fff;
    }

    .booking-details-wrapper {
        display: grid;
        grid-template-columns: 1fr 360px;
        gap: 20px;
        margin-top: 20px;
    }

    @media (max-width: 1200px) {
        .booking-details-wrapper {
            grid-template-columns: 1fr;
        }
    }

    .booking-main-content .postbox,
    .booking-sidebar .postbox {
        margin-bottom: 20px;
    }

    .form-table th {
        width: 200px;
        font-weight: 600;
        padding: 15px 10px;
    }

    .form-table td {
        padding: 15px 10px;
    }

    .postbox-header h2 {
        margin: 0;
        padding: 12px;
    }
</style>

<script>
    jQuery(document).ready(function($) {
        // Email sending functionality
        function showEmailStatus(message, isError) {
            var $status = $('#email-status-message');
            $status.html(message)
                .css('background-color', isError ? '#f8d7da' : '#d4edda')
                .css('color', isError ? '#721c24' : '#155724')
                .slideDown();

            setTimeout(function() {
                $status.slideUp();
            }, 5000);
        }

        // Handle user email button
        $('#send-user-email').on('click', function() {
            var $btn = $(this);
            var bookingId = $btn.data('booking-id');
            var nonce = $btn.data('nonce');

            $btn.prop('disabled', true).text('Sending...');

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'send_booking_email',
                    booking_id: bookingId,
                    email_type: 'user',
                    nonce: nonce
                },
                success: function(response) {
                    if (response.success) {
                        showEmailStatus('Email sent successfully to customer!', false);
                    } else {
                        showEmailStatus('Failed to send email: ' + response.data, true);
                    }
                },
                error: function() {
                    showEmailStatus('Server error while sending email', true);
                },
                complete: function() {
                    $btn.prop('disabled', false).text('📧 Send Email to Customer');
                }
            });
        });

        // Handle admin email button
        $('#send-admin-email').on('click', function() {
            var $btn = $(this);
            var bookingId = $btn.data('booking-id');
            var nonce = $btn.data('nonce');

            $btn.prop('disabled', true).text('Sending...');

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'send_booking_email',
                    booking_id: bookingId,
                    email_type: 'admin',
                    nonce: nonce
                },
                success: function(response) {
                    if (response.success) {
                        showEmailStatus('Email sent successfully to admin!', false);
                    } else {
                        showEmailStatus('Failed to send email: ' + response.data, true);
                    }
                },
                error: function() {
                    showEmailStatus('Server error while sending email', true);
                },
                complete: function() {
                    $btn.prop('disabled', false).text('📧 Send Email to Admin');
                }
            });
        });

        // Status change
        $('#booking-status-select').on('change', function() {
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
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert('Error: ' + response.data.message);
                        location.reload();
                    }
                }
            });
        });

        // Price update
        $('#update-price-btn').on('click', function() {
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
                success: function(response) {
                    if (response.success) {
                        alert('✓ ' + response.data.message);
                        location.reload();
                    } else {
                        alert('Error: ' + response.data);
                    }
                },
                complete: function() {
                    btn.prop('disabled', false).text('Update Price');
                }
            });
        });
    });
</script>