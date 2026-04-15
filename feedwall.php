<?php
/**
 * Plugin Name: Feedwall
 * Description: Headless SPA Feedwall Plugin
 * Version: 1.0
 */

if (!defined('ABSPATH')) exit;

define('FEEDWALL_PATH', plugin_dir_path(__FILE__));
define('FEEDWALL_URL', plugin_dir_url(__FILE__));

require_once FEEDWALL_PATH . 'includes/class-feedwall-activator.php';
require_once FEEDWALL_PATH . 'includes/class-feedwall-db.php';
require_once FEEDWALL_PATH . 'includes/class-feedwall-core.php';
require_once FEEDWALL_PATH . 'includes/class-feedwall-shortcode.php';

register_activation_hook(__FILE__, ['Feedwall_Activator', 'activate']);

function run_feedwall() {
    $plugin = new Feedwall_Core();
    $plugin->run();
}

run_feedwall();
