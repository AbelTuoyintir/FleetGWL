{{-- resources/views/drivers/statistics.blade.php --}}
@extends('layouts.app')
@section('title', 'Driver Statistics - FleetPilot')
@section('content')

<style>
    * { font-family: 'Inter', sans-serif; }
    
    .stat-card {
        transition: all 0.2s ease;
        border: 1px solid #e2e8f0;
        background: white;
        border-radius: 12px;
    }
    .stat-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px -6px rgba(0,0,0,0.1);
    }
    
    .recent-driver-card {
        transition: all 0.2s ease;
        border: 1px solid #e2e8f0;
        background: white;
        border-radius: 12px;
    }
    .recent-driver-card:hover {
        transform: translateX(4px);
        border-left: 3px solid #3b82f6;
    }
    
    .data-table th {
        text-align: left;
        padding: 12px 8px;
        font-size: 12px;
        font-weight: 600;
        color: #475569;
        border-bottom: 1px solid #e2e8f0;
    }
    .data-table td {
        padding: 12px 8px;
        font-size: 13px;
        border-bottom: 1px solid #f1f5f9;
    }
    .data-table tr:hover { background-color: #f8fafc; }
    
    .driver-avatar {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
        font-size: 14px;
        color: white;
    }
    
    @media print {
        .no-print { display: none !important; }
        body { background: white; }
        .stat-card, .recent-driver-card { box-shadow: none !important; border: 1px solid #ddd; }
    }
</style>

<div class="min-h-screen bg-gray-50 py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <!-- Header -->
        <div class="mb-6 flex justify-between items-center flex-wrap gap-4">
            <div>
                <div class="flex items-center gap-3">
                    <a href="{{ route('drivers.index') }}" class="text-gray-500 hover:text-gray-700">
                        <i class="fas fa-arrow-left"></i>
                    </a>
                    <h1 class="text-2xl font-bold text-gray-800">Driver Statistics</h1>
                </div>
                <p class="text-gray-500 text-sm mt-1">Overview of driver metrics and performance</p>
            </div>
            <div class="flex gap-3 no-print">
                <button onclick="window.print()" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg text-sm hover:bg-gray-200 transition">
                    <i class="fas fa-print mr-1"></i> Print Report
                </button>
                <button id="exportStats" class="px-4 py-2 bg-green-600 text-white rounded-lg text-sm hover:bg-green-700 transition">
                    <i class="fas fa-download mr-1"></i> Export CSV
                </button>
                <button id="refreshBtn" class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm hover:bg-blue-700 transition">
                    <i class="fas fa-sync-alt mr-1"></i> Refresh
                </button>
            </div>
        </div>
        
        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 mb-8">
            <div class="stat-card p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-xs uppercase tracking-wide">Total Drivers</p>
                        <p class="text-3xl font-bold text-gray-800">{{ $totalDrivers }}</p>
                        <p class="text-xs text-gray-500 mt-1">active drivers</p>
                    </div>
                    <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-users text-blue-600 text-xl"></i>
                    </div>
                </div>
            </div>
            
            <div class="stat-card p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-xs uppercase tracking-wide">Assigned Drivers</p>
                        <p class="text-3xl font-bold text-green-600">{{ $assignedDrivers }}</p>
                        <p class="text-xs text-gray-500 mt-1">with vehicles assigned</p>
                    </div>
                    <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-car text-green-600 text-xl"></i>
                    </div>
                </div>
                <div class="mt-3">
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="bg-green-600 h-2 rounded-full" style="width: {{ $totalDrivers > 0 ? ($assignedDrivers / $totalDrivers) * 100 : 0 }}%"></div>
                    </div>
                    <p class="text-xs text-gray-500 mt-1">{{ $totalDrivers > 0 ? round(($assignedDrivers / $totalDrivers) * 100) : 0 }}% of total drivers</p>
                </div>
            </div>
            
            <div class="stat-card p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-xs uppercase tracking-wide">Unassigned Drivers</p>
                        <p class="text-3xl font-bold text-orange-600">{{ $unassignedDrivers }}</p>
                        <p class="text-xs text-gray-500 mt-1">waiting for vehicles</p>
                    </div>
                    <div class="w-12 h-12 bg-orange-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-user-slash text-orange-600 text-xl"></i>
                    </div>
                </div>
            </div>
            
            <div class="stat-card p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-xs uppercase tracking-wide">Available Vehicles</p>
                        <p class="text-3xl font-bold text-purple-600">{{ $vehiclesWithoutDrivers }}</p>
                        <p class="text-xs text-gray-500 mt-1">ready for assignment</p>
                    </div>
                    <div class="w-12 h-12 bg-purple-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-car-side text-purple-600 text-xl"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Driver Assignment Progress -->
        <div class="bg-white rounded-xl shadow-sm p-6 mb-8">
            <h3 class="font-semibold text-gray-800 mb-4 flex items-center gap-2">
                <i class="fas fa-chart-pie text-blue-600"></i>
                Driver Assignment Distribution
            </h3>
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <div>
                    <canvas id="assignmentChart" height="250"></canvas>
                </div>
                <div class="flex flex-col justify-center space-y-4">
                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                        <div class="flex items-center gap-3">
                            <div class="w-3 h-3 bg-green-500 rounded-full"></div>
                            <span class="text-sm text-gray-700">Assigned Drivers</span>
                        </div>
                        <span class="font-bold text-lg text-green-600">{{ $assignedDrivers }}</span>
                    </div>
                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                        <div class="flex items-center gap-3">
                            <div class="w-3 h-3 bg-orange-500 rounded-full"></div>
                            <span class="text-sm text-gray-700">Unassigned Drivers</span>
                        </div>
                        <span class="font-bold text-lg text-orange-600">{{ $unassignedDrivers }}</span>
                    </div>
                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                        <div class="flex items-center gap-3">
                            <div class="w-3 h-3 bg-purple-500 rounded-full"></div>
                            <span class="text-sm text-gray-700">Available Vehicles</span>
                        </div>
                        <span class="font-bold text-lg text-purple-600">{{ $vehiclesWithoutDrivers }}</span>
                    </div>
                    <div class="mt-2 p-3 bg-blue-50 rounded-lg">
                        <p class="text-sm text-blue-800">
                            <i class="fas fa-lightbulb mr-2"></i>
                            {{ $unassignedDrivers > 0 && $vehiclesWithoutDrivers > 0 ? 'You have both unassigned drivers and available vehicles. Consider assigning them to optimize fleet utilization.' : ($unassignedDrivers > 0 ? 'Some drivers are waiting for vehicle assignments.' : ($vehiclesWithoutDrivers > 0 ? 'Some vehicles are available for assignment.' : 'All drivers are properly assigned to vehicles.')) }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Recent Drivers -->
        <div class="bg-white rounded-xl shadow-sm overflow-hidden mb-8">
            <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
                <h3 class="font-semibold text-gray-800 flex items-center gap-2">
                    <i class="fas fa-clock text-blue-600"></i>
                    Recently Added Drivers
                </h3>
            </div>
            <div class="divide-y divide-gray-200">
                @forelse($recentDrivers as $driver)
                <div class="recent-driver-card p-4 flex items-center justify-between flex-wrap gap-4">
                    <div class="flex items-center gap-3">
                        @if($driver->user && $driver->user->avatar)
                            <img class="w-10 h-10 rounded-full object-cover" src="{{ Storage::url($driver->user->avatar) }}" alt="{{ $driver->user->first_name }}">
                        @else
                            <div class="driver-avatar" style="background: linear-gradient(135deg, #3b82f6, #1e40af);">
                                {{ strtoupper(substr($driver->user->first_name ?? $driver->name ?? 'D', 0, 1)) }}
                            </div>
                        @endif
                        <div>
                            <p class="font-medium text-gray-800">{{ $driver->user->first_name ?? '' }} {{ $driver->user->last_name ?? $driver->name ?? 'Unknown' }}</p>
                            <p class="text-xs text-gray-500">
                                <i class="fas fa-envelope mr-1"></i>{{ $driver->user->email ?? 'No email' }}
                                @if($driver->vehicle)
                                    <span class="mx-1">•</span>
                                    <i class="fas fa-car mr-1"></i>{{ $driver->vehicle->registration_number }}
                                @endif
                            </p>
                        </div>
                    </div>
                    <div class="flex items-center gap-4">
                        <div class="text-right">
                            <p class="text-xs text-gray-500">Added</p>
                            <p class="text-sm font-medium">{{ $driver->created_at->format('M d, Y') }}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-xs text-gray-500">Status</p>
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium {{ $driver->status == 'active' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                                <i class="fas {{ $driver->status == 'active' ? 'fa-check-circle' : 'fa-times-circle' }} mr-1"></i>
                                {{ ucfirst($driver->status) }}
                            </span>
                        </div>
                        <a href="{{ route('drivers.show', $driver) }}" class="text-blue-600 hover:text-blue-800 transition">
                            <i class="fas fa-eye"></i>
                        </a>
                    </div>
                </div>
                @empty
                <div class="p-8 text-center text-gray-500">
                    <i class="fas fa-users text-gray-300 text-4xl mb-3 block"></i>
                    <p>No drivers added yet</p>
                </div>
                @endforelse
            </div>
        </div>
        
        <!-- Assignment Recommendations -->
        @if($unassignedDrivers > 0 && $vehiclesWithoutDrivers > 0)
        <div class="bg-gradient-to-r from-blue-50 to-indigo-50 rounded-xl p-6 border border-blue-200">
            <div class="flex items-start gap-4">
                <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center flex-shrink-0">
                    <i class="fas fa-clipboard-list text-blue-600 text-xl"></i>
                </div>
                <div class="flex-1">
                    <h3 class="font-semibold text-gray-800 mb-2">Assignment Recommendations</h3>
                    <p class="text-sm text-gray-600 mb-3">
                        You have <strong class="text-orange-600">{{ $unassignedDrivers }}</strong> unassigned driver(s) and 
                        <strong class="text-purple-600">{{ $vehiclesWithoutDrivers }}</strong> available vehicle(s). 
                        Assigning drivers to vehicles will improve fleet utilization and tracking.
                    </p>
                    <div class="flex gap-3">
                        <a href="{{ route('drivers.index') }}" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg text-sm hover:bg-blue-700 transition">
                            <i class="fas fa-link mr-2"></i> Assign Drivers
                        </a>
                        <a href="{{ route('vehicles.index') }}" class="inline-flex items-center px-4 py-2 border border-blue-600 text-blue-600 rounded-lg text-sm hover:bg-blue-50 transition">
                            <i class="fas fa-plus mr-2"></i> View Vehicles
                        </a>
                    </div>
                </div>
            </div>
        </div>
        @endif
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    let assignmentChart = null;
    
    $(document).ready(function() {
        initChart();
        
        $('#refreshBtn').click(function() {
            location.reload();
        });
        
        $('#exportStats').click(function() {
            exportStatistics();
        });
    });
    
    function initChart() {
        const ctx = document.getElementById('assignmentChart').getContext('2d');
        
        assignmentChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Assigned Drivers', 'Unassigned Drivers'],
                datasets: [{
                    data: [{{ $assignedDrivers }}, {{ $unassignedDrivers }}],
                    backgroundColor: ['#10b981', '#f97316'],
                    borderWidth: 0,
                    hoverOffset: 10
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            font: { size: 12 },
                            padding: 15
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const total = {{ $assignedDrivers + $unassignedDrivers }};
                                const percentage = total > 0 ? ((context.raw / total) * 100).toFixed(1) : 0;
                                return `${context.label}: ${context.raw} (${percentage}%)`;
                            }
                        }
                    }
                },
                cutout: '60%'
            }
        });
    }
    
    function exportStatistics() {
        // Create CSV content
        let csvContent = "Driver Statistics Report\n\n";
        csvContent += `Generated on: ${new Date().toLocaleString()}\n\n`;
        csvContent += "Metric,Value\n";
        csvContent += `Total Drivers,${{{ $totalDrivers }}}\n`;
        csvContent += `Assigned Drivers,${{{ $assignedDrivers }}}\n`;
        csvContent += `Unassigned Drivers,${{{ $unassignedDrivers }}}\n`;
        csvContent += `Available Vehicles,${{{ $vehiclesWithoutDrivers }}}\n\n`;
        
        csvContent += "Recent Drivers\n";
        csvContent += "Name,Email,Status,Joined Date\n";
        
        @foreach($recentDrivers as $driver)
            csvContent += `{{ addslashes($driver->user->first_name ?? '') }} {{ addslashes($driver->user->last_name ?? '') }},{{ $driver->user->email ?? 'N/A' }},{{ ucfirst($driver->status) }},{{ $driver->created_at->format('Y-m-d') }}\n`;
        @endforeach
        
        // Download file
        const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        const url = URL.createObjectURL(blob);
        link.href = url;
        link.setAttribute('download', 'driver_statistics.csv');
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        URL.revokeObjectURL(url);
        
        Swal.fire({
            title: 'Export Complete',
            text: 'Driver statistics have been exported to CSV',
            icon: 'success',
            timer: 2000,
            showConfirmButton: false
        });
    }
</script>

@endsection