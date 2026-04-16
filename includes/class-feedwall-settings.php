<?php
if (!defined('ABSPATH')) exit;

class Feedwall_Settings {

    public static function table() {
        return Feedwall_DB::table('settings');
    }

    public static function get($key, $default = null) {
        global $wpdb;

        $value = $wpdb->get_var($wpdb->prepare(
            "SELECT setting_value FROM " . self::table() . " WHERE setting_key = %s",
            $key
        ));

        return $value !== null ? maybe_unserialize($value) : $default;
    }

    public static function set($key, $value) {
        global $wpdb;

        $table = self::table();

        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table WHERE setting_key = %s",
            $key
        ));

        if ($exists) {
            $wpdb->update($table, [
                'setting_value' => maybe_serialize($value)
            ], [
                'setting_key' => $key
            ]);
        } else {
            $wpdb->insert($table, [
                'setting_key' => $key,
                'setting_value' => maybe_serialize($value)
            ]);
        }
    }
}
