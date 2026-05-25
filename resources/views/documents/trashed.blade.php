@extends('layouts.app')
@section('title', 'Trashed Documents - GWL')
@section('content')
<div class="space-y-6 text-sm">
    <!-- Header -->
    <div class="flex items-center gap-3">
        <a href="{{ route('vehicles.documents.index') }}" class="text-gray-500 hover:text-gray-700">
            <i class="fas fa-arrow-left"></i>
        </a>
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Trashed Documents</h1>
            <p class="text-gray-500 text-xs mt-0.5">Manage and restore recently soft-deleted documents</p>
        </div>
    </div>

    <!-- Table Card -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-100">
                <thead class="bg-slate-50 text-slate-500 uppercase tracking-wider text-[11px] font-bold">
                    <tr>
                        <th class="px-6 py-4 text-left">Document Title</th>
                        <th class="px-6 py-4 text-left">Type</th>
                        <th class="px-6 py-4 text-left">Linked Vehicle</th>
                        <th class="px-6 py-4 text-left">Deleted On</th>
                        <th class="px-6 py-4 text-center">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700">
                    @forelse($documents as $doc)
                        <tr class="hover:bg-slate-50/50 transition">
                            <td class="px-6 py-4 font-semibold text-slate-800">{{ $doc->title }}</td>
                            <td class="px-6 py-4">
                                <span class="px-2 py-0.5 bg-slate-100 text-slate-700 rounded-full text-xs font-medium">
                                    {{ ucfirst(str_replace('_', ' ', $doc->document_type)) }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                @if($doc->vehicle)
                                    <span class="font-medium text-slate-700">{{ $doc->vehicle->registration_number }}</span>
                                @else
                                    <span class="text-slate-400">N/A</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-slate-500">{{ $doc->deleted_at ? $doc->deleted_at->format('M j, Y H:i') : $doc->updated_at->format('M j, Y H:i') }}</td>
                            <td class="px-6 py-4 text-center flex justify-center gap-2">
                                <form action="{{ route('vehicles.documents.restore', $doc->id) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="px-3 py-1.5 bg-blue-50 text-blue-600 rounded-xl hover:bg-blue-100 hover:text-blue-700 font-semibold transition text-xs flex items-center gap-1">
                                        <i class="fas fa-undo-alt text-[10px]"></i> Restore
                                    </button>
                                </form>
                                <button onclick="confirmForceDelete({{ $doc->id }})" class="px-3 py-1.5 bg-red-50 text-red-600 rounded-xl hover:bg-red-100 hover:text-red-700 font-semibold transition text-xs flex items-center gap-1">
                                    <i class="fas fa-trash-alt text-[10px]"></i> Delete Permanently
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center text-slate-400">
                                <i class="fas fa-trash-restore-alt text-4xl mb-2 text-slate-200"></i>
                                <p class="font-bold text-slate-600">Trash is Empty</p>
                                <p class="text-xs mt-1">There are no soft-deleted documents in your workspace.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        @if($documents->hasPages())
            <div class="px-6 py-4 bg-slate-50 border-t border-slate-100">
                {{ $documents->links() }}
            </div>
        @endif
    </div>
</div>

<script>
    function confirmForceDelete(id) {
        Swal.fire({
            title: 'Are you absolutely sure?',
            text: "This action is permanent and cannot be undone. The file will be deleted forever.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#475569',
            confirmButtonText: 'Yes, delete permanently'
        }).then((result) => {
            if (result.isConfirmed) {
                // Submit force destroy request
                $.ajax({
                    url: `/vehicles/documents/${id}/force`,
                    method: 'POST',
                    data: {
                        _method: 'DELETE',
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Permanently Deleted!',
                            text: 'Document has been purged.',
                            showConfirmButton: false,
                            timer: 1200
                        });
                        setTimeout(() => {
                            window.location.reload();
                        }, 1200);
                    },
                    error: function() {
                        Swal.fire('Error', 'Failed to purge document.', 'error');
                    }
                });
            }
        });
    }
</script>
@endsection
