<?php
$item = $args['item'];
$is_edit = !empty($item);
$title = $is_edit ? $item->title : '';
$items_json = $is_edit ? $item->items : '[]';
$status = $is_edit && isset($item->status) ? $item->status : 'draft';

// Status Label mapping
$status_labels = array(
    'publish' => __('Published', 'organization-core'),
    'draft' => __('Draft', 'organization-core')
);
$status_label = isset($status_labels[$status]) ? $status_labels[$status] : ucfirst($status);
?>

<div class="wrap shareables-admin-wrap">
    <h1 class="wp-heading-inline"><?php echo $is_edit ? __('Edit Shareable', 'organization-core') : __('Add New Shareable', 'organization-core'); ?></h1>
    
    <a href="<?php echo admin_url('admin.php?page=shareables'); ?>" class="page-title-action"><?php _e('← Back to List', 'organization-core'); ?></a>
    <a href="#" id="add-shareable-item-top" class="page-title-action"><?php _e('Add New Section', 'organization-core'); ?></a>
    
    <hr class="wp-header-end">

    <form id="shareables-form">
        <input type="hidden" name="id" value="<?php echo $is_edit ? esc_attr($item->id) : 0; ?>">
        <input type="hidden" id="post_status" name="status" value="<?php echo esc_attr($status); ?>">

        <div id="poststuff">
            <div id="post-body" class="metabox-holder columns-2">
                
                <!-- Main Content Column -->
                <div id="post-body-content">
                    <div id="titlediv">
                        <div id="titlewrap">
                            <label class="screen-reader-text" id="title-prompt-text" for="title"><?php _e('Enter reference title', 'organization-core'); ?></label>
                            <input type="text" name="title" size="30" value="<?php echo esc_attr($title); ?>" id="title" spellcheck="true" autocomplete="off" placeholder="<?php _e('Enter reference title', 'organization-core'); ?>" required>
                        </div>
                    </div>
<br>
                    <div id="shareable-items-metabox" class="postbox">
                        <div class="postbox-header">
                            <h2 class="hndle"><?php _e('Shareable Content Sections', 'organization-core'); ?></h2>
                        </div>
                        <div class="inside">
                            <div id="shareables-repeater-list">
                                <!-- Items injected via JS -->
                            </div>

                            <button type="button" class="button button-secondary button-large" id="add-shareable-item" style="margin-top: 10px;">
                                <span class="dashicons dashicons-plus-alt2" style="vertical-align: text-bottom;"></span> <?php _e('Add Section', 'organization-core'); ?>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Sidebar Column -->
                <div id="postbox-container-1" class="postbox-container">
                    
                    <!-- Publish Meta Box -->
                    <div id="submitdiv" class="postbox">
                        <div class="postbox-header"><h2 class="hndle ui-sortable-handle"><?php _e('Publish', 'organization-core'); ?></h2></div>
                        <div class="inside">
                            <div class="submitbox" id="submitpost">
                                <div id="minor-publishing">
                                    <div style="display:none;"></div>
                                    <div id="minor-publishing-actions">
                                        <div id="save-action">
                                            <input type="button" name="save" id="save-post" value="<?php _e('Save Draft', 'organization-core'); ?>" class="button">
                                        </div>
                                        <div class="clear"></div>
                                    </div>
                                    <div id="misc-publishing-actions">
                                        <div class="misc-pub-section misc-pub-post-status">
                                            <?php _e('Status:', 'organization-core'); ?> <span id="post-status-display" style="font-weight: 600;"><?php echo esc_html($status_label); ?></span>
                                        </div>
                                        <?php if ($is_edit && !empty($item->created_at)): ?>
                                            <div class="misc-pub-section curtime misc-pub-curtime">
                                                <span id="timestamp">
                                                <?php _e('Created:', 'organization-core'); ?> <b><?php echo date_i18n(get_option('date_format'), strtotime($item->created_at)); ?></b>
                                                </span>
                                            </div>
                                        <?php endif; ?>
                                        <?php if ($is_edit && !empty($item->uuid)): ?>
                                             <div class="misc-pub-section">
                                                <a href="<?php echo home_url('/share/' . $item->uuid); ?>" target="_blank" class="button button-small" style="width: 100%; text-align: center; margin-top: 5px;"><?php _e('View Public Page', 'organization-core'); ?> <span class="dashicons dashicons-external" style="vertical-align: middle; font-size: 14px;"></span></a>
                                             </div>
                                        <?php elseif ($is_edit && $status === 'draft'): ?>
                                            <div class="misc-pub-section">
                                                <p class="description" style="margin: 5px 0;"><?php _e('Public link will be generated when published.', 'organization-core'); ?></p>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="clear"></div>
                                </div>

                                <div id="major-publishing-actions">
                                    <div id="delete-action">
                                        <?php if ($is_edit): ?>
                                            <a class="submitdelete deletion" href="<?php echo wp_nonce_url(admin_url('admin.php?page=shareables&action=delete&id=' . $item->id), 'delete_shareable_' . $item->id); ?>"><?php _e('Move to Trash', 'organization-core'); ?></a>
                                        <?php endif; ?>
                                    </div>
                                    <div id="publishing-action">
                                        <span class="spinner"></span>
                                        <input name="original_publish" type="hidden" id="original_publish" value="<?php _e('Publish', 'organization-core'); ?>">
                                        <input type="submit" name="publish" id="publish" class="button button-primary button-large" value="<?php echo ($status === 'publish') ? __('Update', 'organization-core') : __('Publish', 'organization-core'); ?>">
                                    </div>
                                    <div class="clear"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
            </div>
            <br class="clear">
        </div>
    </form>
</div>

<!-- Template for Repeater Item (Table Format) -->
<script type="text/template" id="tmpl-shareable-item">
    <div class="shareable-item postbox" data-index="{{index}}" style="margin-bottom: 20px;">
        <div class="postbox-header">
            <h2 class="hndle" style="display: flex; justify-content: space-between; align-items: center;">
                <span><?php _e('Section', 'organization-core'); ?> #<span class="item-number">{{number}}</span></span>
                <button type="button" class="button-link delete-item" style="color: #a00; text-decoration: none;">
                    <span class="dashicons dashicons-trash"></span> <?php _e('Remove', 'organization-core'); ?>
                </button>
            </h2>
        </div>
        <div class="inside">
            <table class="form-table" role="presentation">
                <tbody>
                    <tr>
                        <th scope="row">
                            <label><?php _e('Section Title', 'organization-core'); ?></label>
                        </th>
                        <td>
                            <input type="text" class="item-title regular-text" value="{{title}}" placeholder="<?php _e('Enter section title', 'organization-core'); ?>">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label><?php _e('Description', 'organization-core'); ?></label>
                        </th>
                        <td>
                            <textarea class="item-description large-text" rows="5" placeholder="<?php _e('Enter description or content...', 'organization-core'); ?>">{{description}}</textarea>
                        </td>
                    </tr>
                    <tr class="media-row">
                        <th scope="row">
                            <label><?php _e('Media Files', 'organization-core'); ?></label>
                        </th>
                        <td>
                            <div class="media-preview-container" style="display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 10px;">
                                {{media_html}}
                            </div>
                            <input type="hidden" class="item-media-ids" value="{{media_ids}}">
                            <input type="hidden" class="item-media-urls" value="{{media_urls}}">
                            <button type="button" class="button button-secondary select-media">
                                <span class="dashicons dashicons-images-alt2" style="vertical-align: text-bottom;"></span> <?php _e('Add Media', 'organization-core'); ?>
                            </button>
                            <p class="description"><?php _e('Add images, videos, or PDF files', 'organization-core'); ?></p>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</script>

<script>
    window.shareablesInitialData = <?php echo $items_json; ?>;
</script>
