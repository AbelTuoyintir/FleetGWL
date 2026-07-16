{{-- resources/views/admin/drivers/index.blade.php --}}
@extends('layouts.app')
@section('title', 'Driver Hub - Fleet Management')
@section('content')

<style>
    * { font-family: 'Inter', sans-serif; }
    .stat-card {
        transition: all 0.2s ease;
        border: 1px solid #e2e8f0;
        background: white;
        border-radius: 12px;
    }
    .stat-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px -6px rgba(0,0,0,0.1);
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
    .status-active { background: #dcfce7; color: #166534; }
    .status-inactive { background: #fee2e2; color: #991b1b; }
    .data-table th {
        text-align: left;
        padding: 12px 8px;
        font-size: 12px;
        font-weight: 600;
        color: #475569;
        border-bottom: 1px solid #e2e8f0;
    }
    .data-table td {
        padding: 12px 8px;
        font-size: 13px;
        border-bottom: 1px solid #f1f5f9;
    }
    .data-table tr:hover { background-color: #f8fafc; }
</style>

<div class="min-h-screen bg-gray-50 py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        @if(session('success'))
            <div class="mb-4 p-4 bg-green-50 border border-green-200 text-green-800 rounded-lg text-sm">
                <i class="fas fa-check-circle mr-2"></i>{{ session('success') }}
            </div>
        @endif
        @if(session('error'))
            <div class="mb-4 p-4 bg-red-50 border border-red-200 text-red-800 rounded-lg text-sm">
                <i class="fas fa-exclamation-circle mr-2"></i>{{ session('error') }}
            </div>
        @endif

        <div class="mb-6 flex justify-between items-center flex-wrap gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Driver Hub</h1>
                <p class="text-gray-500 text-sm mt-1">Manage fleet drivers, licenses, and vehicle assignments</p>
            </div>
            <div class="flex flex-wrap gap-3">
                <a href="{{ route('drivers.statistics') }}" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg text-sm hover:bg-gray-200 transition">
                    <i class="fas fa-chart-pie mr-1"></i> Statistics
                </a>
                <a href="{{ route('drivers.create') }}" class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm hover:bg-blue-700 transition">
                    <i class="fas fa-plus mr-1"></i> Add Driver
                </a>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 mb-8">
            <div class="stat-card p-5">
                <p class="text-gray-500 text-xs uppercase tracking-wide">Total Drivers</p>
                <p class="text-2xl font-bold text-gray-800">{{ $stats['total_drivers'] ?? 0 }}</p>
            </div>
            <div class="stat-card p-5">
                <p class="text-gray-500 text-xs uppercase tracking-wide">Active</p>
                <p class="text-2xl font-bold text-green-700">{{ $stats['active_drivers'] ?? 0 }}</p>
            </div>
            <div class="stat-card p-5">
                <p class="text-gray-500 text-xs uppercase tracking-wide">Inactive</p>
                <p class="text-2xl font-bold text-red-700">{{ $stats['inactive_drivers'] ?? 0 }}</p>
            </div>
            <div class="stat-card p-5">
                <p class="text-gray-500 text-xs uppercase tracking-wide">With Vehicle</p>
                <p class="text-2xl font-bold text-blue-700">{{ $stats['assigned_drivers'] ?? 0 }}</p>
            </div>
            <div class="stat-card p-5">
                <p class="text-gray-500 text-xs uppercase tracking-wide">Vehicles Available</p>
                <p class="text-2xl font-bold text-purple-700">{{ $stats['available_vehicles'] ?? 0 }}</p>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center">
                <h2 class="font-semibold text-gray-800">
                    <i class="fas fa-id-card text-blue-600 mr-2"></i>All Drivers
                </h2>
                <span class="text-xs text-gray-500">{{ $drivers->total() }} total</span>
            </div>

            <div class="overflow-x-auto">
                <table class="data-table w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th>Driver</th>
                            <th>License</th>
                            <th>Vehicle</th>
                            <th>Status</th>
                            <th>Activity</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($drivers as $driver)
                            @php
                                $user = $driver->user;
                                $displayName = $user?->name ?? 'Unknown';
                            @endphp
                            <tr>
                                <td>
                                    <p class="font-medium text-gray-800">{{ $displayName }}</p>
                                    <p class="text-xs text-gray-500">{{ $user?->email ?? '—' }}</p>
                                </td>
                                <td>
                                    <div class="flex items-center gap-2">
                                        <p class="text-gray-800 font-mono">{{ $driver->license_number ?? '—' }}</p>
                                        @if($driver->license_number)
                                        <button onclick="copyToClipboard('{{ addslashes($driver->license_number) }}', 'License Number')" class="text-gray-400 hover:text-blue-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 rounded p-0.5 transition" title="Copy License Number" aria-label="Copy License Number">
                                            <i class="far fa-copy text-xs"></i>
                                        </button>
                                        @endif
                                    </div>
                                    <p class="text-xs text-gray-500">
                                        @if($driver->license_expiry_date)
                                            Expires {{ $driver->license_expiry_date->format('M d, Y') }}
                                        @else
                                            No expiry on file
                                        @endif
                                    </p>
                                </td>
                                <td>
                                    @if($driver->vehicle)
                                        <p class="font-medium">{{ $driver->vehicle->registration_number }}</p>
                                        <p class="text-xs text-gray-500">{{ $driver->vehicle->make }} {{ $driver->vehicle->model }}</p>
                                        <form method="POST" action="{{ route('drivers.unassign-vehicle', $driver) }}" class="mt-1">
                                            @csrf
                                            <button type="submit" class="text-xs text-red-600 hover:underline">Unassign</button>
                                        </form>
                                    @elseif($availableVehicles->isNotEmpty())
                                        <form method="POST" action="{{ route('drivers.assign-vehicle', $driver) }}" class="flex items-center gap-2">
                                            @csrf
                                            <select name="vehicle_id" class="text-xs border border-gray-200 rounded px-2 py-1" required>
                                                <option value="">Assign vehicle…</option>
                                                @foreach($availableVehicles as $vehicle)
                                                    <option value="{{ $vehicle->id }}">{{ $vehicle->registration_number }}</option>
                                                @endforeach
                                            </select>
                                            <button type="submit" class="text-xs text-blue-600 hover:underline whitespace-nowrap">Assign</button>
                                        </form>
                                    @else
                                        <span class="text-gray-400 text-sm">Unassigned</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="status-badge status-{{ $driver->status }}">
                                        {{ ucfirst($driver->status) }}
                                    </span>
                                </td>
                                <td class="text-xs text-gray-500">
                                    <div>{{ $driver->mileage_logs_count }} mileage logs</div>
                                    <div>{{ $driver->fuel_logs_count }} fuel logs</div>
                                </td>
                                <td class="text-right whitespace-nowrap">
                                    <div class="flex justify-end gap-2">
                                        <a href="{{ route('drivers.show', $driver) }}" class="text-blue-600 hover:text-blue-800 transition p-1" title="View Driver Details" aria-label="View Driver Details">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="{{ route('drivers.edit', $driver) }}" class="text-gray-600 hover:text-gray-800 transition p-1" title="Edit Driver" aria-label="Edit Driver">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <form method="POST" action="{{ route('drivers.destroy', $driver) }}" class="inline" onsubmit="return confirm('Remove this driver from the fleet?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:text-red-800 transition p-1" title="Delete Driver" aria-label="Delete Driver">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-12 text-gray-500">
                                    <i class="fas fa-users text-3xl mb-3 opacity-30"></i>
                                    <p>No drivers registered yet.</p>
                                    <a href="{{ route('drivers.create') }}" class="inline-block mt-3 text-blue-600 hover:underline text-sm">Add your first driver</a>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($drivers->hasPages())
                <div class="px-6 py-4 border-t border-gray-100">
                    {{ $drivers->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
