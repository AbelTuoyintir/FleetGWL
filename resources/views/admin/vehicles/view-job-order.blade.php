<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Job Order {{ $maintenance->job_order_number }}
                </h2>
                <p class="text-sm text-gray-500 mt-1">
                    {{ optional($maintenance->vehicle)->plate_number ?? 'No vehicle' }}
                    &middot; {{ $maintenance->display_date?->format('d M Y') ?? '—' }}
                </p>
            </div>

            <div class="flex items-center gap-2">
                <a href="{{ route('vehicles.maintenance.print', $maintenance) }}"
                   target="_blank"
                   class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-slate-900 text-white text-sm font-semibold hover:bg-slate-800">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0110.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0l.229 2.523a1.125 1.125 0 01-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0021 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 00-1.913-.247M6.34 18H5.25A2.25 2.25 0 013 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 011.913-.247m10.5 0a48.536 48.536 0 00-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5z"/>
                    </svg>
                    Print Job Order
                </a>

                @can('update', $maintenance)
                    <a href="{{ route('maintenance.edit', $maintenance) }}"
                       class="inline-flex items-center px-4 py-2 rounded-lg border border-gray-300 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                        Edit
                    </a>
                @endcan
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- Header card --}}
            <div class="bg-white shadow-sm rounded-xl border border-gray-100 p-6">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-widest text-gray-400">Job Order</p>
                        <p class="text-2xl font-black text-gray-900">{{ $maintenance->job_order_number }}</p>
                        <p class="text-sm text-gray-500 mt-1">
                            Created {{ $maintenance->created_at?->format('d M Y H:i') ?? '—' }}
                        </p>
                    </div>
                    <div class="text-right">
                        <div>{!! $maintenance->status_badge !!}</div>
                        @if ($maintenance->is_overdue)
                            <p class="mt-2 text-xs font-bold text-rose-600 uppercase tracking-wider">
                                Overdue by {{ abs($maintenance->days_until_due) }} day(s)
                            </p>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Vehicle + Driver --}}
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

                <div class="bg-white shadow-sm rounded-xl border border-gray-100 p-6">
                    <h3 class="text-xs font-bold uppercase tracking-widest text-gray-400 mb-4">Vehicle</h3>
                    <dl class="grid grid-cols-2 gap-y-3 text-sm">
                        <dt class="text-gray-500">Plate</dt>
                        <dd class="font-semibold text-gray-900">{{ optional($maintenance->vehicle)->plate_number ?? '—' }}</dd>

                        <dt class="text-gray-500">Make / Model</dt>
                        <dd class="font-semibold text-gray-900">
                            {{ trim((optional($maintenance->vehicle)->make ?? '') . ' ' . (optional($maintenance->vehicle)->model ?? '')) ?: '—' }}
                        </dd>

                        <dt class="text-gray-500">VIN / Chassis</dt>
                        <dd class="font-semibold text-gray-900">{{ optional($maintenance->vehicle)->vin ?? '—' }}</dd>

                        <dt class="text-gray-500">Mileage at service</dt>
                        <dd class="font-semibold text-gray-900">{{ $maintenance->formatted_mileage }}</dd>
                    </dl>
                </div>

                <div class="bg-white shadow-sm rounded-xl border border-gray-100 p-6">
                    <h3 class="text-xs font-bold uppercase tracking-widest text-gray-400 mb-4">Driver / Operator</h3>
                    <dl class="grid grid-cols-2 gap-y-3 text-sm">
                        <dt class="text-gray-500">Name</dt>
                        <dd class="font-semibold text-gray-900">{{ optional($maintenance->driver)->name ?? '—' }}</dd>

                        <dt class="text-gray-500">License</dt>
                        <dd class="font-semibold text-gray-900">{{ optional($maintenance->driver)->license_number ?? '—' }}</dd>

                        <dt class="text-gray-500">Phone</dt>
                        <dd class="font-semibold text-gray-900">{{ optional($maintenance->driver)->phone ?? '—' }}</dd>
                    </dl>
                </div>
            </div>

            {{-- Service details --}}
            <div class="bg-white shadow-sm rounded-xl border border-gray-100 p-6">
                <h3 class="text-xs font-bold uppercase tracking-widest text-gray-400 mb-4">Service Details</h3>
                <dl class="grid grid-cols-2 md:grid-cols-4 gap-y-4 text-sm">
                    <div>
                        <dt class="text-gray-500">Type</dt>
                        <dd class="font-semibold text-gray-900">
                            {{ $maintenance->maintenance_type === 'other'
                                ? ($maintenance->other_maintenance_type ?: 'Other')
                                : \Illuminate\Support\Str::headline($maintenance->maintenance_type ?? '—') }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Service date</dt>
                        <dd class="font-semibold text-gray-900">{{ $maintenance->display_date?->format('d M Y') ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Provider</dt>
                        <dd class="font-semibold text-gray-900">{{ $maintenance->service_provider ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Cost</dt>
                        <dd class="font-semibold text-gray-900">{{ $maintenance->formatted_cost }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Next service due</dt>
                        <dd class="font-semibold text-gray-900">{{ $maintenance->next_service_due?->format('d M Y') ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Next expected mileage</dt>
                        <dd class="font-semibold text-gray-900">{{ $maintenance->formatted_next_service_mileage ?? '—' }}</dd>
                    </div>
                </dl>
            </div>

            {{-- Checklist --}}
            <div class="bg-white shadow-sm rounded-xl border border-gray-100 p-6">
                <h3 class="text-xs font-bold uppercase tracking-widest text-gray-400 mb-4">Checklist</h3>

                @forelse ($maintenance->checklist_items as $item)
                    <div class="flex items-start gap-3 py-2 border-b border-gray-100 last:border-0">
                        <span class="mt-0.5 inline-flex items-center justify-center w-5 h-5 rounded-full text-[10px] font-black
                            {{ $item['done'] ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-600' }}">
                            {{ $item['done'] ? '✓' : '✕' }}
                        </span>
                        <div>
                            <p class="text-sm font-medium text-gray-800">{{ $item['label'] }}</p>
                            @if (!empty($item['note']))
                                <p class="text-xs text-gray-500">{{ $item['note'] }}</p>
                            @endif
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-gray-400">No checklist recorded.</p>
                @endforelse
            </div>

            {{-- Parts + Description --}}
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="bg-white shadow-sm rounded-xl border border-gray-100 p-6">
                    <h3 class="text-xs font-bold uppercase tracking-widest text-gray-400 mb-4">Parts / Services Replaced</h3>
                    @forelse ($maintenance->parts_array as $part)
                        <span class="inline-flex items-center px-3 py-1 mb-2 mr-2 rounded-full bg-slate-100 text-slate-700 text-xs font-semibold">
                            {{ $part }}
                        </span>
                    @empty
                        <p class="text-sm text-gray-400">No parts recorded.</p>
                    @endforelse
                </div>

                <div class="bg-white shadow-sm rounded-xl border border-gray-100 p-6">
                    <h3 class="text-xs font-bold uppercase tracking-widest text-gray-400 mb-4">Description / Remarks</h3>
                    <p class="text-sm text-gray-700 whitespace-pre-line">
                        {{ $maintenance->description ?: 'No description.' }}
                    </p>
                </div>
            </div>

            {{-- Attachments --}}
            @if (!empty($maintenance->attachments_array))
                <div class="bg-white shadow-sm rounded-xl border border-gray-100 p-6">
                    <h3 class="text-xs font-bold uppercase tracking-widest text-gray-400 mb-4">Attachments</h3>
                    <ul class="space-y-2 text-sm">
                        @foreach ($maintenance->attachments_array as $file)
                            @php
                                $path = is_array($file) ? ($file['path'] ?? $file['url'] ?? null) : $file;
                                $name = is_array($file) ? ($file['name'] ?? basename($path ?? '')) : basename($file);
                            @endphp
                            @if ($path)
                                <li>
                                    <a href="{{ \Illuminate\Support\Facades\Storage::url($path) }}"
                                       target="_blank"
                                       class="text-indigo-600 hover:underline">
                                        {{ $name }}
                                    </a>
                                </li>
                            @endif
                        @endforeach
                    </ul>
                </div>
            @endif

        </div>
    </div>
</x-app-layout>