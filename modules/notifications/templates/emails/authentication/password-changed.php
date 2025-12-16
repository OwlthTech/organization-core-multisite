<h2>Password Changed Successfully</h2>

<p>Hi <?php echo $user_name; ?>,</p>

<p>Your password has been changed successfully.</p>

<div class="info-box">
    <p><strong>Security Information:</strong></p>
    <p>Time: <?php echo date('F j, Y g:i A'); ?></p>
    <p>If you didn't make this change, your account may be compromised.</p>
</div>

<p style="text-align: center;">
    <a href="<?php echo $login_url; ?>" class="email-button">Log In Now</a>
</p>

<p><strong>If this wasn't you:</strong></p>
<ul>
    <li>Contact us immediately</li>
    <li>Reset your password again</li>
    <li>Check your account activity</li>
</ul>

<p>Best regards,<br>
The <?php echo $site_name; ?> Team</p>
