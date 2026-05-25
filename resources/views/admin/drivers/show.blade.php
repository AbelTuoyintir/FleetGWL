{{-- resources/views/drivers/show.blade.php --}}
@extends('layouts.app')
@section('title', 'Driver Details - ' . ($driver->user->first_name ?? 'Driver'))
@section('content')

<div class="min-h-screen bg-gray-50 py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <!-- Header -->
        <div class="mb-6 flex justify-between items-center flex-wrap gap-4">
            <div>
                <div class="flex items-center gap-3">
                    <a href="{{ route('drivers.index') }}" class="text-gray-500 hover:text-gray-700">
                        <i class="fas fa-arrow-left"></i>
                    </a>
                    <h1 class="text-2xl font-bold text-gray-800">Driver Details</h1>
                    <span class="status-badge status-{{ $driver->status }}">
                        <i class="fas {{ $driver->status == 'active' ? 'fa-check-circle' : 'fa-times-circle' }}"></i>
                        {{ ucfirst($driver->status) }}
                    </span>
                </div>
                <p class="text-gray-500 text-sm mt-1">{{ $driver->user->first_name ?? '' }} {{ $driver->user->last_name ?? '' }}</p>
            </div>
            <div class="flex gap-3">
                <a href="{{ route('drivers.edit', $driver) }}" class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm hover:bg-blue-700 transition">
                    <i class="fas fa-edit mr-1"></i> Edit Driver
                </a>
                <button onclick="window.print()" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg text-sm hover:bg-gray-200 transition">
                    <i class="fas fa-print mr-1"></i> Print
                </button>
            </div>
        </div>
        
        <!-- Driver Info Cards -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Personal Information -->
            <div class="lg:col-span-2 bg-white rounded-xl shadow-sm p-6">
                <h3 class="font-semibold text-gray-800 mb-4 pb-2 border-b">
                    <i class="fas fa-user text-blue-600 mr-2"></i>Personal Information
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div><label class="text-xs text-gray-500">Full Name</label><p class="font-medium">{{ $driver->user->first_name ?? '' }} {{ $driver->user->last_name ?? '' }}</p></div>
                    <div><label class="text-xs text-gray-500">Email</label><p class="font-medium">{{ $driver->user->email ?? 'N/A' }}</p></div>
                    <div><label class="text-xs text-gray-500">Phone</label><p class="font-medium">{{ $driver->user->phone ?? 'N/A' }}</p></div>
                    <div><label class="text-xs text-gray-500">Gender</label><p class="font-medium">{{ $driver->user->gender ?? 'N/A' }}</p></div>
                    <div><label class="text-xs text-gray-500">Staff ID</label><p class="font-medium">{{ $driver->user->staffID ?? 'N/A' }}</p></div>
                    <div><label class="text-xs text-gray-500">Joined Date</label><p class="font-medium">{{ $driver->created_at->format('M d, Y') }}</p></div>
                </div>
            </div>
            
            <!-- License Information -->
            <div class="bg-white rounded-xl shadow-sm p-6">
                <h3 class="font-semibold text-gray-800 mb-4 pb-2 border-b">
                    <i class="fas fa-id-card text-green-600 mr-2"></i>License Information
                </h3>
                <div class="space-y-3">
                    <div><label class="text-xs text-gray-500">License Number</label><p class="font-medium">{{ $driver->license_number ?? 'N/A' }}</p></div>
                    <div><label class="text-xs text-gray-500">License Class</label><p class="font-medium">{{ $driver->license_class ?? 'N/A' }}</p></div>
                    <div><label class="text-xs text-gray-500">Expiry Date</label><p class="font-medium {{ $driver->license_expiry_date && $driver->license_expiry_date->isPast() ? 'text-red-600' : '' }}">{{ $driver->license_expiry_date ? $driver->license_expiry_date->format('M d, Y') : 'N/A' }}</p></div>
                    @if($driver->license_photo)
                    <div><label class="text-xs text-gray-500">License Photo</label><a href="{{ Storage::url($driver->license_photo) }}" target="_blank" class="text-blue-600 text-sm block">View License <i class="fas fa-external-link-alt"></i></a></div>
                    @endif
                </div>
            </div>
        </div>
        
        <!-- Vehicle Assignment & Stats -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-6">
            <div class="bg-white rounded-xl shadow-sm p-6">
                <h3 class="font-semibold text-gray-800 mb-4 pb-2 border-b">
                    <i class="fas fa-car text-purple-600 mr-2"></i>Vehicle Assignment
                </h3>
                @if($driver->vehicle)
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-truck text-blue-600 text-xl"></i>
                        </div>
                        <div>
                            <p class="font-bold text-gray-800">{{ $driver->vehicle->registration_number }}</p>
                            <p class="text-sm text-gray-500">{{ $driver->vehicle->make }} {{ $driver->vehicle->model }} ({{ $driver->vehicle->year }})</p>
                        </div>
                    </div>
                @else
                    <p class="text-gray-500">No vehicle assigned</p>
                @endif
            </div>
            
            <div class="bg-white rounded-xl shadow-sm p-6">
                <h3 class="font-semibold text-gray-800 mb-4 pb-2 border-b">
                    <i class="fas fa-chart-line text-orange-600 mr-2"></i>Statistics
                </h3>
                <div class="grid grid-cols-2 gap-4">
                    <div class="text-center p-3 bg-gray-50 rounded-lg">
                        <p class="text-2xl font-bold text-blue-600">{{ $stats['total_mileage'] ?? 0 }}</p>
                        <p class="text-xs text-gray-500">Total Mileage (km)</p>
                    </div>
                    <div class="text-center p-3 bg-gray-50 rounded-lg">
                        <p class="text-2xl font-bold text-green-600">{{ $stats['total_fuel'] ?? 0 }}</p>
                        <p class="text-xs text-gray-500">Fuel Used (L)</p>
                    </div>
                    <div class="text-center p-3 bg-gray-50 rounded-lg">
                        <p class="text-2xl font-bold text-orange-600">{{ $stats['total_maintenances'] ?? 0 }}</p>
                        <p class="text-xs text-gray-500">Maintenances</p>
                    </div>
                    <div class="text-center p-3 bg-gray-50 rounded-lg">
                        <p class="text-2xl font-bold text-purple-600">{{ $stats['avg_efficiency'] ? number_format($stats['avg_efficiency'], 1) : 0 }}</p>
                        <p class="text-xs text-gray-500">Avg Efficiency (km/L)</p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Emergency Contact -->
        @if($driver->emergency_contact_name || $driver->emergency_contact_phone)
        <div class="bg-white rounded-xl shadow-sm p-6 mt-6">
            <h3 class="font-semibold text-gray-800 mb-4 pb-2 border-b">
                <i class="fas fa-phone-alt text-red-600 mr-2"></i>Emergency Contact
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div><label class="text-xs text-gray-500">Name</label><p class="font-medium">{{ $driver->emergency_contact_name ?? 'N/A' }}</p></div>
                <div><label class="text-xs text-gray-500">Phone</label><p class="font-medium">{{ $driver->emergency_contact_phone ?? 'N/A' }}</p></div>
            </div>
        </div>
        @endif
    </div>
</div>

@endsection