@extends('layouts.app')
@section('title', 'Maintenance Job Order Details')
@section('content')

<style>
    * { font-family: 'Inter', sans-serif; }
    
    @media print {
        .no-print, .sidebar-fleet, header, .action-buttons, .print-hide {
            display: none !important;
        }
        body { background: white; }
        .print-container { margin: 0; padding: 0; }
        .info-card { break-inside: avoid; border: 1px solid #ddd; }
        .status-badge { border: 1px solid #ccc; }
    }
    
    .info-card {
        background: white;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        overflow: hidden;
        margin-bottom: 20px;
    }
    
    .status-badge {
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
        display: inline-block;
    }
    .status-scheduled { background: #fef3c7; color: #92400e; }
    .status-in_progress { background: #dbeafe; color: #1e40af; }
    .status-completed { background: #dcfce7; color: #166534; }
    .status-cancelled { background: #fee2e2; color: #991b1b; }
    .status-dispatched { background: #f1f5f9; color: #475569; }
    
    .priority-badge {
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
        display: inline-block;
    }
    .priority-low { background: #f1f5f9; color: #475569; }
    .priority-medium { background: #dbeafe; color: #1e40af; }
    .priority-high { background: #fed7aa; color: #9a3412; }
    .priority-urgent { background: #fee2e2; color: #991b1b; }
    
    .info-label {
        font-size: 11px;
        font-weight: 600;
        text-transform: uppercase;
        color: #64748b;
        letter-spacing: 0.5px;
    }
    
    .info-value {
        font-size: 14px;
        font-weight: 500;
        color: #1e293b;
        margin-top: 4px;
    }
    
    .section-title {
        font-size: 16px;
        font-weight: 700;
        color: #1e293b;
    }
    
    .action-buttons {
        position: sticky;
        top: 20px;
    }
</style>

<div class="min-h-screen bg-gray-50 py-6 print-container">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <!-- Action Buttons (No Print) -->
        <div class="action-buttons no-print mb-6 flex justify-end gap-3">
            <button onclick="window.print()" class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm hover:bg-blue-700 transition flex items-center gap-2">
                <i class="fas fa-print"></i> Print
            </button>
            <button onclick="exportToPDF()" class="px-4 py-2 bg-green-600 text-white rounded-lg text-sm hover:bg-green-700 transition flex items-center gap-2">
                <i class="fas fa-download"></i> Export PDF
            </button>
            <a href="{{ url()->previous() }}" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg text-sm hover:bg-gray-200 transition flex items-center gap-2">
                <i class="fas fa-arrow-left"></i> Back
            </a>
        </div>
        
        <!-- Maintenance Details Content -->
        <div id="maintenanceContent">
            <!-- Loading State -->
            <div id="loadingState" class="text-center py-12">
                <div class="inline-block w-12 h-12 border-4 border-gray-200 border-t-blue-600 rounded-full animate-spin"></div>
                <p class="mt-3 text-gray-500">Loading maintenance details...</p>
            </div>
            
            <!-- Content will be populated by JavaScript -->
            <div id="detailsContent" style="display: none;"></div>
        </div>
    </div>
</div>

<style>
    @keyframes spin {
        to { transform: rotate(360deg); }
    }
</style>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>

<script>
$(document).ready(function() {
    loadMaintenanceDetails();
});

function loadMaintenanceDetails() {
    const maintenanceId = {{ $maintenance->id }};
    
    $.ajax({
        url: `/vehicles/maintenance/${maintenanceId}/data`,
        method: 'GET',
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            console.log('Maintenance Data Received:', response);
            if (response.success) {
                try {
                    renderMaintenanceDetails(response.data);
                    $('#loadingState').hide();
                    $('#detailsContent').show();
                } catch (e) {
                    console.error('JS Error during rendering:', e);
                    showError('An error occurred while displaying the data: ' + e.message);
                }
            } else {
                showError(response.message || 'Failed to load maintenance details');
            }
        },
        error: function() {
            showError('Error loading maintenance details. Please try again.');
        }
    });
}

function renderMaintenanceDetails(data) {
    if (!data) return showError('No data available');
    
    // Status badge class
    const status = data.status || 'scheduled';
    const priority = data.priority || 'medium';
    const statusClass = `status-${status}`;
    const priorityClass = `priority-${priority}`;
    
    // Format services list
    let servicesHtml = '';
    let services = data.services;
    
    // Handle case where services might be a stringified JSON
    if (typeof services === 'string') {
        try { services = JSON.parse(services); } catch(e) { services = [services]; }
    }
    
    if (Array.isArray(services) && services.length > 0) {
        servicesHtml = services.map(service => {
            const name = typeof service === 'object' ? (service.name || service.description) : service;
            const cost = typeof service === 'object' ? service.cost : null;
            
            return `
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                    <div class="flex items-center gap-3">
                        <i class="fas fa-check-circle text-green-500"></i>
                        <span class="text-gray-700">${escapeHtml(name)}</span>
                    </div>
                    ${cost ? `<span class="font-semibold text-gray-800">GHS ${parseFloat(cost).toFixed(2)}</span>` : ''}
                </div>
            `;
        }).join('');
    } else {
        servicesHtml = '<p class="text-gray-500 text-center py-4">No services specified</p>';
    }
    
    // Checklist items
    let checklistHtml = '';
    let checklist = data.checklist;
    
    if (typeof checklist === 'string') {
        try { checklist = JSON.parse(checklist); } catch(e) { checklist = []; }
    }

    if (Array.isArray(checklist) && checklist.length > 0) {
        checklistHtml = `
            <div class="space-y-2">
                ${checklist.map(item => {
                    const itemName = typeof item === 'object' ? item.name : item;
                    const isCompleted = typeof item === 'object' ? item.completed : false;
                    
                    return `
                        <div class="flex items-center gap-3 p-2 bg-gray-50 rounded-lg">
                            <i class="fas ${isCompleted ? 'fa-check-circle text-green-500' : 'fa-clock text-gray-400'}"></i>
                            <span class="text-sm text-gray-700">${escapeHtml(itemName)}</span>
                            ${isCompleted ? '<span class="text-xs text-green-600 ml-auto">Completed</span>' : ''}
                        </div>
                    `;
                }).join('')}
            </div>
        `;
    }
    
    const html = `
        <!-- Header Section -->
        <div class="text-center mb-8">
            <h1 class="text-2xl font-bold text-gray-800">MAINTENANCE JOB ORDER</h1>
            <p class="text-gray-500 mt-1">Vehicle Maintenance Service Report</p>
        </div>
        
        <!-- Job Order Header -->
        <div class="info-card">
            <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
                <div class="flex justify-between items-center flex-wrap gap-3">
                    <div>
                        <span class="text-xs text-gray-500">JOB ORDER #</span>
                        <h2 class="text-xl font-bold text-gray-800">${data.job_order_number || 'JO-' + String(data.id).padStart(6, '0')}</h2>
                    </div>
                    <div class="flex gap-2">
                        <span class="${statusClass} status-badge">
                            <i class="fas ${getStatusIcon(status)} mr-1"></i> ${capitalize(status)}
                        </span>
                        <span class="${priorityClass} priority-badge">
                            <i class="fas ${getPriorityIcon(priority)} mr-1"></i> ${capitalize(priority)} Priority
                        </span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Vehicle Information -->
        <div class="info-card">
            <div class="px-6 py-3 bg-gray-50 border-b border-gray-200">
                <h3 class="section-title">
                    <i class="fas fa-truck text-blue-600 mr-2"></i>Vehicle Information
                </h3>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div>
                        <div class="info-label">Registration Number</div>
                        <div class="info-value font-mono">${data.vehicle_registration || 'N/A'}</div>
                    </div>
                    <div>
                        <div class="info-label">Make & Model</div>
                        <div class="info-value">${data.vehicle_make || ''} ${data.vehicle_model || 'N/A'}</div>
                    </div>
                    <div>
                        <div class="info-label">Year</div>
                        <div class="info-value">${data.vehicle_year || 'N/A'}</div>
                    </div>
                    <div>
                        <div class="info-label">Current Mileage</div>
                        <div class="info-value">${data.mileage_at_service ? numberFormat(data.mileage_at_service) + ' km' : 'N/A'}</div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Maintenance Details -->
        <div class="info-card">
            <div class="px-6 py-3 bg-gray-50 border-b border-gray-200">
                <h3 class="section-title">
                    <i class="fas fa-clipboard-list text-blue-600 mr-2"></i>Maintenance Details
                </h3>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div>
                        <div class="info-label">Maintenance Type</div>
                        <div class="info-value">${getMaintenanceTypeLabel(data.maintenance_type)}</div>
                    </div>
                    <div>
                        <div class="info-label">Scheduled Date</div>
                        <div class="info-value">${formatDate(data.maintenance_date)}</div>
                    </div>
                    <div>
                        <div class="info-label">Service Provider / Mechanic</div>
                        <div class="info-value">${data.service_provider || 'Not specified'}</div>
                    </div>
                    <div>
                        <div class="info-label">Driver</div>
                        <div class="info-value">${data.driver_name || 'Not assigned'}</div>
                    </div>
                    ${data.completion_date ? `
                    <div>
                        <div class="info-label">Completion Date</div>
                        <div class="info-value">${formatDate(data.completion_date)}</div>
                    </div>
                    ` : ''}
                    ${data.cost ? `
                    <div>
                        <div class="info-label">Total Cost</div>
                        <div class="info-value text-lg font-bold text-green-600">GHS ${parseFloat(data.cost).toLocaleString(undefined, {minimumFractionDigits: 2})}</div>
                    </div>
                    ` : `
                    <div>
                        <div class="info-label">Cost Status</div>
                        <div class="info-value text-orange-600">Pending (Invoice to be provided)</div>
                    </div>
                    `}
                </div>
            </div>
        </div>
        
        <!-- Services Performed -->
        <div class="info-card">
            <div class="px-6 py-3 bg-gray-50 border-b border-gray-200">
                <h3 class="section-title">
                    <i class="fas fa-list-check text-green-600 mr-2"></i>Services Performed
                </h3>
            </div>
            <div class="p-6">
                ${servicesHtml}
            </div>
        </div>
        
        <!-- Description / Issue Reported -->
        ${data.description ? `
        <div class="info-card">
            <div class="px-6 py-3 bg-gray-50 border-b border-gray-200">
                <h3 class="section-title">
                    <i class="fas fa-file-alt text-gray-600 mr-2"></i>Issue Reported / Description
                </h3>
            </div>
            <div class="p-6">
                <p class="text-gray-700">${escapeHtml(data.description)}</p>
            </div>
        </div>
        ` : ''}
        
        <!-- Workshop Notes -->
        ${data.technician_notes ? `
        <div class="info-card">
            <div class="px-6 py-3 bg-gray-50 border-b border-gray-200">
                <h3 class="section-title">
                    <i class="fas fa-sticky-note text-yellow-600 mr-2"></i>Workshop Notes
                </h3>
            </div>
            <div class="p-6 bg-yellow-50">
                <p class="text-yellow-800">${escapeHtml(data.technician_notes)}</p>
            </div>
        </div>
        ` : ''}
        
        <!-- Checklist (if any) -->
        ${checklistHtml ? `
        <div class="info-card">
            <div class="px-6 py-3 bg-gray-50 border-b border-gray-200">
                <h3 class="section-title">
                    <i class="fas fa-check-double text-purple-600 mr-2"></i>Maintenance Checklist
                </h3>
            </div>
            <div class="p-6">
                ${checklistHtml}
            </div>
        </div>
        ` : ''}
        
        <!-- Signature & Authorization Section -->
        <div class="info-card">
            <div class="px-6 py-3 bg-gray-50 border-b border-gray-200">
                <h3 class="section-title">
                    <i class="fas fa-signature text-gray-600 mr-2"></i>Authorization & Sign-off
                </h3>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="text-center p-4 border rounded-lg">
                        <p class="text-sm text-gray-500 mb-2">Issued By</p>
                        <div class="border-t-2 border-gray-300 pt-2 mt-4">
                            <p class="font-medium">${data.created_by_name || 'System'}</p>
                            <p class="text-xs text-gray-400">${formatDateTime(data.created_at)}</p>
                        </div>
                    </div>
                    <div class="text-center p-4 border rounded-lg">
                        <p class="text-sm text-gray-500 mb-2">Authorized By (Mechanic/Workshop)</p>
                        <div class="border-t-2 border-gray-300 pt-2 mt-4">
                            <p class="font-medium">${data.service_provider || '___________________'}</p>
                            <p class="text-xs text-gray-400">Stamp & Signature</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Footer -->
        <div class="text-center text-xs text-gray-400 mt-8 pt-4 border-t">
            <p>Generated on ${new Date().toLocaleString()} | FleetPilot Maintenance Management System</p>
            <p class="mt-1">This is a computer-generated document. No signature is required.</p>
        </div>
    `;
    
    $('#detailsContent').html(html);
}

function showError(message) {
    $('#loadingState').hide();
    $('#detailsContent').html(`
        <div class="text-center py-12">
            <i class="fas fa-exclamation-triangle text-red-500 text-5xl mb-3"></i>
            <p class="text-red-600">${message}</p>
            <a href="{{ url()->previous() }}" class="inline-block mt-4 px-4 py-2 bg-blue-600 text-white rounded-lg">Go Back</a>
        </div>
    `).show();
}

function exportToPDF() {
    const element = document.getElementById('detailsContent');
    const opt = {
        margin: [0.5, 0.5, 0.5, 0.5],
        filename: `maintenance-job-order-${Date.now()}.pdf`,
        image: { type: 'jpeg', quality: 0.98 },
        html2canvas: { scale: 2, letterRendering: true },
        jsPDF: { unit: 'in', format: 'a4', orientation: 'portrait' }
    };
    html2pdf().set(opt).from(element).save();
}

// Helper functions
function capitalize(str) {
    return str ? str.charAt(0).toUpperCase() + str.slice(1) : '';
}

function formatDate(dateString) {
    if (!dateString) return 'N/A';
    return new Date(dateString).toLocaleDateString('en-GB', {
        day: '2-digit',
        month: 'short',
        year: 'numeric'
    });
}

function formatDateTime(dateString) {
    if (!dateString) return 'N/A';
    return new Date(dateString).toLocaleString('en-GB', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

function numberFormat(num) {
    return new Intl.NumberFormat().format(num || 0);
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function getStatusIcon(status) {
    const icons = {
        'scheduled': 'fa-calendar-alt',
        'in_progress': 'fa-spinner',
        'completed': 'fa-check-circle',
        'cancelled': 'fa-times-circle',
        'dispatched': 'fa-paper-plane'
    };
    return icons[status] || 'fa-info-circle';
}

function getPriorityIcon(priority) {
    const icons = {
        'low': 'fa-arrow-down',
        'medium': 'fa-minus',
        'high': 'fa-arrow-up',
        'urgent': 'fa-exclamation-triangle'
    };
    return icons[priority] || 'fa-flag';
}

function getMaintenanceTypeLabel(type) {
    const labels = {
        'general_service': 'General Service',
        'specific': 'Specific Maintenance',
        'both': 'General + Specific Maintenance'
    };
    return labels[type] || type || 'Not specified';
}
</script>

@endsection
