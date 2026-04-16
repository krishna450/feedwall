<?php
if (!defined('ABSPATH')) exit;

class Feedwall_Posts {

    const MAX_SIZE = 2097152; // 2MB
    const ALLOWED_TYPES = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];

    public static function submit_post($req) {
        global $wpdb;

        $user_id = self::auth_user($req);
        if (!$user_id) return ['error' => 'Unauthorized'];

        $text = sanitize_textarea_field($req['content_text'] ?? '');

        if (strlen($text) > 500) {
            return ['error' => 'Text too long'];
        }

        if (self::contains_bad_words($text)) {
            return ['error' => 'Content violates guidelines'];
        }

        $image_path = null;

        if (!empty($_FILES['image'])) {
            $image_path = self::process_image($_FILES['image']);
            if (isset($image_path['error'])) return $image_path;
        }

        // ✅ Use settings fallback for state
        $state = Feedwall_Geo::from_request();
        if (!$state) {
            $state = Feedwall_Settings::get('default_state', 'KL');
        }

        $wpdb->insert(Feedwall_DB::table('posts'), [
            'user_id' => $user_id,
            'content_text' => $text,
            'image_path' => $image_path,
            'state_code' => $state
        ]);

        return ['success' => true];
    }

    private static function contains_bad_words($text) {
        $file = FEEDWALL_PATH . 'data/bad_words.json';
        if (!file_exists($file)) return false;

        $words = json_decode(file_get_contents($file), true);

        if (!is_array($words)) return false;

        foreach ($words as $word) {
            if (stripos($text, $word) !== false) {
                return true;
            }
        }
        return false;
    }

    private static function process_image($file) {

        if (empty($file['tmp_name']) || !file_exists($file['tmp_name'])) {
            return ['error' => 'Invalid upload'];
        }

        if ($file['size'] > self::MAX_SIZE) {
            return ['error' => 'File too large'];
        }

        if (!in_array($file['type'], self::ALLOWED_TYPES)) {
            return ['error' => 'Invalid file type'];
        }

        $upload = wp_upload_dir();
        $dir = $upload['basedir'] . '/feedwall_media/';

        if (!file_exists($dir)) {
            wp_mkdir_p($dir);
        }

        $name = uniqid('fw_');

        $thumb = $dir . $name . '_thumb.jpg';
        $wall = $dir . $name . '_wall.jpg';

        // ✅ Safer image creation based on MIME
        switch ($file['type']) {
            case 'image/jpeg':
                $src = imagecreatefromjpeg($file['tmp_name']);
                break;
            case 'image/png':
                $src = imagecreatefrompng($file['tmp_name']);
                break;
            case 'image/webp':
                $src = function_exists('imagecreatefromwebp') ? imagecreatefromwebp($file['tmp_name']) : null;
                break;
            case 'image/gif':
                $src = imagecreatefromgif($file['tmp_name']);
                break;
            default:
                return ['error' => 'Unsupported image'];
        }

        if (!$src) return ['error' => 'Invalid image'];

        // Thumb
        $thumb_img = imagescale($src, 100, 100);
        imagejpeg($thumb_img, $thumb, 70);

        // Wall display
        $wall_img = imagescale($src, 800);
        imagejpeg($wall_img, $wall, 85);

        imagedestroy($src);
        imagedestroy($thumb_img);
        imagedestroy($wall_img);

        return basename($name);
    }

    public static function get_wall($req) {
        global $wpdb;

        $state = Feedwall_Geo::from_request();
        if (!$state) {
            $state = Feedwall_Settings::get('default_state', 'KL');
        }

        $table = Feedwall_DB::table('posts');

        $results = $wpdb->get_results($wpdb->prepare("
            SELECT * FROM $table
            WHERE state_code = %s
            AND status = 'active'
            AND created_at > NOW() - INTERVAL 24 HOUR
            ORDER BY created_at DESC
        ", $state));

        return $results;
    }

    // ✅ FIXED: was private → now public
    public static function auth_user($req) {

        $headers = function_exists('getallheaders') ? getallheaders() : [];

        // Fallback for servers without getallheaders
        if (empty($headers['Authorization'])) {
            $headers['Authorization'] = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        }

        if (empty($headers['Authorization'])) return false;

        $token = str_replace('Bearer ', '', $headers['Authorization']);

        return Feedwall_Auth::validate_token($token);
    }
}
