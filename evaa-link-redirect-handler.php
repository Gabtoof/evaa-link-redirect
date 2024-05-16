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
define('MY_PLUGIN_ROOT_DIR', plugin_dir_path(__FILE__));

require_once plugin_dir_path(__FILE__) . 'includes/functions.php';
require_once plugin_dir_path(__FILE__) . 'includes/shortcode.php';

// Enqueue scripts and styles
function evaa_enqueue_scripts() {
    if (is_a_page_with_shortcode('evaa_link_redirect')) {
        // Enqueue the main JavaScript file
        wp_enqueue_script('evaa-scripts', plugin_dir_url(__FILE__) . 'assets/evaa-scripts.js', array('jquery'), '1.0.8', true);
        
        // Localize script to pass PHP variables to JavaScript
        wp_localize_script('evaa-scripts', 'evaaData', array('fullUrl' => $fullUrl)); // Ensure $fullUrl is defined and relevant

        // Uncomment below to enqueue styles if you have any
        // wp_enqueue_style('evaa-styles', plugin_dir_url(__FILE__) . 'assets/style.css', array(), '1.0.8');
    }
}
add_action('wp_enqueue_scripts', 'evaa_enqueue_scripts');

// Optionally add a helper function to detect if a shortcode is used in a post
function is_a_page_with_shortcode($shortcode = '') {
    global $post;
    return (is_a($post, 'WP_Post') && has_shortcode($post->post_content, $shortcode));
}
?>
