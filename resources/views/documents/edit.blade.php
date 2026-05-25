@extends('layouts.app')
@section('title', 'Edit Document - GWL')
@section('content')
<div class="max-w-3xl mx-auto space-y-6 text-sm">
    <!-- Header -->
    <div class="flex items-center gap-3">
        <a href="{{ route('vehicles.documents.show', $document->id) }}" class="text-gray-500 hover:text-gray-700">
            <i class="fas fa-arrow-left"></i>
        </a>
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Edit Document Details</h1>
            <p class="text-gray-500 text-xs mt-0.5">Modify properties or upload a new version of document #{{ $document->id }}</p>
        </div>
    </div>

    <!-- Edit Form Card -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
        <form action="{{ route('vehicles.documents.update', $document->id) }}" method="POST" enctype="multipart/form-data" class="space-y-6">
            @csrf
            @method('PUT')

            <div class="space-y-4">
                <!-- Title -->
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Document Title *</label>
                    <input type="text" name="title" value="{{ old('title', $document->title) }}" required placeholder="e.g. Insurance Certificate 2026" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none transition text-sm">
                    @error('title') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <!-- Vehicle and Type -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Linked Vehicle</label>
                        <select name="vehicle_id" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none transition text-sm appearance-none">
                            <option value="">General/No Vehicle</option>
                            @foreach($vehicles as $vehicle)
                                <option value="{{ $vehicle->id }}" {{ old('vehicle_id', $document->vehicle_id) == $vehicle->id ? 'selected' : '' }}>{{ $vehicle->registration_number }} - {{ $vehicle->make }} {{ $vehicle->model }}</option>
                            @endforeach
                        </select>
                        @error('vehicle_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Document Type *</label>
                        <select name="document_type" required class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none transition text-sm appearance-none">
                            @foreach($documentTypes as $type)
                                <option value="{{ $type }}" {{ old('document_type', $document->document_type) == $type ? 'selected' : '' }}>{{ ucfirst(str_replace('_', ' ', $type)) }}</option>
                            @endforeach
                        </select>
                        @error('document_type') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>

                <!-- Reference and Status -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Reference/Policy Number</label>
                        <input type="text" name="reference_number" value="{{ old('reference_number', $document->reference_number) }}" placeholder="Optional reference info" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none transition text-sm">
                        @error('reference_number') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Status *</label>
                        <select name="status" required class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none transition text-sm appearance-none">
                            <option value="active" {{ old('status', $document->status) == 'active' ? 'selected' : '' }}>Active</option>
                            <option value="expired" {{ old('status', $document->status) == 'expired' ? 'selected' : '' }}>Expired</option>
                            <option value="archived" {{ old('status', $document->status) == 'archived' ? 'selected' : '' }}>Archived</option>
                            <option value="draft" {{ old('status', $document->status) == 'draft' ? 'selected' : '' }}>Draft</option>
                        </select>
                        @error('status') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>

                <!-- Dates -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Issue Date</label>
                        <input type="date" name="issue_date" value="{{ old('issue_date', $document->issue_date ? $document->issue_date->format('Y-m-d') : '') }}" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none transition text-sm">
                        @error('issue_date') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Expiry Date</label>
                        <input type="date" name="expiry_date" value="{{ old('expiry_date', $document->expiry_date ? $document->expiry_date->format('Y-m-d') : '') }}" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none transition text-sm">
                        @error('expiry_date') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>

                <!-- Upload New Version -->
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Upload New File Version (Optional)</label>
                    <div class="mt-1 flex justify-center px-6 pt-4 pb-5 border-2 border-slate-200 border-dashed rounded-2xl hover:border-blue-400 transition cursor-pointer bg-slate-50/50 group" onclick="document.getElementById('fileInput').click()">
                        <div class="space-y-1 text-center">
                            <i class="fas fa-cloud-upload-alt text-xl text-slate-400 group-hover:text-blue-500 transition mb-2"></i>
                            <div class="flex text-xs text-slate-600 justify-center">
                                <span class="relative cursor-pointer font-semibold text-blue-600 hover:text-blue-500">Upload replacement file</span>
                            </div>
                            <p class="text-[10px] text-slate-400">Leaves file unchanged if blank. Version tracker will increment by 1.</p>
                            <input id="fileInput" name="file" type="file" class="sr-only" onchange="updateFileName(this)">
                            <p id="fileNameDisplay" class="text-xs font-medium text-blue-600 mt-2 {{ $document->file_name ? '' : 'hidden' }}">{{ $document->file_name ?? '' }}</p>
                        </div>
                    </div>
                    @error('file') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <!-- Settings -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 bg-slate-50/80 p-3.5 rounded-xl border border-slate-100">
                    <div class="flex items-center gap-2.5">
                        <input type="hidden" name="is_public" value="0">
                        <input type="checkbox" name="is_public" id="is_public" value="1" {{ old('is_public', $document->is_public) ? 'checked' : '' }} class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500">
                        <label for="is_public" class="text-xs font-semibold text-gray-700">Make Publicly Viewable</label>
                    </div>
                    <div class="flex items-center gap-2.5">
                        <input type="hidden" name="requires_acknowledgement" value="0">
                        <input type="checkbox" name="requires_acknowledgement" id="requires_acknowledgement" value="1" {{ old('requires_acknowledgement', $document->requires_acknowledgement) ? 'checked' : '' }} class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500">
                        <label for="requires_acknowledgement" class="text-xs font-semibold text-gray-700">Requires Signature/Acknowledge</label>
                    </div>
                </div>

                <!-- Description -->
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Description / Notes</label>
                    <textarea name="description" rows="3" placeholder="Additional details..." class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none transition text-sm">{{ old('description', $document->description) }}</textarea>
                    @error('description') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            <!-- Submit Section -->
            <div class="flex justify-end gap-3 border-t border-slate-50 pt-4">
                <a href="{{ route('vehicles.documents.show', $document->id) }}" class="px-6 py-2.5 bg-white text-slate-600 border border-slate-200 rounded-xl font-bold hover:bg-slate-50 transition text-center">
                    Cancel
                </a>
                <button type="submit" class="px-6 py-2.5 bg-blue-600 text-white rounded-xl font-bold shadow-lg shadow-blue-500/20 hover:bg-blue-700 transition">
                    Save Changes
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function updateFileName(input) {
        const display = document.getElementById('fileNameDisplay');
        if (input.files && input.files[0]) {
            display.textContent = input.files[0].name;
            display.classList.remove('hidden');
        } else {
            display.textContent = "{{ $document->file_name }}";
        }
    }
</script>
@endsection
