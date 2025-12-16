<?php

/**
 * Fired during plugin deactivation
 *
 * @link       https://owlth.tech
 * @since      1.0.0
 *
 * @package    Organization_Core
 * @subpackage Organization_Core/includes
 */

/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @since      1.0.0
 * @package    Organization_Core
 * @subpackage Organization_Core/includes
 * @author     Owlth Tech <owlthtech@gmail.com>
 */
class OC_Deactivator {

	/**
	 * Get all module deactivator files.
	 *
	 * @return array Array of module deactivator paths and class names
	 */
	private static function get_module_deactivators() {
		$deactivators = array();
		$modules_dir = plugin_dir_path( dirname( __FILE__ ) ) . 'modules';

		// Scan modules directory
		$module_folders = array_filter( glob( $modules_dir . '/*' ), 'is_dir' );

		foreach ( $module_folders as $module_folder ) {
			$deactivator_file = $module_folder . '/deactivator.php';
			
			if ( file_exists( $deactivator_file ) ) {
				// Convert path to class name (e.g., bookings/deactivator.php -> Bookings_Deactivator)
				$module_name = basename( $module_folder );
				$class_name = str_replace( '-', '_', ucfirst( $module_name ) ) . '_Deactivator';
				
				$deactivators[] = array(
					'file' => $deactivator_file,
					'class' => $class_name
				);
			}
		}

		return $deactivators;
	}

	/**
	 * Execute deactivation logic for the plugin and all modules.
	 *
	 * Scans through all modules and executes their deactivation logic
	 * if a deactivator.php file exists and implements the expected class.
	 *
	 * @since    1.0.0
	 */
	public static function deactivate() {
		// Get all module deactivators
		$deactivators = self::get_module_deactivators();
		
		// Loop through and deactivate each module
		foreach ( $deactivators as $deactivator ) {
			if ( ! class_exists( $deactivator['class'] ) ) {
				require_once $deactivator['file'];
			}
			
			// Check if class exists after including file
			if ( class_exists( $deactivator['class'] ) && method_exists( $deactivator['class'], 'deactivate' ) ) {
				call_user_func( array( $deactivator['class'], 'deactivate' ) );
			}
		}
	}

}
