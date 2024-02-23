<?php

namespace WeDevs\Plugin;

class Api {
  public function __construct() {
    add_action('rest_api_init', [$this, 'register_rest_api']);
  }

  public function register_rest_api() {
    $addressbook = new Api\AddressbookApi();
    $addressbook->register_routes();
  }
}
