@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-8">
        <h1 class="text-3xl font-bold">Student Dashboard</h1>
        <div class="bg-blue-100 text-blue-800 px-4 py-2 rounded-lg font-medium">
            Welcome back, {{ Auth::user()->name }}!
        </div>
    </div>

    @if(session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-6">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-6">
            {{ session('error') }}
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-8">
        <!-- Pictorial Learning Curve -->
        <div class="lg:col-span-2 bg-white p-6 rounded-xl shadow-sm border border-gray-100">
            <h2 class="text-xl font-semibold mb-4 flex items-center">
                <i class="fas fa-chart-line mr-2 text-blue-500"></i>
                Your Learning Curve
            </h2>
            <div class="h-64">
                <canvas id="learningCurveChart"></canvas>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
            <h2 class="text-xl font-semibold mb-4 flex items-center">
                <i class="fas fa-history mr-2 text-gray-500"></i>
                Recent Attempts
            </h2>
            <div class="space-y-4">
                @forelse($recentAttempts as $attempt)
                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                        <div>
                            <div class="font-medium text-sm">{{ $attempt->type === 'module' ? ($attempt->module->title ?? 'Module') : 'Final Exam' }}</div>
                            <div class="text-xs text-gray-500">{{ $attempt->created_at->diffForHumans() }}</div>
                        </div>
                        <div class="text-right">
                            <div class="font-bold {{ $attempt->status === 'passed' ? 'text-green-600' : 'text-red-600' }}">
                                {{ $attempt->score }}%
                            </div>
                            <div class="text-[10px] uppercase tracking-wider text-gray-400">{{ $attempt->status }}</div>
                        </div>
                    </div>
                @empty
                    <p class="text-gray-500 text-sm text-center py-4">No attempts yet. Start learning!</p>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Courses and Modules -->
    <h2 class="text-2xl font-bold mb-6">Available Courses</h2>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        @foreach($courses as $course)
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="p-6 bg-gradient-to-r from-blue-600 to-indigo-700 text-white">
                    <h3 class="text-xl font-bold mb-2">{{ $course->title }}</h3>
                    <p class="text-blue-100 text-sm">{{ $course->description }}</p>
                </div>
                <div class="p-6">
                    <div class="flex justify-between items-center mb-4">
                        <span class="text-sm font-semibold text-gray-500 uppercase tracking-wider">Modules</span>
                        <a href="{{ route('quiz.exam', $course) }}" class="text-sm bg-indigo-100 text-indigo-700 px-3 py-1 rounded-full font-medium hover:bg-indigo-200 transition">
                            Take Final Exam
                        </a>
                    </div>
                    <div class="space-y-3">
                        @foreach($course->modules as $module)
                            <a href="{{ route('learning.module', [$course, $module]) }}" class="flex items-center justify-between p-3 border border-gray-100 rounded-lg hover:bg-gray-50 transition group">
                                <div class="flex items-center">
                                    <div class="w-8 h-8 rounded-full bg-blue-50 text-blue-600 flex items-center justify-center mr-3 font-bold text-sm">
                                        {{ $loop->iteration }}
                                    </div>
                                    <span class="font-medium group-hover:text-blue-600">{{ $module->title }}</span>
                                </div>
                                <i class="fas fa-chevron-right text-gray-300 group-hover:text-blue-400"></i>
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const ctx = document.getElementById('learningCurveChart').getContext('2d');
        const data = @json($learningCurve);

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: data.map(d => d.date),
                datasets: [{
                    label: 'Score %',
                    data: data.map(d => d.score),
                    borderColor: '#3b82f6',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    tension: 0.4,
                    fill: true,
                    pointBackgroundColor: data.map(d => d.status === 'passed' ? '#10b981' : '#ef4444'),
                    pointRadius: 5
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100,
                        grid: {
                            color: '#f3f4f6'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const item = data[context.dataIndex];
                                return `Score: ${item.score}% (${item.label})`;
                            }
                        }
                    }
                }
            }
        });
    });
</script>
@endsection
