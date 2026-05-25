{{-- resources/views/documents/show.blade.php --}}
@extends('layouts.app')
@section('title', $document->title)
@section('content')

<div class="min-h-screen bg-gray-50 py-6">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <!-- Header -->
        <div class="mb-6">
            <div class="flex items-center gap-3 mb-2">
                <a href="{{ route('documents.index') }}" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-arrow-left"></i>
                </a>
                <h1 class="text-2xl font-bold text-gray-800">{{ $document->title }}</h1>
            </div>
            <div class="flex gap-2">
                @php $typeInfo = $documentTypes[$document->document_type] ?? $documentTypes['other']; @endphp
                <span class="px-2 py-1 rounded-full text-xs {{ $typeInfo['bg'] }} {{ $typeInfo['text'] }}">
                    <i class="fas {{ $typeInfo['icon'] }} mr-1"></i>{{ $typeInfo['name'] }}
                </span>
                <span class="status-badge status-{{ $document->status }}">
                    <i class="fas {{ $document->status == 'active' ? 'fa-check-circle' : ($document->status == 'expired' ? 'fa-times-circle' : 'fa-archive') }}"></i>
                    {{ ucfirst($document->status) }}
                </span>
                @if($document->is_expired)
                    <span class="status-badge status-expired">
                        <i class="fas fa-exclamation-triangle"></i> Expired
                    </span>
                @endif
            </div>
        </div>
        
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Document Preview -->
            <div class="lg:col-span-2 bg-white rounded-xl shadow-sm p-6">
                <div class="border rounded-lg p-8 text-center">
                    @php
                        $fileExtension = strtolower($document->extension);
                        $isImage = in_array($fileExtension, ['jpg', 'jpeg', 'png', 'gif']);
                        $isPDF = $fileExtension === 'pdf';
                    @endphp
                    
                    @if($isImage)
                        <img src="{{ Storage::url($document->file_path) }}" alt="{{ $document->title }}" class="max-w-full rounded-lg shadow">
                    @else
                        <i class="fas {{ $isPDF ? 'fa-file-pdf' : 'fa-file-alt' }} text-gray-400 text-8xl mb-4"></i>
                        <p class="text-gray-500">Preview not available for this file type</p>
                    @endif
                </div>
                
                <div class="mt-4 flex gap-3">
                    <a href="{{ route('documents.download', $document) }}" class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg text-center hover:bg-blue-700">
                        <i class="fas fa-download mr-2"></i>Download
                    </a>
                    @if($isPDF || $isImage)
                        <a href="{{ Storage::url($document->file_path) }}" target="_blank" class="flex-1 px-4 py-2 bg-gray-100 text-gray-700 rounded-lg text-center hover:bg-gray-200">
                            <i class="fas fa-external-link-alt mr-2"></i>View Full Screen
                        </a>
                    @endif
                    <a href="{{ route('documents.edit', $document) }}" class="px-4 py-2 bg-yellow-500 text-white rounded-lg hover:bg-yellow-600">
                        <i class="fas fa-edit"></i>
                    </a>
                </div>
            </div>
            
            <!-- Document Details -->
            <div class="space-y-6">
                <div class="bg-white rounded-xl shadow-sm p-6">
                    <h3 class="font-semibold text-gray-800 mb-4 pb-2 border-b">Document Details</h3>
                    
                    <div class="space-y-3">
                        <div>
                            <label class="text-xs text-gray-500">Title</label>
                            <p class="font-medium">{{ $document->title }}</p>
                        </div>
                        
                        @if($document->reference_number)
                        <div>
                            <label class="text-xs text-gray-500">Reference Number</label>
                            <p class="font-mono text-sm">{{ $document->reference_number }}</p>
                        </div>
                        @endif
                        
                        @if($document->vehicle)
                        <div>
                            <label class="text-xs text-gray-500">Vehicle</label>
                            <p class="font-medium">{{ $document->vehicle->registration_number }} - {{ $document->vehicle->make }} {{ $document->vehicle->model }}</p>
                        </div>
                        @endif
                        
                        @if($document->issue_date)
                        <div>
                            <label class="text-xs text-gray-500">Issue Date</label>
                            <p>{{ $document->issue_date->format('F j, Y') }}</p>
                        </div>
                        @endif
                        
                        @if($document->expiry_date)
                        <div>
                            <label class="text-xs text-gray-500">Expiry Date</label>
                            <p class="{{ $document->is_expired ? 'text-red-600 font-semibold' : (now()->diffInDays($document->expiry_date) <= 30 ? 'text-yellow-600 font-semibold' : '') }}">
                                {{ $document->expiry_date->format('F j, Y') }}
                                @if(!$document->is_expired)
                                    ({{ now()->diffInDays($document->expiry_date) }} days left)
                                @endif
                            </p>
                        </div>
                        @endif
                        
                        @if($document->tags)
                        <div>
                            <label class="text-xs text-gray-500">Tags</label>
                            <div class="flex flex-wrap gap-1 mt-1">
                                @foreach(explode(',', $document->tags) as $tag)
                                    <span class="px-2 py-0.5 bg-gray-100 text-gray-600 rounded-full text-xs">{{ trim($tag) }}</span>
                                @endforeach
                            </div>
                        </div>
                        @endif
                        
                        @if($document->description)
                        <div>
                            <label class="text-xs text-gray-500">Description</label>
                            <p class="text-sm">{{ $document->description }}</p>
                        </div>
                        @endif
                        
                        <div>
                            <label class="text-xs text-gray-500">File Info</label>
                            <p class="text-sm">{{ $document->file_name }} ({{ $document->formatted_file_size }})</p>
                        </div>
                        
                        @if($document->requires_acknowledgement)
                        <div class="mt-4 pt-3 border-t">
                            <div class="flex items-center justify-between">
                                <span class="text-sm font-medium">Acknowledgement Status</span>
                                @if($document->acknowledged_at)
                                    <span class="text-green-600 text-sm">✓ Acknowledged</span>
                                @else
                                    <button onclick="acknowledgeDocument({{ $document->id }})" class="px-3 py-1 bg-purple-600 text-white rounded-lg text-sm hover:bg-purple-700">
                                        <i class="fas fa-signature mr-1"></i>Acknowledge
                                    </button>
                                @endif
                            </div>
                            @if($document->acknowledged_at)
                                <p class="text-xs text-gray-500 mt-1">
                                    Acknowledged by {{ $document->acknowledgedBy->name ?? 'Unknown' }} on {{ $document->acknowledged_at->format('F j, Y g:i A') }}
                                </p>
                            @endif
                        </div>
                        @endif
                    </div>
                </div>
                
                <!-- Version Info -->
                @if($document->version > 1)
                <div class="bg-white rounded-xl shadow-sm p-6">
                    <h3 class="font-semibold text-gray-800 mb-3">Version History</h3>
                    <p class="text-sm">Version {{ $document->version }} (Current)</p>
                    @if($document->previous_version_id)
                        <a href="{{ route('documents.show', $document->previous_version_id) }}" class="text-blue-600 text-sm">View previous version</a>
                    @endif
                </div>
                @endif
                
                <!-- Actions -->
                <div class="bg-white rounded-xl shadow-sm p-6">
                    <h3 class="font-semibold text-gray-800 mb-3">Actions</h3>
                    <div class="space-y-2">
                        <a href="{{ route('documents.download', $document) }}" class="block w-full text-center px-4 py-2 border border-blue-600 text-blue-600 rounded-lg hover:bg-blue-50">
                            <i class="fas fa-download mr-2"></i>Download
                        </a>
                        <a href="{{ route('documents.edit', $document) }}" class="block w-full text-center px-4 py-2 border border-yellow-600 text-yellow-600 rounded-lg hover:bg-yellow-50">
                            <i class="fas fa-edit mr-2"></i>Edit
                        </a>
                        <form action="{{ route('documents.destroy', $document) }}" method="POST" class="delete-form">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="w-full text-center px-4 py-2 border border-red-600 text-red-600 rounded-lg hover:bg-red-50">
                                <i class="fas fa-trash mr-2"></i>Delete
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
function acknowledgeDocument(id) {
    Swal.fire({
        title: 'Acknowledge Document',
        text: 'By acknowledging, you confirm that you have read and understood this document.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        confirmButtonText: 'Yes, I acknowledge'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: `/documents/${id}/acknowledge`,
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                success: function() {
                    Swal.fire('Acknowledged!', 'Document acknowledged successfully.', 'success');
                    location.reload();
                },
                error: function() {
                    Swal.fire('Error', 'Failed to acknowledge document', 'error');
                }
            });
        }
    });
}

$('.delete-form').on('submit', function(e) {
    e.preventDefault();
    const form = this;
    Swal.fire({
        title: 'Delete Document?',
        text: 'This action cannot be undone.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        confirmButtonText: 'Yes, delete'
    }).then((result) => {
        if (result.isConfirmed) form.submit();
    });
});
</script>

@endsection