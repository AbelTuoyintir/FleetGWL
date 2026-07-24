{{-- 
  Dynamic Maintenance Print Notice Blade Template
  Supports: Single maintenance record OR batch records with filter info
  Variables expected from controller:
    - $maintenance (optional)        : Single Maintenance model instance
    - $vehicle    (optional)         : Vehicle model (for single print)
    - $driver     (optional)         : Driver model (for single print)
    - $maintenanceRecords (optional) : Collection of Maintenance (for batch print)
    - $company    : array            : Company configuration
    - $affectedZones : array         : List of affected service zones
    - $signatoryName : string        : Signatory name
    - $signatoryTitle : string       : Signatory title
    - $regionName : string           : Region name display
    - $isBatch (optional) : bool     : Whether this is a batch print
--}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vehicle Maintenance · {{ $company['short_name'] ?? 'Ghana Water Ltd.' }}</title>
    <!-- Tailwind via CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            background: #e5e7eb;
            display: flex;
            justify-content: center;
            align-items: flex-start;
            min-height: 100vh;
            padding: 2rem 1rem;
            font-family: 'Segoe UI', Roboto, system-ui, sans-serif;
        }
        .letter-card {
            max-width: 1000px;
            width: 100%;
            background: white;
            box-shadow: 0 20px 40px -12px rgba(0,0,0,0.25);
            border-radius: 1rem;
            transition: all 0.2s;
            position: relative;
            overflow: hidden;
        }
        .watermark {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-25deg) scale(1.8);
            opacity: 0.07;
            pointer-events: none;
            z-index: 0;
            user-select: none;
            max-width: 80%;
            max-height: 80%;
            object-fit: contain;
        }
        .letter-content {
            position: relative;
            z-index: 2;
            padding: 1.5rem 2rem;
        }
        @media (max-width: 640px) {
            .letter-content { padding: 1rem 1.25rem; }
            .watermark { transform: translate(-50%, -50%) rotate(-25deg) scale(1.2); }
        }
        .letter-head {
            border-bottom: 2px solid #1e3a5f;
        }
        .board-line {
            border-top: 1px dashed #9ca3af;
            font-size: 0.7rem;
        }
        .signature {
            font-family: 'Courier New', monospace;
        }
        .header-logo-img {
            height: 70px;
            width: auto;
            max-width: 180px;
            object-fit: contain;
        }
        @media (max-width: 480px) {
            .header-logo-img { height: 50px; max-width: 130px; }
        }
        @media print {
            body { background: white; padding: 0.5in; }
            .letter-card { box-shadow: none; border-radius: 0; }
            .watermark { opacity: 0.1; }
        }
        .print-btn {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            z-index: 100;
            padding: 0.75rem 1.5rem;
            background: #1e3a5f;
            color: white;
            border: none;
            border-radius: 0.5rem;
            cursor: pointer;
            font-weight: 600;
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
            transition: all 0.2s;
        }
        .print-btn:hover {
            background: #15294a;
            transform: translateY(-2px);
        }
        @media print {
            .print-btn { display: none !important; }
        }
        .maintenance-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.75rem;
        }
        .maintenance-table th {
            background: #f1f5f9;
            text-align: left;
            padding: 0.5rem 0.75rem;
            border-bottom: 2px solid #cbd5e1;
            font-weight: 600;
            color: #1e293b;
        }
        .maintenance-table td {
            padding: 0.5rem 0.75rem;
            border-bottom: 1px solid #e2e8f0;
        }
        .maintenance-table tr:hover {
            background: #f8fafc;
        }
        .status-badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 9999px;
            font-size: 10px;
            font-weight: 600;
        }
        .status-completed { background: #dcfce7; color: #166534; }
        .status-scheduled { background: #dbeafe; color: #1e40af; }
        .status-dispatched { background: #f1f5f9; color: #475569; }
        .status-waiting { background: #fef3c7; color: #92400e; }
        .status-cancelled { background: #fee2e2; color: #991b1b; }
        .status-in_progress { background: #c7d2fe; color: #3730a3; }
    </style>
</head>
<body>

<button class="print-btn" onclick="window.print()">
    <i class="fas fa-print mr-2"></i>Print / Save PDF
</button>

<div class="letter-card">

    <!-- ===== WATERMARK ===== -->
    <img 
        class="watermark" 
        src="{{ $company['logo_url'] ?? '' }}" 
        alt="{{ $company['short_name'] ?? '' }} logo watermark"
        aria-hidden="true"
    >

    <!-- ===== MAIN CONTENT ===== -->
    <div class="letter-content">

        <!-- ===== HEADER ===== -->
        <div class="letter-head pb-4 mb-4 flex flex-wrap justify-between items-center">
            <div class="flex items-center gap-3 flex-1 min-w-[180px]">
                <img 
                    class="header-logo-img" 
                    src="{{ $company['logo_url'] ?? '' }}" 
                    alt="{{ $company['short_name'] ?? '' }} Logo"
                >
                <div class="hidden sm:block h-12 w-0.5 bg-gray-300"></div>
                <div class="text-sm text-gray-600">
                    <span class="font-semibold text-blue-900">{{ $regionName ?? 'TEMA REGION' }}</span>
                </div>
            </div>
            <div class="text-right text-sm text-gray-700 mt-1 md:mt-0">
                <p><span class="font-semibold">Main Bankers:</span> 
                    @if(isset($company['bankers']) && count($company['bankers']) > 0)
                        {{ $company['bankers'][0] }}
                    @else
                        Social Security Bank
                    @endif
                </p>
                <p>
                    @if(isset($company['bankers']) && count($company['bankers']) > 1)
                        {{ $company['bankers'][1] }}
                    @else
                        Ghana Commercial Bank
                    @endif
                </p>
                <p><span class="font-semibold">My Ref. No.:</span> 
                    @if(isset($maintenance))
                        GWL/MNT/{{ $maintenance->id }}/{{ date('Y') }}
                    @else
                        GWL/MNT/BATCH/{{ date('Ymd') }}
                    @endif
                </p>
                <p><span class="font-semibold">Your Ref. No.:</span> ....................</p>
            </div>
        </div>

        <!-- ===== ADDRESS & DATE ===== -->
        <div class="flex flex-wrap justify-between text-sm text-gray-700 border-b border-gray-200 pb-2 mb-4">
            <div>
                <p><i class="fas fa-map-pin mr-1 text-blue-600"></i> {{ $company['default_region']['address'] ?? 'Post Office Box 163, Tema – Ghana' }}</p>
                <p><i class="fas fa-globe-africa mr-1 text-blue-600"></i> {{ $company['default_region']['sub_address'] ?? 'West Africa' }}</p>
            </div>
            <div class="text-right">
                <p><i class="far fa-calendar-alt mr-1 text-blue-600"></i> 
                    @if(isset($maintenance) && $maintenance->maintenance_date)
                        {{ $maintenance->maintenance_date->format('jS F, Y') }}
                    @else
                        {{ now()->format('jS F, Y') }}
                    @endif
                </p>
            </div>
        </div>

        @if(isset($isBatch) && $isBatch)
            {{-- ===== BATCH PRINT MODE ===== --}}
            <div class="mb-5">
                <h3 class="text-2xl font-bold text-blue-900 border-l-4 border-blue-600 pl-3 uppercase tracking-wide">
                    <i class="fas fa-tools mr-2 text-blue-600"></i> VEHICLE MAINTENANCE NOTICE
                </h3>
                <p class="text-gray-600 mt-1 italic">
                    Management of {{ $company['name'] ?? 'Ghana Water Company Limited' }} – {{ $regionName ?? 'Tema Region' }}, 
                    wishes to respectfully inform its cherished customers, that there will be a 
                    <span class="font-semibold text-blue-800">scheduled vehicle service interruption</span> 
                    for routine maintenance works affecting vehicles under the following jurisdictions.
                </p>
            </div>

            @if(count($affectedZones) > 0)
            <div class="bg-blue-50 p-4 rounded-lg border border-blue-100 mb-6">
                <h4 class="text-md font-bold text-blue-800 uppercase tracking-wider flex items-center">
                    <i class="fas fa-map-marked-alt mr-2 text-blue-700"></i> AFFECTED SERVICE ZONES
                </h4>
                <div class="flex flex-wrap gap-2 mt-2 text-sm">
                    @foreach($affectedZones as $zone)
                        <span class="bg-blue-100 text-blue-700 px-3 py-1 rounded-full">• {{ $zone }}</span>
                    @endforeach
                </div>
                <p class="text-xs text-gray-500 mt-2 border-t border-blue-200 pt-1">
                    <i class="fas fa-info-circle mr-1"></i> Fleet maintenance will affect service vehicles and utility transport in these areas.
                </p>
            </div>
            @endif

            <div class="bg-white rounded-lg border border-gray-200 overflow-hidden mb-6">
                <h4 class="text-sm font-bold text-gray-700 bg-gray-50 px-4 py-2 border-b flex items-center">
                    <i class="fas fa-list mr-2 text-blue-600"></i> MAINTENANCE RECORDS ({{ $maintenanceRecords->count() }})
                </h4>
                <div class="overflow-x-auto">
                    <table class="maintenance-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Date</th>
                                <th>Vehicle</th>
                                <th>Driver</th>
                                <th>Type</th>
                                <th>Mileage</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($maintenanceRecords as $record)
                            <tr>
                                <td class="font-medium">#{{ $record->id }}</td>
                                <td>{{ $record->maintenance_date ? $record->maintenance_date->format('d/m/Y') : ($record->date ? $record->date->format('d/m/Y') : '-') }}</td>
                                <td>
                                    <span class="font-medium">{{ $record->vehicle->registration_number ?? 'N/A' }}</span>
                                    @if($record->vehicle)
                                        <br><span class="text-gray-500">{{ $record->vehicle->make ?? '' }} {{ $record->vehicle->model ?? '' }}</span>
                                    @endif
                                </td>
                                <td>{{ $record->driver->name ?? ($record->driver->user->name ?? 'N/A') }}</td>
                                <td>{{ ucfirst($record->maintenance_type ?? 'N/A') }}</td>
                                <td>{{ number_format($record->mileage_at_service ?? 0) }} km</td>
                                <td>
                                    @php
                                        $statusClass = match($record->status) {
                                            'completed' => 'status-completed',
                                            'scheduled' => 'status-scheduled',
                                            'dispatched' => 'status-dispatched',
                                            'waiting' => 'status-waiting',
                                            'cancelled' => 'status-cancelled',
                                            'in_progress' => 'status-in_progress',
                                            default => 'status-scheduled',
                                        };
                                    @endphp
                                    <span class="status-badge {{ $statusClass }}">
                                        {{ ucfirst(str_replace('_', ' ', $record->status)) }}
                                    </span>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="text-center py-6 text-gray-500">
                                    <i class="fas fa-inbox text-2xl mb-2 block"></i>
                                    No maintenance records found for the selected criteria.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        @else
            {{-- ===== SINGLE VEHICLE PRINT MODE ===== --}}
            @php
                $maintenance = $maintenance ?? null;
                $vehicle = $vehicle ?? ($maintenance ? $maintenance->vehicle : null);
                $driver = $driver ?? ($maintenance ? $maintenance->driver : null);
            @endphp

            <div class="mb-5">
                <h3 class="text-2xl font-bold text-blue-900 border-l-4 border-blue-600 pl-3 uppercase tracking-wide">
                    <i class="fas fa-tools mr-2 text-blue-600"></i> VEHICLE MAINTENANCE NOTICE
                </h3>
                <p class="text-gray-600 mt-1 italic">
                    Management of {{ $company['name'] ?? 'Ghana Water Company Limited' }} – {{ $regionName ?? 'Tema Region' }}, 
                    wishes to respectfully inform its cherished customers, that there will be a 
                    <span class="font-semibold text-blue-800">scheduled vehicle service interruption</span> 
                    for routine maintenance works on 
                    <span class="font-semibold">{{ $vehicle ? $vehicle->registration_number . ' (' . $vehicle->make . ' ' . $vehicle->model . ')' : 'the affected vehicle' }}</span>.
                </p>
            </div>

            <!-- Vehicle & Maintenance Details Card -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                <div class="bg-blue-50 p-4 rounded-lg border border-blue-100">
                    <h4 class="text-md font-bold text-blue-800 uppercase tracking-wider flex items-center mb-2">
                        <i class="fas fa-truck mr-2 text-blue-700"></i> VEHICLE DETAILS
                    </h4>
                    @if($vehicle)
                    <table class="w-full text-sm">
                        <tr class="border-b border-blue-100">
                            <td class="py-1 font-medium text-gray-600">Registration:</td>
                            <td class="py-1 font-bold text-gray-800">{{ $vehicle->registration_number ?? 'N/A' }}</td>
                        </tr>
                        <tr class="border-b border-blue-100">
                            <td class="py-1 font-medium text-gray-600">Make/Model:</td>
                            <td class="py-1 text-gray-800">{{ $vehicle->make ?? '' }} {{ $vehicle->model ?? '' }} ({{ $vehicle->year ?? '' }})</td>
                        </tr>
                        <tr class="border-b border-blue-100">
                            <td class="py-1 font-medium text-gray-600">Color:</td>
                            <td class="py-1 text-gray-800">{{ $vehicle->color ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <td class="py-1 font-medium text-gray-600">Current Mileage:</td>
                            <td class="py-1 text-gray-800">{{ number_format($vehicle->mileage ?? 0) }} km</td>
                        </tr>
                    </table>
                    @else
                    <p class="text-sm text-gray-500 italic">Vehicle information unavailable.</p>
                    @endif
                </div>

                <div class="bg-green-50 p-4 rounded-lg border border-green-100">
                    <h4 class="text-md font-bold text-green-800 uppercase tracking-wider flex items-center mb-2">
                        <i class="fas fa-wrench mr-2 text-green-700"></i> MAINTENANCE DETAILS
                    </h4>
                    @if($maintenance)
                    <table class="w-full text-sm">
                        <tr class="border-b border-green-100">
                            <td class="py-1 font-medium text-gray-600">Type:</td>
                            <td class="py-1 font-bold text-gray-800">{{ ucfirst($maintenance->maintenance_type ?? 'N/A') }}</td>
                        </tr>
                        <tr class="border-b border-green-100">
                            <td class="py-1 font-medium text-gray-600">Date:</td>
                            <td class="py-1 text-gray-800">{{ $maintenance->maintenance_date ? $maintenance->maintenance_date->format('jS F, Y') : ($maintenance->date ? $maintenance->date->format('jS F, Y') : 'N/A') }}</td>
                        </tr>
                        <tr class="border-b border-green-100">
                            <td class="py-1 font-medium text-gray-600">Mileage at Service:</td>
                            <td class="py-1 text-gray-800">{{ number_format($maintenance->mileage_at_service ?? 0) }} km</td>
                        </tr>
                        <tr class="border-b border-green-100">
                            <td class="py-1 font-medium text-gray-600">Status:</td>
                            <td class="py-1">
                                @php
                                    $statusClass = match($maintenance->status) {
                                        'completed' => 'status-completed',
                                        'scheduled' => 'status-scheduled',
                                        'dispatched' => 'status-dispatched',
                                        'waiting' => 'status-waiting',
                                        'cancelled' => 'status-cancelled',
                                        'in_progress' => 'status-in_progress',
                                        default => 'status-scheduled',
                                    };
                                @endphp
                                <span class="status-badge {{ $statusClass }}">
                                    {{ ucfirst(str_replace('_', ' ', $maintenance->status)) }}
                                </span>
                            </td>
                        </tr>
                        @if($maintenance->cost > 0)
                        <tr>
                            <td class="py-1 font-medium text-gray-600">Cost (GHS):</td>
                            <td class="py-1 font-bold text-gray-800">GHS {{ number_format($maintenance->cost, 2) }}</td>
                        </tr>
                        @endif
                    </table>
                    @else
                    <p class="text-sm text-gray-500 italic">Maintenance information unavailable.</p>
                    @endif
                </div>
            </div>

            @if($driver)
            <div class="bg-yellow-50 p-4 rounded-lg border border-yellow-200 mb-6">
                <h4 class="text-md font-bold text-yellow-800 uppercase tracking-wider flex items-center mb-2">
                    <i class="fas fa-id-card mr-2 text-yellow-700"></i> ASSIGNED DRIVER
                </h4>
                <div class="text-sm">
                    <p><span class="font-medium">Name:</span> {{ $driver->name ?? 'N/A' }}</p>
                    @if($driver->license_number)
                    <p><span class="font-medium">License No.:</span> {{ $driver->license_number }}</p>
                    @endif
                    @if($driver->license_expiry_date)
                    <p><span class="font-medium">License Expiry:</span> {{ $driver->license_expiry_date->format('jS F, Y') }}</p>
                    @endif
                </div>
            </div>
            @endif

            <!-- Description / Services -->
            @if($maintenance && $maintenance->description)
            <div class="bg-white p-4 rounded-lg border border-gray-200 mb-6">
                <h4 class="text-md font-bold text-gray-800 flex items-center mb-2">
                    <i class="fas fa-clipboard-list mr-2 text-blue-600"></i> DESCRIPTION / ISSUE REPORTED
                </h4>
                <p class="text-sm text-gray-700">{{ $maintenance->description }}</p>
            </div>
            @endif

            @if($maintenance && $maintenance->checklist && count($maintenance->checklist) > 0)
            <div class="bg-white p-4 rounded-lg border border-gray-200 mb-6">
                <h4 class="text-md font-bold text-gray-800 flex items-center mb-2">
                    <i class="fas fa-check-double mr-2 text-green-600"></i> SERVICES PERFORMED
                </h4>
                <ul class="list-disc list-inside text-sm text-gray-700 space-y-1">
                    @foreach($maintenance->checklist as $service)
                        <li>{{ is_array($service) ? ($service['name'] ?? json_encode($service)) : $service }}</li>
                    @endforeach
                </ul>
            </div>
            @endif

            <!-- Affected Zones (from vehicle hierarchy) -->
            @if(count($affectedZones) > 0)
            <div class="bg-purple-50 p-4 rounded-lg border border-purple-100 mb-6">
                <h4 class="text-md font-bold text-purple-800 uppercase tracking-wider flex items-center">
                    <i class="fas fa-map-marker-alt mr-2 text-purple-700"></i> AFFECTED SERVICE ZONES
                </h4>
                <div class="flex flex-wrap gap-2 mt-2 text-sm">
                    @foreach($affectedZones as $zone)
                        <span class="bg-purple-100 text-purple-700 px-3 py-1 rounded-full">• {{ $zone }}</span>
                    @endforeach
                </div>
            </div>
            @endif

            <!-- Advice Box -->
            <div class="bg-yellow-50 p-4 rounded-lg border border-yellow-200 mb-6">
                <div class="flex items-start">
                    <i class="fas fa-gas-pump text-yellow-700 text-xl mr-3 mt-1"></i>
                    <div>
                        <p class="font-semibold text-yellow-800">ADVICE FOR CUSTOMERS</p>
                        <p class="text-gray-700 text-sm">
                            Customers in the affected areas are therefore being advised to 
                            <span class="font-bold text-yellow-900">ensure vehicles are fueled and serviced</span> 
                            before the interruption. 
                            <span class="block mt-1 text-gray-600">Supply (fuel &amp; maintenance support) will be restored as soon as the maintenance work is completed.</span>
                        </p>
                    </div>
                </div>
            </div>
        @endif

        <!-- ===== REGRET & SIGNATURE ===== -->
        <div class="flex flex-wrap justify-between items-center border-t border-gray-200 pt-4 mt-2">
            <div>
                <p class="text-gray-700 text-sm"><i class="fas fa-exclamation-triangle text-amber-500 mr-1"></i> Any inconvenience caused is deeply regretted.</p>
                <p class="text-gray-800 font-medium mt-1"><i class="fas fa-check-circle text-green-600 mr-1"></i> Thank you for your cooperation.</p>
            </div>
            <div class="text-right">
                <p class="font-bold text-blue-900">Yours faithfully,</p>
                <p class="font-bold text-blue-800 text-lg signature tracking-wider">{{ $signatoryName ?? 'ING. MAC-DOE HANYABUI' }}</p>
                <p class="text-sm text-gray-600 -mt-1">{{ $signatoryTitle ?? '(AG. REGIONAL CHIEF MANAGER)' }}</p>
            </div>
        </div>

        <!-- ===== BOARD OF DIRECTORS ===== -->
        <div class="board-line mt-6 pt-3 text-[0.65rem] text-gray-600">
            <p class="font-semibold text-gray-700">Board of Directors:</p>
            <p>{{ $company['board_of_directors'] ?? 'Board information unavailable' }}</p>
        </div>

        <!-- ===== FOOTER ===== -->
        <div class="mt-4 pt-3 border-t border-gray-200 text-[0.6rem] text-gray-500 flex flex-wrap justify-between gap-2">
            <div>
                <p><span class="font-semibold text-gray-700">Head Office:</span> {{ $company['head_office']['address'] ?? '' }}</p>
                <p>Tel. No. {{ $company['head_office']['tel'] ?? '' }} Fax: {{ $company['head_office']['fax'] ?? '' }}</p>
                <p>Telephone: {{ $company['head_office']['telephone'] ?? '' }} · Website: <a href="#" class="text-blue-600 hover:underline">{{ $company['head_office']['website'] ?? '' }}</a> · E-mail: {{ $company['head_office']['email'] ?? '' }}</p>
            </div>
            <div class="text-right">
                <p><span class="font-semibold text-gray-700">{{ $regionName ?? 'Tema Region' }}:</span> Registration Office: {{ $company['default_region']['address'] ?? '' }}</p>
                <p>Tel. {{ $company['default_region']['tel'] ?? '' }}, Fax: {{ $company['default_region']['fax'] ?? '' }}</p>
                <p>Email: {{ $company['default_region']['email'] ?? '' }} · Website: <a href="#" class="text-blue-600 hover:underline">{{ $company['default_region']['website'] ?? '' }}</a></p>
            </div>
        </div>

        <!-- small watermark text -->
        <div class="mt-2 text-[0.5rem] text-gray-300 text-center border-t border-gray-100 pt-1">
            <i class="fas fa-tools mr-1"></i> vehicle maintenance edition · {{ date('Y') }}
        </div>
    </div> <!-- /letter-content -->
</div> <!-- /letter-card -->

<script>
    // Auto-trigger print dialog when page loads (optional - uncomment if desired)
    // window.onload = function() { setTimeout(function() { window.print(); }, 500); };
</script>

</body>
</html>

