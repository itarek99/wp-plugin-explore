<?php

namespace WeDevs\Plugin\Frontend;

class Shortcode {
  public function __construct() {
    add_shortcode('wedevs-shortcode', [$this, 'render_shortcode']);
  }

  public function render_shortcode($atts, $content = '') {
    return '<div class="wedevs">Hello From Shortcode</div>';
  }
}
