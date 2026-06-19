<div id="ai-chat-widget" class="fixed bottom-6 right-6 z-50">
    <!-- Chat Toggle Button -->
    <button id="chat-toggle" class="w-14 h-14 bg-blue-600 text-white rounded-full shadow-2xl flex items-center justify-center hover:bg-blue-700 transition-all duration-300 focus:outline-none">
        <i class="fas fa-comment-dots text-2xl"></i>
    </button>

    <!-- Chat Window -->
    <div id="chat-window" class="hidden absolute bottom-16 right-0 w-80 sm:w-96 bg-white rounded-2xl shadow-2xl border border-gray-200 flex flex-col overflow-hidden transition-all duration-300 origin-bottom-right transform scale-95 opacity-0">
        <!-- Header -->
        <div class="bg-blue-600 p-4 text-white flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-white/20 rounded-full flex items-center justify-center">
                    <i class="fas fa-robot"></i>
                </div>
                <div>
                    <h3 class="font-bold text-sm">System Support</h3>
                    <p class="text-[10px] text-blue-100 flex items-center gap-1">
                        <span class="w-2 h-2 bg-green-400 rounded-full animate-pulse"></span>
                        AI Agent Online 24/7
                    </p>
                </div>
            </div>
            <button id="close-chat" class="text-white/80 hover:text-white">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <!-- Messages Area -->
        <div id="chat-messages" class="flex-1 p-4 overflow-y-auto max-h-96 space-y-4 bg-gray-50 min-h-[300px]">
            <!-- Messages will be loaded here -->
        </div>

        <!-- Input Area -->
        <div class="p-4 bg-white border-t border-gray-100">
            <form id="chat-form" class="flex gap-2">
                <input type="text" id="chat-input" placeholder="Ask me anything..." class="flex-1 bg-gray-100 border-none rounded-xl px-4 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none" autocomplete="off">
                <button type="submit" class="bg-blue-600 text-white w-10 h-10 rounded-xl flex items-center justify-center hover:bg-blue-700 transition">
                    <i class="fas fa-paper-plane text-sm"></i>
                </button>
            </form>
        </div>
    </div>
</div>

<style>
    .chat-window-open {
        display: flex !important;
        transform: scale(1) !important;
        opacity: 1 !important;
    }
    .message-bubble {
        max-width: 85%;
        padding: 10px 14px;
        border-radius: 18px;
        font-size: 13px;
        line-height: 1.5;
    }
    .user-message {
        background: #2563eb;
        color: white;
        margin-left: auto;
        border-bottom-right-radius: 4px;
    }
    .ai-message {
        background: white;
        color: #374151;
        border-bottom-left-radius: 4px;
        border: 1px solid #e5e7eb;
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const chatToggle = document.getElementById('chat-toggle');
    const chatWindow = document.getElementById('chat-window');
    const closeChat = document.getElementById('close-chat');
    const chatForm = document.getElementById('chat-form');
    const chatInput = document.getElementById('chat-input');
    const chatMessages = document.getElementById('chat-messages');

    // Toggle Chat
    chatToggle.addEventListener('click', () => {
        const isOpen = chatWindow.classList.contains('chat-window-open');
        if (!isOpen) {
            chatWindow.classList.add('chat-window-open');
            loadHistory();
        } else {
            chatWindow.classList.remove('chat-window-open');
        }
    });

    closeChat.addEventListener('click', () => {
        chatWindow.classList.remove('chat-window-open');
    });

    // Load Chat History
    function loadHistory() {
        if (chatMessages.children.length > 0) return; // Only load once

        fetch('/ai-support/history')
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    if (data.history.length === 0) {
                        appendMessage('ai', 'Hello! I am your Ghana Water Limited Fleet Support AI. How can I help you today?');
                    } else {
                        data.history.forEach(msg => {
                            appendMessage(msg.sender_type, msg.message);
                        });
                    }
                }
            });
    }

    // Append Message to UI
    function appendMessage(sender, text) {
        const div = document.createElement('div');
        div.className = `message-bubble ${sender}-message animate-fade-in`;
        div.innerText = text;
        chatMessages.appendChild(div);
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }

    // Handle Form Submit
    chatForm.addEventListener('submit', (e) => {
        e.preventDefault();
        const message = chatInput.value.trim();
        if (!message) return;

        appendMessage('user', message);
        chatInput.value = '';

        // Add loading state
        const loadingDiv = document.createElement('div');
        loadingDiv.className = 'message-bubble ai-message italic text-gray-400';
        loadingDiv.innerText = 'Typing...';
        chatMessages.appendChild(loadingDiv);
        chatMessages.scrollTop = chatMessages.scrollHeight;

        fetch('/ai-support/chat', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({ message: message })
        })
        .then(async (res) => {
            if (!res.ok) {
                const text = await res.text().catch(() => '');
                throw new Error(`HTTP ${res.status}: ${text || 'Request failed'}`);
            }
            return res.json();
        })
        .then(data => {
            chatMessages.removeChild(loadingDiv);
            if (data.status === 'success') {
                appendMessage('ai', data.ai_message);
            } else {
                appendMessage('ai', data.message || 'Sorry, I encountered an error. Please try again.');
            }
        })
        .catch((err) => {
            chatMessages.removeChild(loadingDiv);
            console.error('AI chat fetch failed:', err);
            appendMessage('ai', `Request failed: ${err?.message || 'Please check your network/server.'}`);
        });
    });
});
</script>
