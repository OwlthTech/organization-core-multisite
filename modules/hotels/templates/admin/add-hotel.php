<?php
// Determine if we're editing or adding
$is_edit = isset($hotel) && $hotel;
$page_title = $is_edit ? __('Edit Hotel', 'organization-core') : __('Add New Hotel', 'organization-core');
$form_action = $is_edit ? 'update_hotel' : 'save_hotel';
$nonce_action = $is_edit ? 'update_hotel_action' : 'save_hotel_action';
$nonce_name = $is_edit ? 'update_hotel_nonce' : 'save_hotel_nonce';
$submit_text = $is_edit ? __('Update Hotel', 'organization-core') : __('Add Hotel', 'organization-core');

// Default values
$hotel_id = $is_edit ? $hotel->id : '';
$hotel_name = $is_edit ? $hotel->name : '';
$hotel_address = $is_edit ? $hotel->address : '';
$number_of_person = $is_edit ? $hotel->number_of_person : 0;
$number_of_rooms = $is_edit ? $hotel->number_of_rooms : 0;
?>

<div class="wrap">
    <h1 class="wp-heading-inline"><?php echo esc_html($page_title); ?></h1>
    <a href="<?php echo admin_url('admin.php?page=hotels'); ?>" class="page-title-action"><?php _e('← Back to Hotels', 'organization-core'); ?></a>
    <hr class="wp-header-end">

    <?php if (isset($_GET['message']) && $_GET['message'] === 'error') : ?>
        <div class="notice notice-error is-dismissible">
            <p><?php _e('Error saving hotel. Please try again.', 'organization-core'); ?></p>
        </div>
    <?php endif; ?>

    <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
        <input type="hidden" name="action" value="<?php echo esc_attr($form_action); ?>">
        <?php if ($is_edit): ?>
            <input type="hidden" name="hotel_id" value="<?php echo esc_attr($hotel_id); ?>">
        <?php endif; ?>
        <?php wp_nonce_field($nonce_action, $nonce_name); ?>

        <table class="form-table">
            <tr>
                <th scope="row"><label for="hotel_name"><?php _e('Hotel Name', 'organization-core'); ?></label></th>
                <td><input name="hotel_name" type="text" id="hotel_name" value="<?php echo esc_attr($hotel_name); ?>" class="regular-text" required></td>
            </tr>
            <tr>
                <th scope="row"><label for="hotel_address"><?php _e('Address', 'organization-core'); ?></label></th>
                <td><textarea name="hotel_address" id="hotel_address" class="large-text" rows="3"><?php echo esc_textarea($hotel_address); ?></textarea></td>
            </tr>
            <tr>
                <th scope="row"><label for="number_of_person"><?php _e('Capacity (Persons)', 'organization-core'); ?></label></th>
                <td><input name="number_of_person" type="number" id="number_of_person" value="<?php echo esc_attr($number_of_person); ?>" class="small-text"></td>
            </tr>
            <tr>
                <th scope="row"><label for="number_of_rooms"><?php _e('Number of Rooms', 'organization-core'); ?></label></th>
                <td><input name="number_of_rooms" type="number" id="number_of_rooms" value="<?php echo esc_attr($number_of_rooms); ?>" class="small-text"></td>
            </tr>
        </table>

        <?php submit_button($submit_text); ?>
    </form>
</div>
