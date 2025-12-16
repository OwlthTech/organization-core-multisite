<?php

/**
 * Bookings Module Configuration
 *
 * @package    Organization_Core
 * @subpackage Organization_Core/modules/bookings
 */

// If this file is called directly, abort.
if (! defined('WPINC')) {
    die;
}

return array(
    'id' => 'user-sync',
    'name' => __('User Synchronization', 'organization-core'),
    'description' => __('Comprehensive user synchronization system across multisite networks', 'organization-core'),
    'version' => '1.0.0',
    'author' => 'OwlthTech',

    // Module behavior settings
    'default_enabled' => false,  // Not enabled by default
    'network_only' => false,     // Can be enabled per site
    'required' => false,         // Not required for core functionality

    // Module dependencies (other modules this module needs)
    'dependencies' => array(),

    // Features this module supports
    'supports' => array(
        'templates',     // Has overridable templates
        'ajax',         // Uses AJAX functionality
        'cpt',          // Registers custom post types
        'shortcodes'    // Provides shortcodes
    ),

    // Template directories
    'template_paths' => array(
        'admin' => 'templates/admin/',
        'public' => 'templates/public/',
    ),

    // Assets
    'assets' => array(
        'admin_css' => array('assets/css/bookings-admin.css'),
        'admin_js' => array('assets/js/bookings-admin.js'),
        'public_css' => array('assets/css/bookings-public.css'),
        'public_js' => array('assets/js/bookings-public.js'),
    ),

    // Main module class file
    'class' => 'class-bookings.php'
);
