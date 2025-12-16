<?php
if (!defined('WPINC')) die;

$current_blog_id = get_current_blog_id();
$register_page_link = get_site_url(null, 'signup');
$recover_page_link = get_site_url(null, 'forgot-password');
?>

<section class="event-cart pt-12">
    <div class="container">
        <div class="event-login-main mx-auto">
            <div class="event-login p-5 border-all">

                <!-- ✅ Message Container - Shows all messages here -->
                <div class="mus-form-message" id="login-messages" style="margin-bottom: 1rem;"></div>

                <form id="mus-login-form" method="post">
                    <div class="input-user mb-3">
                        <label for="mus_username">
                            <?php _e('Username or Email', 'organization-core'); ?>
                            <span class="required text-danger">*</span>
                        </label>
                        <input type="text" name="username" id="mus_username"
                            class="input-text bg-grey border-0" placeholder="Email Address" required />
                    </div>

                    <div class="input-password position-relative w-100 mb-3">
                        <label for="mus_password">
                            <?php _e('Password', 'organization-core'); ?>
                            <span class="required text-danger">*</span>
                        </label>
                        <input type="password" name="password" id="mus_password"
                            class="input-text bg-grey border-0" required />
                    </div>

                    <div class="remember-me-section mb-3">
                        <label class="d-flex align-items-center mb-0">
                            <input type="checkbox" name="remember" class="me-2" />
                            <span><?php _e('Remember Me', 'organization-core'); ?></span>
                            <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                        </label>
                    </div>

                    <button type="submit" class="nir-btn w-100" id="login-submit-btn">
                        <span class="btn-text"><?php _e('Login', 'organization-core'); ?></span>
                        <span class="spinner-border spinner-border-sm d-none"></span>
                    </button>

                    <input type="hidden" name="action" value="mus_process_login" />
                    <input type="hidden" name="blog_id" value="<?php echo esc_attr($current_blog_id); ?>" />
                    <?php wp_nonce_field('mus_nonce', 'nonce'); ?>
                </form>

                <div class="forgot-password-section text-center mb-4">
                    <a href="<?php echo esc_url($recover_page_link); ?>" class="theme">
                        <?php _e('Forgot Password?', 'organization-core'); ?>
                    </a>
                </div>

                <div class="registration-section text-center">
                    <p class="mb-0">
                        <?php _e("Don't have an account?", 'organization-core'); ?>
                        <a href="<?php echo esc_url($register_page_link); ?>" class="register-link theme">
                            <?php _e('Click here', 'organization-core'); ?>
                        </a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ✅ Load MUS Params & JavaScript -->
<script>
    window.mus_params = {
        ajax_url: '<?php echo esc_js(admin_url("admin-ajax.php")); ?>',
        home_url: '<?php echo esc_js(get_site_url()); ?>'
    };
    console.log('✅ MUS Params:', window.mus_params);
</script>