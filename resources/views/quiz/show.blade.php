<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $type === 'module' ? 'Module Quiz' : 'Course Exam' }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .no-select { user-select: none; }
        .quiz-container { max-width: 800px; margin: 2rem auto; }
        .sticky-timer { position: sticky; top: 0; z-index: 50; }
    </style>
</head>
<body class="bg-gray-50 no-select">

<header class="bg-white border-b border-gray-200 py-4 px-6 sticky-timer shadow-sm">
    <div class="max-w-4xl mx-auto flex justify-between items-center">
        <div>
            <h1 class="font-bold text-lg text-gray-900">{{ $type === 'module' ? $module->title : $course->title }}</h1>
            <p class="text-xs text-gray-500">{{ $type === 'module' ? 'Module Quiz' : 'Final Exam' }} • {{ $questions->count() }} Questions</p>
        </div>
        <div class="flex items-center gap-4">
            <div id="timer" class="font-mono text-xl font-bold text-blue-600 bg-blue-50 px-3 py-1 rounded border border-blue-100">
                00:00
            </div>
            <button onclick="confirmExit()" class="text-gray-400 hover:text-red-500 transition">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
    </div>
</header>

<main class="quiz-container px-4">
    <div id="warning-banner" class="hidden bg-red-600 text-white p-4 rounded-lg mb-6 text-center animate-pulse shadow-lg sticky top-20 z-40">
        <i class="fas fa-exclamation-triangle mr-2"></i>
        <strong>Warning:</strong> You switched tabs/windows. This action is logged. Please stay on this page to avoid disqualification.
    </div>

    <form action="{{ route('quiz.submit', [$course, $module]) }}" method="POST" id="quizForm">
        @csrf
        @foreach($questions as $index => $question)
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
                <div class="flex mb-4">
                    <span class="w-8 h-8 rounded-full bg-gray-100 text-gray-500 flex items-center justify-center mr-3 font-bold text-sm shrink-0">
                        {{ $index + 1 }}
                    </span>
                    <h3 class="text-lg font-medium text-gray-800 pt-1">
                        {{ $question->question_text }}
                    </h3>
                </div>

                <div class="space-y-3 ml-11">
                    @php $options = is_array($question->options) ? $question->options : json_decode($question->options, true); @endphp
                    @foreach($options as $option)
                        <label class="flex items-center p-3 border border-gray-100 rounded-lg cursor-pointer hover:bg-blue-50 hover:border-blue-200 transition group">
                            <input type="radio" name="answers[{{ $question->id }}]" value="{{ $option }}" required
                                   class="w-4 h-4 text-blue-600 border-gray-300 focus:ring-blue-500">
                            <span class="ml-3 text-gray-700 group-hover:text-blue-900">{{ $option }}</span>
                        </label>
                    @endforeach
                </div>
            </div>
        @endforeach

        <div class="flex justify-center pb-20">
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-12 rounded-xl shadow-lg transition-all transform hover:scale-105">
                Submit {{ $type === 'module' ? 'Quiz' : 'Exam' }}
            </button>
        </div>
    </form>
</main>

<script>
    // Simple Timer
    let seconds = 0;
    const timerElement = document.getElementById('timer');
    setInterval(() => {
        seconds++;
        const mins = Math.floor(seconds / 60).toString().padStart(2, '0');
        const secs = (seconds % 60).toString().padStart(2, '0');
        timerElement.innerText = `${mins}:${secs}`;
    }, 1000);

    function confirmExit() {
        if (confirm('Are you sure you want to exit? Your progress will not be saved.')) {
            window.location.href = '{{ route("student.dashboard") }}';
        }
    }

    // Prevent leaving page
    window.onbeforeunload = function() {
        return "Are you sure you want to leave? Your progress will be lost.";
    };

    document.getElementById('quizForm').onsubmit = function() {
        window.onbeforeunload = null;
    };

    // Tab Switch / Blur Detection
    let blurCount = 0;
    const banner = document.getElementById('warning-banner');

    window.addEventListener('blur', () => {
        blurCount++;
        banner.classList.remove('hidden');
        console.warn(`Proctoring: User blurred window (${blurCount} times)`);
    });

    window.addEventListener('focus', () => {
        setTimeout(() => {
            banner.classList.add('hidden');
        }, 3000);
    });

    // Prevent right click
    document.addEventListener('contextmenu', event => event.preventDefault());

    // Prevent keyboard shortcuts
    document.addEventListener('keydown', event => {
        if (event.ctrlKey && (event.key === 'c' || event.key === 'v' || event.key === 'u' || event.key === 'p')) {
            event.preventDefault();
            alert('Shortcuts are disabled during the quiz.');
        }
    });
</script>
</body>
</html>
