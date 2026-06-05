{{-- resources/views/driver/fuel-mileage/maintenance-create.blade.php --}}
@extends('layouts.driver')
@section('title', 'Request Maintenance')

@section('content')
<div class="min-h-screen bg-gray-50 py-6">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <div class="mb-6">
            <a href="{{ route('driver.fuel-mileage.dashboard') }}" class="text-gray-500 hover:text-gray-700">
                <i class="fas fa-arrow-left mr-2"></i>Back to Dashboard
            </a>
            <h1 class="text-2xl font-bold text-gray-800 mt-2">Request Maintenance</h1>
            <p class="text-gray-500 text-sm mt-1">Submit a maintenance request for {{ $selectedVehicle->registration_number ?? 'your vehicle' }}</p>

        </div>
        
        <div class="bg-white rounded-xl shadow-sm overflow-hidden">
            <form method="POST" action="{{ route('driver.fuel-mileage.maintenance.store') }}" class="p-6 space-y-6">
                @csrf
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="form-label">Maintenance Date *</label>
                        <input type="date" name="maintenance_date" class="form-input @error('maintenance_date') border-red-500 @enderror" 
                               value="{{ old('maintenance_date', date('Y-m-d')) }}" required>
                        @error('maintenance_date')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <div>
                        <label class="form-label">Current Mileage (km) *</label>
                        <input type="number" name="mileage_at_service" class="form-input @error('mileage_at_service') border-red-500 @enderror" 
                               value="{{ old('mileage_at_service', $selectedVehicle->current_mileage ?? $selectedVehicle->mileage ?? 0) }}" required>

                        @error('mileage_at_service')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
                
                <div>
                    <label class="form-label">Maintenance Type *</label>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mt-2">
                        <label class="flex items-center p-3 border rounded-lg cursor-pointer hover:bg-gray-50">
                            <input type="radio" name="maintenance_type" value="servicing" class="mr-3" required>
                            <div>
                                <div class="font-medium">Servicing</div>
                                <div class="text-xs text-gray-500">Regular service</div>
                            </div>
                        </label>
                        <label class="flex items-center p-3 border rounded-lg cursor-pointer hover:bg-gray-50">
                            <input type="radio" name="maintenance_type" value="specific" class="mr-3">
                            <div>
                                <div class="font-medium">Specific</div>
                                <div class="text-xs text-gray-500">Specific repair</div>
                            </div>
                        </label>
                        <label class="flex items-center p-3 border rounded-lg cursor-pointer hover:bg-gray-50">
                            <input type="radio" name="maintenance_type" value="breakdown" class="mr-3">
                            <div>
                                <div class="font-medium">Breakdown</div>
                                <div class="text-xs text-gray-500">Emergency repair</div>
                            </div>
                        </label>
                    </div>
                    @error('maintenance_type')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                
                <div id="otherTypeField" style="display: none;">
                    <label class="form-label">Please specify</label>
                    <input type="text" name="other_maintenance_type" class="form-input" placeholder="Enter maintenance type">
                </div>
                
                <div>
                    <label class="form-label">Description of Issue</label>
                    <textarea name="description" rows="4" class="form-textarea @error('description') border-red-500 @enderror" 
                              placeholder="Please describe the issue in detail...">{{ old('description') }}</textarea>
                    @error('description')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                
                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                    <div class="flex items-start gap-3">
                        <i class="fas fa-info-circle text-yellow-600 mt-0.5"></i>
                        <div>
                            <p class="text-sm text-yellow-800 font-medium">Important Information</p>
                            <p class="text-xs text-yellow-700 mt-1">Your request will be reviewed by the fleet manager. Cost will be added after service completion.</p>
                        </div>
                    </div>
                </div>
                
                <div class="flex justify-end gap-3 pt-4 border-t">
                    <a href="{{ route('driver.fuel-mileage.dashboard') }}" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                        Cancel
                    </a>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                        <i class="fas fa-paper-plane mr-2"></i>Submit Request
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.querySelectorAll('input[name="maintenance_type"]').forEach(radio => {
        radio.addEventListener('change', function() {
            const otherField = document.getElementById('otherTypeField');
            if (this.value === 'specific') {
                otherField.style.display = 'block';
            } else {
                otherField.style.display = 'none';
            }
        });
    });
</script>
@endsection