<?php
/*
Plugin Name: EVAA Link Redirect Handler
Plugin URI: https://github.com/Gabtoof/evaa-link-redirect
Description: Handles link creation, redirection, validation, and rate limiting for URLs.
Version: 1.2.6
Author: Andrew Batiuk
Author URI: https://github.com/Gaftoof
*/

require 'plugin-update-checker/plugin-update-checker.php';  // Include the update checker
use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

$myUpdateChecker = PucFactory::buildUpdateChecker(
    'https://github.com/Gabtoof/evaa-link-redirect/',
    __FILE__,
    'evaa-link-redirect-handler'
);

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

function evaa_log_url($url, $action) {
    $logFile = plugin_dir_path(__FILE__) . 'evaa_urls.log'; // Log file in the plugin directory
    $date = date('Y-m-d H:i:s');
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'Unknown IP';
    $logEntry = "$date - IP: $ipAddress - $action: $url\n";
    file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
}

function evaa_validate_url($url) {
    $allowed_protocols = ['http', 'https'];
    if (!filter_var($url, FILTER_VALIDATE_URL) || strpos($url, ' ') !== false) {
        evaa_log_url($url, 'Invalid format or contains spaces');
        return false;
    }
    $parsed_url = parse_url($url);
    if (!in_array($parsed_url['scheme'], $allowed_protocols) || preg_match('/(wp-login|admin|login|register|sql|script|javascript)/i', $url)) {
        evaa_log_url($url, 'Disallowed scheme or keywords');
        return false;
    }
    return true;
}

function evaa_is_within_rate_limit() {
    $logFile = plugin_dir_path(__FILE__) . 'evaa_rate_limit.log'; // Log file in the plugin directory
    $lastRequestTime = file_exists($logFile) ? file_get_contents($logFile) : 0;
    $currentTime = time();
    if ($currentTime - $lastRequestTime < 15) {
        evaa_log_url($_REQUEST['url'] ?? 'none', 'Rate limit exceeded');
        return false;
    }
    file_put_contents($logFile, $currentTime);
    return true;
}

function evaa_link_redirect_handler() {
    ob_start();

    echo "<style>
    button {
        background-color: #4CAF50; /* Green */
        border: none;
        color: white;
        padding: 10px 20px;
        text-align: center;
        text-decoration: none;
        display: inline-block;
        font-size: 16px;
        margin: 4px 2px;
        cursor: pointer;
    }
    button:hover {
        background-color: #45a049;
    }
    </style>";

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['url']) && evaa_is_within_rate_limit() && evaa_validate_url($_POST['url'])) {
        $encodedUrl = urlencode($_POST['url']);
        $redirectUrl = esc_url(site_url('/link?url=' . $encodedUrl));
        evaa_log_url($redirectUrl, 'Created'); // Log the creation

        $copyChecked = isset($_POST['copyToClipboard']) ? 'checked' : '';
        echo "<input id='urlBox' type='text' value='$redirectUrl' style='width: 80%;' readonly>";
        echo "<button onclick='copyToClipboard()'>Copy to Clipboard</button>";
        echo "<script>
        function copyToClipboard() {
            var copyText = document.getElementById('urlBox');
            copyText.select();
            document.execCommand('copy');
            document.getElementById('copyNotification').textContent = 'Link has been copied to your clipboard for convenience.';
        }
        if (document.getElementById('autoCopy').checked) {
            window.onload = copyToClipboard;
        }
        </script>";
        echo "<p><a href='$redirectUrl' target='_blank'>Click here to test link</a>. It has been copied to your clipboard for convenience.</p>";
        echo "<p id='copyNotification'></p>";
        echo "<input type='checkbox' id='autoCopy' name='copyToClipboard' $copyChecked> <label for='autoCopy'>Copy link to clipboard automatically</label>";
    } else {
        // Display form for creating links
        echo "<form method='post' action=''>
            <label for='url'>Enter URL:</label>
            <input type='text' id='url' name='url' required style='width: 80%;'>
            <input type='checkbox' id='autoCopy' name='copyToClipboard' checked> <label for='autoCopy'>Copy link to clipboard automatically</label>
            <button type='submit' style='background-color: #4CAF50; color: white; padding: 10px 20px; margin-left: 10px;'>Create Link</button>
        </form>";
        if ($_SERVER['REQUEST_METHOD'] === 'POST' or isset($_GET['url'])) {
            echo "<p>Error: Invalid request or URL. Please check and try again.</p>";
        }
    }

    return ob_get_clean();
}

add_shortcode('evaa_link_redirect', 'evaa_link_redirect_handler');


