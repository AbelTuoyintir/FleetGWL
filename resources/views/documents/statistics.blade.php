@extends('layouts.app')
@section('title', 'Document Analytics - GWL')
@section('content')
<div class="space-y-6 text-sm">
    <!-- Header -->
    <div class="flex items-center gap-3">
        <a href="{{ route('vehicles.documents.index') }}" class="text-gray-500 hover:text-gray-700">
            <i class="fas fa-arrow-left"></i>
        </a>
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Document Insights & Statistics</h1>
            <p class="text-gray-500 text-xs mt-0.5">Overview of policy status, compliance rates, and type distribution</p>
        </div>
    </div>

    <!-- Quick Count Grid -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="glass-card rounded-2xl p-4 flex items-center justify-between shadow-sm">
            <div>
                <p class="text-gray-500 text-xs uppercase tracking-wide">Total Registered</p>
                <p class="text-3xl font-bold text-slate-800">{{ $stats['total'] }}</p>
            </div>
            <div class="w-10 h-10 bg-slate-100 text-slate-600 rounded-full flex items-center justify-center">
                <i class="fas fa-file-alt text-lg"></i>
            </div>
        </div>
        <div class="glass-card rounded-2xl p-4 flex items-center justify-between shadow-sm">
            <div>
                <p class="text-gray-500 text-xs uppercase tracking-wide">Active / Compliant</p>
                <p class="text-3xl font-bold text-green-700">{{ $stats['active'] }}</p>
            </div>
            <div class="w-10 h-10 bg-green-50 text-green-600 rounded-full flex items-center justify-center">
                <i class="fas fa-check-circle text-lg"></i>
            </div>
        </div>
        <div class="glass-card rounded-2xl p-4 flex items-center justify-between shadow-sm">
            <div>
                <p class="text-gray-500 text-xs uppercase tracking-wide">Expired / Lapsed</p>
                <p class="text-3xl font-bold text-red-700">{{ $stats['expired'] }}</p>
            </div>
            <div class="w-10 h-10 bg-red-50 text-red-600 rounded-full flex items-center justify-center">
                <i class="fas fa-exclamation-triangle text-lg"></i>
            </div>
        </div>
        <div class="glass-card rounded-2xl p-4 flex items-center justify-between shadow-sm">
            <div>
                <p class="text-gray-500 text-xs uppercase tracking-wide">Compliance Rate</p>
                <p class="text-3xl font-bold text-blue-700">
                    {{ $stats['total'] > 0 ? round(($stats['active'] / $stats['total']) * 100) : 0 }}%
                </p>
            </div>
            <div class="w-10 h-10 bg-blue-50 text-blue-600 rounded-full flex items-center justify-center">
                <i class="fas fa-percentage text-lg"></i>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Type Distribution -->
        <div class="bg-white rounded-2xl border border-slate-100 p-5 shadow-sm space-y-4">
            <h3 class="font-bold text-slate-800 border-b border-slate-50 pb-2">Document Type Distribution</h3>
            <div class="relative h-[300px] flex items-center justify-center">
                <canvas id="typeChart"></canvas>
            </div>
        </div>

        <!-- Monthly Uploads -->
        <div class="bg-white rounded-2xl border border-slate-100 p-5 shadow-sm space-y-4">
            <h3 class="font-bold text-slate-800 border-b border-slate-50 pb-2">Upload Trends ({{ date('Y') }})</h3>
            <div class="relative h-[300px]">
                <canvas id="trendChart"></canvas>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        // Document Types Chart (Pie/Doughnut)
        const typeCtx = document.getElementById('typeChart').getContext('2d');
        const typesData = @json($stats['by_type']);
        const typeLabels = Object.keys(typesData).map(k => k.replace('_', ' ').toUpperCase());
        const typeValues = Object.values(typesData);

        new Chart(typeCtx, {
            type: 'doughnut',
            data: {
                labels: typeLabels,
                datasets: [{
                    data: typeValues,
                    backgroundColor: [
                        '#3b82f6', '#10b981', '#f59e0b', '#ef4444', 
                        '#8b5cf6', '#ec4899', '#6366f1', '#14b8a6', 
                        '#f97316', '#64748b'
                    ],
                    borderWidth: 2,
                    borderColor: '#ffffff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right',
                        labels: {
                            boxWidth: 12,
                            font: { size: 11, family: 'Inter' }
                        }
                    }
                },
                cutout: '65%'
            }
        });

        // Upload Trends Chart (Bar)
        const trendCtx = document.getElementById('trendChart').getContext('2d');
        const trendsData = @json($stats['by_month']);
        
        // Prep months array
        const monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        const trendValues = Array(12).fill(0);
        
        Object.entries(trendsData).forEach(([monthNum, count]) => {
            trendValues[monthNum - 1] = count;
        });

        new Chart(trendCtx, {
            type: 'bar',
            data: {
                labels: monthNames,
                datasets: [{
                    label: 'Files Uploaded',
                    data: trendValues,
                    backgroundColor: 'rgba(59, 130, 246, 0.85)',
                    borderRadius: 6,
                    maxBarThickness: 30
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { stepSize: 1 }
                    }
                }
            }
        });
    });
</script>
@endsection
