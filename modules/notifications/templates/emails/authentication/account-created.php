<h2>Welcome to <?php echo esc_html($site_name); ?>!</h2>

<p>Hi <?php echo esc_html($user_name); ?>,</p>

<p>Your account has been successfully created. You can now log in and start using our services.</p>

<div class="info-box">
    <p><strong>Your Account Details:</strong></p>
    <p>Email: <?php echo esc_html($user_email); ?></p>
</div>

<p style="text-align: center;">
    <a href="<?php echo esc_url($login_url); ?>" class="email-button">Log In Now</a>
</p>

<p>If you have any questions, please don't hesitate to contact us.</p>

<p>Best regards,<br>
The <?php echo esc_html($site_name); ?> Team</p>
