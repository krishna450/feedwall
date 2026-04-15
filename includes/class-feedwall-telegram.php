<?php
if (!defined('ABSPATH')) exit;

class Feedwall_Telegram {

    const BOT_TOKEN = 'YOUR_BOT_TOKEN';

    public static function tg_pair($req) {
        global $wpdb;

        $user_id = Feedwall_Posts::auth_user($req);
        if (!$user_id) return ['error' => 'Unauthorized'];

        $code = substr(str_shuffle('ABCDEFGH123456'), 0, 6);

        set_transient("fw_tg_pair_$code", $user_id, 300);

        return ['code' => $code];
    }

    public static function webhook($req) {
        $data = json_decode(file_get_contents('php://input'), true);

        if (!isset($data['message']['text'])) return;

        $text = trim($data['message']['text']);
        $chat_id = $data['message']['chat']['id'];

        $user_id = get_transient("fw_tg_pair_$text");

        if ($user_id) {
            global $wpdb;

            $wpdb->update(
                Feedwall_DB::table('users'),
                ['telegram_chat_id' => $chat_id],
                ['user_id' => $user_id]
            );

            delete_transient("fw_tg_pair_$text");

            self::send($chat_id, "✅ Feedwall connected!");
        }
    }

    public static function notify_thread($post_id, $commenter_id, $text) {
        global $wpdb;

        $users = $wpdb->get_results($wpdb->prepare("
            SELECT DISTINCT u.telegram_chat_id
            FROM " . Feedwall_DB::table('users') . " u
            LEFT JOIN " . Feedwall_DB::table('comments') . " c ON u.user_id = c.user_id
            WHERE c.post_id = %d AND u.telegram_chat_id IS NOT NULL
        ", $post_id));

        foreach ($users as $u) {
            self::send($u->telegram_chat_id, "💬 New comment: $text");
        }
    }

    private static function send($chat_id, $message) {
        wp_remote_post("https://api.telegram.org/bot" . self::BOT_TOKEN . "/sendMessage", [
            'body' => [
                'chat_id' => $chat_id,
                'text' => $message
            ]
        ]);
    }
}
