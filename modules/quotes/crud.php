<?php

/**
 * Quotes CRUD Operations
 */

if (!defined('WPINC')) {
	die;
}

class OC_Quotes_CRUD
{
	/**
	 * Get proper table name
	 */
	private static function get_table_name()
	{
		global $wpdb;
		return $wpdb->base_prefix . 'quotes';
	}
	public static function insert_quote($data)
	{
		global $wpdb;
		$table_name = self::get_table_name();

		$insert_data = array(
			'blog_id'               => get_current_blog_id(),
			'user_id'               => intval($data['user_id'] ?? 0),
			'educator_name'         => sanitize_text_field($data['educator_name'] ?? ''),
			'school_name'           => sanitize_text_field($data['school_name'] ?? ''),
			'school_address'        => sanitize_textarea_field($data['school_address'] ?? ''),
			'position'              => sanitize_text_field($data['position'] ?? ''),
			'email'                 => sanitize_email($data['email'] ?? ''),
			'school_phone'          => sanitize_text_field($data['school_phone'] ?? ''),
			'cell_phone'            => sanitize_text_field($data['cell_phone'] ?? ''),
			'best_time_to_reach'    => sanitize_text_field($data['best_time_to_reach'] ?? ''),
			'destination_id'        => intval($data['destination_id'] ?? 0),
			'destination_name'      => sanitize_text_field($data['destination_name'] ?? ''),
			'destination_slug'      => sanitize_text_field($data['destination_slug'] ?? ''),
			'hear_about_us'         => sanitize_text_field($data['hear_about_us'] ?? ''),
			'meal_quote'            => isset($data['meal_quote']) && $data['meal_quote'] ? 1 : 0,
			'transportation_quote'  => isset($data['transportation_quote']) && $data['transportation_quote'] ? 1 : 0,
			'special_requests'      => sanitize_textarea_field($data['special_requests'] ?? ''),
			'quote_data'            => wp_json_encode($data),
		);

		$format = array(
			'%d',
			'%d',
			'%s',
			'%s',
			'%s',
			'%s',
			'%s',
			'%s',
			'%s',
			'%s',
			'%d',
			'%s',
			'%s',
			'%s',
			'%d',
			'%d',
			'%s',
			'%s'
		);

		$result = $wpdb->insert($table_name, $insert_data, $format);

		if ($result) {
			return $wpdb->insert_id;
		}

		return false;
	}

	/**
	 * READ: Get quote by ID
	 */
	public static function get_quote($quote_id)
	{
		global $wpdb;
		$table_name = self::get_table_name();

		$result = $wpdb->get_row($wpdb->prepare(
			"SELECT * FROM $table_name WHERE id = %d AND blog_id = %d",
			$quote_id,
			get_current_blog_id()
		));

		return $result ? $result : null;
	}

	/**
	 * READ: Get all quotes
	 */
	public static function get_all_quotes()
	{
		global $wpdb;
		$table_name = self::get_table_name();

		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM $table_name WHERE blog_id = %d ORDER BY created_at DESC LIMIT 100",
				intval(get_current_blog_id())
			)
		);

		if (!is_array($results)) {
			$results = array();
		}

		return $results;
	}

	/**
	 * READ: Get quotes with filters
	 */
	public static function get_quotes($args = array())
	{
		global $wpdb;
		$table_name = self::get_table_name();

		$defaults = array(
			'blog_id'        => get_current_blog_id(),
			'user_id'        => '',
			'search'         => '',
			'destination_id' => '',
			'orderby'        => 'created_at',
			'order'          => 'DESC',
			'limit'          => -1,
			'offset'         => 0,
		);

		$args = wp_parse_args($args, $defaults);

		$query = "SELECT * FROM $table_name WHERE blog_id = %d";
		$prepare_args = array($args['blog_id']);

		if (!empty($args['user_id'])) {
			$query .= " AND user_id = %d";
			$prepare_args[] = intval($args['user_id']);
		}

		if (!empty($args['destination_id'])) {
			$query .= " AND destination_id = %d";
			$prepare_args[] = intval($args['destination_id']);
		}

		if (!empty($args['search'])) {
			$query .= " AND (educator_name LIKE %s OR email LIKE %s OR school_name LIKE %s)";
			$search_term = '%' . $wpdb->esc_like($args['search']) . '%';
			$prepare_args[] = $search_term;
			$prepare_args[] = $search_term;
			$prepare_args[] = $search_term;
		}

		$order = in_array(strtoupper($args['order']), array('ASC', 'DESC')) ? $args['order'] : 'DESC';
		$orderby = in_array($args['orderby'], array('id', 'created_at', 'educator_name')) ? $args['orderby'] : 'created_at';
		$query .= " ORDER BY $orderby $order";

		if ($args['limit'] > 0) {
			$query .= " LIMIT %d";
			$prepare_args[] = intval($args['limit']);

			if ($args['offset'] > 0) {
				$query .= " OFFSET %d";
				$prepare_args[] = intval($args['offset']);
			}
		}

		$results = $wpdb->get_results($wpdb->prepare($query, ...$prepare_args));

		if (!is_array($results)) {
			$results = array();
		}

		return $results;
	}

	/**
	 * READ: Get user's quotes
	 */
	public static function get_user_quotes($user_id)
	{
		return self::get_quotes(array(
			'user_id' => $user_id,
			'limit'   => -1,
			'orderby' => 'created_at',
			'order'   => 'DESC'
		));
	}

	/**
	 * DELETE: Delete a quote
	 */
	public static function delete_quote($quote_id)
	{
		global $wpdb;
		$table_name = self::get_table_name();

		$result = $wpdb->delete(
			$table_name,
			array(
				'id'      => intval($quote_id),
				'blog_id' => get_current_blog_id()
			),
			array('%d', '%d')
		);

		return $result !== false;
	}

	/**
	 * COUNT: Get count of quotes
	 */
	public static function get_quote_count($args = array())
	{
		global $wpdb;
		$table_name = self::get_table_name();

		$defaults = array(
			'search' => '',
		);

		$args = wp_parse_args($args, $defaults);

		$query = "SELECT COUNT(*) FROM $table_name WHERE blog_id = %d";
		$prepare_args = array(get_current_blog_id());

		if (!empty($args['search'])) {
			$query .= " AND (educator_name LIKE %s OR email LIKE %s OR school_name LIKE %s)";
			$search_term = '%' . $wpdb->esc_like($args['search']) . '%';
			$prepare_args[] = $search_term;
			$prepare_args[] = $search_term;
			$prepare_args[] = $search_term;
		}

		$count = $wpdb->get_var($wpdb->prepare($query, ...$prepare_args));

		return intval($count ?? 0);
	}
}
