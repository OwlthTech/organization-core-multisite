<h2>Hotel Assigned to Booking</h2>

<p>A hotel has been assigned to booking #<?php echo esc_html($booking_ref); ?>.</p>

<div class="info-box">
    <p><strong>Booking Reference:</strong> <?php echo esc_html($booking_ref); ?></p>
    <p><strong>Customer:</strong> <?php echo esc_html($user_name); ?></p>
    <p><strong>Hotel Name:</strong> <?php echo esc_html($hotel_name); ?></p>
    <p><strong>Hotel Address:</strong> <?php echo esc_html($hotel_address); ?></p>
    <?php if (!empty($check_in)): ?>
    <p><strong>Check-In:</strong> <?php echo esc_html($check_in); ?></p>
    <?php endif; ?>
    <?php if (!empty($check_out)): ?>
    <p><strong>Check-Out:</strong> <?php echo esc_html($check_out); ?></p>
    <?php endif; ?>
</div>

<p>The customer has been notified of this hotel assignment.</p>

<p>Best regards,<br>
<?php echo esc_html(get_bloginfo('name')); ?> System</p>
