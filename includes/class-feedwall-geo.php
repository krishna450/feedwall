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
            if (!$reader) return 'KL';

            $record = $reader->city($ip);

            $subdivision = $record->mostSpecificSubdivision->isoCode;

            return $subdivision ?: 'KL';

        } catch (Exception $e) {
            return 'KL';
        }
    }

    public static function from_request() {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        return self::detect_state($ip);
    }
}
