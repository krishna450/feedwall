<?php
if (!defined('ABSPATH')) exit;

class Feedwall_API {

    public function register_routes() {

        register_rest_route('feedwall/v1', '/check-username', [
            'methods' => 'GET',
            'callback' => [$this, 'check_username']
        ]);

        register_rest_route('feedwall/v1', '/register', [
            'methods' => 'POST',
            'callback' => [$this, 'register']
        ]);

        register_rest_route('feedwall/v1', '/login', [
            'methods' => 'POST',
            'callback' => [$this, 'login']
        ]);

        register_rest_route('feedwall/v1', '/geo-detect', [
            'methods' => 'GET',
            'callback' => [$this, 'geo_detect']
        ]);

        register_rest_route('feedwall/v1', '/geo-override', [
            'methods' => 'POST',
            'callback' => [$this, 'geo_override']
        ]);
    }

    public function check_username($req) {
        global $wpdb;

        $username = sanitize_text_field($req['username']);
        $table = Feedwall_DB::table('users');

        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table WHERE username = %s",
            $username
        ));

        return ['available' => !$exists];
    }

    public function register($req) {
        global $wpdb;

        $username = sanitize_text_field($req['username']);
        $passcode = sanitize_text_field($req['passcode']);

        if (!preg_match('/^\d{6}$/', $passcode)) {
            return ['error' => 'Invalid passcode'];
        }

        $table = Feedwall_DB::table('users');

        $wpdb->insert($table, [
            'username' => $username,
            'passcode_hash' => Feedwall_Auth::hash_passcode($passcode)
        ]);

        return ['success' => true];
    }

    public function login($req) {
        global $wpdb;

        $username = sanitize_text_field($req['username']);
        $passcode = sanitize_text_field($req['passcode']);

        $rate_key = "fw_login_" . md5($username);

        if (!Feedwall_Auth::rate_limit($rate_key)) {
            return ['error' => 'Too many attempts'];
        }

        $table = Feedwall_DB::table('users');

        $user = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE username = %s",
            $username
        ));

        if (!$user || !Feedwall_Auth::verify_passcode($passcode, $user->passcode_hash)) {
            return ['error' => 'Invalid credentials'];
        }

        $token = Feedwall_Auth::generate_token();
        Feedwall_Auth::store_session($user->user_id, $token);

        return ['token' => $token];
    }

    public function geo_detect() {
        return ['state' => Feedwall_Geo::from_request()];
    }

    public function geo_override($req) {
        $state = sanitize_text_field($req['state']);
        set_transient('fw_geo_override_' . $_SERVER['REMOTE_ADDR'], $state, 3600);
        return ['state' => $state];
    }
}
