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

        $table = Feedwall_DB::table('posts');

        // Expire after 24h
        $wpdb->query("
            UPDATE $table
            SET status = 'expired'
            WHERE created_at < NOW() - INTERVAL 24 HOUR
            AND status = 'active'
        ");

        // Delete after 120h
        $posts = $wpdb->get_results("
            SELECT * FROM $table
            WHERE created_at < NOW() - INTERVAL 120 HOUR
        ");

        foreach ($posts as $post) {

            $upload = wp_upload_dir();
            $dir = $upload['basedir'] . '/feedwall_media/';

            @unlink($dir . $post->image_path . '_thumb.jpg');
            @unlink($dir . $post->image_path . '_wall.jpg');

            $wpdb->delete($table, ['post_id' => $post->post_id]);
        }
    }
}
