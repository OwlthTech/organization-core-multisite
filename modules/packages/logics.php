<?php

// Disable Gutenberg for Packages (optional - uncomment if needed)
add_filter('use_block_editor_for_post_type', 'owlth_prefix_disable_gutenberg_for_cpt', 10, 2);
function owlth_prefix_disable_gutenberg_for_cpt($use_block_editor, $post_type)
{
    if ('packages' === $post_type) {
        return false;
    }
    return $use_block_editor;
}

// Register "Packages" Custom Post Type
function register_packages_cpt()
{
    $labels = array(
        'name'                  => _x('Packages', 'Post type general name', 'textdomain'),
        'singular_name'         => _x('Package', 'Post type singular name', 'textdomain'),
        'menu_name'             => _x('Packages', 'Admin Menu text', 'textdomain'),
        'name_admin_bar'        => _x('Package', 'Add New on Toolbar', 'textdomain'),
        'add_new'               => __('Add New', 'textdomain'),
        'add_new_item'          => __('Add New Package', 'textdomain'),
        'new_item'              => __('New Package', 'textdomain'),
        'edit_item'             => __('Edit Package', 'textdomain'),
        'view_item'             => __('View Package', 'textdomain'),
        'all_items'             => __('All Packages', 'textdomain'),
        'search_items'          => __('Search Packages', 'textdomain'),
        'parent_item_colon'     => __('Parent Packages:', 'textdomain'),
        'not_found'             => __('No packages found.', 'textdomain'),
        'not_found_in_trash'    => __('No packages found in Trash.', 'textdomain'),
    );

    $args = array(
        'labels'             => $labels,
        'public'             => true,
        'publicly_queryable' => true,
        'show_ui'            => true,
        'show_in_menu'       => true,
        'query_var'          => true,
        // CRITICAL FIX: Set rewrite to false, we'll handle URLs manually
        'rewrite'            => false,
        'taxonomies'         => array('location', 'parks'),
        'capability_type'    => 'post',
        'has_archive'        => false, // Disable archive to avoid conflicts
        'hierarchical'       => false,
        'menu_position'      => 5,
        'menu_icon'          => 'dashicons-archive',
        'supports'           => array('title', 'editor', 'thumbnail'),
        'show_in_rest'       => true,
    );

    register_post_type('packages', $args);
}
add_action('init', 'register_packages_cpt');

// Register taxonomies
function register_taxonomies_for_packages()
{
    // Locations
    register_taxonomy('location', 'packages', array(
        'labels' => array(
            'name' => 'Locations',
            'singular_name' => 'Location',
            'search_items' => 'Search Locations',
            'all_items' => 'All Locations',
            'edit_item' => 'Edit Location',
            'add_new_item' => 'Add New Location',
            'menu_name' => 'Locations',
        ),
        'show_in_rest' => true,
        'hierarchical' => true,
        'show_admin_column' => true,
        'show_ui' => true,
        'rewrite' => array('slug' => 'location'),
    ));

    // Parks
    register_taxonomy('parks', 'packages', array(
        'labels' => array(
            'name' => 'Parks',
            'singular_name' => 'Park',
            'search_items' => 'Search Parks',
            'all_items' => 'All Parks',
            'edit_item' => 'Edit Park',
            'add_new_item' => 'Add New Park',
            'menu_name' => 'Parks',
        ),
        'show_in_rest' => true,
        'hierarchical' => true,
        'show_admin_column' => true,
        'show_ui' => true,
        'rewrite' => array('slug' => 'park'),
    ));
}
add_action('init', 'register_taxonomies_for_packages');

// School Package Meta Box
function add_school_package_meta_box()
{
    add_meta_box(
        'school_package_meta',
        'School Package Available',
        'render_school_package_meta_box',
        'packages',
        'side',
        'default'
    );
}
add_action('add_meta_boxes', 'add_school_package_meta_box');

function render_school_package_meta_box($post)
{
    wp_nonce_field('save_school_package_meta', 'school_package_nonce');
    $school_package = get_post_meta($post->ID, '_school_package', true);
    $school_package = $school_package ? $school_package : 'false';
?>
    <p><strong>Does this package offer school options?</strong></p>
    <label>
        <input type="radio" name="school_package" value="true" <?php checked($school_package, 'true'); ?> />
        <strong>True</strong> - School registration required
    </label><br />
    <label>
        <input type="radio" name="school_package" value="false" <?php checked($school_package, 'false'); ?> />
        <strong>False</strong> - No school registration needed
    </label>
<?php
}

// Festival Dates Meta Box
function packages_dates_meta_box()
{
    add_meta_box(
        'packages_dates',
        'Festival Dates',
        'render_packages_dates_meta_box',
        'packages',
        'normal',
        'default'
    );
}
add_action('add_meta_boxes', 'packages_dates_meta_box');

function render_packages_dates_meta_box($post)
{
    wp_nonce_field('save_packages_dates', 'packages_dates_nonce');
    $dates = get_post_meta($post->ID, '_festival_dates', true);

    if (!is_array($dates)) {
        $dates = array();
    }

    // Ensure each date entry has date and note keys
    $dates = array_map(function ($item) {
        if (is_string($item)) {
            return array('date' => $item, 'note' => '');
        }
        return $item;
    }, $dates);
?>
    <!-- <style>
        .festival-date-row {
            display: flex;
            gap: 10px;
            align-items: center;
            margin-bottom: 10px;
            padding: 10px;
            background: #f5f5f5;
            border-radius: 4px;
        }

        .festival-date-row input[type="date"] {
            width: 200px;
        }

        .festival-date-row input[type="text"] {
            flex: 1;
            min-width: 300px;
        }

        .festival-date-row label {
            font-weight: 600;
            min-width: 80px;
        }
    </style> -->

    <div id="festival-dates-wrapper">
        <?php if (!empty($dates)): ?>
            <?php foreach ($dates as $index => $date_info): ?>
                <div class="festival-date-row">
                    <label>Date:</label>
                    <input type="date" name="festival_dates[<?php echo $index; ?>][date]"
                        value="<?php echo esc_attr($date_info['date']); ?>" required />

                    <label>Note:</label>
                    <input type="text" name="festival_dates[<?php echo $index; ?>][note]"
                        value="<?php echo esc_attr($date_info['note']); ?>"
                        placeholder="e.g., AM & PM, Judges Invitational" />

                    <button type="button" class="remove-date button">Remove</button>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="festival-date-row">
                <label>Date:</label>
                <input type="date" name="festival_dates[0][date]" value="" required />

                <label>Note:</label>
                <input type="text" name="festival_dates[0][note]" value=""
                    placeholder="e.g., AM & PM, Judges Invitational" />

                <button type="button" class="remove-date button">Remove</button>
            </div>
        <?php endif; ?>
    </div>

    <button type="button" id="add-date" class="button button-primary" style="margin-top: 10px;">
        Add Another Date
    </button>

    <script>
        jQuery(document).ready(function($) {
            // Add new date row
            $('#add-date').on('click', function() {
                var rowCount = $('#festival-dates-wrapper .festival-date-row').length;
                var newRow = '<div class="festival-date-row">' +
                    '<label>Date:</label>' +
                    '<input type="date" name="festival_dates[' + rowCount + '][date]" value="" required />' +
                    '<label>Note:</label>' +
                    '<input type="text" name="festival_dates[' + rowCount + '][note]" value="" ' +
                    'placeholder="e.g., AM & PM, Judges Invitational" />' +
                    '<button type="button" class="remove-date button">Remove</button>' +
                    '</div>';
                $('#festival-dates-wrapper').append(newRow);
            });

            // Remove date row
            $('#festival-dates-wrapper').on('click', '.remove-date', function() {
                var rowCount = $('#festival-dates-wrapper .festival-date-row').length;
                if (rowCount > 1) {
                    $(this).closest('.festival-date-row').remove();
                    // Re-index remaining rows
                    $('#festival-dates-wrapper .festival-date-row').each(function(index) {
                        $(this).find('input[type="date"]').attr('name', 'festival_dates[' + index + '][date]');
                        $(this).find('input[type="text"]').attr('name', 'festival_dates[' + index + '][note]');
                    });
                } else {
                    $(this).closest('.festival-date-row').find('input').val('');
                }
            });
        });
    </script>
<?php
}
// Short Excerpt Meta Box
function packages_short_excerpt_meta_box()
{
    add_meta_box(
        'packages_short_excerpt',
        'Short Excerpt for List Display',
        'render_packages_short_excerpt_meta_box',
        'packages',
        'normal',
        'high'
    );
}
add_action('add_meta_boxes', 'packages_short_excerpt_meta_box');

function render_packages_short_excerpt_meta_box($post)
{
    wp_nonce_field('save_packages_short_excerpt', 'packages_short_excerpt_nonce');
    $short_excerpt = get_post_meta($post->ID, '_package_short_excerpt', true);
?>
    <div style="margin-bottom: 15px;">
        <label for="package_short_excerpt" style="display: block; font-weight: bold; margin-bottom: 5px;">
            Short Excerpt for Package List:
        </label>
        <textarea
            id="package_short_excerpt"
            name="package_short_excerpt"
            rows="3"
            cols="50"
            style="width: 100%; max-width: 100%;"
            placeholder="Enter a short description that will appear in the package list..."><?php echo esc_textarea($short_excerpt); ?></textarea>
        <p style="font-style: italic; color: #666; margin-top: 5px;">
            This text will be displayed in package listings on the frontend.
        </p>
    </div>
<?php
}

// Save all meta boxes
function save_packages_meta_boxes($post_id)
{
    // Check autosave
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;

    // Save school package
    if (isset($_POST['school_package_nonce']) && wp_verify_nonce($_POST['school_package_nonce'], 'save_school_package_meta')) {
        $school_package = isset($_POST['school_package']) ? sanitize_text_field($_POST['school_package']) : 'false';
        update_post_meta($post_id, '_school_package', $school_package);
    }

    // Save dates with notes
    if (isset($_POST['packages_dates_nonce']) && wp_verify_nonce($_POST['packages_dates_nonce'], 'save_packages_dates')) {
        if (isset($_POST['festival_dates']) && is_array($_POST['festival_dates'])) {
            $dates_data = array();

            foreach ($_POST['festival_dates'] as $date_info) {
                if (!empty($date_info['date'])) {
                    $dates_data[] = array(
                        'date' => sanitize_text_field($date_info['date']),
                        'note' => sanitize_text_field($date_info['note'])
                    );
                }
            }

            if (!empty($dates_data)) {
                update_post_meta($post_id, '_festival_dates', $dates_data);
            } else {
                delete_post_meta($post_id, '_festival_dates');
            }
        } else {
            delete_post_meta($post_id, '_festival_dates');
        }
    }

    // Save short excerpt
    if (isset($_POST['packages_short_excerpt_nonce']) && wp_verify_nonce($_POST['packages_short_excerpt_nonce'], 'save_packages_short_excerpt')) {
        if (isset($_POST['package_short_excerpt'])) {
            $short_excerpt = sanitize_textarea_field($_POST['package_short_excerpt']);
            update_post_meta($post_id, '_package_short_excerpt', $short_excerpt);
        } else {
            delete_post_meta($post_id, '_package_short_excerpt');
        }
    }
}
add_action('save_post', 'save_packages_meta_boxes');

// Enqueue jQuery for admin
function packages_admin_scripts($hook)
{
    global $post;

    if (($hook == 'post-new.php' || $hook == 'post.php') && isset($post) && 'packages' === $post->post_type) {
        wp_enqueue_script('jquery');
        wp_enqueue_media();
    }
}
add_action('admin_enqueue_scripts', 'packages_admin_scripts');
?>
<?php
// ============================================
// LOCATION-BASED DATES & PARKS CONFIGURATION
// Uses HTML5 <details> - Zero JavaScript, Zero CSS
// ============================================

/**
 * Add Location-Date-Park Configuration Meta Box
 */
function add_location_date_park_metabox()
{
    add_meta_box(
        'location_date_park_mapping',
        'Location-Specific Dates & Parks Configuration',
        'render_location_date_park_metabox',
        'packages',
        'normal',
        'high'
    );
}
add_action('add_meta_boxes', 'add_location_date_park_metabox');

/**
 * Render the meta box
 */
function render_location_date_park_metabox($post)
{
    wp_nonce_field('save_location_date_park_mapping', 'location_date_park_nonce');

    // Get selected locations
    $selected_locations = wp_get_post_terms($post->ID, 'location', array('fields' => 'all'));

    // Get all parks
    $all_parks = get_terms(array(
        'taxonomy' => 'parks',
        'hide_empty' => false,
        'orderby' => 'name',
        'order' => 'ASC'
    ));

    // Get festival dates
    $all_festival_dates = get_post_meta($post->ID, '_festival_dates', true);
    if (!is_array($all_festival_dates)) {
        $all_festival_dates = array();
    }

    // Get saved config
    $saved_config = get_post_meta($post->ID, '_location_date_park_config', true);
    if (!is_array($saved_config)) {
        $saved_config = array();
    }

    // Convert to lookup
    $config_by_location = array();
    foreach ($saved_config as $cfg) {
        $config_by_location[$cfg['location_id']] = $cfg;
    }

?>

    <?php if (empty($selected_locations)): ?>
        <p>
            <strong>ℹ No locations selected.</strong><br>
            Select locations from the sidebar, then save to configure dates and parks.
        </p>
    <?php else: ?>
        <p style="margin-bottom: 15px;">
            <strong>Instructions:</strong> Click each location to expand and select available dates and parks.
        </p>

        <?php foreach ($selected_locations as $location): ?>
            <?php
            $location_id = $location->term_id;
            $location_name = $location->name;

            $current_config = $config_by_location[$location_id] ?? array(
                'location_id' => $location_id,
                'dates' => array(),
                'parks' => array()
            );
            ?>

            <!-- HTML5 Details/Summary - Native Browser Accordion -->
            <details style="border: 1px solid #c3c4c7; margin-bottom: 10px; background: #fff;">
                <summary style="padding: 10px 12px; cursor: pointer; background: #f6f7f7; border-bottom: 1px solid #c3c4c7; font-weight: 600;">
                    <?php echo esc_html($location_name); ?>
                </summary>

                <div style="padding: 15px 12px;">
                    <input type="hidden" name="ldp_config[<?php echo $location_id; ?>][location_id]" value="<?php echo $location_id; ?>" />

                    <table class="form-table">
                        <tbody>
                            <!-- Dates -->
                            <tr>
                                <th scope="row" style="width: 200px;">
                                    Available Dates
                                </th>
                                <td>
                                    <?php if (!empty($all_festival_dates)): ?>
                                        <?php foreach ($all_festival_dates as $date_info): ?>
                                            <?php
                                            if (is_string($date_info)) {
                                                $date = $date_info;
                                                $note = '';
                                            } else {
                                                $date = $date_info['date'] ?? '';
                                                $note = $date_info['note'] ?? '';
                                            }

                                            if (empty($date)) continue;

                                            $formatted = date('F j, Y', strtotime($date));
                                            $day = date('l', strtotime($date));
                                            $checked = in_array($date, $current_config['dates']);
                                            ?>

                                            <label style="display: block; margin-bottom: 5px;">
                                                <input type="checkbox"
                                                    name="ldp_config[<?php echo $location_id; ?>][dates][]"
                                                    value="<?php echo esc_attr($date); ?>"
                                                    <?php checked($checked); ?> />
                                                <strong><?php echo esc_html($formatted); ?></strong>
                                                <span class="description">(<?php echo esc_html($day); ?>)</span>
                                                <?php if ($note): ?>
                                                    <em style="color: #2271b1;"> - <?php echo esc_html($note); ?></em>
                                                <?php endif; ?>
                                            </label>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <p class="description">No dates available. Add dates above first.</p>
                                    <?php endif; ?>
                                </td>
                            </tr>

                            <!-- Parks -->
                            <tr>
                                <th scope="row">
                                    Available Parks
                                </th>
                                <td>
                                    <?php if (!empty($all_parks)): ?>
                                        <div style="column-count: 2; column-gap: 20px;">
                                            <?php foreach ($all_parks as $park): ?>
                                                <?php $checked = in_array($park->term_id, $current_config['parks']); ?>

                                                <label style="display: block; margin-bottom: 5px; break-inside: avoid;">
                                                    <input type="checkbox"
                                                        name="ldp_config[<?php echo $location_id; ?>][parks][]"
                                                        value="<?php echo $park->term_id; ?>"
                                                        <?php checked($checked); ?> />
                                                    <?php echo esc_html($park->name); ?>
                                                </label>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php else: ?>
                                        <p class="description">No parks available. Create parks first.</p>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </details>

        <?php endforeach; ?>
    <?php endif; ?>

<?php
}

/**
 * Save configuration
 */
function save_location_date_park_mapping($post_id)
{
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;

    if (
        !isset($_POST['location_date_park_nonce']) ||
        !wp_verify_nonce($_POST['location_date_park_nonce'], 'save_location_date_park_mapping')
    ) {
        return;
    }

    $selected_locations = wp_get_post_terms($post_id, 'location', array('fields' => 'ids'));

    if (empty($selected_locations) || is_wp_error($selected_locations)) {
        delete_post_meta($post_id, '_location_date_park_config');
        return;
    }

    $cleaned_config = array();

    if (isset($_POST['ldp_config']) && is_array($_POST['ldp_config'])) {
        foreach ($_POST['ldp_config'] as $location_id => $config) {
            $location_id = intval($location_id);

            if (!in_array($location_id, $selected_locations)) continue;

            $entry = array(
                'location_id' => $location_id,
                'dates' => array(),
                'parks' => array()
            );

            if (!empty($config['dates']) && is_array($config['dates'])) {
                $entry['dates'] = array_map('sanitize_text_field', $config['dates']);
            }

            if (!empty($config['parks']) && is_array($config['parks'])) {
                $entry['parks'] = array_map('intval', $config['parks']);
            }

            if (!empty($entry['dates']) || !empty($entry['parks'])) {
                $cleaned_config[] = $entry;
            }
        }
    }

    if (!empty($cleaned_config)) {
        update_post_meta($post_id, '_location_date_park_config', $cleaned_config);
    } else {
        delete_post_meta($post_id, '_location_date_park_config');
    }
}
add_action('save_post', 'save_location_date_park_mapping');

// ============================================
// REMOVE PARKS METABOX & COLUMN
// Parks are now managed per-location in the configuration metabox
// ============================================

/**
 * Remove Parks taxonomy metabox from sidebar
 * Parks are now selected per-location inside the configuration metabox
 */
function remove_parks_metabox_from_packages()
{
    remove_meta_box('tagsdiv-parks', 'packages', 'side');
}
add_action('admin_menu', 'remove_parks_metabox_from_packages');

/**
 * Remove Parks column from packages list table
 */
function remove_parks_column_from_packages($columns)
{
    unset($columns['taxonomy-parks']);
    return $columns;
}
add_filter('manage_packages_posts_columns', 'remove_parks_column_from_packages');

/**
 * OPTIONAL: Keep Parks taxonomy registered but hidden from packages edit screen
 * This ensures parks still exist in the system but aren't shown in the default UI
 */
function modify_parks_taxonomy_for_packages()
{
    // Re-register parks taxonomy with show_in_quick_edit = false
    register_taxonomy('parks', 'packages', array(
        'labels' => array(
            'name' => 'Parks',
            'singular_name' => 'Park',
            'search_items' => 'Search Parks',
            'all_items' => 'All Parks',
            'edit_item' => 'Edit Park',
            'add_new_item' => 'Add New Park',
            'menu_name' => 'Parks',
        ),
        'show_in_rest' => true,
        'hierarchical' => true,
        'show_admin_column' => false,  // Hide from column
        'show_ui' => true,             // Keep UI in admin menu
        'show_in_quick_edit' => false, // Hide from quick edit
        'meta_box_cb' => false,        // Remove default metabox
        'rewrite' => array('slug' => 'park'),
    ));
}
// Hook with higher priority to override the initial registration
add_action('init', 'modify_parks_taxonomy_for_packages', 20);
