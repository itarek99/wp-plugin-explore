<?php

namespace WeDevs\Plugin\Admin;

class Menu {
  public function __construct() {
    add_action('admin_menu', [$this, 'admin_menu']);
  }

  public function admin_menu() {
    $parent_slug = 'wedevs_plugin';
    $capability = 'manage_options';

    add_menu_page(
      __('WeDevs Plugin', 'wedevs'),
      __('WeDevs', 'wedevs'),
      $capability,
      $parent_slug,
      [$this, 'address_book_submenu'],
      'dashicons-admin-generic',
      20
    );

    add_submenu_page(
      $parent_slug,
      __('Address Book', 'wedevs'),
      __('Address Book', 'wedevs'),
      $capability,
      $parent_slug,
      [$this, 'address_book_submenu']
    );

    add_submenu_page(
      $parent_slug,
      __('Settings', 'wedevs'),
      __('Settings', 'wedevs'),
      $capability,
      'wedevs_settings',
      [$this, 'settings_submenu']
    );
  }

  public function address_book_submenu() {
    $address_book = new Addressbook();
    $address_book->plugin_page();
  }

  public function settings_submenu() {
    echo '<div class="wrap">';
    echo '<h2>Settings</h2>';
    echo '</div>';
  }
}
