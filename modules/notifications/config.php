<?php
/**
 * Notifications Module Configuration
 *
 * @package    Organization_Core
 * @subpackage Organization_Core/modules/notifications
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

return array(
    'id' => 'notifications',
    'name' => __('Notifications System', 'organization-core'),
    'description' => __('Centralized email notification system with Action Scheduler for reliable delivery', 'organization-core'),
    'version' => '2.0.0',
    'author' => 'OwlthTech',

    // Module behavior settings
    'default_enabled' => true,   // Enabled by default (core functionality)
    'network_only' => false,     // Can be enabled per site
    'required' => false,         // Not strictly required but recommended

    // Module dependencies (other modules this module needs)
    'dependencies' => array(),

    // Features this module supports
    'supports' => array(
        'templates',     // Has overridable email templates
        'ajax',          // May use AJAX for testing
        'scheduler'      // Uses Action Scheduler
    ),

    // Template directories
    'template_paths' => array(
        'emails' => 'templates/emails/',
    ),

    // Assets
    'assets' => array(
        // Future: admin CSS/JS for notification settings
    ),

    // Main module class file
    'class' => 'class-notifications.php'
);
