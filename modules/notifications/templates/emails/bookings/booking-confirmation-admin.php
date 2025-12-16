<?php

/**
 * Admin Booking Notification Email Template
 * Variables: $email_data (array)
 * Note: Can have different format from user email
 */

if (!defined('WPINC')) {
    exit;
}

// $logo_url = plugin_dir_url(dirname(dirname(dirname(__FILE__)))) . 'assets/images/FMF-logo.png';
$logo = OC_Asset_Handler::get_theme_image('logo');
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Booking Notification</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            color: #000;
            margin: 0;
            padding: 0;
            background: #fff;
        }

        .email-container {
            max-width: 700px;
            margin: 0 auto;
            padding: 20px;
        }

        .logo {
            text-align: center;
            margin-bottom: 20px;
        }

        .logo img {
            max-width: 250px;
            height: auto;
        }

        .alert {
            background: #fff3cd;
            border: 1px solid #ffc107;
            padding: 12px;
            margin: 15px 0;
            border-radius: 4px;
            color: #856404;
        }

        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }

        .info-table td {
            border: 1px solid #000;
            padding: 8px;
            vertical-align: top;
        }

        .info-table .label {
            font-weight: bold;
            width: 35%;
            background: #f5f5f5;
        }

        .info-table .value {
            width: 65%;
        }

        .section-title {
            font-weight: bold;
            font-size: 13px;
            margin: 20px 0 10px 0;
            padding: 5px 0;
            border-bottom: 2px solid #000;
            background: #e8e8e8;
            padding: 8px;
        }

        .footer {
            text-align: center;
            margin-top: 30px;
            padding: 20px;
            background: #f5f5f5;
            border-top: 2px solid #000;
            font-size: 11px;
        }
    </style>
</head>

<body>
    <div class="email-container">
        <div class="logo">
            <img src="<?php echo esc_url($logo); ?>" alt="FORUM Music Festivals">
        </div>

        <div class="alert">
            <strong>⚠️ NEW BOOKING RECEIVED</strong><br>
            A new booking has been submitted and requires review and confirmation.
        </div>

        <div class="section-title">📋 Booking Details</div>
        <table class="info-table">
            <tr>
                <td class="label">Booking ID</td>
                <td class="value"><strong>#<?php echo esc_html($email_data['booking_id']); ?></strong></td>
            </tr>

            <?php if (!empty($email_data['package_title'])): ?>
                <tr>
                    <td class="label">Package Type</td>
                    <td class="value"><?php echo esc_html($email_data['package_title']); ?></td>
                </tr>
            <?php endif; ?>

            <?php if (!empty($email_data['parks'])): ?>
                <tr>
                    <td class="label">Park(s) Chosen</td>
                    <td class="value"><?php echo wp_kses_post($email_data['parks']); ?></td>
                </tr>
            <?php endif; ?>

            <tr>
                <td class="label">Include Meals</td>
                <td class="value"><?php echo esc_html($email_data['include_meals']); ?></td>
            </tr>

            <?php if (!empty($email_data['festival_location'])): ?>
                <tr>
                    <td class="label">Festival Location</td>
                    <td class="value"><?php echo esc_html($email_data['festival_location']); ?></td>
                </tr>
            <?php endif; ?>

            <?php if (!empty($email_data['festival_date'])): ?>
                <tr>
                    <td class="label">Festival Date</td>
                    <td class="value">
                        <?php echo esc_html($email_data['festival_date']); ?>
                        (<?php echo esc_html($email_data['festival_date_day']); ?>)
                    </td>
                </tr>
            <?php endif; ?>
        </table>

        <div class="section-title">👤 Contact Information</div>
        <table class="info-table">
            <?php if (!empty($email_data['director_name'])): ?>
                <tr>
                    <td class="label">Director Name</td>
                    <td class="value"><?php echo esc_html($email_data['director_name']); ?></td>
                </tr>
            <?php endif; ?>

            <tr>
                <td class="label">Email</td>
                <td class="value"><?php echo esc_html($email_data['user_email']); ?></td>
            </tr>

            <?php if (!empty($email_data['director_phone'])): ?>
                <tr>
                    <td class="label">Phone</td>
                    <td class="value"><?php echo esc_html($email_data['director_phone']); ?></td>
                </tr>
            <?php endif; ?>

            <?php if (!empty($email_data['director_cellphone'])): ?>
                <tr>
                    <td class="label">Cellphone</td>
                    <td class="value"><?php echo esc_html($email_data['director_cellphone']); ?></td>
                </tr>
            <?php endif; ?>
        </table>

        <?php if (!empty($email_data['school_name'])): ?>
            <div class="section-title">🏫 School Information</div>
            <table class="info-table">
                <tr>
                    <td class="label">School Name</td>
                    <td class="value"><?php echo esc_html($email_data['school_name']); ?></td>
                </tr>
                <?php if (!empty($email_data['school_address_1'])): ?>
                    <tr>
                        <td class="label">Address</td>
                        <td class="value"><?php echo esc_html($email_data['school_address_1']); ?></td>
                    </tr>
                <?php endif; ?>
                <?php if (!empty($email_data['school_city'])): ?>
                    <tr>
                        <td class="label">City, State, Zip</td>
                        <td class="value">
                            <?php echo esc_html($email_data['school_city']); ?>
                            <?php if (!empty($email_data['school_state'])): ?>
                                , <?php echo esc_html($email_data['school_state']); ?>
                            <?php endif; ?>
                            <?php if (!empty($email_data['school_zip'])): ?>
                                <?php echo esc_html($email_data['school_zip']); ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endif; ?>
                <?php if (!empty($email_data['school_phone'])): ?>
                    <tr>
                        <td class="label">School Phone</td>
                        <td class="value"><?php echo esc_html($email_data['school_phone']); ?></td>
                    </tr>
                <?php endif; ?>
            </table>
        <?php endif; ?>

        <div class="section-title">👥 Group Details</div>
        <table class="info-table">
            <tr>
                <td class="label">Total Students</td>
                <td class="value"><?php echo esc_html($email_data['total_students']); ?></td>
            </tr>
            <tr>
                <td class="label">Total Chaperones</td>
                <td class="value"><?php echo esc_html($email_data['total_chaperones']); ?></td>
            </tr>
        </table>

        <?php if (!empty($email_data['notes'])): ?>
            <div class="section-title">📝 Special Requests</div>
            <table class="info-table">
                <tr>
                    <td class="value"><?php echo nl2br(esc_html($email_data['notes'])); ?></td>
                </tr>
            </table>
        <?php endif; ?>

        <div class="footer">
            <strong>Action Required</strong><br>
            Please log in to the admin panel to review and confirm this booking.<br>
            <br>
            <strong>FORUM Music Festivals</strong><br>
            © <?php echo date('Y'); ?> FORUM Music Festivals. All rights reserved.
        </div>
    </div>
</body>

</html>