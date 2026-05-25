@extends('layouts.app')
@section('title', 'Document Hub - GWL')
@section('content')
<style>
    .document-card {
        transition: all 0.2s ease;
        border: 1px solid rgba(226, 232, 240, 0.8);
    }
    .document-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 20px -8px rgba(0, 0, 0, 0.08);
        border-color: rgba(59, 130, 246, 0.3);
    }
    .status-active { background: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0; }
    .status-expired { background: #fef2f2; color: #991b1b; border: 1px solid #fca5a5; }
    .status-archived { background: #f1f5f9; color: #334155; border: 1px solid #cbd5e1; }
    .status-draft { background: #fef9c3; color: #854d0e; border: 1px solid #fef08a; }
</style>

<div class="space-y-6 text-sm">
    <!-- Header -->
    <div class="flex flex-wrap justify-between items-center gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Document Management Hub</h1>
            <p class="text-gray-500 text-xs mt-0.5">Manage licenses, road worthiness, insurance, and compliance documents</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('vehicles.documents.expiring') }}" class="px-4 py-2 border border-yellow-300 text-yellow-700 bg-yellow-50 rounded-xl text-xs font-semibold hover:bg-yellow-100 transition flex items-center gap-2">
                <i class="fas fa-bell"></i> Expiring Soon
            </a>
            <a href="{{ route('vehicles.documents.trashed') }}" class="px-4 py-2 border border-slate-300 text-slate-700 bg-slate-50 rounded-xl text-xs font-semibold hover:bg-slate-100 transition flex items-center gap-2">
                <i class="fas fa-trash-alt"></i> Trashed
            </a>
            <button onclick="showUploadModal()" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold shadow-lg shadow-blue-500/20 transition flex items-center gap-2">
                <i class="fas fa-upload"></i> Upload Documents
            </button>
        </div>
    </div>

    <!-- Quick Stats -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="glass-card rounded-2xl p-4 flex items-center justify-between shadow-sm">
            <div>
                <p class="text-gray-500 text-xs uppercase tracking-wide">Total Documents</p>
                <p class="text-2xl font-bold text-slate-800">{{ $documents->total() }}</p>
            </div>
            <div class="w-10 h-10 bg-blue-50 text-blue-600 rounded-full flex items-center justify-center">
                <i class="fas fa-file-alt text-lg"></i>
            </div>
        </div>
        <div class="glass-card rounded-2xl p-4 flex items-center justify-between shadow-sm">
            <div>
                <p class="text-gray-500 text-xs uppercase tracking-wide">Active Policies</p>
                <p class="text-2xl font-bold text-green-700">{{ \App\Models\Document::where('status', 'active')->count() }}</p>
            </div>
            <div class="w-10 h-10 bg-green-50 text-green-600 rounded-full flex items-center justify-center">
                <i class="fas fa-check-circle text-lg"></i>
            </div>
        </div>
        <div class="glass-card rounded-2xl p-4 flex items-center justify-between shadow-sm">
            <div>
                <p class="text-gray-500 text-xs uppercase tracking-wide">Expired / Warning</p>
                <p class="text-2xl font-bold text-red-700">{{ \App\Models\Document::where('is_expired', true)->orWhere('expiry_date', '<', now())->count() }}</p>
            </div>
            <div class="w-10 h-10 bg-red-50 text-red-600 rounded-full flex items-center justify-center">
                <i class="fas fa-exclamation-triangle text-lg"></i>
            </div>
        </div>
        <div class="glass-card rounded-2xl p-4 flex items-center justify-between shadow-sm">
            <div>
                <p class="text-gray-500 text-xs uppercase tracking-wide">Pending Review</p>
                <p class="text-2xl font-bold text-amber-700">{{ \App\Models\Document::where('requires_acknowledgement', true)->whereNull('acknowledged_at')->count() }}</p>
            </div>
            <div class="w-10 h-10 bg-amber-50 text-amber-600 rounded-full flex items-center justify-center">
                <i class="fas fa-eye-slash text-lg"></i>
            </div>
        </div>
    </div>

    <!-- Filters & Search -->
    <div class="bg-white rounded-2xl shadow-sm p-4 border border-slate-100">
        <form method="GET" action="{{ route('vehicles.documents.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-3">
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Search Keywords</label>
                <div class="relative">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Title, ref number, etc..." class="w-full pl-9 pr-4 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none transition text-xs">
                    <i class="fas fa-search absolute left-3.5 top-3 text-slate-400 text-xs"></i>
                </div>
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Document Type</label>
                <select name="type" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none transition text-xs appearance-none">
                    <option value="all">All Types</option>
                    @foreach($documentTypes as $type)
                        <option value="{{ $type }}" {{ request('type') == $type ? 'selected' : '' }}>{{ ucfirst(str_replace('_', ' ', $type)) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Status</label>
                <select name="status" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none transition text-xs appearance-none">
                    <option value="all">All Statuses</option>
                    @foreach($statuses as $status)
                        <option value="{{ $status }}" {{ request('status') == $status ? 'selected' : '' }}>{{ ucfirst($status) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-end gap-2">
                <button type="submit" class="flex-1 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl font-semibold transition text-xs flex justify-center items-center gap-1.5">
                    <i class="fas fa-filter"></i> Apply Filters
                </button>
                @if(request()->anyFilled(['search', 'type', 'status']))
                    <a href="{{ route('vehicles.documents.index') }}" class="px-3 py-2 bg-red-50 hover:bg-red-100 text-red-600 rounded-xl font-semibold transition text-xs flex justify-center items-center">
                        <i class="fas fa-undo"></i>
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Documents Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        @forelse($documents as $doc)
            <?php
                $expiryDate = $doc->expiry_date ? \Carbon\Carbon::parse($doc->expiry_date) : null;
                $isExpiring = $expiryDate && $expiryDate->isFuture() && $expiryDate->diffInDays(now()) <= 30;
                $isExpired = $expiryDate && $expiryDate->isPast() || $doc->is_expired;
            ?>
            <div class="document-card bg-white rounded-2xl p-4 flex flex-col justify-between shadow-sm relative overflow-hidden">
                @if($isExpired)
                    <div class="absolute top-0 left-0 right-0 h-1 bg-red-500"></div>
                @elseif($isExpiring)
                    <div class="absolute top-0 left-0 right-0 h-1 bg-yellow-500"></div>
                @else
                    <div class="absolute top-0 left-0 right-0 h-1 bg-blue-500"></div>
                @endif
                
                <div>
                    <!-- Title and Type Icon -->
                    <div class="flex justify-between items-start gap-2 mb-3">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl flex items-center justify-center
                                {{ $doc->document_type == 'insurance' ? 'bg-blue-100 text-blue-600' : 
                                   ($doc->document_type == 'registration' ? 'bg-green-100 text-green-600' : 'bg-slate-100 text-slate-600') }}">
                                <i class="fas {{ $doc->document_type == 'insurance' ? 'fa-file-invoice' : 
                                    ($doc->document_type == 'registration' ? 'fa-id-card' : 'fa-file') }} text-base"></i>
                            </div>
                            <div class="overflow-hidden">
                                <h3 class="font-bold text-gray-800 truncate" title="{{ $doc->title }}">{{ $doc->title }}</h3>
                                <p class="text-[10px] text-gray-500 uppercase font-semibold tracking-wide">{{ str_replace('_', ' ', $doc->document_type) }}</p>
                            </div>
                        </div>
                        <span class="text-[10px] px-2 py-0.5 rounded-full font-bold uppercase shadow-sm
                            {{ $doc->status == 'active' ? 'status-active' : 
                               ($doc->status == 'expired' ? 'status-expired' : 
                               ($doc->status == 'archived' ? 'status-archived' : 'status-draft')) }}">
                            {{ $doc->status }}
                        </span>
                    </div>

                    <!-- Details -->
                    <div class="space-y-1.5 border-t border-slate-50 pt-2.5 text-xs">
                        <div class="flex justify-between text-gray-500">
                            <span>Vehicle:</span>
                            @if($doc->vehicle)
                                <a href="{{ route('vehicles.show', $doc->vehicle->id) }}" class="font-semibold text-blue-600 hover:underline">
                                    {{ $doc->vehicle->registration_number }}
                                </a>
                            @else
                                <span class="text-gray-400">Global / General</span>
                            @endif
                        </div>
                        @if($doc->reference_number)
                            <div class="flex justify-between text-gray-500">
                                <span>Reference No:</span>
                                <span class="font-medium text-gray-800">{{ $doc->reference_number }}</span>
                            </div>
                        @endif
                        <div class="flex justify-between text-gray-500">
                            <span>Issue Date:</span>
                            <span class="text-gray-700">{{ $doc->issue_date ? $doc->issue_date->format('M j, Y') : 'N/A' }}</span>
                        </div>
                        <div class="flex justify-between text-gray-500">
                            <span>Expiry Date:</span>
                            <span class="{{ $isExpired ? 'text-red-600 font-bold' : ($isExpiring ? 'text-yellow-600 font-semibold' : 'text-gray-700') }}">
                                {{ $doc->expiry_date ? $doc->expiry_date->format('M j, Y') : 'N/A' }}
                            </span>
                        </div>
                        @if($doc->description)
                            <div class="text-[11px] text-gray-500 mt-2 line-clamp-2 bg-slate-50 p-2 rounded-lg italic">
                                "{{ $doc->description }}"
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Footer Actions -->
                <div class="mt-4 flex gap-2 border-t border-slate-100 pt-3">
                    <a href="{{ route('vehicles.documents.preview', $doc->id) }}" target="_blank" class="flex-1 py-1.5 bg-blue-50 text-blue-600 rounded-xl font-semibold hover:bg-blue-100 transition text-center text-xs flex justify-center items-center gap-1">
                        <i class="fas fa-eye text-[10px]"></i> View File
                    </a>
                    <a href="{{ route('vehicles.documents.download', $doc->id) }}" class="px-3 py-1.5 border border-slate-200 text-slate-600 rounded-xl hover:bg-slate-50 transition text-center text-xs flex justify-center items-center">
                        <i class="fas fa-download"></i>
                    </a>
                    <button onclick="confirmDelete({{ $doc->id }})" class="px-3 py-1.5 border border-red-200 text-red-500 rounded-xl hover:bg-red-50 hover:text-red-600 transition flex justify-center items-center">
                        <i class="fas fa-trash-alt"></i>
                    </button>
                </div>
            </div>
        @empty
            <div class="col-span-3 text-center py-16 bg-white border border-slate-100 rounded-2xl shadow-sm text-gray-500">
                <i class="fas fa-folder-open text-5xl mb-3 text-slate-300"></i>
                <p class="font-bold text-slate-700 text-base">No Documents Found</p>
                <p class="text-xs text-slate-400 mt-1">Try resetting filters or upload a new fleet document.</p>
                <button onclick="showUploadModal()" class="mt-4 px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold shadow-lg shadow-blue-500/20 transition">
                    <i class="fas fa-upload mr-1"></i> Upload Document
                </button>
            </div>
        @endforelse
    </div>

    <!-- Pagination -->
    <div class="mt-6">
        {{ $documents->appends(request()->query())->links() }}
    </div>
</div>

<!-- Upload Document Modal -->
<div id="uploadDocumentModal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" aria-hidden="true" onclick="hideUploadModal()"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full border border-slate-200">
            <div class="absolute top-4 right-4">
                <button onclick="hideUploadModal()" class="text-slate-400 hover:text-slate-600 transition">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form id="uploadDocumentForm" enctype="multipart/form-data">
                @csrf
                <div class="bg-white px-6 pt-6 pb-6">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="w-10 h-10 bg-blue-50 text-blue-600 rounded-full flex items-center justify-center">
                            <i class="fas fa-file-upload text-lg"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-slate-800" id="modal-title">Upload Documents</h3>
                            <p class="text-xs text-slate-500">Register new fleet documents or certificates</p>
                        </div>
                    </div>
                    
                    <div class="space-y-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Target Vehicle *</label>
                            <select name="vehicle_id" required class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none transition text-sm appearance-none">
                                <option value="" disabled selected>Select Fleet Unit...</option>
                                @foreach($vehicles as $vehicle)
                                    <option value="{{ $vehicle->id }}">{{ $vehicle->registration_number }} - {{ $vehicle->make }} {{ $vehicle->model }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Document Base Title</label>
                            <input type="text" name="title" required placeholder="e.g. Insurance Certificate 2026" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none transition text-sm">
                        </div>
                        
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Document Type</label>
                                <select name="document_type" required class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none transition text-sm appearance-none">
                                    @foreach($documentTypes as $type)
                                        <option value="{{ $type }}">{{ ucfirst(str_replace('_', ' ', $type)) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Ref Number</label>
                                <input type="text" name="reference_number" placeholder="Optional" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none transition text-sm">
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Issue Date</label>
                                <input type="date" name="issue_date" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none transition text-sm">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Expiry Date</label>
                                <input type="date" name="expiry_date" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none transition text-sm">
                            </div>
                        </div>
                        
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Select File(s) *</label>
                            <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-slate-200 border-dashed rounded-2xl hover:border-blue-400 transition cursor-pointer bg-slate-50/50 group" onclick="document.getElementById('fileInput').click()">
                                <div class="space-y-1 text-center">
                                    <i class="fas fa-cloud-upload-alt text-2xl text-slate-400 group-hover:text-blue-500 transition mb-2"></i>
                                    <div class="flex text-sm text-slate-600">
                                        <span class="relative cursor-pointer font-semibold text-blue-600 hover:text-blue-500">Upload documents</span>
                                        <p class="pl-1">or drag and drop</p>
                                    </div>
                                    <p class="text-xs text-slate-400">PDF, PNG, JPG up to 10MB (Multiple allowed)</p>
                                    <input id="fileInput" name="files[]" type="file" class="sr-only" required multiple onchange="updateFileNames(this)">
                                    <p id="fileNameDisplay" class="text-xs font-medium text-blue-600 mt-2 hidden"></p>
                                </div>
                            </div>
                        </div>
                        
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Description</label>
                            <textarea name="description" rows="2" placeholder="Additional details..." class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none transition text-sm"></textarea>
                        </div>
                    </div>
                </div>
                <div class="bg-slate-50 px-6 py-4 flex flex-row-reverse gap-3 border-t border-slate-100">
                    <button type="submit" id="submitUpload" class="px-6 py-2.5 bg-blue-600 text-white rounded-xl text-sm font-bold shadow-lg shadow-blue-500/20 hover:bg-blue-700 transition flex items-center gap-2">
                        <span>Start Upload</span>
                        <i class="fas fa-arrow-right text-xs"></i>
                    </button>
                    <button type="button" onclick="hideUploadModal()" class="px-6 py-2.5 bg-white text-slate-600 border border-slate-200 rounded-xl text-sm font-bold hover:bg-slate-50 transition">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function showUploadModal() {
        $('#uploadDocumentModal').removeClass('hidden');
        $('body').addClass('overflow-hidden');
    }

    function hideUploadModal() {
        $('#uploadDocumentModal').addClass('hidden');
        $('body').removeClass('overflow-hidden');
        $('#uploadDocumentForm')[0].reset();
        $('#fileNameDisplay').addClass('hidden');
    }

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

    $(document).ready(function() {
        $('#uploadDocumentForm').on('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const submitBtn = $('#submitUpload');
            const originalBtnText = submitBtn.html();
            
            submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Uploading...');
            
            $.ajax({
                url: "{{ route('vehicles.documents.store') }}",
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    if (response.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Uploaded!',
                            text: response.message,
                            showConfirmButton: false,
                            timer: 1500
                        });
                        setTimeout(() => {
                            window.location.reload();
                        }, 1500);
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: response.message
                        });
                        submitBtn.prop('disabled', false).html(originalBtnText);
                    }
                },
                error: function(xhr) {
                    let errorMessage = 'Something went wrong while uploading.';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    }
                    Swal.fire({
                        icon: 'error',
                        title: 'Upload Failed',
                        text: errorMessage
                    });
                    submitBtn.prop('disabled', false).html(originalBtnText);
                }
            });
        });
    });

    function confirmDelete(id) {
        Swal.fire({
            title: 'Are you sure?',
            text: "This document will be moved to trash.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#475569',
            confirmButtonText: 'Yes, delete it'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: `/vehicles/documents/${id}`,
                    method: 'POST',
                    data: {
                        _method: 'DELETE',
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Deleted!',
                            text: 'Document has been trashed.',
                            showConfirmButton: false,
                            timer: 1200
                        });
                        setTimeout(() => {
                            window.location.reload();
                        }, 1200);
                    },
                    error: function() {
                        Swal.fire('Error', 'Failed to delete the document.', 'error');
                    }
                });
            }
        });
    }
</script>
@endsection
