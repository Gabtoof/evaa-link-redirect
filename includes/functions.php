<?php
// Helper function to ensure the log directory exists
function ensureLogDirectoryExists() {
    $logDirectory = dirname(plugin_dir_path(__FILE__)) . '/logs/';
    if (!file_exists($logDirectory)) {
        mkdir($logDirectory, 0755, true); // Create the directory with read and write permissions for the server
    }
    return $logDirectory;
}

// Separate rate limit check
function isWithinRateLimit($type) {
    $logDirectory = ensureLogDirectoryExists();
    $logFile = $logDirectory . $type . '_rate_limit_log.txt';
    $lastRequestTime = file_exists($logFile) ? file_get_contents($logFile) : 0;
    $currentTime = time();
    $limitSeconds = ($type === 'redirect') ? 10 : 15; // 10 seconds for redirects, 15 for link creation

    if ($currentTime - $lastRequestTime < $limitSeconds) {
        return false;
    }
    file_put_contents($logFile, $currentTime);
    return true;
}

// Function to log all redirections to a specific file
function logAllRedirections($url) {
    if (!isWithinRateLimit('redirect')) {
        return; // Exit if rate limit is exceeded
    }
    $logDirectory = ensureLogDirectoryExists();
    $logFile = $logDirectory . 'all_redirections_log.txt';
    $date = date('Y-m-d H:i:s');
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'Unknown IP';
    $logEntry = "Redirect: $date - IP: $ipAddress - URL: $url\n";
    file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
}

// Function to log when a link is created
function logLinkCreation($url) {
    if (!isWithinRateLimit('creation')) {
        return; // Exit if rate limit is exceeded
    }
    $logDirectory = ensureLogDirectoryExists();
    $logFile = $logDirectory . 'link_creation_log.txt';
    $date = date('Y-m-d H:i:s');
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'Unknown IP';
    $logEntry = "Link created: $date - IP: $ipAddress - URL: $url\n";
    file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
}

function validateURL($url) {
    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        logInvalidURL($url, 'URL is not well-formed.');
        return false;
    }
    $disallowed_keywords = ['wp-login', 'admin', 'login', 'register', 'sql', 'script', 'javascript'];
    foreach ($disallowed_keywords as $keyword) {
        if (strpos($url, $keyword) !== false) {
            logInvalidURL($url, 'URL contains blacklisted words.');
            return false;
        }
    }
    return true;
}

function logInvalidURL($url, $reason) {
    $logDirectory = ensureLogDirectoryExists();
    $logFile = $logDirectory . 'invalid_url_log.txt';
    $date = date('Y-m-d H:i:s');
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'Unknown IP';
    $logEntry = "$date - IP: $ipAddress - Attempt: Invalid URL detected: $url - Reason: $reason\n";
    file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
}
?>
