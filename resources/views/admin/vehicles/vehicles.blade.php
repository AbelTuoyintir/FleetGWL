@extends('layouts.app')
@section('title', 'Vehicles - GWL')
@section('content')

    <style>
        
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
            display: inline-block;
        }
        .status-active { background: #dcfce7; color: #166534; }
        .status-inactive { background: #fee2e2; color: #991b1b; }
        .status-maintenance { background: #ffedd5; color: #9a3412; }
        .status-disposed { background: #e5e7eb; color: #374151; }
        
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
        
        /* Sidebar styles matching your dashboard */
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
        .nav-item-fleet:focus-visible { outline: 2px solid #3b82f6; outline-offset: -2px; background-color: #f1f5f9; }
        .nav-active-fleet { background-color: #eff6ff; color: #2563eb; font-weight: 500; border-left: 3px solid #3b82f6; }
        
        .overlay-fleet {
            position: fixed; inset: 0;
            background: rgba(0,0,0,0.3);
            backdrop-filter: blur(2px);
            z-index: 35;
            display: none;
        }
        .overlay-open { display: block; }
        
        .submenu-item { padding-left: 2.5rem; }
        .rotate-180 { transform: rotate(180deg); }
        
        /* Form styles */
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
        
        /* Table styles */
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
    </style>



<!-- Main Content -->
<div class="min-h-screen">
    <div class="max-w-7xl mx-auto">
        <!-- Header -->
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-800">Vehicle Management</h1>
            <p class="text-gray-500 text-sm mt-1">Manage your fleet vehicles, registrations, and assignments</p>
        </div>
        
        <!-- Tabs -->
        <div class="bg-white rounded-t-xl border-b border-gray-200 px-6" role="tablist">
            <div class="flex space-x-8">
                <button id="tab-all-vehicles" role="tab" aria-controls="all-vehicles-tab" aria-selected="true" data-tab="all-vehicles" class="tab-btn py-4 text-sm font-medium text-gray-600 hover:text-blue-600 transition focus:outline-none focus-visible:text-blue-600">
                    <i class="fas fa-truck-moving mr-2"></i>All Fleet Units
                </button>
                <button id="tab-add-vehicle" role="tab" aria-controls="add-vehicle-tab" aria-selected="false" data-tab="add-vehicle" class="tab-btn py-4 text-sm font-medium text-gray-600 hover:text-blue-600 transition focus:outline-none focus-visible:text-blue-600">
                    <i class="fas fa-plus-circle mr-2"></i>Add New Vehicle
                </button>
                <button id="tab-live-map" role="tab" data-tab="live-map" class="tab-btn py-4 text-sm font-medium text-gray-600 hover:text-blue-600 transition focus:outline-none focus-visible:text-blue-600">
                    <i class="fas fa-map-location-dot mr-2"></i>Live Map View
                </button>
                <button id="tab-status-overview" role="tab" aria-controls="status-overview-tab" aria-selected="false" data-tab="status-overview" class="tab-btn py-4 text-sm font-medium text-gray-600 hover:text-blue-600 transition focus:outline-none focus-visible:text-blue-600">
                    <i class="fas fa-chart-simple mr-2"></i>Status Overview
                </button>
            </div>
        </div>
        
        <!-- Tab Content Container -->
        <div class="bg-white rounded-b-xl shadow-sm p-6">
            <!-- All Vehicles Tab -->
            <div id="all-vehicles-tab" role="tabpanel" aria-labelledby="tab-all-vehicles" class="tab-content">
                <!-- Filters -->
                <div class="flex flex-wrap gap-4 items-center justify-between mb-6">
                    <div class="flex flex-wrap gap-3">
                        <select id="filter-status" class="form-input w-40 text-sm">
                            <option value="">All Status</option>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                            <option value="maintenance">Maintenance</option>
                            <option value="disposed">Disposed</option>
                        </select>
                        <select id="filter-type" class="form-input w-40 text-sm">
                            <option value="">All Types</option>
                            <option value="Saloon">Saloon</option>
                            <option value="SUV">SUV</option>
                            <option value="Truck">Truck</option>
                            <option value="Bus">Bus</option>
                            <option value="Pickup">Pickup</option>
                        </select>
                        <div class="relative">
                            <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 text-sm"></i>
                            <input type="text" id="search-vehicles" placeholder="Search by reg no, make, model..." 
                                   class="form-input pl-9 w-72 text-sm">
                        </div>
                    </div>
                    <div class="flex gap-2">
                        <button id="import-vehicles" class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm hover:bg-blue-700 transition">
                            <i class="fas fa-upload mr-1"></i>Import
                        </button>
                        <button id="export-vehicles" class="px-4 py-2 bg-green-600 text-white rounded-lg text-sm hover:bg-green-700 transition">
                            <i class="fas fa-download mr-1"></i>Export
                        </button>
                        <button id="refresh-vehicles" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg text-sm hover:bg-gray-200 transition">
                            <i class="fas fa-sync-alt mr-1"></i>Refresh
                        </button>
                    </div>
                </div>
                
                <!-- Vehicles Table -->
                <div class="overflow-x-auto">
                    <table class="data-table w-full">
                        <thead>
                            <tr>
                                <th>Reg Number</th>
                                <th>Vehicle Details</th>
                                <th>Type</th>
                                <th>Status</th>
                                <th>Driver</th>
                                <th>Location</th>
                                <th>Insurance Expiry</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="vehicles-table-body">
                            <tr><td colspan="8" class="text-center py-8"><div class="loading-spinner"></div> Loading...</td></tr>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <div id="pagination" class="mt-6 flex justify-between items-center">
                    <div id="pagination-info" class="text-sm text-gray-600"></div>
                    <div id="pagination-buttons" class="flex gap-2"></div>
                </div>
            </div>

            <!-- Import Modal -->
            <div id="import-modal" class="hidden fixed inset-0 bg-black/40 z-50 items-center justify-center p-4">
                <div class="bg-white w-full max-w-2xl rounded-xl shadow-xl">
                    <div class="flex items-center justify-between border-b px-5 py-4">
                        <h3 class="text-lg font-semibold text-gray-800">Bulk Import Vehicles</h3>
                        <button id="close-import-modal" class="text-gray-500 hover:text-gray-700" aria-label="Close import modal" title="Close import modal">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <div class="p-5 space-y-4">
                        <div class="flex items-center justify-between">
                            <p class="text-sm text-gray-600">Download and fill the template, then upload CSV/XLSX/XLS.</p>
                            <a href="{{ route('vehicles.template') }}" class="text-sm text-blue-600 hover:text-blue-700 font-medium">
                                <i class="fas fa-file-download mr-1"></i>Download Template
                            </a>
                        </div>

                        <div id="drop-zone" class="border-2 border-dashed border-gray-300 rounded-lg p-8 text-center hover:border-blue-400 transition">
                            <input id="import-file" type="file" accept=".csv,.xlsx,.xls" class="hidden">
                            <i class="fas fa-cloud-upload-alt text-3xl text-blue-500 mb-3"></i>
                            <p class="text-sm text-gray-700 mb-2">Drag & drop your file here</p>
                            <button type="button" id="choose-file-btn" class="px-4 py-2 text-sm bg-gray-100 rounded-lg hover:bg-gray-200">Choose File</button>
                            <p id="selected-file-name" class="text-xs text-gray-500 mt-3"></p>
                        </div>

                        <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                            <input id="update-existing" type="checkbox" class="rounded border-gray-300">
                            Update existing vehicles (otherwise duplicates are skipped)
                        </label>

                        <div id="import-progress-wrap" class="hidden">
                            <div class="w-full bg-gray-200 rounded-full h-2.5">
                                <div id="import-progress-bar" class="bg-blue-600 h-2.5 rounded-full" style="width: 0%"></div>
                            </div>
                            <p id="import-progress-text" class="text-xs text-gray-600 mt-1">0%</p>
                        </div>

                        <div id="import-result" class="hidden rounded-lg border p-3 text-sm"></div>
                    </div>
                    <div class="px-5 py-4 border-t flex justify-end gap-2">
                        <button id="cancel-import" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg text-sm hover:bg-gray-200">Cancel</button>
                        <button id="submit-import" class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm hover:bg-blue-700">
                            <i class="fas fa-upload mr-1"></i>Import Data
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Add Vehicle Tab -->
            <div id="add-vehicle-tab" role="tabpanel" aria-labelledby="tab-add-vehicle" class="tab-content hidden">
                <form id="vehicle-form" enctype="multipart/form-data">
                    @csrf
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        <!-- Left Column -->
                        <div class="lg:col-span-2 space-y-6">
                            <!-- Basic Information -->
                            <div class="border rounded-lg p-5">
                                <h3 class="font-semibold text-gray-800 mb-4"><i class="fas fa-info-circle mr-2 text-blue-600"></i>Basic Information</h3>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="form-label">Registration Number *</label>
                                        <input type="text" name="registration_number" class="form-input" required>
                                    </div>
                                    <div>
                                        <label class="form-label">Vehicle Type *</label>
                                        <select name="vehicle_type" class="form-input" required>
                                            <option value="">Select Type</option>
                                            <option value="Saloon">Saloon</option>
                                            <option value="SUV">SUV</option>
                                            <option value="Truck">Truck</option>
                                            <option value="Bus">Bus</option>
                                            <option value="Van">Van</option>
                                            <option value="Pickup">Pickup</option>
                                            <option value="Motorcycle">Motorcycle</option>
                                            <option value="Others">Others</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="form-label">Make *</label>
                                        <input type="text" name="make" class="form-input" required>
                                    </div>
                                    <div>
                                        <label class="form-label">Model *</label>
                                        <input type="text" name="model" class="form-input" required>
                                    </div>
                                    <div>
                                        <label class="form-label">Year</label>
                                        <input type="number" name="year" class="form-input" min="1900" max="{{ date('Y') }}">
                                    </div>
                                    <div>
                                        <label class="form-label">Color</label>
                                        <input type="text" name="color" class="form-input">
                                    </div>
                                    <div>
                                        <label class="form-label">Chassis Number *</label>
                                        <input type="text" name="chassis_number" class="form-input" required>
                                    </div>
                                    <div>
                                        <label class="form-label">Engine Number</label>
                                        <input type="text" name="engine_number" class="form-input">
                                    </div>
                                    <div>
                                        <label class="form-label">Current Mileage (km)</label>
                                        <input type="number" name="mileage" class="form-input" min="0">
                                    </div>
                                    <div>
                                        <label class="form-label">Fuel Consumption (km/L)</label>
                                        <input type="number" name="fuel_consumption" class="form-input" step="0.1" min="0">
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Assignment Information -->
                            <div class="border rounded-lg p-5">
                                <h3 class="font-semibold text-gray-800 mb-4"><i class="fas fa-user-check mr-2 text-green-600"></i>Assignment Details</h3>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="form-label">Assigned Driver</label>
                                        <select name="assigned_driver_id" class="form-input" id="driver-select">
                                            <option value="">Select Driver</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="form-label">Status *</label>
                                        <select name="status" class="form-input" required>
                                            <option value="active">Active</option>
                                            <option value="inactive">Inactive</option>
                                            <option value="maintenance">Maintenance</option>
                                            <option value="disposed">Disposed</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Right Column -->
                        <div class="space-y-6">
                            <!-- Location Information -->
                            <div class="border rounded-lg p-5">
                                <h3 class="font-semibold text-gray-800 mb-4"><i class="fas fa-map-marker-alt mr-2 text-red-600"></i>Location</h3>
                                <div class="space-y-3">
                                    <div>
                                        <label class="form-label">Region</label>
                                        <select name="region_id" class="form-input" id="region-select">
                                            <option value="">Select Region</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="form-label">District</label>
                                        <select name="district_id" class="form-input" id="district-select">
                                            <option value="">Select District</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="form-label">Station</label>
                                        <select name="station_id" class="form-input" id="station-select">
                                            <option value="">Select station</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Financial & Documents -->
                            <div class="border rounded-lg p-5">
                                <h3 class="font-semibold text-gray-800 mb-4"><i class="fas fa-file-invoice-dollar mr-2 text-yellow-600"></i>Financial & Documents</h3>
                                <div class="space-y-3">
                                    <div>
                                        <label class="form-label">Purchase Price (GHS)</label>
                                        <input type="number" name="purchase_price" class="form-input" step="0.01" min="0">
                                    </div>
                                    <div>
                                        <label class="form-label">Purchase Date</label>
                                        <input type="date" name="purchase_date" class="form-input">
                                    </div>
                                    <div>
                                        <label class="form-label">Registration Date</label>
                                        <input type="date" name="registration_date" class="form-input">
                                    </div>
                                    <div>
                                        <label class="form-label">Insurance Expiry Date</label>
                                        <input type="date" name="insurance_expiry_date" class="form-input">
                                    </div>
                                    <div>
                                        <label class="form-label">Next Inspection Date</label>
                                        <input type="date" name="next_inspection_date" class="form-input">
                                    </div>
                                    <div>
                                        <label class="form-label">Vehicle Photo</label>
                                        <input type="file" name="photo" class="form-input" accept="image/*">
                                        <p class="text-xs text-gray-500 mt-1">Max 10MB. JPG, PNG, GIF</p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="border rounded-lg p-5">
                                <label class="form-label">Notes</label>
                                <textarea name="notes" rows="3" class="form-input" placeholder="Additional notes about the vehicle..."></textarea>
                            </div>
                        </div>
                    </div>
                    
                    <div class="flex justify-end gap-3 mt-8 pt-6 border-t">
                        <button type="button" id="cancel-form" class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition">
                            Cancel
                        </button>
                        <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                            <i class="fas fa-save mr-2"></i>Add Vehicle
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- Status Overview Tab -->
            <div id="status-overview-tab" role="tabpanel" aria-labelledby="tab-status-overview" class="tab-content hidden">
                <!-- Statistics Cards -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-5 mb-8" id="stats-cards">
                    <div class="stat-card p-5"><div class="loading-spinner"></div></div>
                </div>
                
                <!-- Charts Row -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                    <!-- Status Distribution -->
                    <div class="border rounded-lg p-5">
                        <h3 class="font-semibold text-gray-800 mb-4"><i class="fas fa-chart-pie mr-2"></i>Vehicle Status Distribution</h3>
                        <canvas id="status-chart" class="h-64"></canvas>
                    </div>
                    
                    <!-- Vehicle Type Distribution -->
                    <div class="border rounded-lg p-5">
                        <h3 class="font-semibold text-gray-800 mb-4"><i class="fas fa-chart-bar mr-2"></i>Vehicle Type Distribution</h3>
                        <canvas id="type-chart" class="h-64"></canvas>
                    </div>
                </div>
                
                <!-- Alerts and Warnings -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div class="border rounded-lg p-5">
                        <h3 class="font-semibold text-gray-800 mb-4"><i class="fas fa-exclamation-triangle mr-2 text-orange-500"></i>Insurance Alerts</h3>
                        <div id="insurance-alerts" class="space-y-3"></div>
                    </div>
                    
                    <div class="border rounded-lg p-5">
                        <h3 class="font-semibold text-gray-800 mb-4"><i class="fas fa-tachometer-alt mr-2 text-blue-500"></i>Maintenance Overview</h3>
                        <div id="maintenance-alerts" class="space-y-3"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
let currentPage = 1;
let statusChart = null;
let typeChart = null;

function activateTab(tabId, updateHistory = true) {
    if (!tabId) return;

    if (tabId === 'live-map') {
        window.location.href = '{{ route("vehicles.tracking") }}';
        return;
    }

    $('.tab-btn').removeClass('tab-active text-blue-600 border-blue-600').addClass('text-gray-600').attr('aria-selected', 'false');
    const $activeTab = $(`.tab-btn[data-tab="${tabId}"]`);
    $activeTab.addClass('tab-active text-blue-600 border-blue-600').attr('aria-selected', 'true');

    $('.tab-content').addClass('hidden');
    $(`#${tabId}-tab`).removeClass('hidden');

    if (tabId === 'status-overview') {
        loadStatistics();
    }

    if (updateHistory) {
        history.pushState(null, '', `?tab=${tabId}`);
    }
}

// Tab Management
$(document).ready(function() {
    // Load form data
    loadFormData();
    loadVehicles();
    
    // Tab switching
    $('.tab-btn').on('click', function(e) {
        e.preventDefault();
        const tabId = $(this).data('tab');
        activateTab(tabId, true);
    });
    
    // Check URL param for initial tab
    const urlParams = new URLSearchParams(window.location.search);
    const activeTab = urlParams.get('tab');
    if (activeTab && ['all-vehicles', 'add-vehicle', 'status-overview'].includes(activeTab)) {
        activateTab(activeTab, false);
    } else {
        activateTab('all-vehicles', false);
    }
    
    // Filter events
    $('#filter-status, #filter-type, #search-vehicles').on('change keyup', function() {
        currentPage = 1;
        loadVehicles();
    });
    
    // Refresh button
    $('#refresh-vehicles').click(function() {
        loadVehicles();
    });

    // Import button
    $('#import-vehicles').click(() => openImportModal());
    
    // Export button
    $('#export-vehicles').click(() => {
        window.location.href = '{{ route("vehicles.export") }}';
    });
    
    // Form submission
    $('#vehicle-form').submit(function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        const $form = $(this);
        const $submitBtn = $form.find('button[type="submit"]');
        const originalHtml = $submitBtn.html();

        $submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-2"></i>Adding...');
        
        $.ajax({
            url: '{{ route("vehicles.store") }}',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function(response) {
                if (response && response.success === false) {
                    Swal.fire('Error', response.message || 'Failed to add vehicle', 'error');
                    return;
                }
                Swal.fire('Success', 'Vehicle added successfully!', 'success');
                $('#vehicle-form')[0].reset();
                loadVehicles();
                activateTab('all-vehicles', true);
            },
            error: function(xhr) {
                let errors = xhr.responseJSON && xhr.responseJSON.errors ? xhr.responseJSON.errors : null;
                if (errors) {
                    let errorMsg = Object.values(errors).flat().join('\n');
                    Swal.fire('Error', errorMsg, 'error');
                } else {
                    Swal.fire('Error', 'Failed to add vehicle', 'error');
                }
            },
            complete: function() {
                $submitBtn.prop('disabled', false).html(originalHtml);
            }
        });
    });
    
    $('#cancel-form').click(() => {
        $('#vehicle-form')[0].reset();
        activateTab('all-vehicles', true);
    });

    // Import modal controls
    $('#close-import-modal, #cancel-import').click(() => closeImportModal());
    $('#choose-file-btn').click(() => $('#import-file').click());
    $('#drop-zone').on('dragover', function(e) {
        e.preventDefault();
        $(this).addClass('border-blue-500 bg-blue-50');
    });
    $('#drop-zone').on('dragleave', function() {
        $(this).removeClass('border-blue-500 bg-blue-50');
    });
    $('#drop-zone').on('drop', function(e) {
        e.preventDefault();
        $(this).removeClass('border-blue-500 bg-blue-50');
        const files = e.originalEvent.dataTransfer.files;
        if (files.length) {
            $('#import-file')[0].files = files;
            $('#selected-file-name').text(files[0].name);
        }
    });
    $('#import-file').on('change', function() {
        const file = this.files && this.files[0] ? this.files[0] : null;
        $('#selected-file-name').text(file ? file.name : '');
    });
    $('#submit-import').click(() => submitImport());

    const shouldOpenImport = new URLSearchParams(window.location.search).get('showImport');
    if (shouldOpenImport === '1') {
        openImportModal();
    }
});

function openImportModal() {
    $('#import-modal').removeClass('hidden').addClass('flex');
}

function closeImportModal() {
    $('#import-modal').addClass('hidden').removeClass('flex');
}

function submitImport() {
    const fileInput = $('#import-file')[0];
    if (!fileInput.files || !fileInput.files.length) {
        Swal.fire('No file selected', 'Please choose a CSV/XLSX/XLS file first.', 'warning');
        return;
    }

    const formData = new FormData();
    formData.append('file', fileInput.files[0]);
    formData.append('update_existing', $('#update-existing').is(':checked') ? '1' : '0');

    $('#import-progress-wrap').removeClass('hidden');
    $('#import-result').addClass('hidden').empty();
    $('#submit-import').prop('disabled', true).text('Importing...');

    $.ajax({
        url: '{{ route("vehicles.import") }}',
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        xhr: function() {
            const xhr = new window.XMLHttpRequest();
            xhr.upload.addEventListener('progress', function(evt) {
                if (evt.lengthComputable) {
                    const percent = Math.round((evt.loaded / evt.total) * 100);
                    $('#import-progress-bar').css('width', `${percent}%`);
                    $('#import-progress-text').text(`${percent}%`);
                }
            }, false);
            return xhr;
        },
        success: function(response) {
            const data = response.data || {};
            const errors = (data.errors || []).slice(0, 10);
            const failedRowsLink = (data.failed || 0) > 0
                ? `<p class="mt-2"><a href="{{ route("vehicles.import.failed.download") }}" class="text-blue-700 underline hover:text-blue-900"><i class="fas fa-file-csv mr-1"></i>Download Failed Rows CSV</a></p>`
                : '';
            const html = `
                <p><strong>Total Rows:</strong> ${data.total_rows || 0}</p>
                <p><strong>Created:</strong> ${data.created || 0}</p>
                <p><strong>Updated:</strong> ${data.updated || 0}</p>
                <p><strong>Skipped:</strong> ${data.skipped || 0}</p>
                <p><strong>Failed:</strong> ${data.failed || 0}</p>
                ${errors.length ? `<hr class="my-2"><p class="font-medium">Errors (first 10):</p><ul class="list-disc pl-5">${errors.map(e => `<li>${e}</li>`).join('')}</ul>` : ''}
                ${failedRowsLink}
            `;
            $('#import-result').removeClass('hidden').addClass('border-green-200 bg-green-50 text-green-900').html(html);
            loadVehicles();
        },
        error: function(xhr) {
            const msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Import failed. Please check your file and try again.';
            $('#import-result').removeClass('hidden').addClass('border-red-200 bg-red-50 text-red-900').html(msg);
        },
        complete: function() {
            $('#submit-import').prop('disabled', false).html('<i class="fas fa-upload mr-1"></i>Import Data');
        }
    });
}

// Load vehicles with filters
function loadVehicles() {
    const $refreshBtn = $('#refresh-vehicles');
    const $refreshIcon = $refreshBtn.find('i');

    $refreshBtn.prop('disabled', true);
    $refreshIcon.addClass('fa-spin');

    const filters = {
        status: $('#filter-status').val(),
        vehicle_type: $('#filter-type').val(),
        search: $('#search-vehicles').val(),
        page: currentPage
    };
    
    $.ajax({
        url: '{{ route("vehicles.data") }}',
        method: 'GET',
        data: filters,
        success: function(response) {
            if (!response || !response.success) {
                $('#vehicles-table-body').html('<tr><td colspan="8" class="text-center py-8 text-red-500">Failed to load vehicles</td></tr>');
                return;
            }
            renderVehiclesTable(response.data);
            renderPagination(response.pagination);
        },
        error: function() {
            $('#vehicles-table-body').html('<tr><td colspan="8" class="text-center py-8 text-red-500">Failed to load vehicles</td></tr>');
        },
        complete: function() {
            $refreshBtn.prop('disabled', false);
            $refreshIcon.removeClass('fa-spin');
        }
    });
}

function renderVehiclesTable(vehicles) {
    if (!vehicles || vehicles.length === 0) {
        $('#vehicles-table-body').html('<tr><td colspan="8" class="text-center py-8 text-gray-500">No vehicles found</td></tr>');
        return;
    }
    
    let html = '';
    vehicles.forEach(vehicle => {
        const statusClass = `status-${vehicle.status}`;
        const insuranceClass = vehicle.insurance_expiry_date && new Date(vehicle.insurance_expiry_date) < new Date() ? 'text-red-600 font-semibold' : '';
        const driverName = (vehicle.assigned_driver && vehicle.assigned_driver.name)
            ? vehicle.assigned_driver.name
            : '<span class="text-gray-400">Unassigned</span>';
        const locationName = (vehicle.district && vehicle.district.name)
            ? vehicle.district.name
            : ((vehicle.region && vehicle.region.name) ? vehicle.region.name : 'N/A');
        
        html += `
            <tr>
                <td class="font-medium">${vehicle.registration_number}</td>
                <td>${vehicle.make} ${vehicle.model}<br><span class="text-xs text-gray-500">${vehicle.year || 'N/A'}</span></td>
                <td>${vehicle.vehicle_type}</td>
                <td><span class="status-badge ${statusClass}">${vehicle.status}</span></td>
                <td>${driverName}</td>
                <td>${locationName}</td>
                <td class="${insuranceClass}">${vehicle.insurance_expiry_date || 'N/A'}</td>
                <td>
                    <div class="flex gap-2">
                        <a href="/vehicles/tracking?id=${vehicle.id}" class="text-indigo-600 hover:text-indigo-800" aria-label="Track Live" title="Track Live">
                            <i class="fas fa-location-crosshairs"></i>
                        </a>
                        <a href="/vehicles/${vehicle.id}" class="text-green-600 hover:text-green-800" aria-label="View Vehicle Details" title="View Details">
                            <i class="fas fa-eye"></i>
                        </a>
                        <a href="/vehicles/${vehicle.id}/edit" class="text-blue-600 hover:text-blue-800" aria-label="Edit Vehicle" title="Edit Vehicle">
                            <i class="fas fa-edit"></i>
                        </a>
                        <button onclick="deleteVehicle(${vehicle.id})" class="text-red-600 hover:text-red-800" aria-label="Delete Vehicle" title="Delete Vehicle">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `;
    });
    $('#vehicles-table-body').html(html);
}

function renderPagination(pagination) {
    if (!pagination) return;
    
    $('#pagination-info').text(`Showing ${(pagination.current_page - 1) * pagination.per_page + 1} to ${Math.min(pagination.current_page * pagination.per_page, pagination.total)} of ${pagination.total} vehicles`);
    
    let buttons = '';
    for (let i = 1; i <= pagination.last_page; i++) {
        if (i === pagination.current_page) {
            buttons += `<button class="px-3 py-1 bg-blue-600 text-white rounded">${i}</button>`;
        } else {
            buttons += `<button onclick="goToPage(${i})" class="px-3 py-1 border rounded hover:bg-gray-50">${i}</button>`;
        }
    }
    $('#pagination-buttons').html(buttons);
}

function goToPage(page) {
    currentPage = page;
    loadVehicles();
}

function deleteVehicle(id) {
    Swal.fire({
        title: 'Are you sure?',
        text: "This vehicle will be soft-deleted!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        confirmButtonText: 'Yes, delete it!'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: `/vehicles/${id}`,
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                success: function() {
                    Swal.fire('Deleted!', 'Vehicle has been deleted.', 'success');
                    loadVehicles();
                },
                error: function() {
                    Swal.fire('Error!', 'Failed to delete vehicle.', 'error');
                }
            });
        }
    });
}

function loadFormData() {
    $.ajax({
        url: '{{ route("vehicles.form-data") }}',
        method: 'GET',
        success: function(response) {
            if (response.success) {
                const data = response.data;
                
                // Populate dropdowns
                populateSelect('#region-select', data.regions, 'id', 'name');
                populateSelect('#district-select', data.districts, 'id', 'name');
                populateSelect('#station-select', data.stations, 'id', 'name');
                populateSelect('#office-select', data.offices, 'id', 'name');
                populateSelect('#driver-select', data.drivers, 'id', 'name');
            }
        },
        error: function() {
            console.error('Failed to load form data');
        }
    });
}

function populateSelect(selector, items, valueKey, textKey) {
    const $select = $(selector);
    const currentValue = $select.val();
    $select.empty().append('<option value="">Select</option>');
    if (items && items.length) {
        items.forEach(item => {
            $select.append(`<option value="${item[valueKey]}">${item[textKey]}</option>`);
        });
    }
    if (currentValue) $select.val(currentValue);
}

function loadStatistics() {
    $.ajax({
        url: '{{ route("vehicles.statistics") }}',
        method: 'GET',
        success: function(response) {
            if (!response || !response.success) {
                return;
            }
            renderStatsCards(response.data);
            renderStatusChart(response.data);
            renderTypeChart(response.data.by_type);
            renderAlerts(response.data);
        },
        error: function() {
            console.error('Failed to load statistics');
        }
    });
}

function renderStatsCards(stats) {
    const cards = [
        { label: 'Total Vehicles', value: stats.total || 0, icon: 'fa-truck', color: 'blue' },
        { label: 'Active', value: stats.active || 0, icon: 'fa-check-circle', color: 'green' },
        { label: 'In Maintenance', value: stats.maintenance || 0, icon: 'fa-tools', color: 'orange' },
        { label: 'Unassigned', value: stats.unassigned || 0, icon: 'fa-user-slash', color: 'red' },
        { label: 'Assigned', value: stats.assigned || 0, icon: 'fa-user-check', color: 'purple' },
        { label: 'Expired Insurance', value: stats.expired_insurance || 0, icon: 'fa-file-invoice', color: 'red' }
    ];
    
    let html = '';
    cards.forEach(card => {
        html += `
            <div class="stat-card p-5 text-center">
                <i class="fas ${card.icon} text-3xl text-${card.color}-500 mb-2"></i>
                <p class="text-2xl font-bold text-gray-800">${card.value}</p>
                <p class="text-sm text-gray-600">${card.label}</p>
            </div>
        `;
    });
    $('#stats-cards').html(html);
}

function renderStatusChart(stats) {
    const ctx = document.getElementById('status-chart').getContext('2d');
    if (statusChart) statusChart.destroy();
    
    statusChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['Active', 'Inactive', 'Maintenance', 'Disposed'],
            datasets: [{
                data: [stats.active || 0, stats.inactive || 0, stats.maintenance || 0, stats.disposed || 0],
                backgroundColor: ['#22c55e', '#64748b', '#f97316', '#ef4444'],
                borderWidth: 0
            }]
        },
        options: { responsive: true, maintainAspectRatio: true, plugins: { legend: { position: 'bottom' } } }
    });
}

function renderTypeChart(types) {
    const ctx = document.getElementById('type-chart').getContext('2d');
    if (typeChart) typeChart.destroy();
    
    const safeTypes = Array.isArray(types) ? types : [];
    const labels = safeTypes.map(t => t.vehicle_type);
    const data = safeTypes.map(t => t.count);
    
    typeChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Number of Vehicles',
                data: data,
                backgroundColor: '#3b82f6',
                borderRadius: 8
            }]
        },
        options: { responsive: true, maintainAspectRatio: true, scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } } }
    });
}

function renderAlerts(stats) {
    // Insurance alerts
    let insuranceHtml = '';
    if (stats.expiring_insurance > 0) {
        insuranceHtml += `<div class="flex items-center gap-3 p-3 bg-yellow-50 rounded-lg"><i class="fas fa-clock text-yellow-600"></i><div><p class="font-medium">${stats.expiring_insurance} vehicle(s) have insurance expiring within 30 days</p><p class="text-xs text-yellow-600">Renew soon to avoid penalties</p></div></div>`;
    }
    if (stats.expired_insurance > 0) {
        insuranceHtml += `<div class="flex items-center gap-3 p-3 bg-red-50 rounded-lg"><i class="fas fa-exclamation-circle text-red-600"></i><div><p class="font-medium">${stats.expired_insurance} vehicle(s) have expired insurance</p><p class="text-xs text-red-600">Immediate action required</p></div></div>`;
    }
    if (!stats.expiring_insurance && !stats.expired_insurance) {
        insuranceHtml = `<div class="text-center text-gray-500 py-4"><i class="fas fa-check-circle text-green-500 text-2xl mb-2"></i><p>All insurance documents are up to date</p></div>`;
    }
    $('#insurance-alerts').html(insuranceHtml);
    
    // Maintenance alerts
    let maintenanceHtml = '';
    if (stats.maintenance_due > 0) {
        maintenanceHtml += `<div class="flex items-center gap-3 p-3 bg-orange-50 rounded-lg"><i class="fas fa-tools text-orange-600"></i><div><p class="font-medium">${stats.maintenance_due} vehicle(s) due for maintenance</p><p class="text-xs text-orange-600">Schedule service appointments</p></div></div>`;
    }
    if (stats.high_mileage > 0) {
        maintenanceHtml += `<div class="flex items-center gap-3 p-3 bg-blue-50 rounded-lg"><i class="fas fa-tachometer-alt text-blue-600"></i><div><p class="font-medium">${stats.high_mileage} vehicle(s) with high mileage (>100,000km)</p><p class="text-xs text-blue-600">Consider major service or replacement</p></div></div>`;
    }
    if (!stats.maintenance_due && !stats.high_mileage) {
        maintenanceHtml = `<div class="text-center text-gray-500 py-4"><i class="fas fa-check-circle text-green-500 text-2xl mb-2"></i><p>All vehicles are in good condition</p></div>`;
    }
    $('#maintenance-alerts').html(maintenanceHtml);
}

// Redundant sidebar functions removed - handled by app layout
</script>
@endsection
