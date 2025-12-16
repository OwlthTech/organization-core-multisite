<?php

/**
 * Quotes AJAX Handlers
 * ✅ UPDATED: Removed status methods, clean implementation
 */

if (!defined('WPINC')) {
	die;
}

class OC_Quotes_Ajax
{
	private $module_id;

	public function __construct($module_id)
	{
		$this->module_id = $module_id;
	}

	/**
	 * ✅ Initialize AJAX handlers
	 */
	public function init()
	{
		// Frontend: Submit quote
		add_action('wp_ajax_handle_quote_submission', array($this, 'handle_quote_submission'));
		add_action('wp_ajax_nopriv_handle_quote_submission', array($this, 'handle_quote_submission'));

		// Admin: Get quote details
		add_action('wp_ajax_get_quote_details', array($this, 'get_quote_details'));

		// Admin: Delete quote
		add_action('wp_ajax_delete_quote', array($this, 'delete_quote'));
	}

	/**
	 * ✅ Handle quote form submission
	 */
	public function handle_quote_submission()
	{
		// Verify nonce
		check_ajax_referer('request_quote_action', 'request_quote_nonce');

		// Prepare quote data
		$form_data = array(
			'user_id'               => get_current_user_id(),
			'educator_name'         => sanitize_text_field($_POST['educator_name'] ?? ''),
			'school_name'           => sanitize_text_field($_POST['school_name'] ?? ''),
			'school_address'        => sanitize_textarea_field($_POST['school_address'] ?? ''),
			'position'              => sanitize_text_field($_POST['position'] ?? ''),
			'email'                 => sanitize_email($_POST['email'] ?? ''),
			'school_phone'          => sanitize_text_field($_POST['school_phone'] ?? ''),
			'cell_phone'            => sanitize_text_field($_POST['cell_phone'] ?? ''),
			'best_time_to_reach'    => sanitize_text_field($_POST['best_time_to_reach'] ?? ''),
			'destination_id'        => intval($_POST['destination_id'] ?? 0),
			'destination_name'      => sanitize_text_field($_POST['destination_name'] ?? ''),
			'destination_slug'      => sanitize_text_field($_POST['destination_slug'] ?? ''),
			'hear_about_us'         => sanitize_text_field($_POST['hear_about_us'] ?? ''),
			'meal_quote'            => isset($_POST['meal_quote']) && $_POST['meal_quote'] ? 1 : 0,
			'transportation_quote'  => isset($_POST['transportation_quote']) && $_POST['transportation_quote'] ? 1 : 0,
			'special_requests'      => sanitize_textarea_field($_POST['special_requests'] ?? ''),
		);

		// ✅ Validate required fields
		$validation_errors = $this->validate_quote_data($form_data);
		if (!empty($validation_errors)) {
			wp_send_json_error(array('message' => $validation_errors[0]), 400);
		}

		// Load CRUD
		require_once plugin_dir_path(__FILE__) . 'crud.php';

		// Insert quote using CRUD
		$quote_id = OC_Quotes_CRUD::insert_quote($form_data);

		if ($quote_id) {
			// Hook for plugins/extensions
			do_action('organization_core_quote_submitted', $quote_id, $form_data);

			wp_send_json_success(array(
				'message'  => 'Thank you! Your quote request has been submitted successfully. Our team will contact you within 24 hours.',
				'quote_id' => $quote_id
			));
		} else {
			wp_send_json_error(array(
				'message' => 'Failed to submit quote request. Please try again.'
			), 500);
		}
	}

	/**
	 * ✅ Validate quote data
	 */
	private function validate_quote_data($data)
	{
		$errors = array();

		if (empty($data['educator_name'])) {
			$errors[] = 'Educator name is required.';
		}

		if (empty($data['school_name'])) {
			$errors[] = 'School name is required.';
		}

		if (empty($data['school_address'])) {
			$errors[] = 'School address is required.';
		}

		if (empty($data['position'])) {
			$errors[] = 'Position is required.';
		}

		if (empty($data['email'])) {
			$errors[] = 'Email is required.';
		} elseif (!is_email($data['email'])) {
			$errors[] = 'Please enter a valid email address.';
		}

		if (empty($data['destination_name'])) {
			$errors[] = 'Please select a destination.';
		}

		return $errors;
	}

	/**
	 * ✅ Get quote details (Admin/Frontend modal)
	 */
	public function get_quote_details()
	{
		// Verify nonce first to protect the request
		check_ajax_referer('quotes_admin_nonce', 'nonce');

		if (!isset($_POST['quote_id'])) {
			wp_send_json_error(array('message' => 'Missing parameters'), 400);
		}

		$quote_id = intval($_POST['quote_id']);

		// Load CRUD
		require_once plugin_dir_path(__FILE__) . 'crud.php';

		// Get quote using CRUD
		$quote = OC_Quotes_CRUD::get_quote($quote_id);

		if (!$quote) {
			wp_send_json_error(array('message' => 'Quote not found'), 404);
		}

		// Check authorization - allow if admin or quote owner
		$current_user_id = get_current_user_id();
		$is_admin = current_user_can('manage_options');
		$is_owner = intval($quote->user_id) === intval($current_user_id);

		if (!$is_admin && !$is_owner) {
			wp_send_json_error(array('message' => 'Unauthorized'), 403);
		}

		wp_send_json_success(array('quote' => $quote));
	}

	/**
	 * ✅ Delete quote (Admin only)
	 */
	public function delete_quote()
	{
		// Verify nonce first to protect the request
		check_ajax_referer('quotes_admin_nonce', 'nonce');

		if (!isset($_POST['quote_id'])) {
			wp_send_json_error(array('message' => 'Missing parameters'), 400);
		}

		if (!current_user_can('manage_options')) {
			wp_send_json_error(array('message' => 'Unauthorized'), 403);
		}

		$quote_id = intval($_POST['quote_id']);

		if (!$quote_id) {
			wp_send_json_error(array('message' => 'Invalid quote ID'), 400);
		}

		// Load CRUD
		require_once plugin_dir_path(__FILE__) . 'crud.php';

		// Delete quote using CRUD
		$result = OC_Quotes_CRUD::delete_quote($quote_id);

		if ($result) {
			wp_send_json_success(array(
				'message' => 'Quote deleted successfully.'
			));
		} else {
			wp_send_json_error(array(
				'message' => 'Failed to delete quote.'
			), 500);
		}
	}
}
