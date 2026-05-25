@extends('layouts.app')
@section('title', 'Expiring Documents - GWL')
@section('content')
<div class="space-y-6 text-sm">
    <!-- Header -->
    <div class="flex flex-wrap justify-between items-center gap-4">
        <div class="flex items-center gap-3">
            <a href="{{ route('vehicles.documents.index') }}" class="text-gray-500 hover:text-gray-700">
                <i class="fas fa-arrow-left"></i>
            </a>
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Expiring Documents Alert</h1>
                <p class="text-gray-500 text-xs mt-0.5">Documents set to expire within the next {{ $days }} days</p>
            </div>
        </div>
        
        <!-- Days Filter -->
        <form method="GET" action="{{ route('vehicles.documents.expiring') }}" class="flex items-center gap-2">
            <label class="text-xs text-slate-500 font-semibold whitespace-nowrap">Timeframe:</label>
            <select name="days" onchange="this.form.submit()" class="px-3 py-1.5 bg-white border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none text-xs">
                <option value="15" {{ $days == 15 ? 'selected' : '' }}>Next 15 Days</option>
                <option value="30" {{ $days == 30 ? 'selected' : '' }}>Next 30 Days</option>
                <option value="60" {{ $days == 60 ? 'selected' : '' }}>Next 60 Days</option>
                <option value="90" {{ $days == 90 ? 'selected' : '' }}>Next 90 Days</option>
            </select>
        </form>
    </div>

    <!-- Alert Banner -->
    @if($documents->count() > 0)
        <div class="bg-amber-50 border border-amber-200 rounded-2xl p-4 flex gap-3 items-start">
            <div class="w-8 h-8 rounded-full bg-amber-100 flex items-center justify-center text-amber-700">
                <i class="fas fa-bell"></i>
            </div>
            <div>
                <h4 class="font-bold text-amber-900 text-sm">Action Needed</h4>
                <p class="text-amber-700 text-xs mt-0.5">There are {{ $documents->total() }} documents set to expire soon. Please contact transporters or renew policies to avoid compliance issues.</p>
            </div>
        </div>
    @endif

    <!-- Table Card -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-100">
                <thead class="bg-slate-50 text-slate-500 uppercase tracking-wider text-[11px] font-bold">
                    <tr>
                        <th class="px-6 py-4 text-left">Document Title</th>
                        <th class="px-6 py-4 text-left">Type</th>
                        <th class="px-6 py-4 text-left">Linked Vehicle</th>
                        <th class="px-6 py-4 text-left">Expiry Date</th>
                        <th class="px-6 py-4 text-left">Time Remaining</th>
                        <th class="px-6 py-4 text-center">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700">
                    @forelse($documents as $doc)
                        <?php
                            $expiryDate = \Carbon\Carbon::parse($doc->expiry_date);
                            $daysLeft = ceil(now()->diffInDays($expiryDate, false));
                        ?>
                        <tr class="hover:bg-slate-50/50 transition">
                            <td class="px-6 py-4 font-semibold text-slate-800">
                                <a href="{{ route('vehicles.documents.show', $doc->id) }}" class="hover:text-blue-600">
                                    {{ $doc->title }}
                                </a>
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-2 py-0.5 bg-slate-100 text-slate-700 rounded-full text-xs font-medium">
                                    {{ ucfirst(str_replace('_', ' ', $doc->document_type)) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 font-medium">
                                @if($doc->vehicle)
                                    <a href="{{ route('vehicles.show', $doc->vehicle->id) }}" class="text-blue-600 hover:underline">
                                        {{ $doc->vehicle->registration_number }}
                                    </a>
                                @else
                                    <span class="text-slate-400">N/A</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 font-semibold text-red-600">{{ $doc->expiry_date ? $doc->expiry_date->format('M j, Y') : 'N/A' }}</td>
                            <td class="px-6 py-4">
                                @if($daysLeft <= 0)
                                    <span class="text-red-700 font-bold">Lapsed</span>
                                @elseif($daysLeft <= 7)
                                    <span class="text-red-600 font-bold">{{ $daysLeft }} days left</span>
                                @elseif($daysLeft <= 30)
                                    <span class="text-amber-600 font-bold">{{ $daysLeft }} days left</span>
                                @else
                                    <span class="text-slate-600 font-medium">{{ $daysLeft }} days left</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-center">
                                <a href="{{ route('vehicles.documents.show', $doc->id) }}" class="px-3 py-1.5 bg-blue-50 text-blue-600 rounded-xl hover:bg-blue-100 font-semibold transition text-xs">
                                    Manage
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-slate-400">
                                <i class="fas fa-check-circle text-4xl mb-2 text-green-500"></i>
                                <p class="font-bold text-slate-600">All Clear!</p>
                                <p class="text-xs mt-1">No documents are expiring within the selected timeframe of {{ $days }} days.</p>
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
@endsection
