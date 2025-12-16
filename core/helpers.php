<?php

/**
 * Complete Helper Class for Asset Management
 * 
 * @package Organization_Core
 * @version 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
	exit;
}

/**
 * Verify AJAX nonce helper
 *
 * Provides a small wrapper for `check_ajax_referer()` to standardize AJAX nonce
 * verification across modules. This function will call WP's `check_ajax_referer`
 * which dies/returns on failure — keep behavior identical to WP.
 *
 * @param string $action Nonce action name
 * @param string $field  Field name in $_REQUEST (default: 'nonce')
 * @return void
 */
if (!function_exists('oc_verify_ajax_nonce')) {
	/**
	 * Verify AJAX nonce (non-dying variant)
	 *
	 * Calls WP's `check_ajax_referer()` with `$die = false` so callers can
	 * handle error responses (JSON, wp_die, etc.) consistently across modules.
	 *
	 * @param string $action Nonce action name
	 * @param string $field  Field name in $_REQUEST (default: 'nonce')
	 * @return bool|int False on failure, 1 or 2 on success (same as check_ajax_referer with $die=false)
	 */
	function oc_verify_ajax_nonce($action, $field = 'nonce')
	{
		if (!function_exists('check_ajax_referer')) {
			return false;
		}

		return check_ajax_referer($action, $field, false);
	}
}

class OC_Asset_Handler
{
	/**
	 * Image mapping with direct paths
	 * 
	 * @var array
	 */
	private static $image_map = array(
		'home-bg' => 'assets/images/home-bg.png',
		'logo'    => 'assets/images/logo.png',
		'hero-bg' => 'assets/images/home-bg.png',
		'default' => 'images/default.png',
	);

	/**
	 * Get theme image URL directly
	 * 
	 * @param string $image_name Image identifier
	 * @return string Full URL to image
	 */
	public static function get_theme_image($image_name)
	{
		// Validate image name
		if (!isset(self::$image_map[$image_name])) {
			return self::get_plugin_asset_url(self::$image_map['default']);
		}

		// Get theme directory URI
		$theme_dir = get_template_directory_uri();
		$child_theme_dir = get_stylesheet_directory_uri();

		// Get image path
		$image_path = self::clean_path(self::$image_map[$image_name]);

		// First check child theme
		$child_theme_path = "{$child_theme_dir}/{$image_path}";
		$child_theme_file_path = get_stylesheet_directory() . "/{$image_path}";

		if (file_exists($child_theme_file_path)) {
			return esc_url($child_theme_path);
		}

		// Then check parent theme
		$parent_theme_path = "{$theme_dir}/{$image_path}";
		$parent_theme_file_path = get_template_directory() . "/{$image_path}";

		if (file_exists($parent_theme_file_path)) {
			return esc_url($parent_theme_path);
		}

		// Finally fallback to plugin
		return self::get_plugin_asset_url(self::$image_map[$image_name]);
	}

	/**
	 * Get plugin asset URL (fallback)
	 * 
	 * @param string $asset_path Asset path
	 * @return string Plugin asset URL
	 */
	private static function get_plugin_asset_url($asset_path)
	{
		$path = self::clean_path($asset_path);
		return esc_url(ORGANIZATION_CORE_PLUGIN_URL . 'public/assets/' . $path);
	}

	/**
	 * Clean and normalize path
	 * 
	 * @param string $path Raw path
	 * @return string Cleaned path
	 */
	private static function clean_path($path)
	{
		return trim(str_replace(array('\\', '//'), '/', $path), '/');
	}

	/**
	 * Get asset exists check
	 * 
	 * @param string $asset_path Asset path
	 * @return bool Asset exists
	 */
	public static function asset_exists($asset_path)
	{
		$path = self::clean_path($asset_path);

		// Check child theme
		$child_path = get_stylesheet_directory() . '/' . $path;
		if (file_exists($child_path)) {
			return true;
		}

		// Check parent theme
		$parent_path = get_template_directory() . '/' . $path;
		if (file_exists($parent_path)) {
			return true;
		}

		// Check plugin
		$plugin_path = ORGANIZATION_CORE_PLUGIN_DIR . 'public/assets/' . $path;
		return file_exists($plugin_path);
	}
}
