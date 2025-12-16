<?php

/**
 * Bookings List Page - New Plugin Architecture
 * Path: templates/public/my-account/page-bookings-list.php
 * 
 * Uses OC_Bookings_CRUD for data extraction
 * Compatible with both FMF and SMF multisite installations
 * 
 * @package    Organization_Core
 * @subpackage Bookings/Templates
 */

// ============================================
// SECURITY & AUTHENTICATION
// ============================================

if (!defined('ABSPATH')) {
    exit;
}

if (!is_user_logged_in()) {
    wp_redirect(home_url('/login'));
    exit;
}

// ============================================
// DATA PREPARATION
// ============================================

$current_user = wp_get_current_user();
$user_id = get_current_user_id();
$blog_id = get_current_blog_id();

$booking_crud = new OC_Bookings_CRUD();
// Fetch user bookings using CRUD class
$bookings = $booking_crud->get_bookings(
    $blog_id,
    array(
        'user_id' => $user_id,
        'number'  => -1, // Get all bookings
        'orderby' => 'created_at',
        'order'   => 'DESC'
    )
);
$total_bookings = count($bookings);

// ============================================
// PRESENTATION LAYER
// ============================================

get_header();

// Hero section configuration
$hero_data = array('background_image' => '');
if (class_exists('MUS_Theme_Compatibility')) {
    $hero_data = MUS_Theme_Compatibility::get_hero_config('My Bookings');
}

$booking_bg = OC_Asset_Handler::get_theme_image('hero-bg');
?>

<section class="breadcrumb-main" style="background-image:url('<?php echo esc_url($booking_bg); ?>');">
    <div class="breadcrumb-outer">
        <div class="container">
            <div class="breadcrumb-content text-center pt-5 pb-1">
                <h5 class="theme mb-0">Forum Music Festival</h5>
                <h1 class="mb-3 white">My Bookings</h1>

                <div class="user-breadcrumb-info">
                    <h6 class="white mb-1">
                        <i class="fas fa-user me-2"></i>
                        <?php echo esc_html($current_user->display_name ?: $current_user->user_login); ?>
                    </h6>
                    <h6 class="white opacity-75">
                        <i class="fas fa-calendar me-2"></i>
                        <?php echo esc_html($total_bookings); ?> Booking<?php echo $total_bookings !== 1 ? 's' : ''; ?> Found
                    </h6>
                </div>
            </div>
        </div>
    </div>
    <div class="bread-overlay"></div>
</section>
<!-- BreadCrumb Ends -->

<!-- Bookings Section -->
<section class="bookings-section py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-header bg-theme">
                        <h4 class="mb-0 text-white">
                            <i class="fas fa-calendar-alt me-2"></i>
                            Your Booking History
                        </h4>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($bookings)): ?>
                            <div class="table-responsive">
                                <table class="table table-hover custom-bookings-table">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Booking ID</th>
                                            <th>Package</th>
                                            <th>School</th>
                                            <th>Location</th>
                                            <th>Date</th>
                                            <th>Parks</th>
                                            <th>Status</th>
                                            <th>Rooming List</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($bookings as $booking):
                                            // ========================================
                                            // DATA EXTRACTION USING CRUD HELPERS
                                            // ========================================

                                            $booking_id = intval($booking['id']);
                                            $package_id = intval($booking['package_id']);
                                            $location_id = intval($booking['location_id']);

                                            // Get package name
                                            $package = get_post($package_id);
                                            $package_name = $package && !is_wp_error($package) ? $package->post_title : 'N/A';

                                            // School name (from table column)
                                            $school_name = !empty($booking['school_id']) ?
                                                esc_html($booking_crud->get_school($booking['school_id'])['school_name']) :
                                                'N/A';

                                            // Location name (from table column)
                                            $location_name = !empty($booking['location_id']) ?
                                                esc_html($booking_crud->get_location_name($location_id)) :
                                                'N/A';

                                            // Booking date (from date_selection column)
                                            $booking_date = !empty($booking['date_selection']) ?
                                                date('M j, Y', strtotime($booking['date_selection'])) :
                                                'Not specified';

                                            // Parks selection - Use CRUD helper method
                                            $parks_array = $booking['parks_selection'] ?? array();
                                            $other_park_name = $booking['other_park_name'] ?? '';

                                            // Get formatted park names using CRUD helper
                                            $park_names = $booking_crud->get_park_names(
                                                $parks_array,
                                                $other_park_name
                                            );

                                            // Status styling
                                            $status = strtolower($booking['status']);
                                            $status_class = 'secondary';

                                            switch ($status) {
                                                case 'confirmed':
                                                case 'completed':
                                                    $status_class = 'success';
                                                    break;
                                                case 'pending':
                                                    $status_class = 'warning';
                                                    break;
                                                case 'cancelled':
                                                    $status_class = 'danger';
                                                    break;
                                            }
                                        ?>
                                            <tr>
                                                <td><strong>#<?php echo esc_html($booking_id); ?></strong></td>
                                                <td><?php echo esc_html($package_name); ?></td>
                                                <td><?php echo $school_name; ?></td>
                                                <td><?php echo esc_html($location_name); ?></td>
                                                <td><?php echo esc_html($booking_date); ?></td>
                                                <td>
                                                    <?php if (!empty($park_names)): ?>
                                                        <div class="park-names-list">
                                                            <?php foreach ($park_names as $park_name): ?>
                                                                <div class="park-item"><?php echo esc_html($park_name); ?></div>
                                                            <?php endforeach; ?>
                                                        </div>
                                                    <?php else: ?>
                                                        <span class="text-muted">--</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <span class="badge bg-<?php echo esc_attr($status_class); ?>">
                                                        <?php echo esc_html(ucfirst($status)); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php 
                                                    // Check if booking has hotel data
                                                    $has_hotel = !empty($booking['hotel_data']);
                                                    if ($has_hotel): 
                                                    ?>
                                                        <a href="<?php echo esc_url(home_url('/my-account/rooming-list/' . $booking_id)); ?>" 
                                                           class="btn btn-sm bg-theme text-white">
                                                            <i class="fas fa-bed me-1"></i>Rooming List
                                                        </a>
                                                    <?php else: ?>
                                                        <span class="text-muted">--</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <!-- Empty State -->
                            <div class="text-center py-5">
                                <i class="fas fa-calendar-times fa-4x text-muted mb-3"></i>
                                <h5 class="text-muted">No Bookings Found</h5>
                                <p class="text-muted">You haven't made any bookings yet.</p>

                                <a href="<?php echo esc_url(home_url('/festival-registration')); ?>" class="btn btn-sw bg-theme text-white">
                                    <i class="fas fa-search me-2"></i>Browse Packages
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Navigation Buttons -->
                <div class="text-center mt-4">
                    <a href="<?php echo esc_url(home_url('/my-account')); ?>" class="btn btn-outline-secondary me-2">
                        <i class="fas fa-arrow-left me-2"></i>Back to My Account
                    </a>
                    <a href="<?php echo esc_url(home_url('/my-account/schools')); ?>" class="btn text-white bg-theme">
                        <i class="fas fa-school me-2"></i>View My Schools
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>
<!-- Bookings Section Ends -->

<style>
    /* Remove zebra striping */
    .custom-bookings-table tbody tr:nth-child(even) {
        background-color: transparent !important;
    }

    /* Park names styling */
    .park-names-list {
        max-width: 250px;
    }

    .park-item {
        padding: 2px 0;
        line-height: 1.6;
        white-space: normal;
        word-wrap: break-word;
    }

    .park-item:not(:last-child) {
        border-bottom: 1px solid #f0f0f0;
        margin-bottom: 4px;
        padding-bottom: 4px;
    }

    /* Table hover effect */
    .custom-bookings-table tbody tr:hover {
        background-color: rgba(0, 0, 0, 0.02) !important;
    }

    /* Responsive text on mobile */
    @media (max-width: 768px) {
        .custom-bookings-table {
            font-size: 0.875rem;
        }

        .park-names-list {
            max-width: 150px;
        }
    }
</style>

<?php get_footer(); ?>