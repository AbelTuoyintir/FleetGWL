@extends('layouts.app')
@section('title', $document->title . ' - Details')
@section('content')
<div class="max-w-5xl mx-auto space-y-6 text-sm">
    <!-- Header with Back Button & Actions -->
    <div class="flex justify-between items-center flex-wrap gap-4">
        <div class="flex items-center gap-3">
            <a href="{{ route('vehicles.documents.index') }}" class="text-gray-500 hover:text-gray-700">
                <i class="fas fa-arrow-left"></i>
            </a>
            <div>
                <h1 class="text-2xl font-bold text-gray-800">{{ $document->title }}</h1>
                <p class="text-gray-500 text-xs mt-0.5">Linked to vehicle: 
                    @if($document->vehicle)
                        <a href="{{ route('vehicles.show', $document->vehicle->id) }}" class="text-blue-600 hover:underline font-semibold">
                            {{ $document->vehicle->registration_number }}
                        </a>
                    @else
                        <span class="text-gray-400">N/A</span>
                    @endif
                </p>
            </div>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('vehicles.documents.download', $document->id) }}" class="px-4 py-2 bg-blue-600 text-white rounded-xl text-xs font-bold hover:bg-blue-700 transition shadow-lg shadow-blue-500/10 flex items-center gap-2">
                <i class="fas fa-download"></i> Download Original
            </a>
            <a href="{{ route('vehicles.documents.edit', $document->id) }}" class="px-4 py-2 border border-slate-300 text-slate-700 bg-white rounded-xl text-xs font-semibold hover:bg-slate-50 transition flex items-center gap-2">
                <i class="fas fa-edit"></i> Edit Details
            </a>
        </div>
    </div>

    <!-- Main Content Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Document Information Card -->
        <div class="lg:col-span-1 space-y-6">
            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-5 space-y-4">
                <h3 class="font-bold text-gray-800 border-b border-slate-50 pb-2">Document Details</h3>
                
                <div class="space-y-3">
                    <div>
                        <span class="block text-gray-400 text-xs font-semibold uppercase tracking-wider">Document Type</span>
                        <span class="font-semibold text-slate-800 text-sm mt-0.5 block">{{ ucfirst(str_replace('_', ' ', $document->document_type)) }}</span>
                    </div>
                    @if($document->reference_number)
                    <div>
                        <span class="block text-gray-400 text-xs font-semibold uppercase tracking-wider">Reference/Policy No</span>
                        <span class="font-semibold text-slate-800 text-sm mt-0.5 block">{{ $document->reference_number }}</span>
                    </div>
                    @endif
                    <div>
                        <span class="block text-gray-400 text-xs font-semibold uppercase tracking-wider">Status</span>
                        <span class="inline-block px-2.5 py-0.5 rounded-full text-xs font-bold mt-1 uppercase
                            {{ $document->status == 'active' ? 'bg-green-50 text-green-700 border border-green-200' : 
                               ($document->status == 'expired' ? 'bg-red-50 text-red-700 border border-red-200' : 
                               ($document->status == 'archived' ? 'bg-slate-50 text-slate-700 border border-slate-200' : 'bg-yellow-50 text-yellow-700 border border-yellow-200')) }}">
                            {{ $document->status }}
                        </span>
                    </div>
                    <div class="grid grid-cols-2 gap-2 pt-2">
                        <div>
                            <span class="block text-gray-400 text-xs font-semibold uppercase tracking-wider">Issue Date</span>
                            <span class="text-slate-800 font-medium block mt-0.5">{{ $document->issue_date ? $document->issue_date->format('M j, Y') : 'N/A' }}</span>
                        </div>
                        <div>
                            <span class="block text-gray-400 text-xs font-semibold uppercase tracking-wider">Expiry Date</span>
                            <span class="text-slate-800 font-medium block mt-0.5 {{ $document->is_expired || ($document->expiry_date && $document->expiry_date->isPast()) ? 'text-red-600 font-bold' : '' }}">
                                {{ $document->expiry_date ? $document->expiry_date->format('M j, Y') : 'N/A' }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Metadata & Acknowledgement -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-5 space-y-4">
                <h3 class="font-bold text-gray-800 border-b border-slate-50 pb-2">Acknowledge & Sign-off</h3>
                
                @if($document->requires_acknowledgement)
                    @if($document->acknowledged_at)
                        <div class="bg-green-50 border border-green-100 rounded-xl p-3 flex gap-3 items-start">
                            <i class="fas fa-check-circle text-green-600 text-lg mt-0.5"></i>
                            <div>
                                <p class="font-bold text-green-800">Acknowledged</p>
                                <p class="text-xs text-green-600">By: {{ $document->acknowledgedBy->name ?? 'System Admin' }}</p>
                                <p class="text-[10px] text-green-500">{{ $document->acknowledged_at->format('M j, Y \a\t g:i A') }}</p>
                            </div>
                        </div>
                    @else
                        <div class="bg-amber-50 border border-amber-100 rounded-xl p-3 flex flex-col gap-3">
                            <div class="flex gap-3 items-start">
                                <i class="fas fa-exclamation-circle text-amber-600 text-lg mt-0.5"></i>
                                <div>
                                    <p class="font-bold text-amber-800">Requires Signature</p>
                                    <p class="text-xs text-amber-600">This document requires formal acknowledgement of receipt/review.</p>
                                </div>
                            </div>
                            <form action="{{ route('vehicles.documents.acknowledge', $document->id) }}" method="POST">
                                @csrf
                                <button type="submit" class="w-full py-2 bg-amber-500 hover:bg-amber-600 text-white rounded-lg font-bold text-xs shadow-sm transition">
                                    Acknowledge Document
                                </button>
                            </form>
                        </div>
                    @endif
                @else
                    <p class="text-gray-400 text-xs">This document does not require formal acknowledgment.</p>
                @endif

                <div class="border-t border-slate-50 pt-3 text-xs text-gray-500 space-y-1.5">
                    <div class="flex justify-between">
                        <span>Uploaded On:</span>
                        <span>{{ $document->created_at->format('M j, Y H:i') }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span>Total Downloads:</span>
                        <span class="font-semibold text-slate-700">{{ $document->download_count ?? 0 }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span>Document Size:</span>
                        <span>{{ number_format(($document->file_size ?? 0) / 1024, 1) }} KB</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- File Preview Panel -->
        <div class="lg:col-span-2 space-y-4">
            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-5 flex flex-col h-[650px]">
                <h3 class="font-bold text-gray-800 border-b border-slate-50 pb-3 flex items-center justify-between">
                    <span>File Preview</span>
                    <span class="text-xs font-normal text-gray-500 flex items-center gap-1.5">
                        <i class="far fa-file"></i> {{ $document->file_name }}
                    </span>
                </h3>
                
                <div class="flex-1 mt-4 bg-slate-50 rounded-xl overflow-hidden border border-slate-100 flex items-center justify-center">
                    @if(in_array(strtolower($document->extension), ['pdf']))
                        <iframe src="{{ route('vehicles.documents.preview', $document->id) }}" class="w-full h-full border-none" type="application/pdf"></iframe>
                    @elseif(in_array(strtolower($document->extension), ['png', 'jpg', 'jpeg', 'gif', 'svg']))
                        <div class="w-full h-full overflow-auto flex items-center justify-center p-4">
                            <img src="{{ route('vehicles.documents.preview', $document->id) }}" alt="Preview" class="max-w-full max-h-full rounded-lg shadow-sm object-contain">
                        </div>
                    @else
                        <div class="text-center p-8">
                            <i class="fas fa-file-alt text-5xl text-slate-300 mb-3"></i>
                            <p class="font-semibold text-slate-600">No Online Preview Available</p>
                            <p class="text-xs text-slate-400 mt-1">Previews are supported for PDF, PNG, JPG, and GIF files.</p>
                            <a href="{{ route('vehicles.documents.download', $document->id) }}" class="mt-4 inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-xs font-bold transition">
                                <i class="fas fa-download"></i> Download File to View
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
