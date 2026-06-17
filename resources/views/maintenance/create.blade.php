@extends('layouts.app')
@section('title', 'Add Vehicle Maintenance Record')
@section('content')
<div class="min-h-screen bg-gray-50 py-6">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <form action="{{ route('maintenance.store') }}" method="POST" class="p-8 space-y-6 bg-white rounded-2xl shadow-sm">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Vehicle *</label>
                    <select name="vehicle_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
                        @foreach($vehicles as $vehicle)
                            <option value="{{ $vehicle->id }}">{{ $vehicle->registration_number }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Maintenance Date *</label>
                    <input type="date" name="maintenance_date" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" value="{{ date('Y-m-d') }}" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Maintenance Type *</label>
                    <select name="maintenance_type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
                        <option value="servicing">Servicing</option>
                        <option value="repair">Repair</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Status *</label>
                    <select name="status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
                        <option value="waiting">Waiting Approval</option>
                        <option value="dispatched">Dispatched</option>
                    </select>
                </div>
            </div>
            <button type="submit" class="w-full inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">Create Record</button>
        </form>
    </div>
</div>
@endsection
