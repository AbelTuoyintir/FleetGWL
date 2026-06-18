@extends('layouts.app')
@section('title', 'Edit Maintenance Record')
@section('content')
<div class="min-h-screen bg-gray-50 py-6">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <form action="{{ route('maintenance.update', $maintenance->id) }}" method="POST" class="p-8 space-y-6 bg-white rounded-2xl shadow-sm">
            @csrf
            @method('PUT')
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Status</label>
                    <select name="status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                        <option value="waiting" {{ $maintenance->status == 'waiting' ? 'selected' : '' }}>Waiting Approval</option>
                        <option value="dispatched" {{ $maintenance->status == 'dispatched' ? 'selected' : '' }}>Dispatched</option>
                        <option value="completed" {{ $maintenance->status == 'completed' ? 'selected' : '' }}>Completed</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Priority</label>
                    <select name="priority" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                        <option value="low" {{ $maintenance->priority == 'low' ? 'selected' : '' }}>Low</option>
                        <option value="medium" {{ $maintenance->priority == 'medium' ? 'selected' : '' }}>Medium</option>
                        <option value="high" {{ $maintenance->priority == 'high' ? 'selected' : '' }}>High</option>
                        <option value="urgent" {{ $maintenance->priority == 'urgent' ? 'selected' : '' }}>Urgent</option>
                    </select>
                </div>
            </div>
            <button type="submit" class="w-full inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">Update Record</button>
        </form>
    </div>
</div>
@endsection
