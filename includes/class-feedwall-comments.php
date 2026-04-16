<?php
if (!defined('ABSPATH')) exit;

class Feedwall_Comments {

    public static function add_comment($req) {
        global $wpdb;

        $user_id = Feedwall_Posts::auth_user($req);
        if (!$user_id) return ['error' => 'Unauthorized'];

        $post_id = intval($req['post_id'] ?? 0);
        $text = sanitize_textarea_field($req['content_text'] ?? '');

        if (!$post_id) {
            return ['error' => 'Invalid post ID'];
        }

        if (empty($text)) {
            return ['error' => 'Comment cannot be empty'];
        }

        if (strlen($text) > 500) {
            return ['error' => 'Comment too long'];
        }

        $posts_table = Feedwall_DB::table('posts');
        $comments_table = Feedwall_DB::table('comments');

        // ✅ Ensure post exists and is active
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $posts_table WHERE post_id = %d AND status != 'deleted'",
            $post_id
        ));

        if (!$exists) {
            return ['error' => 'Post not found'];
        }

        $inserted = $wpdb->insert($comments_table, [
            'post_id' => $post_id,
            'user_id' => $user_id,
            'content_text' => $text
        ]);

        if (!$inserted) {
            return ['error' => 'Failed to add comment'];
        }

        // ✅ Trigger Telegram notify only after success
        if (class_exists('Feedwall_Telegram')) {
            Feedwall_Telegram::notify_thread($post_id, $user_id, $text);
        }

        return ['success' => true];
    }

    public static function get_comments($req) {
        global $wpdb;

        $post_id = intval($req['post_id'] ?? 0);

        if (!$post_id) {
            return [];
        }

        return $wpdb->get_results($wpdb->prepare("
            SELECT * FROM " . Feedwall_DB::table('comments') . "
            WHERE post_id = %d
            ORDER BY created_at ASC
        ", $post_id));
    }
}
