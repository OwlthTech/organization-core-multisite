<?php

/**
 * Email Settings Management
 * For managing SMTP and other email settings
 */

if (!defined('WPINC')) die;

/**
 * Register email settings
 */
function register_authentication_email_settings()
{
    // Gmail SMTP Settings
    register_setting('authentication_email', 'mus_gmail_username');
    register_setting('authentication_email', 'mus_gmail_password');

    // From Email Settings
    register_setting('authentication_email', 'mus_from_email');
    register_setting('authentication_email', 'mus_from_name');

    // Add settings section
    add_settings_section(
        'auth_email_settings',
        'Email Configuration',
        function () {
            echo '<p>Configure email settings for password reset and notifications.</p>';
        },
        'authentication_email'
    );

    // Add settings fields
    add_settings_field(
        'mus_gmail_username',
        'Gmail Username',
        function () {
            $value = get_option('mus_gmail_username');
            echo '<input type="email" name="mus_gmail_username" value="' . esc_attr($value) . '" class="regular-text">';
            echo '<p class="description">Gmail account username (e.g., example@gmail.com)</p>';
        },
        'authentication_email',
        'auth_email_settings'
    );

    add_settings_field(
        'mus_gmail_password',
        'Gmail Password/App Password',
        function () {
            echo '<input type="password" name="mus_gmail_password" value="' . esc_attr(get_option('mus_gmail_password')) . '" class="regular-text">';
            echo '<p class="description">Gmail password or app-specific password (recommended)</p>';
        },
        'authentication_email',
        'auth_email_settings'
    );

    add_settings_field(
        'mus_from_email',
        'From Email',
        function () {
            $value = get_option('mus_from_email', get_option('admin_email'));
            echo '<input type="email" name="mus_from_email" value="' . esc_attr($value) . '" class="regular-text">';
            echo '<p class="description">Email address that appears in the From field</p>';
        },
        'authentication_email',
        'auth_email_settings'
    );

    add_settings_field(
        'mus_from_name',
        'From Name',
        function () {
            $value = get_option('mus_from_name', get_bloginfo('name'));
            echo '<input type="text" name="mus_from_name" value="' . esc_attr($value) . '" class="regular-text">';
            echo '<p class="description">Name that appears in the From field</p>';
        },
        'authentication_email',
        'auth_email_settings'
    );
}

// Register settings on admin init
add_action('admin_init', 'register_authentication_email_settings');

/**
 * Add email settings page to admin menu
 */
function add_authentication_email_settings_page()
{
    add_submenu_page(
        'options-general.php',
        'Authentication Email Settings',
        'Auth Email Settings',
        'manage_options',
        'authentication-email-settings',
        function () {
?>
        <div class="wrap">
            <h1>Authentication Email Settings</h1>

            <form method="post" action="options.php">
                <?php
                settings_fields('authentication_email');
                do_settings_sections('authentication_email');
                submit_button();
                ?>
            </form>

            <hr>

            <h2>Test Email Configuration</h2>
            <p>Send a test email to verify your settings are working correctly.</p>

            <form method="post" id="test-email-form">
                <?php wp_nonce_field('test_smtp_email'); ?>
                <button type="submit" class="button button-secondary">Send Test Email</button>
            </form>

            <script>
                jQuery(document).ready(function($) {
                    $('#test-email-form').on('submit', function(e) {
                        e.preventDefault();

                        var $form = $(this);
                        var $submit = $form.find('button[type="submit"]');
                        $submit.prop('disabled', true).text('Sending...');

                        $.post(ajaxurl, {
                                action: 'test_smtp_email',
                                _ajax_nonce: $form.find('input[name="_wpnonce"]').val()
                            })
                            .done(function(response) {
                                alert(response.message);
                            })
                            .fail(function() {
                                alert('Failed to send test email');
                            })
                            .always(function() {
                                $submit.prop('disabled', false).text('Send Test Email');
                            });
                    });
                });
            </script>
        </div>
<?php
        }
    );
}

add_action('admin_menu', 'add_authentication_email_settings_page');
