<h2>⏰ Rooming List Due Soon</h2>

<p>Hi <?php echo esc_html($user_name); ?>,</p>

<p>This is a friendly reminder that your rooming list is due in <strong><?php echo esc_html($days_until ?? '3'); ?> days</strong>.</p>

<div class="info-box">
    <p><strong>Booking Reference:</strong> <?php echo esc_html($booking_ref); ?></p>
    <p><strong>Due Date:</strong> <?php echo esc_html($due_date); ?></p>
    <?php if (!empty($total_rooms)): ?>
    <p><strong>Total Rooms:</strong> <?php echo esc_html($total_rooms); ?></p>
    <?php endif; ?>
</div>

<?php if (!empty($rooming_list_url)): ?>
<p style="text-align: center;">
    <a href="<?php echo esc_url($rooming_list_url); ?>" class="email-button">Complete Rooming List Now</a>
</p>
<?php endif; ?>

<p>Please complete your rooming list as soon as possible to avoid any delays.</p>

<p>If you have any questions or need assistance, please don't hesitate to contact us.</p>

<p>Best regards,<br>
The <?php echo esc_html(get_bloginfo('name')); ?> Team</p>
