{{-- resources/views/driver/fuel-mileage/mileage-create.blade.php --}}
@extends('layouts.driver')
@section('title', 'Record Mileage')

@section('content')
<div class="min-h-screen bg-gray-50 py-6">
    <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <div class="mb-6">
            <a href="{{ route('driver.fuel-mileage.dashboard') }}" class="text-gray-500 hover:text-gray-700">
                <i class="fas fa-arrow-left mr-2"></i>Back to Dashboard
            </a>
            <h1 class="text-2xl font-bold text-gray-800 mt-2">Record Weekly Mileage</h1>
            <p class="text-gray-500 text-sm mt-1">Log your weekly mileage for {{ $vehicle->registration_number }}</p>
        </div>
        
        <div class="bg-white rounded-xl shadow-sm overflow-hidden">
            @if(isset($existingLog))
                <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 m-6 rounded">
                    <div class="flex items-center">
                        <i class="fas fa-exclamation-triangle text-yellow-400 mr-3"></i>
                        <p class="text-sm text-yellow-700">A mileage log already exists for this week ({{ $weekLabel }}).</p>
                    </div>
                </div>
            @endif
            
            <form method="POST" action="{{ route('driver.fuel-mileage.mileage-logs.store') }}" class="p-6 space-y-6">
                @csrf
                
                <div class="bg-blue-50 rounded-lg p-4">
                    <div class="flex items-center gap-3">
                        <i class="fas fa-calendar-week text-blue-600"></i>
                        <div>
                            <p class="text-sm font-medium text-blue-800">Week: {{ $weekLabel }}</p>
                            <p class="text-xs text-blue-600">Starting {{ $weekStart->format('F j, Y') }}</p>
                        </div>
                    </div>
                    <input type="hidden" name="week_label" value="{{ $weekLabel }}">
                    <input type="hidden" name="week_start_date" value="{{ $weekStart->format('Y-m-d') }}">
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="form-label">Start Mileage (km)</label>
                        <input type="number" name="start_mileage" class="form-input " 
                               value="{{ old('start_mileage', $lastWeekLog->end_mileage ?? $vehicle->current_mileage ?? 0) }}" required>
                        <p class="text-xs text-gray-500 mt-1">Previous week's ending mileage</p>
                    </div>
                    <div>
                        <label class="form-label">End Mileage (km) *</label>
                        <input type="number" name="end_mileage" class=" border-1 shadow-sm my-2 rounded-lg @error('end_mileage') border-red-500 @enderror" 
                               value="{{ old('end_mileage') }}" required>
                        @error('end_mileage')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
                
                <div class="bg-gray-50 rounded-lg p-4">
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600">Distance Traveled</span>
                        <span class="text-xl font-bold text-green-600" id="distanceDisplay">0 km</span>
                    </div>
                </div>
                
                <div class="flex justify-end gap-3 pt-4 border-t">
                    <a href="{{ route('driver.fuel-mileage.dashboard') }}" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                        Cancel
                    </a>
                    <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700" {{ isset($existingLog) ? 'disabled' : '' }}>
                        <i class="fas fa-save mr-2"></i>Save Mileage Log
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    const startInput = document.querySelector('input[name="start_mileage"]');
    const endInput = document.querySelector('input[name="end_mileage"]');
    const distanceDisplay = document.getElementById('distanceDisplay');
    
    function calculateDistance() {
        const start = parseFloat(startInput?.value) || 0;
        const end = parseFloat(endInput?.value) || 0;
        const distance = end - start;
        
        if (distance > 0) {
            distanceDisplay.innerHTML = `<span class="text-green-600 font-bold">${distance.toLocaleString()} km</span>`;
            distanceDisplay.style.color = '#16a34a';
        } else if (end > 0 && distance < 0) {
            distanceDisplay.innerHTML = `<span class="text-red-600 font-bold">Invalid (End must be > Start)</span>`;
        } else {
            distanceDisplay.innerHTML = `<span class="text-gray-500">0 km</span>`;
        }
    }
    
    startInput?.addEventListener('input', calculateDistance);
    endInput?.addEventListener('input', calculateDistance);
</script>
@endsection