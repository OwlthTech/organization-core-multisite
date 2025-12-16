<?php

/**
 * User Booking Confirmation Email Template
 * Variables: $email_data (array)
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
    <title>Booking Confirmation</title>
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

        .notice {
            text-align: center;
            font-size: 11px;
            margin: 15px 0;
            padding: 10px;
            background: #f9f9f9;
            border: 1px solid #ddd;
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

        <div class="notice">
            <strong>Thank you for your booking! Please note we will contact you either via email or phone to let you know when your performance is confirmed.</strong>
        </div>

        <table class="info-table">
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

            <?php if ($email_data['include_meals'] === 'Yes' && !empty($email_data['meals_per_day'])): ?>
                <tr>
                    <td class="label">Meals Per Day</td>
                    <td class="value"><?php echo esc_html($email_data['meals_per_day']); ?></td>
                </tr>
            <?php endif; ?>

            <?php if (!empty($email_data['transportation'])): ?>
                <tr>
                    <td class="label">Transport</td>
                    <td class="value"><?php echo esc_html($email_data['transportation']); ?></td>
                </tr>
            <?php endif; ?>

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
                        <br>
                        <small style="color: #666;"><?php echo esc_html($email_data['festival_date_day']); ?></small>
                    </td>
                </tr>
            <?php endif; ?>

            <?php if (!empty($email_data['lodging_dates'])): ?>
                <tr>
                    <td class="label">Lodging Date(s)</td>
                    <td class="value"><?php echo esc_html($email_data['lodging_dates']); ?></td>
                </tr>
            <?php endif; ?>

            <tr>
                <td class="label">Request Type</td>
                <td class="value">New Booking</td>
            </tr>

            <?php if (!empty($email_data['director_name'])): ?>
                <tr>
                    <td class="label">Director's Name</td>
                    <td class="value"><?php echo esc_html($email_data['director_name']); ?></td>
                </tr>
            <?php endif; ?>

            <?php if (!empty($email_data['director_phone'])): ?>
                <tr>
                    <td class="label">Director's Phone</td>
                    <td class="value"><?php echo esc_html($email_data['director_phone']); ?></td>
                </tr>
            <?php endif; ?>

            <?php if (!empty($email_data['director_cellphone'])): ?>
                <tr>
                    <td class="label">Director's Cellphone</td>
                    <td class="value"><?php echo esc_html($email_data['director_cellphone']); ?></td>
                </tr>
            <?php endif; ?>

            <tr>
                <td class="label">E-mail</td>
                <td class="value"><?php echo esc_html($email_data['user_email']); ?></td>
            </tr>
        </table>

        <?php if (!empty($email_data['school_name'])): ?>
            <div class="section-title">School Information</div>
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
                        <td class="label">City, State</td>
                        <td class="value">
                            <?php echo esc_html($email_data['school_city']); ?>
                            <?php if (!empty($email_data['school_state'])): ?>
                                , <?php echo esc_html($email_data['school_state']); ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endif; ?>
                <?php if (!empty($email_data['school_zip'])): ?>
                    <tr>
                        <td class="label">Zip Code</td>
                        <td class="value"><?php echo esc_html($email_data['school_zip']); ?></td>
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

        <?php if (!empty($email_data['notes'])): ?>
            <div class="section-title">Special Requests</div>
            <table class="info-table">
                <tr>
                    <td class="value"><?php echo nl2br(esc_html($email_data['notes'])); ?></td>
                </tr>
            </table>
        <?php endif; ?>

        <div class="section-title">Group Details</div>
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

        <div class="footer">
            <strong>FORUM Music Festivals</strong><br>
            Booking Reference: <strong>#<?php echo esc_html($email_data['booking_id']); ?></strong><br>
            Phone: (818) 885-2700 | Email: info@forumfestivals.com<br>
            © <?php echo date('Y'); ?> FORUM Music Festivals. All rights reserved.
        </div>
    </div>
</body>

</html>