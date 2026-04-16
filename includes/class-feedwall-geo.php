<?php
if (!defined('ABSPATH')) exit;

use GeoIp2\Database\Reader;

class Feedwall_Geo {

    private static function get_reader() {
        $db_path = FEEDWALL_PATH . 'geo/GeoLite2-City.mmdb';

        if (!file_exists($db_path)) {
            return null;
        }

        return new Reader($db_path);
    }

    public static function detect_state($ip) {
        try {
            $reader = self::get_reader();
            if (!$reader) return self::fallback();

            $record = $reader->city($ip);

            $subdivision = $record->mostSpecificSubdivision->isoCode;

            return $subdivision ?: self::fallback();

        } catch (Exception $e) {
            return self::fallback();
        }
    }

    public static function from_request() {

        $ip = self::get_ip();

        // ✅ Check override first
        $override = get_transient('fw_geo_override_' . $ip);
        if ($override) {
            return $override;
        }

        return self::detect_state($ip);
    }

    private static function fallback() {
        return Feedwall_Settings::get('default_state', 'KL');
    }

    private static function get_ip() {

        // Cloudflare
        if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
            return $_SERVER['HTTP_CF_CONNECTING_IP'];
        }

        // Proxy
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            return trim($ips[0]);
        }

        return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    }
}
