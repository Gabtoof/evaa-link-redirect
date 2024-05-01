<?php
/*
Plugin Name: EVAA Link Redirect Handler
Plugin URI: https://github.com/Gabtoof/evaa-link-redirect
Description: Handles link creation, redirection, validation, and rate limiting for URLs.
Version: 1.0.8
Author: Andrew Batiuk
Author URI: https://github.com/Gaftoof
*/

// Include necessary files
require_once plugin_dir_path(__FILE__) . 'includes/functions.php';
require_once plugin_dir_path(__FILE__) . 'includes/shortcode.php';

// Enqueue scripts and styles
function evaa_enqueue_scripts() {
    wp_enqueue_script('evaa-scripts', plugin_dir_url(__FILE__) . 'assets/scripts.js', array('jquery'), null, true);
    wp_enqueue_style('evaa-styles', plugin_dir_url(__FILE__) . 'assets/style.css');
}
add_action('wp_enqueue_scripts', 'evaa_enqueue_scripts');
?>
