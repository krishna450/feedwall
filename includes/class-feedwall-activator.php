<?php
if (!defined('ABSPATH')) exit;

class Feedwall_Activator {

    public static function activate() {
    self::create_tables();
    self::create_upload_dir();

    require_once FEEDWALL_PATH . 'includes/class-feedwall-cron.php';
    Feedwall_Cron::schedule();
}

    private static function create_tables() {
        global $wpdb;

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $charset_collate = $wpdb->get_charset_collate();

        $users = $wpdb->prefix . 'feedwall_users';
        $posts = $wpdb->prefix . 'feedwall_posts';
        $comments = $wpdb->prefix . 'feedwall_comments';
        $sessions = $wpdb->prefix . 'feedwall_sessions';

        $sql = "

        CREATE TABLE $users (
            user_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) UNIQUE NOT NULL,
            passcode_hash VARCHAR(255) NOT NULL,
            telegram_chat_id VARCHAR(50),
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX(username)
        ) $charset_collate;

        CREATE TABLE $posts (
            post_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id BIGINT UNSIGNED NOT NULL,
            content_text TEXT,
            image_path VARCHAR(255),
            state_code VARCHAR(5) DEFAULT 'KL',
            report_count INT DEFAULT 0,
            status VARCHAR(20) DEFAULT 'active',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX(state_code),
            INDEX(created_at),
            INDEX(user_id)
        ) $charset_collate;

        CREATE TABLE $comments (
            comment_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            post_id BIGINT UNSIGNED NOT NULL,
            user_id BIGINT UNSIGNED NOT NULL,
            content_text TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX(post_id),
            INDEX(user_id)
        ) $charset_collate;

        CREATE TABLE $sessions (
            session_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id BIGINT UNSIGNED NOT NULL,
            token_hash VARCHAR(255) NOT NULL,
            expires_at DATETIME,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX(user_id),
            INDEX(expires_at)
        ) $charset_collate;
        ";

        dbDelta($sql);
    }

    private static function create_upload_dir() {
        $upload_dir = wp_upload_dir();
        $feedwall_dir = $upload_dir['basedir'] . '/feedwall_media';

        if (!file_exists($feedwall_dir)) {
            wp_mkdir_p($feedwall_dir);
        }
    }
}
