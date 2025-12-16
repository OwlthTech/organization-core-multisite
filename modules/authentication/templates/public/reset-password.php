<?php
if (!defined('WPINC')) die;

// Get reset key and login from URL
$key = isset($_GET['key']) ? sanitize_text_field($_GET['key']) : '';
$login = isset($_GET['login']) ? sanitize_text_field($_GET['login']) : '';

// Verify the reset key
$user = check_password_reset_key($key, $login);
if (is_wp_error($user)) {
?>
    <section class="event-cart pt-12">
        <div class="container">
            <div class="event-login-main mx-auto">
                <div class="event-login p-5 border-all">
                    <div class="alert alert-danger">
                        <?php _e('This password reset link is invalid or has expired.', 'organization-core'); ?>
                        <a href="<?php echo esc_url(get_site_url(null, 'forgot-password')); ?>" class="theme ms-2">
                            <?php _e('Request a new link', 'organization-core'); ?>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>
<?php
    return;
}
?>

<section class="event-cart pt-12">
    <div class="container">
        <div class="event-login-main mx-auto">
            <div class="event-login p-5 border-all">

                <h3 class="mb-4"><?php _e('Set New Password', 'organization-core'); ?></h3>

                <!-- ✅ Message Container -->
                <div class="mus-form-message" id="reset-messages" style="margin-bottom: 1rem;"></div>

                <form id="mus-reset-form" method="post">
                    <div class="input-password position-relative w-100 mb-3">
                        <label for="mus_reset_password">
                            <?php _e('New Password', 'organization-core'); ?>
                            <span class="required text-danger">*</span>
                        </label>
                        <input type="password" name="password" id="mus_reset_password"
                            class="input-text bg-grey border-0" placeholder="Enter new password" required
                            minlength="6" />
                    </div>

                    <div class="input-password position-relative w-100 mb-3">
                        <label for="mus_reset_confirm">
                            <?php _e('Confirm Password', 'organization-core'); ?>
                            <span class="required text-danger">*</span>
                        </label>
                        <input type="password" name="confirm_password" id="mus_reset_confirm"
                            class="input-text bg-grey border-0" placeholder="Confirm new password" required
                            minlength="6" />
                    </div>

                    <button type="submit" class="nir-btn w-100" id="reset-submit-btn">
                        <span class="btn-text"><?php _e('Reset Password', 'organization-core'); ?></span>
                        <span class="spinner-border spinner-border-sm d-none"></span>
                    </button>

                    <!-- Hidden fields -->
                    <input type="hidden" name="action" value="mus_process_new_password" />
                    <input type="hidden" name="key" value="<?php echo esc_attr($key); ?>" />
                    <input type="hidden" name="login" value="<?php echo esc_attr($login); ?>" />
                    <?php wp_nonce_field('mus_nonce', 'nonce'); ?>

                    <div class="password-requirements mt-3 small text-muted">
                        <p><?php _e('Password must:', 'organization-core'); ?></p>
                        <ul>
                            <li><?php _e('Be at least 6 characters long', 'organization-core'); ?></li>
                            <li><?php _e('Match in both fields', 'organization-core'); ?></li>
                        </ul>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>

<script>
    window.mus_params = {
        ajax_url: '<?php echo esc_js(admin_url("admin-ajax.php")); ?>',
        home_url: '<?php echo esc_js(get_site_url()); ?>'
    };
    console.log(window.mus_params);
</script>