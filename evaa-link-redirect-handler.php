<?php
/*
Plugin Name: EVAA Link Redirect Handler
Plugin URI: https://github.com/Gabtoof/evaa-link-redirect
Description: Handles link creation, redirection, validation, and rate limiting for URLs.
Version: 1.0.3
Author: Andrew Batiuk
Author URI: https://github.com/Gaftoof
*/

require 'plugin-update-checker-5.4/plugin-update-checker.php';  // Include the update checker

$myUpdateChecker = Puc_v4_Factory::buildUpdateChecker(
    'https://raw.githubusercontent.com/Gabtoof/evaa-link-redirect/main/update-info.json', // URL of your GitHub repository
    __FILE__, // Full path to the main plugin file
    'evaa-link-redirect-handler' // Unique slug for your plugin
);

// Optional: Set the GitHub access token if your repository is private
// $myUpdateChecker->setAuthentication('your-github-access-token');

// Optional: Set the branch that contains the stable release
// $myUpdateChecker->setBranch('main');

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Function for logging invalid URLs
function evaa_log_invalid_url($url, $reason) {
    $logFile = WP_CONTENT_DIR . '/invalid_url_log.txt'; // Safe location for log files
    $date = date('Y-m-d H:i:s');
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'Unknown IP';
    $logEntry = "$date - IP: $ipAddress - URL: $url - Reason: $reason\n";
    file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
}

// URL validation function
function evaa_validate_url($url) {
    $allowed_protocols = ['http', 'https'];
    if (!filter_var($url, FILTER_VALIDATE_URL) || strpos($url, ' ') !== false) {
        evaa_log_invalid_url($url, 'Invalid format or contains spaces');
        return false;
    }
    $parsed_url = parse_url($url);
    if (!in_array($parsed_url['scheme'], $allowed_protocols) || preg_match('/(wp-login|admin|login|register|sql|script|javascript)/i', $url)) {
        evaa_log_invalid_url($url, 'Disallowed scheme or keywords');
        return false;
    }
    return true;
}

// Rate limiting function
function evaa_is_within_rate_limit() {
    $logFile = WP_CONTENT_DIR . '/rate_limit_log.txt'; // Safe location for log files
    $lastRequestTime = file_exists($logFile) ? file_get_contents($logFile) : 0;
    $currentTime = time();
    if ($currentTime - $lastRequestTime < 15) {
        evaa_log_invalid_url($_REQUEST['url'] ?? 'none', 'Rate limit exceeded');
        return false;
    }
    file_put_contents($logFile, $currentTime);
    return true;
}

// Shortcode function for form and redirect logic
function evaa_link_redirect_handler() {
    ob_start(); // Start output buffering

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['url']) && evaa_is_within_rate_limit() && evaa_validate_url($_POST['url'])) {
        $encodedUrl = urlencode($_POST['url']);
        $redirectUrl = esc_url(site_url('/?url=' . $encodedUrl)); // Build redirect URL
        echo "Link created: <a href='$redirectUrl'>Click here to test redirection</a>";
    } elseif (isset($_GET['url']) && evaa_is_within_rate_limit() && evaa_validate_url($_GET['url'])) {
        $url = urldecode($_GET['url']);
        if (!headers_sent()) {
            header("Location: $url");
            exit;
        }
    } else {
        // Display form for creating links
        ?>
        <form method="post" action="">
            <label for="url">Enter URL:</label>
            <input type="text" id="url" name="url" required>
            <button type="submit">Create Link</button>
        </form>
        <?php
        if ($_SERVER['REQUEST_METHOD'] === 'POST' || isset($_GET['url'])) {
            echo "<p>Error: Invalid request or URL. Please check and try again.</p>";
        }
    }

    return ob_get_clean(); // Return the buffered output
}

// Register the shortcode with WordPress
add_shortcode('evaa_link_redirect', 'evaa_link_redirect_handler');
