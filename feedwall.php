<?php
/**
 * Plugin Name: Feedwall
 * Description: Headless SPA Feedwall Plugin
 * Version: 1.0
 */

if (!defined('ABSPATH')) exit;

/**
 * Constants
 */
define('FEEDWALL_PATH', plugin_dir_path(__FILE__));
define('FEEDWALL_URL', plugin_dir_url(__FILE__));

/**
 * ✅ Composer Autoload (GeoIP dependency)
 */
if (file_exists(FEEDWALL_PATH . 'vendor/autoload.php')) {
    require_once FEEDWALL_PATH . 'vendor/autoload.php';
}

/**
 * Core Includes
 */
require_once FEEDWALL_PATH . 'includes/class-feedwall-activator.php';
require_once FEEDWALL_PATH . 'includes/class-feedwall-db.php';

/**
 * ✅ Settings System (NEW)
 */
require_once FEEDWALL_PATH . 'includes/class-feedwall-settings.php';
require_once FEEDWALL_PATH . 'includes/class-feedwall-admin.php';

/**
 * Phase 2 Includes (Auth + Geo + API)
 */
require_once FEEDWALL_PATH . 'includes/class-feedwall-auth.php';
require_once FEEDWALL_PATH . 'includes/class-feedwall-geo.php';
require_once FEEDWALL_PATH . 'includes/class-feedwall-api.php';

/**
 * Phase 3 Includes (Posts + Moderation + Cron)
 */
require_once FEEDWALL_PATH . 'includes/class-feedwall-posts.php';
require_once FEEDWALL_PATH . 'includes/class-feedwall-moderation.php';
require_once FEEDWALL_PATH . 'includes/class-feedwall-cron.php';

/**
 * UI + Core
 */
require_once FEEDWALL_PATH . 'includes/class-feedwall-core.php';
require_once FEEDWALL_PATH . 'includes/class-feedwall-shortcode.php';

/**
 * 🔁 Custom Cron Interval (5 minutes)
 */
add_filter('cron_schedules', function($schedules) {
    if (!isset($schedules['five_minutes'])) {
        $schedules['five_minutes'] = [
            'interval' => 300,
            'display'  => 'Every 5 Minutes'
        ];
    }
    return $schedules;
});

/**
 * 🔌 Plugin Activation
 */
register_activation_hook(__FILE__, function() {
    Feedwall_Activator::activate();

    // Ensure cron is scheduled
    if (class_exists('Feedwall_Cron')) {
        Feedwall_Cron::schedule();
    }
});

/**
 * 🚀 Boot Plugin
 */
function run_feedwall() {
    $plugin = new Feedwall_Core();
    $plugin->run();
}

run_feedwall();
