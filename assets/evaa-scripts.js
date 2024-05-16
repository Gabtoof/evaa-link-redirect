document.addEventListener('DOMContentLoaded', function() {
    const copyButton = document.getElementById('copyButton');
    const copyMessage = document.getElementById('copyMessage');
    const autoCopyCheckbox = document.getElementById('autoCopy');

    function copyToClipboard(url) {
        navigator.clipboard.writeText(url).then(function() {
            copyMessage.style.display = 'block';
            setTimeout(function() {
                copyMessage.style.display = 'none';
            }, 3000);
        }, function(err) {
            console.error('Async: Could not copy text:', err);
        });
    }

   
        let url = document.querySelector('#resultUrl').href;
        copyToClipboard(url);
   


    if (copyButton) {
        copyButton.addEventListener('click', function() {
            let url = document.querySelector('#resultUrl').href;
            copyToClipboard(url);
        });
    }
});
