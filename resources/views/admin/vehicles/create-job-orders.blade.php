@extends('layouts.app')
@section('title', 'Create Maintenance Job Order - ' . $vehicle->registration_number)
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
            ring: 2px solid #3b82f6;
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
            border-color: #3b82f6;
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
        
        @media print {
            .sidebar-fleet, .no-print, header, .action-buttons {
                display: none !important;
            }
            main { margin-left: 0 !important; padding: 0 !important; }
        }
    </style>

<!-- Mobile Overlay -->
<div id="mobileOverlay" class="overlay-fleet"></div>

<!-- Main Content -->
<main class="lg:ml-[280px] min-h-screen p-6">
    <div class="max-w-7xl mx-auto">
        <!-- Header -->
        <div class="mb-6">
            <div class="flex items-center gap-3 text-sm text-gray-500 mb-2">
                <a href="{{ route('vehicles.show', $vehicle) }}" class="hover:text-blue-600">
                    <i class="fas fa-arrow-left"></i> Back to Vehicle
                </a>
                <i class="fas fa-chevron-right text-xs"></i>
                <span class="text-gray-700">Create Job Order</span>
            </div>
            <div class="flex justify-between items-center flex-wrap gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">Create Maintenance Job Order</h1>
                    <p class="text-gray-500 text-sm mt-1">
                        {{ $vehicle->registration_number }} - {{ $vehicle->make }} {{ $vehicle->model }} ({{ $vehicle->year }})
                    </p>
                </div>
                <div class="flex gap-3">
                    <span class="px-3 py-1.5 bg-gray-100 rounded-lg text-sm">
                        <i class="fas fa-road mr-1"></i> Current Mileage: {{ number_format($vehicle->mileage ?? 0) }} km
                    </span>
                </div>
            </div>
        </div>
        
        <form id="jobOrderForm" action="{{ route('maintenance.job-order.store', $vehicle) }}" method="POST">
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
                            <!-- General Service Option -->
                            <div class="maintenance-card border-2 rounded-xl p-4" data-type="general_service">
                                <div class="flex items-start gap-3">
                                    <input type="radio" name="maintenance_type" value="general_service" id="general_service" class="mt-1">
                                    <div class="flex-1">
                                        <div class="font-semibold text-gray-800 flex items-center gap-2">
                                            <i class="fas fa-oil-can text-green-600"></i>
                                            General Service
                                        </div>
                                        <p class="text-xs text-gray-500 mt-1">Standard maintenance including oil change, filter replacement, and basic inspections</p>
                                        <div class="mt-3 flex flex-wrap gap-1">
                                            <span class="text-xs bg-gray-100 px-2 py-1 rounded">Oil Change</span>
                                            <span class="text-xs bg-gray-100 px-2 py-1 rounded">Filter Replacement</span>
                                            <span class="text-xs bg-gray-100 px-2 py-1 rounded">Basic Inspection</span>
                                        </div>
                                        <div class="mt-2 text-sm font-semibold text-green-600">Est. GHS 725.00</div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Specific Maintenance Option -->
                            <div class="maintenance-card border-2 rounded-xl p-4" data-type="specific">
                                <div class="flex items-start gap-3">
                                    <input type="radio" name="maintenance_type" value="specific" id="specific" class="mt-1">
                                    <div class="flex-1">
                                        <div class="font-semibold text-gray-800 flex items-center gap-2">
                                            <i class="fas fa-tasks text-blue-600"></i>
                                            Specific Maintenance
                                        </div>
                                        <p class="text-xs text-gray-500 mt-1">Select specific services from the checklist below</p>
                                        <div class="mt-3">
                                            <span class="text-xs bg-blue-100 text-blue-700 px-2 py-1 rounded">Custom Selection</span>
                                        </div>
                                        <div class="mt-2 text-sm font-semibold text-blue-600" id="specificEstimate">Est. GHS 0.00</div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Both Option -->
                            <div class="maintenance-card border-2 rounded-xl p-4" data-type="both">
                                <div class="flex items-start gap-3">
                                    <input type="radio" name="maintenance_type" value="both" id="both" class="mt-1">
                                    <div class="flex-1">
                                        <div class="font-semibold text-gray-800 flex items-center gap-2">
                                            <i class="fas fa-layer-group text-purple-600"></i>
                                            Both (General + Specific)
                                        </div>
                                        <p class="text-xs text-gray-500 mt-1">General service plus additional specific services</p>
                                        <div class="mt-3 flex flex-wrap gap-1">
                                            <span class="text-xs bg-green-100 text-green-700 px-2 py-1 rounded">General Service</span>
                                            <span class="text-xs bg-blue-100 text-blue-700 px-2 py-1 rounded">+ Custom Services</span>
                                        </div>
                                        <div class="mt-2 text-sm font-semibold text-purple-600" id="bothEstimate">Est. GHS 725.00</div>
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
                                Maintenance Checklist
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
                                @php
                                    $categoryItems = $checklistItems->where('category', $categoryKey);
                                @endphp
                                @if($categoryItems->count() > 0)
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
                                        @foreach($categoryItems as $item)
                                            <label class="flex items-start gap-3 p-2 hover:bg-gray-50 rounded-lg cursor-pointer transition">
                                                <input type="checkbox" name="selected_services[]" value="{{ $item->id }}" 
                                                       class="mt-1 service-checkbox" data-category="{{ $categoryKey }}"
                                                       data-cost="{{ $item->default_cost }}" data-name="{{ $item->item_name }}">
                                                <div class="flex-1">
                                                    <div class="font-medium text-gray-800 text-sm">{{ $item->item_name }}</div>
                                                    <div class="text-xs text-gray-500">{{ $item->description ?? 'Standard maintenance service' }}</div>
                                                </div>
                                                <div class="text-right">
                                                    <div class="text-sm font-semibold text-gray-700">GHS {{ number_format($item->default_cost, 2) }}</div>
                                                    <div class="text-xs text-gray-400">{{ $item->estimated_hours }} hrs</div>
                                                </div>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                                @endif
                            @endforeach
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
                            Job Details
                        </h3>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <div class="md:col-span-2">
                                <label class="form-label">Description / Issue Reported</label>
                                <textarea name="description" rows="3" class="form-textarea" placeholder="Describe the issue or required maintenance..."></textarea>
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
                                <label class="form-label">Scheduled Date</label>
                                <input type="date" name="scheduled_date" class="form-input" value="{{ now()->addDays(3)->format('Y-m-d') }}">
                            </div>
                            <div>
                                <label class="form-label">Mechanic Shop / Company</label>
                                <input type="text" name="service_provider" class="form-input" placeholder="e.g. Prime Auto Works Ltd" required>
                            </div>
                            <div>
                                <label class="form-label">Driver</label>
                                <select name="driver_id" class="form-select">
                                    <option value="">Select Driver (Optional)</option>
                                    @foreach($drivers as $driver)
                                        <option value="{{ $driver->id }}">{{ $driver->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="form-label">Manual Estimated Cost (Optional)</label>
                                <input type="number" name="estimated_cost" class="form-input" step="0.01" placeholder="Override auto-calculated cost">
                            </div>
                            <div class="md:col-span-2">
                                <label class="form-label">Workshop Notes (Optional)</label>
                                <textarea name="technician_notes" rows="2" class="form-textarea" placeholder="Any specific instructions for the mechanic shop/company..."></textarea>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Right Column - Summary -->
                <div class="space-y-6">
                    <!-- Cost Summary Card -->
                    <div class="bg-white rounded-xl shadow-sm p-6 sticky top-6">
                        <h3 class="font-semibold text-gray-800 mb-4 flex items-center gap-2">
                            <i class="fas fa-calculator text-green-600"></i>
                            Cost Summary
                        </h3>
                        
                        <div class="space-y-3">
                            <div class="flex justify-between text-sm pb-2 border-b">
                                <span class="text-gray-600">General Service</span>
                                <span class="font-semibold" id="summaryGeneralCost">GHS 0.00</span>
                            </div>
                            <div class="flex justify-between text-sm pb-2 border-b" id="summarySpecificRow" style="display: none;">
                                <span class="text-gray-600">Specific Services</span>
                                <span class="font-semibold" id="summarySpecificCost">GHS 0.00</span>
                            </div>
                            <div class="flex justify-between text-lg font-bold pt-2">
                                <span>Estimated Total</span>
                                <span class="text-blue-600" id="summaryTotalCost">GHS 0.00</span>
                            </div>
                        </div>
                        
                        <div class="mt-4 p-3 bg-yellow-50 rounded-lg">
                            <p class="text-xs text-yellow-800 flex items-start gap-2">
                                <i class="fas fa-info-circle mt-0.5"></i>
                                <span>This is an estimate. Final cost may vary based on actual work performed and parts required.</span>
                            </p>
                        </div>
                        
                        <div class="mt-6 space-y-3">
                            <div class="flex gap-3">
                                <button type="submit" class="flex-1 py-3 bg-blue-600 text-white rounded-xl font-semibold hover:bg-blue-700 transition">
                                    <i class="fas fa-paper-plane mr-2"></i>Create Job Order
                                </button>
                                <a href="{{ route('vehicles.show', $vehicle) }}" class="px-4 py-3 bg-gray-100 text-gray-700 rounded-xl hover:bg-gray-200 transition">
                                    <i class="fas fa-times"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Info Card -->
                    <div class="bg-blue-50 rounded-xl p-4">
                        <div class="flex items-start gap-3">
                            <i class="fas fa-lightbulb text-blue-600 mt-0.5"></i>
                            <div class="text-sm text-blue-800">
                                <p class="font-semibold mb-1">What happens next?</p>
                                <p>1. Job order will be reviewed by fleet manager</p>
                                <p>2. Vehicle will be sent to selected mechanic shop/company</p>
                                <p>3. You'll receive updates on service progress</p>
                                <p>4. Vehicle status will update to "Maintenance"</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</main>

<script>
let selectedServices = [];
let generalServiceCost = 725;
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
        
        // Show/hide checklist section
        if (type === 'specific' || type === 'both') {
            $('#checklistSection').removeClass('hidden');
            if (type === 'both') {
                $('#summaryGeneralCost').text('GHS ' + generalServiceCost.toFixed(2));
                $('#summaryGeneralCost').parent().show();
            }
        } else {
            $('#checklistSection').addClass('hidden');
            if (type === 'general_service') {
                $('#summaryGeneralCost').text('GHS ' + generalServiceCost.toFixed(2));
                $('#summarySpecificRow').hide();
                $('#summaryTotalCost').text('GHS ' + generalServiceCost.toFixed(2));
            }
        }
        
        updateCostSummary();
    });
    
    // Service checkbox change
    $(document).on('change', '.service-checkbox', function() {
        updateSelectedServices();
        updateCategoryCounts();
        updateCostSummary();
    });
    
    // Select all button
    $('#selectAllBtn').click(function() {
        $('.service-checkbox').prop('checked', true);
        updateSelectedServices();
        updateCategoryCounts();
        updateCostSummary();
    });
    
    // Deselect all button
    $('#deselectAllBtn').click(function() {
        $('.service-checkbox').prop('checked', false);
        updateSelectedServices();
        updateCategoryCounts();
        updateCostSummary();
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
        let cost = $(this).data('cost');
        selectedServices.push({ name: name, cost: cost });
        html += `<span class="service-tag selected">
                    <i class="fas fa-check-circle text-xs"></i>
                    ${name}
                    <span class="text-xs opacity-75">GHS ${cost}</span>
                </span>`;
    });
    
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

function updateCostSummary() {
    let maintenanceType = $('input[name="maintenance_type"]:checked').val();
    let specificTotal = 0;
    
    // Calculate specific services total
    $('.service-checkbox:checked').each(function() {
        specificTotal += parseFloat($(this).data('cost')) || 0;
    });
    
    let totalCost = 0;
    
    if (maintenanceType === 'general_service') {
        totalCost = generalServiceCost;
        $('#summaryGeneralCost').text('GHS ' + generalServiceCost.toFixed(2));
        $('#summarySpecificRow').hide();
        $('#specificEstimate').text('Est. GHS 0.00');
        $('#bothEstimate').text('Est. GHS 725.00');
    } else if (maintenanceType === 'specific') {
        totalCost = specificTotal;
        $('#summaryGeneralCost').parent().hide();
        $('#summarySpecificRow').show();
        $('#summarySpecificCost').text('GHS ' + specificTotal.toFixed(2));
        $('#specificEstimate').text('Est. GHS ' + specificTotal.toFixed(2));
    } else if (maintenanceType === 'both') {
        totalCost = generalServiceCost + specificTotal;
        $('#summaryGeneralCost').parent().show();
        $('#summaryGeneralCost').text('GHS ' + generalServiceCost.toFixed(2));
        $('#summarySpecificRow').show();
        $('#summarySpecificCost').text('GHS ' + specificTotal.toFixed(2));
        $('#bothEstimate').text('Est. GHS ' + totalCost.toFixed(2));
    }
    
    $('#summaryTotalCost').text('GHS ' + totalCost.toFixed(2));
    
    // Update manual cost override hint
    if ($('input[name="estimated_cost"]').val()) {
        let manualCost = parseFloat($('input[name="estimated_cost"]').val());
        $('#summaryTotalCost').text('GHS ' + manualCost.toFixed(2) + ' *');
    }
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

// Manual cost override
$('input[name="estimated_cost"]').on('input', function() {
    let manualCost = $(this).val();
    if (manualCost) {
        $('#summaryTotalCost').text('GHS ' + parseFloat(manualCost).toFixed(2) + ' *');
        $('#summaryTotalCost').append('<span class="text-xs text-gray-400 ml-1">(manual override)</span>');
    } else {
        updateCostSummary();
    }
});

// Form submission
$('#jobOrderForm').on('submit', function(e) {
    let maintenanceType = $('input[name="maintenance_type"]:checked').val();
    
    if (!maintenanceType) {
        e.preventDefault();
        Swal.fire('Error', 'Please select a maintenance type', 'error');
        return;
    }
    
    if ((maintenanceType === 'specific' || maintenanceType === 'both') && $('.service-checkbox:checked').length === 0) {
        e.preventDefault();
        Swal.fire('Warning', 'Please select at least one service from the checklist', 'warning');
        return;
    }
    
    e.preventDefault();

    Swal.fire({
        title: 'Confirm Job Order',
        text: 'Create this maintenance job order?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        confirmButtonText: 'Yes, create it!'
    }).then((result) => {
        if (result.isConfirmed) {
            $('#jobOrderForm').off('submit').trigger('submit');
        }
    });
});
</script>
@endsection

