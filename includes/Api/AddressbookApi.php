<?php

namespace WeDevs\Plugin\Api;

use WP_REST_Controller;
use WP_REST_Server;
use WP_Error;
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
        'args' => $this->get_endpoint_args_for_item_schema(WP_REST_Server::CREATABLE)

      ],
      'schema' => [$this, 'get_item_schema']
    ]);

    register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<id>[\d]+)', [
      [
        'methods' => WP_REST_Server::READABLE,
        'callback' => [$this, 'get_item'],
        'permission_callback' => [$this, 'permissions_check'],
        'args' => [
          'id' => [
            'description' => 'Unique identifier for the person.',
            'type' => 'integer',
            'validate_callback' => function ($param, $request, $key) {
              return is_numeric($param);
            }
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
        'permission_callback' => [$this, 'permissions_check'],
        'args' => [
          'id' => [
            'description' => 'Unique identifier for the person.',
            'type' => 'integer',
            'validate_callback' => function ($param, $request, $key) {
              return is_numeric($param);
            }
          ]
        ]
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
    $response = rest_ensure_response($data);
    $total = (new Addressbook())->get_total($args);
    $response->header('X-WP-Total', (int) $total);
    $response->header('X-WP-TotalPages', ceil($total / $args['number']));

    return $response;
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

    if (in_array('created_at', $fields, true)) {
      $data['created_at'] = mysql_to_rfc3339($item->created_at);
    }

    $context = !empty($request['context']) ? $request['context'] : 'view';
    $data = $this->filter_response_by_context($data, $context);

    $response = rest_ensure_response($data);
    $response->add_links($this->prepare_links($item));

    return $response;
  }

  protected function prepare_links($item) {
    $base = sprintf('%s/%s', $this->namespace, $this->rest_base);

    $links = [
      'self' => [
        'href' => rest_url(trailingslashit($base) . $item->id),
      ],
      'collection' => [
        'href' => rest_url($base),
      ],
    ];

    return $links;
  }

  public function create_item($request) {
    $data = $this->prepare_item_for_database($request);
    $id = (new Addressbook())->create($data);
    $item = (new Addressbook())->get($id);

    $response = $this->prepare_item_for_response($item, $request);
    $response->set_status(201);
    return $response;
  }

  public function get_item($request) {
    $data = (new Addressbook())->get($request['id']);
    if (!$data) {
      return new WP_Error('rest_not_found', esc_html__('address not found.', 'wedevs'), ['status' => 404]);
    }

    $item = $this->prepare_item_for_response($data, $request);
    $response = rest_ensure_response($item);
    return $response;
  }


  public function update_item($request) {
    $data = (new Addressbook())->get($request['id']);
    $item = $this->prepare_item_for_database($request);

    if (!$item) {
      return new WP_Error('rest_not_found', esc_html__('address not found.', 'wedevs'), ['status' => 404]);
    }

    $data_for_db = array_merge((array) $data, $item);

    (new Addressbook())->update($request['id'], $data_for_db);
    $item = (new Addressbook())->get($request['id']);

    $response = $this->prepare_item_for_response($item, $request);
    return $response;
  }

  protected function prepare_item_for_database($request) {
    $prepared = [];

    if (isset($request['name'])) {
      $prepared['name'] = $request['name'];
    }

    if (isset($request['email'])) {
      $prepared['email'] = $request['email'];
    }

    if (isset($request['phone'])) {
      $prepared['phone'] = $request['phone'];
    }

    return $prepared;
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
      return new WP_Error('rest_forbidden', esc_html__('Unauthorized Action', 'wedevs'), ['status' => 403]);
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
        'created_at' => [
          'description' => 'The date the person was created.',
          'type' => 'string',
          'format' => 'date-time',
          'context' => ['view', 'edit'],
          'readonly' => true
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
