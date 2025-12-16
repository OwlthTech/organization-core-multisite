<?php

/**
 * Template for Request a Quote page
 * ✅ CLEANED: All CSS and JS moved to external files
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

get_header();

global $post;
$target_page_id = $post->ID;

// ===== GET DESTINATION FROM URL =====
$url_destination_slug = get_query_var('destination_slug');
$url_destination_name = '';
$matched_destination_id = null;

if (!empty($url_destination_slug)) {
    $destination_post = get_page_by_path($url_destination_slug, OBJECT, 'destination');

    if ($destination_post) {
        $matched_destination_id = $destination_post->ID;
        $url_destination_name = get_the_title($destination_post->ID);
    }
}

if (empty($url_destination_name) && isset($_GET['destination'])) {
    $url_destination_name = sanitize_text_field($_GET['destination']);
}

// Get current user email if logged in
$prefill_email = '';
if (is_user_logged_in()) {
    $current_user = wp_get_current_user();
    $prefill_email = $current_user->user_email;
}

// Get all destinations for dropdown
$all_destinations = get_posts(array(
    'post_type' => 'destination',
    'posts_per_page' => -1,
    'post_status' => 'publish',
    'orderby' => 'title',
    'order' => 'ASC'
));

// Basic page data
$hero_data = array(
    'background_image' => get_template_directory_uri() . '/assets/images/bg1.jpg',
    'shape_image' => get_template_directory_uri() . '/assets/images/shape8.png',
    'page_title' => 'Request a Quote'
);
?>

<!-- BreadCrumb Starts -->
<section class="breadcrumb-main pb-20 pt-14" style="background-image: url(<?php echo esc_url($hero_data['background_image']); ?>);">
    <div class="section-shape section-shape1 top-inherit bottom-0" style="background-image: url(<?php echo esc_url($hero_data['shape_image']); ?>)"></div>
    <div class="breadcrumb-outer">
        <div class="container">
            <div class="breadcrumb-content text-center">
                <h1 class="mb-3"><?php echo esc_html($hero_data['page_title']); ?></h1>
                <nav aria-label="breadcrumb" class="d-block">
                    <ul class="breadcrumb">
                        <li class="breadcrumb-item"><a href="<?php echo esc_url(home_url()); ?>">Home</a></li>
                        <?php if (!empty($url_destination_name) && $matched_destination_id): ?>
                            <li class="breadcrumb-item"><a href="<?php echo esc_url(home_url('/destinations/')); ?>">Destinations</a></li>
                            <li class="breadcrumb-item"><a href="<?php echo get_permalink($matched_destination_id); ?>"><?php echo esc_html($url_destination_name); ?></a></li>
                        <?php endif; ?>
                        <li class="breadcrumb-item active" aria-current="page"><?php echo esc_html($hero_data['page_title']); ?></li>
                    </ul>
                </nav>
            </div>
        </div>
    </div>
    <div class="dot-overlay"></div>
</section>
<!-- BreadCrumb Ends -->

<!-- Request Quote Form Starts -->
<section class="contact-main pt-6 pb-60">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8 col-md-10">
                <div class="contact-info bg-white p-5 rounded shadow-sm">
                    <div class="contact-info-title text-center mb-4">
                        <h3 class="mb-3 theme1"><?php echo esc_html($hero_data['page_title']); ?></h3>
                        <?php if (!empty($url_destination_name)): ?>
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle me-2"></i>
                                Selected Destination: <strong><?php echo esc_html($url_destination_name); ?></strong>
                            </div>
                        <?php endif; ?>
                        <p class="mb-4">Please fill out the form below and we'll get back to you with a customized quote for your educational travel needs.</p>
                    </div>

                    <div class="quote-form">
                        <form method="post" action="" name="quoteform" id="quoteform" class="needs-validation" novalidate>
                            <?php wp_nonce_field('request_quote_action', 'request_quote_nonce'); ?>

                            <div class="row">
                                <!-- Educator's Name -->
                                <div class="col-md-6 mb-3">
                                    <label for="educator_name" class="form-label">Educator's Name <span class="text-danger">*</span></label>
                                    <input type="text" name="educator_name" class="form-control" id="educator_name" placeholder="Enter educator's name" required>
                                    <div class="invalid-feedback">Please provide educator's name.</div>
                                </div>

                                <!-- School Name -->
                                <div class="col-md-6 mb-3">
                                    <label for="school_name" class="form-label">School Name <span class="text-danger">*</span></label>
                                    <input type="text" name="school_name" class="form-control" id="school_name" placeholder="Enter school name" required>
                                    <div class="invalid-feedback">Please provide school name.</div>
                                </div>

                                <!-- School Address -->
                                <div class="col-12 mb-3">
                                    <label for="school_address" class="form-label">School Address <span class="text-danger">*</span></label>
                                    <textarea name="school_address" class="form-control" id="school_address" rows="3" placeholder="Enter complete school address" required></textarea>
                                    <div class="invalid-feedback">Please provide school address.</div>
                                </div>

                                <!-- Position -->
                                <div class="col-md-6 mb-3">
                                    <label for="position" class="form-label">Position <span class="text-danger">*</span></label>
                                    <input type="text" name="position" class="form-control" id="position" placeholder="e.g., Principal, Teacher, Coordinator" required>
                                    <div class="invalid-feedback">Please provide your position.</div>
                                </div>

                                <!-- Email -->
                                <div class="col-md-6 mb-3">
                                    <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                                    <input type="email" name="email" class="form-control" id="email" placeholder="Enter email address" value="<?php echo esc_attr($prefill_email); ?>" required>
                                    <div class="invalid-feedback">Please provide a valid email address.</div>
                                </div>

                                <!-- School Phone -->
                                <div class="col-md-6 mb-3">
                                    <label for="school_phone" class="form-label">School Phone</label>
                                    <input type="tel" name="school_phone" class="form-control" id="school_phone" placeholder="Enter school phone number">
                                </div>

                                <!-- Cell Phone -->
                                <div class="col-md-6 mb-3">
                                    <label for="cell_phone" class="form-label">Cell Phone</label>
                                    <input type="tel" name="cell_phone" class="form-control" id="cell_phone" placeholder="Enter cell phone number">
                                </div>

                                <!-- Best Time to Reach -->
                                <div class="col-md-6 mb-3">
                                    <label for="best_time_to_reach" class="form-label">Best Time to Reach Me</label>
                                    <select name="best_time_to_reach" class="form-select" id="best_time_to_reach">
                                        <option value="">Select best time</option>
                                        <option value="Morning (8-12 PM)">Morning (8-12 PM)</option>
                                        <option value="Afternoon (12-5 PM)">Afternoon (12-5 PM)</option>
                                        <option value="Evening (5-8 PM)">Evening (5-8 PM)</option>
                                        <option value="Anytime">Anytime</option>
                                    </select>
                                </div>

                                <!-- Destination Dropdown -->
                                <div class="col-md-6 mb-3">
                                    <label for="destination_name" class="form-label">Destination <span class="text-danger">*</span></label>
                                    <select name="destination_name" class="form-select" id="destination_name" required>
                                        <option value="">Select a destination...</option>
                                        <?php if (!empty($all_destinations)): ?>
                                            <?php foreach ($all_destinations as $dest): ?>
                                                <?php
                                                $dest_slug = get_post_field('post_name', $dest->ID);
                                                $dest_title = get_the_title($dest->ID);

                                                $is_selected = false;
                                                if ($matched_destination_id && $dest->ID === $matched_destination_id) {
                                                    $is_selected = true;
                                                } elseif (!empty($url_destination_slug) && $dest_slug === $url_destination_slug) {
                                                    $is_selected = true;
                                                } elseif (!empty($url_destination_name) && $dest_title === $url_destination_name) {
                                                    $is_selected = true;
                                                }
                                                ?>
                                                <option value="<?php echo esc_attr($dest_title); ?>"
                                                    data-slug="<?php echo esc_attr($dest_slug); ?>"
                                                    data-id="<?php echo esc_attr($dest->ID); ?>"
                                                    <?php selected($is_selected, true); ?>>
                                                    <?php echo esc_html($dest_title); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </select>
                                    <div class="invalid-feedback">Please select your desired destination.</div>
                                </div>

                                <!-- Special Requests -->
                                <div class="col-12 mb-4">
                                    <label for="special_requests" class="form-label">Special Requests</label>
                                    <textarea name="special_requests" class="form-control" id="special_requests" rows="4" placeholder="Please list special requests including sightseeing, theatre performances, theme parks or any other details that would be pertinent to your trip."></textarea>
                                    <small class="form-text text-muted">Include any specific requirements, accessibility needs, dietary restrictions, or educational focus areas.</small>
                                </div>

                                <!-- How did you hear about us -->
                                <div class="col-12 mb-3">
                                    <label for="hear_about_us" class="form-label">How did you hear about us?</label>
                                    <select name="hear_about_us" class="form-select" id="hear_about_us">
                                        <option value="">Please select</option>
                                        <option value="Google Search">Google Search</option>
                                        <option value="Social Media">Social Media</option>
                                        <option value="Referral from colleague">Referral from colleague</option>
                                        <option value="School district">School district</option>
                                        <option value="Trade show/conference">Trade show/conference</option>
                                        <option value="Previous experience">Previous experience</option>
                                        <option value="Other">Other</option>
                                    </select>
                                </div>

                                <!-- Meal Options -->
                                <div class="col-12 mb-2">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="meal_quote" id="meal_quote" value="1">
                                        <label class="form-check-label" for="meal_quote">
                                            <i class="fas fa-utensils me-1"></i> Please provide a quote that includes meal options.
                                        </label>
                                    </div>
                                </div>

                                <!-- Transportation -->
                                <div class="col-12 mb-4">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="transportation_quote" id="transportation_quote" value="1">
                                        <label class="form-check-label" for="transportation_quote">
                                            <i class="fas fa-bus me-1"></i> I would like to include a quote for transportation.
                                        </label>
                                    </div>
                                </div>

                                <!-- Hidden fields -->
                                <input type="hidden" name="destination_slug" id="destination_slug" value="<?php echo esc_attr($url_destination_slug); ?>">
                                <input type="hidden" name="destination_id" id="destination_id" value="<?php echo esc_attr($matched_destination_id); ?>">

                                <!-- Submit Button -->
                                <div class="col-12 text-center">
                                    <button type="submit" name="submit_quote" class="btn nir-btn btn-lg px-5 py-3" id="submitQuoteBtn">
                                        <i class="fas fa-paper-plane me-2"></i>Send Request
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>

                    <!-- Contact Information -->
                    <div class="contact-info-footer mt-5 pt-4 border-top text-center">
                        <h5 class="mb-3">Need Help?</h5>
                        <p class="mb-2">
                            <i class="fas fa-phone theme me-2"></i>
                            <strong>Phone:</strong> <a href="tel:18887636786">1-888-76-FORUM (1-888-763-6786)</a>
                        </p>
                        <p class="mb-2">
                            <i class="fas fa-envelope theme me-2"></i>
                            <strong>Email:</strong> <a href="mailto:info@forumtravel.org">info@forumtravel.org</a>
                        </p>
                        <p class="mb-0">
                            <i class="fas fa-clock theme me-2"></i>
                            <strong>Response Time:</strong> We typically respond within 24 hours
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<!-- Request Quote Form Ends -->

<?php get_footer(); ?>