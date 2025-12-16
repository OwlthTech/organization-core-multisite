<?php

/**
 * Email Test Functions
 * For debugging email functionality
 */

if (!defined('WPINC')) die;

// ✅ REQUIRED: Email and SMTP handlers
require_once dirname(__FILE__) . '/includes/class-authentication-email-smtp.php';

function test_smtp_configuration()
{
    // Test email to admin
    $to = get_option('admin_email');
    $subject = 'SMTP Test Email';
    $message = 'This is a test email sent at: ' . current_time('mysql');
    $headers = array('Content-Type: text/html; charset=UTF-8');


    // Add action to catch PHPMailer errors
    add_action('wp_mail_failed', function ($error) {});

    // Send test email
    $result = wp_mail($to, $subject, $message, $headers);

    if ($result) {
        error_log('✅ Test email sent successfully!');
    } else {
        error_log('❌ Test email failed to send');
    }

    return $result;
}

// Run test when file is accessed directly
if (defined('DOING_AJAX') && DOING_AJAX) {
    add_action('wp_ajax_test_smtp_email', function () {
        check_admin_referer('test_smtp_email');
        $result = test_smtp_configuration();
        wp_send_json(array(
            'success' => $result,
            'message' => $result ? 'Test email sent successfully' : 'Failed to send test email'
        ));
    });
}
