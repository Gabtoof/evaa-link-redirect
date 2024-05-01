<?php
// Shortcode implementation with form and processing, including conditional redirection
function evaa_redirect_shortcode() {
    $output = '';

    // Check if the 'url' query parameter is set and handle redirection
    if (isset($_GET['url'])) {
        $redirectUrl = $_GET['url'];
        if (filter_var($redirectUrl, FILTER_VALIDATE_URL)) {
            // Check rate limit for redirects before proceeding
            if (!isWithinRateLimit('redirect')) {
                return "<p>Rate limit exceeded. Please try again later.</p>";
            }
            logAllRedirections($redirectUrl); // Log the redirection action
            $output .= "<p>You will be redirected in 2 seconds...</p>";
            $output .= "<script>
                            setTimeout(function() {
                                window.location.href = '" . esc_url($redirectUrl) . "';
                            }, 2000);
                        </script>";
            return $output; // Return here to stop further processing if redirect is occurring
        } else {
            $output .= "<p>Invalid URL. Please check your link and try again.</p>";
        }
    }

    // Normal form processing for user submissions
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['url']) && isWithinRateLimit('creation')) {
        $url = $_POST['url'];
        if (validateURL($url)) {
            logLinkCreation($url); // Log the creation of the link
            // Create the full URL with the user's link as a query parameter
            $fullUrl = esc_url('https://albertaev.ca/link?' . http_build_query(['url' => $url]));

            $output .= '<div style="background-color: #f0f0f0; padding: 20px; margin-top: 10px; position: relative; padding-right: 0; padding-left: 0;">
            <p>Here\'s your link: <a href="' . $fullUrl . '" target="_blank"><strong id="resultUrl" style="color: black;">' . $fullUrl . '</strong></a></p>
            <button onclick="copyToClipboard()" style="background-color: green; color: white; padding: 8px; border: none; cursor: pointer;">Copy</button>
            <hr style="border: none; background-color: white; height: 2px; width: 100%; position: absolute; bottom: -1px; left: 0; right: 0;">
        </div>';

            $output .= "<script>
                            function copyToClipboard() {
                                var copyText = document.getElementById('resultUrl').textContent;
                                navigator.clipboard.writeText(copyText);
                            }
                            if (document.getElementById('autoCopy').checked) {
                                copyToClipboard();
                            }
                        </script>";
        } else {
            $output .= "<p>Sorry, something appears wrong with the link. Please ensure you are submitting a valid link.</p>";
        }
    } else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Handle the case when rate limit for creation is exceeded
        $output .= "<p>Rate limit exceeded for creating new links. Please try again later.</p>";
    }

    // Form for user to submit their URL
    $output .= '<form action="" method="post" style="background-color: #f0f0f0; padding: 20px;">
    <input type="text" name="url" placeholder="Enter your URL here" style="color: black; padding: 8px; border: 1px solid black;" required>
    <button type="submit" style="background-color: green; color: white; padding: 8px; border: none;">Submit Link</button>
    <br><input type="checkbox" id="autoCopy" checked style="margin-top: 10px;"> <label for="autoCopy" style="color: black;">Copy to clipboard</label>
</form>';

    return $output;
}

add_shortcode('evaa_link_redirect', 'evaa_redirect_shortcode');
?>
