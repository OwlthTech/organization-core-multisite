<?php

/**
 * Authentication Module Configuration
 *
 * @package    Organization_Core
 * @subpackage Organization_Core/modules/authentication
 */

// If this file is called directly, abort.
if (! defined('WPINC')) {
    die;
}

return array(
    'id'                => 'authentication',
    'name'              => __('Authentication System', 'organization-core'),
    'description'       => __('User authentication, registration, and password management', 'organization-core'),
    'version'           => '1.0.0',
    'author'            => 'Your Organization',

    // Module behavior settings
    'default_enabled'   => true,  // Enabled by default
    'network_only'      => false, // Can be enabled per site
    'required'          => true,  // Required for core functionality

    // Module dependencies (other modules this module needs)
    'dependencies'      => array(),

    // Features this module supports
    'supports'          => array(
        'templates',     // Has overridable templates
        'ajax',         // Uses AJAX functionality
        'shortcodes'    // Provides shortcodes
    ),

    // Template directories
    'template_paths'    => array(
        'public' => 'public/templates/',
    ),

    // Assets
    'assets'            => array(
        'public_css'  => array('assets/css/authentication.css'),
        'public_js'   => array('assets/js/authentication.js'),
    ),

    // Main module class file
    'class'             => 'class-authentication.php'
);
