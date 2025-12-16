# Notifications Module

**Version:** 2.0.0  
**Author:** OwlthTech  
**Status:** Active & Production Ready

---

## 🚨 Current Issues & Solutions

### Issue #1: Email Sending Failures (SMTP Authentication)

**Problem:**
- Emails not being sent due to SMTP authentication failures
- Error: `535-5.7.8 Username and Password not accepted`
- Gmail rejecting credentials even when they appear correct

**Root Causes:**
1. **Incorrect App Password Format**: Gmail App Passwords must be 16 characters without spaces
2. **Missing 2-Step Verification**: Gmail requires 2-Step Verification to generate App Passwords
3. **Revoked/Expired App Passwords**: Old App Passwords may have been revoked
4. **Wrong Credentials Storage**: Credentials stored in wrong WordPress options

**Solution:**

The notifications module relies on the **Authentication module** for SMTP configuration. Follow these steps:

1. **Enable 2-Step Verification on Gmail**:
   - Go to [Google Account Security](https://myaccount.google.com/security)
   - Enable 2-Step Verification if not already enabled

2. **Generate New App Password**:
   - Go to [App Passwords](https://myaccount.google.com/apppasswords)
   - Select app: **Mail**
   - Select device: **Other (Custom name)** → Enter "WordPress"
   - Click **Generate**
   - Copy the 16-character password (e.g., `abcdefghijklmnop`)

3. **Configure SMTP in Authentication Module**:
   - Navigate to **WordPress Admin → Authentication → Settings**
   - Enter your Gmail address as SMTP username
   - Enter the 16-character App Password (no spaces)
   - Save settings

4. **Test Email Sending**:
   - Navigate to **WordPress Admin → Notifications**
   - Click **Send Test Email**
   - Check your inbox for the test email
   - Review debug logs at `wp-content/debug.log`

**Expected Debug Log Output (Success):**
```
[NOTIFICATIONS] Sending instant email: account_created
[EMAIL SENDER] ✓ Email sent: account_created to user@example.com
[NOTIFICATIONS] ✓ Email sent successfully: account_created
```

**Expected Debug Log Output (Failure):**
```
[EMAIL SENDER] ✗ Email failed: account_created to user@example.com - Error: SMTP Error: Could not authenticate
[EMAIL SENDER] ✗ Authentication failure detected. Please verify the SMTP username and App Password
```

---

### Issue #2: Scattered SMTP Configuration Files

**Problem:**
- Multiple SMTP setup files scattered across the plugin
- Hardcoded credentials in temporary files (security risk)
- Confusion about where to configure SMTP

**Solution:**
All temporary SMTP setup files have been removed:
- ~~`set-smtp-credentials.php`~~ (REMOVED)
- ~~`set-smtp-credentials.sql`~~ (REMOVED)
- ~~`setup-smtp.php`~~ (REMOVED)
- ~~`check-smtp-credentials.php`~~ (REMOVED)

**SMTP configuration is now centralized in the Authentication module only.**

---

## 📋 Module Overview

The Notifications module provides a centralized, reliable email notification system for the entire Organization Core plugin. It uses **Action Scheduler** for queued email delivery and supports both instant and scheduled notifications.

### Key Features

- ✅ **Centralized Email System**: Single source for all plugin notifications
- ✅ **Action Scheduler Integration**: Reliable queued email delivery
- ✅ **Template System**: Customizable HTML email templates
- ✅ **Module-Specific Handlers**: Organized notification handlers per module
- ✅ **SMTP Support**: Uses Authentication module's SMTP configuration
- ✅ **Admin Interface**: Test emails, view logs, manage settings
- ✅ **Error Handling**: Comprehensive error logging and recovery

### Supported Notification Types

#### Authentication Module (4 notifications)
- `account_created` - Welcome email when user registers
- `login_success` - Login confirmation email
- `profile_updated` - Profile change confirmation
- `password_changed` - Password change alert

#### Bookings Module (3 notifications)
- `booking_confirmation_user` - Booking confirmation to user
- `booking_confirmation_admin` - Booking notification to admin
- `rooming_list_due_reminder` - Reminder when rooming list is due

#### Hotels Module (2 notifications)
- `hotel_assigned_user` - Hotel assignment notification to user
- `hotel_assigned_admin` - Hotel assignment notification to admin

#### Rooming List Module (2 notifications)
- `rooming_list_created` - Rooming list creation confirmation
- `rooming_list_due_reminder_3days` - 3-day reminder for rooming list

**Total: 11 active notification types** (expandable to 60+ as documented in requirements)

---

## 📁 File Structure

```
modules/notifications/
├── MODULE.md                           # This file - comprehensive documentation
├── IMPLEMENTATION_GUIDE.md             # Development guide for implementing notifications
├── NOTIFICATIONS_REQUIREMENTS.md       # Complete requirements for all 60+ notifications
│
├── class-notifications.php             # Main module class
├── config.php                          # Module configuration
├── activator.php                       # Module activation logic
├── index.php                           # Directory access protection
│
├── handlers/                           # Module-specific notification handlers
│   ├── class-authentication-notifications.php
│   ├── class-bookings-notifications.php
│   ├── class-hotels-notifications.php
│   └── class-rooming-list-notifications.php
│
├── includes/                           # Core notification system classes
│   ├── class-action-scheduler.php      # Action Scheduler wrapper
│   ├── class-email-sender.php          # Email sending logic
│   ├── class-notification-handler.php  # Central notification handler
│   └── class-template-loader.php       # Email template loader
│
├── templates/                          # Email templates
│   └── emails/
│       ├── base/                       # Base templates (header, footer, wrapper)
│       │   ├── email-header.php
│       │   ├── email-footer.php
│       │   └── email-wrapper.php
│       │
│       ├── authentication/             # Authentication module templates
│       │   ├── account-created.php
│       │   ├── login-success.php
│       │   ├── profile-updated.php
│       │   └── password-changed.php
│       │
│       ├── bookings/                   # Bookings module templates
│       │   ├── booking-confirmation-user.php
│       │   └── booking-confirmation-admin.php
│       │
│       ├── hotels/                     # Hotels module templates
│       │   ├── hotel-assigned-user.php
│       │   └── hotel-assigned-admin.php
│       │
│       └── rooming-list/               # Rooming list module templates
│           ├── rooming-list-created.php
│           └── rooming-list-due-reminder.php
│
└── logs/                               # Notification logs (auto-generated)
    └── index.php
```

---

## 🔧 Core Components

### 1. Main Module Class (`class-notifications.php`)

The main orchestrator that:
- Loads Action Scheduler library
- Initializes all dependencies
- Loads module-specific handlers
- Provides admin interface
- Handles test email sending

**Key Methods:**
- `load_action_scheduler()` - Loads Action Scheduler library
- `load_dependencies()` - Loads core classes
- `load_module_handlers()` - Loads notification handlers
- `init_module_handlers()` - Initializes handlers with hooks
- `render_admin_page()` - Renders admin interface

### 2. Notification Handler (`includes/class-notification-handler.php`)

Central handler that:
- Receives notification requests
- Determines instant vs scheduled delivery
- Queues emails via Action Scheduler
- Tracks notification history

**Key Methods:**
- `trigger($notification_type, $data, $schedule_time)` - Trigger a notification
- `send_instant($notification_type, $data)` - Send email immediately
- `schedule($notification_type, $data, $schedule_time)` - Schedule for later

### 3. Email Sender (`includes/class-email-sender.php`)

Handles email delivery:
- Loads email templates
- Merges data with templates
- Configures SMTP via Authentication module
- Sends emails via `wp_mail()`
- Handles errors and retries

**Key Methods:**
- `send($notification_type, $data)` - Send an email
- `get_template_path($notification_type)` - Get template file path
- `get_recipient($notification_type, $data)` - Determine recipient
- `get_subject($notification_type, $data)` - Generate subject line

### 4. Action Scheduler (`includes/class-action-scheduler.php`)

Manages scheduled notifications:
- Queues emails for future delivery
- Processes scheduled actions
- Handles retries on failure
- Provides admin interface for queue management

**Key Methods:**
- `schedule_notification($notification_type, $data, $timestamp)` - Schedule a notification
- `process_scheduled_notification($notification_type, $data)` - Process scheduled notification
- `get_pending_notifications()` - Get all pending notifications

### 5. Module-Specific Handlers (`handlers/`)

Each handler:
- Hooks into module-specific actions
- Prepares notification data
- Triggers notifications via central handler

**Example: Authentication Notifications**
```php
class OC_Authentication_Notifications {
    public function init() {
        add_action('oc_user_registered', [$this, 'on_user_registered']);
        add_action('oc_user_login', [$this, 'on_user_login']);
    }
    
    public function on_user_registered($user_id) {
        $user = get_userdata($user_id);
        $data = [
            'user_name' => $user->display_name,
            'user_email' => $user->user_email,
            'login_url' => wp_login_url(),
        ];
        
        OC_Notification_Handler::trigger('account_created', $data);
    }
}
```

---

## 🎨 Email Template System

### Template Structure

Each email template consists of:
1. **Header** (`templates/emails/base/email-header.php`) - Logo, branding
2. **Content** (`templates/emails/{module}/{template}.php`) - Email body
3. **Footer** (`templates/emails/base/email-footer.php`) - Unsubscribe, copyright

### Template Variables

Templates have access to these default variables:
- `$site_name` - WordPress site name
- `$login_url` - Login page URL
- `$user_name` - Recipient's name
- `$user_email` - Recipient's email

Plus notification-specific variables passed in `$data`.

### Creating a New Template

1. **Create template file**:
   ```php
   // templates/emails/bookings/payment-received.php
   <h2>Payment Received!</h2>
   <p>Hi <?php echo esc_html($user_name); ?>,</p>
   <p>We've received your payment of <?php echo esc_html($amount); ?> for booking #<?php echo esc_html($booking_id); ?>.</p>
   ```

2. **Add to template map** in `class-email-sender.php`:
   ```php
   private function get_template_path($notification_type) {
       $template_map = array(
           'payment_received' => 'bookings/payment-received.php',
           // ... other templates
       );
   }
   ```

3. **Add subject** in `class-email-sender.php`:
   ```php
   private function get_subject($notification_type, $data) {
       $subjects = array(
           'payment_received' => 'Payment Received - Booking #' . ($data['booking_id'] ?? ''),
           // ... other subjects
       );
   }
   ```

4. **Trigger notification** from your module:
   ```php
   OC_Notification_Handler::trigger('payment_received', [
       'user_name' => $user->display_name,
       'user_email' => $user->user_email,
       'booking_id' => $booking_id,
       'amount' => '$500.00',
   ]);
   ```

---

## 🚀 Usage Examples

### Two Ways to Trigger Notifications

The notifications module provides **two methods** for triggering email notifications:

#### Method 1: Static `trigger()` Method (Recommended)
```php
// Direct method call - clean and simple
OC_Notification_Handler::trigger('notification_type', $data, $delay);
```

**Advantages:**
- ✅ Clean, direct syntax
- ✅ IDE autocomplete support
- ✅ Type checking
- ✅ Immediate feedback if class doesn't exist

#### Method 2: WordPress `do_action()` Hook
```php
// WordPress-native action hook
do_action('oc_send_notification', 'notification_type', $data, $delay);
```

**Advantages:**
- ✅ WordPress-native pattern
- ✅ Decoupled from notifications module
- ✅ Works even if notifications module is disabled (fails gracefully)
- ✅ Can be filtered/modified by other plugins
- ✅ Familiar to WordPress developers

**Both methods are fully supported and do the exact same thing internally.**

---

### Example 1: Send Instant Notification

**Using Static Method:**
```php
// From any module
OC_Notification_Handler::trigger('booking_confirmation_user', [
    'user_name' => 'John Doe',
    'user_email' => 'john@example.com',
    'booking_id' => 123,
    'booking_ref' => 'BK-2024-123',
    'event_name' => 'Summer Music Festival',
    'event_date' => '2024-07-15',
]);
```

**Using do_action:**
```php
// WordPress-native approach
do_action('oc_send_notification', 'booking_confirmation_user', [
    'user_name' => 'John Doe',
    'user_email' => 'john@example.com',
    'booking_id' => 123,
    'booking_ref' => 'BK-2024-123',
    'event_name' => 'Summer Music Festival',
    'event_date' => '2024-07-15',
]);
```

### Example 2: Schedule Notification for Later

**Using Static Method:**
```php
// Schedule reminder for 3 days before event
$reminder_time = strtotime($event_date . ' -3 days');

OC_Notification_Handler::trigger('rooming_list_due_reminder_3days', [
    'user_name' => 'John Doe',
    'user_email' => 'john@example.com',
    'booking_ref' => 'BK-2024-123',
    'due_date' => $due_date,
], $reminder_time);
```

**Using do_action:**
```php
// Schedule reminder using WordPress action
$reminder_time = strtotime($event_date . ' -3 days');

do_action('oc_send_notification', 'rooming_list_due_reminder_3days', [
    'user_name' => 'John Doe',
    'user_email' => 'john@example.com',
    'booking_ref' => 'BK-2024-123',
    'due_date' => $due_date,
], $reminder_time);
```

### Example 3: Send to Admin

**Using Static Method:**
```php
// Send notification to admin
OC_Notification_Handler::trigger('booking_confirmation_admin', [
    'recipient_type' => 'admin', // Important!
    'booking_id' => 123,
    'customer_name' => 'John Doe',
    'customer_email' => 'john@example.com',
]);
```

**Using do_action:**
```php
// Send to admin using WordPress action
do_action('oc_send_notification', 'booking_confirmation_admin', [
    'recipient_type' => 'admin', // Important!
    'booking_id' => 123,
    'customer_name' => 'John Doe',
    'customer_email' => 'john@example.com',
]);
```

### Example 4: Hook into Module Actions

```php
// In your module's handler class
class OC_Bookings_Notifications {
    public function init() {
        // Hook into booking creation
        add_action('oc_booking_created', [$this, 'on_booking_created'], 10, 2);
    }
    
    public function on_booking_created($booking_id, $booking_data) {
        // Prepare notification data
        $user = get_userdata($booking_data['user_id']);
        
        $data = [
            'user_name' => $user->display_name,
            'user_email' => $user->user_email,
            'booking_id' => $booking_id,
            'booking_ref' => $booking_data['reference'],
            'event_name' => $booking_data['event_name'],
        ];
        
        // Option 1: Use static method
        OC_Notification_Handler::trigger('booking_confirmation_user', $data);
        
        // Option 2: Use do_action (same result)
        // do_action('oc_send_notification', 'booking_confirmation_user', $data);
    }
}
```

### Example 5: Triggering from CRUD Operations

```php
// In your module's CRUD class
class OC_Hotels_CRUD {
    public static function create_hotel($data) {
        // ... create hotel logic ...
        
        $hotel_id = $wpdb->insert_id;
        
        // Trigger notification using do_action
        do_action('oc_send_notification', 'hotel_created_admin', [
            'hotel_id' => $hotel_id,
            'hotel_name' => $data['hotel_name'],
            'created_by' => get_current_user_id(),
        ]);
        
        return $hotel_id;
    }
}
```

### Example 6: Conditional Notifications

```php
// Send notification only if certain conditions are met
if ($booking_status === 'confirmed' && $send_confirmation) {
    do_action('oc_send_notification', 'booking_confirmation_user', [
        'user_name' => $user->display_name,
        'user_email' => $user->user_email,
        'booking_id' => $booking_id,
    ]);
}
```

---

### Which Method Should You Use?

**Use Static `trigger()` Method When:**
- You want IDE autocomplete and type checking
- You're writing new code from scratch
- You want immediate feedback if notifications module is missing
- You prefer clean, direct syntax

**Use `do_action()` Hook When:**
- You want WordPress-native patterns
- You need the notification to fail gracefully if module is disabled
- You want other plugins to be able to intercept/modify notifications
- You're more comfortable with WordPress hooks
- You want maximum flexibility and decoupling

**Both are equally valid and supported!**



---

## ⚙️ SMTP Configuration

### Prerequisites

1. **Gmail Account** with 2-Step Verification enabled
2. **Gmail App Password** (16 characters, no spaces)
3. **Authentication Module** installed and activated

### Configuration Steps

1. **Generate Gmail App Password**:
   - Visit [https://myaccount.google.com/apppasswords](https://myaccount.google.com/apppasswords)
   - Select app: **Mail**
   - Select device: **Other** → Enter "WordPress"
   - Click **Generate**
   - Copy the 16-character password

2. **Configure in WordPress**:
   - Go to **WordPress Admin → Authentication → Settings**
   - Enter Gmail address as **SMTP Username**
   - Enter App Password as **SMTP Password** (no spaces!)
   - **From Email**: Your Gmail address
   - **From Name**: Your site name (e.g., "Forum Music Festivals")
   - Click **Save Settings**

3. **Test Configuration**:
   - Go to **WordPress Admin → Notifications**
   - Click **Send Test Email**
   - Check inbox for test email
   - Review `wp-content/debug.log` for any errors

### SMTP Settings Reference

The Authentication module stores SMTP credentials in these WordPress options:
- `mus_gmail_username` - SMTP username (Gmail address)
- `mus_gmail_password` - SMTP password (App Password)
- `mus_from_email` - From email address
- `mus_from_name` - From name

**Do not modify these directly!** Use the Authentication module admin interface.

---

## 🐛 Troubleshooting

### Problem: Emails Not Sending

**Symptoms:**
- No emails received
- Debug log shows: `[EMAIL SENDER] ✗ Email failed`

**Solutions:**

1. **Check SMTP Credentials**:
   ```
   - Verify App Password is 16 characters (no spaces)
   - Ensure 2-Step Verification is enabled on Gmail
   - Try generating a new App Password
   ```

2. **Check Debug Logs**:
   ```
   - Enable WordPress debug: WP_DEBUG = true in wp-config.php
   - Check wp-content/debug.log for errors
   - Look for SMTP authentication errors
   ```

3. **Test SMTP Connection**:
   ```
   - Go to Authentication → Settings
   - Click "Test SMTP Connection"
   - Review test results
   ```

4. **Check Action Scheduler**:
   ```
   - Go to Tools → Action Scheduler
   - Look for failed actions
   - Check error messages
   ```

---

### Problem: Wrong Email Template

**Symptoms:**
- Email sent but content is wrong
- Missing variables in email

**Solutions:**

1. **Check Template Mapping**:
   ```php
   // In class-email-sender.php
   private function get_template_path($notification_type) {
       // Verify notification_type maps to correct template
   }
   ```

2. **Check Data Variables**:
   ```php
   // Ensure all required variables are passed
   OC_Notification_Handler::trigger('notification_type', [
       'user_name' => $name,    // Required
       'user_email' => $email,  // Required
       // ... other variables
   ]);
   ```

3. **Check Template File**:
   ```
   - Verify template file exists
   - Check for PHP errors in template
   - Ensure variables are properly escaped
   ```

---

### Problem: Scheduled Notifications Not Sending

**Symptoms:**
- Instant emails work, but scheduled ones don't
- Action Scheduler shows pending actions

**Solutions:**

1. **Check WP-Cron**:
   ```
   - Ensure WP-Cron is running
   - Check if DISABLE_WP_CRON is false
   - Manually trigger: wp-admin/admin-ajax.php?action=run_cron
   ```

2. **Check Action Scheduler**:
   ```
   - Go to Tools → Action Scheduler
   - Look for pending/failed actions
   - Manually run pending actions
   ```

3. **Check Schedule Time**:
   ```php
   // Ensure timestamp is in the future
   $schedule_time = strtotime('+3 days'); // Future time
   OC_Notification_Handler::trigger('notification', $data, $schedule_time);
   ```

---

### Problem: Duplicate Emails

**Symptoms:**
- Users receiving multiple copies of same email
- Action Scheduler shows duplicate actions

**Solutions:**

1. **Check for Duplicate Hooks**:
   ```php
   // Ensure handlers are initialized only once
   // In class-notifications.php
   public function init_module_handlers() {
       static $initialized = false;
       if ($initialized) return;
       $initialized = true;
       // ... initialize handlers
   }
   ```

2. **Check Action Scheduler**:
   ```
   - Go to Tools → Action Scheduler
   - Look for duplicate scheduled actions
   - Cancel duplicates
   ```

---

## 📊 Admin Interface

### Notifications Admin Page

**Location:** WordPress Admin → Notifications

**Features:**
- **Test Email**: Send a test email to verify SMTP configuration
- **Unsent Queue**: View emails that failed to send
- **Resend Failed**: Retry sending failed emails
- **Clear Queue**: Clear the unsent email queue
- **View Logs**: Access notification logs

### Action Scheduler Interface

**Location:** WordPress Admin → Tools → Action Scheduler

**Features:**
- View all scheduled notifications
- See pending, completed, and failed actions
- Manually run pending actions
- Cancel scheduled actions
- View action logs and errors

---

## 🔐 Security Considerations

### SMTP Credentials

- ✅ Credentials stored in WordPress options (encrypted by WordPress)
- ✅ No hardcoded credentials in code
- ✅ Temporary setup files removed
- ✅ Admin-only access to SMTP settings

### Email Content

- ✅ All variables escaped with `esc_html()` in templates
- ✅ No user input directly in email subjects
- ✅ HTML emails use Content-Type header

### Access Control

- ✅ Admin interface requires `manage_options` capability
- ✅ SMTP test requires administrator role
- ✅ Notification triggers validated before sending

---

## 📈 Future Enhancements

### Planned Features

1. **Email Queue Dashboard**: Visual dashboard for email queue status
2. **Notification Preferences**: User-level notification preferences
3. **Email Templates Editor**: Admin interface to edit templates
4. **Multiple SMTP Providers**: Support for SendGrid, Mailgun, etc.
5. **Email Analytics**: Track open rates, click rates
6. **Notification History**: Complete history of sent notifications
7. **Bulk Notifications**: Send notifications to multiple users
8. **Email Scheduling UI**: Schedule notifications via admin interface

### Expandable Notification Types

See `NOTIFICATIONS_REQUIREMENTS.md` for complete list of 60+ planned notification types across all modules.

---

## 📚 Related Documentation

- [IMPLEMENTATION_GUIDE.md](./IMPLEMENTATION_GUIDE.md) - Developer guide for implementing notifications
- [NOTIFICATIONS_REQUIREMENTS.md](./NOTIFICATIONS_REQUIREMENTS.md) - Complete requirements for all notifications
- [Authentication Module](../authentication/MODULE_GUIDE.md) - SMTP configuration details
- [Action Scheduler Documentation](https://actionscheduler.org/) - Action Scheduler library docs

---

## 🆘 Support

### Debug Mode

Enable debug mode to see detailed logs:

```php
// In wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

Logs will be written to `wp-content/debug.log`.

### Common Debug Log Patterns

**Success:**
```
[NOTIFICATIONS] Sending instant email: account_created
[EMAIL SENDER] ✓ Email sent: account_created to user@example.com
[NOTIFICATIONS] ✓ Email sent successfully: account_created
```

**SMTP Auth Failure:**
```
[EMAIL SENDER] ✗ Email failed: account_created - Error: SMTP Error: Could not authenticate
[EMAIL SENDER] ✗ Authentication failure detected. Please verify SMTP credentials
```

**Template Not Found:**
```
[EMAIL SENDER] Template not found: /path/to/template.php
```

**No Recipient:**
```
[EMAIL SENDER] No recipient for: notification_type
```

---

## 📝 Changelog

### Version 2.0.0 (Current)
- ✅ Removed temporary SMTP setup files (security fix)
- ✅ Removed placeholder files from module
- ✅ Consolidated SMTP configuration to Authentication module
- ✅ Created comprehensive MODULE.md documentation
- ✅ Cleaned up module structure
- ✅ Improved error handling and logging

### Version 1.0.0
- Initial implementation with Action Scheduler
- Basic email templates for authentication, bookings, hotels, rooming list
- Module-specific notification handlers
- Admin interface for testing and management

---

**Last Updated:** 2025-12-16  
**Maintained By:** OwlthTech Development Team
