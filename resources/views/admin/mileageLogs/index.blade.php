@extends('layouts.app')
@section('title', 'Mileage Logs - Fleet Management')
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
    
    .form-input, .form-select, .form-textarea {
        width: 100%;
        padding: 8px 12px;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        font-size: 14px;
        transition: all 0.2s;
    }
    .form-input:focus, .form-select:focus, .form-textarea:focus {
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
    
    .status-badge {
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 600;
    }
    .status-good { background: #dcfce7; color: #166534; }
    .status-warning { background: #fef3c7; color: #92400e; }
    .status-critical { background: #fee2e2; color: #991b1b; }
    
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
        max-width: 600px;
        width: 90%;
        max-height: 85vh;
        overflow-y: auto;
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
    
    .search-help {
        font-size: 11px;
        margin-top: 4px;
        display: block;
    }
    .search-help.text-green-600 { color: #16a34a; }
    .search-help.text-amber-600 { color: #d97706; }
    .search-help.text-red-600 { color: #dc2626; }
</style>

<div class="min-h-screen bg-gray-50 py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <!-- Header -->
        <div class="mb-6 flex justify-between items-center flex-wrap gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Mileage Logs</h1>
                <p class="text-gray-500 text-sm mt-1">Track and manage vehicle mileage records</p>
            </div>
            <div class="flex gap-3">
                <button onclick="openCreateModal()" class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm hover:bg-blue-700 transition flex items-center gap-2">
                    <i class="fas fa-plus"></i> Add Mileage Log
                </button>
                <button onclick="window.print()" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg text-sm hover:bg-gray-200 transition">
                    <i class="fas fa-print"></i> Print
                </button>
                <button id="exportData" class="px-4 py-2 bg-green-600 text-white rounded-lg text-sm hover:bg-green-700 transition">
                    <i class="fas fa-download"></i> Export
                </button>
            </div>
        </div>
        
        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 mb-6" id="statsCards">
            <div class="stat-card p-5">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-xs uppercase tracking-wide">Total Distance</p>
                        <p class="text-2xl font-bold text-gray-800" id="totalDistance">0</p>
                        <p class="text-xs text-gray-500">kilometers</p>
                    </div>
                    <i class="fas fa-road text-blue-400 text-3xl opacity-50"></i>
                </div>
            </div>
            <div class="stat-card p-5">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-xs uppercase tracking-wide">Total Logs</p>
                        <p class="text-2xl font-bold text-gray-800" id="totalLogs">0</p>
                        <p class="text-xs text-gray-500">records</p>
                    </div>
                    <i class="fas fa-clipboard-list text-green-400 text-3xl opacity-50"></i>
                </div>
            </div>
            <div class="stat-card p-5">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-xs uppercase tracking-wide">Average Distance</p>
                        <p class="text-2xl font-bold text-gray-800" id="avgDistance">0</p>
                        <p class="text-xs text-gray-500">km per log</p>
                    </div>
                    <i class="fas fa-chart-line text-purple-400 text-3xl opacity-50"></i>
                </div>
            </div>
            <div class="stat-card p-5">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-xs uppercase tracking-wide">Vehicles</p>
                        <p class="text-2xl font-bold text-gray-800" id="totalVehicles">0</p>
                        <p class="text-xs text-gray-500">active vehicles</p>
                    </div>
                    <i class="fas fa-truck text-orange-400 text-3xl opacity-50"></i>
                </div>
            </div>
        </div>
        
        <!-- Filters with Search (No Dropdowns) -->
        <div class="bg-white rounded-xl shadow-sm p-5 mb-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
                <!-- Vehicle Search Input -->
                <div>
                    <label class="form-label text-xs">Vehicle (Number Plate)</label>
                    <input type="text" id="filterVehicle" class="form-input text-sm" placeholder="Search by registration number...">
                    <small id="vehicleSearchHelp" class="search-help text-gray-500">Type at least 2 characters to search</small>
                </div>
                
                <!-- Driver Search Input -->
                <div>
                    <label class="form-label text-xs">Driver</label>
                    <input type="text" id="filterDriver" class="form-input text-sm" placeholder="Search by driver name...">
                    <small id="driverSearchHelp" class="search-help text-gray-500">Type at least 2 characters to search</small>
                </div>
                
                <div>
                    <label class="form-label text-xs">Date From</label>
                    <input type="date" id="filterDateFrom" class="form-input text-sm">
                </div>
                <div>
                    <label class="form-label text-xs">Date To</label>
                    <input type="date" id="filterDateTo" class="form-input text-sm">
                </div>
                <div class="flex items-end gap-2">
                    <button id="applyFilters" class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm hover:bg-blue-700 transition">
                        <i class="fas fa-search"></i> Apply
                    </button>
                    <button id="resetFilters" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg text-sm hover:bg-gray-200 transition">
                        <i class="fas fa-undo-alt"></i> Reset
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Mileage Logs Table -->
        <div class="bg-white rounded-xl shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="data-table w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Vehicle</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Driver</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Start (km)</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">End (km)</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Distance</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Status</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="mileageLogsBody">
                        <tr>
                            <td colspan="9" class="text-center py-8">
                                <div class="loading-spinner"></div> Loading...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <div class="px-6 py-4 border-t border-gray-200 flex justify-between items-center flex-wrap gap-3">
                <div id="paginationInfo" class="text-sm text-gray-500"></div>
                <div id="paginationButtons" class="flex gap-2"></div>
            </div>
        </div>
    </div>
</div>

<!-- Create/Edit Modal -->
<div id="mileageModal" class="modal">
    <div class="modal-content">
        <div class="sticky top-0 bg-white px-6 py-4 border-b border-gray-200 flex justify-between items-center">
            <h3 class="text-xl font-semibold text-gray-800">
                <i class="fas fa-gas-pump text-blue-600 mr-2"></i>
                <span id="modalTitle">Add Mileage Log</span>
            </h3>
            <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        
        <form id="mileageForm" class="p-6 space-y-4">
            @csrf
            <input type="hidden" name="id" id="logId">
            
            <!-- Vehicle Search -->
            <div class="relative">
                <label class="form-label">Vehicle (Number Plate) *</label>
                <input type="text" id="modalVehicleSearch" class="form-input w-full" placeholder="Enter registration number (e.g., GWL-001)" autocomplete="off" required>
                <div id="modalVehicleDropdown" class="absolute z-50 w-full mt-1 bg-white border border-gray-200 rounded-lg shadow-lg hidden max-h-60 overflow-y-auto"></div>
                <input type="hidden" name="vehicle_id" id="modalVehicleId" required>
                <small id="modalVehicleHelp" class="text-xs text-gray-500 mt-1 block">
                    <i class="fas fa-info-circle mr-1"></i> Type at least 2 characters to search
                </small>
            </div>
            
            <!-- Vehicle Info Card -->
            <div id="modalVehicleInfo" class="hidden bg-blue-50 border border-blue-100 rounded-lg p-3">
                <div class="flex items-center gap-3">
                    <div class="bg-blue-600 text-white p-2 rounded-lg">
                        <i class="fas fa-car"></i>
                    </div>
                    <div class="flex-1">
                        <div class="flex justify-between items-start">
                            <div>
                                <h4 class="font-bold text-blue-900" id="modalInfoMakeModel">Vehicle Name</h4>
                                <div class="flex gap-3 mt-1 text-xs text-blue-800">
                                    <span><i class="fas fa-calendar-alt mr-1"></i><span id="modalInfoYear">---</span></span>
                                    <span><i class="fas fa-palette mr-1"></i><span id="modalInfoColor">---</span></span>
                                </div>
                            </div>
                            <span class="px-2 py-1 bg-blue-100 text-blue-700 rounded text-xs font-bold" id="modalInfoPlate">PLATE</span>
                        </div>
                        <div class="mt-2 pt-2 border-t border-blue-100 flex justify-between text-xs text-blue-800">
                            <div><span class="opacity-70">Current Odometer:</span> <span class="font-bold" id="modalInfoOdo">0</span> km</div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Driver Search -->
            <div class="relative">
                <label class="form-label">Driver *</label>
                <input type="text" id="modalDriverSearch" class="form-input w-full" placeholder="Search driver by name..." autocomplete="off" >
                <div id="modalDriverDropdown" class="absolute z-50 w-full mt-1 bg-white border border-gray-200 rounded-lg shadow-lg hidden max-h-60 overflow-y-auto"></div>
                <input type="hidden" name="driver_id" id="modalDriverId">
                <small id="modalDriverHelp" class="text-xs text-gray-500 mt-1 block">
                    <i class="fas fa-info-circle mr-1"></i> Type at least 2 characters to search
                </small>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="form-label">Start Mileage (km) *</label>
                    <input type="number" name="start_mileage" id="startMileage" class="form-input" step="1">
                </div>
                <div>
                    <label class="form-label">End Mileage (km) *</label>
                    <input type="number" name="end_mileage" id="endMileage" class="form-input" step="1">
                </div>
            </div>
            
            <div>
                <label class="form-label">Distance Traveled (km)</label>
                <div id="distanceDisplay" class="px-4 py-2 bg-gray-100 rounded-lg text-sm font-semibold text-green-600">0 km</div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="form-label">Date *</label>
                    <input type="date" name="date" id="logDate" class="form-input" value="{{ date('Y-m-d') }}" required>
                </div>
                <div>
                    <label class="form-label">Service Alert</label>
                    <select name="service_alert" id="serviceAlert" class="form-input">
                        <option value="0">No</option>
                        <option value="1">Yes - Needs Service</option>
                    </select>
                </div>
            </div>

            
            <div>
                <label class="form-label">Notes</label>
                <textarea name="notes" id="notes" rows="3" class="form-textarea" placeholder="Additional notes..."></textarea>
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


<!-- View Details Modal -->
<div id="viewModal" class="modal">
    <div class="modal-content max-w-2xl">
        <div class="sticky top-0 bg-white px-6 py-4 border-b border-gray-200 flex justify-between items-center">
            <h3 class="text-xl font-semibold text-gray-800">
                <i class="fas fa-info-circle text-blue-600 mr-2"></i>Mileage Log Details
            </h3>
            <button onclick="closeViewModal()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        <div id="viewModalContent" class="p-6">
            <div class="text-center py-8">
                <div class="loading-spinner"></div>
                Loading...
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
let currentPage = 1;
let currentFilters = {};
let vehicleSearchTimeout = null;
let driverSearchTimeout = null;
let modalVehicleSearchTimeout = null;
let modalDriverSearchTimeout = null;

$(document).ready(function() {
    loadMileageLogs();
    loadStatistics();
    
    // Calculate distance on input
    $('#startMileage, #endMileage').on('input', function() {
        let start = parseFloat($('#startMileage').val()) || 0;
        let end = parseFloat($('#endMileage').val()) || 0;
        let distance = end - start;
        
        if (distance > 0) {
            $('#distanceDisplay').html(`<span class="text-green-600 font-semibold">${distance.toLocaleString()} km</span>`);
        } else if (end > 0 && distance < 0) {
            $('#distanceDisplay').html(`<span class="text-red-600 font-semibold">Invalid (End must be > Start)</span>`);
        } else {
            $('#distanceDisplay').html('<span class="text-gray-500">0 km</span>');
        }
    });
    
    // Filter Vehicle Search Functionality
    $('#filterVehicle').on('input', function() {
        clearTimeout(vehicleSearchTimeout);
        let vehicleTerm = $(this).val().trim();
        
        if (vehicleTerm.length < 2) {
            $('#vehicleSearchHelp').html('<span class="text-gray-500"><i class="fas fa-info-circle mr-1"></i> Type at least 2 characters to search</span>');
            return;
        }
        
        $('#vehicleSearchHelp').html('<i class="fas fa-circle-notch fa-spin mr-1 text-blue-500"></i> Searching...');
        
        vehicleSearchTimeout = setTimeout(() => {
            applyFilters();
        }, 500);
    });
    
    // Filter Driver Search Functionality
    $('#filterDriver').on('input', function() {
        clearTimeout(driverSearchTimeout);
        let driverTerm = $(this).val().trim();
        
        if (driverTerm.length < 2) {
            $('#driverSearchHelp').html('<span class="text-gray-500"><i class="fas fa-info-circle mr-1"></i> Type at least 2 characters to search</span>');
            return;
        }
        
        $('#driverSearchHelp').html('<i class="fas fa-circle-notch fa-spin mr-1 text-blue-500"></i> Searching...');
        
        driverSearchTimeout = setTimeout(() => {
            applyFilters();
        }, 500);
    });
    
    // Modal Vehicle Search
    $('#modalVehicleSearch').on('input', function() {
        clearTimeout(modalVehicleSearchTimeout);
        let searchTerm = $(this).val().trim();
        
        if (searchTerm.length < 2) {
            $('#modalVehicleDropdown').addClass('hidden').empty();
            $('#modalVehicleId').val('');
            $('#modalVehicleHelp').html('<i class="fas fa-info-circle mr-1"></i> Type at least 2 characters to search');
            return;
        }
        
        $('#modalVehicleHelp').html('<i class="fas fa-circle-notch fa-spin mr-1 text-blue-500"></i> Searching...');
        
        modalVehicleSearchTimeout = setTimeout(() => {
            $.ajax({
                url: '{{ route("vehicles.search") }}',
                method: 'GET',
                data: { plate: searchTerm },
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    if (response.success && response.data) {
                        selectModalVehicle(response.data);
                        $('#modalVehicleDropdown').addClass('hidden').empty();
                        $('#modalVehicleHelp').html('<span class="text-green-600"><i class="fas fa-check-circle mr-1"></i> Vehicle found</span>');
                    } else if (response.vehicles && response.vehicles.length > 0) {
                        showModalVehicleDropdown(response.vehicles);
                        $('#modalVehicleHelp').html(`<span class="text-blue-600"><i class="fas fa-list mr-1"></i> ${response.vehicles.length} vehicles found. Click to select.</span>`);
                    } else {
                        $('#modalVehicleId').val('');
                        $('#modalVehicleInfo').addClass('hidden');
                        $('#modalVehicleDropdown').addClass('hidden').empty();
                        $('#modalVehicleHelp').html('<span class="text-amber-600"><i class="fas fa-exclamation-triangle mr-1"></i> No matching vehicle found</span>');
                    }
                },
                error: function() {
                    $('#modalVehicleId').val('');
                    $('#modalVehicleHelp').html('<span class="text-red-600"><i class="fas fa-times-circle mr-1"></i> Error searching for vehicle</span>');
                }
            });
        }, 500);
    });
    
    // Modal Driver Search
    $('#modalDriverSearch').on('input', function() {
        clearTimeout(modalDriverSearchTimeout);
        let searchTerm = $(this).val().trim();
        
        if (searchTerm.length < 2) {
            $('#modalDriverDropdown').addClass('hidden').empty();
            $('#modalDriverId').val('');
            $('#modalDriverHelp').html('<i class="fas fa-info-circle mr-1"></i> Type at least 2 characters to search');
            return;
        }
        
        $('#modalDriverHelp').html('<i class="fas fa-circle-notch fa-spin mr-1 text-blue-500"></i> Searching...');
        
        modalDriverSearchTimeout = setTimeout(() => {
            $.ajax({
                url: '{{ route("drivers.search") }}',
                method: 'GET',
                data: { search: searchTerm },
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    if (response.success && response.drivers && response.drivers.length > 0) {
                        showModalDriverDropdown(response.drivers);
                        $('#modalDriverHelp').html(`<span class="text-blue-600"><i class="fas fa-list mr-1"></i> ${response.drivers.length} drivers found. Click to select.</span>`);
                    } else {
                        $('#modalDriverId').val('');
                        $('#modalDriverDropdown').addClass('hidden').empty();
                        $('#modalDriverHelp').html('<span class="text-amber-600"><i class="fas fa-exclamation-triangle mr-1"></i> No matching driver found</span>');
                    }
                },
                error: function() {
                    $('#modalDriverId').val('');
                    $('#modalDriverHelp').html('<span class="text-red-600"><i class="fas fa-times-circle mr-1"></i> Error searching for driver</span>');
                }
            });
        }, 500);
    });
    
    // Filter events
    $('#applyFilters').click(function() {
        applyFilters();
    });
    
    $('#resetFilters').click(function() {
        $('#filterVehicle, #filterDriver').val('');
        $('#filterDateFrom, #filterDateTo').val('');
        $('#vehicleSearchHelp').html('<span class="text-gray-500"><i class="fas fa-info-circle mr-1"></i> Type at least 2 characters to search</span>');
        $('#driverSearchHelp').html('<span class="text-gray-500"><i class="fas fa-info-circle mr-1"></i> Type at least 2 characters to search</span>');
        currentPage = 1;
        currentFilters = {};
        loadMileageLogs();
        loadStatistics();
    });
    
    // Export
    $('#exportData').click(function() {
        exportData();
    });
    
    // Form submission
    $('#mileageForm').on('submit', function(e) {
        e.preventDefault();
        saveMileageLog();
    });
    
    // Close dropdowns when clicking outside
    $(document).on('click', function(e) {
        if (!$(e.target).closest('#modalVehicleSearch').length && !$(e.target).closest('#modalVehicleDropdown').length) {
            $('#modalVehicleDropdown').addClass('hidden');
        }
        if (!$(e.target).closest('#modalDriverSearch').length && !$(e.target).closest('#modalDriverDropdown').length) {
            $('#modalDriverDropdown').addClass('hidden');
        }
    });
});

function applyFilters() {
    currentPage = 1;
    currentFilters = {
        vehicle_search: $('#filterVehicle').val(),
        driver_search: $('#filterDriver').val(),
        date_from: $('#filterDateFrom').val(),
        date_to: $('#filterDateTo').val()
    };
    
    // Update help text
    if ($('#filterVehicle').val().length >= 2) {
        $('#vehicleSearchHelp').html('<span class="text-green-600"><i class="fas fa-check-circle mr-1"></i> Searching...</span>');
    }
    if ($('#filterDriver').val().length >= 2) {
        $('#driverSearchHelp').html('<span class="text-green-600"><i class="fas fa-check-circle mr-1"></i> Searching...</span>');
    }
    
    loadMileageLogs();
    loadStatistics();
}

function loadStatistics() {
    let params = new URLSearchParams();
    if (currentFilters.vehicle_search) params.append('vehicle_search', currentFilters.vehicle_search);
    if (currentFilters.driver_search) params.append('driver_search', currentFilters.driver_search);
    if (currentFilters.date_from) params.append('date_from', currentFilters.date_from);
    if (currentFilters.date_to) params.append('date_to', currentFilters.date_to);
    
    $.ajax({
        url: '{{ route("mileage-logs.statistics") }}?' + params.toString(),
        method: 'GET',
        success: function(response) {
            if (response.success) {
                $('#totalDistance').text(response.total_distance.toLocaleString());
                $('#totalLogs').text(response.total_logs);
                $('#avgDistance').text(response.avg_distance.toFixed(1));
                $('#totalVehicles').text(response.total_vehicles || 0);
            }
        }
    });
}

function loadMileageLogs() {
    let params = new URLSearchParams({
        page: currentPage
    });
    
    if (currentFilters.vehicle_search) params.append('vehicle_search', currentFilters.vehicle_search);
    if (currentFilters.driver_search) params.append('driver_search', currentFilters.driver_search);
    if (currentFilters.date_from) params.append('date_from', currentFilters.date_from);
    if (currentFilters.date_to) params.append('date_to', currentFilters.date_to);
    
    $.ajax({
        url: '{{ route("mileage-logs.data") }}?' + params.toString(),
        method: 'GET',
        success: function(response) {
            if (response.success) {
                renderTable(response.data);
                renderPagination(response.pagination);
                
                // Update search help text based on results
                if (currentFilters.vehicle_search && currentFilters.vehicle_search.length >= 2) {
                    if (response.data && response.data.length > 0) {
                        $('#vehicleSearchHelp').html('<span class="text-green-600"><i class="fas fa-check-circle mr-1"></i> Vehicle found</span>');
                    } else {
                        $('#vehicleSearchHelp').html('<span class="text-amber-600"><i class="fas fa-exclamation-triangle mr-1"></i> No matching vehicle found</span>');
                    }
                }
                if (currentFilters.driver_search && currentFilters.driver_search.length >= 2) {
                    if (response.data && response.data.length > 0) {
                        $('#driverSearchHelp').html('<span class="text-green-600"><i class="fas fa-check-circle mr-1"></i> Driver found</span>');
                    } else {
                        $('#driverSearchHelp').html('<span class="text-amber-600"><i class="fas fa-exclamation-triangle mr-1"></i> No matching driver found</span>');
                    }
                }
            }
        },
        error: function() {
            $('#mileageLogsBody').html('<tr><td colspan="9" class="text-center py-8 text-red-500">Failed to load data</td</tr>');
        }
    });
}

function renderTable(logs) {
    if (!logs || logs.length === 0) {
        $('#mileageLogsBody').html('<tr><td colspan="9" class="text-center py-8 text-gray-500">No mileage logs found</td</tr>');
        return;
    }
    
    let html = '';
    logs.forEach(log => {
        let distance = (log.end_mileage - log.start_mileage).toLocaleString();
        let statusClass = log.service_alert ? 'status-critical' : 'status-good';
        let statusText = log.service_alert ? 'Service Needed' : 'OK';
        
        html += `
            <tr class="hover:bg-gray-50">
                <td class="px-6 py-4 text-sm">${formatDate(log.date)}</td>
                <td class="px-6 py-4 text-sm">
                    <span class="font-medium">${log.vehicle?.registration_number || 'N/A'}</span>
                    <br><span class="text-xs text-gray-500">${log.vehicle?.make || ''} ${log.vehicle?.model || ''}</span>
                </td>
                <td class="px-6 py-4 text-sm">${log.driver?.name || 'N/A'}</td>
                <td class="px-6 py-4 text-sm text-right font-mono">${(log.start_mileage || 0).toLocaleString()}</td>
                <td class="px-6 py-4 text-sm text-right font-mono">${(log.end_mileage || 0).toLocaleString()}</td>
                <td class="px-6 py-4 text-sm text-right font-semibold text-green-600">${distance} km</td>
                <td class="px-6 py-4 text-sm text-center">
                    <span class="status-badge ${statusClass}">${statusText}</span>
                </td>
                <td class="px-6 py-4 text-sm text-center">
                    <div class="flex gap-2 justify-center">
                        <button onclick="viewLog(${log.id})" class="text-blue-600 hover:text-blue-800" title="View">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button onclick="editLog(${log.id})" class="text-green-600 hover:text-green-800" title="Edit">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button onclick="deleteLog(${log.id})" class="text-red-600 hover:text-red-800" title="Delete">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `;
    });
    $('#mileageLogsBody').html(html);
}

function renderPagination(pagination) {
    if (!pagination) return;
    
    $('#paginationInfo').text(`Showing ${pagination.from} to ${pagination.to} of ${pagination.total} records`);
    
    let buttons = '';
    if (pagination.current_page > 1) {
        buttons += `<button onclick="goToPage(${pagination.current_page - 1})" class="px-3 py-1 border rounded hover:bg-gray-50">‹ Prev</button>`;
    }
    
    let startPage = Math.max(1, pagination.current_page - 2);
    let endPage = Math.min(pagination.last_page, pagination.current_page + 2);
    
    if (startPage > 1) {
        buttons += `<button onclick="goToPage(1)" class="px-3 py-1 border rounded hover:bg-gray-50">1</button>`;
        if (startPage > 2) buttons += `<span class="px-2">...</span>`;
    }
    
    for (let i = startPage; i <= endPage; i++) {
        if (i === pagination.current_page) {
            buttons += `<button class="px-3 py-1 bg-blue-600 text-white rounded">${i}</button>`;
        } else {
            buttons += `<button onclick="goToPage(${i})" class="px-3 py-1 border rounded hover:bg-gray-50">${i}</button>`;
        }
    }
    
    if (endPage < pagination.last_page) {
        if (endPage < pagination.last_page - 1) buttons += `<span class="px-2">...</span>`;
        buttons += `<button onclick="goToPage(${pagination.last_page})" class="px-3 py-1 border rounded hover:bg-gray-50">${pagination.last_page}</button>`;
    }
    
    if (pagination.current_page < pagination.last_page) {
        buttons += `<button onclick="goToPage(${pagination.current_page + 1})" class="px-3 py-1 border rounded hover:bg-gray-50">Next ›</button>`;
    }
    
    $('#paginationButtons').html(buttons);
}

function goToPage(page) {
    currentPage = page;
    loadMileageLogs();
}

// ==================== MODAL SEARCH FUNCTIONS ====================

function showModalVehicleDropdown(vehicles) {
    let dropdown = $('#modalVehicleDropdown');
    dropdown.empty();
    
    let html = '<div class="max-h-60 overflow-y-auto">';
    vehicles.forEach(vehicle => {
        html += `
            <div class="px-4 py-2 hover:bg-blue-50 cursor-pointer border-b border-gray-100 last:border-0" 
                 onclick="selectModalVehicleFromList(${vehicle.id}, '${escapeHtml(vehicle.registration_number)}', '${escapeHtml(vehicle.make)} ${escapeHtml(vehicle.model)}', '${vehicle.year}', '${escapeHtml(vehicle.color || '')}', ${vehicle.current_odometer || 0})">
                <div class="font-medium text-gray-800">${escapeHtml(vehicle.registration_number)}</div>
                <div class="text-xs text-gray-500">${escapeHtml(vehicle.make)} ${escapeHtml(vehicle.model)} ${vehicle.year ? '(' + vehicle.year + ')' : ''}</div>
            </div>
        `;
    });
    html += '</div>';
    
    dropdown.html(html).removeClass('hidden');
}

function selectModalVehicleFromList(id, registrationNumber, makeModel, year, color, odometer) {
    $('#modalVehicleId').val(id);
    $('#modalVehicleSearch').val(registrationNumber);
    
    // Update vehicle info card
    $('#modalInfoMakeModel').text(makeModel);
    $('#modalInfoYear').text(year || 'N/A');
    $('#modalInfoColor').text(color || 'N/A');
    $('#modalInfoPlate').text(registrationNumber);
    $('#modalInfoOdo').text(odometer.toLocaleString());
    $('#modalVehicleInfo').removeClass('hidden').fadeIn(200);
    
    // Auto-fill start mileage if empty
    let currentStart = parseFloat($('#startMileage').val()) || 0;
    if (currentStart === 0 && odometer > 0) {
        $('#startMileage').val(odometer);
    }
    
    $('#modalVehicleDropdown').addClass('hidden');
    $('#modalVehicleHelp').html('<span class="text-green-600"><i class="fas fa-check-circle mr-1"></i> Vehicle selected</span>');
}

function selectModalVehicle(vehicle) {
    $('#modalVehicleId').val(vehicle.id);
    $('#modalVehicleSearch').val(vehicle.registration_number);
    
    // Update vehicle info card
    $('#modalInfoMakeModel').text(vehicle.make_model || vehicle.make + ' ' + vehicle.model);
    $('#modalInfoYear').text(vehicle.year || 'N/A');
    $('#modalInfoColor').text(vehicle.color || 'N/A');
    $('#modalInfoPlate').text(vehicle.registration_number);
    $('#modalInfoOdo').text((vehicle.current_odometer || 0).toLocaleString());
    $('#modalVehicleInfo').removeClass('hidden').fadeIn(200);
    
    // Auto-fill start mileage if empty
    let currentStart = parseFloat($('#startMileage').val()) || 0;
    if (currentStart === 0 && vehicle.current_odometer > 0) {
        $('#startMileage').val(vehicle.current_odometer);
    }
    
    $('#modalVehicleHelp').html('<span class="text-green-600"><i class="fas fa-check-circle mr-1"></i> Vehicle selected</span>');
}

function showModalDriverDropdown(drivers) {
    let dropdown = $('#modalDriverDropdown');
    dropdown.empty();
    
    let html = '<div class="max-h-60 overflow-y-auto">';
    drivers.forEach(driver => {
        let driverName = driver.user ? driver.user.name : driver.name;
        let driverEmail = driver.user ? driver.user.email : (driver.email || '');
        html += `
            <div class="px-4 py-2 hover:bg-blue-50 cursor-pointer border-b border-gray-100 last:border-0" 
                 onclick="selectModalDriver(${driver.id}, '${escapeHtml(driverName)}')">
                <div class="font-medium text-gray-800">${escapeHtml(driverName)}</div>
                <div class="text-xs text-gray-500">${escapeHtml(driverEmail)}</div>
            </div>
        `;
    });
    html += '</div>';
    
    dropdown.html(html).removeClass('hidden');
}

function selectModalDriver(id, name) {
    $('#modalDriverId').val(id);
    $('#modalDriverSearch').val(name);
    $('#modalDriverDropdown').addClass('hidden');
    $('#modalDriverHelp').html('<span class="text-green-600"><i class="fas fa-check-circle mr-1"></i> Driver selected</span>');
}

// ==================== MODAL FUNCTIONS ====================

function openCreateModal() {
    $('#modalTitle').text('Add Mileage Log');
    $('#mileageForm')[0].reset();
    $('#logId').val('');
    $('#modalVehicleSearch').val('');
    $('#modalDriverSearch').val('');
    $('#modalVehicleId').val('');
    $('#modalDriverId').val('');
    $('#modalVehicleInfo').addClass('hidden');
    $('#modalVehicleDropdown').addClass('hidden').empty();
    $('#modalDriverDropdown').addClass('hidden').empty();
    $('#distanceDisplay').html('<span class="text-gray-500">0 km</span>');
    $('#logDate').val(new Date().toISOString().split('T')[0]);
    $('#modalVehicleHelp').html('<i class="fas fa-info-circle mr-1"></i> Type at least 2 characters to search');
    $('#modalDriverHelp').html('<i class="fas fa-info-circle mr-1"></i> Type at least 2 characters to search');
    $('#mileageModal').addClass('active');
}

function editLog(id) {
    $.ajax({
        url: '/mileage-logs/' + id + '/edit-data',
        method: 'GET',
        success: function(response) {
            if (response.success) {
                $('#modalTitle').text('Edit Mileage Log');
                $('#logId').val(response.data.id);
                
                // Load vehicle info
                if (response.data.vehicle) {
                    $('#modalVehicleSearch').val(response.data.vehicle.registration_number);
                    $('#modalVehicleId').val(response.data.vehicle_id);
                    $('#modalInfoMakeModel').text(response.data.vehicle.make + ' ' + response.data.vehicle.model);
                    $('#modalInfoYear').text(response.data.vehicle.year || 'N/A');
                    $('#modalInfoColor').text(response.data.vehicle.color || 'N/A');
                    $('#modalInfoPlate').text(response.data.vehicle.registration_number);
                    $('#modalInfoOdo').text((response.data.vehicle.mileage || 0).toLocaleString());
                    $('#modalVehicleInfo').removeClass('hidden');
                    $('#modalVehicleHelp').html('<span class="text-green-600"><i class="fas fa-check-circle mr-1"></i> Vehicle loaded</span>');
                }
                
                // Load driver info
                if (response.data.driver) {
                    $('#modalDriverSearch').val(response.data.driver.name);
                    $('#modalDriverId').val(response.data.driver_id);
                    $('#modalDriverHelp').html('<span class="text-green-600"><i class="fas fa-check-circle mr-1"></i> Driver loaded</span>');
                }
                
                $('#startMileage').val(response.data.start_mileage);
                $('#endMileage').val(response.data.end_mileage);
                $('#logDate').val(response.data.date);
                $('#serviceAlert').val(response.data.service_alert ? 1 : 0);
                $('#notes').val(response.data.notes || '');
                
                let distance = response.data.end_mileage - response.data.start_mileage;
                $('#distanceDisplay').html(`<span class="text-green-600 font-semibold">${distance.toLocaleString()} km</span>`);
                
                $('#mileageModal').addClass('active');
            }
        },
        error: function() {
            Swal.fire('Error', 'Failed to load mileage log data', 'error');
        }
    });
}

function viewLog(id) {
    $.ajax({
        url: '/mileage-logs/' + id,
        method: 'GET',
        success: function(response) {
            if (response.success) {
                let data = response.data;
                let distance = data.end_mileage - data.start_mileage;
                
                let html = `
                    <div class="space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div class="bg-gray-50 p-3 rounded-lg">
                                <p class="text-xs text-gray-500">Vehicle</p>
                                <p class="font-semibold text-gray-800">${data.vehicle?.registration_number || 'N/A'}</p>
                                <p class="text-sm text-gray-500">${data.vehicle?.make || ''} ${data.vehicle?.model || ''}</p>
                            </div>
                            <div class="bg-gray-50 p-3 rounded-lg">
                                <p class="text-xs text-gray-500">Driver</p>
                                <p class="font-semibold text-gray-800">${data.driver?.name || 'N/A'}</p>
                                <p class="text-sm text-gray-500">${data.driver?.email || ''}</p>
                            </div>
                            <div class="bg-gray-50 p-3 rounded-lg">
                                <p class="text-xs text-gray-500">Start Mileage</p>
                                <p class="font-semibold text-gray-800">${(data.start_mileage || 0).toLocaleString()} km</p>
                            </div>
                            <div class="bg-gray-50 p-3 rounded-lg">
                                <p class="text-xs text-gray-500">End Mileage</p>
                                <p class="font-semibold text-gray-800">${(data.end_mileage || 0).toLocaleString()} km</p>
                            </div>
                            <div class="bg-gray-50 p-3 rounded-lg">
                                <p class="text-xs text-gray-500">Distance Traveled</p>
                                <p class="font-semibold text-green-600 text-lg">${distance.toLocaleString()} km</p>
                            </div>
                            <div class="bg-gray-50 p-3 rounded-lg">
                                <p class="text-xs text-gray-500">Date</p>
                                <p class="font-semibold text-gray-800">${formatDate(data.date)}</p>
                            </div>
                        </div>
                        ${data.service_alert ? `
                        <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                            <div class="flex items-center gap-2">
                                <i class="fas fa-bell text-red-600"></i>
                                <span class="font-semibold text-red-700">Service Alert</span>
                            </div>
                            <p class="text-sm text-red-600 mt-1">This vehicle may need maintenance soon.</p>
                        </div>
                        ` : ''}
                        ${data.notes ? `
                        <div class="bg-gray-50 rounded-lg p-4">
                            <p class="text-xs text-gray-500 mb-1">Notes</p>
                            <p class="text-sm text-gray-700">${escapeHtml(data.notes)}</p>
                        </div>
                        ` : ''}
                        <div class="text-xs text-gray-400 pt-2 border-t">
                            <p>Created: ${formatDateTime(data.created_at)}</p>
                            <p>Last updated: ${formatDateTime(data.updated_at)}</p>
                        </div>
                    </div>
                `;
                $('#viewModalContent').html(html);
                $('#viewModal').addClass('active');
            }
        },
        error: function() {
            $('#viewModalContent').html('<div class="text-center py-8 text-red-500">Failed to load details</div>');
        }
    });
}

function saveMileageLog() {
    let formData = {
        vehicle_id: $('#modalVehicleId').val(),
        driver_id: $('#modalDriverId').val(),
        start_mileage: $('#startMileage').val(),
        end_mileage: $('#endMileage').val(),
        date: $('#logDate').val(),
        service_alert: $('#serviceAlert').val(),
        notes: $('#notes').val(),
        _token: '{{ csrf_token() }}'
    };
    
    let start = parseFloat(formData.start_mileage);
    let end = parseFloat(formData.end_mileage);
    
    if (!formData.vehicle_id) {
        Swal.fire('Error', 'Please select a vehicle', 'error');
        return;
    }
    if (isNaN(start) || start < 0) {
        Swal.fire('Error', 'Please enter a valid start mileage', 'error');
        return;
    }
    if (isNaN(end) || end <= start) {
        Swal.fire('Error', 'End mileage must be greater than start mileage', 'error');
        return;
    }
    
    let logId = $('#logId').val();
    let url = logId ? '/mileage-logs/' + logId : '{{ route("mileage-logs.store") }}';
    
    if (logId) formData._method = 'PUT';
    
    let submitBtn = $('#mileageForm').find('button[type="submit"]');
    let originalText = submitBtn.html();
    submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-2"></i>Saving...');
    
    $.ajax({
        url: url,
        method: 'POST',
        data: formData,
        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
        success: function(response) {
            Swal.fire('Success', response.message || 'Mileage log saved successfully', 'success');
            closeModal();
            loadMileageLogs();
            loadStatistics();
        },
        error: function(xhr) {
            let errorMsg = xhr.responseJSON?.message || 'Failed to save mileage log';
            Swal.fire('Error', errorMsg, 'error');
        },
        complete: function() {
            submitBtn.prop('disabled', false).html(originalText);
        }
    });
}

function deleteLog(id) {
    Swal.fire({
        title: 'Delete Mileage Log?',
        text: 'This action cannot be undone.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        confirmButtonText: 'Yes, delete'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: '/mileage-logs/' + id,
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                success: function() {
                    Swal.fire('Deleted!', 'Mileage log deleted successfully', 'success');
                    loadMileageLogs();
                    loadStatistics();
                },
                error: function() {
                    Swal.fire('Error', 'Failed to delete mileage log', 'error');
                }
            });
        }
    });
}

function closeModal() {
    $('#mileageModal').removeClass('active');
    $('#mileageForm')[0].reset();
    $('#modalVehicleInfo').addClass('hidden');
    $('#modalVehicleDropdown').addClass('hidden').empty();
    $('#modalDriverDropdown').addClass('hidden').empty();
}

function closeViewModal() {
    $('#viewModal').removeClass('active');
}

function exportData() {
    let params = new URLSearchParams();
    if (currentFilters.vehicle_search) params.append('vehicle_search', currentFilters.vehicle_search);
    if (currentFilters.driver_search) params.append('driver_search', currentFilters.driver_search);
    if (currentFilters.date_from) params.append('date_from', currentFilters.date_from);
    if (currentFilters.date_to) params.append('date_to', currentFilters.date_to);
    params.append('export', 'csv');
    
    window.open('/mileage-logs/export?' + params.toString(), '_blank');
}

function formatDate(dateString) {
    if (!dateString) return 'N/A';
    return new Date(dateString).toLocaleDateString('en-GB', {
        day: '2-digit', month: 'short', year: 'numeric'
    });
}

function formatDateTime(dateString) {
    if (!dateString) return 'N/A';
    return new Date(dateString).toLocaleString('en-GB', {
        day: '2-digit', month: 'short', year: 'numeric',
        hour: '2-digit', minute: '2-digit'
    });
}

function escapeHtml(text) {
    if (!text) return '';
    return text.replace(/[&<>]/g, function(m) {
        if (m === '&') return '&amp;';
        if (m === '<') return '&lt;';
        if (m === '>') return '&gt;';
        return m;
    });
}
</script>

@endsection