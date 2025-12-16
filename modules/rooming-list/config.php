<?php

/**
 * Rooming List Module Configuration
 *
 * @package    Organization_Core
 * @subpackage Organization_Core/modules/rooming-list
 */

if (!defined('WPINC')) {
    die;
}

return array(
    'id' => 'rooming-list',
    'name' => __('Rooming List Management', 'organization-core'),
    'description' => __('Manage room assignments for bookings', 'organization-core'),
    'version' => '1.0.0',
    'author' => 'OwlthTech',

    // Module behavior settings
    'default_enabled' => true,
    'network_only' => false,
    'required' => false,

    // Module dependencies
    'dependencies' => array(
        'bookings', // Needs booking data
        'hotels'    // Needs hotel limits
    ),

    // Features this module supports
    'supports' => array(
        'ajax',
        'templates'
    ),

    // Template directories
    'template_paths' => array(
        'admin' => 'templates/admin/',
    ),

    // Assets
    'assets' => array(),

    // Main module class file
    'class' => 'class-rooming-list.php'
);
