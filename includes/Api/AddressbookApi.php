<?php

namespace WeDevs\Plugin\Api;

use WP_REST_Controller;
use WP_REST_Server;

class AddressbookApi extends WP_REST_Controller {

  public function __construct() {
    $this->namespace = 'wedevs/v1';
    $this->rest_base = 'addressbook';
  }

  public function register_routes() {
    register_rest_route('wedevs/v1', '/addressbook', [
      [
        'methods' => WP_REST_Server::READABLE,
        'callback' => [$this, 'get_items'],
        'permission_callback' => [$this, 'permissions_check'],
      ],
      [
        'methods' => WP_REST_Server::CREATABLE,
        'callback' => [$this, 'create_item'],
        'permission_callback' => [$this, 'permissions_check'],
      ]
    ]);
  }

  public function get_items($request) {
    $items = [
      ['id' => 1, 'name' => 'John Doe'],
      ['id' => 2, 'name' => 'Jane Doe']
    ];

    return rest_ensure_response($items);
  }

  public function create_item($request) {
    $response = ['message' => 'Item created'];
    error_log(print_r($request->get_body(), true));
    return rest_ensure_response(json_decode($request->get_body()));
  }

  public function permissions_check() {
    if (!current_user_can('manage_options')) {
      return new \WP_Error('rest_forbidden', esc_html__('You cannot view the address book.', 'wedevs'), ['status' => 403]);
    }

    return true;
  }
}
