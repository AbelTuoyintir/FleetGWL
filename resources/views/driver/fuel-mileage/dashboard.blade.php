@extends('layouts.driver')
@section('title', 'Driver Dashboard - Fuel & Mileage')

@section('content')
<style>
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
    .vehicle-card {
        background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
        border-radius: 20px;
    }
    .action-card {
        transition: all 0.2s ease;
        cursor: pointer;
        border: 1px solid #e2e8f0;
        background: white;
        border-radius: 16px;
    }
    .action-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1);
        border-color: #3b82f6;
    }
    .notification-bell {
        position: relative;
        cursor: pointer;
    }
    .notification-badge {
        position: absolute;
        top: -8px;
        right: -8px;
        background: #ef4444;
        color: white;
        font-size: 10px;
        font-weight: 600;
        padding: 2px 6px;
        border-radius: 20px;
        min-width: 18px;
        text-align: center;
    }
</style>

<div class="min-h-screen bg-gray-50 py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <!-- Welcome Section -->
        <div class="mb-8">
            <h1 class="text-2xl font-bold text-gray-800">Good {{ $greeting ?? 'Morning' }}, {{ $driver->user->name ?? $driver->name }}!</h1>
            <p class="text-gray-500 text-sm mt-1">Here's what's happening with your assigned vehicle.</p>
        </div>
        
        <!-- Vehicle Info Card -->
        <div class="vehicle-card text-white p-6 mb-8">
            <div class="flex flex-wrap justify-between items-center gap-4">
                <div class="flex items-center gap-4">
                    <div class="w-16 h-16 bg-blue-500/20 rounded-xl flex items-center justify-center">
                        <i class="fas fa-truck text-3xl text-blue-400"></i>
                    </div>
                    <div>
                        <p class="text-blue-300 text-sm">Your Assigned Vehicle</p>
                        <h2 class="text-2xl font-bold">{{ $vehicle->registration_number }}</h2>
                        <p class="text-gray-300 text-sm">{{ $vehicle->make }} {{ $vehicle->model }} ({{ $vehicle->year }})</p>
                    </div>
                </div>
                <div class="text-right">
                    <p class="text-sm text-gray-300">Current Mileage</p>
                    <p class="text-2xl font-bold">{{ number_format($vehicle->current_mileage ?? $vehicle->mileage ?? 0) }}</p>
                    <p class="text-xs text-gray-300">kilometers</p>
                </div>
            </div>
        </div>
        
        <!-- Stats Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 mb-8">
            <div class="stat-card p-5">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-xs uppercase">Monthly Distance</p>
                        <p class="text-2xl font-bold text-gray-800">{{ number_format($stats['monthly_distance'] ?? 0) }} km</p>
                    </div>
                    <i class="fas fa-road text-blue-400 text-3xl opacity-50"></i>
                </div>
            </div>
            <div class="stat-card p-5">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-xs uppercase">Maintenance Cost</p>
                        <p class="text-2xl font-bold text-gray-800">GHS {{ number_format($stats['monthly_maintenance_cost'] ?? 0, 2) }}</p>
                    </div>
                    <i class="fas fa-tools text-green-400 text-3xl opacity-50"></i>
                </div>
            </div>
            <div class="stat-card p-5">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-xs uppercase">Pending Requests</p>
                        <p class="text-2xl font-bold text-gray-800">{{ $stats['pending_requests_count'] ?? 0 }}</p>
                    </div>
                    <i class="fas fa-clock text-orange-400 text-3xl opacity-50"></i>
                </div>
            </div>
            <div class="stat-card p-5">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-xs uppercase">Total Maintenance</p>
                        <p class="text-2xl font-bold text-gray-800">GHS {{ number_format($stats['total_maintenance_expenditure'] ?? 0, 2) }}</p>
                    </div>
                    <i class="fas fa-chart-line text-purple-400 text-3xl opacity-50"></i>
                </div>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <h3 class="font-semibold text-gray-800 mb-4 flex items-center gap-2">
            <i class="fas fa-bolt text-yellow-500"></i> Quick Actions
        </h3>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
            <a href="{{ route('driver.fuel-mileage.maintenance.create') }}" class="action-card p-4 text-center">
                <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-3">
                    <i class="fas fa-tools text-blue-600 text-xl"></i>
                </div>
                <h4 class="font-semibold text-gray-800">Request Maintenance</h4>
                <p class="text-xs text-gray-500 mt-1">Submit service request</p>
            </a>
            <a href="{{ route('driver.fuel-mileage.mileage-logs.create') }}" class="action-card p-4 text-center">
                <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-3">
                    <i class="fas fa-road text-green-600 text-xl"></i>
                </div>
                <h4 class="font-semibold text-gray-800">Record Mileage</h4>
                <p class="text-xs text-gray-500 mt-1">Log weekly mileage</p>
            </a>
            <a href="{{ route('driver.fuel-mileage.quick-log') }}" class="action-card p-4 text-center">
                <div class="w-12 h-12 bg-orange-100 rounded-full flex items-center justify-center mx-auto mb-3">
                    <i class="fas fa-bolt text-orange-600 text-xl"></i>
                </div>
                <h4 class="font-semibold text-gray-800">Quick Log</h4>
                <p class="text-xs text-gray-500 mt-1">Fast entry</p>
            </a>
            <a href="{{ route('driver.fuel-mileage.reports') }}" class="action-card p-4 text-center">
                <div class="w-12 h-12 bg-purple-100 rounded-full flex items-center justify-center mx-auto mb-3">
                    <i class="fas fa-chart-bar text-purple-600 text-xl"></i>
                </div>
                <h4 class="font-semibold text-gray-800">Reports</h4>
                <p class="text-xs text-gray-500 mt-1">View analytics</p>
            </a>
        </div>
        
        <!-- Recent Activity -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Recent Maintenance Requests -->
            <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                <div class="px-6 py-4 bg-gray-50 border-b border-gray-200 flex justify-between items-center">
                    <h3 class="font-semibold text-gray-800">
                        <i class="fas fa-tools text-blue-600 mr-2"></i>Recent Maintenance Requests
                    </h3>
                    <a href="{{ route('driver.fuel-mileage.maintenance.index') }}" class="text-blue-600 text-sm">View all</a>
                </div>
                <div class="divide-y divide-gray-200">
                    @forelse($recentMaintenances as $maintenance)
                    <div class="p-4 hover:bg-gray-50">
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="font-medium text-gray-800">{{ ucfirst($maintenance->maintenance_type) }}</p>
                                <p class="text-sm text-gray-500">{{ $maintenance->maintenance_date->format('M d, Y') }}</p>
                            </div>
                            <span class="px-2 py-1 rounded-full text-xs 
                                {{ $maintenance->status == 'completed' ? 'bg-green-100 text-green-700' : 
                                   ($maintenance->status == 'waiting' ? 'bg-yellow-100 text-yellow-700' : 'bg-gray-100 text-gray-700') }}">
                                {{ ucfirst($maintenance->status) }}
                            </span>
                        </div>
                        @if($maintenance->cost > 0)
                        <p class="text-sm text-gray-600 mt-1">Cost: GHS {{ number_format($maintenance->cost, 2) }}</p>
                        @endif
                    </div>
                    @empty
                    <div class="p-8 text-center text-gray-500">No maintenance records yet</div>
                    @endforelse
                </div>
            </div>
            
            <!-- Recent Mileage Logs -->
            <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                <div class="px-6 py-4 bg-gray-50 border-b border-gray-200 flex justify-between items-center">
                    <h3 class="font-semibold text-gray-800">
                        <i class="fas fa-road text-green-600 mr-2"></i>Recent Mileage Logs
                    </h3>
                    <a href="{{ route('driver.fuel-mileage.mileage-logs.index') }}" class="text-blue-600 text-sm">View all</a>
                </div>
                <div class="divide-y divide-gray-200">
                    @forelse($recentMileageLogs as $log)
                    <div class="p-4 hover:bg-gray-50">
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="font-medium text-gray-800">{{ $log->week_label }}</p>
                                <p class="text-sm text-gray-500">{{ number_format($log->distance_covered) }} km covered</p>
                            </div>
                            @if($log->service_alert)
                            <span class="px-2 py-1 rounded-full text-xs bg-red-100 text-red-700">
                                <i class="fas fa-bell mr-1"></i>Alert
                            </span>
                            @endif
                        </div>
                    </div>
                    @empty
                    <div class="p-8 text-center text-gray-500">No mileage logs yet</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function getTimeGreeting() {
        const hour = new Date().getHours();
        if (hour < 12) return 'Morning';
        if (hour < 18) return 'Afternoon';
        return 'Evening';
    }
    document.addEventListener('DOMContentLoaded', function() {
        const greeting = getTimeGreeting();
        const greetingElement = document.querySelector('.text-2xl.font-bold');
        if (greetingElement) {
            greetingElement.innerHTML = greetingElement.innerHTML.replace('Morning', greeting);
        }
    });
</script>
@endsection