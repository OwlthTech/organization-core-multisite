<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://owlth.tech
 * @since      1.0.0
 *
 * @package    Organization_Core
 * @subpackage Organization_Core/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Organization_Core
 * @subpackage Organization_Core/includes
 * @author     Owlth Tech <owlthtech@gmail.com>
 */
class Organization_Core
{

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      OC_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	protected $module_manager;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct()
	{
		if (defined('ORGANIZATION_CORE_VERSION')) {
			$this->version = ORGANIZATION_CORE_VERSION;
		} else {
			$this->version = '1.0.0';
		}
		$this->plugin_name = 'organization-core';

		$this->load_dependencies();
		$this->set_locale();
		$this->load_modules();
		$this->define_admin_hooks();
		$this->define_public_hooks();
	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - OC_Loader. Orchestrates the hooks of the plugin.
	 * - OC_i18n. Defines internationalization functionality.
	 * - OC_Admin. Defines all hooks for the admin area.
	 * - OC_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies()
	{

		// Core classes
		require_once ORGANIZATION_CORE_PLUGIN_DIR . 'includes/class-organization-core-loader.php';
		require_once ORGANIZATION_CORE_PLUGIN_DIR . 'includes/class-organization-core-i18n.php';
		require_once ORGANIZATION_CORE_PLUGIN_DIR . 'includes/class-template-loader.php';

		// NEW: Sync module
		require_once ORGANIZATION_CORE_PLUGIN_DIR . 'includes/sync/class-user-sync-handler.php';
		require_once ORGANIZATION_CORE_PLUGIN_DIR . 'includes/sync/class-user-meta-sync.php';
		require_once ORGANIZATION_CORE_PLUGIN_DIR . 'includes/sync/class-user-sync-manager.php';
		require_once ORGANIZATION_CORE_PLUGIN_DIR . 'includes/sync/class-user-sync-hooks.php';

		// NEW: Utilities
		require_once ORGANIZATION_CORE_PLUGIN_DIR . 'includes/utilities/class-access-control.php';

		// NEW: Network admin
		if (is_multisite()) {
			require_once ORGANIZATION_CORE_PLUGIN_DIR . 'admin/class-network-user-sync-page.php';
		}

		// Admin classes
		require_once ORGANIZATION_CORE_PLUGIN_DIR . 'admin/class-organization-core-admin.php';

		// Helper functions
		require_once ORGANIZATION_CORE_PLUGIN_DIR . 'core/helpers.php';

		// Network admin (only for multisite)
		if (is_multisite()) {
			require_once ORGANIZATION_CORE_PLUGIN_DIR . 'includes/class-module-registry.php';
			require_once ORGANIZATION_CORE_PLUGIN_DIR . 'admin/class-network-admin.php';
		}

		// Public-facing functionality
		require_once ORGANIZATION_CORE_PLUGIN_DIR . 'public/class-organization-core-public.php';

		$this->loader = new OC_Loader();
	}

	private function load_modules()
	{
		// Load abstract module class first
		require_once ORGANIZATION_CORE_PLUGIN_DIR . 'modules/abstract-module.php';
		require_once ORGANIZATION_CORE_PLUGIN_DIR . 'core/class-module-manager.php';
		require_once ORGANIZATION_CORE_PLUGIN_DIR . 'core/class-module-validator.php';
		require_once ORGANIZATION_CORE_PLUGIN_DIR . 'core/class-network-settings.php';

		// Auto-load all module files
		$this->auto_load_modules();

		// Register all modules
		do_action('organization_core_register_modules');

		// Load and initialize modules
		$module_manager = new OC_Module_Manager();
		$this->loader->add_action('init', $module_manager, 'load_modules', 1);
		$this->loader->add_action('init', $module_manager, 'init_modules', 2);
	}

	/**
	 * Auto-load all module class files
	 */
	private function auto_load_modules()
	{
		$modules_dir = ORGANIZATION_CORE_PLUGIN_DIR . 'modules/';

		// Check if modules directory exists
		if (!is_dir($modules_dir)) {
			return;
		}

		// Scan for module directories
		$module_folders = glob($modules_dir . '*', GLOB_ONLYDIR);

		if (empty($module_folders)) {
			return;
		}

		// Load each module's class file
		foreach ($module_folders as $module_folder) {
			$module_name = basename($module_folder);

			// Skip hidden directories
			if ($module_name[0] === '.') {
				continue;
			}

			// Look for config.php first
			$config_file = $module_folder . '/config.php';

			if (!file_exists($config_file)) {
				continue;
			}

			// Get config to find the class file name
			$config = require $config_file;

			if (!isset($config['class']) || empty($config['class'])) {
				continue;
			}

			// Load the module class file
			$class_file = $module_folder . '/' . $config['class'];

			if (file_exists($class_file)) {
				require_once $class_file;
			}
		}
	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the OC_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale()
	{

		$plugin_i18n = new OC_i18n();

		$this->loader->add_action('plugins_loaded', $plugin_i18n, 'load_plugin_textdomain');
	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks()
	{
		$plugin_admin = new OC_Admin($this->get_plugin_name(), $this->get_version());
		$this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_styles');
		$this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts');

		// ✅ NEW: Initialize Access Control (Admin only)
		$access_control = new Access_Control();
		// Hooks are registered in constructor

		// Network admin hooks (multisite only)
		if (is_multisite()) {
			$network_admin = new OC_Network_Admin($this->get_plugin_name(), $this->get_version());

			$this->loader->add_action('network_admin_menu', $network_admin, 'add_network_menu');
			$this->loader->add_action('admin_init', $network_admin, 'handle_form_submissions');
			$this->loader->add_action('admin_notices', $network_admin, 'show_admin_notices');

			// ✅ NEW: Initialize User Sync Settings Page (Network Admin)
		}
	}


	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_public_hooks()
	{
		$plugin_public = new OC_Public($this->get_plugin_name(), $this->get_version());

		$this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_styles');
		$this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_scripts');

		// ✅ NEW: Initialize User Sync Hooks (User registration, profile updates)
		if (is_multisite()) {
			$sync_hooks = new User_Sync_Hooks();
			// Hooks are registered in constructor
		}
	}


	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run()
	{
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name()
	{
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    OC_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader()
	{
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version()
	{
		return $this->version;
	}
}
