<h2>Hotel Assigned to Your Booking</h2>

<p>Hi <?php echo esc_html($user_name); ?>,</p>

<p>Great news! A hotel has been assigned to your booking.</p>

<div class="info-box">
    <p><strong>Booking Reference:</strong> <?php echo esc_html($booking_ref); ?></p>
    <p><strong>Hotel Name:</strong> <?php echo esc_html($hotel_name); ?></p>
    <p><strong>Hotel Address:</strong> <?php echo esc_html($hotel_address); ?></p>
    <?php if (!empty($check_in)): ?>
    <p><strong>Check-In:</strong> <?php echo esc_html($check_in); ?></p>
    <?php endif; ?>
    <?php if (!empty($check_out)): ?>
    <p><strong>Check-Out:</strong> <?php echo esc_html($check_out); ?></p>
    <?php endif; ?>
</div>

<p>You can now proceed to complete your rooming list for this booking.</p>

<p>If you have any questions about your hotel assignment, please contact us.</p>

<p>Best regards,<br>
The <?php echo esc_html(get_bloginfo('name')); ?> Team</p>
