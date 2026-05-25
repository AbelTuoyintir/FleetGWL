@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">Add New Driver</h1>
            <p class="mt-2 text-sm text-gray-600">Register a new driver to your fleet</p>
        </div>

        <!-- Form Card -->
        <div class="bg-white shadow-xl rounded-lg overflow-hidden">
            <form method="POST" action="{{ route('drivers.store') }}" enctype="multipart/form-data">
                @csrf

                <div class="px-6 py-8 space-y-6">
                    <!-- User Selection -->
                    <div>
                        <h3 class="text-lg font-medium text-gray-900 mb-4 border-b pb-2">
                            <i class="fas fa-user mr-2"></i>Select User
                        </h3>

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Driver Account Source <span class="text-red-500">*</span></label>
                            <div class="flex flex-wrap gap-6">
                                <label class="inline-flex items-center">
                                    <input type="radio"
                                           name="registration_mode"
                                           value="existing"
                                           {{ old('registration_mode', 'existing') === 'existing' ? 'checked' : '' }}
                                           class="text-blue-600 focus:ring-blue-500"
                                           onchange="toggleRegistrationMode()">
                                    <span class="ml-2 text-sm text-gray-700">Use Existing User</span>
                                </label>
                                <label class="inline-flex items-center">
                                    <input type="radio"
                                           name="registration_mode"
                                           value="new"
                                           {{ old('registration_mode') === 'new' ? 'checked' : '' }}
                                           class="text-blue-600 focus:ring-blue-500"
                                           onchange="toggleRegistrationMode()">
                                    <span class="ml-2 text-sm text-gray-700">Create New User</span>
                                </label>
                            </div>
                            @error('registration_mode')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div id="existing-user-section" class="{{ old('registration_mode', 'existing') === 'new' ? 'hidden' : '' }}">
                            <label for="user_id" class="block text-sm font-medium text-gray-700 mb-2">
                                User Account <span class="text-red-500">*</span>
                            </label>
                            <select name="user_id"
                                    id="user_id"
                                    class="block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('user_id') border-red-500 @enderror"
                                    {{ old('registration_mode', 'existing') === 'existing' ? 'required' : '' }}>
                                <option value="">Select a user</option>
                                @foreach($users as $user)
                                    <option value="{{ $user->id }}" {{ old('user_id') == $user->id ? 'selected' : '' }}>
                                        {{ $user->name }} ({{ $user->email }})
                                    </option>
                                @endforeach
                            </select>
                            @error('user_id')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                            <p class="text-gray-500 text-sm mt-1">Only users without driver accounts are shown.</p>
                        </div>

                        <div id="new-user-section" class="space-y-6 {{ old('registration_mode') === 'new' ? '' : 'hidden' }}">
                            <div>
                                <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                                    Full Name <span class="text-red-500">*</span>
                                </label>
                                <input type="text"
                                       name="name"
                                       id="name"
                                       class="block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('name') border-red-500 @enderror"
                                       placeholder="Enter driver's full name"
                                       value="{{ old('name') }}"
                                       {{ old('registration_mode') === 'new' ? 'required' : '' }}>
                                @error('name')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                                        Email <span class="text-red-500">*</span>
                                    </label>
                                    <input type="email"
                                           name="email"
                                           id="email"
                                           class="block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('email') border-red-500 @enderror"
                                           placeholder="Enter email address"
                                           value="{{ old('email') }}"
                                           {{ old('registration_mode') === 'new' ? 'required' : '' }}>
                                    @error('email')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label for="phone" class="block text-sm font-medium text-gray-700 mb-2">Phone</label>
                                    <input type="text"
                                           name="phone"
                                           id="phone"
                                           class="block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('phone') border-red-500 @enderror"
                                           placeholder="Enter phone number"
                                           value="{{ old('phone') }}">
                                    @error('phone')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
                                        Password <span class="text-red-500">*</span>
                                    </label>
                                    <input type="password"
                                           name="password"
                                           id="password"
                                           class="block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('password') border-red-500 @enderror"
                                           placeholder="Minimum 8 characters"
                                           {{ old('registration_mode') === 'new' ? 'required' : '' }}>
                                    @error('password')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-2">
                                        Confirm Password <span class="text-red-500">*</span>
                                    </label>
                                    <input type="password"
                                           name="password_confirmation"
                                           id="password_confirmation"
                                           class="block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                           placeholder="Re-enter password"
                                           {{ old('registration_mode') === 'new' ? 'required' : '' }}>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- License Information -->
                    <div>
                        <h3 class="text-lg font-medium text-gray-900 mb-4 border-b pb-2">
                            <i class="fas fa-id-card mr-2"></i>License Information
                        </h3>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- License Number -->
                            <div>
                                <label for="license_number" class="block text-sm font-medium text-gray-700 mb-2">
                                    License Number <span class="text-red-500">*</span>
                                </label>
                                <input type="text"
                                       name="license_number"
                                       id="license_number"
                                       class="block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('license_number') border-red-500 @enderror"
                                       value="{{ old('license_number') }}"
                                       placeholder="e.g., DL123456789"
                                       required>
                                @error('license_number')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- License Class -->
                            <div>
                                <label for="license_class" class="block text-sm font-medium text-gray-700 mb-2">
                                    License Class (Optional)
                                </label>
                                <select name="license_class"
                                        id="license_class"
                                        class="block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                    <option value="">Select Class</option>
                                    <option value="A" {{ old('license_class') == 'A' ? 'selected' : '' }}>A - Motorcycle</option>
                                    <option value="B" {{ old('license_class') == 'B' ? 'selected' : '' }}>B - Light Vehicle</option>
                                    <option value="C" {{ old('license_class') == 'C' ? 'selected' : '' }}>C - Heavy Vehicle</option>
                                    <option value="D" {{ old('license_class') == 'D' ? 'selected' : '' }}>D - Bus</option>
                                    <option value="E" {{ old('license_class') == 'E' ? 'selected' : '' }}>E - Articulated Vehicle</option>
                                </select>
                                @error('license_class')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- License Expiry Date -->
                            <div>
                                <label for="license_expiry_date" class="block text-sm font-medium text-gray-700 mb-2">
                                    License Expiry Date (Optional)
                                </label>
                                <input type="date"
                                       name="license_expiry_date"
                                       id="license_expiry_date"
                                       class="block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                       value="{{ old('license_expiry_date') }}">
                                @error('license_expiry_date')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Assigned Vehicle -->
                            <div>
                                <label for="assigned_vehicle_id" class="block text-sm font-medium text-gray-700 mb-2">
                                    Assigned Vehicle (Optional)
                                </label>
                                <select name="assigned_vehicle_id"
                                        id="assigned_vehicle_id"
                                        class="block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                    <option value="">Select Vehicle</option>
                                    @foreach($vehicles as $vehicle)
                                        <option value="{{ $vehicle->id }}" {{ old('assigned_vehicle_id') == $vehicle->id ? 'selected' : '' }}>
                                            {{ $vehicle->registration_number }} - {{ $vehicle->make }} {{ $vehicle->model }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('assigned_vehicle_id')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <!-- License Photo -->
                        <div class="mt-6">
                            <label for="license_photo" class="block text-sm font-medium text-gray-700 mb-2">
                                License Photo (Optional)
                            </label>
                            <div class="mt-1 flex items-center">
                                <div id="photoPreview" class="h-32 w-48 bg-gray-200 rounded-lg flex items-center justify-center mr-4 border-2 border-dashed border-gray-300">
                                    <div class="text-center">
                                        <i class="fas fa-camera text-gray-400 text-2xl mb-2"></i>
                                        <p class="text-xs text-gray-500">No photo selected</p>
                                    </div>
                                </div>
                                <div class="flex-1">
                                    <input type="file"
                                           name="license_photo"
                                           id="license_photo"
                                           class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
                                           accept="image/*"
                                           onchange="previewImage(event)">
                                    <p class="text-xs text-gray-500 mt-1">JPG, PNG, GIF up to 2MB</p>
                                    @error('license_photo')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Address -->
                        <div class="mt-6">
                            <label for="address" class="block text-sm font-medium text-gray-700 mb-2">
                                Address (Optional)
                            </label>
                            <textarea name="address"
                                      id="address"
                                      rows="3"
                                      class="block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                      placeholder="Enter driver's address">{{ old('address') }}</textarea>
                            @error('address')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Emergency Contact -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
                            <div>
                                <label for="emergency_contact_name" class="block text-sm font-medium text-gray-700 mb-2">
                                    Emergency Contact Name (Optional)
                                </label>
                                <input type="text"
                                       name="emergency_contact_name"
                                       id="emergency_contact_name"
                                       class="block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                       value="{{ old('emergency_contact_name') }}"
                                       placeholder="Name of emergency contact">
                                @error('emergency_contact_name')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="emergency_contact_phone" class="block text-sm font-medium text-gray-700 mb-2">
                                    Emergency Contact Phone (Optional)
                                </label>
                                <input type="text"
                                       name="emergency_contact_phone"
                                       id="emergency_contact_phone"
                                       class="block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                       value="{{ old('emergency_contact_phone') }}"
                                       placeholder="Phone number of emergency contact">
                                @error('emergency_contact_phone')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <!-- Notes -->
                        <div class="mt-6">
                            <label for="notes" class="block text-sm font-medium text-gray-700 mb-2">
                                Notes (Optional)
                            </label>
                            <textarea name="notes"
                                      id="notes"
                                      rows="2"
                                      class="block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                      placeholder="Any additional notes about the driver">{{ old('notes') }}</textarea>
                            @error('notes')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-between">
                    <a href="{{ route('drivers.index') }}"
                       class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
                        <i class="fas fa-arrow-left mr-2"></i>Cancel
                    </a>
                    <button type="submit"
                            class="inline-flex items-center px-6 py-3 border border-transparent text-sm font-medium rounded-lg shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
                        <i class="fas fa-save mr-2"></i>Create Driver
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function toggleRegistrationMode() {
    const selectedMode = document.querySelector('input[name="registration_mode"]:checked')?.value || 'existing';
    const existingSection = document.getElementById('existing-user-section');
    const newSection = document.getElementById('new-user-section');
    const userIdField = document.getElementById('user_id');
    const newFieldIds = ['name', 'email', 'password', 'password_confirmation'];

    if (selectedMode === 'new') {
        existingSection?.classList.add('hidden');
        newSection?.classList.remove('hidden');
        if (userIdField) userIdField.required = false;
        newFieldIds.forEach((id) => {
            const field = document.getElementById(id);
            if (field) field.required = true;
        });
    } else {
        existingSection?.classList.remove('hidden');
        newSection?.classList.add('hidden');
        if (userIdField) userIdField.required = true;
        newFieldIds.forEach((id) => {
            const field = document.getElementById(id);
            if (field) field.required = false;
        });
    }
}

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
                <i class="fas fa-camera text-gray-400 text-2xl mb-2"></i>
                <p class="text-xs text-gray-500">No photo selected</p>
            </div>
        `;
    }
}

document.addEventListener('DOMContentLoaded', function() {
    toggleRegistrationMode();
});
</script>
@endsection