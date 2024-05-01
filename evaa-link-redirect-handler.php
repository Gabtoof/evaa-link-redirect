<?php
/*
Plugin Name: EVAA Link Redirect Handler
Plugin URI: https://github.com/Gabtoof/evaa-link-redirect
Description: Handles link creation, redirection, validation, and rate limiting for URLs.
Version: 1.0.8
Author: Andrew Batiuk
Author URI: https://github.com/Gaftoof
*/

require 'plugin-update-checker/plugin-update-checker.php';  // Include the update checker
use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

$myUpdateChecker = PucFactory::buildUpdateChecker(
    'https://github.com/Gabtoof/evaa-link-redirect/',
    __FILE__, // Full path to the main plugin file
    'evaa-link-redirect-handler'
);

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Log URL function
function evaa_log_url($url, $action) {
    $logFile = plugin_dir_path(__FILE__) . 'evaa_urls.log'; // Log file in the plugin directory
    $date = date('Y-m-d H:i:s');
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'Unknown IP';
    $logEntry = "$date - IP: $ipAddress - $action: $url\n";
    file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
}

// Validate URL function
function evaa_validate_url($url) {
    if (!filter_var($url, FILTER_VALIDATE_URL) || strpos($url, ' ') !== false) {
        return 'Sorry, the link address appears invalid. Please try again with a different link.';
    }
    $parsed_url = parse_url($url);
    if (!in_array($parsed_url['scheme'], ['http', 'https']) ||
        preg_match('/(wp-login|admin|login|register|sql|script|javascript)/i', $url)) {
        return 'Sorry, the link address appears invalid. Please try again with a different link.';
    }
    return true;
}

// Rate limiting function
function evaa_is_within_rate_limit() {
    $logFile = plugin_dir_path(__FILE__) . 'evaa_rate_limit.log'; // Log file in the plugin directory
    $lastRequestTime = file_exists($logFile) ? file_get_contents($logFile) : 0;
    $currentTime = time();
    if ($currentTime - $lastRequestTime < 15) {
        return 'Sorry, our service is being heavily used. Please try again in a few moments.';
    }
    file_put_contents($logFile, $currentTime);
    return true;
}

// Handle global redirections
add_action('init', 'evaa_handle_redirection');
function evaa_handle_redirection() {
    if (isset($_GET['url']) && !empty($_GET['url'])) {
        $url = esc_url_raw($_GET['url']);
        $validation = evaa_validate_url($url);
        $rate_limit = evaa_is_within_rate_limit();

        if ($validation === true && $rate_limit === true) {
            evaa_log_url($url, 'Redirected'); // Log the redirection
            wp_redirect($url);
            exit;
        } else {
            wp_redirect(home_url('/error-page?error=' . urlencode($validation ?? $rate_limit)));
            exit;
        }
    }
}

// Register the shortcode with WordPress
add_shortcode('evaa_link_redirect', 'evaa_link_redirect_handler');
function evaa_link_redirect_handler() {
    // Since the shortcode might contain form and error handling logic, it should remain separate from redirection logic
    ob_start();
    // Handle shortcode display and actions here
    return ob_get_clean();
}
