<?php

/**
 * Fired during plugin activation
 *
 * @link       https://owlth.tech
 * @since      1.0.0
 *
 * @package    Organization_Core
 * @subpackage Organization_Core/includes
 */

class OC_Activator
{

	/**
	 * Get all module activator files.
	 *
	 * @return array Array of module activator paths and class names
	 */
	private static function get_module_activators()
	{
		$activators = array();
		$modules_dir = plugin_dir_path(dirname(__FILE__)) . 'modules';

		// Scan modules directory
		$module_folders = array_filter(glob($modules_dir . '/*'), 'is_dir');

		foreach ($module_folders as $module_folder) {
			$activator_file = $module_folder . '/activator.php';

			if (file_exists($activator_file)) {
				// Convert path to class name
				// e.g., bookings/activator.php -> OC_Bookings_Activator
				$module_name = basename($module_folder);
				$class_name = 'OC_' . str_replace('-', '_', ucwords(str_replace('_', ' ', $module_name))) . '_Activator';

				$activators[] = array(
					'file' => $activator_file,
					'class' => $class_name,
					'module' => $module_name
				);
			}
		}

		return $activators;
	}

	/**
	 * Execute activation logic for the plugin and all modules.
	 *
	 * @since    1.0.0
	 */
	public static function activate()
	{

		// Get all module activators
		$activators = self::get_module_activators();

		// Loop through and activate each module
		foreach ($activators as $activator) {

			// Include the activator file
			if (! class_exists($activator['class'])) {
				require_once $activator['file'];
			}

			// Call activate method if it exists
			if (class_exists($activator['class'])) {
				if (method_exists($activator['class'], 'activate')) {
					call_user_func(array($activator['class'], 'activate'));
				} else {
					error_log('No activate() method in ' . $activator['class']);
				}
			} else {
				error_log('Class not found: ' . $activator['class']);
			}
		}

		// Flush all rewrite rules one final time
		flush_rewrite_rules(false);
	}
}
