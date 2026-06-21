<div id="ai-chat-widget" class="fixed bottom-6 right-6 z-50">
    <!-- Chat Toggle Button -->
    <button id="chat-toggle" aria-label="Open AI Support Chat" aria-expanded="false" aria-controls="chat-window" class="w-14 h-14 bg-blue-600 text-white rounded-full shadow-2xl flex items-center justify-center hover:bg-blue-700 transition-all duration-300 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-2">
        <i class="fas fa-comment-dots text-2xl" aria-hidden="true"></i>
    </button>

    <!-- Chat Window -->
    <div id="chat-window" role="dialog" aria-label="AI Support Chat Window" class="hidden absolute bottom-16 right-0 w-80 sm:w-96 bg-white rounded-2xl shadow-2xl border border-gray-200 flex flex-col overflow-hidden transition-all duration-300 origin-bottom-right transform scale-95 opacity-0">
        <!-- Header -->
        <div class="bg-blue-600 p-4 text-white flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-white/20 rounded-full flex items-center justify-center">
                    <i class="fas fa-robot" aria-hidden="true"></i>
                </div>
                <div>
                    <h3 class="font-bold text-sm">System Support</h3>
                    <p class="text-[10px] text-blue-100 flex items-center gap-1">
                        <span class="w-2 h-2 bg-green-400 rounded-full animate-pulse" aria-hidden="true"></span>
                        AI Agent Online 24/7
                    </p>
                </div>
            </div>
            <button id="close-chat" aria-label="Close chat" class="text-white/80 hover:text-white focus:outline-none focus-visible:ring-2 focus-visible:ring-white rounded">
                <i class="fas fa-times" aria-hidden="true"></i>
            </button>
        </div>

        <!-- Messages Area -->
        <div id="chat-messages" aria-live="polite" class="flex-1 p-4 overflow-y-auto max-h-96 space-y-4 bg-gray-50 min-h-[300px]">
            <!-- Messages will be loaded here -->
        </div>

        <!-- Input Area -->
        <div class="p-4 bg-white border-t border-gray-100">
            <form id="chat-form" class="flex gap-2">
                <input type="text" id="chat-input" aria-label="Type your message" placeholder="Ask me anything..." class="flex-1 bg-gray-100 border-none rounded-xl px-4 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none" autocomplete="off">
                <button type="submit" aria-label="Send message" class="bg-blue-600 text-white w-10 h-10 rounded-xl flex items-center justify-center hover:bg-blue-700 transition focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500">
                    <i class="fas fa-paper-plane text-sm" aria-hidden="true"></i>
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
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .animate-fade-in {
        animation: fadeIn 0.3s ease-out forwards;
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
            chatToggle.setAttribute('aria-expanded', 'true');
            loadHistory();
        } else {
            chatWindow.classList.remove('chat-window-open');
            chatToggle.setAttribute('aria-expanded', 'false');
        }
    });

    closeChat.addEventListener('click', () => {
        chatWindow.classList.remove('chat-window-open');
        chatToggle.setAttribute('aria-expanded', 'false');
    });

    // Load Chat History
    function loadHistory() {
        if (chatMessages.children.length > 0) return; // Only load once

        fetch('/ai-support/history')
            .then(res => {
                if (!res.ok) throw new Error('Failed to load history');
                return res.json();
            })
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
            })
            .catch(err => {
                console.error('History load error:', err);
                appendMessage('ai', 'Welcome! How can I assist you today?');
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
        .then(res => {
            if (!res.ok) {
                return res.text().then(text => {
                    throw new Error(`HTTP ${res.status}: ${text || 'Request failed'}`);
                });
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
            if (chatMessages.contains(loadingDiv)) {
                chatMessages.removeChild(loadingDiv);
            }
            console.error('AI chat fetch failed:', err);
            appendMessage('ai', `Request failed: ${err?.message || 'Please check your network/server.'}`);
        });
    });
});
</script>
