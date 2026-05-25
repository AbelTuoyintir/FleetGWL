@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">Edit Driver</h1>
            <p class="mt-2 text-sm text-gray-600">Update driver information for {{ $driver->name }}</p>
        </div>

        <!-- Form Card -->
        <div class="bg-white shadow-xl rounded-lg overflow-hidden">
            <form method="POST" action="{{ route('drivers.update', $driver) }}" enctype="multipart/form-data">
                @csrf
                @method('PUT')

                <div class="px-6 py-8 space-y-6">
                    <!-- User Info (Read-only) -->
                    <div class="p-4 bg-gray-50 rounded-lg">
                        <h3 class="text-sm font-medium text-gray-700 mb-2">User Account</h3>
                        <div class="flex items-center">
                            <div class="flex-shrink-0 h-10 w-10 bg-gray-200 rounded-full flex items-center justify-center">
                                <i class="fas fa-user text-gray-400"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-gray-900">{{ $driver->name }}</p>
                                <p class="text-sm text-gray-500">{{ $driver->email }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- License Information -->
                    <div>
                        <h3 class="text-lg font-medium text-gray-900 mb-4 border-b pb-2">
                            <i class="fas fa-id-card mr-2"></i>License Information
                        </h3>

                        <!-- License Number -->
                        <div class="mb-6">
                            <label for="license_number" class="block text-sm font-medium text-gray-700 mb-2">
                                License Number <span class="text-red-500">*</span>
                            </label>
                            <input type="text"
                                   name="license_number"
                                   id="license_number"
                                   class="block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('license_number') border-red-500 @enderror"
                                   value="{{ old('license_number', $driver->license_number) }}"
                                   placeholder="e.g., DL123456789"
                                   required>
                            @error('license_number')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Vehicle Assignment -->
                        <div class="mb-6">
                            <label for="assigned_vehicle_id" class="block text-sm font-medium text-gray-700 mb-2">
                                Assign Vehicle
                            </label>
                            <select name="assigned_vehicle_id"
                                    id="assigned_vehicle_id"
                                    class="block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('assigned_vehicle_id') border-red-500 @enderror">
                                <option value="">No vehicle assigned</option>
                                @foreach($vehicles as $vehicle)
                                    <option value="{{ $vehicle->id }}"
                                            {{ old('assigned_vehicle_id', $driver->assigned_vehicle_id) == $vehicle->id ? 'selected' : '' }}>
                                        {{ $vehicle->plate_number }} - {{ $vehicle->make }} {{ $vehicle->model }}
                                        @if($vehicle->assigned_driver_id == $driver->id)
                                            (Currently assigned)
                                        @endif
                                    </option>
                                @endforeach
                            </select>
                            @error('assigned_vehicle_id')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                            <p class="text-gray-500 text-sm mt-1">
                                @if($driver->vehicle)
                                    Currently assigned to: {{ $driver->vehicle->plate_number }}
                                @else
                                    Not currently assigned to any vehicle
                                @endif
                            </p>
                        </div>

                        <!-- License Photo -->
                        <div>
                            <label for="license_photo" class="block text-sm font-medium text-gray-700 mb-2">
                                License Photo
                            </label>
                            <div class="flex flex-col md:flex-row md:items-center">
                                <!-- Current Photo -->
                                <div class="mb-4 md:mb-0 md:mr-6">
                                    @if($driver->license_photo)
                                        <div class="relative">
                                            <img src="{{ asset('storage/' . $driver->license_photo) }}"
                                                 alt="Current License Photo"
                                                 class="h-40 w-64 object-cover rounded-lg border border-gray-200">
                                            <div class="absolute top-2 right-2">
                                                <a href="{{ asset('storage/' . $driver->license_photo) }}"
                                                   target="_blank"
                                                   class="bg-blue-600 text-white p-1 rounded-full hover:bg-blue-700">
                                                    <i class="fas fa-expand text-xs"></i>
                                                </a>
                                            </div>
                                        </div>
                                        <p class="text-xs text-gray-500 mt-1">Current license photo</p>
                                    @else
                                        <div class="h-40 w-64 bg-gray-200 rounded-lg flex items-center justify-center">
                                            <div class="text-center">
                                                <i class="fas fa-id-card text-gray-400 text-2xl mb-2"></i>
                                                <p class="text-xs text-gray-500">No license photo</p>
                                            </div>
                                        </div>
                                    @endif
                                </div>

                                <!-- Upload New -->
                                <div class="flex-1">
                                    <div id="photoPreview" class="h-40 w-full bg-gray-100 rounded-lg flex items-center justify-center mb-4 border-2 border-dashed border-gray-300">
                                        <div class="text-center">
                                            <i class="fas fa-cloud-upload-alt text-gray-400 text-2xl mb-2"></i>
                                            <p class="text-xs text-gray-500">New photo preview</p>
                                        </div>
                                    </div>
                                    <input type="file"
                                           name="license_photo"
                                           id="license_photo"
                                           class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
                                           accept="image/*"
                                           onchange="previewImage(event)">
                                    <p class="text-xs text-gray-500 mt-1">Upload new license photo (JPG, PNG, GIF up to 2MB)</p>
                                    @error('license_photo')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                    @if($driver->license_photo)
                                        <div class="mt-2">
                                            <label class="inline-flex items-center">
                                                <input type="checkbox" name="remove_photo" value="1" class="rounded border-gray-300 text-red-600">
                                                <span class="ml-2 text-sm text-gray-700">Remove current license photo</span>
                                            </label>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-between">
                    <a href="{{ route('drivers.show', $driver) }}"
                       class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50">
                        <i class="fas fa-times mr-2"></i>Cancel
                    </a>
                    <div class="flex space-x-3">
                        <a href="{{ route('drivers.show', $driver) }}"
                           class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50">
                            <i class="fas fa-eye mr-2"></i>View
                        </a>
                        <button type="submit"
                                class="inline-flex items-center px-6 py-2 border border-transparent text-sm font-medium rounded-lg shadow-sm text-white bg-blue-600 hover:bg-blue-700">
                            <i class="fas fa-save mr-2"></i>Update Driver
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function previewImage(event) {
    const preview = document.getElementById('photoPreview');
    const file = event.target.files[0];

    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.innerHTML = `<img src="${e.target.result}" class="h-full w-full object-cover rounded-lg">`;
        }
        reader.readAsDataURL(file);
    } else {
        preview.innerHTML = `
            <div class="text-center">
                <i class="fas fa-cloud-upload-alt text-gray-400 text-2xl mb-2"></i>
                <p class="text-xs text-gray-500">New photo preview</p>
            </div>
        `;
    }
}
</script>
@endsection
