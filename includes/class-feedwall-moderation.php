<?php
if (!defined('ABSPATH')) exit;

class Feedwall_Moderation {

    public static function report_post($req) {
        global $wpdb;

        $post_id = intval($req['post_id']);
        $table = Feedwall_DB::table('posts');

        $wpdb->query($wpdb->prepare("
            UPDATE $table
            SET report_count = report_count + 1
            WHERE post_id = %d
        ", $post_id));

        $wpdb->query($wpdb->prepare("
            UPDATE $table
            SET status = 'pending_review'
            WHERE post_id = %d AND report_count >= 5
        ", $post_id));

        return ['success' => true];
    }
}
