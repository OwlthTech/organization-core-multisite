<?php

/**
 * Password Reset Email Template
 * 
 * Available Variables:
 * - $user_name
 * - $site_name
 * - $reset_url
 * - $expires_in
 * 
 * @package Organization_Core
 * @subpackage Modules/Authentication/Templates
 */

if (!defined('WPINC')) die;
?>

<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f4f4f4;
        }

        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        .header {
            background-color: #0073aa;
            color: white;
            padding: 30px 20px;
            text-align: center;
        }

        .header h1 {
            margin: 0;
            font-size: 24px;
        }

        .content {
            padding: 30px 20px;
        }

        .button {
            background-color: #0073aa;
            color: white;
            padding: 12px 30px;
            text-decoration: none;
            display: inline-block;
            border-radius: 4px;
            font-weight: bold;
            margin: 20px 0;
        }

        .link-text {
            word-break: break-all;
            color: #0073aa;
            font-size: 12px;
        }

        .warning-box {
            background-color: #fff8e5;
            border: 1px solid #ffecb5;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }

        .footer {
            text-align: center;
            font-size: 12px;
            color: #666;
            padding: 20px;
            border-top: 1px solid #ddd;
            background-color: #f9f9f9;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <h1>🔐 Password Reset Request</h1>
        </div>
        <div class="content">
            <p>Hello <strong><?php echo esc_html($user_name); ?></strong>,</p>
            <p>We received a request to reset your password on <strong><?php echo esc_html($site_name); ?></strong>.</p>
            <p>Click the button below to reset your password:</p>
            <center>
                <a href="<?php echo esc_url($reset_url); ?>" class="button">Reset Your Password</a>
            </center>
            <p>Or copy this link:</p>
            <p class="link-text">
                <a href="<?php echo esc_url($reset_url); ?>">
                    <?php echo esc_url($reset_url); ?>
                </a>
            </p>
            <div class="warning-box">
                <strong>⏰ Important:</strong> This link expires in <?php echo esc_html($expires_in); ?>.
            </div>
            <p>If you didn't request this, ignore this email. Your account is safe.</p>
            <p>Best regards,<br><strong><?php echo esc_html($site_name); ?></strong></p>
        </div>
        <div class="footer">
            <p>© <?php echo date('Y'); ?> <?php echo esc_html($site_name); ?>. All rights reserved.</p>
        </div>
    </div>
</body>

</html>