<script>
    function copyAccountNumber(accountNumber, buttonId) {
        navigator.clipboard.writeText(accountNumber).then(function() {
            const button = document.getElementById(buttonId);
            const originalSvg = button.innerHTML;
            
            button.classList.add('copied');
            button.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>';
            
            setTimeout(function() {
                button.classList.remove('copied');
                button.innerHTML = originalSvg;
            }, 2000);
        }).catch(function(err) {
            const textArea = document.createElement('textarea');
            textArea.value = accountNumber;
            textArea.style.position = 'fixed';
            textArea.style.left = '-999999px';
            document.body.appendChild(textArea);
            textArea.select();
            try {
                document.execCommand('copy');
                const button = document.getElementById(buttonId);
                const originalSvg = button.innerHTML;
                button.classList.add('copied');
                button.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>';
                setTimeout(function() {
                    button.classList.remove('copied');
                    button.innerHTML = originalSvg;
                }, 2000);
            } catch (err) {
                console.error('Failed to copy:', err);
            }
            document.body.removeChild(textArea);
        });
    }
</script>

