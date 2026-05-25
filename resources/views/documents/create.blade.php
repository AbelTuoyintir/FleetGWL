@extends('layouts.app')
@section('title', 'Register Document - GWL')
@section('content')
<div class="max-w-2xl mx-auto space-y-6 text-sm">
    <!-- Header -->
    <div class="flex items-center gap-3">
        <a href="{{ route('vehicles.documents.index') }}" class="text-gray-500 hover:text-gray-700">
            <i class="fas fa-arrow-left"></i>
        </a>
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Register New Document</h1>
            <p class="text-gray-500 text-xs mt-0.5">Upload and link documents to fleet units</p>
        </div>
    </div>

    <!-- Form Card -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
        <form action="{{ route('vehicles.documents.store') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
            @csrf

            <div class="space-y-4">
                <!-- Vehicle -->
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Target Vehicle *</label>
                    <select name="vehicle_id" required class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none transition text-sm appearance-none">
                        <option value="" disabled selected>Select Fleet Unit...</option>
                        @foreach($vehicles as $vehicle)
                            <option value="{{ $vehicle->id }}" {{ old('vehicle_id') == $vehicle->id ? 'selected' : '' }}>{{ $vehicle->registration_number }} - {{ $vehicle->make }} {{ $vehicle->model }}</option>
                        @endforeach
                    </select>
                    @error('vehicle_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <!-- Title -->
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Document Base Title *</label>
                    <input type="text" name="title" value="{{ old('title') }}" required placeholder="e.g. Insurance Certificate 2026" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none transition text-sm">
                    @error('title') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                
                <!-- Type and Ref -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Document Type *</label>
                        <select name="document_type" required class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none transition text-sm appearance-none">
                            @foreach($documentTypes as $type)
                                <option value="{{ $type }}" {{ old('document_type') == $type ? 'selected' : '' }}>{{ ucfirst(str_replace('_', ' ', $type)) }}</option>
                            @endforeach
                        </select>
                        @error('document_type') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Ref Number</label>
                        <input type="text" name="reference_number" value="{{ old('reference_number') }}" placeholder="Optional reference details" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none transition text-sm">
                        @error('reference_number') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>
                
                <!-- Dates -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Issue Date</label>
                        <input type="date" name="issue_date" value="{{ old('issue_date') }}" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none transition text-sm">
                        @error('issue_date') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Expiry Date</label>
                        <input type="date" name="expiry_date" value="{{ old('expiry_date') }}" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none transition text-sm">
                        @error('expiry_date') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>
                
                <!-- File upload (Multiple) -->
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Select File(s) *</label>
                    <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-slate-200 border-dashed rounded-2xl hover:border-blue-400 transition cursor-pointer bg-slate-50/50 group" onclick="document.getElementById('fileInput').click()">
                        <div class="space-y-1 text-center">
                            <i class="fas fa-cloud-upload-alt text-2xl text-slate-400 group-hover:text-blue-500 transition mb-2"></i>
                            <div class="flex text-sm text-slate-600 justify-center">
                                <span class="relative cursor-pointer font-semibold text-blue-600 hover:text-blue-500">Upload documents</span>
                                <p class="pl-1">or drag and drop</p>
                            </div>
                            <p class="text-xs text-slate-400">PDF, PNG, JPG up to 10MB (Multiple uploads supported)</p>
                            <input id="fileInput" name="files[]" type="file" class="sr-only" required multiple onchange="updateFileNames(this)">
                            <p id="fileNameDisplay" class="text-xs font-medium text-blue-600 mt-2 hidden"></p>
                        </div>
                    </div>
                    @error('files') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    @error('files.*') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                
                <!-- Description -->
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Description / Notes</label>
                    <textarea name="description" rows="3" placeholder="Additional details..." class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none transition text-sm">{{ old('description') }}</textarea>
                    @error('description') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            <!-- Submit Section -->
            <div class="flex justify-end gap-3 border-t border-slate-50 pt-4">
                <a href="{{ route('vehicles.documents.index') }}" class="px-6 py-2.5 bg-white text-slate-600 border border-slate-200 rounded-xl font-bold hover:bg-slate-50 transition text-center">
                    Cancel
                </a>
                <button type="submit" class="px-6 py-2.5 bg-blue-600 text-white rounded-xl font-bold shadow-lg shadow-blue-500/20 hover:bg-blue-700 transition">
                    Register Document(s)
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function updateFileNames(input) {
        const display = document.getElementById('fileNameDisplay');
        if (input.files && input.files.length > 0) {
            if (input.files.length === 1) {
                display.textContent = input.files[0].name;
            } else {
                display.textContent = input.files.length + ' files selected';
            }
            display.classList.remove('hidden');
        } else {
            display.classList.add('hidden');
        }
    }
</script>
@endsection
