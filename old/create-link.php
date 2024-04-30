<?php
function logInvalidURL($url, $reason) {
    $logFile = 'invalid_url_log.txt'; // Ensure this file is in a writable directory
    $date = date('Y-m-d H:i:s');

    // Initialize IP address with a fallback
    $ipAddress = 'Unknown IP';

    // Safely check for X-Forwarded-For and fall back to REMOTE_ADDR
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ipList = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        $ipAddress = trim($ipList[0]); // Use the first IP if there are multiple
    } elseif (!empty($_SERVER['REMOTE_ADDR'])) {
        $ipAddress = $_SERVER['REMOTE_ADDR'];
    }

    $logEntry = "$date - IP: $ipAddress - Creation: Invalid URL detected: $url - Reason: $reason\n";

    // Attempt to write to the log file, checking for success
    if (file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX) === false) {
        // Optionally handle the error, such as sending an email to an administrator
        error_log("Failed to write to log file: $logFile");
    }
}

function validateURL($url) {
    // Checks remain the same as your last working version
    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        logInvalidURL($url, 'URL is not well-formed.');
        return false;
    }
    if (strpos($url, ' ') !== false) {
        logInvalidURL($url, 'URL has spaces.');
        return false;
    }
    $allowed_protocols = ['http', 'https'];
    $parsed_url = parse_url($url);
    if (!isset($parsed_url['scheme']) || !in_array($parsed_url['scheme'], $allowed_protocols)) {
        logInvalidURL($url, 'URL does not start with http/https.');
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

// Function to check if the request is within the rate limit ($lastRequest < nn where nn is seconds)
function isWithinRateLimit() {
    $logFile = 'evaa_link_redirect_rate_limit_check-create_link.txt';
    $lastRequestTime = file_exists($logFile) ? file_get_contents($logFile) : 0;
    $currentTime = time();
    if ($currentTime - $lastRequestTime < 15) {
        logInvalidURL($_POST['search_query'], 'Rate limit exceeded.');
        return false;
    } else {
        file_put_contents($logFile, $currentTime);
        return true;
    }
}

// Form handling code
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['search_query'])) {
    if (isWithinRateLimit() && validateURL($_POST['search_query'])) {
        $redirectURL = "https://albertaev.ca/link?url=" . urlencode($_POST['search_query']);
        echo "Redirect URL: <input id='urlBox' type='text' value='" . htmlspecialchars($redirectURL) . "' readonly style='width: 100%; max-width: 600px;'>";
        echo "<button onclick='copyToClipboard()' style='cursor:pointer; background-color: #4CAF50; color: white; border: none; padding: 10px 20px; text-align: center; display: inline-block; font-size: 16px;'>Copy URL</button>";
        echo "<p id='copyNotification' style='color: green;'></p>";
        echo "<script>
        window.onload = function() {
            function copyToClipboard() {
                var copyText = document.getElementById('urlBox');
                copyText.select();
                document.execCommand('copy');
                document.getElementById('copyNotification').textContent = 'Link has been copied to your clipboard.';
            }
            copyToClipboard(); // Auto copy when the page is fully loaded
        }
        </script>";
    } else {
        echo "Sorry, we detected issues with that link and cannot continue OR this service is currently in high demand. Try back in a few moments after verifying a valid link.";
    }
} else {
    // Display the form if not submitted or on first load
?>
<form method="post">
    <label for="search_query">Original Link (must start with https://):</label>
    <input type="text" id="search_query" name="search_query" required style="width: 100%; max-width: 600px;">
    <button type="submit" style="cursor:pointer; background-color: #008CBA; color: white; border: none; padding: 10px 20px; text-align: center; display: inline-block; font-size: 16px;">Generate Link</button>
</form>
<?php
}
?>