<?php
/**
 * Notifications Module
 * Centralized notification system with Action Scheduler
 *
 * @package    Organization_Core
 * @subpackage Notifications
 * @version    2.0.0
 */

if (!defined('WPINC')) {
    die;
}

class OC_Notifications_Module extends OC_Abstract_Module {
    
    private $notification_handler;
    private $action_scheduler;
    private $module_handlers = array();
    
    public function __construct() {
        parent::__construct();
        $config = $this->get_config();
        $this->module_id = $config['id'];
        $this->module_name = $config['name'];
        $this->version = $config['version'];
        
        // Load Action Scheduler
        $this->load_action_scheduler();
        
        // Load core classes
        $this->load_dependencies();
    }

    /**
     * Load Action Scheduler library
     */
    private function load_action_scheduler() {
        // Use WordPress constant for plugin directory
        $plugin_slug = 'organization-core-new';
        $plugin_dir = WP_PLUGIN_DIR . '/' . $plugin_slug;
        
        // Load Action Scheduler main file directly
        $as_main_file = $plugin_dir . '/vendor/woocommerce/action-scheduler/action-scheduler.php';
        
        if (file_exists($as_main_file)) {
            require_once $as_main_file;
            
            // Verify Action Scheduler is loaded
            if (function_exists('as_next_scheduled_action')) {
                error_log('[NOTIFICATIONS] ✓ Action Scheduler loaded successfully');
            } else {
                error_log('[NOTIFICATIONS] ✗ Action Scheduler file loaded but functions not available');
            }
        } else {
            error_log('[NOTIFICATIONS] ✗ Action Scheduler not found at: ' . $as_main_file);
            
            // Try autoload as fallback
            $autoload_path = $plugin_dir . '/vendor/autoload.php';
            if (file_exists($autoload_path)) {
                require_once $autoload_path;
                error_log('[NOTIFICATIONS] Tried autoload fallback');
            }
        }
    }
    
    /**
     * Load required dependencies
     */
    private function load_dependencies() {
        // Load logger
        require_once plugin_dir_path(__FILE__) . 'logs/class-notification-logger.php';
        
        // Load core classes
        require_once plugin_dir_path(__FILE__) . 'includes/class-notification-handler.php';
        require_once plugin_dir_path(__FILE__) . 'includes/class-action-scheduler.php';
        
        // Load module-specific handlers
        $this->load_module_handlers();
    }
    
    /**
     * Load all module-specific notification handlers
     */
    private function load_module_handlers() {
        $handlers_dir = plugin_dir_path(__FILE__) . 'handlers/';
        
        $handler_files = array(
            'class-authentication-notifications.php',
            'class-bookings-notifications.php',
            'class-hotels-notifications.php',
            'class-rooming-list-notifications.php',
        );
        
        foreach ($handler_files as $file) {
            $file_path = $handlers_dir . $file;
            if (file_exists($file_path)) {
                require_once $file_path;
            }
        }
    }

    public function init() {
        $validator = new OC_Module_Validator();
        
        if (!$validator->is_module_enabled_for_site($this->get_module_id())) {
            return;
        }

        // Initialize notification handler
        $this->notification_handler = OC_Notification_Handler::get_instance();
        $this->notification_handler->init();

        // Ensure global SMTP configuration (Authentication module) is initialized so
        // PHPMailer hooks are present when Action Scheduler runs background jobs.
        $auth_smtp_file = plugin_dir_path( dirname(__FILE__) ) . '../authentication/includes/class-authentication-email-smtp.php';
        if (class_exists('Authentication_Email_SMTP')) {
            Authentication_Email_SMTP::get_instance();
            error_log('[NOTIFICATIONS] Authentication SMTP class already loaded and initialized');
        } elseif (file_exists($auth_smtp_file)) {
            require_once $auth_smtp_file;
            if (class_exists('Authentication_Email_SMTP')) {
                Authentication_Email_SMTP::get_instance();
                error_log('[NOTIFICATIONS] Authentication SMTP loaded and initialized for notifications');
            } else {
                error_log('[NOTIFICATIONS] Authentication SMTP file found but class missing');
            }
        } else {
            error_log('[NOTIFICATIONS] Authentication SMTP file not found; SMTP may not be configured');
        }
        
        // Initialize Action Scheduler
        $this->action_scheduler = OC_Action_Scheduler::get_instance();
        $this->action_scheduler->init();
        
        // Initialize module-specific handlers
        $this->init_module_handlers();
        
        // Handle settings post
        add_action('admin_post_oc_update_notifications_settings', array($this, 'handle_admin_post_update_settings'));

        // Handle test email post
        add_action('admin_post_oc_notifications_send_test', array($this, 'handle_admin_post_send_test'));
        add_action('admin_post_oc_notifications_clear_unsent', array($this, 'handle_admin_post_clear_unsent'));
        add_action('admin_post_oc_notifications_resend_unsent', array($this, 'handle_admin_post_resend_unsent'));

        // Add admin menu
        add_action('admin_menu', array($this, 'add_admin_menu'));
        // Show notice if there are queued unsent emails due to SMTP problems
        add_action('admin_notices', array($this, 'maybe_show_unsent_notice'));
        
        error_log('[NOTIFICATIONS] Module initialized with Action Scheduler');
    }

    /**
     * Show admin notice if there are unsent emails queued
     */
    public function maybe_show_unsent_notice()
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        $unsent = get_option('mus_unsent_emails', array());
        if (!empty($unsent)) {
            $count = count($unsent);
            $link = admin_url('admin.php?page=notifications');
            echo '<div class="notice notice-error"><p><strong>Notifications:</strong> There are ' . esc_html($count) . ' unsent email(s) queued due to SMTP problems. <a href="' . esc_url($link) . '">Open Notifications</a> to review and resend after fixing SMTP settings.</p></div>';
        }
    }
    
    /**
     * Initialize all module-specific handlers
     */
    private function init_module_handlers() {
        $handler_classes = array(
            'OC_Authentication_Notifications',
            'OC_Bookings_Notifications',
            'OC_Hotels_Notifications',
            'OC_Rooming_List_Notifications',
        );
        
        foreach ($handler_classes as $class) {
            if (class_exists($class)) {
                $handler = new $class();
                $handler->init();
                $this->module_handlers[] = $handler;
                error_log('[NOTIFICATIONS] Initialized handler: ' . $class);
            }
        }
    }

    public function get_module_id() {
        return 'notifications';
    }

    /**
     * Handle admin POST for sending a test email from the Notifications admin page
     */
    public function handle_admin_post_send_test() {
        if (!current_user_can('manage_options')) {
            wp_die(__('Unauthorized', 'organization-core'));
        }

        check_admin_referer('oc_notifications_test_email');

        $to = isset($_POST['test_email_to']) ? sanitize_email($_POST['test_email_to']) : '';
        $template = isset($_POST['test_template']) ? sanitize_text_field($_POST['test_template']) : 'account_created';

        $redirect = wp_get_referer() ?: admin_url('admin.php?page=notifications');

        if (empty($to) || !is_email($to)) {
            $redirect = add_query_arg('test_error', urlencode('Invalid recipient email'), $redirect);
            wp_redirect($redirect);
            exit;
        }

        // Prepare sample data for common templates
        $sample_name = 'Test User';
        $sample_data = array(
            'user_name' => $sample_name,
            'user_email' => $to,
            'login_url' => wp_login_url(),
            'site_name' => get_bloginfo('name'),
        );

        // Use OC_Email_Sender directly for immediate synchronous test
        require_once plugin_dir_path(__FILE__) . '/includes/class-email-sender.php';
        $sender = new OC_Email_Sender();

        $sent = $sender->send($template, $sample_data);

        if ($sent) {
            $redirect = add_query_arg('test_sent', '1', $redirect);
        } else {
            $error = $sender->get_last_error() ?: 'Unknown error';
            $redirect = add_query_arg('test_error', urlencode($error), $redirect);
        }

        wp_redirect($redirect);
        exit;
    }

    public function get_config() {
        return require plugin_dir_path(__FILE__) . 'config.php';
    }

    public function add_admin_menu() {
        add_menu_page(
            __('Notifications', 'organization-core'),
            __('Notifications', 'organization-core'),
            'manage_options',
            'notifications',
            array($this, 'render_admin_page'),
            'dashicons-email-alt',
            25
        );
        
        // Add submenu for Action Scheduler
        if (function_exists('as_next_scheduled_action')) {
            add_submenu_page(
                'notifications',
                __('Scheduled Actions', 'organization-core'),
                __('Scheduled Actions', 'organization-core'),
                'manage_options',
                'action-scheduler',
                array($this, 'render_scheduler_page')
            );
        }
    }

    public function render_admin_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Notifications System', 'organization-core'); ?></h1>
            
            <div class="card">
                <h2><?php _e('System Status', 'organization-core'); ?></h2>
                <table class="widefat">
                    <tr>
                        <td><strong>Action Scheduler:</strong></td>
                        <td><?php echo function_exists('as_next_scheduled_action') ? '✓ Active' : '✗ Not Available'; ?></td>
                    </tr>
                    <tr>
                        <td><strong>Notification Handler:</strong></td>
                        <td><?php echo isset($this->notification_handler) ? '✓ Initialized' : '✗ Not Initialized'; ?></td>
                    </tr>
                    <tr>
                        <td><strong>Module Handlers:</strong></td>
                        <td><?php echo count($this->module_handlers) . ' handlers loaded'; ?></td>
                    </tr>
                </table>
            </div>
            
            <div class="card">
                <h2><?php _e('Active Notification Handlers', 'organization-core'); ?></h2>
                <ul>
                    <li><strong>Authentication:</strong> 5 notifications (Account Created, Login Success, Profile Updated, Password Changed, Account Inactive)</li>
                    <li><strong>Bookings:</strong> 9 notifications (Confirmation, Status Changed, Payment, Reminders, Draft Expiring)</li>
                    <li><strong>Hotels:</strong> 5 notifications (Created, Updated, Deleted, Assigned, Availability Report)</li>
                    <li><strong>Rooming List:</strong> 7 notifications (Created, Updated, Locked, Auto-Locked, Incomplete Warning)</li>
                </ul>
                <p><strong>Total:</strong> 26+ notifications ready</p>
            </div>
            
            <?php if (function_exists('as_next_scheduled_action')): ?>
            <div class="card">
                <h2><?php _e('Scheduled Checks', 'organization-core'); ?></h2>
                <table class="widefat">
                    <thead>
                        <tr>
                            <th>Check Type</th>
                            <th>Frequency</th>
                            <th>Next Run</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $scheduled_actions = array(
                            'oc_check_booking_reminders' => array('Daily', '7 days before festival'),
                            'oc_check_rooming_list_due_dates' => array('Daily', '3 days before due date'),
                            'oc_check_rooming_list_incomplete' => array('Daily', '1 day before due date'),
                            'oc_check_quote_followups' => array('Daily', '48 hours after submission'),
                            'oc_send_hotel_availability_report' => array('Weekly', 'Monday 9 AM'),
                            'oc_check_school_expiring' => array('Monthly', '1 year old schools'),
                        );
                        
                        foreach ($scheduled_actions as $hook => $info) {
                            $next_run = as_next_scheduled_action($hook);
                            ?>
                            <tr>
                                <td><?php echo esc_html($info[1]); ?></td>
                                <td><?php echo esc_html($info[0]); ?></td>
                                <td><?php echo $next_run ? date('Y-m-d H:i:s', $next_run) : 'Not Scheduled'; ?></td>
                                <td><?php echo $next_run ? '✓ Active' : '✗ Inactive'; ?></td>
                            </tr>
                            <?php
                        }
                        ?>
                    </tbody>
                </table>
                
                <p style="margin-top: 15px;">
                    <a href="<?php echo admin_url('admin.php?page=action-scheduler'); ?>" class="button button-primary">
                        View All Scheduled Actions
                    </a>
                </p>
            </div>

            <?php // Notifications settings ?>
            <div class="card">
                <h2><?php _e('Notifications Settings', 'organization-core'); ?></h2>
                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                    <?php wp_nonce_field('oc_update_notifications_settings'); ?>
                    <input type="hidden" name="action" value="oc_update_notifications_settings" />
                    <table class="form-table">
                        <tr>
                            <th scope="row">Enable Async Email Sending</th>
                            <td>
                                <?php $async = get_option('mus_notifications_async'); ?>
                                <label><input type="checkbox" name="mus_notifications_async" value="1" <?php checked(1, (int) $async); ?> /> Send emails asynchronously through Action Scheduler</label>
                            </td>
                        </tr>
                    </table>
                    <p>
                        <button type="submit" class="button button-primary">Save Settings</button>
                    </p>
                </form>
                <script>
                (function(){
                    var selectAll = document.getElementById('unsent_select_all');
                    if (!selectAll) return;
                    selectAll.addEventListener('change', function(){
                        var checkboxes = document.querySelectorAll('input[name="unsent_selected[]"]');
                        for (var i=0;i<checkboxes.length;i++) { checkboxes[i].checked = selectAll.checked; }
                    });
                })();
                </script>

                <?php $unsent = get_option('mus_unsent_emails', array()); if (!empty($unsent)): ?>
                <h3><?php _e('Unsent Emails (Queued)', 'organization-core'); ?></h3>
                <p>These messages were queued after repeated SMTP authentication failures. They will not be retried automatically until credentials are fixed. Use the controls below to resend selected messages after fixing your SMTP settings.</p>

                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                    <?php wp_nonce_field('oc_notifications_resend_unsent'); ?>
                    <input type="hidden" name="action" value="oc_notifications_resend_unsent" />

                    <table class="widefat">
                        <thead>
                            <tr>
                                <th><input type="checkbox" id="unsent_select_all" /></th>
                                <th>Time</th>
                                <th>To</th>
                                <th>Subject</th>
                                <th>Reason</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach (array_reverse(array_slice($unsent, -50), true) as $idx => $u): ?>
                                <tr>
                                    <td><input type="checkbox" name="unsent_selected[]" value="<?php echo esc_attr($idx); ?>" /></td>
                                    <td><?php echo date('Y-m-d H:i:s', $u['time']); ?></td>
                                    <td><?php echo esc_html(is_array($u['to']) ? implode(', ', $u['to']) : $u['to']); ?></td>
                                    <td><?php echo esc_html($u['subject'] ?? ''); ?></td>
                                    <td><?php echo esc_html($u['error']['error'] ?? ($u['reason'] ?? 'unsent')); ?></td>
                                    <td>
                                        <button type="submit" name="single_index" value="<?php echo esc_attr($idx); ?>" class="button">Resend</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <p style="margin-top:10px;">
                        <button type="submit" name="resend_action" value="selected" class="button button-primary">Resend Selected</button>
                        <button type="submit" name="resend_action" value="all" class="button">Resend All</button>
                    </p>
                </form>

                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="margin-top:10px; display:inline-block;">
                    <?php wp_nonce_field('oc_notifications_clear_unsent'); ?>
                    <input type="hidden" name="action" value="oc_notifications_clear_unsent" />
                    <button type="submit" class="button">Clear Unsent Messages</button>
                </form>
                <?php endif; ?>

                <h3><?php _e('Send Test Email', 'organization-core'); ?></h3>
                <p>Use this form to send a short test email using the Notification templates. If the send fails, run the SMTP Test in the Authentication module and verify your credentials (App Password for Gmail).</p>
                <?php if (isset($_GET['test_sent'])): ?>
                    <div class="notice notice-success is-dismissible"><p>Test email sent successfully.</p></div>
                <?php elseif (isset($_GET['test_error'])): ?>
                    <div class="notice notice-error is-dismissible"><p>Error sending test email: <?php echo esc_html(urldecode($_GET['test_error'])); ?></p></div>
                <?php endif; ?>

                <?php if (isset($_GET['resend_success']) || isset($_GET['resend_failed']) || isset($_GET['resend_error'])): ?>
                    <?php $s = intval($_GET['resend_success'] ?? 0); $f = intval($_GET['resend_failed'] ?? 0); ?>
                    <?php if (!empty($_GET['resend_error'])): ?>
                        <div class="notice notice-error is-dismissible"><p><?php echo esc_html(urldecode($_GET['resend_error'])); ?></p></div>
                    <?php else: ?>
                        <div class="notice notice-success is-dismissible"><p>Resend completed. <strong><?php echo esc_html($s); ?></strong> succeeded, <strong><?php echo esc_html($f); ?></strong> failed.</p></div>
                    <?php endif; ?>
                <?php endif; ?>

                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                    <?php wp_nonce_field('oc_notifications_test_email'); ?>
                    <input type="hidden" name="action" value="oc_notifications_send_test" />
                    <table class="form-table">
                        <tr>
                            <th scope="row"><label for="test_email_to">Recipient Email</label></th>
                            <td><input type="email" id="test_email_to" name="test_email_to" required class="regular-text" value="<?php echo esc_attr(get_option('admin_email')); ?>" /></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="test_template">Template</label></th>
                            <td>
                                <select id="test_template" name="test_template">
                                    <option value="account_created">Account Created (sample)</option>
                                    <option value="login_success">Login Success (sample)</option>
                                    <option value="profile_updated">Profile Updated (sample)</option>
                                </select>
                                <p class="description">Templates use sample data (user name & email) for rendering.</p>
                            </td>
                        </tr>
                    </table>
                    <p>
                        <button type="submit" class="button button-secondary">Send Test Email</button>
                    </p>
                </form>
            </div>

            <?php endif; ?>
        </div>
        <?php
    }
    
    public function render_scheduler_page() {
        // Redirect to Action Scheduler admin page
        if (class_exists('ActionScheduler_AdminView')) {
            $admin_view = new ActionScheduler_AdminView();
            $admin_view->render_admin_ui();
        } else {
            echo '<div class="wrap">';
            echo '<h1>Action Scheduler</h1>';
            echo '<p>Action Scheduler admin interface not available.</p>';
            echo '</div>';
        }
    }

    /**
     * Handle settings form post
     */
    public function handle_admin_post_update_settings() {
        if (!current_user_can('manage_options')) {
            wp_die(__('Unauthorized', 'organization-core'));
        }

        check_admin_referer('oc_update_notifications_settings');

        $async = isset($_POST['mus_notifications_async']) ? 1 : 0;
        update_option('mus_notifications_async', $async);

        $redirect = wp_get_referer() ?: admin_url('admin.php?page=notifications');
        wp_redirect(add_query_arg('settings-updated', 'true', $redirect));
        exit;
    }

    /**
     * Clear the unsent emails queue (admin action)
     */
    public function handle_admin_post_clear_unsent()
    {
        if (!current_user_can('manage_options')) {
            wp_die(__('Unauthorized', 'organization-core'));
        }

        check_admin_referer('oc_notifications_clear_unsent');

        delete_option('mus_unsent_emails');

        $redirect = wp_get_referer() ?: admin_url('admin.php?page=notifications');
        wp_redirect(add_query_arg('cleared_unsent', '1', $redirect));
        exit;
    }

    /**
     * Resend selected or all unsent messages (admin action)
     */
    public function handle_admin_post_resend_unsent()
    {
        if (!current_user_can('manage_options')) {
            wp_die(__('Unauthorized', 'organization-core'));
        }

        check_admin_referer('oc_notifications_resend_unsent');

        // If SMTP auth failure transient exists, do not attempt resends — advise admin to fix credentials first
        $smtp_auth_failure = get_transient('mus_smtp_auth_failure');
        if (!empty($smtp_auth_failure)) {
            $redirect = wp_get_referer() ?: admin_url('admin.php?page=notifications');
            $redirect = add_query_arg('resend_error', urlencode('SMTP authentication failure is active. Please fix SMTP settings and re-run the SMTP test first.'), $redirect);
            wp_redirect($redirect);
            exit;
        }

        $unsent = get_option('mus_unsent_emails', array());
        if (empty($unsent)) {
            $redirect = wp_get_referer() ?: admin_url('admin.php?page=notifications');
            $redirect = add_query_arg('resend_error', urlencode('No unsent messages to resend.'), $redirect);
            wp_redirect($redirect);
            exit;
        }

        $selected = isset($_POST['unsent_selected']) && is_array($_POST['unsent_selected']) ? array_map('intval', $_POST['unsent_selected']) : array();
        $action = sanitize_text_field($_POST['resend_action'] ?? '');
        $single_index = isset($_POST['single_index']) ? intval($_POST['single_index']) : null;

        // Determine indices to attempt
        if ($single_index !== null) {
            $indices = array($single_index);
        } elseif ($action === 'all') {
            $indices = array_keys($unsent);
        } else {
            $indices = $selected;
        }

        $success = 0;
        $failed = 0;

        // Sort indices so we handle stable order
        sort($indices);

        // We'll build a new remaining queue with failed ones
        $remaining = $unsent;

        foreach ($indices as $idx) {
            if (!isset($unsent[$idx])) {
                $failed++;
                continue;
            }

            $item = $unsent[$idx];
            $to = $item['to'];
            $subject = $item['subject'] ?? '';
            $message = $item['message'] ?? '';
            $headers = $item['headers'] ?? array();

            // Attempt to send via wp_mail so PHPMailer hooks apply
            $sent = wp_mail($to, $subject, $message, $headers);

            if ($sent) {
                $success++;
                // remove from remaining
                unset($remaining[$idx]);

                // Log success
                error_log('[NOTIFICATIONS] Resent queued email to: ' . (is_array($to) ? implode(',', $to) : $to) . ' subject: ' . $subject);
                if (class_exists('OC_Notification_Logger')) {
                    OC_Notification_Logger::log('unsent_resend', array('to' => $to, 'subject' => $subject), true, 'Resent by admin');
                }
            } else {
                $failed++;
                error_log('[NOTIFICATIONS] ✗ Failed to resend queued email to: ' . (is_array($to) ? implode(',', $to) : $to) . ' subject: ' . $subject);
            }
        }

        // Persist remaining queue
        update_option('mus_unsent_emails', $remaining);

        $redirect = wp_get_referer() ?: admin_url('admin.php?page=notifications');
        $redirect = add_query_arg(array('resend_success' => $success, 'resend_failed' => $failed), $redirect);
        wp_redirect($redirect);
        exit;
    }
}

// Register module
add_action('organization_core_register_modules', function() {
    $config = require plugin_dir_path(__FILE__) . 'config.php';
    OC_Module_Registry::register_module($config['id'], $config);
});
