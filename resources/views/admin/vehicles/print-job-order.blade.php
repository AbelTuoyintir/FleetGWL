<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Job Order {{ $maintenance->job_order_number }}</title>
    <style>
        @page { size: A4 portrait; margin: 12mm 12mm 14mm 12mm; }

        * { box-sizing: border-box; }
        html, body { margin: 0; padding: 0; }
        body {
            font-family: "DejaVu Sans", Arial, Helvetica, sans-serif;
            font-size: 11.5px;
            line-height: 1.45;
            color: #111827;
            background: #fff;
        }

        .sheet { width: 100%; max-width: 190mm; margin: 0 auto; }

        /* Header */
        .doc-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 2px solid #111827;
            padding-bottom: 10px;
            margin-bottom: 14px;
        }
        .company-name { font-size: 18px; font-weight: 800; letter-spacing: .04em; }
        .company-meta { font-size: 10px; color: #4b5563; margin-top: 2px; }
        .jo-box { text-align: right; }
        .jo-label { font-size: 9px; letter-spacing: .15em; text-transform: uppercase; color: #6b7280; }
        .jo-number { font-size: 20px; font-weight: 900; letter-spacing: .02em; }
        .status-pill {
            display: inline-block;
            margin-top: 4px;
            padding: 2px 10px;
            border-radius: 999px;
            font-size: 9px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .08em;
            border: 1px solid #d1d5db;
            background: #f3f4f6;
        }

        /* Sections */
        .section { margin-bottom: 14px; page-break-inside: avoid; }
        .section-title {
            font-size: 9px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .12em;
            color: #6b7280;
            margin: 0 0 6px;
            border-bottom: 1px solid #e5e7eb;
            padding-bottom: 3px;
        }

        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #d1d5db; padding: 5px 8px; vertical-align: top; text-align: left; }
        th {
            background: #f3f4f6;
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: .06em;
            color: #374151;
            font-weight: 700;
        }
        td.k { width: 28%; background: #fafafa; color: #4b5563; font-size: 10px; }
        td.v { font-weight: 600; }

        .grid-2 { display: flex; gap: 10px; }
        .grid-2 > * { flex: 1; }

        .check-yes { color: #047857; font-weight: 800; }
        .check-no  { color: #b91c1c; font-weight: 800; }

        .total-row td { font-size: 13px; font-weight: 800; background: #f9fafb; }

        .desc-box {
            border: 1px solid #d1d5db;
            padding: 8px;
            min-height: 44px;
            white-space: pre-line;
            font-size: 11px;
        }

        /* Signatures */
        .signatures { display: flex; gap: 16px; margin-top: 26px; page-break-inside: avoid; }
        .signatures > div { flex: 1; }
        .sig-space { height: 42px; }
        .sig-line { border-top: 1px solid #111827; padding-top: 4px; font-size: 9.5px; color: #374151; }
        .sig-label { font-size: 9px; text-transform: uppercase; letter-spacing: .1em; color: #6b7280; }

        .footer {
            margin-top: 18px;
            border-top: 1px dashed #d1d5db;
            padding-top: 6px;
            font-size: 8.5px;
            color: #9ca3af;
            display: flex;
            justify-content: space-between;
        }

        /* Screen-only toolbar */
        .no-print { margin: 16px auto; max-width: 190mm; text-align: right; }
        .btn {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 6px;
            background: #111827;
            color: #fff;
            font-size: 12px;
            font-weight: 700;
            text-decoration: none;
            cursor: pointer;
            border: 0;
        }
        .btn.secondary { background: #fff; color: #111827; border: 1px solid #d1d5db; }

        @media print {
            .no-print { display: none !important; }
            body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }
    </style>
</head>
<body>

@php
    $statusLabels = [
        'waiting'    => 'Pending Approval',
        'dispatched' => 'In Workshop',
        'scheduled'  => 'Scheduled',
        'completed'  => 'Finalized',
        'cancelled'  => 'Cancelled',
    ];
    $statusLabel = $statusLabels[$maintenance->status] ?? ucfirst($maintenance->status);

    $serviceType = $maintenance->maintenance_type === 'other'
        ? ($maintenance->other_maintenance_type ?: 'Other')
        : \Illuminate\Support\Str::headline($maintenance->maintenance_type ?? '—');
@endphp

<div class="no-print">
    <a href="{{ route('maintenance.show', $maintenance) }}" class="btn secondary">← Back</a>
    <button class="btn" onclick="window.print()">Print</button>
</div>

<div class="sheet">

    {{-- ============ HEADER ============ --}}
    <div class="doc-header">
        <div>
            <div class="company-name">{{ config('app.name', 'Fleet Management') }}</div>
            <div class="company-meta">
                Vehicle Maintenance — Job Order<br>
                Printed {{ now()->format('d M Y H:i') }}
            </div>
        </div>
        <div class="jo-box">
            <div class="jo-label">Job Order No.</div>
            <div class="jo-number">{{ $maintenance->job_order_number }}</div>
            <span class="status-pill">{{ $statusLabel }}</span>
        </div>
    </div>

    {{-- ============ VEHICLE / DRIVER ============ --}}
    <div class="grid-2">
        <div class="section">
            <p class="section-title">Vehicle</p>
            <table>
                <tr><td class="k">Plate Number</td><td class="v">{{ optional($maintenance->vehicle)->plate_number ?? '—' }}</td></tr>
                <tr><td class="k">Make / Model</td><td class="v">{{ trim((optional($maintenance->vehicle)->make ?? '') . ' ' . (optional($maintenance->vehicle)->model ?? '')) ?: '—' }}</td></tr>
                <tr><td class="k">VIN / Chassis</td><td class="v">{{ optional($maintenance->vehicle)->vin ?? '—' }}</td></tr>
                <tr><td class="k">Mileage at Service</td><td class="v">{{ $maintenance->formatted_mileage }}</td></tr>
            </table>
        </div>

        <div class="section">
            <p class="section-title">Driver / Operator</p>
            <table>
                <tr><td class="k">Name</td><td class="v">{{ optional($maintenance->driver)->name ?? '—' }}</td></tr>
                <tr><td class="k">License No.</td><td class="v">{{ optional($maintenance->driver)->license_number ?? '—' }}</td></tr>
                <tr><td class="k">Phone</td><td class="v">{{ optional($maintenance->driver)->phone ?? '—' }}</td></tr>
                <tr><td class="k">Service Date</td><td class="v">{{ $maintenance->display_date?->format('d M Y') ?? '—' }}</td></tr>
            </table>
        </div>
    </div>

    {{-- ============ SERVICE DETAILS ============ --}}
    <div class="section">
        <p class="section-title">Service Details</p>
        <table>
            <tr>
                <td class="k">Maintenance Type</td><td class="v">{{ $serviceType }}</td>
                <td class="k">Service Provider</td><td class="v">{{ $maintenance->service_provider ?? '—' }}</td>
            </tr>
            <tr>
                <td class="k">Next Service Due</td><td class="v">{{ $maintenance->next_service_due?->format('d M Y') ?? '—' }}</td>
                <td class="k">Next Expected Mileage</td><td class="v">{{ $maintenance->formatted_next_service_mileage ?? '—' }}</td>
            </tr>
        </table>
    </div>

    {{-- ============ CHECKLIST ============ --}}
    <div class="section">
        <p class="section-title">Inspection / Maintenance Checklist</p>
        <table>
            <thead>
                <tr>
                    <th style="width:6%;">#</th>
                    <th style="width:60%;">Item</th>
                    <th style="width:14%; text-align:center;">Done</th>
                    <th>Remarks</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($maintenance->checklist_items as $i => $item)
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td>{{ $item['label'] }}</td>
                        <td style="text-align:center;">
                            @if ($item['done'])
                                <span class="check-yes">✓ Yes</span>
                            @else
                                <span class="check-no">✕ No</span>
                            @endif
                        </td>
                        <td>{{ $item['note'] ?? '' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" style="text-align:center; color:#9ca3af;">No checklist recorded.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- ============ PARTS ============ --}}
    <div class="section">
        <p class="section-title">Parts / Services Replaced</p>
        <table>
            <thead>
                <tr>
                    <th style="width:6%;">#</th>
                    <th>Part / Service</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($maintenance->parts_array as $i => $part)
                    <tr><td>{{ $i + 1 }}</td><td>{{ $part }}</td></tr>
                @empty
                    <tr><td colspan="2" style="text-align:center; color:#9ca3af;">No parts recorded.</td></tr>
                @endforelse
                <tr class="total-row">
                    <td colspan="1" style="text-align:right;">TOTAL COST</td>
                    <td>{{ $maintenance->formatted_cost }}</td>
                </tr>
            </tbody>
        </table>
    </div>

    {{-- ============ DESCRIPTION ============ --}}
    <div class="section">
        <p class="section-title">Description / Remarks</p>
        <div class="desc-box">{{ $maintenance->description ?: '—' }}</div>
    </div>

    {{-- ============ ATTACHMENTS ============ --}}
    @if (!empty($maintenance->attachments_array))
        <div class="section">
            <p class="section-title">Attachments</p>
            <table>
                <tbody>
                @foreach ($maintenance->attachments_array as $file)
                    @php
                        $path = is_array($file) ? ($file['path'] ?? $file['url'] ?? null) : $file;
                        $name = is_array($file) ? ($file['name'] ?? basename($path ?? '')) : basename($file);
                    @endphp
                    @if ($path)
                        <tr><td>📎 {{ $name }}</td></tr>
                    @endif
                @endforeach
                </tbody>
            </table>
        </div>
    @endif

    {{-- ============ SIGNATURES ============ --}}
    <div class="signatures">
        <div>
            <div class="sig-space"></div>
            <div class="sig-line">
                <div class="sig-label">Prepared By</div>
                {{ optional($maintenance->creator)->name ?? '____________________' }}
            </div>
        </div>
        <div>
            <div class="sig-space"></div>
            <div class="sig-line">
                <div class="sig-label">Workshop / Service Provider</div>
                {{ $maintenance->service_provider ?? '____________________' }}
            </div>
        </div>
        <div>
            <div class="sig-space"></div>
            <div class="sig-line">
                <div class="sig-label">Driver / Operator</div>
                {{ optional($maintenance->driver)->name ?? '____________________' }}
            </div>
        </div>
    </div>

    <div class="footer">
        <span>{{ config('app.name') }} — {{ $maintenance->job_order_number }}</span>
        <span>Generated {{ now()->format('d/m/Y H:i') }}</span>
    </div>

</div>

{{-- Auto open print dialog when ?auto=1 --}}
@if (request('auto'))
    <script>window.addEventListener('load', () => window.print());</script>
@endif

</body>
</html>