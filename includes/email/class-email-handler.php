<?php

/**
 * Email Handler - Send emails with custom templates
 * 
 * No wrapper - templates handle their own HTML structure
 * 
 * @package    Organization_Core
 * @subpackage Includes/Email
 */

if (!defined('ABSPATH')) {
    exit;
}

class OC_Email_Handler
{
    /**
     * Send email using module template - SIMPLE VERSION
     * 
     *   No wrapper added
     *   Template is complete HTML
     *   Only validates template exists
     * 
     * @param string $to Recipient email
     * @param string $subject Email subject
     * @param string $template_name Template file name
     * @param string $module_id Module ID
     * @param array $template_data Data for template
     * @return bool Success
     */
    public static function send_with_template($to, $subject, $template_name, $module_id = '', $template_data = array())
    {
        // Validate email
        if (!is_email($to)) {
            error_log('[EMAIL] Invalid email: ' . $to);
            return false;
        }

        // Load template loader
        require_once ORGANIZATION_CORE_PLUGIN_DIR . 'includes/email/class-email-template-loader.php';

        //   CHECK IF TEMPLATE EXISTS
        if (!OC_Email_Template_Loader::email_template_exists($template_name, $module_id)) {
            return false;
        }

        // RENDER TEMPLATE (complete HTML)
        $html_content = OC_Email_Template_Loader::render_email_template(
            $template_name,
            $module_id,
            $template_data
        );

        if (!$html_content) {
            error_log('[EMAIL] Failed to render template: ' . $template_name);
            return false;
        }

        //   SEND EMAIL
        try {
            // Set HTML content type
            $headers = array('Content-Type: text/html; charset=UTF-8');

            $result = wp_mail($to, $subject, $html_content, $headers);

            if ($result) {
                error_log('[EMAIL] Sent successfully to: ' . $to);
            } else {
                error_log('[EMAIL] Failed to send to: ' . $to);
            }

            return $result;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Send email asynchronously (non-blocking)
     * 
     * @param string $to Recipient email
     * @param string $subject Email subject
     * @param string $template_name Template file name
     * @param string $module_id Module ID
     * @param array $template_data Template data
     * @param array $context Optional logging context
     * @return bool True if queued
     */
    public static function send_async_with_template($to, $subject, $template_name, $module_id = '', $template_data = array(), $context = array())
    {
        // Load template loader first to validate
        require_once ORGANIZATION_CORE_PLUGIN_DIR . 'includes/email/class-email-template-loader.php';

        //   VALIDATE TEMPLATE EXISTS BEFORE QUEUING
        if (!OC_Email_Template_Loader::email_template_exists($template_name, $module_id)) {
            error_log('❌ [EMAIL ASYNC] Template not found: ' . $template_name . ' (Module: ' . $module_id . ')');
            return false;
        }

        // Build admin-ajax URL for background call
        $ajax_url = admin_url('admin-ajax.php');

        // Prepare POST data
        $body = array(
            'action' => 'org_core_send_email_async',
            'to' => $to,
            'subject' => $subject,
            'template_name' => $template_name,
            'module_id' => $module_id,
            'template_data' => wp_json_encode($template_data),
            'context' => wp_json_encode($context),
            'nonce' => wp_create_nonce('background_email_nonce')
        );

        // Make NON-BLOCKING HTTP request
        wp_remote_post($ajax_url, array(
            'timeout' => 0.01,
            'blocking' => false,
            'body' => $body,
            'cookies' => $_COOKIE
        ));

        error_log('📧 [EMAIL ASYNC] Queued for: ' . $to);
        return true;
    }

    /**
     * Background email handler (runs in separate process)
     */
    public static function handle_background_email()
    {
        // Verify nonce (use unified helper for AJAX checks)
        if (! oc_verify_ajax_nonce('background_email_nonce', 'nonce')) {
            wp_die('Security check failed');
        }

        $to = isset($_POST['to']) ? sanitize_email($_POST['to']) : '';
        $subject = isset($_POST['subject']) ? sanitize_text_field($_POST['subject']) : '';
        $template_name = isset($_POST['template_name']) ? sanitize_text_field($_POST['template_name']) : '';
        $module_id = isset($_POST['module_id']) ? sanitize_text_field($_POST['module_id']) : '';
        $template_data = isset($_POST['template_data']) ? json_decode(stripslashes($_POST['template_data']), true) : array();

        if (empty($to) || empty($subject) || empty($template_name) || empty($module_id)) {
            wp_die();
        }

        // Send email
        $result = self::send_with_template($to, $subject, $template_name, $module_id, $template_data);

        if ($result) {
            error_log('[EMAIL BACKGROUND] Sent to: ' . $to);
        } else {
            error_log('[EMAIL BACKGROUND] Failed to send to: ' . $to);
        }

        wp_die();
    }
}

// Register AJAX handler
add_action('wp_ajax_org_core_send_email_async', array('OC_Email_Handler', 'handle_background_email'));
add_action('wp_ajax_nopriv_org_core_send_email_async', array('OC_Email_Handler', 'handle_background_email'));
