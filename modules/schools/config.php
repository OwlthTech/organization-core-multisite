<?php

/**
 * Schools Module Configuration
 *
 * @package    Organization_Core
 * @subpackage Organization_Core/modules/schools
 */

// If this file is called directly, abort.
if (! defined('WPINC')) {
    die;
}

return array(
    'id'          => 'schools',
    'name'        => __('Schools Management', 'organization-core'),
    'description' => __('Comprehensive schools management system for multisite networks. Allows users to register and manage multiple school profiles with detailed administrative and principal information.', 'organization-core'),
    'version'     => '1.0.0',
    'author'      => 'Your Organization',

    // Module behavior settings
    'default_enabled' => false,  // Not enabled by default
    'network_only'    => false,  // Can be enabled per site
    'required'        => false,  // Not required for core functionality

    // Module dependencies (other modules this module needs)
    'dependencies' => array(),

    // Features this module supports
    'supports' => array(
        'templates',     // Has overridable templates
        'ajax',          // Uses AJAX functionality
        'cpt',           // Registers custom post types
        'shortcodes'     // Provides shortcodes
    ),

    // Template directories
    'template_paths' => array(
        'admin'  => 'templates/admin/',
        'public' => 'templates/public/',
    ),

    // Assets
    'assets' => array(
        'admin_css'  => array('assets/css/schools-admin.css'),
        'admin_js'   => array('assets/js/schools-admin.js'),
        'public_css' => array('assets/css/schools-public.css'),
        'public_js'  => array('assets/js/schools-public.js'),
    ),

    // Main module class file
    'class' => 'class-schools.php'
);
