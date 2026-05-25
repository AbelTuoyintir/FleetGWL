{{-- resources/views/admin/mileage-logs/index.blade.php --}}
@extends('layouts.app')
@section('title', 'Admin - Mileage Logs Management')
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
    
    @media print {
        .no-print { display: none !important; }
        body { background: white; }
        .stat-card { box-shadow: none !important; border: 1px solid #ddd; }
    }
</style>

<div class="min-h-screen bg-gray-50 py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <!-- Header -->
        <div class="mb-6 flex justify-between items-center flex-wrap gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Mileage Logs Management</h1>
                <p class="text-gray-500 text-sm mt-1">Track and manage vehicle mileage records</p>
            </div>
            <div class="flex gap-3 no-print">
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
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 mb-6">
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
                        <p class="text-gray-500 text-xs uppercase tracking-wide">Service Alerts</p>
                        <p class="text-2xl font-bold text-gray-800" id="serviceAlerts">0</p>
                        <p class="text-xs text-gray-500">vehicles need service</p>
                    </div>
                    <i class="fas fa-bell text-red-400 text-3xl opacity-50"></i>
                </div>
            </div>
        </div>
        
        <!-- Filters -->
        <div class="bg-white rounded-xl shadow-sm p-5 mb-6 no-print">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
                <div>
                    <label class="form-label text-xs">Vehicle</label>
                    <select id="filterVehicle" class="form-input text-sm">
                        <option value="">All Vehicles</option>
                        @foreach($vehicles ?? [] as $vehicle)
                            <option value="{{ $vehicle->id }}">{{ $vehicle->registration_number }} - {{ $vehicle->make }} {{ $vehicle->model }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="form-label text-xs">Driver</label>
                    <select id="filterDriver" class="form-input text-sm">
                        <option value="">All Drivers</option>
                        @foreach($drivers ?? [] as $driver)
                            <option value="{{ $driver->id }}">{{ $driver->name }}</option>
                        @endforeach
                    </select>
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
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Vehicle</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Driver</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Start (km)</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">End (km)</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Distance</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
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
            
            <div>
                <label class="form-label">Vehicle *</label>
                <select name="vehicle_id" id="vehicleId" class="form-input" required>
                    <option value="">Select Vehicle</option>
                    @foreach($vehicles ?? [] as $vehicle)
                        <option value="{{ $vehicle->id }}">{{ $vehicle->registration_number }} - {{ $vehicle->make }} {{ $vehicle->model }}</option>
                    @endforeach
                </select>
            </div>
            
            <div>
                <label class="form-label">Driver *</label>
                <select name="driver_id" id="driverId" class="form-input" required>
                    <option value="">Select Driver</option>
                    @foreach($drivers ?? [] as $driver)
                        <option value="{{ $driver->id }}">{{ $driver->name }}</option>
                    @endforeach
                </select>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="form-label">Start Mileage (km) *</label>
                    <input type="number" name="start_mileage" id="startMileage" class="form-input" step="1" required>
                </div>
                <div>
                    <label class="form-label">End Mileage (km) *</label>
                    <input type="number" name="end_mileage" id="endMileage" class="form-input" step="1" required>
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
    
    // Filter events
    $('#applyFilters').click(function() {
        currentPage = 1;
        currentFilters = {
            vehicle_id: $('#filterVehicle').val(),
            driver_id: $('#filterDriver').val(),
            date_from: $('#filterDateFrom').val(),
            date_to: $('#filterDateTo').val()
        };
        loadMileageLogs();
        loadStatistics();
    });
    
    $('#resetFilters').click(function() {
        $('#filterVehicle, #filterDriver').val('');
        $('#filterDateFrom, #filterDateTo').val('');
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
});

function loadStatistics() {
    let params = new URLSearchParams();
    if (currentFilters.vehicle_id) params.append('vehicle_id', currentFilters.vehicle_id);
    if (currentFilters.date_from) params.append('date_from', currentFilters.date_from);
    if (currentFilters.date_to) params.append('date_to', currentFilters.date_to);
    
    $.ajax({
        url: '{{ route("admin.mileage-logs.statistics") }}?' + params.toString(),
        method: 'GET',
        success: function(response) {
            if (response.success) {
                $('#totalDistance').text(response.total_distance.toLocaleString());
                $('#totalLogs').text(response.total_logs);
                $('#avgDistance').text(response.avg_distance.toFixed(1));
                $('#serviceAlerts').text(response.service_alerts);
            }
        }
    });
}

function loadMileageLogs() {
    let params = new URLSearchParams({
        page: currentPage
    });
    
    if (currentFilters.vehicle_id) params.append('vehicle_id', currentFilters.vehicle_id);
    if (currentFilters.driver_id) params.append('driver_id', currentFilters.driver_id);
    if (currentFilters.date_from) params.append('date_from', currentFilters.date_from);
    if (currentFilters.date_to) params.append('date_to', currentFilters.date_to);
    
    $.ajax({
        url: '{{ route("admin.mileage-logs.data") }}?' + params.toString(),
        method: 'GET',
        success: function(response) {
            if (response.success) {
                renderTable(response.data);
                renderPagination(response.pagination);
            }
        },
        error: function() {
            $('#mileageLogsBody').html('<tr><td colspan="9" class="text-center py-8 text-red-500">Failed to load data</td></tr>');
        }
    });
}

function renderTable(logs) {
    if (!logs || logs.length === 0) {
        $('#mileageLogsBody').html('<tr><td colspan="9" class="text-center py-8 text-gray-500">No mileage logs found</td></tr>');
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
    $('#paginationInfo').text(`Showing ${pagination.from} to ${pagination.to} of ${pagination.total} records`);
    
    let buttons = '';
    if (pagination.current_page > 1) {
        buttons += `<button onclick="goToPage(${pagination.current_page - 1})" class="px-3 py-1 border rounded hover:bg-gray-50">‹ Prev</button>`;
    }
    
    for (let i = 1; i <= pagination.last_page; i++) {
        if (i === pagination.current_page) {
            buttons += `<button class="px-3 py-1 bg-blue-600 text-white rounded">${i}</button>`;
        } else if (Math.abs(i - pagination.current_page) <= 2 || i === 1 || i === pagination.last_page) {
            buttons += `<button onclick="goToPage(${i})" class="px-3 py-1 border rounded hover:bg-gray-50">${i}</button>`;
        } else if (Math.abs(i - pagination.current_page) === 3) {
            buttons += `<span class="px-2">...</span>`;
        }
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

function openCreateModal() {
    $('#modalTitle').text('Add Mileage Log');
    $('#mileageForm')[0].reset();
    $('#logId').val('');
    $('#distanceDisplay').html('<span class="text-gray-500">0 km</span>');
    $('#logDate').val(new Date().toISOString().split('T')[0]);
    $('#mileageModal').addClass('active');
}

function editLog(id) {
    $.ajax({
        url: '/admin/mileage-logs/' + id + '/edit-data',
        method: 'GET',
        success: function(response) {
            if (response.success) {
                $('#modalTitle').text('Edit Mileage Log');
                $('#logId').val(response.data.id);
                $('#vehicleId').val(response.data.vehicle_id);
                $('#driverId').val(response.data.driver_id);
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
        url: '/admin/mileage-logs/' + id,
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
        vehicle_id: $('#vehicleId').val(),
        driver_id: $('#driverId').val(),
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
    if (!formData.driver_id) {
        Swal.fire('Error', 'Please select a driver', 'error');
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
    let url = logId ? '/admin/mileage-logs/' + logId : '{{ route("admin.mileage-logs.store") }}';
    let method = logId ? 'PUT' : 'POST';
    
    if (logId) formData._method = 'PUT';
    
    let submitBtn = $(this).find('button[type="submit"]');
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
                url: '/admin/mileage-logs/' + id,
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
}

function closeViewModal() {
    $('#viewModal').removeClass('active');
}

function exportData() {
    let params = new URLSearchParams();
    if (currentFilters.vehicle_id) params.append('vehicle_id', currentFilters.vehicle_id);
    if (currentFilters.driver_id) params.append('driver_id', currentFilters.driver_id);
    if (currentFilters.date_from) params.append('date_from', currentFilters.date_from);
    if (currentFilters.date_to) params.append('date_to', currentFilters.date_to);
    params.append('export', 'csv');
    
    window.open('/admin/mileage-logs/export?' + params.toString(), '_blank');
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