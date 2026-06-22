@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="mb-6 flex items-center text-sm text-gray-500">
        <a href="{{ route('student.dashboard') }}" class="hover:text-blue-600">Dashboard</a>
        <i class="fas fa-chevron-right mx-2 text-[10px]"></i>
        <span class="text-gray-900 font-medium">{{ $course->title }}</span>
    </div>

    <div class="flex flex-col lg:flex-row gap-8">
        <!-- Main Content -->
        <div class="flex-1">
            <div class="bg-white p-8 rounded-xl shadow-sm border border-gray-100">
                <h1 class="text-3xl font-bold mb-6">{{ $module->title }}</h1>

                <div class="prose max-w-none mb-10 text-gray-700 leading-relaxed">
                    {!! nl2br(e($module->content)) !!}

                    <div class="mt-8 p-6 bg-blue-50 rounded-xl border border-blue-100">
                        <h4 class="font-bold text-blue-800 mb-2">Key Concepts to Remember:</h4>
                        <ul class="list-disc list-inside text-blue-700 space-y-1">
                            <li>Understanding the core architecture of the system.</li>
                            <li>Properly implementing the randomly selected question bank.</li>
                            <li>Adhering to the exam prerequisite logic.</li>
                        </ul>
                    </div>
                </div>

                <div class="flex justify-between items-center pt-6 border-t border-gray-100">
                    <div>
                        @if($lockedOut)
                            <div class="bg-red-100 text-red-700 px-4 py-2 rounded-lg text-sm font-bold flex items-center">
                                <i class="fas fa-lock mr-2"></i>
                                QUIZ LOCKED: You have failed 4 times. Review carefully.
                            </div>
                        @else
                            <div class="text-sm text-gray-500">
                                Fail count: <span class="font-bold text-gray-700">{{ $failCount }} / 4</span>
                            </div>
                        @endif
                    </div>

                    <a href="{{ $lockedOut ? '#' : route('quiz.module', [$course, $module]) }}"
                       class="px-8 py-3 rounded-lg font-bold text-white transition {{ $lockedOut ? 'bg-gray-400 cursor-not-allowed' : 'bg-blue-600 hover:bg-blue-700 shadow-lg hover:shadow-xl' }}">
                        Start Module Quiz (60 Qs)
                    </a>
                </div>
            </div>
        </div>

        <!-- AI Assistant Sidebar -->
        <div class="w-full lg:w-80 shrink-0">
            <div class="bg-gradient-to-br from-indigo-900 to-blue-900 rounded-xl shadow-xl overflow-hidden sticky top-8">
                <div class="p-4 bg-white/10 flex items-center border-b border-white/10">
                    <div class="w-8 h-8 rounded-full bg-cyan-400 flex items-center justify-center mr-3">
                        <i class="fas fa-robot text-indigo-900 text-sm"></i>
                    </div>
                    <span class="text-white font-bold tracking-wide">AI Concept Explainer</span>
                </div>
                <div class="p-5 flex flex-col h-[400px]">
                    <div id="aiChatBox" class="flex-1 overflow-y-auto mb-4 space-y-4 pr-2 custom-scrollbar">
                        <div class="text-blue-100 text-xs leading-relaxed opacity-70">
                            Hi! I'm your AI assistant. Ask me anything about this module or technical concepts you find difficult.
                        </div>
                    </div>

                    <div class="mt-auto">
                        <div class="relative">
                            <input type="text" id="aiConceptInput" placeholder="Ask a question..."
                                   class="w-full pl-4 pr-10 py-2 bg-white/10 border border-white/20 rounded-lg text-white placeholder-blue-300/50 focus:outline-none focus:ring-2 focus:ring-cyan-400 text-sm"
                                   onkeydown="if(event.key==='Enter') askAI()">
                            <button onclick="askAI()" id="aiAskBtn"
                                    class="absolute right-2 top-1.5 text-cyan-400 hover:text-cyan-300 transition disabled:opacity-50">
                                <i id="aiBtnText" class="fas fa-paper-plane"></i>
                                <i id="aiBtnLoader" class="fas fa-circle-notch fa-spin hidden"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function appendMessage(role, text) {
        const chatBox = document.getElementById('aiChatBox');
        const msgDiv = document.createElement('div');
        msgDiv.className = `animate-fadeIn ${role === 'user' ? 'text-right' : ''}`;

        const inner = document.createElement('div');
        inner.className = `inline-block p-3 rounded-xl text-sm ${role === 'user'
            ? 'bg-white/20 text-white rounded-br-none'
            : 'bg-cyan-400/10 text-blue-50 italic border-l-2 border-cyan-400 rounded-bl-none'}`;

        inner.innerText = text;
        msgDiv.appendChild(inner);
        chatBox.appendChild(msgDiv);
        chatBox.scrollTop = chatBox.scrollHeight;
    }

    function askAI() {
        const input = document.getElementById('aiConceptInput');
        const concept = input.value.trim();
        if (!concept) return;

        const btn = document.getElementById('aiAskBtn');
        const btnText = document.getElementById('aiBtnText');
        const loader = document.getElementById('aiBtnLoader');

        // Append user message
        appendMessage('user', concept);
        input.value = '';

        // Loading state
        btn.disabled = true;
        btnText.classList.add('hidden');
        loader.classList.remove('hidden');

        fetch('{{ route("ai.ask") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                concept: concept,
                context: '{{ $module->title }}'
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                appendMessage('ai', data.explanation);
            }
        })
        .catch(err => {
            console.error(err);
            appendMessage('ai', 'Sorry, I encountered an error connecting to the brain. Please try again.');
        })
        .finally(() => {
            btn.disabled = false;
            btnText.classList.remove('hidden');
            loader.classList.add('hidden');
        });
    }
</script>

<style>
    .custom-scrollbar::-webkit-scrollbar { width: 4px; }
    .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
    .custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(255, 255, 255, 0.1); border-radius: 10px; }

    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .animate-fadeIn {
        animation: fadeIn 0.4s ease forwards;
    }
</style>
@endsection
