<?php
if (!defined('ABSPATH')) exit;

class Feedwall_Admin {

    public static function init() {
        add_action('admin_menu', [__CLASS__, 'menu']);
    }

    public static function menu() {
        add_menu_page(
            'Feedwall Settings',
            'Feedwall',
            'manage_options',
            'feedwall-settings',
            [__CLASS__, 'render'],
            'dashicons-format-chat'
        );
    }

    public static function render() {

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {

            Feedwall_Settings::set('bot_token', sanitize_text_field($_POST['bot_token']));
            Feedwall_Settings::set('report_threshold', intval($_POST['report_threshold']));
            Feedwall_Settings::set('default_state', sanitize_text_field($_POST['default_state']));
            Feedwall_Settings::set('login_limit', intval($_POST['login_limit']));

            echo '<div class="updated"><p>Settings saved</p></div>';
        }

        $bot = Feedwall_Settings::get('bot_token', '');
        $report = Feedwall_Settings::get('report_threshold', 5);
        $state = Feedwall_Settings::get('default_state', 'KL');
        $limit = Feedwall_Settings::get('login_limit', 5);

        ?>

        <div class="wrap">
            <h1>Feedwall Settings</h1>

            <form method="POST">
                <table class="form-table">

                    <tr>
                        <th>Telegram Bot Token</th>
                        <td><input type="text" name="bot_token" value="<?php echo esc_attr($bot); ?>" size="50"></td>
                    </tr>

                    <tr>
                        <th>Report Threshold</th>
                        <td><input type="number" name="report_threshold" value="<?php echo esc_attr($report); ?>"></td>
                    </tr>

                    <tr>
                        <th>Default State</th>
                        <td><input type="text" name="default_state" value="<?php echo esc_attr($state); ?>"></td>
                    </tr>

                    <tr>
                        <th>Login Attempt Limit</th>
                        <td><input type="number" name="login_limit" value="<?php echo esc_attr($limit); ?>"></td>
                    </tr>

                </table>

                <p><button class="button button-primary">Save Settings</button></p>
            </form>
        </div>

        <?php
    }
}
