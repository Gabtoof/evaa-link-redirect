<?php
function evaa_redirect_shortcode() {
    $output = '';

    if (isset($_GET['url'])) {
        $redirectUrl = $_GET['url'];
        if (filter_var($redirectUrl, FILTER_VALIDATE_URL) && validateURL($redirectUrl)) {
            if (!isWithinRateLimit('redirect')) {
                return "<p style='font-size: 18px; color: red;'><strong>⚠️ Rate limit exceeded. Please try again later.</strong></p>";
            }
            logAllRedirections($redirectUrl);
            $output .= "<p>You will be redirected to <a href='" . esc_url($redirectUrl) . "' style='color: blue;'>" . esc_html($redirectUrl) . "</a> in 2 seconds...</p>";
            $output .= "<script>setTimeout(function() { window.location.href = '" . esc_url($redirectUrl) . "'; }, 2000);</script>";
            return $output;
        } else {
            $output .= "<p style='font-size: 18px; color: red;'>⚠️ Invalid URL. Please check your link and try again.</p>";
        }
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['url']) && isWithinRateLimit('creation')) {
        $url = $_POST['url'];
        if (validateURL($url)) {
            logLinkCreation($url);
            $fullUrl = esc_url('https://albertaev.ca/link?' . http_build_query(['url' => $url]));

            $output .= '<div id="evaaLinkContainer" style="background-color: #f0f0f0; padding: 20px; margin-top: 10px;">
                <p>Here\'s your link: <a href="' . $fullUrl . '" target="_blank" id="resultUrl" style="color: blue;">' . $fullUrl . '</a></p>
                <p id="copyMessage" style="color: green; display: none;">Link has been copied to your clipboard.</p>
                <button id="copyButton" style="background-color: green; color: white; padding: 8px; border: none; cursor: pointer;">Copy</button>
                <hr style="background-color: white; height: 2px; width: 100%; position: absolute; bottom: -1px;">
            </div>';

            // Ensure the script is only enqueued when the shortcode is processed
            if (!wp_script_is('evaa-scripts', 'enqueued')) {
                wp_enqueue_script('evaa-scripts', plugin_dir_url(__FILE__) . 'assets/evaa-scripts.js', array(), '1.0.8', true);
            }
        } else {
            $output .= "<p style='font-size: 18px; color: red;'>Sorry, something appears wrong with the link. Please ensure you are submitting a valid link.</p>";
        }
    } else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $output .= "<p style='font-size: 18px; color: red;'>⚠️ Rate limit exceeded. Please try again later.</p>";
    }

    $output .= '<form action="" method="post" style="background-color: #f0f0f0; padding: 20px;">
    <input type="text" name="url" placeholder="Paste your http(s) link here" style="padding: 8px; border: 1px solid black; width: 100%; box-sizing: border-box;" required>
    <button type="submit" style="background-color: green; color: white; padding: 8px; border: none; width: auto; display: block; margin: 10px 0 10px 0;">Submit Link</button>
    <br><strong>Note:</strong> New URL will be copied to clipboard automatically
    </form>';

    return $output;
}
add_shortcode('evaa_link_redirect', 'evaa_redirect_shortcode');
?>
