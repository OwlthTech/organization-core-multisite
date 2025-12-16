<?php

if (!defined('WPINC')) {
    die;
}
// Booking Details Metaboxes

class Booking_Details_Metaboxes
{

    private $screen_id;
    private $booking_crud;

    public function init($screen_id, $booking_crud)
    {
        $this->screen_id = $screen_id;
        $this->booking_crud = $booking_crud;
        $this->register_booking_metaboxes();
    }

    /**
     * Register booking detail metaboxes
     */

    public function register_booking_metaboxes()
    {

        // Booking Overview Metabox
        add_meta_box(
            'booking_overview_meta',
            __('Booking Overview', 'organization-core'),
            array($this, 'render_booking_overview_metabox'),
            $this->screen_id,
            'normal',
            'high'
        );

        // Customer Information Metabox
        add_meta_box(
            'customer_info_meta',
            __('Customer Information', 'organization-core'),
            array($this, 'render_customer_info_metabox'),
            $this->screen_id,
            'normal',
            'high'
        );

        // Group Details Metabox
        add_meta_box(
            'group_details_meta',
            __('Group Details', 'organization-core'),
            array($this, 'render_group_details_metabox'),
            $this->screen_id,
            'normal',
            'high'
        );

        // Sidebar: Customer Notes
        add_meta_box(
            'customer_notes_meta',
            __('Customer Notes', 'organization-core'),
            array($this, 'render_customer_notes_metabox'),
            $this->screen_id,
            'normal',
            'default'
        );

        // Hotels Assigned Metabox
        add_meta_box(
            'booking_hotel_meta',
            __('Hotel Assigned', 'organization-core'),
            array($this, 'render_booking_hotel_metabox'),
            $this->screen_id,
            'normal',
            'default'
        );



        // Add filters for default collapsed/open states based on booking stage
        // $this->attach_metabox_collapse_filters();

        // Sidebar: Status & Actions
        add_meta_box(
            'booking_status_meta',
            __('Status & Price', 'organization-core'),
            array($this, 'render_status_metabox'),
            $this->screen_id,
            'side',
            'high'
        );

        // Sidebar: Assign to staff 
        add_meta_box(
            'booking_assign_staff_meta',
            __('Assign to Staff', 'organization-core'),
            array($this, 'render_booking_assign_staff_metabox'),
            $this->screen_id,
            'side',
            'default'
        );

        // Sidebar: Internal Notes
        add_meta_box(
            'internal_notes_meta',
            __('Internal Notes', 'organization-core'),
            array($this, 'render_internal_notes_metabox'),
            $this->screen_id,
            'side',
            'default'
        );

        // Sidebar: History & Actions
        add_meta_box(
            'booking_history_meta',
            __('History & Actions', 'organization-core'),
            array($this, 'render_booking_history_metabox'),
            $this->screen_id,
            'side',
            'default'
        );
    }

    /**
     * Render functions
     * 1. 
     * 2. 
     */

    /**
     * Render booking overview metabox
     */
    public function render_booking_overview_metabox($post, $metabox)
    {
        $booking_id = isset($_GET['booking_id']) ? absint($_GET['booking_id']) : 0;
        $booking = $this->booking_crud->get_booking($booking_id, get_current_blog_id());

        if (!$booking) {
            echo '<p>' . __('Booking data not found.', 'organization-core') . '</p>';
            return;
        }

        $package = $booking['package_id'] ? get_post($booking['package_id']) : null;
?>
        <table class="form-table" role="presentation">
            <tr>
                <th><?php _e('Booking ID', 'organization-core'); ?>:</th>
                <td>#<?php echo esc_html($booking_id); ?></td>
            </tr>
            <tr>
                <th><?php _e('Package', 'organization-core'); ?>:</th>
                <td>
                    <?php if ($package): ?>
                        <?php echo esc_html($package->post_title); ?>
                    <?php else: ?>
                        <?php _e('N/A', 'organization-core'); ?>
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <th><?php _e('Festival Date', 'organization-core'); ?>:</th>
                <td>
                    <?php
                    if (!empty($booking['date_selection'])) {
                        echo date('F j, Y', strtotime($booking['date_selection']));
                    } else {
                        _e('N/A', 'organization-core');
                    }
                    ?>
                </td>
            </tr>
            <tr>
                <th><?php _e('Status', 'organization-core'); ?>:</th>
                <td>
                    <span style="font-size: small;"
                        class="booking-status-badge status-<?php echo esc_attr($booking['status']); ?>">
                        <?php echo esc_html(ucfirst($booking['status'])); ?>
                    </span>
                </td>
            </tr>
        </table>
    <?php
    }

    /**
     * Render customer info metabox
     */
    public function render_customer_info_metabox($post, $metabox)
    {
        $booking_id = isset($_GET['booking_id']) ? absint($_GET['booking_id']) : 0;
        $booking = $this->booking_crud->get_booking($booking_id, get_current_blog_id());
        $user = $booking ? get_userdata($booking['user_id']) : null;
    ?>
        <table class="form-table" role="presentation">
            <tr>
                <th><?php _e('Name', 'organization-core'); ?>:</th>
                <td><?php echo $user ? esc_html($user->display_name) : __('N/A', 'organization-core'); ?></td>
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
            <tr>
                <th><?php _e('School', 'organization-core'); ?>:</th>
                <td>
                    <?php
                    if (!empty($booking['school_id'])) {
                        $school = $this->booking_crud->get_school($booking['school_id']);
                        echo esc_html($school['school_name'] ?? 'N/A');
                    } else {
                        _e('N/A', 'organization-core');
                    }
                    ?>
                </td>
            </tr>
        </table>
    <?php
    }

    /**
     * Render group details metabox
     */
    public function render_group_details_metabox($post, $metabox)
    {
        $booking_id = isset($_GET['booking_id']) ? absint($_GET['booking_id']) : 0;
        $booking = $this->booking_crud->get_booking($booking_id, get_current_blog_id());
    ?>
        <table class="form-table" role="presentation">
            <tr>
                <th><?php _e('Total Students', 'organization-core'); ?>:</th>
                <td><?php echo intval($booking['total_students']); ?></td>
            </tr>
            <tr>
                <th><?php _e('Total Chaperones', 'organization-core'); ?>:</th>
                <td><?php echo intval($booking['total_chaperones']); ?></td>
            </tr>
            <tr>
                <th><?php _e('Total Attendees', 'organization-core'); ?>:</th>
                <td><?php echo intval($booking['total_students']) + intval($booking['total_chaperones']); ?></td>
            </tr>
        </table>
    <?php
    }

    /**
     * Render hotel metabox
     */
    public function render_booking_hotel_metabox($post, $metabox)
    {
        $booking_id = isset($_GET['booking_id']) ? absint($_GET['booking_id']) : 0;
        $booking = $this->booking_crud->get_booking($booking_id, get_current_blog_id());

        // Get all hotels
        $hotels = OC_Hotels_CRUD::get_hotels(array('limit' => -1));

        // Decode hotel_data JSON if exists
        $hotel_data = array();
        if (!empty($booking['hotel_data'])) {
            $hotel_data = is_string($booking['hotel_data'])
                ? json_decode($booking['hotel_data'], true)
                : $booking['hotel_data'];
        }

        // Get saved values
        $saved_hotel_id = isset($hotel_data['hotel_id']) ? intval($hotel_data['hotel_id']) : 0;
        $saved_hotel_address = isset($hotel_data['hotel_address']) ? $hotel_data['hotel_address'] : '';
        $saved_count_per_room = isset($hotel_data['count_per_room']) ? intval($hotel_data['count_per_room']) : 0;
        $saved_rooms_allotted = isset($hotel_data['rooms_allotted']) ? intval($hotel_data['rooms_allotted']) : 0;
        $saved_checkin = isset($hotel_data['checkin_date']) ? $hotel_data['checkin_date'] : '';
        $saved_checkout = isset($hotel_data['checkout_date']) ? $hotel_data['checkout_date'] : '';
        $saved_due_date = isset($hotel_data['due_date']) ? $hotel_data['due_date'] : '';

        wp_nonce_field('save_hotel_assignment_' . $booking_id, 'hotel_nonce');
    ?>
        <table class="form-table" role="presentation">
            <tr>
                <th><label for="booking_hotel_select"><?php _e('Hotel', 'organization-core'); ?></label></th>
                <td>
                    <select id="booking_hotel_select" name="booking_hotel_id" class="widefat">
                        <option value=""><?php _e('Select hotel', 'organization-core'); ?></option>
                        <?php foreach ($hotels as $h): ?>
                            <option
                                value="<?php echo esc_attr($h->id); ?>"
                                data-address="<?php echo esc_attr($h->address ?? ''); ?>"
                                data-count="<?php echo esc_attr($h->number_of_person ?? 4); ?>"
                                <?php selected($saved_hotel_id, $h->id); ?>>
                                <?php echo esc_html($h->name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>

            <tr>
                <th><?php _e('Address', 'organization-core'); ?></th>
                <td>
                    <input type="text"
                        id="booking_hotel_address"
                        name="booking_hotel_address"
                        value="<?php echo esc_attr($saved_hotel_address); ?>"
                        readonly
                        class="regular-text" />
                </td>
            </tr>

            <tr>
                <th><label for="booking_hotel_count_per_room"><?php _e('Allowed members per room', 'organization-core'); ?></label></th>
                <td>
                    <input type="number"
                        id="booking_hotel_count_per_room"
                        name="booking_hotel_count_per_room"
                        value="<?php echo esc_attr($saved_count_per_room); ?>"
                        min="1"
                        max="10"
                        class="small-text" />
                </td>
            </tr>

            <tr>
                <th><label for="booking_hotel_rooms_allotted"><?php _e('Allot number of rooms', 'organization-core'); ?></label></th>
                <td>
                    <input type="number"
                        id="booking_hotel_rooms_allotted"
                        name="booking_hotel_rooms_allotted"
                        min="0"
                        class="small-text"
                        value="<?php echo esc_attr($saved_rooms_allotted); ?>" />
                </td>
            </tr>

            <tr>
                <th><label for="booking_hotel_checkin"><?php _e('Check-in date', 'organization-core'); ?></label></th>
                <td>
                    <input type="date"
                        id="booking_hotel_checkin"
                        name="booking_hotel_checkin"
                        value="<?php echo esc_attr($saved_checkin); ?>" />
                </td>
            </tr>

            <tr>
                <th><label for="booking_hotel_checkout"><?php _e('Check-out date', 'organization-core'); ?></label></th>
                <td>
                    <input type="date"
                        id="booking_hotel_checkout"
                        name="booking_hotel_checkout"
                        value="<?php echo esc_attr($saved_checkout); ?>" />
                </td>
            </tr>
             <tr>
                <th><label for="booking_hotel_due_date"><?php _e('Due date', 'organization-core'); ?></label></th>
                <td>
                    <input type="date"
                        id="booking_hotel_due_date"
                        name="booking_hotel_due_date"
                        value="<?php echo esc_attr($saved_due_date); ?>" />
                </td>
            </tr>
        </table>
        <p>
            <button type="submit" name="save_hotel_assignment" class="button button-primary">
                <?php _e('Save Hotel Assignment', 'organization-core'); ?>
            </button>
            <?php if ($saved_hotel_id): ?>
                <a href="<?php echo admin_url('admin.php?page=organization-rooming-list&action=edit&booking_id=' . $booking_id); ?>" class="button button-secondary" style="margin-left: 10px;">
                    <?php _e('Manage Rooming List', 'organization-core'); ?>
                </a>
            <?php endif; ?>
        </p>
        <script>
            jQuery(function($) {
                // Hotel Selection Logic
                $('#booking_hotel_select').on('change', function() {
                    var opt = $(this).find(':selected');
                    $('#booking_hotel_address').val(opt.data('address') || '');
                    if (!$('#booking_hotel_count_per_room').val()) {
                        $('#booking_hotel_count_per_room').val(opt.data('count') || 4);
                    }
                });

                // Date Validation Logic
                var $checkin = $('#booking_hotel_checkin');
                var $checkout = $('#booking_hotel_checkout');
                var $due_date = $('#booking_hotel_due_date');
            
                $checkin.on('change', function() {
                    var checkinDate = $(this).val();
                    if (checkinDate) {
                        $checkout.attr('min', checkinDate);
                        // If checkout is before checkin, clear it
                        if ($checkout.val() && $checkout.val() < checkinDate) {
                            $checkout.val('');
                        }
                    } else {
                        $checkout.removeAttr('min');
                    }
                });

                $checkout.on('change', function() {
                    var checkoutDate = $(this).val();
                    if (checkoutDate) {
                        $checkin.attr('max', checkoutDate);
                        // If checkin is after checkout, clear it
                        if ($checkin.val() && $checkin.val() > checkoutDate) {
                            $checkin.val('');
                        }
                    } else {
                        $checkin.removeAttr('max');
                    }
                });

                // ✅ Due Date Validation: Must be BEFORE check-in date
                $checkin.on('change', function() {
                    var checkinDate = $(this).val();
                    if (checkinDate) {
                        // Set max date for due date to be before check-in
                        $due_date.attr('max', checkinDate);
                        // If due date is on or after check-in, clear it
                        if ($due_date.val() && $due_date.val() >= checkinDate) {
                            $due_date.val('');
                            alert('Due date must be before the check-in date. Please select a new due date.');
                        }
                    } else {
                        $due_date.removeAttr('max');
                    }
                });

                $due_date.on('change', function() {
                    var dueDate = $(this).val();
                    var checkinDate = $checkin.val();
                    
                    if (dueDate && checkinDate) {
                        // Validate that due date is before check-in
                        if (dueDate >= checkinDate) {
                            alert('Due date must be before the check-in date.');
                            $(this).val('');
                        }
                    }
                });

                // Trigger on load to set initial constraints
                $checkin.trigger('change');
                $checkout.trigger('change');
            });
        </script>
        <?php
    }



    /**
     * Attach per-metabox filters that set the 'closed' class based on booking stage/status.
     */
    protected function attach_metabox_collapse_filters()
    {
        // Map of metabox_id => callback or simple conditions.
        // Modify keys & values to reflect your real rules.
        $rules = array(
            'booking_overview_meta' => function ($stage, $post) {
                // example: show overview open only for 'new' stage; collapse otherwise
                return !in_array($stage, array('new', 'needs_attention'));
            },
            'customer_info_meta' => function ($stage, $post) {
                // collapse customer info for 'archived' stage
                return $stage === 'archived';
            },
            'group_details_meta' => function ($stage, $post) {
                // keep group details open for 'confirmed' stage, closed otherwise
                return $stage !== 'confirmed';
            },
            'booking_hotel_meta' => function ($stage, $post) {
                // collapse if stage is not 'hotel_assigned'
                return $stage !== 'hotel_assigned';
            },

            'customer_notes_meta' => function ($stage, $post) {
                // always open
                return true;
            },
        );

        $screen = $this->screen_id;

        foreach ($rules as $metabox_id => $should_collapse_fn) {
            // closure that WP filter expects: receives array $classes
            add_filter("postbox_classes_{$screen}_{$metabox_id}", function ($classes) use ($should_collapse_fn) {
                $should_collapse = (bool) call_user_func($should_collapse_fn, 'new', []); // pass 'new', 'archived', 'confirmed', 'hotel_assigned', 'finalized', etc.
                if ($should_collapse) {
                    var_dump($should_collapse);
                    $classes[] = 'closed';
                    return $classes;
                }
                return $classes;

                $post_id = 0;

                // try to find current post id reliably in admin
                if (isset($_GET['booking_id'])) {
                    $post_id = absint($_GET['booking_id']);
                } elseif (isset($_POST['booking_id'])) {
                    $post_id = absint($_POST['booking_id']);
                } elseif (isset($_REQUEST['booking_id'])) {
                    $post_id = absint($_REQUEST['booking_id']);
                }

                if (!$post_id) {
                    return $classes;
                }




                // $post = get_post($post_id);
                // if (!$post) {
                //     return $classes;
                // }

                // // === Determine booking stage/status ===
                // // Example: booking stage stored in post meta '_booking_stage'
                // // Adjust the meta key or logic if you use a taxonomy or post_status.
                // $stage = get_post_meta($post_id, '_booking_stage', true);

                // // fallback: use post_status if you store status there
                // if (empty($stage)) {
                //     $stage = $post->post_status;
                // }

                // // If the rule says the box should be collapsed, add 'closed' class
                // $should_collapse = false;
                // try {
                //     $should_collapse = (bool) call_user_func($should_collapse_fn, $stage, $post);
                // } catch (\Throwable $e) {
                //     // if the rule callback errors, default to not changing classes
                //     $should_collapse = false;
                // }

                // if ($should_collapse && !in_array('closed', $classes, true)) {
                //     $classes[] = 'closed';
                // }

                // return $classes;
            });
        }
    }

    /**
     * Render status metabox (sidebar)
     */
    public function render_status_metabox($post, $metabox)
    {
        $booking_id = isset($_GET['booking_id']) ? absint($_GET['booking_id']) : 0;
        $booking = $this->booking_crud->get_booking($booking_id, get_current_blog_id());
        ?>
        <p class="status-change-container post-attributes-label-wrapper" data-booking-id="<?php echo $booking_id; ?>">
            <label for="booking-status-select"><strong><?php _e('Current Status:', 'organization-core'); ?></strong></label>
        </p>
        <select id="booking-status-select" class="widefat">
            <option value="pending" <?php selected($booking['status'], 'pending'); ?>>
                <?php _e('Pending', 'organization-core'); ?>
            </option>
            <option value="confirmed" <?php selected($booking['status'], 'confirmed'); ?>>
                <?php _e('Confirmed', 'organization-core'); ?>
            </option>
            <option value="completed" <?php selected($booking['status'], 'completed'); ?>>
                <?php _e('Completed', 'organization-core'); ?>
            </option>
            <option value="cancelled" <?php selected($booking['status'], 'cancelled'); ?>>
                <?php _e('Cancelled', 'organization-core'); ?>
            </option>
        </select>

        <!-- <hr style="margin:20px 0;"> -->

        <p class="price-change-container post-attributes-label-wrapper" data-booking-id="<?php echo $booking_id; ?>">
            <label for="booking-price-input"><strong><?php _e('Total Amount:', 'organization-core'); ?></strong></label>
        </p>
        <div style="display:flex;gap:8px;align-items:center;width: 100%;align-items: stretch;">
            <input type="number" id="booking-price-input" class="small" step="0.01" min="0"
                value="<?php echo number_format((float) $booking['total_amount'], 2, '.', ''); ?>" placeholder="0.00"
                style="width: 100%;">
            <button type="button" class="button" id="update-price-btn" style="display:flex; align-items:center;">
                <span class="mdi--floppy-disc-move-outline"></span>
            </button>
            <span class=""></span>
        </div>
        <div style="display:none;gap:8px;align-items:center;">
            <span><?php echo number_format((float) $booking['total_amount'], 2, '.', ''); ?></span>
            <span style="padding:8px;font-size:16px;border:1px;" class="dashicons dashicons-edit"></span>
        </div>
        <p class="description"><?php _e('Set the total booking amount', 'organization-core'); ?></p>
    <?php
    }

    /**
     * Render notes metabox (sidebar)
     */
    public function render_customer_notes_metabox($post, $metabox)
    {
        $booking_id = isset($_GET['booking_id']) ? absint($_GET['booking_id']) : 0;
        $booking = $this->booking_crud->get_booking($booking_id, get_current_blog_id());
        $notes = $booking['special_notes'] ?? 'Fake customer notes for testing purpose.';
    ?>
        <p>
            <?php echo esc_textarea($notes); ?>
        </p>
        <!-- <hr> -->
        <p style="font-size: smaller;" class="description">
            <?php _e('Customer added notes.', 'organization-core'); ?>
        </p>
    <?php
    }

    /**
     * Render notes metabox (sidebar)
     */
    public function render_internal_notes_metabox($post, $metabox)
    {
        $booking_id = isset($_GET['booking_id']) ? absint($_GET['booking_id']) : 0;
        $booking = $this->booking_crud->get_booking($booking_id, get_current_blog_id());
    ?>
        <p class="description">
            <?php _e('Internal notes for internal use only.', 'organization-core'); ?>
        </p>
        <hr>
        <p>
            <strong><?php _e('Existing Notes:', 'organization-core'); ?></strong>
            <!-- List of private and shared notes by different members -->
        <ul>
            <li>
                <p>
                    <span style="font-size: smaller; color: #666;">(2024-02-10 14:30)</span>
                    <br /><strong>Me</strong>: <em>Contacted customer.</em>
                </p>
            </li>
            <li>
                <p>
                    <span style="font-size: smaller; color: #666;">(2024-02-12 09:15)</span>
                    <br /><strong>John Doe</strong>: <em>Contacted customer for additional info.</em>
                </p>
            </li>
            <li>
                <p>
                    <span style="font-size: smaller; color: #666;">(2024-02-14 11:45)</span>
                    <br /><strong>Me</strong> (Private): <em>Need to share quotation.</em>
                </p>
            </li>
        </ul>
        </p>
        <hr>
        <p>
            <textarea id="internal-notes" class="widefat" rows="4"
                placeholder="<?php _e('Add internal notes...', 'organization-core'); ?>"><?php echo esc_textarea($booking['internal_notes'] ?? ''); ?></textarea>
        </p>
        <p>
            <select id="internal-notes-visibility" class="widefat">
                <option value="private"><?php _e('Private', 'organization-core'); ?></option>
                <option value="shared"><?php _e('Shared with Staff', 'organization-core'); ?></option>
            </select>
        </p>
        <p>
            <button type="button" class="button button-secondary" style="width:100%;">
                <?php _e('Save Notes', 'organization-core'); ?>
            </button>
        </p>
    <?php
    }

    /**
     * Render assign to staff metabox (sidebar)
     */
    public function render_booking_assign_staff_metabox($post, $metabox)
    {
        $booking_id = isset($_GET['booking_id']) ? absint($_GET['booking_id']) : 0;
        // Mocked staff list for demonstration
        $staff_members = array(
            array('id' => 1, 'name' => 'John Doe'),
            array('id' => 2, 'name' => 'Jane Smith'),
            array('id' => 3, 'name' => 'Alice Johnson'),
        );

        $assiged_staff_id = 1; // Mocked assigned staff ID

    ?>
        <p class="description">Assigned to: <span
                style="font-weight: 600;"><?php echo esc_html($staff_members[array_search($assiged_staff_id, array_column($staff_members, 'id'))]['name']); ?></span>
        </p>
        <select id="assign-staff-select" class="widefat">
            <option value=""><?php _e('Select Staff Member', 'organization-core'); ?></option>
            <?php foreach ($staff_members as $staff): ?>
                <option value="<?php echo esc_attr($staff['id']); ?>" <?php selected($assiged_staff_id, $staff['id']); ?>>
                    <?php echo esc_html($staff['name']); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <p>
            <textarea id="staff-notes" class="widefat" rows="4"
                placeholder="<?php _e('Add notes for staff member...', 'organization-core'); ?>"></textarea>
        </p>
        <p style="margin-top: 10px;">
            <button type="button" class="button button-secondary" style="width:100%;">
                <?php _e('Assign Staff', 'organization-core'); ?>
            </button>
        </p>
    <?php
    }

    /**
     * Render booking history metabox
     */
    public function render_booking_history_metabox($post, $metabox)
    {
        $booking_id = isset($_GET['booking_id']) ? absint($_GET['booking_id']) : 0;
        // $history = $this->booking_crud->get_booking_history($booking_id, get_current_blog_id());
        // Mocked history for demonstration
        $history = array(
            array('timestamp' => '2024-01-15 10:00:00', 'action' => 'Booking created.'),
            array('timestamp' => '2024-01-20 14:30:00', 'action' => 'Status changed to Confirmed.'),
            array('timestamp' => '2024-02-01 09:15:00', 'action' => 'Total amount updated to $500.00.'),
        );
    ?>
        <div>
            <?php if (!empty($history)): ?>
                <ul class="booking-history">
                    <?php foreach ($history as $index => $entry): ?>
                        <!-- need to print index in span -->
                        <li style="display: flex;align-items: center;gap:8px; width: 100%;">
                            <span class="index"><?php echo $index + 1; ?></span>
                            <p>
                                <strong><?php echo esc_html($entry['action']); ?></strong><br />
                                <i style="font-size: x-small;"><?php echo date('F j, Y g:i A', strtotime($entry['timestamp'])); ?></i>
                            </p>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <!-- Last sync -->
                <p class="description"><?php echo esc_html(__('Last sync: ', 'organization-core') . date('F j, Y g:i A')); ?></p>
            <?php else: ?>
                <p><?php _e('No history available for this booking.', 'organization-core'); ?></p>
            <?php endif; ?>
        </div>
        <style>
            .booking-history {
                /* border-left: 1px solid #d5d5d5; */
            }

            .booking-history .index {
                font-size: 10px;
                padding: 2px 6px;
                /* border-radius: 100%; */
                background-color: white;
                border: 1px solid #d5d5d5;
                z-index: 1;
            }

            .booking-history li {
                position: relative;
                width: 100%;
                /* padding-left: 20px; */
            }

            .booking-history li::before {
                position: absolute;
                left: 0;
                top: 50%;
                transform: translateY(-50%);
                content: '';
                display: inline-block;
                width: 27px;
                height: 1px;
                background-color: #d5d5d5;
                vertical-align: middle;
                z-index: 0;
            }

            .booking-history li p {
                position: relative;
                padding: 10px;
                border: 1px solid #d5d5d5;
                z-index: 1;
                /* background-color: lightgray;  */
                width: 100%;
                margin-top: unset;
                margin-bottom: 1em;
            }
        </style>
<?php
    }
}
