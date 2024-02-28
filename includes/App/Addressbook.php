<?php

namespace WeDevs\Plugin\App;

class Addressbook {
  public function create($data) {
    global $wpdb;
    $table = $wpdb->prefix . 'addressbook';
    $format = ['%s', '%s', '%s'];
    $wpdb->insert($table, $data, $format);
    return $wpdb->insert_id;
  }

  public function get($id) {
    global $wpdb;
    $table = $wpdb->prefix . 'addressbook';
    $query = "SELECT * FROM $table WHERE id = %d";
    return $wpdb->get_row($wpdb->prepare($query, $id));
  }

  public function get_all($args) {
    global $wpdb;
    $table = $wpdb->prefix . 'addressbook';
    $defaults = [
      'number' => 10,
      'offset' => 0,
      'orderby' => 'id',
      'order' => 'ASC'
    ];
    $args = wp_parse_args($args, $defaults);
    $items = $wpdb->get_results("SELECT * FROM $table LIMIT $args[number] OFFSET $args[offset]");
    return $items;
  }

  public function update($id, $data) {
    global $wpdb;
    $table = $wpdb->prefix . 'addressbook';
    $wpdb->update($table, $data, ['id' => $id]);
  }

  public function delete($id) {
    global $wpdb;
    $table = $wpdb->prefix . 'addressbook';
    $result = $wpdb->delete($table, ['id' => $id]);
    return $result;
  }

  public function get_total() {
    global $wpdb;
    $table = $wpdb->prefix . 'addressbook';
    return (int) $wpdb->get_var("SELECT COUNT(id) FROM $table");
  }
}
