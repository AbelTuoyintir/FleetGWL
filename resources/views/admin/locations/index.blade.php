{{-- resources/views/admin/locations/index.blade.php --}}
@extends('layouts.app')
@section('title', 'Location Management - Regions, Districts & Stations')
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
    
    .status-badge {
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }
    .status-active { background: #dcfce7; color: #166534; }
    .status-inactive { background: #fee2e2; color: #991b1b; }
    
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
        max-width: 500px;
        width: 90%;
        max-height: 85vh;
        overflow-y: auto;
    }
    
    .form-input, .form-select {
        width: 100%;
        padding: 8px 12px;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        font-size: 14px;
        transition: all 0.2s;
    }
    .form-input:focus, .form-select:focus {
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
    
    .tab-btn {
        transition: all 0.2s;
    }
    .tab-active {
        border-bottom: 2px solid #2563eb;
        color: #1e40af;
        font-weight: 600;
    }
    
    @media (max-width: 768px) {
        .stat-card { padding: 12px; }
        .data-table th, .data-table td { padding: 8px 4px; }
    }
</style>

<div class="min-h-screen bg-gray-50 py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <!-- Header -->
        <div class="mb-6 flex justify-between items-center flex-wrap gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Location Management</h1>
                <p class="text-gray-500 text-sm mt-1">Manage regions, districts, and stations</p>
            </div>
            <div class="flex gap-3">
                <button onclick="window.print()" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg text-sm hover:bg-gray-200 transition">
                    <i class="fas fa-print"></i> Print
                </button>
                <button id="refreshBtn" class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm hover:bg-blue-700 transition">
                    <i class="fas fa-sync-alt"></i> Refresh
                </button>
            </div>
        </div>
        
        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5 mb-6">
            <div class="stat-card p-5">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-xs uppercase tracking-wide">Total Regions</p>
                        <p class="text-2xl font-bold text-gray-800" id="totalRegions">0</p>
                    </div>
                    <i class="fas fa-map-marker-alt text-blue-400 text-3xl opacity-50"></i>
                </div>
            </div>
            <div class="stat-card p-5">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-xs uppercase tracking-wide">Total Districts</p>
                        <p class="text-2xl font-bold text-gray-800" id="totalDistricts">0</p>
                    </div>
                    <i class="fas fa-city text-green-400 text-3xl opacity-50"></i>
                </div>
            </div>
            <div class="stat-card p-5">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-xs uppercase tracking-wide">Total Stations</p>
                        <p class="text-2xl font-bold text-gray-800" id="totalStations">0</p>
                    </div>
                    <i class="fas fa-building text-purple-400 text-3xl opacity-50"></i>
                </div>
            </div>
        </div>
        
        <!-- Tabs -->
        <div class="bg-white rounded-t-xl border-b border-gray-200 px-6">
            <div class="flex space-x-8 overflow-x-auto">
                <button data-tab="regions" class="tab-btn py-4 text-sm font-medium text-gray-600 hover:text-blue-600 transition tab-active">
                    <i class="fas fa-map-marker-alt mr-2"></i>Regions
                </button>
                <button data-tab="districts" class="tab-btn py-4 text-sm font-medium text-gray-600 hover:text-blue-600 transition">
                    <i class="fas fa-city mr-2"></i>Districts
                </button>
                <button data-tab="stations" class="tab-btn py-4 text-sm font-medium text-gray-600 hover:text-blue-600 transition">
                    <i class="fas fa-building mr-2"></i>Stations
                </button>
            </div>
        </div>
        
        <div class="bg-white rounded-b-xl shadow-sm p-6">
            
            <!-- ==================== REGIONS TAB ==================== -->
            <div id="regions-tab" class="tab-content">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="font-semibold text-gray-800">
                        <i class="fas fa-map-marker-alt text-blue-600 mr-2"></i>Manage Regions
                    </h3>
                    <button onclick="openRegionModal()" class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm hover:bg-blue-700 transition flex items-center gap-2">
                        <i class="fas fa-plus"></i> Add Region
                    </button>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="data-table w-full">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Code</th>
                                <th>Status</th>
                                <th>Created At</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="regionsTableBody">
                            <tr><td colspan="6" class="text-center py-8"><div class="loading-spinner"></div> Loading...</td></tr>
                        </tbody>
                    </table>
                </div>
                <div id="regionsPagination" class="mt-4 flex justify-center"></div>
            </div>
            
            <!-- ==================== DISTRICTS TAB ==================== -->
            <div id="districts-tab" class="tab-content hidden">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="font-semibold text-gray-800">
                        <i class="fas fa-city text-green-600 mr-2"></i>Manage Districts
                    </h3>
                    <button onclick="openDistrictModal()" class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm hover:bg-blue-700 transition flex items-center gap-2">
                        <i class="fas fa-plus"></i> Add District
                    </button>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="data-table w-full">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Code</th>
                                <th>Region</th>
                                <th>Status</th>
                                <th>Created At</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="districtsTableBody">
                            <tr><td colspan="7" class="text-center py-8"><div class="loading-spinner"></div> Loading...</td></tr>
                        </tbody>
                    </table>
                </div>
                <div id="districtsPagination" class="mt-4 flex justify-center"></div>
            </div>
            
            <!-- ==================== STATIONS TAB ==================== -->
            <div id="stations-tab" class="tab-content hidden">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="font-semibold text-gray-800">
                        <i class="fas fa-building text-purple-600 mr-2"></i>Manage Stations
                    </h3>
                    <button onclick="openStationModal()" class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm hover:bg-blue-700 transition flex items-center gap-2">
                        <i class="fas fa-plus"></i> Add Station
                    </button>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="data-table w-full">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Code</th>
                                <th>Type</th>
                                <th>Region</th>
                                <th>District</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="stationsTableBody">
                            <tr><td colspan="8" class="text-center py-8"><div class="loading-spinner"></div> Loading...</td></tr>
                        </tbody>
                    </table>
                </div>
                <div id="stationsPagination" class="mt-4 flex justify-center"></div>
            </div>
        </div>
    </div>
</div>

<!-- ==================== MODALS ==================== -->

<!-- Region Modal -->
<div id="regionModal" class="modal">
    <div class="modal-content">
        <div class="sticky top-0 bg-white px-6 py-4 border-b border-gray-200 flex justify-between items-center">
            <h3 class="text-xl font-semibold text-gray-800">
                <i class="fas fa-map-marker-alt text-blue-600 mr-2"></i>
                <span id="regionModalTitle">Add Region</span>
            </h3>
            <button onclick="closeRegionModal()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        <form id="regionForm" class="p-6 space-y-4">
            @csrf
            <input type="hidden" name="id" id="regionId">
            <div>
                <label class="form-label">Region Name *</label>
                <input type="text" name="name" id="regionName" class="form-input" required>
            </div>
            <div>
                <label class="form-label">Region Code *</label>
                <input type="text" name="code" id="regionCode" class="form-input" required>
            </div>
            <div>
                <label class="form-label">Status</label>
                <select name="status" id="regionStatus" class="form-select">
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>
            <div class="flex justify-end gap-3 pt-4 border-t">
                <button type="button" onclick="closeRegionModal()" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Save Region</button>
            </div>
        </form>
    </div>
</div>

<!-- District Modal -->
<div id="districtModal" class="modal">
    <div class="modal-content">
        <div class="sticky top-0 bg-white px-6 py-4 border-b border-gray-200 flex justify-between items-center">
            <h3 class="text-xl font-semibold text-gray-800">
                <i class="fas fa-city text-green-600 mr-2"></i>
                <span id="districtModalTitle">Add District</span>
            </h3>
            <button onclick="closeDistrictModal()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        <form id="districtForm" class="p-6 space-y-4">
            @csrf
            <input type="hidden" name="id" id="districtId">
            <div>
                <label class="form-label">District Name *</label>
                <input type="text" name="name" id="districtName" class="form-input" required>
            </div>
            <div>
                <label class="form-label">District Code (Optional)</label>
                <input type="text" name="code" id="districtCode" class="form-input">
            </div>
            <div>
                <label class="form-label">Region *</label>
                <select name="region_id" id="districtRegionId" class="form-select" required>
                    <option value="">Select Region</option>
                </select>
            </div>
            <div>
                <label class="form-label">Status</label>
                <select name="status" id="districtStatus" class="form-select">
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>
            <div class="flex justify-end gap-3 pt-4 border-t">
                <button type="button" onclick="closeDistrictModal()" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">Save District</button>
            </div>
        </form>
    </div>
</div>

<!-- Station Modal -->
<div id="stationModal" class="modal">
    <div class="modal-content">
        <div class="sticky top-0 bg-white px-6 py-4 border-b border-gray-200 flex justify-between items-center">
            <h3 class="text-xl font-semibold text-gray-800">
                <i class="fas fa-building text-purple-600 mr-2"></i>
                <span id="stationModalTitle">Add Station</span>
            </h3>
            <button onclick="closeStationModal()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        <form id="stationForm" class="p-6 space-y-4">
            @csrf
            <input type="hidden" name="id" id="stationId">
            <div>
                <label class="form-label">Station Name *</label>
                <input type="text" name="name" id="stationName" class="form-input" required>
            </div>
            <div>
                <label class="form-label">Station Code *</label>
                <input type="text" name="code" id="stationCode" class="form-input" required>
            </div>
            <div>
                <label class="form-label">Station Type</label>
                <select name="type" id="stationType" class="form-select">
                    <option value="treatment_plant">Treatment Plant</option>
                    <option value="pumping_station">Pumping Station</option>
                    <option value="distribution">Distribution</option>
                    <option value="reservoir">Reservoir</option>
                    <option value="workshop">Workshop</option>
                </select>
            </div>
            <div>
                <label class="form-label">Region *</label>
                <select name="region_id" id="stationRegionId" class="form-select" required>
                    <option value="">Select Region</option>
                </select>
            </div>
            <div>
                <label class="form-label">District *</label>
                <select name="district_id" id="stationDistrictId" class="form-select">
                    <option value="">Select District</option>
                </select>
            </div>
            <div>
                <label class="form-label">Status</label>
                <select name="status" id="stationStatus" class="form-select">
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>
            <div class="flex justify-end gap-3 pt-4 border-t">
                <button type="button" onclick="closeStationModal()" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700">Save Station</button>
            </div>
        </form>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
let currentPage = { regions: 1, districts: 1, stations: 1 };
let currentTab = 'regions';

$(document).ready(function() {
    loadRegions();
    loadStats();
    
    // Tab switching
    $('.tab-btn').click(function() {
        let tabId = $(this).data('tab');
        currentTab = tabId;
        
        $('.tab-btn').removeClass('tab-active text-blue-600 border-blue-600').addClass('text-gray-600');
        $(this).addClass('tab-active text-blue-600 border-blue-600');
        
        $('.tab-content').addClass('hidden');
        $(`#${tabId}-tab`).removeClass('hidden');
        
        if (tabId === 'regions') loadRegions();
        else if (tabId === 'districts') loadDistricts();
        else if (tabId === 'stations') loadStations();
    });
    
    $('#refreshBtn').click(() => location.reload());
    
    // Region Form Submit
    $('#regionForm').submit(function(e) {
        e.preventDefault();
        saveRegion();
    });
    
    // District Form Submit
    $('#districtForm').submit(function(e) {
        e.preventDefault();
        saveDistrict();
    });
    
    // Station Form Submit
    $('#stationForm').submit(function(e) {
        e.preventDefault();
        saveStation();
    });
    
    // Load regions for dropdowns
    loadRegionDropdowns();
});

function loadStats() {
    $.ajax({
        url: '{{ route("locations.stats") }}',
        method: 'GET',
        success: function(response) {
            if (response.success) {
                $('#totalRegions').text(response.total_regions);
                $('#totalDistricts').text(response.total_districts);
                $('#totalStations').text(response.total_stations);
            }
        }
    });
}

// ==================== REGIONS ====================
function loadRegions(page = 1) {
    $.ajax({
        url: '{{ route("locations.regions.data") }}?page=' + page,
        method: 'GET',
        success: function(response) {
            if (response.success) {
                renderRegionsTable(response.data);
                renderPagination('regions', response.pagination);
            }
        }
    });
}

function renderRegionsTable(regions) {
    if (!regions || regions.length === 0) {
        $('#regionsTableBody').html('<tr><td colspan="6" class="text-center py-8 text-gray-500">No regions found</td></tr>');
        return;
    }
    
    let html = '';
    regions.forEach(region => {
        html += `
            <tr class="hover:bg-gray-50">
                <td class="px-6 py-4">#${region.id}</td>
                <td class="px-6 py-4 font-medium">${escapeHtml(region.name)}</td>
                <td class="px-6 py-4"><code class="bg-gray-100 px-2 py-1 rounded">${escapeHtml(region.code)}</code></td>
                <td class="px-6 py-4">
                    <span class="status-badge status-${region.status}">
                        <i class="fas ${region.status === 'active' ? 'fa-check-circle' : 'fa-times-circle'}"></i>
                        ${region.status === 'active' ? 'Active' : 'Inactive'}
                    </span>
                </td>
                <td class="px-6 py-4 text-sm">${formatDate(region.created_at)}</td>
                <td class="px-6 py-4">
                    <div class="flex gap-2">
                        <button onclick="editRegion(${region.id})" class="text-blue-600 hover:text-blue-800" title="Edit"><i class="fas fa-edit"></i></button>
                        <button onclick="deleteRegion(${region.id})" class="text-red-600 hover:text-red-800" title="Delete"><i class="fas fa-trash"></i></button>
                    </div>
                </td>
            </tr>
        `;
    });
    $('#regionsTableBody').html(html);
}

function openRegionModal() {
    $('#regionModalTitle').text('Add Region');
    $('#regionForm')[0].reset();
    $('#regionId').val('');
    $('#regionModal').addClass('active');
}

function editRegion(id) {
    $.ajax({
        url: '/locations/regions/' + id,
        method: 'GET',
        success: function(response) {
            if (response.success) {
                $('#regionModalTitle').text('Edit Region');
                $('#regionId').val(response.data.id);
                $('#regionName').val(response.data.name);
                $('#regionCode').val(response.data.code);
                $('#regionStatus').val(response.data.status);
                $('#regionModal').addClass('active');
            }
        }
    });
}

function saveRegion() {
    let id = $('#regionId').val();
    let url = id ? '/locations/regions/' + id : '{{ route("locations.regions.store") }}';
    let method = id ? 'PUT' : 'POST';
    
    let formData = {
        name: $('#regionName').val(),
        code: $('#regionCode').val(),
        status: $('#regionStatus').val(),
        _token: '{{ csrf_token() }}'
    };
    if (id) formData._method = 'PUT';
    
    $.ajax({
        url: url,
        method: 'POST',
        data: formData,
        success: function(response) {
            if (response.success) {
                Swal.fire('Success', response.message, 'success');
                closeRegionModal();
                loadRegions(currentPage.regions);
                loadStats();
                loadRegionDropdowns();
            }
        },
        error: function(xhr) {
            Swal.fire('Error', xhr.responseJSON?.message || 'Something went wrong', 'error');
        }
    });
}

function deleteRegion(id) {
    Swal.fire({
        title: 'Delete Region?',
        text: 'This will also delete all districts and stations in this region!',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        confirmButtonText: 'Yes, delete'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: '/locations/regions/' + id,
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                success: function(response) {
                    Swal.fire('Deleted!', response.message, 'success');
                    loadRegions(currentPage.regions);
                    loadStats();
                    loadRegionDropdowns();
                },
                error: function() {
                    Swal.fire('Error', 'Failed to delete region', 'error');
                }
            });
        }
    });
}

function closeRegionModal() {
    $('#regionModal').removeClass('active');
}

// ==================== DISTRICTS ====================
function loadDistricts(page = 1) {
    $.ajax({
        url: '{{ route("locations.districts.data") }}?page=' + page,
        method: 'GET',
        success: function(response) {
            if (response.success) {
                renderDistrictsTable(response.data);
                renderPagination('districts', response.pagination);
            }
        },
        error: function(xhr) {
            const msg = xhr.responseJSON?.message || 'Failed to load districts';
            $('#districtsTableBody').html(`<tr><td colspan="7" class="text-center py-8 text-red-600">${escapeHtml(msg)}</td></tr>`);
        }
    });
}

function renderDistrictsTable(districts) {
    if (!districts || districts.length === 0) {
        $('#districtsTableBody').html('<tr><td colspan="7" class="text-center py-8 text-gray-500">No districts found</td></tr>');
        return;
    }
    
    let html = '';
    districts.forEach(district => {
        html += `
            <tr class="hover:bg-gray-50">
                <td class="px-6 py-4">#${district.id}</td>
                <td class="px-6 py-4 font-medium">${escapeHtml(district.name)}</td>
                <td class="px-6 py-4"><code class="bg-gray-100 px-2 py-1 rounded">${escapeHtml(district.code)}</code></td>
                <td class="px-6 py-4">${escapeHtml(district.region_name)}</td>
                <td class="px-6 py-4">
                    <span class="status-badge status-${district.status}">
                        <i class="fas ${district.status === 'active' ? 'fa-check-circle' : 'fa-times-circle'}"></i>
                        ${district.status === 'active' ? 'Active' : 'Inactive'}
                    </span>
                </td>
                <td class="px-6 py-4 text-sm">${formatDate(district.created_at)}</td>
                <td class="px-6 py-4">
                    <div class="flex gap-2">
                        <button onclick="editDistrict(${district.id})" class="text-blue-600 hover:text-blue-800"><i class="fas fa-edit"></i></button>
                        <button onclick="deleteDistrict(${district.id})" class="text-red-600 hover:text-red-800"><i class="fas fa-trash"></i></button>
                    </div>
                </td>
            </tr>
        `;
    });
    $('#districtsTableBody').html(html);
}

function openDistrictModal() {
    $('#districtModalTitle').text('Add District');
    $('#districtForm')[0].reset();
    $('#districtId').val('');
    loadRegionDropdown('districtRegionId');
    $('#districtModal').addClass('active');
}

function editDistrict(id) {
    $.ajax({
        url: '/locations/districts/' + id,
        method: 'GET',
        success: function(response) {
            if (response.success) {
                $('#districtModalTitle').text('Edit District');
                $('#districtId').val(response.data.id);
                $('#districtName').val(response.data.name);
                $('#districtCode').val(response.data.code);
                $('#districtRegionId').val(response.data.region_id);
                $('#districtStatus').val(response.data.status);
                $('#districtModal').addClass('active');
            }
        }
    });
}

function saveDistrict() {
    let id = $('#districtId').val();
    let url = id ? '/locations/districts/' + id : '{{ route("locations.districts.store") }}';
    
    let formData = {
        name: $('#districtName').val(),
        code: $('#districtCode').val(),
        region_id: $('#districtRegionId').val(),
        status: $('#districtStatus').val(),
        _token: '{{ csrf_token() }}'
    };
    if (id) formData._method = 'PUT';
    
    $.ajax({
        url: url,
        method: 'POST',
        data: formData,
        success: function(response) {
            if (response.success) {
                Swal.fire('Success', response.message, 'success');
                closeDistrictModal();
                loadDistricts(currentPage.districts);
                loadStats();
            }
        },
        error: function(xhr) {
            Swal.fire('Error', xhr.responseJSON?.message || 'Something went wrong', 'error');
        }
    });
}

function deleteDistrict(id) {
    Swal.fire({
        title: 'Delete District?',
        text: 'This will also delete all stations in this district!',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        confirmButtonText: 'Yes, delete'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: '/locations/districts/' + id,
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                success: function(response) {
                    Swal.fire('Deleted!', response.message, 'success');
                    loadDistricts(currentPage.districts);
                    loadStats();
                },
                error: function() {
                    Swal.fire('Error', 'Failed to delete district', 'error');
                }
            });
        }
    });
}

function closeDistrictModal() {
    $('#districtModal').removeClass('active');
}

// ==================== STATIONS ====================
function loadStations(page = 1) {
    $.ajax({
        url: '{{ route("locations.stations.data") }}?page=' + page,
        method: 'GET',
        success: function(response) {
            if (response.success) {
                renderStationsTable(response.data);
                renderPagination('stations', response.pagination);
            }
        },
        error: function(xhr) {
            const msg = xhr.responseJSON?.message || 'Failed to load stations';
            $('#stationsTableBody').html(`<tr><td colspan="8" class="text-center py-8 text-red-600">${escapeHtml(msg)}</td></tr>`);
        }
    });
}

function renderStationsTable(stations) {
    if (!stations || stations.length === 0) {
        $('#stationsTableBody').html('<tr><td colspan="8" class="text-center py-8 text-gray-500">No stations found</td></tr>');
        return;
    }
    
    let html = '';
    stations.forEach(station => {
        html += `
            <tr class="hover:bg-gray-50">
                <td class="px-6 py-4">#${station.id}</td>
                <td class="px-6 py-4 font-medium">${escapeHtml(station.name)}</td>
                <td class="px-6 py-4"><code class="bg-gray-100 px-2 py-1 rounded">${escapeHtml(station.code)}</code></td>
                <td class="px-6 py-4"><span class="px-2 py-1 rounded-full text-xs bg-purple-100 text-purple-700">${escapeHtml(station.type)}</span></td>
                <td class="px-6 py-4">${escapeHtml(station.region_name)}</td>
                <td class="px-6 py-4">${escapeHtml(station.district_name)}</td>
                <td class="px-6 py-4">
                    <span class="status-badge status-${station.status}">
                        <i class="fas ${station.status === 'active' ? 'fa-check-circle' : 'fa-times-circle'}"></i>
                        ${station.status === 'active' ? 'Active' : 'Inactive'}
                    </span>
                </td>
                <td class="px-6 py-4">
                    <div class="flex gap-2">
                        <button onclick="editStation(${station.id})" class="text-blue-600 hover:text-blue-800"><i class="fas fa-edit"></i></button>
                        <button onclick="deleteStation(${station.id})" class="text-red-600 hover:text-red-800"><i class="fas fa-trash"></i></button>
                    </div>
                </td>
            </tr>
        `;
    });
    $('#stationsTableBody').html(html);
}

function openStationModal() {
    $('#stationModalTitle').text('Add Station');
    $('#stationForm')[0].reset();
    $('#stationId').val('');
    loadRegionDropdown('stationRegionId');
    $('#stationModal').addClass('active');
    
    // Load districts when region changes
    $('#stationRegionId').off('change').on('change', function() {
        loadDistrictsForStation($(this).val());
    });
}

function loadDistrictsForStation(regionId) {
    if (regionId) {
        $.ajax({
            url: '/locations/regions/' + regionId + '/districts',
            method: 'GET',
            success: function(response) {
                let options = '<option value="">Select District</option>';
                response.districts.forEach(district => {
                    options += `<option value="${district.id}">${district.name}</option>`;
                });
                $('#stationDistrictId').html(options);
            }
        });
    } else {
        $('#stationDistrictId').html('<option value="">Select District</option>');
    }
}

function editStation(id) {
    $.ajax({
        url: '/locations/stations/' + id,
        method: 'GET',
        success: function(response) {
            if (response.success) {
                $('#stationModalTitle').text('Edit Station');
                $('#stationId').val(response.data.id);
                $('#stationName').val(response.data.name);
                $('#stationCode').val(response.data.code);
                $('#stationType').val(response.data.type);
                $('#stationRegionId').val(response.data.region_id);
                $('#stationStatus').val(response.data.status);
                
                // Load districts for the selected region
                loadDistrictsForStation(response.data.region_id);
                setTimeout(() => {
                    $('#stationDistrictId').val(response.data.district_id);
                }, 300);
                
                $('#stationModal').addClass('active');
            }
        }
    });
}

function saveStation() {
    let id = $('#stationId').val();
    let url = id ? '/locations/stations/' + id : '{{ route("locations.stations.store") }}';
    
    let formData = {
        name: $('#stationName').val(),
        code: $('#stationCode').val(),
        type: $('#stationType').val(),
        region_id: $('#stationRegionId').val(),
        district_id: $('#stationDistrictId').val(),
        status: $('#stationStatus').val(),
        _token: '{{ csrf_token() }}'
    };
    if (id) formData._method = 'PUT';
    
    $.ajax({
        url: url,
        method: 'POST',
        data: formData,
        success: function(response) {
            if (response.success) {
                Swal.fire('Success', response.message, 'success');
                closeStationModal();
                loadStations(currentPage.stations);
                loadStats();
            }
        },
        error: function(xhr) {
            Swal.fire('Error', xhr.responseJSON?.message || 'Something went wrong', 'error');
        }
    });
}

function deleteStation(id) {
    Swal.fire({
        title: 'Delete Station?',
        text: 'This action cannot be undone!',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        confirmButtonText: 'Yes, delete'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: '/locations/stations/' + id,
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                success: function(response) {
                    Swal.fire('Deleted!', response.message, 'success');
                    loadStations(currentPage.stations);
                    loadStats();
                },
                error: function() {
                    Swal.fire('Error', 'Failed to delete station', 'error');
                }
            });
        }
    });
}

function closeStationModal() {
    $('#stationModal').removeClass('active');
}

// ==================== HELPER FUNCTIONS ====================
function loadRegionDropdowns() {
    loadRegionDropdown('districtRegionId');
    loadRegionDropdown('stationRegionId');
}

function loadRegionDropdown(selectId) {
    $.ajax({
        url: '{{ route("locations.regions.list") }}',
        method: 'GET',
        success: function(response) {
            let options = '<option value="">Select Region</option>';
            response.regions.forEach(region => {
                options += `<option value="${region.id}">${region.name}</option>`;
            });
            $('#' + selectId).html(options);
        }
    });
}

function renderPagination(tab, pagination) {
    if (!pagination || pagination.last_page <= 1) {
        $(`#${tab}Pagination`).empty();
        return;
    }
    
    let html = '<div class="flex gap-2">';
    if (pagination.current_page > 1) {
        html += `<button onclick="load${tab.charAt(0).toUpperCase() + tab.slice(1)}(${pagination.current_page - 1})" class="px-3 py-1 border rounded hover:bg-gray-50">‹ Prev</button>`;
    }
    for (let i = 1; i <= pagination.last_page; i++) {
        if (i === pagination.current_page) {
            html += `<button class="px-3 py-1 bg-blue-600 text-white rounded">${i}</button>`;
        } else if (Math.abs(i - pagination.current_page) <= 2 || i === 1 || i === pagination.last_page) {
            html += `<button onclick="load${tab.charAt(0).toUpperCase() + tab.slice(1)}(${i})" class="px-3 py-1 border rounded hover:bg-gray-50">${i}</button>`;
        }
    }
    if (pagination.current_page < pagination.last_page) {
        html += `<button onclick="load${tab.charAt(0).toUpperCase() + tab.slice(1)}(${pagination.current_page + 1})" class="px-3 py-1 border rounded hover:bg-gray-50">Next ›</button>`;
    }
    html += '</div>';
    $(`#${tab}Pagination`).html(html);
}

function formatDate(dateString) {
    if (!dateString) return 'N/A';
    return new Date(dateString).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
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
