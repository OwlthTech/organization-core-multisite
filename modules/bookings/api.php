<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

require_once __DIR__ . '/crud.php';

class Bookings_REST_Controller extends WP_REST_Controller
{

    protected $namespace;
    protected $rest_base;

    public function __construct()
    {
        $this->namespace = 'organization-core/v1';
        $this->rest_base = 'bookings';
    }

    public function register_routes()
    {
        register_rest_route($this->namespace, '/' . $this->rest_base, [
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [$this, 'get_items'],
                'permission_callback' => [$this, 'get_items_permissions_check'],
                'args' => $this->get_collection_params(),
            ],
            [
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => [$this, 'create_item'],
                'permission_callback' => [$this, 'create_item_permissions_check'],
                'args' => $this->get_endpoint_args_for_item_schema(WP_REST_Server::CREATABLE),
            ],
            'schema' => [$this, 'get_public_item_schema'],
        ]);

        register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<id>[\d]+)', [
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [$this, 'get_item'],
                'permission_callback' => [$this, 'get_item_permissions_check'],
                'args' => [
                    'context' => $this->get_context_param(['default' => 'view']),
                ],
            ],
            [
                'methods' => WP_REST_Server::EDITABLE,
                'callback' => [$this, 'update_item'],
                'permission_callback' => [$this, 'update_item_permissions_check'],
                'args' => $this->get_endpoint_args_for_item_schema(WP_REST_Server::EDITABLE),
            ],
            [
                'methods' => WP_REST_Server::DELETABLE,
                'callback' => [$this, 'delete_item'],
                'permission_callback' => [$this, 'delete_item_permissions_check'],
                'args' => [
                    'force' => [
                        'type' => 'boolean',
                        'default' => false,
                        'description' => 'Whether to bypass trash and force deletion.',
                    ],
                ],
            ],
            'schema' => [$this, 'get_public_item_schema'],
        ]);
    }

    public function get_items($request)
    {
        $blog_id = get_current_blog_id();
        $args = [];
        $params = $this->get_collection_params();
        foreach ($params as $key => $value) {
            if (isset($request[$key])) {
                $args[$key] = $request[$key];
            }
        }

        $bookings = OC_Bookings_CRUD::get_bookings($blog_id, $args);
        $data = [];
        foreach ($bookings as $booking) {
            $item = $this->prepare_item_for_response($booking, $request);
            $data[] = $this->prepare_response_for_collection($item);
        }

        return new WP_REST_Response($data, 200);
    }

    public function get_item($request)
    {
        $id = (int) $request['id'];
        $blog_id = get_current_blog_id();
        $booking = OC_Bookings_CRUD::get_booking($id, $blog_id);

        if (empty($booking)) {
            return new WP_Error('rest_booking_not_found', 'Booking not found.', ['status' => 404]);
        }

        $data = $this->prepare_item_for_response($booking, $request);
        return new WP_REST_Response($data, 200);
    }

    public function create_item($request)
    {
        $blog_id = get_current_blog_id();
        $booking_data = $request->get_param('booking_data');

        if (empty($booking_data)) {
            return new WP_Error('rest_booking_data_required', 'Booking data is required.', ['status' => 400]);
        }

        $booking_id = OC_Bookings_CRUD::create_booking($blog_id, $booking_data);

        if (!$booking_id) {
            return new WP_Error('rest_cannot_create', 'Failed to create booking.', ['status' => 500]);
        }

        $booking = get_booking($booking_id, $blog_id);
        $response = $this->prepare_item_for_response($booking, $request);

        return new WP_REST_Response($response, 201);
    }

    public function update_item($request)
    {
        $id = (int) $request['id'];
        $blog_id = get_current_blog_id();

        $booking_data = $request->get_param('booking_data');
        $status = $request->get_param('status');

        $result = OC_Bookings_CRUD::update_booking($id, $blog_id, $booking_data, $status);

        if (!$result) {
            return new WP_Error('rest_cannot_update', 'Failed to update booking or no changes made.', ['status' => 500]);
        }

        $booking = get_booking($id, $blog_id);
        $response = $this->prepare_item_for_response($booking, $request);

        return new WP_REST_Response($response, 200);
    }

    public function delete_item($request)
    {
        $id = (int) $request['id'];
        $blog_id = get_current_blog_id();

        $result = OC_Bookings_CRUD::delete_booking($id, $blog_id);

        if (!$result) {
            return new WP_Error('rest_cannot_delete', 'Failed to delete booking.', ['status' => 500]);
        }

        return new WP_REST_Response(['deleted' => true, 'id' => $id], 200);
    }

    public function prepare_item_for_response($item, $request)
    {
        $data = [];
        $fields = $this->get_fields_for_response($request);

        if (in_array('id', $fields, true)) {
            $data['id'] = (int) $item['id'];
        }
        if (in_array('blog_id', $fields, true)) {
            $data['blog_id'] = (int) $item['blog_id'];
        }
        if (in_array('booking_data', $fields, true)) {
            $data['booking_data'] = $item['booking_data'];
        }
        if (in_array('status', $fields, true)) {
            $data['status'] = $item['status'];
        }
        if (in_array('created_at', $fields, true)) {
            $data['created_at'] = $item['created_at'];
        }
        if (in_array('modified_at', $fields, true)) {
            $data['modified_at'] = $item['modified_at'];
        }

        $context = !empty($request['context']) ? $request['context'] : 'view';
        $data = $this->add_additional_fields_to_object($data, $request);
        $data = $this->filter_response_by_context($data, $context);

        return rest_ensure_response($data);
    }

    public function get_items_permissions_check($request)
    {
        return current_user_can('read');
    }

    public function get_item_permissions_check($request)
    {
        return $this->get_items_permissions_check($request);
    }

    public function create_item_permissions_check($request)
    {
        return current_user_can('edit_posts');
    }

    public function update_item_permissions_check($request)
    {
        return $this->create_item_permissions_check($request);
    }

    public function delete_item_permissions_check($request)
    {
        return current_user_can('delete_posts');
    }

    public function get_collection_params()
    {
        return [
            'context' => $this->get_context_param(['default' => 'view']),
            'page' => [
                'description' => 'Current page of the collection.',
                'type' => 'integer',
                'default' => 1,
                'sanitize_callback' => 'absint',
                'validate_callback' => 'rest_validate_request_arg',
                'minimum' => 1,
            ],
            'per_page' => [
                'description' => 'Maximum number of items to be returned in result set.',
                'type' => 'integer',
                'default' => 10,
                'sanitize_callback' => 'absint',
                'validate_callback' => 'rest_validate_request_arg',
                'minimum' => 1,
                'maximum' => 100,
            ],
            'status' => [
                'description' => 'Filter by booking status.',
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
            ],
        ];
    }

    public function get_item_schema()
    {
        if ($this->schema) {
            return $this->add_additional_fields_schema($this->schema);
        }

        $schema = [
            '$schema' => 'http://json-schema.org/draft-04/schema#',
            'title' => $this->rest_base,
            'type' => 'object',
            'properties' => [
                'id' => [
                    'description' => 'Unique identifier for the object.',
                    'type' => 'integer',
                    'context' => ['view', 'edit', 'embed'],
                    'readonly' => true,
                ],
                'blog_id' => [
                    'description' => 'The blog ID for the booking.',
                    'type' => 'integer',
                    'context' => ['view', 'edit', 'embed'],
                ],
                'booking_data' => [
                    'description' => 'Booking data in JSON format.',
                    'type' => 'object',
                    'context' => ['view', 'edit'],
                    'properties' => [],
                ],
                'status' => [
                    'description' => 'Booking status.',
                    'type' => 'string',
                    'context' => ['view', 'edit'],
                ],
                'created_at' => [
                    'description' => 'The date the object was created.',
                    'type' => 'string',
                    'format' => 'date-time',
                    'context' => ['view', 'edit'],
                    'readonly' => true,
                ],
                'modified_at' => [
                    'description' => 'The date the object was last modified.',
                    'type' => 'string',
                    'format' => 'date-time',
                    'context' => ['view', 'edit'],
                    'readonly' => true,
                ],
            ],
        ];

        $this->schema = $schema;

        return $this->add_additional_fields_schema($this->schema);
    }
}

add_action('rest_api_init', function () {
    $controller = new Bookings_REST_Controller();
    $controller->register_routes();
});
