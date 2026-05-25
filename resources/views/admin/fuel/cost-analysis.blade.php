@extends('layouts.app')
@section('title', 'Fuel Cost Analysis - GWL')
@section('content')
    
    <style>
        * { font-family: 'Inter', sans-serif; }
        body { background: #f1f5f9; }
        
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
        
        .sidebar-fleet {
            position: fixed; top: 0; left: 0;
            height: 100vh; width: 280px;
            background: white;
            border-right: 1px solid #e2e8f0;
            z-index: 40;
            transition: transform 0.25s ease;
            overflow-y: auto;
        }
        .sidebar-closed { transform: translateX(-100%); }
        @media (min-width: 1024px) { .sidebar-fleet { transform: translateX(0); } }
        
        .nav-item-fleet {
            transition: all 0.2s;
            border-radius: 10px;
            cursor: pointer;
        }
        .nav-item-fleet:hover { background-color: #f1f5f9; color: #1e40af; }
        .nav-active-fleet { background-color: #eff6ff; color: #2563eb; font-weight: 500; border-left: 3px solid #3b82f6; }
        
        .overlay-fleet {
            position: fixed; inset: 0;
            background: rgba(0,0,0,0.3);
            backdrop-filter: blur(2px);
            z-index: 35;
            display: none;
        }
        .overlay-open { display: block; }
        
        .form-input {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.2s;
        }
        .form-input:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 2px rgba(59,130,246,0.1);
        }
        .form-label {
            font-size: 13px;
            font-weight: 500;
            color: #374151;
            margin-bottom: 4px;
            display: block;
        }
        
        .loading-spinner {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 2px solid #e2e8f0;
            border-radius: 50%;
            border-top-color: #3b82f6;
            animation: spin 0.6s linear infinite;
        }
        @keyframes spin { to { transform: rotate(360deg); } }
        
        .trend-up { color: #ef4444; }
        .trend-down { color: #10b981; }
        
        /* Modal styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        .modal.active {
            display: flex;
        }
        .modal-content {
            background: white;
            border-radius: 16px;
            max-width: 800px;
            width: 90%;
            max-height: 85vh;
            overflow-y: auto;
        }
        
        @media print {
            .sidebar-fleet, .no-print, header, .filter-section, .action-buttons {
                display: none !important;
            }
            main { margin-left: 0 !important; padding: 0 !important; }
        }
    </style>
</head>
<body>

<!-- Mobile Overlay -->
<div id="mobileOverlay" class="overlay-fleet"></div>

<!-- Sidebar -->
<aside id="fleetSidebar" class="sidebar-fleet sidebar-closed lg:translate-x-0 shadow-xl flex flex-col">
    <div class="p-5 border-b border-slate-200 flex items-center justify-between">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 bg-blue-800 rounded-xl flex items-center justify-center">
                <i class="fas fa-chart-line text-white text-xl"></i>
            </div>
            <div>
                <h1 class="font-bold text-lg text-gray-800">Fleet<span class="text-blue-700">Pilot</span></h1>
                <p class="text-[10px] text-gray-500">Cost Analysis</p>
            </div>
        </div>
        <button id="closeSidebarBtn" class="lg:hidden text-gray-500"><i class="fas fa-times"></i></button>
    </div>
    
    <nav class="flex-1 px-3 py-5 space-y-1 overflow-y-auto">
        <a href="{{ route('dashboard') }}" class="nav-item-fleet flex items-center gap-3 px-3 py-2.5 rounded-lg">
            <i class="fas fa-chart-line w-5"></i><span>Dashboard</span>
        </a>
        <a href="{{ route('vehicles.index') }}" class="nav-item-fleet flex items-center gap-3 px-3 py-2.5 rounded-lg">
            <i class="fas fa-truck-moving w-5"></i><span>Vehicle Registry</span>
        </a>
        <a href="{{ route('fuel-management.index') }}" class="nav-item-fleet flex items-center gap-3 px-3 py-2.5 rounded-lg">
            <i class="fas fa-gas-pump w-5"></i><span>Fuel Management</span>
        </a>
        <a href="#" class="nav-item-fleet nav-active-fleet flex items-center gap-3 px-3 py-2.5 rounded-lg">
            <i class="fas fa-coins w-5"></i><span>Cost Analysis</span>
        </a>
    </nav>
    
    <div class="p-4 border-t border-slate-200 text-[11px] text-gray-400 text-center">
        <i class="fas fa-coins mr-1"></i> FleetPilot Cost Analysis
    </div>
</aside>

<!-- Main Content -->
<main class="lg:ml-[280px] min-h-screen p-6">
    <div class="max-w-7xl mx-auto">
        <!-- Header -->
        <div class="mb-6 flex justify-between items-center flex-wrap gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Fuel Cost Analysis</h1>
                <p class="text-gray-500 text-sm mt-1">Comprehensive fuel cost tracking and analysis across your fleet</p>
            </div>
            <div class="flex gap-3 no-print">
                <button onclick="window.print()" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg text-sm hover:bg-gray-200 transition">
                    <i class="fas fa-print mr-1"></i>Print Report
                </button>
                <button id="export-data" class="px-4 py-2 bg-green-600 text-white rounded-lg text-sm hover:bg-green-700 transition">
                    <i class="fas fa-download mr-1"></i>Export Data
                </button>
            </div>
        </div>
        
        <!-- Filter Section -->
        <div class="bg-white rounded-lg shadow-sm p-5 mb-6 filter-section">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
                <div>
                    <label class="form-label text-xs">
                        <i class="fas fa-truck mr-1"></i>Vehicle
                    </label>
                    <select id="vehicle-filter" class="form-input">
                        <option value="">All Vehicles</option>
                        @foreach($vehicles as $vehicle)
                            <option value="{{ $vehicle->id }}">{{ $vehicle->registration_number }} - {{ $vehicle->make }} {{ $vehicle->model }}</option>
                        @endforeach
                    </select>
                </div>
                
                <div>
                    <label class="form-label text-xs">
                        <i class="fas fa-calendar mr-1"></i>Date From
                    </label>
                    <input type="date" id="date-from" value="{{ $dateFrom }}" class="form-input">
                </div>
                
                <div>
                    <label class="form-label text-xs">
                        <i class="fas fa-calendar mr-1"></i>Date To
                    </label>
                    <input type="date" id="date-to" value="{{ $dateTo }}" class="form-input">
                </div>
                
                <div>
                    <label class="form-label text-xs">
                        <i class="fas fa-tint mr-1"></i>Fuel Type
                    </label>
                    <select id="fuel-type-filter" class="form-input">
                        <option value="">All Types</option>
                        @foreach($fuelTypes as $type)
                            <option value="{{ $type }}">{{ ucfirst($type) }}</option>
                        @endforeach
                    </select>
                </div>
                
                <div class="flex items-end gap-2">
                    <button id="apply-filters" class="w-full px-4 py-2 bg-blue-600 text-white rounded-lg text-sm hover:bg-blue-700">
                        <i class="fas fa-chart-line mr-1"></i>Apply
                    </button>
                    <button id="reset-filters" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg text-sm hover:bg-gray-200">
                        <i class="fas fa-undo-alt"></i>
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Loading Indicator -->
        <div id="loading-overlay" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
            <div class="bg-white rounded-lg p-6 flex items-center gap-3">
                <div class="loading-spinner"></div>
                <span class="text-gray-700">Loading cost analysis data...</span>
            </div>
        </div>
        
        <!-- Summary Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 mb-6" id="summary-cards">
            <div class="stat-card p-4"><div class="loading-spinner"></div></div>
        </div>
        
        <!-- Charts Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
            <!-- Cost by Vehicle Chart -->
            <div class="bg-white rounded-lg shadow-sm p-5">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="font-semibold text-gray-800">
                        <i class="fas fa-chart-bar text-blue-600 mr-2"></i>Cost by Vehicle
                    </h3>
                    <button id="view-vehicle-details" class="text-xs text-blue-600 hover:text-blue-800 hidden">
                        View Details <i class="fas fa-arrow-right ml-1"></i>
                    </button>
                </div>
                <canvas id="cost-by-vehicle-chart" height="300"></canvas>
            </div>
            
            <!-- Cost by Month Chart -->
            <div class="bg-white rounded-lg shadow-sm p-5">
                <h3 class="font-semibold text-gray-800 mb-4">
                    <i class="fas fa-chart-line text-green-600 mr-2"></i>Monthly Cost Trend
                </h3>
                <canvas id="cost-by-month-chart" height="300"></canvas>
            </div>
            
            <!-- Daily Cost Trend -->
            <div class="bg-white rounded-lg shadow-sm p-5">
                <h3 class="font-semibold text-gray-800 mb-4">
                    <i class="fas fa-chart-line text-orange-600 mr-2"></i>Daily Cost Trend (Last 30 Days)
                </h3>
                <canvas id="daily-cost-chart" height="300"></canvas>
            </div>
            
            <!-- Cost by Fuel Type -->
            <div class="bg-white rounded-lg shadow-sm p-5">
                <h3 class="font-semibold text-gray-800 mb-4">
                    <i class="fas fa-chart-pie text-purple-600 mr-2"></i>Cost by Fuel Type
                </h3>
                <canvas id="cost-by-type-chart" height="300"></canvas>
            </div>
        </div>
        
        <!-- Cost Efficiency Rankings -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
            <!-- Most Cost Efficient -->
            <div class="bg-white rounded-lg shadow-sm p-5">
                <h3 class="font-semibold text-gray-800 mb-4">
                    <i class="fas fa-trophy text-yellow-500 mr-2"></i>Most Cost Efficient Vehicles
                    <span class="text-xs font-normal text-gray-500 ml-2">(Lowest GHS/km)</span>
                </h3>
                <div id="most-efficient-list" class="space-y-3">
                    <div class="text-center py-8 text-gray-500">No data available</div>
                </div>
            </div>
            
            <!-- Least Cost Efficient -->
            <div class="bg-white rounded-lg shadow-sm p-5">
                <h3 class="font-semibold text-gray-800 mb-4">
                    <i class="fas fa-exclamation-triangle text-red-500 mr-2"></i>Least Cost Efficient Vehicles
                    <span class="text-xs font-normal text-gray-500 ml-2">(Highest GHS/km)</span>
                </h3>
                <div id="least-efficient-list" class="space-y-3">
                    <div class="text-center py-8 text-gray-500">No data available</div>
                </div>
            </div>
        </div>
        
        <!-- Cost by Vehicle Table -->
        <div class="bg-white rounded-lg shadow-sm p-5 mb-6">
            <h3 class="font-semibold text-gray-800 mb-4">
                <i class="fas fa-table mr-2 text-gray-600"></i>Detailed Cost Breakdown by Vehicle
            </h3>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Vehicle</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Total Cost (GHS)</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Total Fuel (L)</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Distance (km)</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Avg Price/L</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Cost/km</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">% of Total</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="cost-table-body">
                        <tr><td colspan="8" class="text-center py-8 text-gray-500">Apply filters to view data</td</tr>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Weekly Comparison -->
        <div class="bg-white rounded-lg shadow-sm p-5">
            <h3 class="font-semibold text-gray-800 mb-4">
                <i class="fas fa-calendar-week mr-2 text-indigo-600"></i>Week-over-Week Comparison
            </h3>
            <div id="weekly-comparison" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="text-center py-8 text-gray-500 col-span-3">Loading...</div>
            </div>
        </div>
    </div>
</main>

<!-- Vehicle Details Modal -->
<div id="vehicleModal" class="modal">
    <div class="modal-content">
        <div class="p-6 border-b border-gray-200 flex justify-between items-center sticky top-0 bg-white">
            <h3 class="text-xl font-semibold text-gray-800">
                <i class="fas fa-truck text-blue-600 mr-2"></i>
                <span id="modal-vehicle-name">Vehicle Cost Details</span>
            </h3>
            <button onclick="closeVehicleModal()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        <div class="p-6" id="modal-content">
            <div class="loading-spinner"></div>
        </div>
    </div>
</div>

<script>
// Chart variables
let costByVehicleChart = null;
let costByMonthChart = null;
let dailyCostChart = null;
let costByTypeChart = null;

$(document).ready(function() {
    loadCostAnalysis();
    
    $('#apply-filters').click(function() {
        loadCostAnalysis();
    });
    
    $('#reset-filters').click(function() {
        $('#vehicle-filter').val('');
        $('#fuel-type-filter').val('');
        $('#date-from').val('');
        $('#date-to').val('');
        loadCostAnalysis();
    });
    
    $('#export-data').click(function() {
        exportData();
    });
    
    $('#view-vehicle-details').click(function() {
        let vehicleId = $('#vehicle-filter').val();
        if (vehicleId) {
            showVehicleDetails(vehicleId);
        }
    });
});

function loadCostAnalysis() {
    $('#loading-overlay').removeClass('hidden');
    
    let params = new URLSearchParams();
    if ($('#vehicle-filter').val()) params.append('vehicle_id', $('#vehicle-filter').val());
    if ($('#fuel-type-filter').val()) params.append('fuel_type', $('#fuel-type-filter').val());
    if ($('#date-from').val()) params.append('date_from', $('#date-from').val());
    if ($('#date-to').val()) params.append('date_to', $('#date-to').val());
    
    $.ajax({
        url: '{{ route("fuel.cost-analysis-data") }}?' + params.toString(),
        method: 'GET',
        success: function(response) {
            if (response.success) {
                renderSummaryCards(response.summary);
                renderCostByVehicleChart(response.cost_by_vehicle);
                renderCostByMonthChart(response.cost_by_month);
                renderDailyCostChart(response.daily_cost);
                renderCostByTypeChart(response.cost_by_fuel_type);
                renderCostTable(response.cost_by_vehicle);
                renderEfficiencyLists(response.cost_efficiency);
                renderWeeklyComparison(response.weekly_comparison, response.trends);
                
                // Show/hide vehicle details button
                if ($('#vehicle-filter').val()) {
                    $('#view-vehicle-details').removeClass('hidden');
                } else {
                    $('#view-vehicle-details').addClass('hidden');
                }
            }
        },
        error: function(xhr) {
            console.error('Error loading data:', xhr);
            Swal.fire('Error', 'Failed to load cost analysis data', 'error');
        },
        complete: function() {
            $('#loading-overlay').addClass('hidden');
        }
    });
}

function renderSummaryCards(summary) {
    let html = `
        <div class="stat-card p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-xs uppercase">Total Cost</p>
                    <p class="text-2xl font-bold text-gray-800">GHS ${(summary.total_cost || 0).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}</p>
                    <p class="text-xs text-gray-500">${summary.total_logs || 0} transactions</p>
                </div>
                <i class="fas fa-money-bill-wave text-green-400 text-3xl opacity-50"></i>
            </div>
        </div>
        <div class="stat-card p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-xs uppercase">Total Fuel</p>
                    <p class="text-2xl font-bold text-gray-800">${(summary.total_fuel || 0).toFixed(1)} L</p>
                    <p class="text-xs text-gray-500">${summary.unique_vehicles || 0} vehicles</p>
                </div>
                <i class="fas fa-tint text-blue-400 text-3xl opacity-50"></i>
            </div>
        </div>
        <div class="stat-card p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-xs uppercase">Total Distance</p>
                    <p class="text-2xl font-bold text-gray-800">${(summary.total_distance || 0).toLocaleString()} km</p>
                    <p class="text-xs text-gray-500">${summary.period_days || 0} days period</p>
                </div>
                <i class="fas fa-road text-orange-400 text-3xl opacity-50"></i>
            </div>
        </div>
        <div class="stat-card p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-xs uppercase">Avg Fuel Price</p>
                    <p class="text-2xl font-bold text-gray-800">GHS ${(summary.avg_fuel_price || 0).toFixed(2)}</p>
                    <p class="text-xs text-gray-500">per liter</p>
                </div>
                <i class="fas fa-chart-line text-purple-400 text-3xl opacity-50"></i>
            </div>
        </div>
        <div class="stat-card p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-xs uppercase">Cost per km</p>
                    <p class="text-2xl font-bold text-gray-800">GHS ${(summary.avg_cost_per_km || 0).toFixed(2)}</p>
                    <p class="text-xs text-gray-500">per kilometer</p>
                </div>
                <i class="fas fa-tachometer-alt text-red-400 text-3xl opacity-50"></i>
            </div>
        </div>
    `;
    $('#summary-cards').html(html);
}

function renderCostByVehicleChart(data) {
    if (costByVehicleChart) costByVehicleChart.destroy();
    
    let ctx = document.getElementById('cost-by-vehicle-chart').getContext('2d');
    let vehicles = data.slice(0, 8).map(item => item.vehicle_name.substring(0, 25));
    let costs = data.slice(0, 8).map(item => item.total_cost);
    
    costByVehicleChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: vehicles,
            datasets: [{
                label: 'Total Cost (GHS)',
                data: costs,
                backgroundColor: '#3b82f6',
                borderRadius: 8
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            scales: { y: { beginAtZero: true, title: { display: true, text: 'GHS' } } },
            plugins: { legend: { position: 'top' } }
        }
    });
}

function renderCostByMonthChart(data) {
    if (costByMonthChart) costByMonthChart.destroy();
    
    let ctx = document.getElementById('cost-by-month-chart').getContext('2d');
    let months = data.map(item => item.month_key);
    let costs = data.map(item => item.total_cost);
    
    costByMonthChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: months,
            datasets: [{
                label: 'Monthly Cost (GHS)',
                data: costs,
                borderColor: '#10b981',
                backgroundColor: 'rgba(16,185,129,0.1)',
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            scales: { y: { beginAtZero: true, title: { display: true, text: 'GHS' } } }
        }
    });
}

function renderDailyCostChart(data) {
    if (dailyCostChart) dailyCostChart.destroy();
    
    let ctx = document.getElementById('daily-cost-chart').getContext('2d');
    let dates = Object.keys(data);
    let costs = Object.values(data).map(item => item.cost);
    
    dailyCostChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: dates,
            datasets: [{
                label: 'Daily Cost (GHS)',
                data: costs,
                borderColor: '#f59e0b',
                backgroundColor: 'rgba(245,158,11,0.1)',
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            scales: { y: { beginAtZero: true, title: { display: true, text: 'GHS' } } }
        }
    });
}

function renderCostByTypeChart(data) {
    if (costByTypeChart) costByTypeChart.destroy();
    
    let ctx = document.getElementById('cost-by-type-chart').getContext('2d');
    let types = Object.keys(data);
    let costs = types.map(type => data[type].total_cost);
    let colors = types.map(type => data[type].color);
    
    costByTypeChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: types.map(t => t.toUpperCase()),
            datasets: [{
                data: costs,
                backgroundColor: colors,
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: { position: 'bottom' },
                tooltip: { callbacks: { label: (ctx) => `${ctx.label}: GHS ${ctx.raw.toLocaleString()}` } }
            }
        }
    });
}

function renderCostTable(data) {
    if (!data || data.length === 0) {
        $('#cost-table-body').html('<tr><td colspan="8" class="text-center py-8 text-gray-500">No data available</td></tr>');
        return;
    }
    
    let html = '';
    data.forEach(item => {
        html += `
            <tr class="hover:bg-gray-50">
                <td class="px-6 py-4 text-sm font-medium text-gray-900">${item.vehicle_name}</td>
                <td class="px-6 py-4 text-sm text-right font-semibold">GHS ${item.total_cost.toLocaleString(undefined, {minimumFractionDigits: 2})}</td>
                <td class="px-6 py-4 text-sm text-right">${item.total_fuel.toFixed(1)} L</td>
                <td class="px-6 py-4 text-sm text-right">${item.total_distance.toLocaleString()} km</td>
                <td class="px-6 py-4 text-sm text-right">GHS ${item.avg_fuel_price.toFixed(2)}</td>
                <td class="px-6 py-4 text-sm text-right font-medium ${item.cost_per_km > 2 ? 'text-red-600' : 'text-green-600'}">GHS ${item.cost_per_km.toFixed(2)}</td>
                <td class="px-6 py-4 text-sm text-right">${item.percentage}%</td>
                <td class="px-6 py-4 text-sm text-center">
                    <button onclick="showVehicleDetailsFromId(${item.vehicle_id || 0})" class="text-blue-600 hover:text-blue-800">
                        <i class="fas fa-chart-line"></i>
                    </button>
                </td>
            </tr>
        `;
    });
    $('#cost-table-body').html(html);
}

function renderEfficiencyLists(efficiency) {
    // Most efficient (lowest cost per km)
    let mostHtml = '';
    if (efficiency.most_efficient && efficiency.most_efficient.length > 0) {
        efficiency.most_efficient.forEach((item, index) => {
            let medal = index === 0 ? '🥇' : index === 1 ? '🥈' : index === 2 ? '🥉' : '📊';
            mostHtml += `
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                    <div class="flex items-center gap-3">
                        <span class="text-xl">${medal}</span>
                        <div>
                            <p class="font-medium text-gray-800">${item.vehicle_name}</p>
                            <p class="text-xs text-gray-500">${item.logs_count || 0} fuel records</p>
                        </div>
                    </div>
                    <div class="text-right">
                        <p class="text-xl font-bold text-green-600">GHS ${item.cost_per_km.toFixed(2)}</p>
                        <p class="text-xs text-gray-500">per km</p>
                    </div>
                </div>
            `;
        });
    } else {
        mostHtml = '<div class="text-center py-8 text-gray-500">No data available</div>';
    }
    $('#most-efficient-list').html(mostHtml);
    
    // Least efficient (highest cost per km)
    let leastHtml = '';
    if (efficiency.least_efficient && efficiency.least_efficient.length > 0) {
        efficiency.least_efficient.forEach((item) => {
            leastHtml += `
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                    <div>
                        <p class="font-medium text-gray-800">${item.vehicle_name}</p>
                        <p class="text-xs text-gray-500">${item.logs_count || 0} fuel records</p>
                    </div>
                    <div class="text-right">
                        <p class="text-xl font-bold text-red-600">GHS ${item.cost_per_km.toFixed(2)}</p>
                        <p class="text-xs text-orange-600">⚠️ Needs attention</p>
                    </div>
                </div>
            `;
        });
    } else {
        leastHtml = '<div class="text-center py-8 text-gray-500">No data available</div>';
    }
    $('#least-efficient-list').html(leastHtml);
}

function renderWeeklyComparison(comparison, trends) {
    let trendClass = comparison.trend === 'down' ? 'trend-down' : 'trend-up';
    let trendIcon = comparison.trend === 'down' ? 'fa-arrow-down' : 'fa-arrow-up';
    
    let html = `
        <div class="text-center p-4 bg-gray-50 rounded-lg">
            <p class="text-gray-600 text-sm">This Week</p>
            <p class="text-2xl font-bold text-gray-800">GHS ${(comparison.current_week_cost || 0).toLocaleString(undefined, {minimumFractionDigits: 2})}</p>
        </div>
        <div class="text-center p-4 bg-gray-50 rounded-lg">
            <p class="text-gray-600 text-sm">Previous Week</p>
            <p class="text-2xl font-bold text-gray-800">GHS ${(comparison.previous_week_cost || 0).toLocaleString(undefined, {minimumFractionDigits: 2})}</p>
        </div>
        <div class="text-center p-4 bg-gray-50 rounded-lg">
            <p class="text-gray-600 text-sm">Change</p>
            <p class="text-2xl font-bold ${trendClass}">
                <i class="fas ${trendIcon}"></i> ${Math.abs(comparison.cost_change_percent || 0)}%
            </p>
            <p class="text-xs text-gray-500">vs previous week</p>
        </div>
    `;
    
    // Add trend insight
    if (trends && trends.cost_change !== 0) {
        let trendText = trends.cost_change < 0 ? 'decreased' : 'increased';
        let trendColor = trends.cost_change < 0 ? 'text-green-600' : 'text-red-600';
        html += `
            <div class="col-span-3 mt-2 text-center text-sm">
                <span class="${trendColor}">
                    <i class="fas ${trends.cost_change < 0 ? 'fa-arrow-down' : 'fa-arrow-up'}"></i>
                    Cost ${trendText} by ${Math.abs(trends.cost_change)}% compared to previous period
                </span>
            </div>
        `;
    }
    
    $('#weekly-comparison').html(html);
}

function showVehicleDetails(vehicleId) {
    $('#vehicleModal').addClass('active');
    $('#modal-vehicle-name').text('Loading...');
    
    let params = new URLSearchParams();
    params.append('vehicle_id', vehicleId);
    if ($('#date-from').val()) params.append('date_from', $('#date-from').val());
    if ($('#date-to').val()) params.append('date_to', $('#date-to').val());
    
    $.ajax({
        url: '{{ route("fuel.cost-breakdown") }}?' + params.toString(),
        method: 'GET',
        success: function(response) {
            if (response.success) {
                renderVehicleModal(response);
            } else {
                $('#modal-content').html('<div class="text-center py-8 text-red-500">Failed to load vehicle details</div>');
            }
        },
        error: function() {
            $('#modal-content').html('<div class="text-center py-8 text-red-500">Error loading details</div>');
        }
    });
}

function showVehicleDetailsFromId(vehicleId) {
    showVehicleDetails(vehicleId);
}

function renderVehicleModal(data) {
    $('#modal-vehicle-name').text(data.vehicle.name);
    
    let monthlyHtml = '';
    data.monthly_breakdown.forEach(item => {
        monthlyHtml += `
            <tr class="border-b">
                <td class="p-2">${item.month}</td>
                <td class="p-2 text-right">GHS ${item.total_cost.toFixed(2)}</td>
                <td class="p-2 text-right">${item.total_fuel.toFixed(1)} L</td>
                <td class="p-2 text-right">${item.total_distance.toLocaleString()} km</td>
                <td class="p-2 text-right">GHS ${item.cost_per_km.toFixed(2)}</td>
            </tr>
        `;
    });
    
    let transactionsHtml = '';
    data.expensive_transactions.forEach(transaction => {
        transactionsHtml += `
            <tr class="border-b">
                <td class="p-2">${transaction.date}</td>
                <td class="p-2">${transaction.fuel_station || 'N/A'}</td>
                <td class="p-2 text-right">${transaction.fuel_quantity.toFixed(1)} L</td>
                <td class="p-2 text-right font-semibold">GHS ${transaction.fuel_cost.toFixed(2)}</td>
                <td class="p-2">${transaction.driver}</td>
            </tr>
        `;
    });
    
    let html = `
        <div class="space-y-6">
            <!-- Summary -->
            <div class="grid grid-cols-2 gap-4">
                <div class="bg-blue-50 p-3 rounded-lg text-center">
                    <p class="text-xs text-gray-600">Total Cost</p>
                    <p class="text-xl font-bold text-blue-700">GHS ${data.summary.total_cost.toFixed(2)}</p>
                </div>
                <div class="bg-green-50 p-3 rounded-lg text-center">
                    <p class="text-xs text-gray-600">Cost/km</p>
                    <p class="text-xl font-bold text-green-700">GHS ${data.summary.cost_per_km.toFixed(2)}</p>
                </div>
                <div class="bg-orange-50 p-3 rounded-lg text-center">
                    <p class="text-xs text-gray-600">Total Fuel</p>
                    <p class="text-xl font-bold text-orange-700">${data.summary.total_fuel.toFixed(1)} L</p>
                </div>
                <div class="bg-purple-50 p-3 rounded-lg text-center">
                    <p class="text-xs text-gray-600">Total Distance</p>
                    <p class="text-xl font-bold text-purple-700">${data.summary.total_distance.toLocaleString()} km</p>
                </div>
            </div>
            
            <!-- Monthly Breakdown -->
            <div>
                <h4 class="font-semibold text-gray-800 mb-3">Monthly Cost Breakdown</h4>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-gray-50">
                            <tr><th class="p-2 text-left">Month</th><th class="p-2 text-right">Cost</th><th class="p-2 text-right">Fuel</th><th class="p-2 text-right">Distance</th><th class="p-2 text-right">Cost/km</th></tr>
                        </thead>
                        <tbody>${monthlyHtml || '<tr><td colspan="5" class="text-center py-4 text-gray-500">No monthly data</td></tr>'}</tbody>
                    </table>
                </div>
            </div>
            
            <!-- Recent Transactions -->
            <div>
                <h4 class="font-semibold text-gray-800 mb-3">Recent Expensive Transactions</h4>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-gray-50">
                            <tr><th class="p-2 text-left">Date</th><th class="p-2 text-left">Station</th><th class="p-2 text-right">Fuel</th><th class="p-2 text-right">Cost</th><th class="p-2 text-left">Driver</th></tr>
                        </thead>
                        <tbody>${transactionsHtml || '<tr><td colspan="5" class="text-center py-4 text-gray-500">No transactions</td></tr>'}</tbody>
                    </table>
                </div>
            </div>
        </div>
    `;
    
    $('#modal-content').html(html);
}

function closeVehicleModal() {
    $('#vehicleModal').removeClass('active');
}

function exportData() {
    let params = new URLSearchParams();
    if ($('#vehicle-filter').val()) params.append('vehicle_id', $('#vehicle-filter').val());
    if ($('#fuel-type-filter').val()) params.append('fuel_type', $('#fuel-type-filter').val());
    if ($('#date-from').val()) params.append('date_from', $('#date-from').val());
    if ($('#date-to').val()) params.append('date_to', $('#date-to').val());
    params.append('export', 'csv');
    
    window.open('{{ route("fuel.cost-analysis-data") }}?' + params.toString(), '_blank');
}

// Sidebar functions
const sidebar = document.getElementById('fleetSidebar');
const overlay = document.getElementById('mobileOverlay');
const menuToggle = document.getElementById('menuToggleBtn');
const closeSidebar = document.getElementById('closeSidebarBtn');

if (menuToggle) {
    menuToggle.addEventListener('click', () => {
        sidebar?.classList.remove('sidebar-closed');
        overlay?.classList.add('overlay-open');
    });
}

if (closeSidebar) {
    closeSidebar.addEventListener('click', () => {
        sidebar?.classList.add('sidebar-closed');
        overlay?.classList.remove('overlay-open');
    });
}

if (overlay) {
    overlay.addEventListener('click', () => {
        sidebar?.classList.add('sidebar-closed');
        overlay.classList.remove('overlay-open');
    });
}
</script>
@endsection