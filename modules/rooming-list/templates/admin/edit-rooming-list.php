<?php
/**
 * Template: Rooming List - Edit View
 */

if (!defined('ABSPATH')) {
    exit;
}
?>
<!-- Material Symbols -->
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" />

<div class="wrap">
    <h1 class="wp-heading-inline">
        <?php printf(__('Rooming List for Booking #%d', 'organization-core'), $booking_id); ?>
    </h1>
    <a href="<?php echo admin_url('admin.php?page=organization-rooming-list'); ?>" class="page-title-action">
        <?php _e('Back to List', 'organization-core'); ?>
    </a>
    <a href="<?php echo admin_url('admin.php?page=organization-bookings&action=view&booking_id=' . $booking_id); ?>" class="page-title-action">
        <?php _e('View Booking', 'organization-core'); ?>
    </a>
    <hr class="wp-header-end">

    <?php if (empty($hotel_data['hotel_id'])): ?>
        <div class="notice notice-warning inline">
            <p><?php _e('Please assign a hotel to the booking first.', 'organization-core'); ?></p>
        </div>
    <?php else: ?>

        <div class="card" style="max-width: 100%; margin-top: 20px; padding: 20px;">
            <p class="description" style="font-size: 14px; margin-bottom: 15px;">
                <?php printf(
                    __('Limits: <strong>%d</strong> Rooms Allowed | <strong>%d</strong> Occupants per Room', 'organization-core'),
                    $rooms_allotted,
                    $max_per_room
                ); ?>
            </p>

            <!-- Table Structure -->
            <table class="widefat striped fixed" cellspacing="1" border="1" style="border-collapse: collapse; margin-top: 15px;" id="rooming-list-table">
                <thead>
                    <tr>
                        <th style="width: 15%; text-align: center; font-weight: 600;font-size: 14px;">
                            <?php _e('Room Number', 'organization-core'); ?>
                        </th>
                        <th style="width: 40%; font-weight: 600;font-size: 14px;"><?php _e('Name', 'organization-core'); ?></th>
                        <th style="width: 30%; font-weight: 600;font-size: 14px;"><?php _e('Type', 'organization-core'); ?></th>
                        <th style="width: 15%; font-weight: 600;font-size: 14px;"><?php _e('Action', 'organization-core'); ?></th>
                    </tr>
                </thead>
                <tbody style="border-collapse: collapse;">
                    <!-- JS will populate this -->
                </tbody>
            </table>

            <!-- Button Layout -->
            <div style="display:flex; justify-content: space-between; margin-top: 15px;">
                <div style="display:flex; align-items: center; gap:10px;">
                    <button type="button" class="button button-secondary" id="btn-add-room-test">
                        <?php _e('Add Room', 'organization-core'); ?>
                    </button>
                    <button type="button" class="button button-secondary" id="btn-import-list-test">
                        <?php _e('Import Rooming List', 'organization-core'); ?>
                    </button>
                    <button type="button" class="button button-secondary" id="btn-export-list-test">
                        <?php _e('Export Rooming List', 'organization-core'); ?>
                    </button>
                    <a style="font-size: smaller;" href="<?php echo plugin_dir_url(dirname(dirname(dirname(__FILE__)))) . 'rooming-list/assets/sample-rooming-list.csv'; ?>" target="_blank">
                        <?php _e('Download Sample Rooming List', 'organization-core'); ?>
                    </a>
                </div>
                <div style="display:flex; align-items: center; gap:10px;">
                    <button type="button" class="button button-primary" id="btn-save-list-test">
                        <?php _e('Save list', 'organization-core'); ?>
                    </button>
                    <button type="button" class="button button-primary" id="btn-save-lock-list-test">
                        <?php _e('Save & lock list', 'organization-core'); ?>
                    </button>
                </div>
            </div>

            <!-- Hidden File Input for Import -->
            <input type="file" id="import-file-input" accept=".csv" style="display: none;">
        </div>
    <?php endif; ?>
</div>
