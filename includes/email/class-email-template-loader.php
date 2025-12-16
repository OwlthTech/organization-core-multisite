<?php

/**
 * Email Template Loader - Extends OC_Template_Loader
 * 
 * Handles email template loading with validation
 * Checks: Theme > Plugin before rendering
 * 
 * @package    Organization_Core
 * @subpackage Includes/Email
 */

if (!defined('ABSPATH')) {
    exit;
}

class OC_Email_Template_Loader
{
    /**
     * Locate email template
     * 
     * Priority:
     * 1. Child theme: themes/child/organization-core/{module}/emails/{template}.php
     * 2. Parent theme: themes/parent/organization-core/{module}/emails/{template}.php
     * 3. Plugin: modules/{module}/templates/emails/{template}.php
     * 
     * @param string $template_name Template file name (e.g., 'booking-confirmation.php')
     * @param string $module_id Module ID (e.g., 'bookings')
     * @return string|false Full template path or false if not found
     */
    public static function locate_email_template($template_name, $module_id = '')
    {
        if (empty($module_id) || empty($template_name)) {
            error_log('[EMAIL TEMPLATE LOADER] Missing module_id or template_name');
            return false;
        }

        $template = false;

        // PRIORITY 1 & 2: Check theme directory (child theme gets priority automatically)
        $template = self::check_theme_email_template($template_name, $module_id);

        // PRIORITY 3: Check plugin default
        if (!$template) {
            $template = self::check_plugin_email_template($template_name, $module_id);
        }

        // Log the result
        if ($template) {
            error_log('[EMAIL TEMPLATE LOADER] Found: ' . $template);
        } else {
            error_log('[EMAIL TEMPLATE LOADER] NOT FOUND: ' . $template_name . ' in module: ' . $module_id);
        }

        // Allow developers to filter template path
        return apply_filters(
            'organization_core_locate_email_template',
            $template,
            $template_name,
            $module_id
        );
    }

    /**
     * Check theme directory for email template override
     * 
     * @param string $template_name Template name
     * @param string $module_id Module ID
     * @return string|false Template path if found
     */
    private static function check_theme_email_template($template_name, $module_id)
    {
        $paths = array(
            'organization-core/' . $module_id . '/emails/' . $template_name,
            'organization-core/emails/' . $template_name,
        );

        return locate_template($paths);
    }

    /**
     * Check plugin default for email template
     * 
     * @param string $template_name Template name
     * @param string $module_id Module ID
     * @return string|false Template path if found
     */
    private static function check_plugin_email_template($template_name, $module_id)
    {
        $template_path = ORGANIZATION_CORE_PLUGIN_DIR . 'modules/' . $module_id . '/templates/emails/' . $template_name;

        if (file_exists($template_path)) {
            return $template_path;
        }

        return false;
    }

    /**
     * Load and render email template with data
     * 
     * @param string $template_name Template name
     * @param string $module_id Module ID
     * @param array $data Data to pass to template
     * @return string|false Rendered HTML or false if template not found
     */
    public static function render_email_template($template_name, $module_id = '', $data = array())
    {
        // Locate template
        $template = self::locate_email_template($template_name, $module_id);

        if (!$template) {
            error_log('[EMAIL TEMPLATE LOADER] Cannot render - template not found: ' . $template_name);
            return false;
        }

        // Start output buffering
        ob_start();

        try {
            // Extract data variables for template scope
            if ($data && is_array($data)) {
                extract($data);
            }

            // Hook before template loads
            do_action(
                'organization_core_before_email_template',
                $template_name,
                $template,
                $data,
                $module_id
            );

            // Load template
            include $template;

            // Hook after template loads
            do_action(
                'organization_core_after_email_template',
                $template_name,
                $template,
                $data,
                $module_id
            );

            // Get rendered content
            $content = ob_get_clean();

            return $content;
        } catch (Exception $e) {
            ob_end_clean();
            return false;
        }
    }

    /**
     * Check if email template exists
     * 
     * @param string $template_name Template name
     * @param string $module_id Module ID
     * @return bool True if exists
     */
    public static function email_template_exists($template_name, $module_id = '')
    {
        $template = self::locate_email_template($template_name, $module_id);
        return !empty($template);
    }

    /**
     * Get all available email templates for a module
     * 
     * @param string $module_id Module ID
     * @return array List of template names
     */
    public static function get_available_email_templates($module_id = '')
    {
        $templates = array();
        $path = ORGANIZATION_CORE_PLUGIN_DIR . 'modules/' . $module_id . '/templates/emails/';

        if (is_dir($path)) {
            $files = scandir($path);
            foreach ($files as $file) {
                if (substr($file, -4) === '.php') {
                    $templates[] = $file;
                }
            }
        }

        return $templates;
    }
}
