// JavaScript for handling front-end logic
document.addEventListener('DOMContentLoaded', function() {
    const copyButton = document.querySelector('button[onclick="copyToClipboard()"]');
    if (copyButton) {
        copyButton.addEventListener('click', function() {
            var copyText = document.getElementById('resultUrl').textContent;
            navigator.clipboard.writeText(copyText);
        });
    }
});
