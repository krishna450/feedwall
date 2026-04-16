<?php
if (!defined('ABSPATH')) exit;

class Feedwall_Telegram {

    public static function tg_pair($req) {
        $user_id = Feedwall_Posts::auth_user($req);
        if (!$user_id) return ['error' => 'Unauthorized'];

        $code = substr(str_shuffle('ABCDEFGH123456'), 0, 6);

        set_transient("fw_tg_pair_$code", $user_id, 300);

        return ['code' => $code];
    }

    public static function webhook($req) {

        $data = json_decode(file_get_contents('php://input'), true);

        if (empty($data['message']['text']) || empty($data['message']['chat']['id'])) {
            return;
        }

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

            self::send($chat_id, "✅ Feedwall connected successfully!");
        }
    }

    public static function notify_thread($post_id, $commenter_id, $text) {
        global $wpdb;

        $users_table = Feedwall_DB::table('users');
        $comments_table = Feedwall_DB::table('comments');

        // ✅ Get all participants except commenter
        $users = $wpdb->get_results($wpdb->prepare("
            SELECT DISTINCT u.user_id, u.telegram_chat_id
            FROM $users_table u
            LEFT JOIN $comments_table c ON u.user_id = c.user_id
            WHERE c.post_id = %d
            AND u.telegram_chat_id IS NOT NULL
            AND u.user_id != %d
        ", $post_id, $commenter_id));

        if (!$users) return;

        foreach ($users as $u) {
            if (!empty($u->telegram_chat_id)) {
                self::send($u->telegram_chat_id, "💬 New comment: " . $text);
            }
        }
    }

    private static function send($chat_id, $message) {

        // ✅ Get token from settings
        $token = Feedwall_Settings::get('bot_token');

        if (empty($token)) return;

        wp_remote_post("https://api.telegram.org/bot{$token}/sendMessage", [
            'body' => [
                'chat_id' => $chat_id,
                'text' => $message
            ],
            'timeout' => 5
        ]);
    }
}
