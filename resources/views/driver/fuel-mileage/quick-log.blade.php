{{-- resources/views/driver/fuel-mileage/quick-log.blade.php --}}
@extends('layouts.driver')
@section('title', 'Quick Log - Fast Entry')

@section('content')
<style>
    .quick-card {
        transition: all 0.2s ease;
        border: 1px solid #e2e8f0;
        background: white;
        border-radius: 16px;
    }
    .quick-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px -6px rgba(0,0,0,0.1);
        border-color: #3b82f6;
    }
    .info-box {
        background: #f0f9ff;
        border-left: 4px solid #3b82f6;
    }
    .log-type-btn {
        transition: all 0.2s;
        cursor: pointer;
    }
    .log-type-btn.active {
        background: #3b82f6;
        color: white;
        border-color: #3b82f6;
    }
    .log-type-btn.active i {
        color: white;
    }
</style>

<div class="min-h-screen bg-gray-50 py-6">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <!-- Header -->
        <div class="mb-6">
            <div class="flex items-center gap-3">
                <a href="{{ route('driver.fuel-mileage.dashboard') }}" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-arrow-left"></i>
                </a>
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">Quick Log</h1>
                    <p class="text-gray-500 text-sm mt-1">Fast entry for mileage and maintenance records</p>
                </div>
            </div>
        </div>

        <!-- Vehicle Info Card -->
        <div class="bg-white rounded-xl shadow-sm p-4 mb-6 border-l-4 border-blue-500">
            <div class="flex items-center justify-between flex-wrap gap-3">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-truck text-blue-600"></i>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500">Your Vehicle</p>
                        <p class="font-bold text-gray-800">{{ $vehicle->registration_number }}</p>
                        <p class="text-xs text-gray-500">{{ $vehicle->make }} {{ $vehicle->model }}</p>
                    </div>
                </div>
                <div class="text-right">
                    <p class="text-xs text-gray-500">Current Mileage</p>
                    <p class="text-xl font-bold text-gray-800">{{ number_format($vehicle->current_mileage ?? $vehicle->mileage ?? 0) }} km</p>
                </div>
            </div>
        </div>

        <!-- Last Records Info -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
            @if($lastMaintenance)
            <div class="info-box p-3 rounded-lg">
                <div class="flex items-center gap-2">
                    <i class="fas fa-tools text-blue-600"></i>
                    <span class="text-xs text-gray-600">Last Maintenance:</span>
                    <span class="text-xs font-medium">{{ $lastMaintenance->maintenance_date->format('M d, Y') }}</span>
                    <span class="text-xs text-gray-500">at {{ number_format($lastMaintenance->mileage_at_service) }} km</span>
                </div>
            </div>
            @endif
            
            @if($lastMileageLog)
            <div class="info-box p-3 rounded-lg">
                <div class="flex items-center gap-2">
                    <i class="fas fa-road text-green-600"></i>
                    <span class="text-xs text-gray-600">Last Mileage:</span>
                    <span class="text-xs font-medium">{{ $lastMileageLog->week_label }}</span>
                    <span class="text-xs text-gray-500">{{ number_format($lastMileageLog->end_mileage) }} km</span>
                </div>
            </div>
            @endif
        </div>

        <!-- Main Form -->
        <div class="quick-card p-6">
            <h3 class="font-semibold text-gray-800 mb-4 flex items-center gap-2">
                <i class="fas fa-bolt text-yellow-500"></i> Quick Entry
            </h3>

            <form method="POST" action="{{ route('driver.fuel-mileage.quick-log.store') }}" class="space-y-6">
                @csrf

                <!-- Log Type Selection -->
                <div>
                    <label class="form-label">What would you like to record? *</label>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mt-2">
                        <div class="log-type-btn border rounded-lg p-4 text-center" data-type="maintenance">
                            <input type="radio" name="log_type" value="maintenance" id="typeMaintenance" class="hidden" required>
                            <div class="cursor-pointer">
                                <i class="fas fa-tools text-2xl text-blue-500 mb-2 block"></i>
                                <div class="font-medium text-gray-800">Maintenance Only</div>
                                <p class="text-xs text-gray-500">Request service</p>
                            </div>
                        </div>
                        <div class="log-type-btn border rounded-lg p-4 text-center" data-type="mileage">
                            <input type="radio" name="log_type" value="mileage" id="typeMileage" class="hidden" required>
                            <div class="cursor-pointer">
                                <i class="fas fa-road text-2xl text-green-500 mb-2 block"></i>
                                <div class="font-medium text-gray-800">Mileage Only</div>
                                <p class="text-xs text-gray-500">Log weekly mileage</p>
                            </div>
                        </div>
                        <div class="log-type-btn border rounded-lg p-4 text-center" data-type="both">
                            <input type="radio" name="log_type" value="both" id="typeBoth" class="hidden" required>
                            <div class="cursor-pointer">
                                <i class="fas fa-layer-group text-2xl text-purple-500 mb-2 block"></i>
                                <div class="font-medium text-gray-800">Both</div>
                                <p class="text-xs text-gray-500">Maintenance + Mileage</p>
                            </div>
                        </div>
                    </div>
                    @error('log_type')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Common Fields -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="form-label">Date *</label>
                        <input type="date" name="date" class="form-input @error('date') border-red-500 @enderror" 
                               value="{{ old('date', date('Y-m-d')) }}" required>
                        @error('date')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label class="form-label">Odometer Reading (km) *</label>
                        <input type="number" name="odometer" id="odometer" class="form-input @error('odometer') border-red-500 @enderror" 
                               value="{{ old('odometer', $vehicle->current_mileage ?? $vehicle->mileage ?? 0) }}" required>
                        @error('odometer')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Maintenance Fields (shown when maintenance or both selected) -->
                <div id="maintenanceFields" class="hidden space-y-4 border-t pt-4">
                    <h4 class="font-medium text-gray-800 flex items-center gap-2">
                        <i class="fas fa-tools text-blue-600"></i> Maintenance Details
                    </h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="form-label">Maintenance Type *</label>
                            <select name="maintenance_type" id="maintenanceType" class="form-select">
                                <option value="">Select Type</option>
                                <option value="servicing">Servicing - Regular Service</option>
                                <option value="specific">Specific - Specific Repair</option>
                                <option value="breakdown">Breakdown - Emergency Repair</option>
                            </select>
                        </div>
                        <div id="otherTypeField" style="display: none;">
                            <label class="form-label">Please specify</label>
                            <input type="text" name="other_maintenance_type" class="form-input" placeholder="Enter maintenance type">
                        </div>
                    </div>
                    <div>
                        <label class="form-label">Description *</label>
                        <textarea name="description" id="maintenanceDesc" rows="3" class="form-textarea" 
                                  placeholder="Describe the issue or service needed..."></textarea>
                    </div>
                </div>

                <!-- Mileage Fields (shown when mileage or both selected) -->
                <div id="mileageFields" class="hidden space-y-4 border-t pt-4">
                    <h4 class="font-medium text-gray-800 flex items-center gap-2">
                        <i class="fas fa-road text-green-600"></i> Mileage Details
                    </h4>
                    <div>
                        <label class="form-label">Week Label *</label>
                        <input type="text" name="week_label" id="weekLabel" class="form-input" 
                               value="{{ old('week_label', 'Week ' . date('W') . ' (' . date('M d') . ')') }}" 
                               placeholder="e.g., Week 23 (Jun 3-9)">
                        <p class="text-xs text-gray-500 mt-1">Current week: {{ 'Week ' . date('W') . ' (' . date('M d') . ')' }}</p>
                    </div>
                </div>

                <!-- Distance Display -->
                <div id="distanceInfo" class="hidden bg-green-50 rounded-lg p-3">
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600">Distance Traveled this week:</span>
                        <span id="distanceDisplay" class="text-xl font-bold text-green-600">0 km</span>
                    </div>
                    <p class="text-xs text-gray-500 mt-1">Calculated from your last recorded mileage</p>
                </div>

                <!-- Submit Button -->
                <div class="flex justify-end gap-3 pt-4 border-t">
                    <a href="{{ route('driver.fuel-mileage.dashboard') }}" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                        Cancel
                    </a>
                    <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 flex items-center gap-2">
                        <i class="fas fa-save"></i> Save Quick Log
                    </button>
                </div>
            </form>
        </div>

        <!-- Tips Section -->
        <div class="mt-6 p-4 bg-yellow-50 rounded-lg border border-yellow-200">
            <div class="flex items-start gap-3">
                <i class="fas fa-lightbulb text-yellow-600 mt-0.5"></i>
                <div>
                    <p class="text-sm font-medium text-yellow-800">Quick Tips</p>
                    <ul class="text-xs text-yellow-700 mt-1 space-y-1">
                        <li>• <strong>Maintenance Only</strong> - Use when you need to request vehicle service</li>
                        <li>• <strong>Mileage Only</strong> - Use to log your weekly mileage reading</li>
                        <li>• <strong>Both</strong> - Use when reporting an issue AND logging mileage at the same time</li>
                        <li>• The current odometer reading will be used for both records</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Get the last recorded mileage from the server
    const lastMileage = {{ $lastMileageLog->end_mileage ?? $vehicle->current_mileage ?? $vehicle->mileage ?? 0 }};
    const currentMileageInput = document.getElementById('odometer');
    const distanceDisplay = document.getElementById('distanceDisplay');
    const distanceInfo = document.getElementById('distanceInfo');

    // Update distance display when odometer changes
    function updateDistance() {
        const currentValue = parseFloat(currentMileageInput.value) || 0;
        const distance = currentValue - lastMileage;
        
        if (distance > 0) {
            distanceDisplay.innerHTML = `<span class="text-green-600 font-bold">${distance.toLocaleString()} km</span>`;
            distanceInfo.classList.remove('hidden');
        } else if (currentValue > 0 && distance < 0) {
            distanceDisplay.innerHTML = `<span class="text-red-600 font-bold">Invalid (Odometer must be > ${lastMileage.toLocaleString()})</span>`;
            distanceInfo.classList.remove('hidden');
        } else {
            distanceInfo.classList.add('hidden');
        }
    }

    currentMileageInput.addEventListener('input', updateDistance);
    updateDistance();

    // Log type selection handling
    const logTypeBtns = document.querySelectorAll('.log-type-btn');
    const maintenanceFields = document.getElementById('maintenanceFields');
    const mileageFields = document.getElementById('mileageFields');
    const maintenanceTypeSelect = document.getElementById('maintenanceType');
    const otherTypeField = document.getElementById('otherTypeField');
    const maintenanceDesc = document.getElementById('maintenanceDesc');
    const weekLabel = document.getElementById('weekLabel');

    logTypeBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            // Remove active class from all
            logTypeBtns.forEach(b => {
                b.classList.remove('active');
                b.style.background = '';
                b.style.borderColor = '';
            });
            
            // Add active class to clicked
            btn.classList.add('active');
            btn.style.background = '#eff6ff';
            btn.style.borderColor = '#3b82f6';
            
            // Check the radio button
            const radio = btn.querySelector('input[type="radio"]');
            if (radio) radio.checked = true;
            
            const type = btn.dataset.type;
            
            // Show/hide fields based on selection
            if (type === 'maintenance' || type === 'both') {
                maintenanceFields.classList.remove('hidden');
                if (maintenanceTypeSelect) maintenanceTypeSelect.required = true;
                if (maintenanceDesc) maintenanceDesc.required = true;
            } else {
                maintenanceFields.classList.add('hidden');
                if (maintenanceTypeSelect) maintenanceTypeSelect.required = false;
                if (maintenanceDesc) maintenanceDesc.required = false;
            }
            
            if (type === 'mileage' || type === 'both') {
                mileageFields.classList.remove('hidden');
                if (weekLabel) weekLabel.required = true;
            } else {
                mileageFields.classList.add('hidden');
                if (weekLabel) weekLabel.required = false;
            }
        });
    });

    // Handle "Specific" maintenance type to show "Other" field
    if (maintenanceTypeSelect) {
        maintenanceTypeSelect.addEventListener('change', function() {
            if (this.value === 'specific') {
                otherTypeField.style.display = 'block';
            } else {
                otherTypeField.style.display = 'none';
            }
        });
    }

    // Auto-fill week label if not manually entered
    if (weekLabel && !weekLabel.value) {
        const now = new Date();
        const weekNum = getWeekNumber(now);
        const month = now.toLocaleString('default', { month: 'short' });
        const day = now.getDate();
        weekLabel.value = `Week ${weekNum} (${month} ${day})`;
    }

    function getWeekNumber(date) {
        const d = new Date(Date.UTC(date.getFullYear(), date.getMonth(), date.getDate()));
        const dayNum = d.getUTCDay() || 7;
        d.setUTCDate(d.getUTCDate() + 4 - dayNum);
        const yearStart = new Date(Date.UTC(d.getUTCFullYear(), 0, 1));
        return Math.ceil((((d - yearStart) / 86400000) + 1) / 7);
    }
</script>

@endsection     