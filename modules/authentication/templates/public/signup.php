<?php
if (!defined('WPINC')) die;

$current_blog_id = get_current_blog_id();
$login_page_link = get_site_url(null, 'login');
?>

<section class="event-cart pt-12">
    <div class="container">
        <div class="event-login-main mx-auto">
            <div class="event-login p-5 border-all">

                <!-- ✅ Message Container -->
                <div class="mus-form-message" id="signup-messages" style="margin-bottom: 1rem;"></div>

                <form id="mus-signup-form" method="post">
                    <div class="input-user mb-3">
                        <label for="mus_signup_username">
                            <?php _e('Username', 'organization-core'); ?>
                            <span class="required text-danger">*</span>
                        </label>
                        <input type="text" name="username" id="mus_signup_username"
                            class="input-text bg-grey border-0" placeholder="Choose a username" required />
                    </div>

                    <div class="input-user mb-3">
                        <label for="mus_signup_email">
                            <?php _e('Email Address', 'organization-core'); ?>
                            <span class="required text-danger">*</span>
                        </label>
                        <input type="email" name="email" id="mus_signup_email"
                            class="input-text bg-grey border-0" placeholder="your@email.com" required />
                    </div>

                    <div class="input-password position-relative w-100 mb-3">
                        <label for="mus_signup_password">
                            <?php _e('Password', 'organization-core'); ?>
                            <span class="required text-danger">*</span>
                        </label>
                        <input type="password" name="password" id="mus_signup_password"
                            class="input-text bg-grey border-0" placeholder="Enter password" required />
                    </div>

                    <div class="input-password position-relative w-100 mb-3">
                        <label for="mus_signup_confirm">
                            <?php _e('Confirm Password', 'organization-core'); ?>
                            <span class="required text-danger">*</span>
                        </label>
                        <input type="password" name="confirm_password" id="mus_signup_confirm"
                            class="input-text bg-grey border-0" placeholder="Confirm password" required />
                    </div>

                    <div class="form-check mb-3">
                        <input type="checkbox" name="agree_terms" id="mus_agree_terms" class="form-check-input" required />
                        <label class="form-check-label" for="mus_agree_terms">
                            <?php _e('I agree to the Terms & Conditions', 'organization-core'); ?>
                            <span class="required text-danger">*</span>
                        </label>
                    </div>

                    <button type="submit" class="nir-btn w-100" id="signup-submit-btn">
                        <span class="btn-text"><?php _e('Create Account', 'organization-core'); ?></span>
                        <span class="spinner-border spinner-border-sm d-none"></span>
                    </button>

                    <input type="hidden" name="action" value="mus_process_registration" />
                    <input type="hidden" name="blog_id" value="<?php echo esc_attr($current_blog_id); ?>" />
                    <?php wp_nonce_field('mus_nonce', 'nonce'); ?>
                </form>

                <div class="login-section text-center mt-4">
                    <p class="mb-0">
                        <?php _e("Already have an account?", 'organization-core'); ?>
                        <a href="<?php echo esc_url($login_page_link); ?>" class="register-link theme">
                            <?php _e('Login here', 'organization-core'); ?>
                        </a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
    window.mus_params = {
        ajax_url: '<?php echo esc_js(admin_url("admin-ajax.php")); ?>',
        home_url: '<?php echo esc_js(get_site_url()); ?>'
    };
</script>