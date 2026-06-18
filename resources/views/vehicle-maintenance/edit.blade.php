{{-- resources/views/vehicle-maintenance/edit.blade.php --}}
@extends('layouts.app')
@section('title', 'Edit Maintenance Record')
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
    
    .info-card {
        background: white;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        overflow: hidden;
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
    .status-scheduled { background: #dbeafe; color: #1e40af; }
    .status-completed { background: #dcfce7; color: #166534; }
    .status-cancelled { background: #fee2e2; color: #991b1b; }
    .status-waiting { background: #fef3c7; color: #92400e; }
    .status-dispatched { background: #f1f5f9; color: #475569; }
    
    .checklist-card {
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        transition: all 0.2s;
    }
    .checklist-card:hover {
        border-color: #cbd5e1;
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
    
    @media (max-width: 768px) {
        .form-grid { grid-template-columns: 1fr; }
    }
</style>

<div class="min-h-screen bg-gray-50 py-6">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <!-- Header -->
        <div class="mb-6">
            <div class="flex items-center gap-3 text-sm text-gray-500 mb-2">
<a href="{{ route('maintenance.index') }}" class="hover:text-blue-600">
                    <i class="fas fa-arrow-left"></i> Back to Maintenance
                </a>
                <i class="fas fa-chevron-right text-xs"></i>
                <span class="text-gray-700">Edit Maintenance Record #{{ $maintenance->id }}</span>
            </div>
            <div class="flex justify-between items-center flex-wrap gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">Edit Maintenance Record</h1>
                    <p class="text-gray-500 text-sm mt-1">Update maintenance details for vehicle</p>
                </div>
                <div class="flex gap-3">
                    <span class="px-3 py-1.5 bg-gray-100 rounded-lg text-sm">
                        <i class="fas fa-calendar mr-1"></i> Record #{{ $maintenance->id }}
                    </span>
                </div>
            </div>
        </div>
        
        <form method="POST" action="{{ $maintenance->exists ? route('maintenance.update', $maintenance) : route('maintenance.store') }}" class="space-y-6">
            @csrf
            @method('PUT')
            
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Left Column - Main Form -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Basic Information -->
                    <div class="info-card">
                        <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
                            <h3 class="font-semibold text-gray-800">
                                <i class="fas fa-info-circle text-blue-600 mr-2"></i>Basic Information
                            </h3>
                        </div>
                        <div class="p-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                <div>
                                    <label class="form-label">Vehicle *</label>
                                    <select name="vehicle_id" class="form-select" required>
                                        <option value="">Select Vehicle</option>
                                        @foreach($vehicles as $vehicle)
                                            <option value="{{ $vehicle->id }}" {{ old('vehicle_id', $maintenance->vehicle_id) == $vehicle->id ? 'selected' : '' }}>
                                                {{ $vehicle->registration_number }} - {{ $vehicle->make }} {{ $vehicle->model }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="form-label">Driver</label>
                                    <select name="driver_id" class="form-select">
                                        <option value="">Select Driver</option>
                                        @foreach($drivers as $driver)
                                            <option value="{{ $driver->id }}" {{ old('driver_id', $maintenance->driver_id) == $driver->id ? 'selected' : '' }}>
                                                {{ $driver->user->first_name ?? '' }} {{ $driver->user->last_name ?? $driver->name ?? '' }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="form-label">Maintenance Type *</label>
                                    <select name="maintenance_type" class="form-select" required>
                                        <option value="servicing">Servicing - Regular Maintenance</option>
                                        <option value="specific">Specific - Targeted Repair</option>
                                        <option value="both">Both - Service + Repair</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="form-label">Status *</label>
                                    <select name="status" class="form-select" required>
                                        <option value="scheduled">Scheduled</option>
                                        <option value="waiting">Waiting</option>
                                        <option value="dispatched">Dispatched</option>
                                        <option value="completed">Completed</option>
                                        <option value="cancelled">Cancelled</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="form-label">Maintenance Date *</label>
                                    <input type="date" name="maintenance_date" class="form-input" value="{{ old('maintenance_date', $maintenance->maintenance_date ? $maintenance->maintenance_date->format('Y-m-d') : '') }}" required>
                                </div>
                                <div>
                                    <label class="form-label">Mileage at Service (km)</label>
                                    <input type="number" name="mileage_at_service" class="form-input" value="{{ old('mileage_at_service', $maintenance->mileage_at_service) }}" min="0">
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Checklist Section -->
                    <div class="info-card">
                        <div class="px-6 py-4 bg-gray-50 border-b border-gray-200 flex justify-between items-center">
                            <h3 class="font-semibold text-gray-800">
                                <i class="fas fa-check-square text-blue-600 mr-2"></i>Service Checklist
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
                        <div class="p-6">
                            @php
                                $savedChecklist = is_array($maintenance->checklist) ? $maintenance->checklist : (array) json_decode($maintenance->checklist, true);
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
                            
                            <div class="space-y-4 max-h-96 overflow-y-auto pr-2">
                                @foreach($categories as $categoryKey => $category)
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
                                            @foreach($items[$categoryKey] ?? [] as $item)
                                                <label class="flex items-start gap-3 p-2 hover:bg-gray-50 rounded-lg cursor-pointer transition">
                                                    <input type="checkbox" name="checklist[]" value="{{ $item }}" 
                                                           class="mt-1 service-checkbox" data-category="{{ $categoryKey }}"
                                                           {{ is_array($savedChecklist) && in_array($item, $savedChecklist) ? 'checked' : '' }}>
                                                    <div class="flex-1">
                                                        <div class="font-medium text-gray-800 text-sm">{{ $item }}</div>
                                                    </div>
                                                </label>
                                            @endforeach
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            
                            <div class="mt-4">
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" id="otherRepairCheck" class="rounded">
                                    <span class="text-sm text-gray-700">Other service not listed</span>
                                </label>
                                <input type="text" name="other_maintenance_type" id="otherRepairInput" class="hidden w-full mt-2 form-input" placeholder="Please specify the service performed" value="{{ old('other_maintenance_type', $maintenance->other_maintenance_type) }}">
                            </div>
                            
                            <!-- Selected Services Summary -->
                            <div class="mt-4 p-4 bg-gray-50 rounded-lg">
                                <h4 class="font-medium text-gray-800 mb-2">Selected Services</h4>
                                <div id="selectedServicesList" class="flex flex-wrap gap-2 min-h-[50px]">
                                    @if(!empty($savedChecklist))
                                        @foreach($savedChecklist as $service)
                                            <span class="service-tag selected">
                                                <i class="fas fa-check-circle text-xs"></i>
                                                {{ $service }}
                                            </span>
                                        @endforeach
                                    @else
                                        <span class="text-xs text-gray-400">No services selected</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Description -->
                    <div class="info-card">
                        <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
                            <h3 class="font-semibold text-gray-800">
                                <i class="fas fa-file-alt text-gray-600 mr-2"></i>Description & Notes
                            </h3>
                        </div>
                        <div class="p-6">
                            <textarea name="description" rows="4" class="form-textarea" placeholder="Describe the issue or maintenance performed...">{{ old('description', $maintenance->description) }}</textarea>
                        </div>
                    </div>
                </div>
                
                <!-- Right Column - Cost & Service Info -->
                <div class="space-y-6">
                    <!-- Cost Information -->
                    <div class="info-card">
                        <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
                            <h3 class="font-semibold text-gray-800">
                                <i class="fas fa-coins text-green-600 mr-2"></i>Cost Information
                            </h3>
                        </div>
                        <div class="p-6 space-y-4">
                            <div>
                                <label class="form-label">Cost (GHS) *</label>
                                <input type="number" name="cost" class="form-input" step="0.01" min="0" value="{{ old('cost', $maintenance->cost) }}" required>
                            </div>
                            <div>
                                <label class="form-label">Service Provider</label>
                                <input type="text" name="service_provider" class="form-input" placeholder="e.g., Prime Auto Works" value="{{ old('service_provider', $maintenance->service_provider) }}">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Future Service Planning -->
                    <div class="info-card">
                        <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
                            <h3 class="font-semibold text-gray-800">
                                <i class="fas fa-calendar-alt text-purple-600 mr-2"></i>Future Service Planning
                            </h3>
                        </div>
                        <div class="p-6 space-y-4">
                            <div>
                                <label class="form-label">Next Service Due Date</label>
                                <input type="date" name="next_service_due" class="form-input" value="{{ old('next_service_due', $maintenance->next_service_due) }}">
                            </div>
                            <div>
                                <label class="form-label">Next Expected Mileage (km)</label>
                                <input type="number" name="next_expected_mileage" class="form-input" min="0" value="{{ old('next_expected_mileage', $maintenance->next_expected_mileage) }}">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Status Info -->
                    <div class="info-card">
                        <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
                            <h3 class="font-semibold text-gray-800">
                                <i class="fas fa-info-circle text-gray-600 mr-2"></i>Record Information
                            </h3>
                        </div>
                        <div class="p-6 space-y-2 text-sm">
                            <div class="flex justify-between">
                                <span class="text-gray-500">Created:</span>
                                <span>{{ optional($maintenance->created_at)->format('M d, Y g:i A') ?? 'N/A' }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-500">Last Modified:</span>
                                <span>{{ optional($maintenance->updated_at)->format('M d, Y g:i A') ?? 'N/A' }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-500">Created By:</span>
                                <span>{{ $maintenance->created_by ? optional($maintenance->creator)->name : 'System' }}</span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Action Buttons -->
                    <div class="bg-white rounded-xl shadow-sm p-6 sticky top-6">
                        <button type="submit" class="w-full py-3 bg-blue-600 text-white rounded-xl font-semibold hover:bg-blue-700 transition flex items-center justify-center gap-2">
                            <i class="fas fa-save"></i> Update Maintenance Record
                        </button>
href="{{ route('maintenance.index') }}"
                            <i class="fas fa-times"></i> Cancel
                        </a>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
$(document).ready(function() {
    // Initialize maintenance type
    let savedType = '{{ old('maintenance_type', $maintenance->maintenance_type) }}';
    $('select[name="maintenance_type"]').val(savedType);
    
    // Initialize status
    let savedStatus = '{{ old('status', $maintenance->status) }}';
    $('select[name="status"]').val(savedStatus);
    
    // Service checkbox change
    $('.service-checkbox').on('change', function() {
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
            $('#otherRepairInput').addClass('hidden');
        }
    });
    
    // Check if other repair has value
    if ($('#otherRepairInput').val()) {
        $('#otherRepairCheck').prop('checked', true);
        $('#otherRepairInput').removeClass('hidden');
    }
    
    // Initialize selected services display
    updateSelectedServices();
    updateCategoryCounts();
});

function toggleCategory(categoryId) {
    $('#category-' + categoryId).toggleClass('hidden');
    $('#chevron-' + categoryId).toggleClass('rotate-90');
}

function updateSelectedServices() {
    let html = '';
    
    $('.service-checkbox:checked').each(function() {
        let name = $(this).val();
        html += `<span class="service-tag selected">
                    <i class="fas fa-check-circle text-xs"></i>
                    ${name}
                </span>`;
    });
    
    let otherService = $('#otherRepairInput').val();
    if (otherService) {
        html += `<span class="service-tag selected">
                    <i class="fas fa-check-circle text-xs"></i>
                    ${otherService}
                </span>`;
    }
    
    if (html === '') {
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
</script>

@endsection