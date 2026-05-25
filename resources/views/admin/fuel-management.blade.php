@extends('layouts.app')
@section('title', 'Fuel Management - GWL')
@section('content')
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- Date Range Picker -->
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/moment/min/moment.min.js"></script>
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
    
    <style>
        * { font-family: 'Inter', sans-serif; }
        body { background: #f1f5f9; }
        
        .tab-active {
            border-bottom: 2px solid #2563eb;
            color: #1e40af;
            font-weight: 600;
        }
        
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
        
        .status-badge {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
        }
        .status-recorded { background: #fef3c7; color: #92400e; }
        .status-verified { background: #dcfce7; color: #166534; }
        .status-rejected { background: #fee2e2; color: #991b1b; }
        
        .fuel-type-badge {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 500;
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
            ring: 2px solid #3b82f6;
        }
        .form-label {
            font-size: 13px;
            font-weight: 500;
            color: #374151;
            margin-bottom: 4px;
            display: block;
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
            max-height: 90vh;
            overflow-y: auto;
        }

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
        
        @media print {
            .sidebar-fleet, .no-print, header, .filter-section, .action-buttons, .tab-buttons {
                display: none !important;
            }
            main { margin-left: 0 !important; padding: 0 !important; }
            .stat-card, .bg-white { box-shadow: none !important; border: 1px solid #ddd !important; }
            body { background: white !important; }
        }
    </style>
<!-- Main Content -->
<div class="min-h-screen">
    <div class="max-w-7xl mx-auto">
        <!-- Header -->
        <div class="mb-6 flex justify-between items-center flex-wrap gap-4">
            <div class="flex items-center gap-3">
                <button id="menuToggleBtn" class="lg:hidden p-2 text-gray-600 hover:text-blue-600">
                    <i class="fas fa-bars text-xl"></i>
                </button>
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">Fuel Management</h1>
                    <p class="text-gray-500 text-sm mt-1">Track fuel consumption, costs, and efficiency across your fleet</p>
                </div>
            </div>
            <div class="flex gap-3 no-print">
                <button onclick="openAddModal()" class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm hover:bg-blue-700 transition">
                    <i class="fas fa-plus mr-1"></i>Add Fuel Log
                </button>
                <button onclick="window.print()" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg text-sm hover:bg-gray-200 transition">
                    <i class="fas fa-print mr-1"></i>Print
                </button>
            </div>
        </div>
        
        <!-- Quick Stats Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 mb-6" id="quick-stats">
            <div class="stat-card p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-xs uppercase">Total Fuel</p>
                        <p class="text-2xl font-bold text-gray-800" id="total-fuel">0</p>
                        <p class="text-xs text-gray-500">Liters</p>
                    </div>
                    <i class="fas fa-tint text-blue-400 text-3xl opacity-50"></i>
                </div>
            </div>
            <div class="stat-card p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-xs uppercase">Total Cost</p>
                        <p class="text-2xl font-bold text-gray-800" id="total-cost">GHS 0</p>
                        <p class="text-xs text-gray-500">Ghana Cedis</p>
                    </div>
                    <i class="fas fa-money-bill-wave text-green-400 text-3xl opacity-50"></i>
                </div>
            </div>
            <div class="stat-card p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-xs uppercase">Total Distance</p>
                        <p class="text-2xl font-bold text-gray-800" id="total-distance">0</p>
                        <p class="text-xs text-gray-500">Kilometers</p>
                    </div>
                    <i class="fas fa-road text-orange-400 text-3xl opacity-50"></i>
                </div>
            </div>
            <div class="stat-card p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-xs uppercase">Avg Efficiency</p>
                        <p class="text-2xl font-bold text-gray-800" id="avg-efficiency">0</p>
                        <p class="text-xs text-gray-500">km/Liter</p>
                    </div>
                    <i class="fas fa-tachometer-alt text-purple-400 text-3xl opacity-50"></i>
                </div>
            </div>
            <div class="stat-card p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-xs uppercase">Cost per km</p>
                        <p class="text-2xl font-bold text-gray-800" id="avg-cost-km">GHS 0</p>
                        <p class="text-xs text-gray-500">per kilometer</p>
                    </div>
                    <i class="fas fa-chart-line text-red-400 text-3xl opacity-50"></i>
                </div>
            </div>
        </div>
        
        <!-- Tabs -->
        <div class="bg-white rounded-t-xl border-b border-gray-200 px-6">
            <div class="flex space-x-8 overflow-x-auto">
                <button data-tab="logs" class="tab-btn py-4 text-sm font-medium text-gray-600 hover:text-blue-600 transition tab-active">
                    <i class="fas fa-list-ul mr-2"></i>Fuel Logs
                </button>
                <button data-tab="analytics" class="tab-btn py-4 text-sm font-medium text-gray-600 hover:text-blue-600 transition">
                    <i class="fas fa-chart-line mr-2"></i>Consumption Analytics
                </button>
            </div>
        </div>
        
        <div class="bg-white rounded-b-xl shadow-sm p-6">
            <!-- Tab 1: Fuel Logs -->
            <div id="logs-tab" class="tab-content">
                <!-- Filters -->
                <div class="mb-6 flex flex-wrap gap-4 items-center justify-between">
                    <div class="flex flex-wrap gap-3">
                        <select id="filter-vehicle" class="form-input w-48 text-sm">
                            <option value="">All Vehicles</option>
                            @foreach($vehicles as $vehicle)
                                <option value="{{ $vehicle->id }}">{{ $vehicle->registration_number }} - {{ $vehicle->make }} {{ $vehicle->model }}</option>
                            @endforeach
                        </select>
                        <select id="filter-fuel-type" class="form-input w-32 text-sm">
                            <option value="">All Types</option>
                            <option value="petrol">Petrol</option>
                            <option value="diesel">Diesel</option>
                            <option value="electric">Electric</option>
                        </select>
                        <select id="filter-status" class="form-input w-32 text-sm">
                            <option value="">All Status</option>
                            <option value="recorded">Recorded</option>
                            <option value="verified">Verified</option>
                            <option value="rejected">Rejected</option>
                        </select>
                        <input type="text" id="date-range" class="form-input w-56 text-sm" placeholder="Select date range">
                    </div>
                    <div class="flex gap-2">
                        <button id="reset-filters" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg text-sm hover:bg-gray-200 transition">
                            <i class="fas fa-undo-alt mr-1"></i>Reset
                        </button>
                        <button id="export-logs" class="px-4 py-2 bg-green-600 text-white rounded-lg text-sm hover:bg-green-700 transition">
                            <i class="fas fa-download mr-1"></i>Export
                        </button>
                    </div>
                </div>
                
                <!-- Fuel Logs Table -->
                <div class="overflow-x-auto">
                    <table class="data-table w-full">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Vehicle</th>
                                <th>Driver</th>
                                <th>Odometer (km)</th>
                                <th>Fuel (L)</th>
                                <th>Cost (GHS)</th>
                                <th>Price/L</th>
                                <th>Type</th>
                                <th>Distance</th>
                                <th>Efficiency</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="fuel-logs-body">
                            @foreach($fuelLogs as $log)
                            <tr>
                                <td>{{ $log->date->format('Y-m-d') }}</td>
                                <td>
                                    <span class="font-medium">{{ $log->vehicle->registration_number ?? 'N/A' }}</span>
                                    <br><span class="text-xs text-gray-500">{{ $log->vehicle->make ?? '' }} {{ $log->vehicle->model ?? '' }}</span>
                                </td>
                                <td>{{ $log->driver->name ?? 'N/A' }}</td>
                                <td>{{ number_format($log->odometer) }}</td>
                                <td class="font-medium">{{ number_format($log->fuel_quantity, 1) }} L</td>
                                <td class="font-medium">GHS {{ number_format($log->fuel_cost, 2) }}</td>
                                <td>GHS {{ number_format($log->fuel_price_per_unit, 2) }}</td>
                                <td><span class="fuel-type-badge bg-blue-100 text-blue-700 px-2 py-1 rounded">{{ ucfirst($log->fuel_type) }}</span></td>
                                <td>{{ number_format($log->distance_traveled ?? 0) }} km</td>
                                <td>
                                    @if($log->fuel_efficiency)
                                        <span class="text-green-600 font-medium">{{ number_format($log->fuel_efficiency, 1) }} km/L</span>
                                    @else
                                        <span class="text-gray-400">--</span>
                                    @endif
                                </td>
                                <td><span class="status-badge status-{{ $log->status }}">{{ ucfirst($log->status) }}</span></td>
                                <td>
                                    <div class="flex gap-2">
                                        <button onclick="editLog({{ $log->id }})" class="text-blue-600 hover:text-blue-800">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button onclick="deleteLog({{ $log->id }})" class="text-red-600 hover:text-red-800">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <div class="mt-6">
                    {{ $fuelLogs->links() }}
                </div>
            </div>
            
            <!-- Tab 2: Consumption Analytics -->
            <div id="analytics-tab" class="tab-content hidden">
                <!-- Analytics Filters -->
                <div class="mb-6 flex flex-wrap gap-4 items-end">
                    <div class="flex-1 min-w-[200px]">
                        <label class="form-label text-xs">Vehicle</label>
                        <select id="analytics-vehicle" class="form-input">
                            <option value="">All Vehicles</option>
                            @foreach($vehicles as $vehicle)
                                <option value="{{ $vehicle->id }}">{{ $vehicle->registration_number }} - {{ $vehicle->make }} {{ $vehicle->model }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex-1 min-w-[250px]">
                        <label class="form-label text-xs">Date Range</label>
                        <input type="text" id="analytics-date-range" class="form-input" placeholder="Select date range">
                    </div>
                    <div>
                        <button id="update-analytics" class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm hover:bg-blue-700">
                            <i class="fas fa-chart-line mr-1"></i>Update Charts
                        </button>
                    </div>
                </div>
                
                <!-- Analytics Charts Grid -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                    <!-- Fuel Consumption Trend -->
                    <div class="border rounded-lg p-5">
                        <h3 class="font-semibold text-gray-800 mb-4">
                            <i class="fas fa-chart-line text-blue-600 mr-2"></i>Fuel Consumption Trend
                        </h3>
                        <canvas id="fuel-trend-chart" height="250"></canvas>
                    </div>
                    
                    <!-- Cost Trend -->
                    <div class="border rounded-lg p-5">
                        <h3 class="font-semibold text-gray-800 mb-4">
                            <i class="fas fa-chart-line text-green-600 mr-2"></i>Fuel Cost Trend
                        </h3>
                        <canvas id="cost-trend-chart" height="250"></canvas>
                    </div>
                    
                    <!-- Fuel by Type -->
                    <div class="border rounded-lg p-5">
                        <h3 class="font-semibold text-gray-800 mb-4">
                            <i class="fas fa-chart-pie text-purple-600 mr-2"></i>Fuel Distribution by Type
                        </h3>
                        <canvas id="fuel-type-chart" height="250"></canvas>
                    </div>
                    
                    <!-- Efficiency by Vehicle -->
                    <div class="border rounded-lg p-5">
                        <h3 class="font-semibold text-gray-800 mb-4">
                            <i class="fas fa-tachometer-alt text-orange-600 mr-2"></i>Fuel Efficiency by Vehicle
                        </h3>
                        <canvas id="efficiency-chart" height="250"></canvas>
                    </div>
                </div>
                
                <!-- Monthly Summary Table -->
                <div class="border rounded-lg p-5">
                    <h3 class="font-semibold text-gray-800 mb-4">
                        <i class="fas fa-table mr-2 text-gray-600"></i>Monthly Consumption Summary
                    </h3>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm" id="monthly-summary-table">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="p-3 text-left">Month</th>
                                    <th class="p-3 text-right">Fuel (L)</th>
                                    <th class="p-3 text-right">Cost (GHS)</th>
                                    <th class="p-3 text-right">Distance (km)</th>
                                    <th class="p-3 text-right">Efficiency (km/L)</th>
                                    <th class="p-3 text-right">Cost/km (GHS)</th>
                                </tr>
                            </thead>
                            <tbody id="monthly-summary-body">
                                <tr><td colspan="6" class="text-center py-8 text-gray-500">Select date range to view summary</td><tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add/Edit Fuel Log Modal -->
<div id="fuelLogModal" class="modal">
    <div class="modal-content">
        <div class="p-6 border-b border-gray-200 flex justify-between items-center">
            <h3 class="text-xl font-semibold text-gray-800">
                <i class="fas fa-gas-pump text-blue-600 mr-2"></i>
                <span id="modal-title">Add Fuel Log</span>
            </h3>
            <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        
        <form id="fuel-log-form" class="p-6 space-y-4">
            @csrf
            <input type="hidden" name="id" id="log-id">
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="relative">
                    <label class="form-label">Vehicle (Number Plate) *</label>
                    <input type="text" id="vehicle_plate" class="form-input w-full" placeholder="Enter registration number (e.g., GWL-001)">
                    <div id="search_results_dropdown" class="absolute z-50 w-full hidden"></div>
                    <input type="hidden" name="vehicle_id" id="vehicle_id">
                    <small id="vehicle_plate_help" class="text-xs text-gray-500 mt-1 block">
                        <i class="fas fa-info-circle mr-1"></i> Type at least 2 characters to search
                    </small>
                </div>

                <div class="md:col-span-2 hidden" id="vehicle_info_display">
                    <div class="bg-blue-50 border border-blue-100 rounded-lg p-3 flex items-start gap-3">
                        <div class="bg-blue-600 text-white p-2 rounded-lg shrink-0">
                            <i class="fas fa-car text-lg"></i>
                        </div>
                        <div class="flex-1">
                            <div class="flex justify-between items-start">
                                <div>
                                    <h4 class="font-bold text-blue-900" id="info_make_model">Vehicle Name</h4>
                                    <div class="flex gap-3 mt-1 text-xs text-blue-800">
                                        <span><i class="fas fa-calendar-alt mr-1 opacity-70"></i><span id="info_year">---</span></span>
                                        <span><i class="fas fa-palette mr-1 opacity-70"></i><span id="info_color">---</span></span>
                                    </div>
                                </div>
                                <span class="px-2 py-1 bg-blue-100 text-blue-700 rounded text-xs font-bold" id="info_plate">PLATE</span>
                            </div>
                            <div class="mt-2 pt-2 border-t border-blue-100 flex justify-between items-center text-xs text-blue-800">
                                <div><span class="opacity-70">Last Odometer:</span> <span class="font-bold" id="info_odo">0</span> km</div>
                                <div><span class="opacity-70">Driver:</span> <span class="font-bold" id="info_driver">Not Assigned</span></div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div>
                    <label class="form-label">Date *</label>
                    <input type="date" name="date" id="date" class="form-input" required>
                </div>
                
                <div>
                    <label class="form-label">Odometer (km) *</label>
                    <input type="number" name="odometer" id="odometer" class="form-input" step="1" required>
                </div>
                
                <div>
                    <label class="form-label">Fuel Quantity (L) *</label>
                    <input type="number" name="fuel_quantity" id="fuel_quantity" class="form-input" step="0.01" required>
                </div>
                
                <div>
                    <label class="form-label">Fuel Cost (GHS) *</label>
                    <input type="number" name="fuel_cost" id="fuel_cost" class="form-input" step="0.01" required>
                </div>
                
                <div>
                    <label class="form-label">Price per Unit (GHS/L)</label>
                    <input type="number" name="fuel_price_per_unit" id="fuel_price_per_unit" class="form-input" step="0.01" readonly>
                </div>
                
                <div>
                    <label class="form-label">Fuel Type *</label>
                    <select name="fuel_type" id="fuel_type" class="form-input" required>
                        <option value="petrol">Petrol</option>
                        <option value="diesel">Diesel</option>
                        <option value="electric">Electric</option>
                        <option value="hybrid">Hybrid</option>
                        <option value="cng">CNG</option>
                        <option value="lpg">LPG</option>
                    </select>
                </div>

                <div>
                    <label class="form-label">Driver</label>
                    <input type="text" id="driver_search" class="form-input" placeholder="Search driver by name">
                    <select name="driver_id" id="driver_id" class="form-input">
                        <option value="">Select Driver</option>
                        @foreach($drivers as $driver)
                            <option value="{{ $driver->user->id ?? $driver->id }}">
                                {{ $driver->user->name ?? $driver->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="form-label">Fuel Station</label>
                    <input type="text" name="fuel_station" id="fuel_station" class="form-input" placeholder="e.g., TotalEnergies">
                </div>
                
                <div>
                    <label class="form-label">Receipt Number</label>
                    <input type="text" name="receipt_number" id="receipt_number" class="form-input">
                </div>
                
                <div class="md:col-span-2">
                    <label class="form-label">Notes</label>
                    <textarea name="notes" id="notes" rows="3" class="form-input" placeholder="Additional notes..."></textarea>
                </div>
            </div>
            
            <div class="flex gap-2 mt-4">
                <label class="flex items-center gap-2">
                    <input type="checkbox" name="is_full_tank" value="1" id="is_full_tank">
                    <span class="text-sm">Full Tank</span>
                </label>
                <label class="flex items-center gap-2 ml-4">
                    <input type="checkbox" name="is_maintenance_fuel" value="1" id="is_maintenance_fuel">
                    <span class="text-sm">Maintenance Fuel</span>
                </label>
            </div>
            
            <div class="flex justify-end gap-3 pt-4 border-t">
                <button type="button" onclick="closeModal()" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                    Cancel
                </button>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                    <i class="fas fa-save mr-2"></i>Save Log
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Chart variables
let fuelTrendChart, costTrendChart, fuelTypeChart, efficiencyChart;
let fuelPlateDebounce = null;
let driverSearchDebounce = null;
let currentVehicleId = null;

// Initialize when page loads
$(document).ready(function() {
    initDatePickers();
    loadQuickStats();
    
    // Initialize date to today
    $('#date').val(new Date().toISOString().split('T')[0]);
    
    // Calculate price per unit automatically
    $('#fuel_cost, #fuel_quantity').on('input', function() {
        let cost = parseFloat($('#fuel_cost').val()) || 0;
        let quantity = parseFloat($('#fuel_quantity').val()) || 0;
        if (quantity > 0) {
            let pricePerUnit = cost / quantity;
            $('#fuel_price_per_unit').val(pricePerUnit.toFixed(2));
        } else {
            $('#fuel_price_per_unit').val('');
        }
    });
    
    // Form submission for fuel log
    $('#fuel-log-form').on('submit', function(e) {
        e.preventDefault();
        saveFuelLog();
    });
    
    // Close modal when clicking outside
    $(window).on('click', function(e) {
        if ($(e.target).hasClass('modal')) {
            closeModal();
        }
    });
    
    // Tab switching
    $('.tab-btn').click(function() {
        let tabId = $(this).data('tab');
        
        $('.tab-btn').removeClass('tab-active text-blue-600 border-blue-600').addClass('text-gray-600');
        $(this).addClass('tab-active text-blue-600 border-blue-600');
        
        $('.tab-content').addClass('hidden');
        $(`#${tabId}-tab`).removeClass('hidden');
        
        if (tabId === 'analytics') {
            loadAnalyticsData();
        }
    });
    
    // Filter listeners
    $('#filter-vehicle, #filter-fuel-type, #filter-status, #date-range').on('change', function() {
        applyFilters();
    });
    
    $('#reset-filters').click(function() {
        $('#filter-vehicle, #filter-fuel-type, #filter-status').val('');
        $('#date-range').val('');
        applyFilters();
    });
    
    // Analytics
    $('#update-analytics').click(() => loadAnalyticsData());
});

// Vehicle Search Functionality
$('#vehicle_plate').on('input', function() {
    clearTimeout(fuelPlateDebounce);
    let plate = $(this).val().trim();
    
    if (plate.length < 2) {
        $('#vehicle_id').val('');
        $('#vehicle_info_display').addClass('hidden');
        $('#search_results_dropdown').addClass('hidden').empty();
        $('#vehicle_plate_help').html('<i class="fas fa-info-circle mr-1"></i> Type at least 2 characters to search');
        return;
    }
    
    $('#vehicle_plate_help').html('<i class="fas fa-circle-notch fa-spin mr-1 text-blue-500"></i> Searching...');
    
    fuelPlateDebounce = setTimeout(() => {
        $.ajax({
            url: '{{ route("vehicles.search") }}',
            method: 'GET',
            data: { plate: plate },
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success && response.data) {
                    selectVehicleFromSearch(response.data);
                    $('#search_results_dropdown').addClass('hidden').empty();
                    $('#vehicle_plate_help').html('<span class="text-green-600"><i class="fas fa-check-circle mr-1"></i> Vehicle found</span>');
                } else if (response.vehicles && response.vehicles.length > 0) {
                    showVehicleDropdown(response.vehicles);
                    $('#vehicle_plate_help').html(`<span class="text-blue-600"><i class="fas fa-list mr-1"></i> ${response.vehicles.length} vehicles found. Click to select.</span>`);
                } else {
                    $('#vehicle_id').val('');
                    $('#vehicle_info_display').fadeOut(200, function() { $(this).addClass('hidden'); });
                    $('#search_results_dropdown').addClass('hidden').empty();
                    $('#vehicle_plate_help').html('<span class="text-amber-600"><i class="fas fa-exclamation-triangle mr-1"></i> No matching vehicle found</span>');
                }
            },
            error: function(xhr) {
                console.error('Search error:', xhr);
                $('#vehicle_id').val('');
                $('#vehicle_info_display').addClass('hidden');
                $('#search_results_dropdown').addClass('hidden').empty();
                $('#vehicle_plate_help').html('<span class="text-red-600"><i class="fas fa-times-circle mr-1"></i> Error searching for vehicle</span>');
            }
        });
    }, 500);
});

// Driver Search Functionality
$('#driver_search').on('input', function() {
    clearTimeout(driverSearchDebounce);
    let searchTerm = $(this).val().trim();
    
    if (searchTerm.length < 2) {
        $('#driver_id').val('');
        return;
    }
    
    driverSearchDebounce = setTimeout(() => {
        $.ajax({
            url: '{{ route("drivers.search") }}',
            method: 'GET',
            data: { search: searchTerm },
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success && response.drivers) {
                    updateDriverDropdown(response.drivers, searchTerm);
                }
            }
        });
    }, 300);
});

// Hide dropdown when clicking outside
$(document).on('click', function(e) {
    if (!$(e.target).closest('#vehicle_plate').length && !$(e.target).closest('#search_results_dropdown').length) {
        $('#search_results_dropdown').addClass('hidden');
    }
});

// ==================== HELPER FUNCTIONS ====================

function showVehicleDropdown(vehicles) {
    let dropdown = $('#search_results_dropdown');
    dropdown.empty();
    
    let html = '<div class="absolute z-50 w-full mt-1 bg-white border border-gray-200 rounded-lg shadow-lg max-h-60 overflow-y-auto">';
    vehicles.forEach(vehicle => {
        html += `
            <div class="px-4 py-2 hover:bg-blue-50 cursor-pointer border-b border-gray-100 last:border-0 transition" 
                 onclick="selectVehicleFromList(${vehicle.id}, '${vehicle.registration_number}', '${vehicle.make} ${vehicle.model}', '${vehicle.year}', '${vehicle.color || ''}')">
                <div class="font-medium text-gray-800">${vehicle.registration_number}</div>
                <div class="text-xs text-gray-500">${vehicle.make} ${vehicle.model} ${vehicle.year ? '(' + vehicle.year + ')' : ''}</div>
            </div>
        `;
    });
    html += '</div>';
    
    dropdown.html(html).removeClass('hidden');
}

function selectVehicleFromList(id, registrationNumber, makeModel, year, color) {
    currentVehicleId = id;
    $('#vehicle_id').val(id);
    $('#vehicle_plate').val(registrationNumber);
    
    $.ajax({
        url: '/vehicles/details/' + id,
        method: 'GET',
        success: function(response) {
            if (response.success) {
                updateVehicleInfoCard(response.data);
            }
        },
        error: function() {
            $('#info_make_model').text(makeModel || 'Unknown Vehicle');
            $('#info_year').text(year || 'N/A');
            $('#info_color').text(color || 'N/A');
            $('#info_plate').text(registrationNumber);
            $('#vehicle_info_display').removeClass('hidden').fadeIn(200);
        }
    });
    
    $('#search_results_dropdown').addClass('hidden');
}

function selectVehicleFromSearch(vehicle) {
    currentVehicleId = vehicle.id;
    $('#vehicle_id').val(vehicle.id);
    $('#vehicle_plate').val(vehicle.registration_number);
    updateVehicleInfoCard(vehicle);
}

function updateVehicleInfoCard(vehicle) {
    $('#info_make_model').text(vehicle.make_model || vehicle.make + ' ' + vehicle.model || 'Unknown Vehicle');
    $('#info_year').text(vehicle.year || 'N/A');
    $('#info_color').text(vehicle.color || 'N/A');
    $('#info_plate').text(vehicle.registration_number);
    
    let odometerValue = vehicle.current_odometer || 0;
    $('#info_odo').text(odometerValue.toLocaleString());
    
    if (vehicle.fuel_type) {
        $('#fuel_type').val(vehicle.fuel_type);
    }
    
    if (vehicle.driver_id) {
        $('#driver_id').val(vehicle.driver_id);
        $('#info_driver').text(vehicle.driver_name || 'Assigned');
        $('#driver_search').val(vehicle.driver_name || '');
    } else {
        $('#info_driver').text('Not Assigned');
    }
    
    let currentOdoInput = parseFloat($('#odometer').val()) || 0;
    if (currentOdoInput === 0 && odometerValue > 0) {
        $('#odometer').val(odometerValue);
    }
    
    $('#vehicle_info_display').removeClass('hidden').fadeIn(200);
}

function updateDriverDropdown(drivers, searchTerm) {
    let driverSelect = $('#driver_id');
    let currentValue = driverSelect.val();
    
    driverSelect.empty();
    driverSelect.append('<option value="">Select Driver</option>');
    
    drivers.forEach(driver => {
        let driverName = driver.user ? driver.user.name : driver.name;
        if (driverName.toLowerCase().includes(searchTerm.toLowerCase())) {
            driverSelect.append(`<option value="${driver.id}">${driverName}</option>`);
        }
    });
    
    if (currentValue && driverSelect.find(`option[value="${currentValue}"]`).length) {
        driverSelect.val(currentValue);
    }
}

// ==================== MODAL FUNCTIONS ====================

function openAddModal() {
    $('#modal-title').text('Add Fuel Log');
    $('#fuel-log-form')[0].reset();
    $('#log-id').val('');
    $('#vehicle_id').val('');
    $('#vehicle_plate').val('');
    $('#vehicle_info_display').addClass('hidden');
    $('#search_results_dropdown').addClass('hidden').empty();
    $('#date').val(new Date().toISOString().split('T')[0]);
    $('#fuel_price_per_unit').val('');
    $('#fuelLogModal').addClass('active');
}

function editLog(id) {
    $.ajax({
        url: '/fuel-management/' + id + '/edit-data',
        method: 'GET',
        success: function(response) {
            if (response.success) {
                $('#modal-title').text('Edit Fuel Log');
                $('#log-id').val(response.data.id);
                $('#vehicle_id').val(response.data.vehicle_id);
                $('#date').val(response.data.date);
                $('#odometer').val(response.data.odometer);
                $('#fuel_quantity').val(response.data.fuel_quantity);
                $('#fuel_cost').val(response.data.fuel_cost);
                $('#fuel_price_per_unit').val(response.data.fuel_price_per_unit);
                $('#fuel_type').val(response.data.fuel_type);
                $('#fuel_station').val(response.data.fuel_station);
                $('#receipt_number').val(response.data.receipt_number);
                $('#driver_id').val(response.data.driver_id);
                $('#notes').val(response.data.notes);
                $('#is_full_tank').prop('checked', response.data.is_full_tank == 1);
                $('#is_maintenance_fuel').prop('checked', response.data.is_maintenance_fuel == 1);
                
                if (response.data.vehicle_id) {
                    $.ajax({
                        url: '/vehicles/details/' + response.data.vehicle_id,
                        method: 'GET',
                        success: function(vehicleResponse) {
                            if (vehicleResponse.success) {
                                $('#vehicle_plate').val(vehicleResponse.data.registration_number);
                                updateVehicleInfoCard(vehicleResponse.data);
                            }
                        }
                    });
                }
                
                $('#fuelLogModal').addClass('active');
            }
        },
        error: function() {
            Swal.fire('Error', 'Failed to load fuel log data', 'error');
        }
    });
}

function closeModal() {
    $('#fuelLogModal').removeClass('active');
    $('#fuel-log-form')[0].reset();
    $('#vehicle_info_display').addClass('hidden');
    $('#search_results_dropdown').addClass('hidden').empty();
    $('#log-id').val('');
}

function deleteLog(id) {
    Swal.fire({
        title: 'Are you sure?',
        text: "This fuel log will be permanently deleted!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        confirmButtonText: 'Yes, delete it!'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: '/fuel-management/' + id,
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                success: function() {
                    Swal.fire('Deleted!', 'Fuel log has been deleted.', 'success');
                    location.reload();
                },
                error: function() {
                    Swal.fire('Error!', 'Failed to delete fuel log.', 'error');
                }
            });
        }
    });
}

// ==================== SAVE FUEL LOG (FIXED) ====================
function saveFuelLog() {
    let vehicleId = $('#vehicle_id').val();
    if (!vehicleId) {
        Swal.fire('Error', 'Please select a vehicle first', 'error');
        return;
    }
    
    let formData = {
        vehicle_id: vehicleId,
        date: $('#date').val(),
        odometer: $('#odometer').val(),
        fuel_quantity: $('#fuel_quantity').val(),
        fuel_cost: $('#fuel_cost').val(),
        fuel_price_per_unit: $('#fuel_price_per_unit').val(),
        fuel_type: $('#fuel_type').val(),
        fuel_station: $('#fuel_station').val(),
        receipt_number: $('#receipt_number').val(),
        driver_id: $('#driver_id').val(),
        notes: $('#notes').val(),
        is_full_tank: $('#is_full_tank').is(':checked') ? 1 : 0,
        is_maintenance_fuel: $('#is_maintenance_fuel').is(':checked') ? 1 : 0,
        _token: '{{ csrf_token() }}'
    };
    
    // Validate required fields
    if (!$('#date').val()) {
        Swal.fire('Error', 'Please select a date', 'error');
        return;
    }
    if (!$('#odometer').val()) {
        Swal.fire('Error', 'Please enter odometer reading', 'error');
        return;
    }
    if (!$('#fuel_quantity').val() || parseFloat($('#fuel_quantity').val()) <= 0) {
        Swal.fire('Error', 'Please enter a valid fuel quantity', 'error');
        return;
    }
    if (!$('#fuel_cost').val() || parseFloat($('#fuel_cost').val()) <= 0) {
        Swal.fire('Error', 'Please enter a valid fuel cost', 'error');
        return;
    }
    
    let submitBtn = $('#fuel-log-form').find('button[type="submit"]');
    let originalText = submitBtn.html();
    submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-2"></i>Saving...');
    
    let logId = $('#log-id').val();
    let url;
    
    // FIXED: Build URLs in JavaScript - NO PHP route() with empty parameter
    if (logId && logId !== '') {
        // Update existing record
        url = '/fuel-management/' + logId;
        formData._method = 'PUT';
    } else {
        // Create new record
        url = '/fuel-management/store';
    }
    
    $.ajax({
        url: url,
        method: 'POST',
        data: formData,
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        success: function(response) {
            Swal.fire({
                title: 'Success!',
                text: response.message || (logId ? 'Fuel log updated successfully!' : 'Fuel log added successfully!'),
                icon: 'success',
                timer: 2000,
                showConfirmButton: false
            }).then(() => {
                closeModal();
                location.reload();
            });
        },
        error: function(xhr) {
            let errorMessage = 'Failed to save fuel log';
            if (xhr.responseJSON && xhr.responseJSON.message) {
                errorMessage = xhr.responseJSON.message;
            } else if (xhr.status === 422) {
                let errors = xhr.responseJSON.errors;
                errorMessage = Object.values(errors).flat().join('\n');
            }
            Swal.fire('Error', errorMessage, 'error');
        },
        complete: function() {
            submitBtn.prop('disabled', false).html(originalText);
        }
    });
}

// ==================== ANALYTICS FUNCTIONS ====================

function initDatePickers() {
    const hasDateRangePlugin = typeof $.fn.daterangepicker === 'function';
    const hasMoment = typeof window.moment !== 'undefined';

    if (!hasDateRangePlugin || !hasMoment) {
        console.warn('DateRangePicker dependency missing. Falling back to manual date-range input.');
        $('#date-range, #analytics-date-range')
            .attr('placeholder', 'YYYY-MM-DD to YYYY-MM-DD')
            .attr('title', 'Enter date range manually, e.g. 2026-01-01 to 2026-01-31');
        return;
    }

    $('#date-range, #analytics-date-range').daterangepicker({
        autoUpdateInput: false,
        locale: { cancelLabel: 'Clear', format: 'YYYY-MM-DD' },
        ranges: {
            'Today': [moment(), moment()],
            'Last 7 Days': [moment().subtract(6, 'days'), moment()],
            'Last 30 Days': [moment().subtract(29, 'days'), moment()],
            'This Month': [moment().startOf('month'), moment().endOf('month')],
            'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
        }
    });
    
    $('#date-range, #analytics-date-range').on('apply.daterangepicker', function(ev, picker) {
        $(this).val(picker.startDate.format('YYYY-MM-DD') + ' to ' + picker.endDate.format('YYYY-MM-DD'));
    });
    
    $('#date-range, #analytics-date-range').on('cancel.daterangepicker', function() {
        $(this).val('');
    });
}

function loadQuickStats() {
    $.ajax({
        url: '{{ route("fuel-management.quick-stats") }}',
        method: 'GET',
        success: function(response) {
            if (response.success) {
                $('#total-fuel').text(response.total_fuel?.toFixed(1) || '0');
                $('#total-cost').text('GHS ' + (response.total_cost?.toFixed(2) || '0'));
                $('#total-distance').text((response.total_distance?.toFixed(0) || '0').toLocaleString());
                $('#avg-efficiency').text(response.avg_efficiency?.toFixed(1) || '0');
                $('#avg-cost-km').text('GHS ' + (response.avg_cost_per_km?.toFixed(2) || '0'));
            }
        }
    });
}

function applyFilters() {
    let params = new URLSearchParams();
    if ($('#filter-vehicle').val()) params.append('vehicle_id', $('#filter-vehicle').val());
    if ($('#filter-fuel-type').val()) params.append('fuel_type', $('#filter-fuel-type').val());
    if ($('#filter-status').val()) params.append('status', $('#filter-status').val());
    
    let dateRange = $('#date-range').val();
    if (dateRange) {
        let dates = dateRange.split(' to ');
        if (dates[0]) params.append('date_from', dates[0]);
        if (dates[1]) params.append('date_to', dates[1]);
    }
    
    window.location.href = '{{ route("fuel-management.index") }}?' + params.toString();
}

function loadAnalyticsData() {
    let params = new URLSearchParams();
    if ($('#analytics-vehicle').val()) params.append('vehicle_id', $('#analytics-vehicle').val());
    
    let dateRange = $('#analytics-date-range').val();
    if (dateRange) {
        let dates = dateRange.split(' to ');
        if (dates[0]) params.append('date_from', dates[0]);
        if (dates[1]) params.append('date_to', dates[1]);
    }
    
    $.ajax({
        url: '{{ route("fuel-management.analytics-data") }}?' + params.toString(),
        method: 'GET',
        success: function(response) {
            if (response.success) {
                updateCharts(response.data);
                updateMonthlySummary(response.data);
            }
        }
    });
}

function updateCharts(data) {
    // Fuel Trend Chart
    if (fuelTrendChart) fuelTrendChart.destroy();
    fuelTrendChart = new Chart(document.getElementById('fuel-trend-chart'), {
        type: 'line',
        data: {
            labels: data.months || [],
            datasets: [{
                label: 'Fuel Consumption (L)',
                data: data.fuel_data || [],
                borderColor: '#3b82f6',
                backgroundColor: 'rgba(59,130,246,0.1)',
                fill: true,
                tension: 0.4
            }]
        },
        options: { responsive: true, maintainAspectRatio: true }
    });
    
    // Cost Trend Chart
    if (costTrendChart) costTrendChart.destroy();
    costTrendChart = new Chart(document.getElementById('cost-trend-chart'), {
        type: 'line',
        data: {
            labels: data.months || [],
            datasets: [{
                label: 'Cost (GHS)',
                data: data.cost_data || [],
                borderColor: '#10b981',
                backgroundColor: 'rgba(16,185,129,0.1)',
                fill: true,
                tension: 0.4
            }]
        },
        options: { responsive: true, maintainAspectRatio: true }
    });
    
    // Fuel Type Chart
    if (fuelTypeChart) fuelTypeChart.destroy();
    fuelTypeChart = new Chart(document.getElementById('fuel-type-chart'), {
        type: 'doughnut',
        data: {
            labels: data.fuel_types?.labels || [],
            datasets: [{
                data: data.fuel_types?.values || [],
                backgroundColor: ['#3b82f6', '#10b981', '#f59e0b']
            }]
        },
        options: { responsive: true, maintainAspectRatio: true }
    });
    
    // Efficiency Chart
    if (efficiencyChart) efficiencyChart.destroy();
    efficiencyChart = new Chart(document.getElementById('efficiency-chart'), {
        type: 'bar',
        data: {
            labels: data.efficiency?.vehicles || [],
            datasets: [{
                label: 'km/L',
                data: data.efficiency?.values || [],
                backgroundColor: '#f59e0b'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            scales: { y: { beginAtZero: true } }
        }
    });
}

function updateMonthlySummary(data) {
    let html = '';
    if (data.monthly_summary && data.monthly_summary.length) {
        data.monthly_summary.forEach(item => {
            html += `<tr class="border-b">
                <td class="p-3">${item.month}</td>
                <td class="p-3 text-right">${item.fuel.toFixed(1)}</td>
                <td class="p-3 text-right">GHS ${item.cost.toFixed(2)}</td>
                <td class="p-3 text-right">${item.distance.toFixed(0).toLocaleString()}</td>
                <td class="p-3 text-right">${item.efficiency.toFixed(1)} km/L</td>
                <td class="p-3 text-right">GHS ${(item.cost/item.distance).toFixed(2)}</td>
             </tr>`;
        });
    } else {
        html = '<tr><td colspan="6" class="text-center py-8 text-gray-500">No data available</td></tr>';
    }
    $('#monthly-summary-body').html(html);
}

// Export logs
$('#export-logs').click(function() {
    let params = new URLSearchParams();
    if ($('#filter-vehicle').val()) params.append('vehicle_id', $('#filter-vehicle').val());
    if ($('#filter-fuel-type').val()) params.append('fuel_type', $('#filter-fuel-type').val());
    if ($('#filter-status').val()) params.append('status', $('#filter-status').val());
    
    let dateRange = $('#date-range').val();
    if (dateRange) {
        let dates = dateRange.split(' to ');
        if (dates[0]) params.append('date_from', dates[0]);
        if (dates[1]) params.append('date_to', dates[1]);
    }
    
    window.open('{{ route("fuel-management.export") }}?' + params.toString(), '_blank');
});

// Sidebar functions
function toggleSubMenu(menuId) {
    const sub = document.getElementById(`${menuId}-submenu`);
    const chevron = document.getElementById(`${menuId}-chevron`);
    if (sub) {
        sub.classList.toggle('hidden');
        if(chevron) chevron.classList.toggle('rotate-180');
    }
}

// Mobile sidebar
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
