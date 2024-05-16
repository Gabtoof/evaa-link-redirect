<?php
// Helper function to ensure the log directory exists
function ensureLogDirectoryExists() {
    $logDirectory = MY_PLUGIN_ROOT_DIR . 'logs/';
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
    $limitSeconds = ($type === 'redirect') ? 1 : 5; // 1 second for redirects, 5 seconds for link creation

    if ($currentTime - $lastRequestTime < $limitSeconds) {
        return false;
    }
    file_put_contents($logFile, $currentTime);
    return true;
}

// Function to log all redirections to a specific file
function logAllRedirections($url) {
    $logDirectory = ensureLogDirectoryExists();
    $logFile = $logDirectory . 'all_redirections_log.txt';
    $date = date('Y-m-d H:i:s');
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'Unknown IP';
    $logEntry = "Redirect: $date - IP: $ipAddress - URL: $url\n";
    file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
}

// Function to log when a link is created
function logLinkCreation($url) {
    $logDirectory = ensureLogDirectoryExists();
    $logFile = $logDirectory . 'all_created_links_log.txt';
    $date = date('Y-m-d H:i:s');
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'Unknown IP';
    $logEntry = "Link created: $date - IP: $ipAddress - URL: $url\n";
    file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
}

function validateURL($url) {
    // Check if the URL is well-formed
    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        logInvalidURL($url, 'URL is not well-formed.');
        return false;
    }

    // Parse the URL to get the scheme
    $urlComponents = parse_url($url);
    if (!in_array($urlComponents['scheme'], ['http', 'https'])) {
        logInvalidURL($url, 'URL must start with http:// or https://.');
        return false;
    }

    // Check for disallowed keywords in a case-insensitive manner
    $disallowed_keywords = ['wp-login', 'admin', 'login', 'register', 'sql', 'script', 'javascript', 'albertaev.ca/link'];
    foreach ($disallowed_keywords as $keyword) {
        if (stripos($url, $keyword) !== false) {
            logInvalidURL($url, 'URL contains blacklisted words.');
            return false;
        }
    }

    // If all checks pass, the URL is valid
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
