<?php

namespace WeDevs\Plugin\Api;

use WP_REST_Controller;
use WP_REST_Server;
use WP_Error;
use WP_REST_Response;
use WeDevs\Plugin\App\Addressbook;


class AddressbookApi extends WP_REST_Controller {

  public function __construct() {
    $this->namespace = 'wedevs/v1';
    $this->rest_base = 'addressbook';
  }

  public function register_routes() {
    register_rest_route($this->namespace, '/' . $this->rest_base, [
      [
        'methods' => WP_REST_Server::READABLE,
        'callback' => [$this, 'get_items'],
        'permission_callback' => [$this, 'permissions_check'],
        'args' => $this->get_collection_params()
      ],
      [
        'methods' => WP_REST_Server::CREATABLE,
        'callback' => [$this, 'create_item'],
        'permission_callback' => [$this, 'permissions_check'],

      ],
      'schema' => [$this, 'get_item_schema']
    ]);

    register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<id>[\d]+)', [
      [
        'methods' => WP_REST_Server::READABLE,
        'callback' => [$this, 'get_item'],
        'permission_callback' => [$this, 'permissions_check'],
        'args' => [
          'context' => [
            'default' => 'view'
          ]
        ]
      ],
      [
        'methods' => WP_REST_Server::EDITABLE,
        'callback' => [$this, 'update_item'],
        'permission_callback' => [$this, 'permissions_check'],
        'args' => $this->get_endpoint_args_for_item_schema(WP_REST_Server::EDITABLE)
      ],
      [
        'methods' => WP_REST_Server::DELETABLE,
        'callback' => [$this, 'delete_item'],
        'permission_callback' => [$this, 'permissions_check']
      ],
      'schema' => [$this, 'get_item_schema']
    ]);
  }

  public function get_items($request) {
    $args = [];
    $params = $this->get_collection_params();

    foreach ($params as $key => $value) {
      if (isset($request[$key])) {
        $args[$key] = $request[$key];
      }
    }

    $args['number'] = $args['per_page'];
    $args['offset'] = ($args['page'] - 1) * $args['per_page'];

    unset($args['per_page']);
    unset($args['page']);

    $data = [];

    $items = (new Addressbook())->get_all($args);

    foreach ($items as $item) {
      $response = $this->prepare_item_for_response($item, $request);
      $data[] = $this->prepare_response_for_collection($response);
    }

    return $data;
  }

  public function prepare_item_for_response($item, $request) {
    $data = [];
    $fields = $this->get_fields_for_response($request);

    if (in_array('id', $fields, true)) {
      $data['id'] = (int) $item->id;
    }

    if (in_array('name', $fields, true)) {
      $data['name'] = $item->name;
    }

    if (in_array('email', $fields, true)) {
      $data['email'] = $item->email;
    }

    if (in_array('phone', $fields, true)) {
      $data['phone'] = $item->phone;
    }

    $context = !empty($request['context']) ? $request['context'] : 'view';
    $data = $this->filter_response_by_context($data, $context);

    return rest_ensure_response($data);
  }

  public function create_item($request) {
    if (empty($request['name']) || empty($request['email']) || empty($request['phone'])) {
      return new WP_Error('rest_invalid_param', esc_html__('Name, email, and phone number are required.', 'wedevs'), ['status' => 400]);
    }

    $data = [
      'name' => $request['name'],
      'email' => $request['email'],
      'phone' => $request['phone']
    ];

    $id = (new Addressbook())->create($data);
    $item = (new Addressbook())->get($id);

    $response = rest_ensure_response($item);
    $response->set_status(201);
    return $response;
  }



  public function get_item($request) {
    $item = (new Addressbook())->get($request['id']);
    if (!$item) {
      return new WP_Error('rest_not_found', esc_html__('address not found.', 'wedevs'), ['status' => 404]);
    }
    $response = rest_ensure_response($item);
    return $response;
  }


  public function update_item($request) {
    if (empty($request['name']) || empty($request['email']) || empty($request['phone'])) {
      return new WP_Error('rest_invalid_param', esc_html__('Name, email, and phone number are required.', 'wedevs'), ['status' => 400]);
    }

    $data = [
      'name' => $request['name'],
      'email' => $request['email'],
      'phone' => $request['phone']
    ];

    (new Addressbook())->update($request['id'], $data);
    $item = (new Addressbook())->get($request['id']);

    $response = rest_ensure_response($item);
    return $response;
  }

  public function delete_item($request) {
    $result = (new Addressbook())->delete($request['id']);
    if ($result === 0) {
      return new WP_Error('rest_not_found', esc_html__('address not found.', 'wedevs'), ['status' => 404]);
    }

    $data = [
      'id' => (int) $request['id'],
      'deleted' => true

    ];

    $response = rest_ensure_response($data);
    $response->set_status(202);
    return $response;
  }

  public function permissions_check($request) {
    if (!current_user_can('manage_options')) {
      return new WP_Error('rest_forbidden', esc_html__('You cannot update the address book.', 'wedevs'), ['status' => 403]);
    }
    return true;
  }

  public function get_item_schema() {

    if ($this->schema) {
      return $this->add_additional_fields_schema($this->schema);
    }

    $schema = [
      '$schema' => 'http://json-schema.org/draft-04/schema#',
      'title' => 'addressbook',
      'type' => 'object',
      'properties' => [
        'id' => [
          'description' => 'Unique identifier for the person',
          'type' => 'integer',
          'context' => ['view', 'edit', 'embed'],
          'readonly' => true
        ],
        'name' => [
          'description' => 'Name of the person',
          'type' => 'string',
          'context' => ['view', 'edit', 'embed'],
          'required' => true,
          'arg_options' => [
            'sanitize_callback' => 'sanitize_text_field'
          ]
        ],
        'email' => [
          'description' => 'Email of the person',
          'type' => 'string',
          'context' => ['view', 'edit', 'embed'],
          'required' => true,
          'arg_options' => [
            'validate_callback' => function ($param, $request, $key) {
              return is_email($param);
            }
          ]
        ],
        'phone' => [
          'description' => 'Phone number of the person',
          'type' => 'string',
          'context' => ['view', 'edit', 'embed'],
          'required' => true,
          'arg_options' => [
            'validate_callback' => function ($param, $request, $key) {
              return preg_match('/^\+?\d{1,3}\s?\(?\d{3}\)?[\s.-]?\d{3}[\s.-]?\d{4}$/', $param);
            }
          ]
        ],

      ]
    ];

    $this->schema = $schema;
    return $this->add_additional_fields_schema($this->schema);
  }

  public function get_collection_params() {
    $query_params = parent::get_collection_params();
    unset($query_params['search']);
    return $query_params;
  }
}
