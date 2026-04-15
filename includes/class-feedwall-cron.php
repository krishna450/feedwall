<?php
if (!defined('ABSPATH')) exit;

class Feedwall_Cron {

    public static function init() {
        add_action('feedwall_cron_event', [__CLASS__, 'run']);
    }

    public static function schedule() {
        if (!wp_next_scheduled('feedwall_cron_event')) {
            wp_schedule_event(time(), 'five_minutes', 'feedwall_cron_event');
        }
    }

    public static function run() {
        global $wpdb;

        $posts_table = Feedwall_DB::table('posts');
        $comments_table = Feedwall_DB::table('comments');

        // 1️⃣ Expire posts (>24h)
        $wpdb->query("
            UPDATE $posts_table
            SET status = 'expired'
            WHERE created_at < NOW() - INTERVAL 24 HOUR
            AND status = 'active'
        ");

        // 2️⃣ Fetch posts to delete (>120h)
        $posts = $wpdb->get_results("
            SELECT * FROM $posts_table
            WHERE created_at < NOW() - INTERVAL 120 HOUR
        ");

        $upload = wp_upload_dir();
        $dir = $upload['basedir'] . '/feedwall_media/';

        foreach ($posts as $post) {

            // Delete images safely
            if (!empty($post->image_path)) {
                @unlink($dir . $post->image_path . '_thumb.jpg');
                @unlink($dir . $post->image_path . '_wall.jpg');
            }

            // Delete comments first (FK safety logic)
            $wpdb->delete($comments_table, [
                'post_id' => $post->post_id
            ]);

            // Delete post
            $wpdb->delete($posts_table, [
                'post_id' => $post->post_id
            ]);
        }
    }
}
