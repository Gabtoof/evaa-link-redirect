<?php
/*
Template Name: EVAA Link Redirect Template
*/
get_header(); // Include the WordPress header
?>

<?php
// Set Cache-Control headers to prevent caching
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");
?>

<style>
    #main-content {
        padding-top: 100px; /* Adjust this value based on your header's height */
    }
</style>

<div id="main-content">
    <div class="container">
        <div id="content-area" class="clearfix">
            <div id="left-area">
                <?php
                while (have_posts()) : the_post(); // Start the WordPress loop
                    the_content(); // Display the content of the page
                endwhile;

                // Functions and main redirection logic
                function logInvalidURL($url, $reason) {
                    $logFile = 'invalid_url_log.txt';
                    $date = date('Y-m-d H:i:s');
                    $ipAddress = 'Unknown IP';
                    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                        $ipList = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
                        $ipAddress = trim($ipList[0]);
                    } elseif (!empty($_SERVER['REMOTE_ADDR'])) {
                        $ipAddress = $_SERVER['REMOTE_ADDR'];
                    }
                    $logEntry = "$date - IP: $ipAddress - Creation: Invalid URL detected: $url - Reason: $reason\n";
                    if (file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX) === false) {
                        error_log("Failed to write to log file: $logFile");
                    }
                }

                function validateURL($url) {
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

                function isWithinRateLimit() {
                    $logFile = 'evaa_link_redirect_rate_limit_check-redirect.txt';
                    $lastRequestTime = file_exists($logFile) ? file_get_contents($logFile) : 0;
                    $currentTime = time();
                    if ($currentTime - $lastRequestTime < 15) {
                        logInvalidURL($_GET['url'], 'Rate limit exceeded.');
                        return false;
                    } else {
                        file_put_contents($logFile, $currentTime);
                        return true;
                    }
                }

                if (isset($_GET['url']) && isWithinRateLimit() && validateURL($_GET['url'])) {
                    $url = urldecode($_GET['url']);
                    echo '<article><div class="entry-content"><p>You will be redirected to <a href="' . $url . '">' . $url . '</a> in 2 seconds..
..</p></div></article>';
                    echo "<script>setTimeout(function() { window.location.href = '" . $url . "'; }, 2000);</script>";
                } else {
                    echo '<article><div class="entry-content"><p>Sorry, but there seems to be an issue with that link OR this service is currently in high demand. Verify the link and retry in a few seconds.</p></div></article>';
                }
                ?>
            </div>
            <?php get_sidebar(); ?>
        </div>
    </div>
</div>

<?php get_footer(); // Include the WordPress footer ?>
