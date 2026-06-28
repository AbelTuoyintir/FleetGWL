
@extends('layouts.app')
@section('title', 'Edit Vehicle - ' . $vehicle->registration_number)
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
        
        .image-preview {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-radius: 10px;
        }
        
        @media print {
            .sidebar-fleet, .no-print, header {
                display: none !important;
            }
            main { margin-left: 0 !important; padding: 0 !important; }
        }
    </style>

<!-- Main Content -->
<main class="min-h-screen p-2">
    <div class="max-w-5xl mx-auto">
        <!-- Header -->
        <div class="mb-6">
            <div class="flex items-center gap-3 text-sm text-gray-500 mb-2">
                <a href="{{ route('vehicles.index') }}" class="hover:text-blue-600">
                    <i class="fas fa-arrow-left"></i> Back to Vehicles
                </a>
                <i class="fas fa-chevron-right text-xs"></i>
                <a href="{{ route('vehicles.show', $vehicle) }}" class="hover:text-blue-600">
                    {{ $vehicle->registration_number }}
                </a>
                <i class="fas fa-chevron-right text-xs"></i>
                <span class="text-gray-700">Edit</span>
            </div>
            <div class="flex justify-between items-center flex-wrap gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">Edit Vehicle</h1>
                    <p class="text-gray-500 text-sm mt-1">Update vehicle information and details</p>
                </div>
                <div class="flex gap-3">
                    <a href="{{ route('vehicles.show', $vehicle) }}" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg text-sm hover:bg-gray-200 transition">
                        <i class="fas fa-times mr-1"></i> Cancel
                    </a>
                </div>
            </div>
        </div>
        
        <form id="editVehicleForm" action="{{ route('vehicles.update', $vehicle) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Main Form - Left Column (2/3) -->
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
                                    <label class="form-label">Registration Number *</label>
                                    <input type="text" name="registration_number" class="form-input" value="{{ old('registration_number', $vehicle->registration_number) }}" required>
                                    @error('registration_number')
                                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div>
                                    <label class="form-label">Vehicle Type *</label>
                                    <select name="vehicle_type" class="form-select" required>
                                        <option value="">Select Type</option>
                                        <option value="Saloon" {{ old('vehicle_type', $vehicle->vehicle_type) == 'Saloon' ? 'selected' : '' }}>Saloon</option>
                                        <option value="SUV" {{ old('vehicle_type', $vehicle->vehicle_type) == 'SUV' ? 'selected' : '' }}>SUV</option>
                                        <option value="Truck" {{ old('vehicle_type', $vehicle->vehicle_type) == 'Truck' ? 'selected' : '' }}>Truck</option>
                                        <option value="Bus" {{ old('vehicle_type', $vehicle->vehicle_type) == 'Bus' ? 'selected' : '' }}>Bus</option>
                                        <option value="Van" {{ old('vehicle_type', $vehicle->vehicle_type) == 'Van' ? 'selected' : '' }}>Van</option>
                                        <option value="Pickup" {{ old('vehicle_type', $vehicle->vehicle_type) == 'Pickup' ? 'selected' : '' }}>Pickup</option>
                                        <option value="Motorcycle" {{ old('vehicle_type', $vehicle->vehicle_type) == 'Motorcycle' ? 'selected' : '' }}>Motorcycle</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="form-label">Make *</label>
                                    <input type="text" name="make" class="form-input" value="{{ old('make', $vehicle->make) }}" required>
                                </div>
                                <div>
                                    <label class="form-label">Model *</label>
                                    <input type="text" name="model" class="form-input" value="{{ old('model', $vehicle->model) }}" required>
                                </div>
                                <div>
                                    <label class="form-label">Year</label>
                                    <input type="number" name="year" class="form-input" value="{{ old('year', $vehicle->year) }}" min="1900" max="{{ date('Y') + 1 }}">
                                </div>
                                <div>
                                    <label class="form-label">Color</label>
                                    <div class="flex gap-2">
                                        <input type="color" name="color_picker" id="color_picker" class="w-12 h-10 rounded border" value="{{ old('color', $vehicle->color) }}">
                                        <input type="text" name="color" id="color_text" class="form-input flex-1" value="{{ old('color', $vehicle->color) }}" placeholder="e.g., White, Black, Silver">
                                    </div>
                                </div>
                                <div>
                                    <label class="form-label">Chassis Number *</label>
                                    <input type="text" name="chassis_number" class="form-input" value="{{ old('chassis_number', $vehicle->chassis_number) }}" required>
                                </div>
                                <div>
                                    <label class="form-label">Engine Number</label>
                                    <input type="text" name="engine_number" class="form-input" value="{{ old('engine_number', $vehicle->engine_number) }}">
                                </div>
                                <div>
                                    <label class="form-label">Current Mileage (km)</label>
                                    <input type="number" name="mileage" class="form-input" value="{{ old('mileage', $vehicle->mileage) }}" min="0">
                                </div>
                                <div>
                                    <label class="form-label">Fuel Consumption (km/L)</label>
                                    <input type="number" name="fuel_consumption" class="form-input" value="{{ old('fuel_consumption', $vehicle->fuel_consumption) }}" step="0.1" min="0">
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Location & Assignment -->
                    <div class="info-card">
                        <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
                            <h3 class="font-semibold text-gray-800">
                                <i class="fas fa-map-marker-alt text-green-600 mr-2"></i>Location & Assignment
                            </h3>
                        </div>
                        <div class="p-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                <div>
                                    <label class="form-label">Region</label>
                                    <select name="region_id" class="form-select" id="regionSelect">
                                        <option value="">Select Region</option>
                                        @foreach($regions as $region)
                                            <option value="{{ $region->id }}" {{ old('region_id', $vehicle->region_id) == $region->id ? 'selected' : '' }}>
                                                {{ $region->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="form-label">District</label>
                                    <select name="district_id" class="form-select" id="districtSelect">
                                        <option value="">Select District</option>
                                        @foreach($districts as $district)
                                            <option value="{{ $district->id }}" {{ old('district_id', $vehicle->district_id) == $district->id ? 'selected' : '' }}>
                                                {{ $district->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="form-label">Station</label>
                                    <select name="station_id" class="form-select">
                                        <option value="">Select Station</option>
                                        @foreach($stations as $station)
                                            <option value="{{ $station->id }}" {{ old('station_id', $vehicle->station_id) == $station->id ? 'selected' : '' }}>
                                                {{ $station->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="form-label">Office</label>
                                    <select name="office_id" class="form-select">
                                        <option value="">Select Office</option>
                                        @foreach($offices as $office)
                                            <option value="{{ $office->id }}" {{ old('office_id', $vehicle->office_id) == $office->id ? 'selected' : '' }}>
                                                {{ $office->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="form-label">Assigned Driver</label>
                                    <select name="assigned_driver_id" class="form-select">
                                        <option value="">Select Driver</option>
                                        @foreach($drivers as $driver)
                                            <option value="{{ $driver->id }}" {{ old('assigned_driver_id', $vehicle->assigned_driver_id) == $driver->id ? 'selected' : '' }}>
                                                {{ $driver->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="form-label">Status *</label>
                                    <select name="status" class="form-select" required>
                                        <option value="active" {{ old('status', $vehicle->status) == 'active' ? 'selected' : '' }}>Active</option>
                                        <option value="inactive" {{ old('status', $vehicle->status) == 'inactive' ? 'selected' : '' }}>Inactive</option>
                                        <option value="maintenance" {{ old('status', $vehicle->status) == 'maintenance' ? 'selected' : '' }}>Maintenance</option>
                                        <option value="disposed" {{ old('status', $vehicle->status) == 'disposed' ? 'selected' : '' }}>Disposed</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Financial & Documents -->
                    <div class="info-card">
                        <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
                            <h3 class="font-semibold text-gray-800">
                                <i class="fas fa-file-invoice-dollar text-yellow-600 mr-2"></i>Financial & Documents
                            </h3>
                        </div>
                        <div class="p-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                <div>
                                    <label class="form-label">Purchase Price (GHS)</label>
                                    <input type="number" name="purchase_price" class="form-input" value="{{ old('purchase_price', $vehicle->purchase_price) }}" step="0.01" min="0">
                                </div>
                                <div>
                                    <label class="form-label">Purchase Date</label>
                                    <input type="date" name="purchase_date" class="form-input" value="{{ old('purchase_date', $vehicle->purchase_date) }}">
                                </div>
                                <div>
                                    <label class="form-label">Registration Date</label>
                                    <input type="date" name="registration_date" class="form-input" value="{{ old('registration_date', $vehicle->registration_date) }}">
                                </div>
                                <div>
                                    <label class="form-label">Insurance Expiry Date</label>
                                    <input type="date" name="insurance_expiry_date" class="form-input" value="{{ old('insurance_expiry_date', $vehicle->insurance_expiry_date) }}">
                                </div>
                                <div>
                                    <label class="form-label">Next Inspection Date</label>
                                    <input type="date" name="next_inspection_date" class="form-input" value="{{ old('next_inspection_date', $vehicle->next_inspection_date) }}">
                                </div>
                                <div>
                                    <label class="form-label">Owner Name</label>
                                    <input type="text" name="owner_name" class="form-input" value="{{ old('owner_name', $vehicle->owner_name) }}">
                                </div>
                                <div>
                                    <label class="form-label">Owner Contact</label>
                                    <input type="text" name="owner_contact" class="form-input" value="{{ old('owner_contact', $vehicle->owner_contact) }}">
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Notes -->
                    <div class="info-card">
                        <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
                            <h3 class="font-semibold text-gray-800">
                                <i class="fas fa-sticky-note text-purple-600 mr-2"></i>Additional Notes
                            </h3>
                        </div>
                        <div class="p-6">
                            <textarea name="notes" rows="4" class="form-textarea" placeholder="Any additional information about the vehicle...">{{ old('notes', $vehicle->notes) }}</textarea>
                        </div>
                    </div>
                </div>
                
                <!-- Right Column - Photo & Actions (1/3) -->
                <div class="space-y-6">
                    <!-- Vehicle Photo -->
                    <div class="info-card">
                        <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
                            <h3 class="font-semibold text-gray-800">
                                <i class="fas fa-camera text-blue-600 mr-2"></i>Vehicle Photo
                            </h3>
                        </div>
                        <div class="p-6">
                            <div class="text-center">
                                @if($vehicle->photo && Storage::disk('public')->exists($vehicle->photo))
                                    <img src="{{ Storage::url($vehicle->photo) }}" alt="{{ $vehicle->registration_number }}" class="image-preview mb-4" id="imagePreview">
                                @else
                                    <div class="bg-gray-100 rounded-lg p-8 mb-4 text-center" id="imagePreviewContainer">
                                        <i class="fas fa-truck text-gray-400 text-5xl mb-2"></i>
                                        <p class="text-gray-500 text-sm">No photo uploaded</p>
                                    </div>
                                    <img src="" alt="Preview" class="image-preview mb-4 hidden" id="imagePreview">
                                @endif
                                <label class="cursor-pointer">
                                    <span class="inline-block px-4 py-2 bg-gray-100 text-gray-700 rounded-lg text-sm hover:bg-gray-200 transition">
                                        <i class="fas fa-upload mr-1"></i> Choose Photo
                                    </span>
                                    <input type="file" name="photo" id="photoInput" class="hidden" accept="image/*">
                                </label>
                                <p class="text-xs text-gray-500 mt-2">Max 10MB. JPG, PNG, GIF</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Quick Actions -->
                    <div class="info-card">
                        <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
                            <h3 class="font-semibold text-gray-800">
                                <i class="fas fa-bolt text-yellow-600 mr-2"></i>Quick Actions
                            </h3>
                        </div>
                        <div class="p-4 space-y-3">
                            <button type="button" onclick="openMaintenanceModal()" class="w-full text-left px-4 py-2 bg-orange-50 hover:bg-orange-100 rounded-lg transition flex items-center gap-3">
                                <i class="fas fa-tools text-orange-600"></i>
                                <div>
                                    <p class="font-medium text-gray-800">Dispatch for Maintenance</p>
                                    <p class="text-xs text-gray-500">Send vehicle to service center</p>
                                </div>
                            </button>
                            <a href="{{ route('vehicles.maintenance.job-order.create', $vehicle) }}" class="w-full text-left px-4 py-2 bg-purple-50 hover:bg-purple-100 rounded-lg transition flex items-center gap-3">
                                <i class="fas fa-clipboard-list text-purple-600"></i>
                                <div>
                                    <p class="font-medium text-gray-800">Create Job Order</p>
                                    <p class="text-xs text-gray-500">Create detailed maintenance job order</p>
                                </div>
                            </a>
                        </div>
                    </div>
                    
                    <!-- Update Button -->
                    <div class="bg-white rounded-xl shadow-sm p-6 sticky top-6">
                        <button type="submit" class="w-full py-3 bg-blue-600 text-white rounded-xl font-semibold hover:bg-blue-700 transition">
                            <i class="fas fa-save mr-2"></i> Update Vehicle
                        </button>
                        <button type="button" onclick="confirmDelete()" class="w-full mt-3 py-3 bg-red-50 text-red-600 rounded-xl font-semibold hover:bg-red-100 transition">
                            <i class="fas fa-trash mr-2"></i> Delete Vehicle
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</main>

<!-- Maintenance Modal -->
<div id="maintenanceModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-xl shadow-xl max-w-md w-full mx-4">
        <div class="p-6 border-b border-gray-200 flex justify-between items-center">
            <h3 class="text-xl font-semibold text-gray-800">
                <i class="fas fa-tools text-orange-600 mr-2"></i>Dispatch for Maintenance
            </h3>
            <button onclick="closeMaintenanceModal()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        <form id="maintenanceForm" action="{{ route('vehicles.maintenance', $vehicle) }}" method="POST" class="p-6">
            @csrf
            <div class="mb-4">
                <label class="form-label">Maintenance Notes</label>
                <textarea name="maintenance_notes" rows="4" class="form-textarea" placeholder="Describe the maintenance required..."></textarea>
            </div>
            <div class="flex justify-end gap-3">
                <button type="button" onclick="closeMaintenanceModal()" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                    Cancel
                </button>
                <button type="submit" class="px-4 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700">
                    <i class="fas fa-paper-plane mr-2"></i>Dispatch
                </button>
            </div>
        </form>
    </div>
</div>

<script>
$(document).ready(function() {
    // Color picker sync
    $('#color_picker').on('input', function() {
        $('#color_text').val($(this).val());
    });
    
    $('#color_text').on('input', function() {
        $('#color_picker').val($(this).val());
    });
    
    // Image preview
    $('#photoInput').on('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(event) {
                $('#imagePreview').attr('src', event.target.result).removeClass('hidden');
                $('#imagePreviewContainer').addClass('hidden');
            };
            reader.readAsDataURL(file);
        }
    });
    
    // Region/District filtering
    $('#regionSelect').on('change', function() {
        const regionId = $(this).val();
        if (regionId) {
            // Filter districts based on selected region
            $('#districtSelect option').each(function() {
                const districtRegionId = $(this).data('region-id');
                if (districtRegionId && districtRegionId != regionId) {
                    $(this).hide();
                } else {
                    $(this).show();
                }
            });
        } else {
            $('#districtSelect option').show();
        }
    });
});

function openMaintenanceModal() {
    $('#maintenanceModal').removeClass('hidden').addClass('flex');
}

function closeMaintenanceModal() {
    $('#maintenanceModal').addClass('hidden').removeClass('flex');
    $('#maintenanceForm')[0].reset();
}

function confirmDelete() {
    Swal.fire({
        title: 'Delete Vehicle?',
        text: 'This action cannot be undone. The vehicle will be soft-deleted.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        confirmButtonText: 'Yes, delete vehicle',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '{{ route("vehicles.destroy", $vehicle) }}';
            form.innerHTML = '@csrf @method("DELETE")';
            document.body.appendChild(form);
            form.submit();
        }
    });
}

// Sidebar functions
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

// Maintenance form submission
$('#maintenanceForm').on('submit', function(e) {
    e.preventDefault();
    const formData = $(this).serialize();
    
    $.ajax({
        url: $(this).attr('action'),
        method: 'POST',
        data: formData,
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        success: function(response) {
            Swal.fire('Success', 'Vehicle dispatched for maintenance', 'success');
            closeMaintenanceModal();
            setTimeout(() => location.reload(), 1500);
        },
        error: function() {
            Swal.fire('Error', 'Failed to dispatch vehicle', 'error');
        }
    });
});
</script>
@endsection