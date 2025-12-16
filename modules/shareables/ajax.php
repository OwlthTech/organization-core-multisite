<?php

/**
 * Ajax functionality of the plugin.
 *
 * @package    Organization_Core
 * @subpackage Organization_Core/modules/shareables
 */
class OC_Shareables_Ajax {

    private $module_id;

    public function __construct($module_id) {
        $this->module_id = $module_id;
        
        add_action('wp_ajax_shareables_save_item', array($this, 'handle_save_item'));
    }

    public function handle_save_item() {
        check_ajax_referer('shareables_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permission denied.', 'organization-core'));
        }

        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        $title = isset($_POST['title']) ? sanitize_text_field($_POST['title']) : '';
        $items = isset($_POST['items']) ? wp_unslash($_POST['items']) : '[]'; // JSON string
        $status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : 'draft';

        if (empty($title)) {
            wp_send_json_error(__('Title is required.', 'organization-core'));
        }

        // Validate JSON
        json_decode($items);
        if (json_last_error() !== JSON_ERROR_NONE) {
            wp_send_json_error(__('Invalid items data.', 'organization-core'));
        }

        $data = array(
            'title' => $title,
            'items' => $items,
            'status' => $status
        );

        // Smart UUID generation: Only generate on publish
        if ($status === 'publish') {
            if ($id > 0) {
                // Editing existing item - check if UUID exists
                $existing = OC_Shareables_CRUD::get_item($id);
                if ($existing && empty($existing->uuid)) {
                    // Draft being published - generate UUID
                    $data['uuid'] = wp_generate_uuid4();
                }
                // If UUID already exists, don't overwrite it
            } else {
                // New item being published - generate UUID
                $data['uuid'] = wp_generate_uuid4();
            }
        }
        // If status is draft, don't set UUID (will remain NULL)

        if ($id > 0) {
            // Update existing
            $result = OC_Shareables_CRUD::update_item($id, $data);
            if ($result !== false) {
                // Get updated item to return UUID if generated
                $updated_item = OC_Shareables_CRUD::get_item($id);
                $response_data = array(
                    'id' => $id,
                    'message' => __('Shareable updated successfully.', 'organization-core')
                );
                
                if (!empty($updated_item->uuid)) {
                    $response_data['uuid'] = $updated_item->uuid;
                    $response_data['public_url'] = home_url('/share/' . $updated_item->uuid);
                }
                
                wp_send_json_success($response_data);
            } else {
                wp_send_json_error(__('Failed to update shareable.', 'organization-core'));
            }
        } else {
            // Create new
            $new_id = OC_Shareables_CRUD::create_item($data);
            if ($new_id) {
                // Get created item to return UUID if generated
                $new_item = OC_Shareables_CRUD::get_item($new_id);
                $response_data = array(
                    'id' => $new_id,
                    'redirect' => admin_url('admin.php?page=shareables&action=edit&id=' . $new_id),
                    'message' => __('Shareable created successfully.', 'organization-core')
                );
                
                if (!empty($new_item->uuid)) {
                    $response_data['uuid'] = $new_item->uuid;
                    $response_data['public_url'] = home_url('/share/' . $new_item->uuid);
                }
                
                wp_send_json_success($response_data);
            } else {
                wp_send_json_error(__('Failed to create shareable.', 'organization-core'));
            }
        }
    }
}
