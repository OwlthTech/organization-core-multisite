<?php

/**
 * Define the internationalization functionality
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @link       https://owlth.tech
 * @since      1.0.0
 *
 * @package    Organization_Core
 * @subpackage Organization_Core/includes
 */

/**
 * Define the internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @since      1.0.0
 * @package    Organization_Core
 * @subpackage Organization_Core/includes
 * @author     Owlth Tech <owlthtech@gmail.com>
 */
class OC_i18n {
    
    public function load_plugin_textdomain() {
        load_plugin_textdomain(
            'organization-core',
            false,
            dirname( ORGANIZATION_CORE_PLUGIN_BASENAME ) . '/languages/'
        );
    }
}
