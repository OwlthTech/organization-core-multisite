<?php

/**
 * Shareables Module Configuration
 *
 * @package    Organization_Core
 * @subpackage Organization_Core/modules/shareables
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

return array(
    'id' => 'shareables',
    'name' => __('Shareables Management', 'organization-core'),
    'description' => __('Create and manage shareable items with unique URLs.', 'organization-core'),
    'version' => '1.0.0',
    'author' => 'OwlthTech',

    // Module behavior settings
    'default_enabled' => true,
    'network_only' => false,
    'required' => false,

    // Module dependencies (other modules this module needs)
    'dependencies' => array(),

    // Features this module supports
    'supports' => array(
        'templates',
        'ajax',
    ),

    // Template directories
    'template_paths' => array(
        'admin' => 'templates/admin/',
        'public' => 'templates/public/',
    ),

    // Assets
    'assets' => array(
        'admin_js' => array('assets/admin/js/shareables-admin.js'),
        'admin_css' => array('assets/admin/css/shareables-admin.css'),
        'public_js' => array('assets/public/js/shareables.js'),
        'public_css' => array('assets/public/css/shareables.css'),
    ),

    // Main module class file
    'class' => 'class-shareables.php'
);
