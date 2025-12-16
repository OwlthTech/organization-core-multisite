<?php

/**
 * My Account Template for Authentication Module
 * Used with [mus_my_account] shortcode
 *
 * @package Organization_Core
 */

if (!defined('WPINC')) die;

$user_id = isset($user_id) ? $user_id : 0;
$blog_id = isset($blog_id) ? $blog_id : 0;
$user_profile = isset($user_profile) ? $user_profile : array();
$dashboard_bookings = isset($dashboard_bookings) ? $dashboard_bookings : array();
$total_bookings = isset($dashboard_bookings) ? count($dashboard_bookings) : 0;
$booking_counts = isset($booking_counts) ? $booking_counts : array();
$user_schools = isset($user_schools) ? $user_schools : array();
$total_schools = isset($total_schools) ? $total_schools : 0;
$dashboard_schools = isset($dashboard_schools) ? $dashboard_schools : array();

$current_user = wp_get_current_user();

$user_prefix = $user_profile['user_prefix'] ?? '';
$phone_number = $user_profile['phone_number'] ?? '';
$cell_number = $user_profile['cell_number'] ?? '';
$best_time_contact = $user_profile['best_time_contact'] ?? '';

// var_dump($dashboard_bookings);
if (!$user_id || !is_user_logged_in()) {
    echo '<div class="alert alert-danger">You must be logged in to view this page.</div>';
    return;
}

$badge_class = 'bg-theme3';
?>

<style>
    .list-group-item.active {
        z-index: 2;
        color: #fff !important;
        background-color: #f26422 !important;
        border-color: #f26422 !important;
    }
</style>

<!-- ✅ Pass data to JS via inline script -->
<script>
    window.mus_params = window.mus_params || {
        ajax_url: '<?php echo esc_js(is_multisite() ? get_site_url(null, "/wp-admin/admin-ajax.php") : admin_url("admin-ajax.php")); ?>',
        nonce: '<?php echo esc_js(wp_create_nonce("mus_nonce")); ?>',
        home_url: '<?php echo esc_js(get_site_url()); ?>',
        blog_id: <?php echo get_current_blog_id(); ?>,
        is_multisite: <?php echo is_multisite() ? 'true' : 'false'; ?>
    };
</script>

<section class="account-section py-5">
    <div class="container">
        <!-- ✅ Fixed Notification Container -->
        <div id="mus-fixed-notification" style="position: fixed; top: 80px; right: 20px; z-index: 9999; max-width: 400px; min-width: 300px;"></div>

        <div class="row">
            <!-- ✅ Sidebar Navigation -->
            <div class="col-lg-3 mb-4">
                <div class="card shadow-sm">
                    <div class="card-body p-0">
                        <div class="list-group list-group-flush">
                            <!-- Profile Tab -->
                            <a href="#profile"
                                class="list-group-item list-group-item-action active d-flex align-items-center py-3"
                                data-bs-toggle="tab"
                                data-bs-target="#profile">
                                <i class="fas fa-user me-3"></i>
                                <span>My Profile</span>
                            </a>

                            <!-- Bookings Tab -->
                            <a href="#bookings"
                                class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-3"
                                data-bs-toggle="tab"
                                data-bs-target="#bookings">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-calendar me-3"></i>
                                    <span>My Bookings</span>
                                </div>
                                <span class="badge <?php echo esc_attr($badge_class); ?> rounded-pill">
                                    <?php echo intval($total_bookings); ?>
                                </span>
                            </a>

                            <!-- Schools Tab -->
                            <?php
                            $user_schools_for_badge = get_user_schools_for_display($user_id);
                            $total_schools_count = count($user_schools_for_badge ?: []);
                            ?>
                            <a href="#schools"
                                class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-3"
                                data-bs-toggle="tab"
                                data-bs-target="#schools">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-school me-3"></i>
                                    <span>My Schools</span>
                                </div>
                                <span class="badge <?php echo esc_attr($badge_class); ?> rounded-pill schools-badge">
                                    <?php echo intval($total_schools_count); ?>
                                </span>
                            </a>

                            <!-- Logout -->
                            <a href="javascript:void(0)"
                                class="list-group-item list-group-item-action text-danger d-flex align-items-center py-3 border-top"
                                onclick="handleLogout(); return false;">
                                <i class="fas fa-sign-out-alt me-3"></i>
                                <span>Logout</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ✅ Main Content -->
            <div class="col-lg-9">
                <div class="tab-content">
                    <!-- Profile Tab -->
                    <div class="tab-pane fade show active" id="profile">
                        <div class="card">
                            <div class="card-header">
                                <h4 class="mb-0 theme"><i class="fas fa-user me-2"></i>My Profile</h4>
                            </div>
                            <div class="card-body">
                                <form id="mus-profile-form" class="mus-my-account">
                                    <!-- Personal Information Section -->
                                    <div class="row mb-4">
                                        <div class="col-12">
                                            <h5 class="border-bottom pb-2">
                                                <i class="fas fa-id-card me-2"></i>Personal Information
                                            </h5>
                                        </div>

                                        <div class="col-md-3 mb-3">
                                            <label class="form-label">Prefix</label>
                                            <select class="form-select" name="user_prefix">
                                                <option value="">Select</option>
                                                <option value="Mr" <?php selected($user_prefix, 'Mr'); ?>>Mr</option>
                                                <option value="Ms" <?php selected($user_prefix, 'Ms'); ?>>Ms</option>
                                                <option value="Mrs" <?php selected($user_prefix, 'Mrs'); ?>>Mrs</option>
                                                <option value="Dr" <?php selected($user_prefix, 'Dr'); ?>>Dr</option>
                                            </select>
                                        </div>

                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">First Name <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" name="first_name"
                                                value="<?php echo esc_attr($current_user->first_name); ?>" required>
                                        </div>

                                        <div class="col-md-5 mb-3">
                                            <label class="form-label">Last Name <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" name="last_name"
                                                value="<?php echo esc_attr($current_user->last_name); ?>" required>
                                        </div>
                                    </div>

                                    <!-- Contact Information Section -->
                                    <div class="row mb-4">
                                        <div class="col-12">
                                            <h5 class="border-bottom pb-2">
                                                <i class="fas fa-address-card me-2"></i>Contact Information
                                            </h5>
                                        </div>

                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Email Address <span class="text-danger">*</span></label>
                                            <input type="email"
                                                class="form-control email-input"
                                                id="user_email"
                                                name="user_email"
                                                value="<?php echo esc_attr($current_user->user_email); ?>"
                                                required
                                                placeholder="example@domain.com">
                                            <div class="invalid-feedback">Please provide a valid email address</div>
                                        </div>

                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Contact Phone Number <span class="text-danger">*</span></label>
                                            <input type="tel"
                                                class="form-control phone-input"
                                                id="phone_number"
                                                name="phone_number"
                                                value="<?php echo esc_attr($phone_number); ?>"
                                                required
                                                placeholder="(123) 456-7890"
                                                maxlength="14">
                                            <small class="form-text text-muted">Format: (123) 456-7890 - Auto-formatted</small>
                                        </div>

                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Contact Cell Number <span class="text-muted">(Optional)</span></label>
                                            <input type="tel"
                                                class="form-control phone-input"
                                                id="cell_number"
                                                name="cell_number"
                                                value="<?php echo esc_attr($cell_number); ?>"
                                                placeholder="(123) 456-7890"
                                                maxlength="14">
                                            <small class="form-text text-muted">Format: (123) 456-7890 - Auto-formatted</small>
                                        </div>

                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Best time to contact</label>
                                            <input type="text" class="form-control" name="best_time_contact"
                                                value="<?php echo esc_attr($best_time_contact); ?>"
                                                placeholder="e.g., Morning, Afternoon, Evening">
                                        </div>
                                    </div>

                                    <!-- Form Actions -->
                                    <div class="row">
                                        <div class="col-12">
                                            <hr class="my-4">
                                            <div class="d-flex justify-content-between">
                                                <a href="<?php echo home_url('/reset-password/'); ?>" class="nir-btn bg-secondary">
                                                    <i class="fas fa-key me-2"></i>Change Password
                                                </a>
                                                <button type="submit" class="nir-btn bg-theme" id="updateProfileBtn">
                                                    <i class="fas fa-save me-2"></i>Update Profile
                                                    <span class="spinner-border spinner-border-sm d-none ms-2" role="status"></span>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Bookings Tab -->
                    <div class="tab-pane fade" id="bookings">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h4 class="mb-0 theme"><i class="fas fa-calendar me-2"></i>Recent Bookings</h4>
                                <?php if ($total_bookings > 0): ?>
                                    <a href="<?php echo home_url('/my-account/bookings'); ?>" class="btn btn-sm bg-theme text-white">
                                        <i class="fas fa-list me-1"></i>View All
                                    </a>
                                <?php endif; ?>
                            </div>
                            <div class="card-body">
                                <?php if (!empty($dashboard_bookings)): ?>
                                    <?php foreach ($dashboard_bookings as $booking): ?>
                                        <?php
                                        // Support both array and object booking payloads for multisite compatibility.
                                        $package_id = is_array($booking) ? ($booking['package_id'] ?? null) : ($booking->package_id ?? null);
                                        $school_name_raw = is_array($booking) ? ($booking['school_name'] ?? '') : ($booking->school_name ?? '');
                                        $date_selection = is_array($booking) ? ($booking['date_selection'] ?? '') : ($booking->date_selection ?? '');
                                        $status_raw = is_array($booking) ? ($booking['status'] ?? '') : ($booking->status ?? '');
                                        $booking_id = is_array($booking) ? ($booking['id'] ?? '') : ($booking->id ?? '');
                                        $hotel_data = is_array($booking) ? ($booking['hotel_data'] ?? '') : ($booking->hotel_data ?? '');

                                        $package_name = !empty($package_id) ? get_the_title($package_id) : '--';
                                        $school_name = !empty($school_name_raw) ? $school_name_raw : '--';
                                        $booking_date = !empty($date_selection) ? date('M d, Y', strtotime($date_selection)) : '--';
                                        $status = !empty($status_raw) ? $status_raw : 'pending';
                                        $has_hotel = !empty($hotel_data);

                                        $badge_class = 'secondary';
                                        switch ($status) {
                                            case 'confirmed':
                                                $badge_class = 'success';
                                                break;
                                            case 'pending':
                                                $badge_class = 'warning';
                                                break;
                                            case 'cancelled':
                                                $badge_class = 'danger';
                                                break;
                                            case 'completed':
                                                $badge_class = 'info';
                                                break;
                                        }
                                        ?>

                                        <div class="border-start border-4 border-primary rounded p-3 mb-3 bg-light">
                                            <div class="row align-items-center">
                                                <div class="col-md-6">
                                                    <h6 class="mb-1 fw-bold"><?php echo esc_html($package_name); ?></h6>
                                                    <small class="text-muted">
                                                        <i class="fas fa-calendar me-1"></i><?php echo esc_html($booking_date); ?>
                                                        <?php if ($school_name !== '--'): ?>
                                                            | <i class="fas fa-school me-1"></i><?php echo esc_html($school_name); ?>
                                                        <?php endif; ?>
                                                    </small>
                                                </div>

                                                <div class="col-md-2 text-center">
                                                    <span class="badge bg-<?php echo $badge_class; ?>">
                                                        <?php echo ucfirst($status); ?>
                                                    </span>
                                                </div>

                                                <div class="col-md-2 text-center">
                                                    <?php if ($has_hotel): ?>
                                                        <a href="<?php echo esc_url(home_url('/my-account/rooming-list/' . $booking_id)); ?>" 
                                                           class="btn btn-sm bg-theme3 text-white">
                                                            <i class="fas fa-bed me-1"></i>Rooming List
                                                        </a>
                                                    <?php else: ?>
                                                        <span class="text-muted small center">No Hotel Assigned</span>
                                                    <?php endif; ?>
                                                </div>

                                                <div class="col-md-2 text-end">
                                                    <small class="text-muted">#<?php echo esc_html($booking_id); ?></small>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="text-center py-5">
                                        <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                                        <h6>No Bookings Yet</h6>
                                        <p class="text-muted">Start by browsing our packages</p>
                                        <a href="<?php echo home_url('/festival-registration'); ?>" class="btn btn-sm bg-theme text-white">
                                            <i class="fas fa-search me-1"></i>Browse Packages
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Schools Tab -->
                    <div class="tab-pane fade" id="schools">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h4 class="mb-0 theme"><i class="fas fa-school me-2"></i>My Schools</h4>
                                <a href="<?php echo home_url('/my-account/schools/add'); ?>" class="btn btn-sm bg-theme text-white">
                                    <i class="fas fa-plus me-1"></i>Add School
                                </a>
                            </div>
                            <div class="card-body">
                                <?php
                                $user_schools_data = get_user_schools_for_display($user_id);

                                if (!empty($user_schools_data) && is_array($user_schools_data)):
                                ?>
                                    <?php foreach ($user_schools_data as $school_id => $school): ?>
                                        <div class="border rounded p-3 mb-3 bg-light" data-school-id="<?php echo esc_attr($school['id']); ?>">
                                            <div class="row align-items-center">
                                                <div class="col-md-7">
                                                    <h6 class="mb-1 fw-bold">
                                                        <i class="fas fa-graduation-cap me-1"></i>
                                                        <?php echo esc_html($school['school_name'] ?? 'Unknown School'); ?>
                                                    </h6>
                                                    <small class="text-muted">
                                                        <?php if (!empty($school['school_city']) && !empty($school['school_state'])): ?>
                                                            <i class="fas fa-map-marker-alt me-1"></i>
                                                            <?php echo esc_html($school['school_city'] . ', ' . $school['school_state']); ?>
                                                        <?php endif; ?>

                                                        <?php if (!empty($school['school_phone'])): ?>
                                                            | <i class="fas fa-phone me-1"></i>
                                                            <?php echo esc_html($school['school_phone']); ?>
                                                        <?php endif; ?>
                                                    </small>
                                                </div>
                                                <div class="col-md-5 text-end">
                                                    <a href="<?php echo home_url('/my-account/schools/add?edit=' . intval($school['id']) . '&ref=' . urlencode(home_url('/my-account'))); ?>"
                                                        class="btn btn-sm bg-theme3 text-white me-1"
                                                        title="Edit School">
                                                        <i class="fas fa-edit"></i> Edit
                                                    </a>

                                                    <button type="button"
                                                        class="btn btn-sm bg-theme text-white delete-school-btn"
                                                        data-school-id="<?php echo intval($school['id']); ?>"
                                                        title="Delete School">
                                                        <i class="fas fa-trash"></i> Delete
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="text-center py-5">
                                        <i class="fas fa-school fa-3x text-muted mb-3"></i>
                                        <h6>No Schools Added</h6>
                                        <p class="text-muted">Add your school to start making bookings</p>
                                        <a href="<?php echo home_url('/my-account/schools/add'); ?>" class="nir-btn btn-sm">
                                            <i class="fas fa-plus me-1"></i>Add Your School
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>