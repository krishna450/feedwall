<?php
if (!defined('ABSPATH')) exit;

class Feedwall_Shortcode {

    public function __construct() {
        add_shortcode('feedwall_app', [$this, 'render']);
    }

    public function render() {
        return '<div id="feedwall-root"></div>';
    }
}

new Feedwall_Shortcode();
