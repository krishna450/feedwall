<?php
if (!defined('ABSPATH')) exit;

class Feedwall_Threads {

    public static function my_threads($req) {
        global $wpdb;

        $user_id = Feedwall_Posts::auth_user($req);
        if (!$user_id) return ['error' => 'Unauthorized'];

        $posts = Feedwall_DB::table('posts');
        $comments = Feedwall_DB::table('comments');

        return $wpdb->get_results($wpdb->prepare("
            SELECT DISTINCT p.*
            FROM $posts p
            LEFT JOIN $comments c ON p.post_id = c.post_id
            WHERE (p.user_id = %d OR c.user_id = %d)
            AND p.created_at > NOW() - INTERVAL 120 HOUR
            AND p.status != 'deleted'
            ORDER BY p.created_at DESC
        ", $user_id, $user_id));
    }
}
