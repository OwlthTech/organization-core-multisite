<?php

/**
 * Rooming List Public Page Template
 * Path: modules/rooming-list/templates/public/page-rooming-list.php
 * 
 * @package    Organization_Core
 * @subpackage Rooming_List/Templates
 */

// Security & Authentication
if (!defined('ABSPATH')) {
    exit;
}

if (!is_user_logged_in()) {
    wp_redirect(home_url('/login'));
    exit;
}

// Data Preparation
$current_user = wp_get_current_user();
$user_id = get_current_user_id();
$blog_id = get_current_blog_id();
$booking_id = get_query_var('rooming_list_booking_id');

if (!$booking_id) {
    wp_die('Invalid booking ID');
}

// Load CRUD classes
require_once plugin_dir_path(dirname(dirname(__FILE__))) . 'crud.php';
$bookings_crud_path = WP_PLUGIN_DIR . '/organization-core-new/modules/bookings/crud.php';
if (file_exists($bookings_crud_path)) {
    require_once $bookings_crud_path;
}

// Get booking details
$booking = OC_Bookings_CRUD::get_booking($booking_id, $blog_id);

if (!$booking || $booking['user_id'] != $user_id) {
    wp_die('You do not have permission to access this rooming list.');
}

// Check if booking has hotel data
if (empty($booking['hotel_data'])) {
    wp_die('This booking does not have hotel accommodations assigned.');
}

// Parse hotel data
$hotel_data = is_string($booking['hotel_data']) 
    ? json_decode($booking['hotel_data'], true) 
    : $booking['hotel_data'];

if (empty($hotel_data['hotel_id'])) {
    wp_die('No hotel assigned to this booking.');
}

// Get room and member limits
$max_per_room = isset($hotel_data['count_per_room']) ? intval($hotel_data['count_per_room']) : 4;
$rooms_allotted = isset($hotel_data['rooms_allotted']) ? intval($hotel_data['rooms_allotted']) : 0;
if ($max_per_room < 1) $max_per_room = 4;

// Get check-in/check-out dates
$checkin_date = isset($hotel_data['checkin_date']) ? $hotel_data['checkin_date'] : 'N/A';
$checkout_date = isset($hotel_data['checkout_date']) ? $hotel_data['checkout_date'] : 'N/A';

// Get hotel name and address from hotel_data
$hotel_name = isset($hotel_data['hotel_name']) ? $hotel_data['hotel_name'] : 'N/A';
$hotel_address = isset($hotel_data['hotel_address']) ? $hotel_data['hotel_address'] : '';

// Get school name
$school_name = 'N/A';
if (!empty($booking['school_id'])) {
    $school = OC_Bookings_CRUD::get_school($booking['school_id'], $blog_id);
    if ($school) {
        $school_name = $school['school_name'];
    }
}

get_header();

// Hero section configuration
$hero_bg = '';
if (class_exists('OC_Asset_Handler')) {
    $hero_bg = OC_Asset_Handler::get_theme_image('hero-bg');
}
?>

<!-- Material Symbols -->
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" />

<section class="breadcrumb-main" style="background-image:url('<?php echo esc_url($hero_bg); ?>');">
    <div class="breadcrumb-outer">
        <div class="container">
            <div class="breadcrumb-content text-center pt-5 pb-1">
                <h5 class="theme mb-0">Manage My Account</h5>
                <h1 class="mb-3 white">Rooming List - Booking #<?php echo esc_html($booking_id); ?></h1>

                <div class="user-breadcrumb-info">
                    <h6 class="white mb-1">
                        <i class="fas fa-user me-2"></i>
                        <?php echo esc_html($current_user->display_name ?: $current_user->user_login); ?>
                    </h6>
                    <h6 class="white opacity-75">
                        <i class="fas fa-hotel me-2"></i>
                        <?php echo esc_html($hotel_name); ?>
                    </h6>
                </div>
            </div>
        </div>
    </div>
    <div class="bread-overlay"></div>
</section>

<!-- Rooming List Section -->
<section class="rooming-list-public-section py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-12">
                
                <!-- Hotel Information Card -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-theme">
                        <h4 class="mb-0 text-white">
                            <i class="fas fa-hotel me-2"></i>
                            Hotel Information
                        </h4>
                    </div>
                    <div class="card-body">
                        <div class="hotel-info-grid">
                            <div class="info-item">
                                <strong>Booking ID:</strong>
                                <span>#<?php echo esc_html($booking_id); ?></span>
                            </div>
                            <div class="info-item">
                                <strong>School:</strong>
                                <span><?php echo esc_html($school_name); ?></span>
                            </div>
                            <div class="info-item">
                                <strong>Hotel:</strong>
                                <span><?php echo esc_html($hotel_name); ?></span>
                            </div>
                            <?php if ($hotel_address): ?>
                            <div class="info-item">
                                <strong>Address:</strong>
                                <span><?php echo esc_html($hotel_address); ?></span>
                            </div>
                            <?php endif; ?>
                            <div class="info-item">
                                <strong>Check-in:</strong>
                                <span><?php echo esc_html($checkin_date); ?></span>
                            </div>
                            <div class="info-item">
                                <strong>Check-out:</strong>
                                <span><?php echo esc_html($checkout_date); ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Rooming List Management -->
                <div class="card shadow-sm">
                    <div class="card-body">
                        <p class="description mb-3">
                            <?php printf(
                                __('Limits: <strong>%d</strong> Rooms Allowed | <strong>%d</strong> Occupants per Room', 'organization-core'),
                                $rooms_allotted,
                                $max_per_room
                            ); ?>
                        </p>

                        <?php
                        // Determine back URL based on HTTP Referer
                        $referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
                        $back_url = home_url('/my-account/bookings'); // Default
                        $back_text = '< Back to Bookings';
                        
                        // Check if user came from my-account page
                        if (!empty($referer) && strpos($referer, '/my-account') !== false && strpos($referer, '/my-account/bookings') === false) {
                            $back_url = home_url('/my-account#bookings');
                            $back_text = '< Back to My Account';
                        }
                        ?>

                        <!-- Top Action Buttons -->
                        <div class="mb-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <button type="button" class="btn bg-theme text-white" id="btn-add-room-public">
                                <?php _e('Add Room', 'organization-core'); ?>
                            </button>
                            <a href="<?php echo esc_url($back_url); ?>" class="btn btn-secondary">
                                <?php echo esc_html($back_text); ?>
                            </a>
                        </div>

                        <!-- Rooming List Container -->
                        <div id="rooming-list-container">
                            <!-- Table Structure (3 columns - Room info in headers above) -->
                            <table class="table table-bordered table-striped" id="rooming-list-table-public">
                                <thead>
                                    <tr>
                                        <th><?php _e('Name', 'organization-core'); ?></th>
                                        <th><?php _e('Type', 'organization-core'); ?></th>
                                        <th><?php _e('Action', 'organization-core'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- JS will populate this -->
                                </tbody>
                            </table>
                        </div>

                        <!-- Bottom Action Buttons -->
                        <div class="mt-3 d-flex justify-content-end align-items-center flex-wrap gap-2">
                            <button type="button" class="btn bg-theme3 text-white" id="btn-save-list-public">
                                <?php _e('Save list', 'organization-core'); ?>
                            </button>
                            <button type="button" class="btn bg-theme text-white" id="btn-save-lock-list-public">
                                <?php _e('Save & lock list', 'organization-core'); ?>
                            </button>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</section>

<?php get_footer(); ?>
