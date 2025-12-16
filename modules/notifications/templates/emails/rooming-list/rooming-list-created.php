<h2>Rooming List Created</h2>

<p>Hi <?php echo esc_html($user_name); ?>,</p>

<p>Your rooming list has been successfully created for booking #<?php echo esc_html($booking_ref); ?>.</p>

<div class="info-box">
    <p><strong>Booking Reference:</strong> <?php echo esc_html($booking_ref); ?></p>
    <p><strong>Total Rooms:</strong> <?php echo esc_html($total_rooms); ?></p>
    <p><strong>Total Occupants:</strong> <?php echo esc_html($total_occupants); ?></p>
    <?php if (!empty($due_date)): ?>
    <p><strong>Due Date:</strong> <?php echo esc_html($due_date); ?></p>
    <?php endif; ?>
</div>

<?php if (!empty($edit_url)): ?>
<p style="text-align: center;">
    <a href="<?php echo esc_url($edit_url); ?>" class="email-button">View Rooming List</a>
</p>
<?php endif; ?>

<p>Please ensure your rooming list is completed before the due date.</p>

<p>Best regards,<br>
The <?php echo esc_html(get_bloginfo('name')); ?> Team</p>
