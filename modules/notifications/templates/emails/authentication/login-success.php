<h2>Login Successful</h2>

<p>Hi <?php echo $user_name; ?>,</p>

<p>You have successfully logged in to your account.</p>

<div class="info-box">
    <p><strong>Login Details:</strong></p>
    <p>Time: <?php echo date('F j, Y g:i A'); ?></p>
    <p>IP Address: <?php echo $_SERVER['REMOTE_ADDR'] ?? 'Unknown'; ?></p>
</div>

<p>If this wasn't you, please <a href="<?php echo wp_lostpassword_url(); ?>">reset your password</a> immediately.</p>

<p>Best regards,<br>
The <?php echo get_bloginfo('name'); ?> Team</p>
