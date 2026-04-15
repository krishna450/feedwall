<?php
if (!defined('ABSPATH')) exit;

class Feedwall_DB {

    public static function table($name) {
        global $wpdb;
        return $wpdb->prefix . 'feedwall_' . $name;
    }

    public static function now() {
        return current_time('mysql');
    }
}
