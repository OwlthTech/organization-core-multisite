<h2>Profile Updated Successfully</h2>

<p>Hi <?php echo $user_name; ?>,</p>

<p>Your profile has been updated successfully.</p>

<div class="info-box">
    <p><strong>Updated Fields:</strong></p>
    <?php if (isset($updated_fields) && is_array($updated_fields)): ?>
        <ul>
            <?php foreach ($updated_fields as $field): ?>
                <li><?php echo ucwords(str_replace('_', ' ', $field)); ?></li>
            <?php endforeach; ?>
        </ul>
    <?php else: ?>
        <p>Profile information updated</p>
    <?php endif; ?>
</div>

<p>If you didn't make these changes, please contact us immediately.</p>

<p>Best regards,<br>
The <?php echo $site_name; ?> Team</p>
