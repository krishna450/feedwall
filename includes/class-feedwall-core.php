<?php
if (!defined('ABSPATH')) exit;

class Feedwall_Core {

    public function run() {
        $this->load_hooks();
    }

    private function load_hooks() {
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('rest_api_init', [$this, 'register_routes']);
    }

    public function enqueue_assets() {
        wp_enqueue_script(
            'feedwall-app',
            FEEDWALL_URL . 'public/js/app.js',
            [],
            '1.0',
            true
        );

        wp_enqueue_style(
            'feedwall-style',
            FEEDWALL_URL . 'public/css/style.css'
        );

        wp_localize_script('feedwall-app', 'FEEDWALL_CONFIG', [
            'api_url' => rest_url('feedwall/v1/'),
        ]);
    }

    public function register_routes() {
        // Placeholder for next phases
    }
}
