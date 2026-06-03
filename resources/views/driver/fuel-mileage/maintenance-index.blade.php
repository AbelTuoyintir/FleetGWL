{{-- resources/views/driver/fuel-mileage/maintenance-index.blade.php --}}
@extends('layouts.driver')
@section('title', 'Maintenance Requests')

@section('content')
<div class="min-h-screen bg-gray-50 py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <div class="mb-6 flex justify-between items-center">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Maintenance Requests</h1>
                <p class="text-gray-500 text-sm mt-1">View all your maintenance requests</p>
            </div>
            <a href="{{ route('driver.fuel-mileage.maintenance.create') }}" class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm hover:bg-blue-700">
                <i class="fas fa-plus mr-2"></i>New Request
            </a>
        </div>
        
        <!-- Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-5 mb-6">
            <div class="bg-white rounded-lg shadow-sm p-4">
                <p class="text-gray-500 text-sm">Total Cost</p>
                <p class="text-2xl font-bold text-gray-800">GHS {{ number_format($summary['total_cost'] ?? 0, 2) }}</p>
            </div>
            <div class="bg-white rounded-lg shadow-sm p-4">
                <p class="text-gray-500 text-sm">Total Records</p>
                <p class="text-2xl font-bold text-gray-800">{{ $summary['total_records'] ?? 0 }}</p>
            </div>
            <div class="bg-white rounded-lg shadow-sm p-4">
                <p class="text-gray-500 text-sm">Average Cost</p>
                <p class="text-2xl font-bold text-gray-800">GHS {{ number_format($summary['avg_cost'] ?? 0, 2) }}</p>
            </div>
        </div>
        
        <!-- Filters -->
        <div class="bg-white rounded-xl shadow-sm p-4 mb-6">
            <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Start Date</label>
                    <input type="date" name="start_date" class="form-input text-sm" value="{{ request('start_date') }}">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">End Date</label>
                    <input type="date" name="end_date" class="form-input text-sm" value="{{ request('end_date') }}">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Maintenance Type</label>
                    <select name="maintenance_type" class="form-select text-sm">
                        <option value="">All Types</option>
                        <option value="servicing" {{ request('maintenance_type') == 'servicing' ? 'selected' : '' }}>Servicing</option>
                        <option value="specific" {{ request('maintenance_type') == 'specific' ? 'selected' : '' }}>Specific</option>
                        <option value="breakdown" {{ request('maintenance_type') == 'breakdown' ? 'selected' : '' }}>Breakdown</option>
                    </select>
                </div>
                <div class="flex items-end gap-2">
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm">Apply Filters</button>
                    <a href="{{ route('driver.fuel-mileage.maintenance.index') }}" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg text-sm">Reset</a>
                </div>
            </form>
        </div>
        
        <!-- Maintenance Table -->
        <div class="bg-white rounded-xl shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="data-table w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500">Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500">Type</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500">Vehicle</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500">Mileage</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500">Cost (GHS)</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500">Status</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($maintenances as $maintenance)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 text-sm">{{ $maintenance->maintenance_date->format('Y-m-d') }}</td>
                            <td class="px-6 py-4 text-sm">{{ ucfirst($maintenance->maintenance_type) }}</td>
                            <td class="px-6 py-4 text-sm">{{ $maintenance->vehicle->registration_number }}</td>
                            <td class="px-6 py-4 text-sm text-right">{{ number_format($maintenance->mileage_at_service) }}</td>
                            <td class="px-6 py-4 text-sm text-right">GHS {{ number_format($maintenance->cost ?? 0, 2) }}</td>
                            <td class="px-6 py-4 text-sm text-center">
                                <span class="px-2 py-1 rounded-full text-xs 
                                    {{ $maintenance->status == 'completed' ? 'bg-green-100 text-green-700' : 
                                       ($maintenance->status == 'waiting' ? 'bg-yellow-100 text-yellow-700' : 'bg-blue-100 text-blue-700') }}">
                                    {{ ucfirst($maintenance->status) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-center">
                                <a href="{{ route('driver.fuel-mileage.maintenance.show', $maintenance->id) }}" class="text-blue-600 hover:text-blue-800 mx-1">
                                    <i class="fas fa-eye"></i>
                                </a>
                                @if($maintenance->status == 'waiting')
                                <button onclick="deleteMaintenance({{ $maintenance->id }})" class="text-red-600 hover:text-red-800 mx-1">
                                    <i class="fas fa-trash"></i>
                                </button>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="px-6 py-8 text-center text-gray-500">No maintenance records found</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="px-6 py-4 border-t">
                {{ $maintenances->links() }}
            </div>
        </div>
    </div>
</div>

<script>
function deleteMaintenance(id) {
    Swal.fire({
        title: 'Delete Request?',
        text: 'This action cannot be undone.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        confirmButtonText: 'Yes, delete'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch(`/driver/fuel-mileage/maintenance/${id}/delete`, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
            }).then(() => window.location.reload());
        }
    });
}
</script>
@endsection