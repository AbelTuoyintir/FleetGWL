{{-- resources/views/driver/fuel-mileage/maintenance-create.blade.php --}}
@extends('layouts.app')
@section('title', 'Request Maintenance - ' . ($selectedVehicle->registration_number ?? ''))
@section('content')

<style>
    * { font-family: 'Inter', sans-serif; }
    body { background: #f1f5f9; }
    
    .form-input, .form-select, .form-textarea {
        width: 100%;
        padding: 10px 14px;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
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
        font-weight: 600;
        color: #374151;
        margin-bottom: 6px;
        display: block;
    }
    
    .maintenance-card {
        transition: all 0.2s;
        cursor: pointer;
    }
    .maintenance-card.selected {
        border-color: #3b82f6 !important;
        background: #eff6ff;
    }
    
    .checklist-card {
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        transition: all 0.2s;
    }
    .checklist-card:hover {
        border-color: #cbd5e1;
        box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    }
    
    .service-tag {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 12px;
        background: #eff6ff;
        color: #1e40af;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 500;
    }
    .service-tag.selected {
        background: #dcfce7;
        color: #166534;
    }
    
    .priority-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 600;
    }
    .priority-low { background: #f1f5f9; color: #475569; }
    .priority-medium { background: #dbeafe; color: #1e40af; }
    .priority-high { background: #fed7aa; color: #9a3412; }
    .priority-urgent { background: #fee2e2; color: #991b1b; }
    
    .vehicle-card {
        background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
        border-radius: 20px;
    }
    
    @media print {
        .no-print, header, .action-buttons {
            display: none !important;
        }
        main { margin-left: 0 !important; padding: 0 !important; }
    }
</style>

<div class="min-h-screen bg-gray-50 py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <!-- Header -->
        <div class="mb-6">
            <div class="flex items-center gap-3 text-sm text-gray-500 mb-2">
                <a href="{{ route('driver.fuel-mileage.dashboard') }}" class="hover:text-blue-600">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
                <i class="fas fa-chevron-right text-xs"></i>
                <span class="text-gray-700">Request Maintenance</span>
            </div>
            <div class="flex justify-between items-center flex-wrap gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">Request Maintenance</h1>
                    <p class="text-gray-500 text-sm mt-1">
                        {{ $selectedVehicle->registration_number }} - {{ $selectedVehicle->make }} {{ $selectedVehicle->model }} ({{ $selectedVehicle->year }})
                    </p>
                </div>
                <div class="flex gap-3">
                    <span class="px-3 py-1.5 bg-gray-100 rounded-lg text-sm">
                        <i class="fas fa-road mr-1"></i> Current Mileage: {{ number_format($selectedVehicle->mileage ?? 0) }} km
                    </span>
                </div>
            </div>
        </div>
        
        <!-- Vehicle Info Card -->
        <div class="vehicle-card text-white p-5 mb-6">
            <div class="flex flex-wrap justify-between items-center gap-4">
                <div class="flex items-center gap-4">
                    <div class="w-14 h-14 bg-blue-500/20 rounded-xl flex items-center justify-center">
                        <i class="fas fa-truck text-2xl text-blue-400"></i>
                    </div>
                    <div>
                        <p class="text-blue-300 text-xs">Your Assigned Vehicle</p>
                        <h2 class="text-xl font-bold">{{ $selectedVehicle->registration_number }}</h2>
                        <p class="text-gray-300 text-sm">{{ $selectedVehicle->make }} {{ $selectedVehicle->model }}</p>
                    </div>
                </div>
                <div class="text-right">
                    <p class="text-xs text-gray-300">Current Mileage</p>
                    <p class="text-2xl font-bold">{{ number_format($selectedVehicle->mileage ?? 0) }} <span class="text-sm font-normal">km</span></p>
                </div>
            </div>
        </div>
        
        <form id="maintenanceRequestForm" action="{{ route('driver.fuel-mileage.maintenance.store') }}" method="POST">
            @csrf
            
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Left Column - Main Form -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Maintenance Type Selection -->
                    <div class="bg-white rounded-xl shadow-sm p-6">
                        <h3 class="font-semibold text-gray-800 mb-4 flex items-center gap-2">
                            <i class="fas fa-clipboard-list text-blue-600"></i>
                            Maintenance Type
                        </h3>
                        
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <!-- Servicing Option -->
                            <div class="maintenance-card border-2 rounded-xl p-4" data-type="servicing">
                                <div class="flex items-start gap-3">
                                    <input type="radio" name="maintenance_type" value="servicing" id="servicing" class="mt-1" required>
                                    <div class="flex-1">
                                        <div class="font-semibold text-gray-800 flex items-center gap-2">
                                            <i class="fas fa-oil-can text-green-600"></i>
                                            Servicing
                                        </div>
                                        <p class="text-xs text-gray-500 mt-1">Regular maintenance including oil change, filter replacement, and basic inspections</p>
                                        <div class="mt-3 flex flex-wrap gap-1">
                                            <span class="text-xs bg-gray-100 px-2 py-1 rounded">Oil Change</span>
                                            <span class="text-xs bg-gray-100 px-2 py-1 rounded">Filter Replacement</span>
                                            <span class="text-xs bg-gray-100 px-2 py-1 rounded">Basic Inspection</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Specific Repair Option -->
                            <div class="maintenance-card border-2 rounded-xl p-4" data-type="specific">
                                <div class="flex items-start gap-3">
                                    <input type="radio" name="maintenance_type" value="specific" id="specific" class="mt-1">
                                    <div class="flex-1">
                                        <div class="font-semibold text-gray-800 flex items-center gap-2">
                                            <i class="fas fa-tasks text-blue-600"></i>
                                            Specific Repair
                                        </div>
                                        <p class="text-xs text-gray-500 mt-1">Select specific repairs or parts replacement</p>
                                        <div class="mt-3">
                                            <span class="text-xs bg-blue-100 text-blue-700 px-2 py-1 rounded">Custom Selection</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Both Service & Repair Option -->
                            <div class="maintenance-card border-2 rounded-xl p-4" data-type="both">
                                <div class="flex items-start gap-3">
                                    <input type="radio" name="maintenance_type" value="both" id="bothType" class="mt-1">
                                    <div class="flex-1">
                                        <div class="font-semibold text-gray-800 flex items-center gap-2">
                                            <i class="fas fa-layer-group text-purple-600"></i>
                                            Service + Repair
                                        </div>
                                        <p class="text-xs text-gray-500 mt-1">Regular service PLUS additional specific repairs</p>
                                        <div class="mt-3 flex flex-wrap gap-1">
                                            <span class="text-xs bg-green-100 text-green-700 px-2 py-1 rounded">Service Package</span>
                                            <span class="text-xs bg-blue-100 text-blue-700 px-2 py-1 rounded">+ Custom Repairs</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Checklist Section (Shows for specific or both) -->
                    <div id="checklistSection" class="bg-white rounded-xl shadow-sm p-6 hidden">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="font-semibold text-gray-800 flex items-center gap-2">
                                <i class="fas fa-check-square text-blue-600"></i>
                                Repair Checklist
                            </h3>
                            <div class="flex gap-3">
                                <button type="button" id="selectAllBtn" class="text-sm text-blue-600 hover:text-blue-800">
                                    <i class="fas fa-check-double"></i> Select All
                                </button>
                                <button type="button" id="deselectAllBtn" class="text-sm text-gray-500 hover:text-gray-700">
                                    <i class="fas fa-times"></i> Deselect All
                                </button>
                            </div>
                        </div>
                        
                        <!-- Service Note (shown when both is selected) -->
                        <div id="serviceNote" class="hidden mb-4 p-3 bg-green-50 border border-green-200 rounded-lg">
                            <div class="flex items-center gap-2">
                                <i class="fas fa-check-circle text-green-600"></i>
                                <p class="text-sm text-green-800">
                                    <span class="font-semibold">Service Package Included:</span> Oil change, filter replacement, and basic inspection will be performed.
                                </p>
                            </div>
                        </div>
                        
                        <div class="space-y-4 max-h-96 overflow-y-auto pr-2">
                            @php
                                $categories = [
                                    'engine' => ['name' => 'Engine Services', 'icon' => 'fa-engine', 'color' => 'red'],
                                    'transmission' => ['name' => 'Transmission & Drivetrain', 'icon' => 'fa-cogs', 'color' => 'blue'],
                                    'brakes' => ['name' => 'Brake System', 'icon' => 'fa-brake-warning', 'color' => 'orange'],
                                    'electrical' => ['name' => 'Electrical System', 'icon' => 'fa-bolt', 'color' => 'yellow'],
                                    'cooling' => ['name' => 'Cooling System', 'icon' => 'fa-temperature-low', 'color' => 'cyan'],
                                    'tires' => ['name' => 'Tires & Wheels', 'icon' => 'fa-circle', 'color' => 'gray'],
                                    'suspension' => ['name' => 'Suspension & Steering', 'icon' => 'fa-car-side', 'color' => 'indigo'],
                                    'fluids' => ['name' => 'Fluids & Filters', 'icon' => 'fa-oil-can', 'color' => 'teal'],
                                ];
                            @endphp
                            
                            @foreach($categories as $categoryKey => $category)
                                <div class="checklist-card overflow-hidden">
                                    <div class="p-4 bg-gray-50 cursor-pointer" onclick="toggleCategory('{{ $categoryKey }}')">
                                        <div class="flex items-center justify-between">
                                            <div class="flex items-center gap-2">
                                                <i class="fas fa-chevron-right text-gray-400 text-xs transition-transform" id="chevron-{{ $categoryKey }}"></i>
                                                <i class="fas {{ $category['icon'] }} text-{{ $category['color'] }}-500"></i>
                                                <h4 class="font-semibold text-gray-800">{{ $category['name'] }}</h4>
                                            </div>
                                            <span class="text-xs text-gray-500" id="count-{{ $categoryKey }}">0 selected</span>
                                        </div>
                                    </div>
                                    <div id="category-{{ $categoryKey }}" class="hidden p-4 space-y-2 border-t">
                                        @php
                                            $items = [
                                                'engine' => ['Engine Oil Change', 'Oil Filter Replacement', 'Air Filter Replacement', 'Spark Plugs', 'Fuel Filter'],
                                                'transmission' => ['Transmission Fluid', 'Filter Replacement', 'Clutch Adjustment'],
                                                'brakes' => ['Brake Pads', 'Brake Fluid', 'Brake Rotors', 'Brake Calipers'],
                                                'electrical' => ['Battery Test', 'Alternator Check', 'Starter Motor', 'Lighting System'],
                                                'cooling' => ['Coolant Flush', 'Radiator', 'Water Pump', 'Thermostat'],
                                                'tires' => ['Tire Rotation', 'Wheel Alignment', 'Tire Balance', 'Tire Replacement'],
                                                'suspension' => ['Shock Absorbers', 'Struts', 'Ball Joints', 'Tie Rods'],
                                                'fluids' => ['Power Steering Fluid', 'Brake Fluid', 'Transmission Fluid', 'Coolant']
                                            ];
                                        @endphp
                                        @foreach($items[$categoryKey] ?? [] as $item)
                                            <label class="flex items-start gap-3 p-2 hover:bg-gray-50 rounded-lg cursor-pointer transition">
                                                <input type="checkbox" name="selected_services[]" value="{{ $item }}" 
                                                       class="mt-1 service-checkbox" data-category="{{ $categoryKey }}" data-name="{{ $item }}">
                                                <div class="flex-1">
                                                    <div class="font-medium text-gray-800 text-sm">{{ $item }}</div>
                                                </div>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        
                        <!-- Other Repair Input -->
                        <div class="mt-4">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" id="otherRepairCheck" class="rounded">
                                <span class="text-sm text-gray-700">Other repair not listed</span>
                            </label>
                            <input type="text" name="other_maintenance_type" id="otherRepairInput" class="hidden w-full mt-2 form-input" placeholder="Please specify the repair needed">
                        </div>
                        
                        <!-- Selected Services Summary -->
                        <div class="mt-4 p-4 bg-gray-50 rounded-lg">
                            <h4 class="font-medium text-gray-800 mb-2">Selected Services</h4>
                            <div id="selectedServicesList" class="flex flex-wrap gap-2 min-h-[50px]">
                                <span class="text-xs text-gray-400">No services selected</span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Job Details -->
                    <div class="bg-white rounded-xl shadow-sm p-6">
                        <h3 class="font-semibold text-gray-800 mb-4 flex items-center gap-2">
                            <i class="fas fa-info-circle text-blue-600"></i>
                            Issue Details
                        </h3>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <div class="md:col-span-2">
                                <label class="form-label">Description of Issue *</label>
                                <textarea name="description" rows="4" class="form-textarea" placeholder="Describe the issue or required maintenance in detail..."></textarea>
                            </div>
                            <div>
                                <label class="form-label">Priority Level</label>
                                <select name="priority" class="form-select" id="prioritySelect">
                                    <option value="low">Low - Can wait</option>
                                    <option value="medium" selected>Medium - Schedule soon</option>
                                    <option value="high">High - As soon as possible</option>
                                    <option value="urgent">Urgent - Immediate attention</option>
                                </select>
                            </div>
                            <div>
                                <label class="form-label">Preferred Date</label>
                                <input type="date" name="maintenance_date" class="form-input" value="{{ now()->addDays(2)->format('Y-m-d') }}">
                            </div>
                            <div>
                                <label class="form-label">Current Mileage (km)</label>
                                <input type="number" name="mileage_at_service" class="form-input" value="{{ $selectedVehicle->mileage ?? 0 }}" required>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Right Column - Summary -->
                <div class="space-y-6">
                    <!-- Info Card -->
                    <div class="bg-white rounded-xl shadow-sm p-6 sticky top-6">
                        <h3 class="font-semibold text-gray-800 mb-4 flex items-center gap-2">
                            <i class="fas fa-info-circle text-blue-600"></i>
                            Submission Info
                        </h3>
                        
                        <div class="space-y-3">
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">Status</span>
                                <span class="inline-flex px-2 py-1 rounded-full text-xs bg-yellow-100 text-yellow-700">Pending Review</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">Estimated Response</span>
                                <span class="font-medium">24-48 hours</span>
                            </div>
                        </div>
                        
                        <div class="mt-4 p-3 bg-yellow-50 rounded-lg">
                            <p class="text-xs text-yellow-800 flex items-start gap-2">
                                <i class="fas fa-info-circle mt-0.5"></i>
                                <span>Your request will be reviewed by the fleet manager. You will be notified once approved.</span>
                            </p>
                        </div>
                        
                        <div class="mt-6 space-y-3">
                            <div class="flex gap-3">
                                <button type="submit" class="flex-1 py-3 bg-blue-600 text-white rounded-xl font-semibold hover:bg-blue-700 transition">
                                    <i class="fas fa-paper-plane mr-2"></i>Submit Request
                                </button>
                                <a href="{{ route('driver.fuel-mileage.dashboard') }}" class="px-4 py-3 bg-gray-100 text-gray-700 rounded-xl hover:bg-gray-200 transition">
                                    <i class="fas fa-times"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <!-- What happens next -->
                    <div class="bg-blue-50 rounded-xl p-4">
                        <div class="flex items-start gap-3">
                            <i class="fas fa-lightbulb text-blue-600 mt-0.5"></i>
                            <div class="text-sm text-blue-800">
                                <p class="font-semibold mb-1">What happens next?</p>
                                <p>1. ✓ Request submitted to fleet manager</p>
                                <p>2. 🔧 Review and approval process</p>
                                <p>3. 📧 You'll receive status updates</p>
                                <p>4. 🚗 Vehicle scheduled for service</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
let selectedServices = [];
let currentMaintenanceType = null;

$(document).ready(function() {
    // Maintenance type selection
    $('.maintenance-card').click(function() {
        let type = $(this).data('type');
        currentMaintenanceType = type;
        
        // Remove selected class from all cards
        $('.maintenance-card').removeClass('selected border-blue-500 bg-blue-50');
        $(this).addClass('selected border-blue-500 bg-blue-50');
        
        // Check the radio button
        $(this).find('input[type="radio"]').prop('checked', true);
        
        // Show/hide checklist section (for specific or both)
        if (type === 'specific' || type === 'both') {
            $('#checklistSection').removeClass('hidden').fadeIn(200);
            if (type === 'both') {
                $('#serviceNote').removeClass('hidden');
            } else {
                $('#serviceNote').addClass('hidden');
            }
        } else {
            $('#checklistSection').addClass('hidden').fadeOut(200);
            $('.service-checkbox').prop('checked', false);
            updateSelectedServices();
            updateCategoryCounts();
            $('#serviceNote').addClass('hidden');
        }
    });
    
    // Service checkbox change
    $(document).on('change', '.service-checkbox', function() {
        updateSelectedServices();
        updateCategoryCounts();
    });
    
    // Select all button
    $('#selectAllBtn').click(function() {
        $('.service-checkbox').prop('checked', true).trigger('change');
    });
    
    // Deselect all button
    $('#deselectAllBtn').click(function() {
        $('.service-checkbox').prop('checked', false).trigger('change');
    });
    
    // Other repair checkbox
    $('#otherRepairCheck').on('change', function() {
        if ($(this).is(':checked')) {
            $('#otherRepairInput').removeClass('hidden');
        } else {
            $('#otherRepairInput').addClass('hidden').val('');
            updateSelectedServices();
        }
    });
    
    $('#otherRepairInput').on('input', function() {
        updateSelectedServices();
    });
    
    // Priority change visual
    $('#prioritySelect').change(function() {
        let priority = $(this).val();
        $('.priority-badge').remove();
        $(this).after(`<span class="priority-badge priority-${priority} ml-2"><i class="fas ${getPriorityIcon(priority)}"></i> ${priority.toUpperCase()}</span>`);
    });
    
    // Initialize
    updatePriorityBadge();
});

function toggleCategory(categoryId) {
    $('#category-' + categoryId).toggleClass('hidden');
    $('#chevron-' + categoryId).toggleClass('rotate-90');
}

function updateSelectedServices() {
    selectedServices = [];
    let html = '';
    
    $('.service-checkbox:checked').each(function() {
        let name = $(this).data('name');
        selectedServices.push(name);
        html += `<span class="service-tag selected">
                    <i class="fas fa-check-circle text-xs"></i>
                    ${name}
                </span>`;
    });
    
    let otherService = $('#otherRepairInput').val();
    if (otherService) {
        selectedServices.push(otherService);
        html += `<span class="service-tag selected">
                    <i class="fas fa-check-circle text-xs"></i>
                    ${otherService}
                </span>`;
    }
    
    if (selectedServices.length === 0) {
        html = '<span class="text-xs text-gray-400">No services selected</span>';
    }
    
    $('#selectedServicesList').html(html);
}

function updateCategoryCounts() {
    let categories = ['engine', 'transmission', 'brakes', 'electrical', 'cooling', 'tires', 'suspension', 'fluids'];
    
    categories.forEach(category => {
        let count = $(`.service-checkbox[data-category="${category}"]:checked`).length;
        $('#count-' + category).text(count + ' selected');
    });
}

function getPriorityIcon(priority) {
    switch(priority) {
        case 'low': return 'fa-arrow-down';
        case 'medium': return 'fa-minus';
        case 'high': return 'fa-arrow-up';
        case 'urgent': return 'fa-exclamation-triangle';
        default: return 'fa-flag';
    }
}

function updatePriorityBadge() {
    let priority = $('#prioritySelect').val();
    $('#prioritySelect').after(`<span class="priority-badge priority-${priority} ml-2"><i class="fas ${getPriorityIcon(priority)}"></i> ${priority.toUpperCase()}</span>`);
}

    
// Form submission
$('#maintenanceRequestForm').on('submit', function(e) {
    let maintenanceType = $('input[name="maintenance_type"]:checked').val();
    
    if (!maintenanceType) {
        e.preventDefault();
        Swal.fire('Error', 'Please select a maintenance type', 'error');
        return;
    }
    
    if ((maintenanceType === 'specific' || maintenanceType === 'both') && 
        $('.service-checkbox:checked').length === 0 && 
        !$('#otherRepairInput').val()) {
        e.preventDefault();
        Swal.fire('Warning', 'Please select at least one service from the checklist or specify the repair needed', 'warning');
        return;
    }
    
    let description = $('textarea[name="description"]').val();
    if (!description) {
        e.preventDefault();
        Swal.fire('Error', 'Please describe the issue', 'error');
        return;
    }
    
    e.preventDefault();

    let typeDisplay = maintenanceType === 'servicing' ? 'Servicing' : (maintenanceType === 'specific' ? 'Specific Repair' : 'Service + Repair');
    let repairCount = $('.service-checkbox:checked').length;
    let repairMessage = repairCount > 0 ? `<p class="text-sm mt-2"><span class="font-semibold">Repairs:</span> ${repairCount} item(s) selected</p>` : '';
    
    Swal.fire({
        title: 'Confirm Request',
        html: `<div class="text-left">
            <p class="font-semibold">Maintenance Type: <span class="text-blue-600">${typeDisplay}</span></p>
            ${repairMessage}
            <p class="mt-2 text-sm text-gray-600">Submit this maintenance request?</p>
        </div>`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        confirmButtonText: 'Yes, submit it!',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            if (maintenanceType === 'both') {
                $('<input>').attr({
                    type: 'hidden',
                    name: 'include_service',
                    value: '1'
                }).appendTo('#maintenanceRequestForm');
            }
            $('#maintenanceRequestForm').off('submit').trigger('submit');
        }
    });
});
</script>

@endsection
