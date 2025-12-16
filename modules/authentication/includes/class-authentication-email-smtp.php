<?php
if (!defined('WPINC')) die;

/**
 * Authentication Email SMTP Configuration
 * Configures Gmail SMTP for password reset emails
 * Based on: class-multisite-user-sync-email.php
 */
class Authentication_Email_SMTP
{
    private static $instance = null;

    public static function get_instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct()
    {
        // ✅ CRITICAL: Hook into PHPMailer BEFORE wp_mail() is called
        add_action('phpmailer_init', array($this, 'configure_smtp'), 10, 1);

        // Set custom from email/name
        add_filter('wp_mail_from', array($this, 'set_mail_from'), 10);
        add_filter('wp_mail_from_name', array($this, 'set_mail_from_name'), 10);

        // Add email failure logging
        add_action('wp_mail_failed', array($this, 'log_email_error'), 10, 1);

        // Add test email handler
        add_action('wp_ajax_test_smtp_email', array($this, 'handle_test_email'));
    }

    /**
     * ✅ Configure SMTP Settings for PHPMailer
     * 
     * This method is called by WordPress before sending ANY email
     * It configures Gmail SMTP instead of using PHP mail()
     */
    public function configure_smtp($phpmailer)
    {
        // Get credentials from WordPress options
        $username = get_option('mus_gmail_username');
        $password = get_option('mus_gmail_password');

        // If no credentials, use default mail (won't work, but won't break)
        if (empty($username) || empty($password)) {
            return;
        }

        // ✅ ENABLE SMTP
        $phpmailer->isSMTP();

        // ✅ Gmail SMTP Server Settings
        $phpmailer->Host = 'smtp.gmail.com';
        $phpmailer->SMTPAuth = true;
        $phpmailer->Port = 587;
        $phpmailer->SMTPSecure = 'tls';

        // ✅ Gmail Credentials
        $phpmailer->Username = $username;
        $phpmailer->Password = $password;

        // ✅ Set default sender info
        $from_email = $this->set_mail_from('');
        $from_name = $this->set_mail_from_name('');
        $phpmailer->setFrom($from_email, $from_name);

        // ✅ Enable more detailed error reporting
        $phpmailer->SMTPDebug = 2; // Always enable debug output
        $phpmailer->Debugoutput = function ($str, $level) {
            error_log("📧 [SMTP DEBUG Level $level]: $str");
        };

        // ✅ Add better error handling
        $phpmailer->Timeout = 30; // Increase timeout
        $phpmailer->SMTPKeepAlive = true; // Keep connection alive

        // ✅ SSL/TLS Configuration
        $phpmailer->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => true,
                'verify_peer_name' => true,
                'verify_depth' => 3,
                'cafile' => ABSPATH . WPINC . '/certificates/ca-bundle.crt'
            )
        );
    }

    /**
     * Set custom From email address
     */
    public function set_mail_from($email)
    {
        $from_email = get_option('mus_from_email');

        if (!empty($from_email) && is_email($from_email)) {
            return $from_email;
        }

        $admin_email = get_option('admin_email');
        return $admin_email;
    }

    /**
     * Set custom From name
     */
    public function set_mail_from_name($name)
    {
        $from_name = get_option('mus_from_name');

        if (!empty($from_name)) {
            return $from_name;
        }

        $site_name = get_bloginfo('name');
        return $site_name;
    }

    /**
     * Log email errors
     */
    public function log_email_error($wp_error)
    {
        error_log('❌ [MAIL ERROR] Email failed to send');
        error_log('Error Message: ' . $wp_error->get_error_message());
        error_log('Error Data: ' . print_r($wp_error->get_error_data(), true));
    }

    /**
     * Handle AJAX request to send test email
     */
    public function handle_test_email()
    {
        check_ajax_referer('test_smtp_email');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        $admin_email = get_option('admin_email');

        // Prepare test email
        $subject = 'SMTP Test Email from ' . get_bloginfo('name');
        $message = "This is a test email sent from your WordPress site.\n\n";
        $message .= "Current Time: " . current_time('mysql') . "\n";
        $message .= "Site URL: " . get_site_url() . "\n";
        $message .= "From Email: " . $this->set_mail_from('') . "\n";
        $message .= "From Name: " . $this->set_mail_from_name('') . "\n";
        $message .= "SMTP Username: " . get_option('mus_gmail_username') . "\n";

        // Send test email
        $result = wp_mail($admin_email, $subject, $message);

        if ($result) {
            wp_send_json_success("Test email sent successfully to $admin_email. Check your spam folder if you don't see it.");
        } else {
            global $phpmailer;
            $error = $phpmailer instanceof \PHPMailer\PHPMailer\PHPMailer ? $phpmailer->ErrorInfo : 'Unknown error';
            wp_send_json_error("Failed to send test email. Error: $error");
        }
    }
}

// Initialize singleton instance
Authentication_Email_SMTP::get_instance();
