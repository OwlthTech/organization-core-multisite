<?php
if (!defined('WPINC')) die;

$login_page_link = get_site_url(null, 'login');
?>

<section class="event-cart pt-12">
    <div class="container">
        <div class="event-login-main mx-auto">
            <div class="event-login p-5 border-all">

                <h3 class="mb-4"><?php _e('Recover Your Password', 'organization-core'); ?></h3>

                <!-- ✅ Message Container -->
                <div class="mus-form-message" id="forgot-messages" style="margin-bottom: 1rem;"></div>

                <p class="mb-4"><?php _e('Enter your username or email address and we will send you a link to reset your password.', 'organization-core'); ?></p>

                <form id="mus-forgot-form" method="post">
                    <div class="input-user mb-3">
                        <label for="mus_forgot_login">
                            <?php _e('Username or Email', 'organization-core'); ?>
                            <span class="required text-danger">*</span>
                        </label>
                        <input type="text" name="user_login" id="mus_forgot_login"
                            class="input-text bg-grey border-0" placeholder="username or email@example.com" required />
                    </div>

                    <button type="submit" class="nir-btn w-100" id="forgot-submit-btn">
                        <span class="btn-text"><?php _e('Send Reset Link', 'organization-core'); ?></span>
                        <span class="spinner-border spinner-border-sm d-none"></span>
                    </button>

                    <input type="hidden" name="action" value="mus_process_password_reset" />
                    <?php wp_nonce_field('mus_nonce', 'nonce'); ?>
                </form>

                <div class="login-section text-center mt-4">
                    <p class="mb-0">
                        <?php _e("Remember your password?", 'organization-core'); ?>
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

<script src="<?php echo esc_url(plugin_dir_url(dirname(__FILE__)) . '../assets/js/forgot-password.js'); ?>"></script>