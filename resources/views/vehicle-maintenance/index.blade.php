{{-- resources/views/vehicle-maintenance/index.blade.php --}}
@extends('layouts.app')
@section('title', 'Maintenance Management - Admin')
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
    .status-waiting { background: #fef3c7; color: #92400e; }
    .status-scheduled { background: #dbeafe; color: #1e40af; }
    .status-in_progress { background: #c7d2fe; color: #3730a3; }
    .status-completed { background: #dcfce7; color: #166534; }
    .status-cancelled { background: #fee2e2; color: #991b1b; }
    .status-dispatched { background: #f1f5f9; color: #475569; }
    
    .priority-badge {
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 600;
    }
    .priority-low { background: #f1f5f9; color: #475569; }
    .priority-medium { background: #dbeafe; color: #1e40af; }
    .priority-high { background: #fed7aa; color: #9a3412; }
    .priority-urgent { background: #fee2e2; color: #991b1b; }
    
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
    
    .view-details-btn {
        cursor: pointer;
        transition: all 0.2s;
    }
    .view-details-btn:hover {
        transform: scale(1.05);
    }
</style>

<div class="min-h-screen bg-gray-50 py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <!-- Header -->
        <div class="mb-6 flex justify-between items-center flex-wrap gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Maintenance Management</h1>
                <p class="text-gray-500 text-sm mt-1">Manage all vehicle maintenance requests and service records</p>
            </div>
            <div class="flex gap-3">
                <a href="{{ route('vehicle-maintenance.create') }}" class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm hover:bg-blue-700 transition flex items-center gap-2">
                    <i class="fas fa-plus"></i> Add Maintenance
                </a>
                <button onclick="window.print()" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg text-sm hover:bg-gray-200 transition">
                    <i class="fas fa-print"></i> Print
                </button>
                <button id="refreshBtn" class="px-4 py-2 bg-green-600 text-white rounded-lg text-sm hover:bg-green-700 transition">
                    <i class="fas fa-sync-alt"></i> Refresh
                </button>
            </div>
        </div>
        
        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 mb-6">
            <div class="stat-card p-5">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-xs uppercase tracking-wide">Total Records</p>
                        <p class="text-2xl font-bold text-gray-800" id="totalRecords">{{ $maintenanceRecords->total() }}</p>
                    </div>
                    <i class="fas fa-tools text-blue-400 text-3xl opacity-50"></i>
                </div>
            </div>
            <div class="stat-card p-5">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-xs uppercase tracking-wide">Pending Requests</p>
                        <p class="text-2xl font-bold text-yellow-600" id="pendingCount">0</p>
                    </div>
                    <i class="fas fa-clock text-yellow-400 text-3xl opacity-50"></i>
                </div>
            </div>
            <div class="stat-card p-5">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-xs uppercase tracking-wide">In Progress</p>
                        <p class="text-2xl font-bold text-blue-600" id="inProgressCount">0</p>
                    </div>
                    <i class="fas fa-spinner text-blue-400 text-3xl opacity-50"></i>
                </div>
            </div>
            <div class="stat-card p-5">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-xs uppercase tracking-wide">Completed</p>
                        <p class="text-2xl font-bold text-green-600" id="completedCount">0</p>
                    </div>
                    <i class="fas fa-check-circle text-green-400 text-3xl opacity-50"></i>
                </div>
            </div>
        </div>
        
        <!-- Filters -->
        <div class="bg-white rounded-xl shadow-sm p-5 mb-6">
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
                    <label class="form-label text-xs">Status</label>
                    <select id="filterStatus" class="form-input text-sm">
                        <option value="">All Status</option>
                        <option value="waiting">Pending</option>
                        <option value="scheduled">Scheduled</option>
                        <option value="in_progress">In Progress</option>
                        <option value="completed">Completed</option>
                        <option value="cancelled">Cancelled</option>
                        <option value="dispatched">Dispatched</option>
                    </select>
                </div>
                <div>
                    <label class="form-label text-xs">Priority</label>
                    <select id="filterPriority" class="form-input text-sm">
                        <option value="">All Priorities</option>
                        <option value="low">Low</option>
                        <option value="medium">Medium</option>
                        <option value="high">High</option>
                        <option value="urgent">Urgent</option>
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
            </div>
            <div class="flex justify-end gap-2 mt-4">
                <button id="applyFilters" class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm hover:bg-blue-700 transition">
                    <i class="fas fa-search"></i> Apply Filters
                </button>
                <button id="resetFilters" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg text-sm hover:bg-gray-200 transition">
                    <i class="fas fa-undo-alt"></i> Reset
                </button>
            </div>
        </div>
        
        <!-- Maintenance Records Table -->
        <div class="bg-white rounded-xl shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="data-table w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Vehicle</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Driver</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Priority</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Mileage</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Cost (GHS)</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Status</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="maintenanceTableBody">
                        @forelse($maintenanceRecords as $record)
                        <tr class="hover:bg-gray-50 view-details-btn" data-id="{{ $record->id }}">
                            <td class="px-6 py-4 text-sm">#{{ $record->id }}</td>
                            <td class="px-6 py-4 text-sm">{{ $record->maintenance_date ? $record->maintenance_date->format('Y-m-d') : ($record->date ? $record->date->format('Y-m-d') : 'N/A') }}</td>
                            <td class="px-6 py-4">
                                <span class="font-medium text-gray-800">{{ $record->vehicle->registration_number ?? 'N/A' }}</span>
                                <br><span class="text-xs text-gray-500">{{ $record->vehicle->make ?? '' }} {{ $record->vehicle->model ?? '' }}</span>
                            </td>
                            <td class="px-6 py-4 text-sm">{{ $record->driver->name ?? ($record->driver->user->name ?? 'N/A') }}</td>
                            <td class="px-6 py-4">
                                <span class="px-2 py-1 rounded-full text-xs 
                                    {{ $record->maintenance_type == 'servicing' ? 'bg-green-100 text-green-700' : 
                                       ($record->maintenance_type == 'both' ? 'bg-purple-100 text-purple-700' : 'bg-blue-100 text-blue-700') }}">
                                    {{ ucfirst($record->maintenance_type) }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="priority-badge priority-{{ $record->priority ?? 'medium' }}">
                                    <i class="fas {{ $record->priority == 'urgent' ? 'fa-exclamation-triangle' : ($record->priority == 'high' ? 'fa-arrow-up' : ($record->priority == 'low' ? 'fa-arrow-down' : 'fa-minus')) }}"></i>
                                    {{ ucfirst($record->priority ?? 'Medium') }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-right">{{ number_format($record->mileage_at_service ?? 0) }} km</td>
                            <td class="px-6 py-4 text-sm text-right font-semibold">GHS {{ number_format($record->cost ?? 0, 2) }}</td>
                            <td class="px-6 py-4 text-sm text-center">
                                <span class="status-badge status-{{ $record->status }}">
                                    <i class="fas {{ $record->status == 'completed' ? 'fa-check-circle' : ($record->status == 'in_progress' ? 'fa-spinner fa-pulse' : 'fa-clock') }}"></i>
                                    {{ ucfirst(str_replace('_', ' ', $record->status)) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-center">
                                <div class="flex gap-2 justify-center">
                                    <button onclick="viewMaintenance({{ $record->id }})" class="text-blue-600 hover:text-blue-800" title="View Details">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button onclick="editMaintenance({{ $record->id }})" class="text-green-600 hover:text-green-800" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button onclick="updateStatus({{ $record->id }})" class="text-purple-600 hover:text-purple-800" title="Update Status">
                                        <i class="fas fa-exchange-alt"></i>
                                    </button>
                                    <button onclick="deleteMaintenance({{ $record->id }})" class="text-red-600 hover:text-red-800" title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="10" class="px-6 py-12 text-center text-gray-500">
                                <i class="fas fa-tools text-gray-300 text-5xl mb-3 block"></i>
                                <p>No maintenance records found</p>
                                <a href="{{ route('vehicle-maintenance.create') }}" class="inline-block mt-3 px-4 py-2 bg-blue-600 text-white rounded-lg text-sm hover:bg-blue-700">
                                    <i class="fas fa-plus mr-1"></i> Add First Maintenance Record
                                </a>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            @if($maintenanceRecords->hasPages())
            <div class="px-6 py-4 border-t border-gray-200">
                {{ $maintenanceRecords->links() }}
            </div>
            @endif
        </div>
    </div>
</div>

<!-- View Maintenance Modal -->
<div id="viewModal" class="modal">
    <div class="modal-content max-w-2xl">
        <div class="sticky top-0 bg-white px-6 py-4 border-b border-gray-200 flex justify-between items-center">
            <h3 class="text-xl font-semibold text-gray-800">
                <i class="fas fa-tools text-blue-600 mr-2"></i>Maintenance Details
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

<!-- Update Status Modal -->
<div id="statusModal" class="modal">
    <div class="modal-content">
        <div class="sticky top-0 bg-white px-6 py-4 border-b border-gray-200 flex justify-between items-center">
            <h3 class="text-xl font-semibold text-gray-800">
                <i class="fas fa-exchange-alt text-purple-600 mr-2"></i>Update Maintenance Status
            </h3>
            <button onclick="closeStatusModal()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        <form id="statusForm" class="p-6 space-y-4">
            @csrf
            @method('PUT')
            <input type="hidden" name="id" id="statusMaintenanceId">
            
            <div>
                <label class="form-label">Status</label>
                <select name="status" id="newStatus" class="form-select" required>
                    <option value="waiting">Waiting - Pending Review</option>
                    <option value="scheduled">Scheduled - Date Set</option>
                    <option value="in_progress">In Progress - Currently Being Serviced</option>
                    <option value="completed">Completed - Service Done</option>
                    <option value="cancelled">Cancelled - Request Cancelled</option>
                    <option value="dispatched">Dispatched - Sent to Workshop</option>
                </select>
            </div>
            
            <div>
                <label class="form-label">Actual Cost (GHS)</label>
                <input type="number" name="actual_cost" id="actualCost" class="form-input" step="0.01" placeholder="Enter final cost">
            </div>
            
            <div>
                <label class="form-label">Completion Notes</label>
                <textarea name="completion_notes" id="completionNotes" rows="3" class="form-textarea" placeholder="Add notes about the service performed..."></textarea>
            </div>
            
            <div class="flex justify-end gap-3 pt-4 border-t">
                <button type="button" onclick="closeStatusModal()" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700">Update Status</button>
            </div>
        </form>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
let currentPage = 1;
let currentFilters = {};

$(document).ready(function() {
    loadStatistics();
    
    // Filter events
    $('#applyFilters').click(function() {
        applyFiltersAndReload();
    });
    
    $('#resetFilters').click(function() {
        $('#filterVehicle, #filterStatus, #filterPriority').val('');
        $('#filterDateFrom, #filterDateTo').val('');
        currentFilters = {};
        window.location.href = '{{ route("vehicle-maintenance.index") }}';
    });
    
    $('#refreshBtn').click(function() {
        location.reload();
    });
    
    // Status form submission
    $('#statusForm').on('submit', function(e) {
        e.preventDefault();
        updateMaintenanceStatus();
    });
    
    // Make rows clickable for view
    $('.view-details-btn').click(function(e) {
        if (!$(e.target).closest('button').length) {
            const id = $(this).data('id');
            if (id) viewMaintenance(id);
        }
    });
});

function loadStatistics() {
    $.ajax({
        url: '{{ route("vehicle-maintenance.statistics") }}',
        method: 'GET',
        success: function(response) {
            if (response.success) {
                $('#pendingCount').text(response.pending || 0);
                $('#inProgressCount').text(response.in_progress || 0);
                $('#completedCount').text(response.completed || 0);
            }
        }
    });
}

function applyFiltersAndReload() {
    let params = new URLSearchParams();
    if ($('#filterVehicle').val()) params.append('vehicle_id', $('#filterVehicle').val());
    if ($('#filterStatus').val()) params.append('status', $('#filterStatus').val());
    if ($('#filterPriority').val()) params.append('priority', $('#filterPriority').val());
    if ($('#filterDateFrom').val()) params.append('date_from', $('#filterDateFrom').val());
    if ($('#filterDateTo').val()) params.append('date_to', $('#filterDateTo').val());
    
    window.location.href = '{{ route("vehicle-maintenance.index") }}?' + params.toString();
}

function viewMaintenance(id) {
    $('#viewModal').addClass('active');
    $('#viewModalContent').html('<div class="text-center py-8"><div class="loading-spinner"></div> Loading...</div>');
    
    $.ajax({
        url: '/vehicle-maintenance/' + id,
        method: 'GET',
        success: function(response) {
            if (response.success) {
                renderMaintenanceDetails(response.data);
            } else {
                $('#viewModalContent').html('<div class="text-center py-8 text-red-500">Failed to load details</div>');
            }
        },
        error: function() {
            $('#viewModalContent').html('<div class="text-center py-8 text-red-500">Error loading details</div>');
        }
    });
}

function renderMaintenanceDetails(data) {
    let statusClass = `status-${data.status}`;
    let priorityClass = `priority-${data.priority || 'medium'}`;
    
    let servicesHtml = '';
    if (data.checklist && data.checklist.length) {
        servicesHtml = '<div class="mt-4"><h4 class="font-semibold text-gray-800 mb-2">Services Performed:</h4><ul class="list-disc list-inside space-y-1">';
        data.checklist.forEach(service => {
            servicesHtml += `<li class="text-sm text-gray-700">${service}</li>`;
        });
        servicesHtml += '</ul></div>';
    }
    
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
                    <p class="font-semibold text-gray-800">${data.driver?.name || data.driver?.user?.name || 'N/A'}</p>
                </div>
                <div class="bg-gray-50 p-3 rounded-lg">
                    <p class="text-xs text-gray-500">Maintenance Date</p>
                    <p class="font-semibold text-gray-800">${formatDate(data.maintenance_date || data.date)}</p>
                </div>
                <div class="bg-gray-50 p-3 rounded-lg">
                    <p class="text-xs text-gray-500">Mileage at Service</p>
                    <p class="font-semibold text-gray-800">${(data.mileage_at_service || 0).toLocaleString()} km</p>
                </div>
                <div class="bg-gray-50 p-3 rounded-lg">
                    <p class="text-xs text-gray-500">Maintenance Type</p>
                    <p class="font-semibold text-gray-800">${ucfirst(data.maintenance_type)}</p>
                </div>
                <div class="bg-gray-50 p-3 rounded-lg">
                    <p class="text-xs text-gray-500">Priority</p>
                    <p class="font-semibold ${priorityClass} inline-block px-2 py-1 rounded-full text-xs">${ucfirst(data.priority || 'Medium')}</p>
                </div>
                <div class="bg-gray-50 p-3 rounded-lg">
                    <p class="text-xs text-gray-500">Status</p>
                    <p class="status-badge ${statusClass} inline-flex">${ucfirst(data.status.replace('_', ' '))}</p>
                </div>
                <div class="bg-gray-50 p-3 rounded-lg">
                    <p class="text-xs text-gray-500">Cost</p>
                    <p class="font-semibold text-gray-800">GHS ${(data.cost || 0).toLocaleString(undefined, {minimumFractionDigits: 2})}</p>
                </div>
            </div>
            
            ${data.description ? `
            <div class="bg-gray-50 rounded-lg p-4">
                <h4 class="font-semibold text-gray-800 mb-2">Description</h4>
                <p class="text-sm text-gray-700">${escapeHtml(data.description)}</p>
            </div>
            ` : ''}
            
            ${servicesHtml}
            
            <div class="text-xs text-gray-400 pt-2 border-t">
                <p>Created: ${formatDateTime(data.created_at)}</p>
                <p>Last updated: ${formatDateTime(data.updated_at)}</p>
            </div>
        </div>
    `;
    
    $('#viewModalContent').html(html);
}

function updateStatus(id) {
    $('#statusMaintenanceId').val(id);
    $('#statusModal').addClass('active');
}

function updateMaintenanceStatus() {
    let id = $('#statusMaintenanceId').val();
    let formData = {
        status: $('#newStatus').val(),
        actual_cost: $('#actualCost').val(),
        completion_notes: $('#completionNotes').val(),
        _token: '{{ csrf_token() }}',
        _method: 'PUT'
    };
    
    $.ajax({
        url: '/vehicle-maintenance/' + id,
        method: 'POST',
        data: formData,
        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
        success: function(response) {
            if (response.success) {
                Swal.fire('Success', 'Maintenance status updated successfully', 'success');
                closeStatusModal();
                setTimeout(() => location.reload(), 1000);
            } else {
                Swal.fire('Error', response.message || 'Failed to update status', 'error');
            }
        },
        error: function(xhr) {
            Swal.fire('Error', xhr.responseJSON?.message || 'Failed to update status', 'error');
        }
    });
}

function editMaintenance(id) {
    window.location.href = '/vehicle-maintenance/' + id + '/edit';
}

function deleteMaintenance(id) {
    Swal.fire({
        title: 'Delete Maintenance Record?',
        text: 'This action cannot be undone.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        confirmButtonText: 'Yes, delete'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: '/vehicle-maintenance/' + id,
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                success: function() {
                    Swal.fire('Deleted!', 'Maintenance record deleted successfully', 'success');
                    location.reload();
                },
                error: function() {
                    Swal.fire('Error', 'Failed to delete maintenance record', 'error');
                }
            });
        }
    });
}

function closeViewModal() {
    $('#viewModal').removeClass('active');
}

function closeStatusModal() {
    $('#statusModal').removeClass('active');
    $('#statusForm')[0].reset();
}

function formatDate(dateString) {
    if (!dateString) return 'N/A';
    return new Date(dateString).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
}

function formatDateTime(dateString) {
    if (!dateString) return 'N/A';
    return new Date(dateString).toLocaleString('en-GB', {
        day: '2-digit', month: 'short', year: 'numeric',
        hour: '2-digit', minute: '2-digit'
    });
}

function ucfirst(str) {
    if (!str) return '';
    return str.charAt(0).toUpperCase() + str.slice(1);
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