<?php

/**
 * Schools Module - Add/Edit School Page (COMPLETE)
 * 
 * FULLY MIGRATED from old plugin with:
 * - Complete HTML structure from previous version
 * - New CRUD integration
 * - Navigation context detection
 * - Pre-populated director details
 * - Country/State dropdowns
 * - Full validation & AJAX
 * 
 * @package Organization_Core
 * @subpackage Modules/Schools
 * @since 1.0.0
 */

if (! defined('WPINC')) {
    die;
}

// ================================================
// AUTHENTICATION & SESSION
// ================================================

if (! is_user_logged_in()) {
    wp_redirect(home_url('/login'));
    exit;
}

if (! session_id()) {
    session_start();
}

$current_user = wp_get_current_user();
$user_id = get_current_user_id();

// ================================================
// NAVIGATION CONTEXT DETECTION
// ================================================

$navigation_context = [
    'source' => 'unknown',
    'back_url' => '',
    'from_booking' => false,
    'is_edit' => false,
    'edit_key' => '',
    'package_info' => '',
    'debug_info' => []
];

// Detect if editing
$edit_key = sanitize_text_field($_GET['edit'] ?? '');
if (! empty($edit_key)) {
    $navigation_context['is_edit'] = true;
    $navigation_context['edit_key'] = $edit_key;
}

// ================================================
// PRIORITY-BASED BACK URL DETECTION
// ================================================

$back_url = '';

// PRIORITY 1: Explicit ref parameter
if (isset($_GET['ref']) && ! empty($_GET['ref'])) {
    $back_url = esc_url_raw($_GET['ref']);
    $navigation_context['source'] = 'ref_parameter';
} // PRIORITY 2: Session URL
elseif (isset($_SESSION['school_add_referrer']) && ! empty($_SESSION['school_add_referrer'])) {
    $back_url = $_SESSION['school_add_referrer'];
    $navigation_context['source'] = 'session_schools';
} // PRIORITY 3: HTTP Referrer
elseif (! empty($_SERVER['HTTP_REFERER'])) {
    $back_url = esc_url_raw($_SERVER['HTTP_REFERER']);
    $navigation_context['source'] = 'http_referrer';
}

// Validate URL origin
if (! empty($back_url)) {
    $parsed_url = parse_url($back_url);
    $path = $parsed_url['path'] ?? '';

    if (strpos($path, '/my-account') !== false || strpos($path, '/book-now') !== false) {
        $navigation_context['back_url'] = $back_url;
        if (strpos($path, '/book-now') !== false) {
            $navigation_context['from_booking'] = true;
        }
    }
}

// Fallback URL
if (empty($navigation_context['back_url'])) {
    $navigation_context['back_url'] = home_url('/my-account');
    $navigation_context['source'] = 'fallback';
}

// ================================================
// LOAD SCHOOL DATA (IF EDITING)
// ================================================

$school_data = [];
if ($navigation_context['is_edit'] && ! empty($edit_key)) {
    $school_data = get_user_school($user_id, $edit_key);

    if (empty($school_data)) {
        wp_redirect(home_url('/my-account/schools'));
        exit;
    }
}

// ================================================
// PAGE CONFIGURATION
// ================================================

$is_edit = $navigation_context['is_edit'];
$page_title = $is_edit ? 'Edit School' : 'Add New School';
$button_text = $is_edit ? 'Update School' : 'Save School';
$back_button_text = $navigation_context['from_booking'] ? 'Back to Booking' : 'Back to Account';

// ================================================
// USER DATA FOR PRE-POPULATION
// ================================================
$user_prefix = get_user_meta($user_id, 'user_prefix', true) ?? '';

$user_data = [
    'first_name' => $current_user->first_name ?? '',
    'last_name' => $current_user->last_name ?? '',
    'email' => $current_user->user_email ?? '',
    'prefix' => $user_prefix
];

// ================================================
// PRESENTATION LAYER
// ================================================
// Get saved school state value
$saved_school_state = $school_data['school_state'] ?? '';
$saved_school_country = $school_data['school_country'] ?? '';

$booking_bg = OC_Asset_Handler::get_theme_image('hero-bg');
get_header();
?>

<!-- HERO SECTION -->
<section class="breadcrumb-main <?php echo $navigation_context['from_booking'] ? 'booking-context' : 'account-context'; ?>" style="background-image: url('<?php echo esc_url($booking_bg); ?>'); background-size: cover; background-position: center;">
    <div class="breadcrumb-outer">
        <div class="container">
            <div class="breadcrumb-content text-center pt-5 pb-0">
                <h5 class="theme mb-0">Forum Music Festival</h5>
                <h1 class="mb-1 white"><?php echo esc_html($page_title); ?></h1>
                <?php if ($navigation_context['from_booking']) : ?>
                    <p class="white opacity-75 mb-0">
                        <i class="fas fa-ticket-alt me-2"></i>for Your Booking
                    </p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="bread-overlay"></div>
</section>

<!-- FORM SECTION -->
<section class="school-form-section py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="card shadow-lg">
                    <div class="card-body p-4">

                        <!-- Message Container -->
                        <div id="school-form-message" class="alert" role="alert" style="display:none;"></div>

                        <form id="addSchoolForm" class="needs-validation" novalidate>

                            <!-- Hidden Context Fields -->
                            <input type="hidden" name="school_id" id="school_id" value="<?php echo esc_attr($edit_key); ?>">
                            <!-- <input type="hidden" name="action" value="<?php echo $is_edit ? 'update_school' : 'add_school'; ?>"> -->
                            <input type="hidden" id="back_url" value="<?php echo esc_url($navigation_context['back_url']); ?>">
                            <input type="hidden" id="from_booking" value="<?php echo $navigation_context['from_booking'] ? '1' : '0'; ?>">
                            <input type="hidden" id="is_edit" value="<?php echo $is_edit ? '1' : '0'; ?>">

                            <!-- SCHOOL DETAILS SECTION -->
                            <div class="school-details-section mb-4">
                                <h5 class="section-title bg-theme text-white p-2 mb-3 rounded">
                                    <i class="fas fa-building me-2"></i>School Details
                                </h5>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="school_name" class="form-label">School Name <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="school_name" name="school_name"
                                            value="<?php echo esc_attr($school_data['school_name'] ?? ''); ?>"
                                            placeholder="Enter school name" required>
                                        <div class="invalid-feedback">Please provide a school name.</div>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label for="school_phone" class="form-label">
                                            School Phone <span class="text-danger">*</span>
                                        </label>
                                        <input type="tel"
                                            class="form-control phone-input"
                                            id="school_phone"
                                            name="school_phone"
                                            value="<?php echo esc_attr($school_data['school_phone'] ?? ''); ?>"
                                            placeholder="(123) 456-7890"
                                            pattern="\(\d{3}\)\s\d{3}-\d{4}"
                                            title="Phone format: (123) 456-7890"
                                            maxlength="14"
                                            required>
                                        <div class="invalid-feedback">Please provide a valid phone number in format (123) 456-7890</div>
                                        <small class="form-text text-muted">Format: (123) 456-7890</small>
                                    </div>

                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="school_address" class="form-label">School Address <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="school_address" name="school_address"
                                            value="<?php echo esc_attr($school_data['school_address'] ?? ''); ?>"
                                            placeholder="Street address" required>
                                        <div class="invalid-feedback">Please provide an address.</div>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label for="school_address_2" class="form-label">Address 2</label>
                                        <input type="text" class="form-control" id="school_address_2" name="school_address_2"
                                            value="<?php echo esc_attr($school_data['school_address_2'] ?? ''); ?>"
                                            placeholder="Suite, apartment, etc. (optional)">
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label for="school_city" class="form-label">City <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="school_city" name="school_city"
                                            value="<?php echo esc_attr($school_data['school_city'] ?? ''); ?>"
                                            placeholder="City" required>
                                        <div class="invalid-feedback">Please provide a city.</div>
                                    </div>

                                    <div class="col-md-4 mb-3">
                                        <label for="school_country" class="form-label">Country <span class="text-danger">*</span></label>
                                        <select class="form-select" id="school_country" name="school_country" required>
                                            <option value="">Select Country</option>
                                            <option value="US" <?php selected($school_data['school_country'] ?? '', 'US'); ?>>United States</option>
                                            <option value="CA" <?php selected($school_data['school_country'] ?? '', 'CA'); ?>>Canada</option>
                                        </select>
                                        <div class="invalid-feedback">Please select a country.</div>
                                    </div>

                                    <div class="col-md-4 mb-3">
                                        <label for="school_state" class="form-label">State <span class="text-danger">*</span></label>
                                        <select class="form-select"
                                            id="school_state"
                                            name="school_state"
                                            data-saved-state="<?php echo esc_attr($saved_school_state); ?>"
                                            data-saved-country="<?php echo esc_attr($saved_school_country); ?>"
                                            required>
                                            <option value="">Select State</option>
                                        </select>
                                        <div class="invalid-feedback">Please select a state.</div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="school_zip" class="form-label">Zip Code <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="school_zip" name="school_zip"
                                            value="<?php echo esc_attr($school_data['school_zip'] ?? ''); ?>"
                                            placeholder="Zip/Postal Code" required>
                                        <div class="invalid-feedback">Please provide a zip code.</div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="school_website" class="form-label">Website</label>
                                        <input type="url"
                                            class="form-control url-input"
                                            id="school_website"
                                            name="school_website"
                                            value="<?php echo esc_attr($school_data['school_website'] ?? ''); ?>"
                                            placeholder="https://school.com"
                                            pattern="https?://.+"
                                            title="Website must start with http:// or https://">
                                        <div class="invalid-feedback">Please provide a valid URL starting with http:// or https://</div>
                                        <small class="form-text text-muted">Include http:// or https://</small>
                                    </div>
                                </div>
                            </div>

                            <!-- DIRECTOR DETAILS SECTION -->
                            <div class="director-details-section mb-4">
                                <h5 class="section-title bg-theme3 text-white p-2 mb-3 rounded">
                                    <i class="fas fa-user-tie me-2"></i>Director's Details
                                </h5>

                                <div class="row">
                                    <div class="col-md-2 mb-3">
                                        <label for="director_prefix" class="form-label">Prefix <span class="text-danger">*</span></label>
                                        <select class="form-select" id="director_prefix" name="director_prefix" required>
                                            <option value="">Select</option>
                                            <option value="Mr" <?php echo ($is_edit ? ($school_data['director_prefix'] ?? '') : $user_prefix) === 'Mr' ? 'selected' : ''; ?>>Mr</option>
                                            <option value="Ms" <?php echo ($is_edit ? ($school_data['director_prefix'] ?? '') : $user_prefix) === 'Ms' ? 'selected' : ''; ?>>Ms</option>
                                            <option value="Mrs" <?php echo ($is_edit ? ($school_data['director_prefix'] ?? '') : $user_prefix) === 'Mrs' ? 'selected' : ''; ?>>Mrs</option>
                                            <option value="Dr" <?php echo ($is_edit ? ($school_data['director_prefix'] ?? '') : $user_prefix) === 'Dr' ? 'selected' : ''; ?>>Dr</option>
                                        </select>
                                        <div class="invalid-feedback">Please select a prefix.</div>
                                    </div>

                                    <div class="col-md-5 mb-3">
                                        <label for="director_first_name" class="form-label">First Name <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="director_first_name" name="director_first_name"
                                            value="<?php echo esc_attr($school_data['director_first_name'] ?? $user_data['first_name']); ?>"
                                            placeholder="First name" required>
                                        <div class="invalid-feedback">Please provide a first name.</div>
                                    </div>

                                    <div class="col-md-5 mb-3">
                                        <label for="director_last_name" class="form-label">Last Name <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="director_last_name" name="director_last_name"
                                            value="<?php echo esc_attr($school_data['director_last_name'] ?? $user_data['last_name']); ?>"
                                            placeholder="Last name" required>
                                        <div class="invalid-feedback">Please provide a last name.</div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="director_email" class="form-label">
                                            Email <span class="text-danger">*</span>
                                        </label>
                                        <input type="email"
                                            class="form-control email-input"
                                            id="director_email"
                                            name="director_email"
                                            value="<?php echo esc_attr($school_data['director_email'] ?? $user_data['email']); ?>"
                                            placeholder="director@school.com"
                                            pattern="[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}"
                                            title="Please enter a valid email address"
                                            required>
                                        <div class="invalid-feedback">Please provide a valid email address</div>
                                        <div class="valid-feedback">✓ Email looks good!</div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="director_cell_phone" class="form-label">Cell Phone</label>
                                        <input type="tel"
                                            class="form-control phone-input"
                                            id="director_cell_phone"
                                            name="director_cell_phone"
                                            value="<?php echo esc_attr($school_data['director_cell_phone'] ?? ''); ?>"
                                            placeholder="(123) 456-7890"
                                            pattern="\(\d{3}\)\s\d{3}-\d{4}"
                                            title="Phone format: (123) 456-7890"
                                            maxlength="14">
                                        <div class="invalid-feedback">Please provide a valid phone number in format (123) 456-7890</div>
                                        <small class="form-text text-muted">Format: (123) 456-7890</small>
                                    </div>
                                </div>
                            </div>

                            <!-- PRINCIPAL DETAILS SECTION -->
                            <div class="principal-details-section mb-4">
                                <h5 class="section-title bg-theme text-white p-2 mb-3 rounded">
                                    <i class="fas fa-user-graduate me-2"></i>Principal's Details (Optional)
                                </h5>

                                <div class="row">
                                    <div class="col-md-2 mb-3">
                                        <label for="principal_prefix" class="form-label">Prefix</label>
                                        <select class="form-select" id="principal_prefix" name="principal_prefix">
                                            <option value="">Select</option>
                                            <option value="Mr" <?php selected($school_data['principal_prefix'] ?? '', 'Mr'); ?>>Mr</option>
                                            <option value="Ms" <?php selected($school_data['principal_prefix'] ?? '', 'Ms'); ?>>Ms</option>
                                            <option value="Mrs" <?php selected($school_data['principal_prefix'] ?? '', 'Mrs'); ?>>Mrs</option>
                                            <option value="Dr" <?php selected($school_data['principal_prefix'] ?? '', 'Dr'); ?>>Dr</option>
                                        </select>
                                    </div>

                                    <div class="col-md-5 mb-3">
                                        <label for="principal_first_name" class="form-label">First Name</label>
                                        <input type="text" class="form-control" id="principal_first_name" name="principal_first_name"
                                            value="<?php echo esc_attr($school_data['principal_first_name'] ?? ''); ?>"
                                            placeholder="First name">
                                    </div>

                                    <div class="col-md-5 mb-3">
                                        <label for="principal_last_name" class="form-label">Last Name</label>
                                        <input type="text" class="form-control" id="principal_last_name" name="principal_last_name"
                                            value="<?php echo esc_attr($school_data['principal_last_name'] ?? ''); ?>"
                                            placeholder="Last name">
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="school_enrollment" class="form-label">School's Total Enrollment</label>
                                        <input type="number" class="form-control" id="school_enrollment" name="school_enrollment"
                                            value="<?php echo intval($school_data['school_enrollment'] ?? 0); ?>"
                                            placeholder="0" min="0">
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label for="school_notes" class="form-label">Additional Notes</label>
                                        <textarea class="form-control" id="school_notes" name="school_notes" rows="3" placeholder="Any additional information..."></textarea>
                                    </div>
                                </div>
                            </div>

                            <!-- FORM ACTIONS -->
                            <div class="form-actions text-center pt-4 border-top">
                                <button type="button" class="btn btn-outline-secondary me-2" id="goBackBtn">
                                    <i class="fas fa-arrow-left me-2"></i><?php echo esc_html($back_button_text); ?>
                                </button>
                                <button type="submit" class="btn bg-theme text-white" id="saveSchoolBtn">
                                    <i class="fas fa-save me-2"></i><?php echo esc_html($button_text); ?>
                                </button>
                            </div>
                        </form>

                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php get_footer();
