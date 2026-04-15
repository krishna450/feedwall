<?php
if (!defined('ABSPATH')) exit;

class Feedwall_Comments {

    public static function add_comment($req) {
        global $wpdb;

        $user_id = Feedwall_Posts::auth_user($req);
        if (!$user_id) return ['error' => 'Unauthorized'];

        $post_id = intval($req['post_id']);
        $text = sanitize_textarea_field($req['content_text']);

        $wpdb->insert(Feedwall_DB::table('comments'), [
            'post_id' => $post_id,
            'user_id' => $user_id,
            'content_text' => $text
        ]);

        // Trigger Telegram notify
        Feedwall_Telegram::notify_thread($post_id, $user_id, $text);

        return ['success' => true];
    }

    public static function get_comments($req) {
        global $wpdb;

        $post_id = intval($req['post_id']);

        return $wpdb->get_results($wpdb->prepare("
            SELECT * FROM " . Feedwall_DB::table('comments') . "
            WHERE post_id = %d
            ORDER BY created_at ASC
        ", $post_id));
    }
}
