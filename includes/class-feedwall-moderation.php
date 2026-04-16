<?php
if (!defined('ABSPATH')) exit;

class Feedwall_Moderation {

    public static function report_post($req) {
        global $wpdb;

        $post_id = intval($req['post_id'] ?? 0);

        if (!$post_id) {
            return ['error' => 'Invalid post ID'];
        }

        $table = Feedwall_DB::table('posts');

        // ✅ Check if post exists
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table WHERE post_id = %d",
            $post_id
        ));

        if (!$exists) {
            return ['error' => 'Post not found'];
        }

        // ✅ Increment report count
        $wpdb->query($wpdb->prepare("
            UPDATE $table
            SET report_count = report_count + 1
            WHERE post_id = %d
        ", $post_id));

        // ✅ Get threshold from settings
        $threshold = Feedwall_Settings::get('report_threshold', 5);

        // ✅ Update status if threshold reached
        $wpdb->query($wpdb->prepare("
            UPDATE $table
            SET status = 'pending_review'
            WHERE post_id = %d AND report_count >= %d
        ", $post_id, $threshold));

        return ['success' => true];
    }
}
