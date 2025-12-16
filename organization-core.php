<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://owlth.tech
 * @since             1.0.0
 * @package           Organization_Core
 *
 * @wordpress-plugin
 * Plugin Name:       Organization Core Latest
 * Plugin URI:        https://owlth.tech
 * Description:       Manage all features of an organization.
 * Version:           1.0.0
 * Author:            Owlth Tech
 * Author URI:        https://owlth.tech/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       organization-core
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if (! defined('WPINC')) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define('ORGANIZATION_CORE_VERSION', '1.0.0');
define('ORGANIZATION_CORE_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('ORGANIZATION_CORE_PLUGIN_URL', plugin_dir_url(__FILE__));
define('ORGANIZATION_CORE_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-organization-core-activator.php
 */
function activate_organization_core()
{
	require_once plugin_dir_path(__FILE__) . 'includes/class-organization-core-activator.php';
	OC_Activator::activate();
	flush_rewrite_rules();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-organization-core-deactivator.php
 */
function deactivate_organization_core()
{
	require_once plugin_dir_path(__FILE__) . 'includes/class-organization-core-deactivator.php';
	OC_Deactivator::deactivate();
	flush_rewrite_rules();
}

register_activation_hook(__FILE__, 'activate_organization_core');
register_deactivation_hook(__FILE__, 'deactivate_organization_core');

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path(__FILE__) . 'includes/class-organization-core.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */


function run_organization_core()
{
	$plugin = new Organization_Core();
	$plugin->run();
}
add_action('plugins_loaded', 'run_organization_core');



add_action( 'admin_post_run_module_activator', 'run_module_activator' );
function run_module_activator() {
    if ( ! isset( $_POST['run_module_activator_nonce'] ) || ! wp_verify_nonce( $_POST['run_module_activator_nonce'], 'run_module_activator_' . $_POST['module_id'] ) ) {
        wp_die( 'Invalid nonce specified', 'Error', array( 'response' => 403 ) );
    }

    if ( ! current_user_can( 'manage_network_plugins' ) ) {
        wp_die( 'You do not have permission to access this page', 'Error', array( 'response' => 403 ) );
    }

    $module_id = sanitize_text_field( $_POST['module_id'] );
    $module_path = ORGANIZATION_CORE_PLUGIN_DIR . 'modules/' . $module_id . '/activator.php';

    if ( file_exists( $module_path ) ) {
        require_once $module_path;
        $activator_class = 'OC_' . ucfirst( str_replace( '-', '_', $module_id ) ) . '_Activator';
        if ( class_exists( $activator_class ) && method_exists( $activator_class, 'activate' ) ) {
            call_user_func( array( $activator_class, 'activate' ) );
            add_settings_error( 'organization_core_messages', 'module_activated', __( 'Module activator run for ', 'organization-core' ) . $module_id, 'success' );
        }
    } else {
        add_settings_error( 'organization_core_messages', 'module_not_found', __( 'Module activator not found for ', 'organization-core' ) . $module_id, 'error' );
    }

    wp_redirect( network_admin_url( 'admin.php?page=organization-core-modules' ) );
    exit;
}
