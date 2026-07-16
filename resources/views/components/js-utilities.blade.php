
<style>
    @keyframes slideIn {
        from { transform: translateX(100%); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }
    .animate-slide-in {
        animation: slideIn 0.3s ease-out;
    }
</style>

<script>
    /**
     * Copy text to clipboard with fallback and notification
     */
    function copyToClipboard(text, label = 'Information') {
        if (!navigator.clipboard) {
            fallbackCopyToClipboard(text, label);
            return;
        }
        navigator.clipboard.writeText(text).then(function() {
            showNotification('success', `${label} copied to clipboard!`);
        }, function(err) {
            console.error('Could not copy text: ', err);
            fallbackCopyToClipboard(text, label);
        });
    }

    function fallbackCopyToClipboard(text, label = 'Information') {
        const textArea = document.createElement("textarea");
        textArea.value = text;
        textArea.style.top = "0";
        textArea.style.left = "0";
        textArea.style.position = "fixed";
        textArea.style.opacity = "0";
        document.body.appendChild(textArea);
        textArea.focus();
        textArea.select();
        try {
            const successful = document.execCommand('copy');
            if (successful) {
                showNotification('success', `${label} copied to clipboard!`);
            } else {
                showNotification('error', `Failed to copy ${label.toLowerCase()}.`);
            }
        } catch (err) {
            console.error('Fallback: Oops, unable to copy', err);
            showNotification('error', `Failed to copy ${label.toLowerCase()}.`);
        }
        document.body.removeChild(textArea);
    }

    /**
     * Show a global toast notification
     */
    function showNotification(type, message) {
        const notification = $(`
            <div class="fixed top-4 right-4 z-[100] animate-slide-in">
                <div class="px-4 py-3 rounded-lg shadow-lg flex items-center gap-3 ${
                    type === 'success' ? 'bg-green-500' : 'bg-red-500'
                } text-white">
                    <i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'}"></i>
                    <span class="text-sm">${message}</span>
                    <button onclick="$(this).closest('.fixed').remove()" class="ml-4 text-white hover:text-gray-200" aria-label="Dismiss notification" title="Dismiss">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
        `);
        $('body').append(notification);
        setTimeout(() => {
            notification.fadeOut(300, function() { $(this).remove(); });
        }, 3000);
    }
</script>
