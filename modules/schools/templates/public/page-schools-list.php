<?php

/**
 * Schools List Page - Business Logic & Data Initialization
 * 
 * Extracts and prepares all data for the HTML template
 * Uses new CRUD system with numeric school IDs
 * All operations use school['id'] for database consistency
 * 
 * @package Organization_Core
 * @subpackage Modules/Schools
 * @since 1.0.0
 */

if (!defined('WPINC')) {
    die;
}

// ================================================
// 1. AUTHENTICATION CHECK
// ================================================

if (!is_user_logged_in()) {
    wp_redirect(home_url('/login'));
    exit;
}

// ================================================
// 2. SESSION INITIALIZATION
// ================================================

if (!session_id()) {
    session_start();
}

// ================================================
// 3. CURRENT USER & ID EXTRACTION
// ================================================

$current_user = wp_get_current_user();
$user_id = get_current_user_id();

// ================================================
// 4. STORE CURRENT URL FOR BACK NAVIGATION
// ================================================

$current_full_url = (
    (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') .
    '://' . sanitize_text_field($_SERVER['HTTP_HOST']) .
    sanitize_text_field($_SERVER['REQUEST_URI'])
);

$_SESSION['mus_schools_list_url'] = $current_full_url;

// ================================================
// 5. GET USER SCHOOLS USING CRUD
// ================================================

// ✅ NEW: Use universal CRUD function - Returns array keyed by ID
$user_schools = get_user_schools_for_display($user_id);

// ================================================
// 6. PREPARE DISPLAY DATA
// ================================================

// Calculate schools count
$schools_count = count($user_schools ?: []);

// Prepare display name
$display_name = $current_user->display_name ?: $current_user->user_login;

// ================================================
// 7. BUILD URL PARAMETERS FOR ACTIONS
// ================================================

$add_school_url = add_query_arg(
    ['ref' => urlencode($current_full_url)],
    home_url('/my-account/schools/add/')
);

// ================================================
// 8. PREPARE HERO DATA
// ================================================

$hero_data = [];
if (class_exists('MUS_Theme_Compatibility') && method_exists('MUS_Theme_Compatibility', 'get_hero_config')) {
    $hero_data = MUS_Theme_Compatibility::get_hero_config('My Schools');
} else {
    // Fallback hero data
    $hero_data = [
        'background_image' => get_template_directory_uri() . '/images/breadcrumb-bg.jpg',
        'title' => 'My Schools',
        'subtitle' => 'Manage your registered schools'
    ];
}

// ================================================
// 9. START PRESENTATION
// ================================================

$booking_bg = OC_Asset_Handler::get_theme_image('hero-bg');
get_header();
?>

<style>
    table>tbody tr:nth-child(even) {
        background: unset !important;
    }
</style>

<!-- ================================================
     BREADCRUMB / HERO SECTION
     ================================================ -->
<section class="breadcrumb-main" style="background-image:url(<?php echo esc_url($booking_bg); ?>);">
    <div class="breadcrumb-outer">
        <div class="container">
            <div class="breadcrumb-content text-center pt-5 pb-1">
                <h5 class="theme mb-0">Forum Music Festival</h5>
                <h1 class="mb-3 white">My Schools</h1>

                <!-- User Info in Breadcrumb -->
                <div class="user-breadcrumb-info">
                    <h6 class="white mb-1">
                        <i class="fas fa-user me-2"></i>
                        <?php echo esc_html($current_user->display_name ?: $current_user->user_login); ?>
                    </h6>
                    <h6 class="white opacity-75">
                        <i class="fas fa-school me-2"></i>
                        <?php echo count($user_schools ?: []); ?> Schools Registered
                    </h6>
                </div>
            </div>
        </div>
    </div>
    <div class="bread-overlay"></div>
</section>

<!-- ================================================
     SCHOOLS LIST SECTION
     ================================================ -->
<section class="schools-section py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-12">
                <div class="card shadow-sm">
                    <!-- Card Header -->
                    <div class="card-header bg-theme d-flex justify-content-between align-items-center">
                        <h4 class="mb-0 text-white">
                            <i class="fas fa-graduation-cap me-2"></i>
                            Your Registered Schools
                        </h4>
                        <a href="<?php echo home_url('/my-account/schools/add/'); ?>?ref=<?php echo urlencode($current_full_url); ?>"
                            class="btn btn-light btn-sm">
                            <i class="fas fa-plus me-2"></i>Add New School
                        </a>
                    </div>

                    <!-- Card Body -->
                    <div class="card-body">
                        <?php if (!empty($user_schools) && is_array($user_schools)): ?>
                            <!-- ================================================
                                 SCHOOLS TABLE
                                 ================================================ -->
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead class="table-light">
                                        <tr>
                                            <th>School Name</th>
                                            <th>Address</th>
                                            <th>Phone</th>
                                            <th>Director</th>
                                            <th>Email</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($user_schools as $school_id => $school): ?>
                                            <!-- ✅ FIXED: Use numeric school['id'] for data attribute -->
                                            <tr data-school-id="<?php echo esc_attr($school['id']); ?>">
                                                <!-- School Name -->
                                                <td>
                                                    <strong><?php echo esc_html($school['school_name'] ?? 'Unknown School'); ?></strong>
                                                </td>

                                                <!-- Address -->
                                                <td>
                                                    <?php
                                                    $address_parts = array_filter([
                                                        $school['school_address'] ?? '',
                                                        $school['school_city'] ?? '',
                                                        $school['school_state'] ?? ''
                                                    ]);
                                                    echo esc_html(implode(', ', $address_parts) ?: 'N/A');
                                                    ?>
                                                </td>

                                                <!-- Phone -->
                                                <td><?php echo esc_html($school['school_phone'] ?? 'N/A'); ?></td>

                                                <!-- Director Name -->
                                                <td>
                                                    <?php
                                                    $director_name = trim(
                                                        ($school['director_prefix'] ?? '') . ' ' .
                                                            ($school['director_first_name'] ?? '') . ' ' .
                                                            ($school['director_last_name'] ?? '')
                                                    );
                                                    echo esc_html($director_name ?: 'N/A');
                                                    ?>
                                                </td>

                                                <!-- Director Email -->
                                                <td>
                                                    <?php if (!empty($school['director_email'])): ?>
                                                        <a href="mailto:<?php echo esc_attr($school['director_email']); ?>">
                                                            <?php echo esc_html($school['director_email']); ?>
                                                        </a>
                                                    <?php else: ?>
                                                        <span class="text-muted">N/A</span>
                                                    <?php endif; ?>
                                                </td>

                                                <!-- Actions -->
                                                <td>
                                                    <div class="btn-group btn-group-sm">
                                                        <!-- ✅ FIXED: Pass numeric school['id'] to functions -->
                                                        <button class="btn btn-outline-primary"
                                                            onclick="editSchool(<?php echo intval($school['id']); ?>)"
                                                            title="Edit School">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <button class="btn btn-outline-danger "
                                                            onclick="deleteSchool(<?php echo intval($school['id']); ?>)"
                                                            title="Delete School">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>

                        <?php else: ?>
                            <!-- ================================================
                                 EMPTY STATE
                                 ================================================ -->
                            <div class="text-center py-5">
                                <i class="fas fa-school fa-4x text-muted mb-3"></i>
                                <h5 class="text-muted">No Schools Found</h5>
                                <p class="text-muted">You haven't added any schools yet. Schools are required for making bookings.</p>
                                <a href="<?php echo home_url('/my-account/schools/add'); ?>"
                                    class="btn text-white bg-theme btn-lg">
                                    <i class="fas fa-plus me-2"></i>Add Your First School
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- ================================================
                     NAVIGATION LINKS
                     ================================================ -->
                <div class="text-center mt-4">
                    <a href="<?php echo home_url('/my-account'); ?>"
                        class="btn btn-outline-secondary me-2">
                        <i class="fas fa-arrow-left me-2"></i>Back to My Account
                    </a>
                    <a href="<?php echo home_url('/my-account/bookings'); ?>"
                        class="btn text-white bg-theme">
                        <i class="fas fa-calendar me-2"></i>View My Bookings
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<?php get_footer(); ?>