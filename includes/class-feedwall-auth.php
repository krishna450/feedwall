<?php
if (!defined('ABSPATH')) exit;

class Feedwall_Auth {

    const TOKEN_EXPIRY = 604800; // 7 days

    public static function hash_passcode($passcode) {
        return password_hash($passcode, PASSWORD_DEFAULT);
    }

    public static function verify_passcode($passcode, $hash) {
        return password_verify($passcode, $hash);
    }

    public static function generate_token() {
        return bin2hex(random_bytes(32));
    }

    public static function store_session($user_id, $token) {
        global $wpdb;

        $table = Feedwall_DB::table('sessions');

        $wpdb->insert($table, [
            'user_id' => $user_id,
            'token_hash' => password_hash($token, PASSWORD_DEFAULT),
            'expires_at' => date('Y-m-d H:i:s', time() + self::TOKEN_EXPIRY)
        ]);

        return true;
    }

    public static function validate_token($token) {
        global $wpdb;

        $table = Feedwall_DB::table('sessions');

        $sessions = $wpdb->get_results("SELECT * FROM $table WHERE expires_at > NOW()");

        foreach ($sessions as $session) {
            if (password_verify($token, $session->token_hash)) {
                return $session->user_id;
            }
        }

        return false;
    }

    public static function rate_limit($key, $limit = 5, $window = 300) {
        $attempts = get_transient($key);

        if ($attempts === false) {
            set_transient($key, 1, $window);
            return true;
        }

        if ($attempts >= $limit) {
            return false;
        }

        set_transient($key, $attempts + 1, $window);
        return true;
    }
}
