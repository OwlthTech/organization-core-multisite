<?php
// ===== BUSINESS LOGIC SECTION =====
if (!session_id()) {
    session_start();
}

$current_booking_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";

// ========================================
// HELPER FUNCTIONS
// ========================================

function get_park_meal_options($term_id)
{
    $common_options = [
        '20' => '$20 + 10% handling fee',
        '15' => '$15 + 10% handling fee',
        'other' => 'Other - please contact Forum with amount +10% handling fee'
    ];
    $meta_options = get_term_meta($term_id, 'park_meal_options', true);
    if (!empty($meta_options) && is_array($meta_options)) {
        return $meta_options;
    }
    return $common_options;
}

function get_all_parks_data()
{
    $parks = get_terms(array(
        'taxonomy' => 'parks',
        'hide_empty' => false,
    ));
    $parks_data = array();
    $excluded_parks = ['medieval-times-dinner-tournament', 'pirates-dinner-adventure'];
    foreach ($parks as $park) {
        if (in_array($park->slug, $excluded_parks)) continue;
        $parks_data[] = array(
            'id' => $park->term_id,
            'name' => $park->name,
            'slug' => $park->slug,
            'options' => get_park_meal_options($park->term_id)
        );
    }
    return $parks_data;
}

function get_user_schools_for_booking($user_id)
{
    global $wpdb;
    $blog_id = get_current_blog_id();
    $schools_table = $wpdb->base_prefix . 'schools';
    $schools = $wpdb->get_results($wpdb->prepare(
        "SELECT id, school_name, school_address, school_city, school_state, school_zip, 
                school_phone, director_email, director_first_name, director_last_name
         FROM $schools_table 
         WHERE user_id = %d AND blog_id = %d AND status = 'active' 
         ORDER BY created_at DESC",
        $user_id,
        $blog_id
    ), ARRAY_A);
    return is_array($schools) ? $schools : array();
}

function get_location_configs($package_id)
{
    $location_configs_raw = get_post_meta($package_id, '_location_date_park_config', true);
    if (!is_array($location_configs_raw)) {
        return array();
    }
    $enhanced_configs = array();
    foreach ($location_configs_raw as $config) {
        $location_id = intval($config['location_id']);
        $date_strings = isset($config['dates']) && is_array($config['dates']) ? $config['dates'] : array();
        $park_ids = isset($config['parks']) && is_array($config['parks']) ? $config['parks'] : array();
        $all_festival_dates = get_post_meta($package_id, '_festival_dates', true);
        $dates_with_notes = array();
        if (is_array($all_festival_dates)) {
            foreach ($date_strings as $date_string) {
                $found = false;
                foreach ($all_festival_dates as $date_info) {
                    if (is_array($date_info) && isset($date_info['date'])) {
                        if ($date_info['date'] === $date_string) {
                            $dates_with_notes[] = array(
                                'date' => $date_string,
                                'note' => isset($date_info['note']) ? $date_info['note'] : ''
                            );
                            $found = true;
                            break;
                        }
                    }
                }
                if (!$found) {
                    $dates_with_notes[] = array('date' => $date_string, 'note' => '');
                }
            }
        }
        $park_objects = array();
        foreach ($park_ids as $park_id) {
            $park = get_term(intval($park_id), 'parks');
            if ($park && !is_wp_error($park)) {
                $park_objects[] = array(
                    'id' => $park->term_id,
                    'name' => $park->name,
                    'slug' => $park->slug
                );
            }
        }
        $enhanced_configs[$location_id] = array(
            'location_id' => $location_id,
            'dates' => $dates_with_notes,
            'parks' => $park_objects,
            'park_ids' => $park_ids
        );
    }
    return $enhanced_configs;
}

// ========================================
// MAIN LOGIC
// ========================================

$package_id = get_query_var('booking_package_id');
if (!$package_id) {
    echo '<div class="error-container"><h2>Package not found.</h2></div>';
    get_footer();
    return;
}

$package = get_post($package_id);
if (!$package || $package->post_type !== 'packages' || $package->post_status !== 'publish') {
    echo '<div class="error-container"><h2>Invalid or unavailable package.</h2></div>';
    get_footer();
    return;
}

$user_id = get_current_user_id();
$current_user = wp_get_current_user();
$school_required = get_post_meta($package->ID, '_school_package', true) === 'true';

$user_schools_details = array();
if ($school_required) {
    $user_schools_details = get_user_schools_for_booking($user_id);
}

$locations = wp_get_post_terms($package->ID, 'location');
$locations = ($locations && !is_wp_error($locations)) ? $locations : array();

$enhanced_configs = get_location_configs($package->ID);

$breadcrumb_data = array(
    'page_title' => $package->post_title,
    'subtitle' => 'Festival Package Booking',
    'background_image' => get_template_directory_uri() . '/assets/images/home-bg.png'
);

$booking_bg = OC_Asset_Handler::get_theme_image('hero-bg');
get_header();
?>

<section class="breadcrumb-main" style="background-image:url('<?php echo esc_url($booking_bg); ?>');">
    <div class="breadcrumb-outer">
        <div class="container">
            <div class="text-left mb-3"></div>
            <div class="breadcrumb-content text-center pt-14 pb-2">
                <h5 class="theme mb-0"><?php echo esc_html($breadcrumb_data['subtitle']); ?></h5>
                <h1 class="mb-0 white"><?php echo esc_html($breadcrumb_data['page_title']); ?></h1>
                <a href="<?php echo home_url('/packages/' . $package->post_name . '/'); ?>"
                    class="nir-btn btn-sm mt-2">
                    <i class="fas fa-arrow-left me-2 "></i>Back to Package
                </a>
            </div>
        </div>
    </div>
    <div class="bread-overlay"></div>
</section>

<section class="booking-section py-5" id="booking-top">
    <div class="container">
        <div class="multistep-container">
            <div id="smartwizard" class="sw">
                <ul class="nav nav-progress">
                    <?php $step_num = 1; ?>

                    <!-- STEP: SCHOOL SELECTION (CONDITIONAL) -->
                    <?php if ($school_required): ?>
                        <li class="nav-item">
                            <a class="nav-link default" href="#step-<?php echo $step_num; ?>">
                                <div class="num"><?php echo $step_num++; ?></div>
                                <span class="num-title">School</span>
                            </a>
                        </li>
                    <?php endif; ?>

                    <!-- STEP: LOCATION (ALWAYS) -->
                    <li class="nav-item">
                        <a class="nav-link default" href="#step-<?php echo $step_num; ?>">
                            <div class="num"><?php echo $step_num++; ?></div>
                            <span class="num-title">Location</span>
                        </a>
                    </li>

                    <!-- STEP: DATE (ALWAYS) -->
                    <li class="nav-item">
                        <a class="nav-link default" href="#step-<?php echo $step_num; ?>">
                            <div class="num"><?php echo $step_num++; ?></div>
                            <span class="num-title">Date</span>
                        </a>
                    </li>

                    <!-- STEP: PARKS (ALWAYS) -->
                    <li class="nav-item">
                        <a class="nav-link default" href="#step-<?php echo $step_num; ?>">
                            <div class="num"><?php echo $step_num++; ?></div>
                            <span class="num-title">Parks</span>
                        </a>
                    </li>

                    <!-- STEP: ADDITIONAL DETAILS (ALWAYS) -->
                    <li class="nav-item">
                        <a class="nav-link default" href="#step-<?php echo $step_num; ?>">
                            <div class="num"><?php echo $step_num++; ?></div>
                            <span class="num-title">Details</span>
                        </a>
                    </li>

                    <!-- STEP: ENSEMBLE (ALWAYS) -->
                    <li class="nav-item">
                        <a class="nav-link default" href="#step-<?php echo $step_num; ?>">
                            <div class="num"><?php echo $step_num++; ?></div>
                            <span class="num-title">Ensemble</span>
                        </a>
                    </li>
                </ul>

                <div class="tab-content">
                    <?php $step_num = 1; ?>

                    <!-- STEP 1: SCHOOL SELECTION (CONDITIONAL) -->
                    <?php if ($school_required): ?>
                        <div id="step-<?php echo $step_num; ?>" class="tab-pane" role="tabpanel">
                            <?php include plugin_dir_path(__FILE__) . 'steps/step-school-selection.php'; ?>
                        </div>
                        <?php $step_num++; ?>
                    <?php endif; ?>

                    <!-- STEP: LOCATION (ALWAYS) -->
                    <div id="step-<?php echo $step_num; ?>" class="tab-pane" role="tabpanel">
                        <?php include plugin_dir_path(__FILE__) . 'steps/step-location.php'; ?>
                    </div>
                    <?php $step_num++; ?>

                    <!-- STEP: DATE (ALWAYS) -->
                    <div id="step-<?php echo $step_num; ?>" class="tab-pane" role="tabpanel">
                        <?php include plugin_dir_path(__FILE__) . 'steps/step-date.php'; ?>
                    </div>
                    <?php $step_num++; ?>

                    <!-- STEP: PARKS (ALWAYS) -->
                    <div id="step-<?php echo $step_num; ?>" class="tab-pane" role="tabpanel">
                        <?php include plugin_dir_path(__FILE__) . 'steps/step-parks.php'; ?>
                    </div>
                    <?php $step_num++; ?>

                    <!-- STEP: ADDITIONAL DETAILS (ALWAYS) -->
                    <div id="step-<?php echo $step_num; ?>" class="tab-pane" role="tabpanel">
                        <?php include plugin_dir_path(__FILE__) . 'steps/step-additional-details.php'; ?>
                    </div>
                    <?php $step_num++; ?>

                    <!-- STEP: ENSEMBLE (ALWAYS) -->
                    <div id="step-<?php echo $step_num; ?>" class="tab-pane" role="tabpanel">
                        <?php include plugin_dir_path(__FILE__) . 'steps/step-ensemble.php'; ?>
                    </div>
                    <?php $step_num++; ?>
                    <?php
                    // ✅ Extract total steps from step counter
                    // At this point, $step_num = final count
                    $totalSteps = $step_num - 1;
                    ?>

                    <!-- Hidden Data for JavaScript -->
                    <input type="hidden" id="current_package_title" value="<?php echo esc_attr($package->post_title); ?>">

                    <script type="text/javascript">
                        window.allParksData = <?php echo json_encode(get_all_parks_data()); ?>;
                        window.packageTitle = '<?php echo esc_js($package->post_title); ?>';
                        window.schoolRequired = <?php echo $school_required ? 'true' : 'false'; ?>;
                    </script>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include plugin_dir_path(__FILE__) . 'modals/confirmation-modal.php'; ?>
<?php include plugin_dir_path(__FILE__) . 'modals/success-modal.php'; ?>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/smartwizard@6/dist/js/jquery.smartWizard.min.js"></script>
<script type="text/javascript">
    // ✅ FIXED: Correct nonce name
    window.packageId = <?php echo json_encode($package->ID); ?>;
    window.totalSteps = <?php echo json_encode($totalSteps); ?>;
    window.schoolRequired = <?php echo $school_required ? 'true' : 'false'; ?>;
    window.packageTitle = <?php echo json_encode($package->post_title); ?>;
    window.packageSlug = <?php echo json_encode($package->post_name); ?>;

    // ✅ ADD THIS LINE
    window.bookingNonce = '<?php echo wp_create_nonce('bookings_nonce'); ?>';
    window.ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';

    window.userData = <?php echo json_encode(array(
                            'id' => $user_id,
                            'name' => $current_user->display_name,
                            'email' => $current_user->user_email
                        )); ?>;

    window.schoolsData = <?php echo json_encode(array_values($user_schools_details)); ?>;

    window.locationsData = <?php echo json_encode(array_values(array_map(function ($loc) {
                                return array('id' => $loc->term_id, 'name' => $loc->name, 'slug' => $loc->slug);
                            }, $locations))); ?>;

    window.locationConfigs = <?php echo json_encode($enhanced_configs); ?>;

    window.allParksData = <?php echo json_encode(get_all_parks_data()); ?>;

    // ✅ KEEP FOR BACKWARD COMPATIBILITY
    window.bookingServerConfig = {
        ajaxUrl: '<?php echo admin_url('admin-ajax.php'); ?>',
        nonce: '<?php echo wp_create_nonce('bookings_nonce'); ?>',
        homeurl: '<?php echo home_url(); ?>'
    };
</script>


<?php get_footer(); ?>