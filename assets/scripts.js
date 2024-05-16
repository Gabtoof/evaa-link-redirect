function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(() => {
        alert('Link copied to clipboard!');
    }).catch(err => {
        console.error('Failed to copy: ', err);
    });
}

document.addEventListener('DOMContentLoaded', function() {
    const copyButton = document.getElementById('copyButton');
    if (copyButton) {
        copyButton.addEventListener('click', function() {
            const url = document.getElementById('resultUrl').href;
            copyToClipboard(url);
        });
    }
});
