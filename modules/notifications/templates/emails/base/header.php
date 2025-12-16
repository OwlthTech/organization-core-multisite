<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo esc_html($subject ?? 'Notification'); ?></title>
    <?php include dirname(__FILE__) . '/styles.php'; ?>
</head>
<body>
    <table class="email-wrapper" width="100%" cellpadding="0" cellspacing="0">
        <tr>
            <td align="center">
                <table class="email-content" width="600" cellpadding="0" cellspacing="0">
                    <tr>
                        <td class="email-header">
                            <h1><?php echo esc_html(get_bloginfo('name')); ?></h1>
                        </td>
                    </tr>
                    <tr>
                        <td class="email-body">
