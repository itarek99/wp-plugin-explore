<?php

/**
 * Plugin Name: WeDevs Plugin
 * Plugin URI: https://example.com/
 * Description: This is a starter template for creating a WordPress plugin.
 * Version: 1.0.0
 * Author: Tarekul Islam
 * Author URI: https://example.com/
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: wedevs
 * Domain Path: /languages
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
  exit;
}


require_once __DIR__ . '/vendor/autoload.php';

final class WeDevsPlugin {

  const VERSION = '1.0.0';

  private function __construct() {
    $this->define_constants();

    register_activation_hook(__FILE__, [$this, 'activate']);
    add_action('plugins_loaded', [$this, 'init_plugin']);
  }

  public static function init() {
    static $instance = false;

    if (!$instance) {
      $instance = new self();
    }
    return $instance;
  }

  public function define_constants() {
    define('WEDEVS_VERSION', self::VERSION);
    define('WEDEVS_FILE', __FILE__);
    define('WEDEVS_PATH', __DIR__);
    define('WEDEVS_URL', plugins_url('', WEDEVS_FILE));
    define('WEDEVS_ASSETS', WEDEVS_URL . '/assets');
  }

  public function init_plugin() {
    new WeDevs\Plugin\Api();
    if (is_admin()) {
      new WeDevs\Plugin\Admin();
    } else {
      new WeDevs\Plugin\Frontend();
    }
  }

  public function activate() {
    $installer = new WeDevs\Plugin\Installer();
    $installer->run();
  }
}


function wedevs_plugin() {
  return WeDevsPlugin::init();
}

wedevs_plugin();
