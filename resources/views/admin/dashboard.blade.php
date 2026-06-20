@extends('layouts.app')
@section('title', 'Dashboard - GWL')
@section('content')
    <style>
        .stat-card {
            transition: all 0.2s ease;
            border: 1px solid #e2e8f0;
            background-color: #ffffff;
            box-shadow: 0 1px 2px rgba(0,0,0,0.03);
        }
        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px -6px rgba(0,0,0,0.08);
            border-color: #cbd5e1;
        }
        ::-webkit-scrollbar { width: 5px; height: 5px; }
        ::-webkit-scrollbar-track { background: #e2e8f0; border-radius: 10px; }
        ::-webkit-scrollbar-thumb { background: #94a3b8; border-radius: 10px; }
        .hover-row:hover { background-color: #f8fafc; }
        .badge-warning { background: #ffedd5; color: #b45309; }
        .data-table td, .data-table th { padding: 0.75rem 0.5rem; border-bottom: 1px solid #e9eef3; }
        .data-table tr:last-child td { border-bottom: none; }
    </style>

    <div class="space-y-6 text-sm" id="dashboardRoot">
        <div class="flex flex-wrap justify-between items-center">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Fleet Management Dashboard</h1>
                <p class="text-gray-500 text-xs mt-0.5">Ghana Water Company Limited - Real-time fleet intelligence</p>
            </div>
            <div class="flex gap-2 mt-2 sm:mt-0">
                <span class="bg-white border border-slate-200 rounded-lg px-3 py-1.5 text-xs">
                    <i class="far fa-calendar-alt mr-1"></i> <span id="dashboardDate">{{ now()->format('F j, Y') }}</span>
                </span>
            </div>
        </div>

        <div class="grid grid-cols-2 lg:grid-cols-5 gap-4">
            <a href="{{ route('vehicles.tracking') }}" class="stat-card rounded-xl p-4 flex items-center justify-between bg-indigo-600 border-indigo-500 group hover:bg-indigo-700 transition-colors">
                <div>
                    <p class="text-indigo-100 text-xs uppercase tracking-wide font-bold">Live Tracking</p>
                    <p class="text-white text-xs mt-1">Monitor Fleet Live</p>
                </div>
                <div class="w-10 h-10 bg-white/20 rounded-full flex items-center justify-center group-hover:scale-110 transition-transform">
                    <i class="fas fa-map-marked-alt text-white text-xl"></i>
                </div>
            </a>
            <div class="stat-card rounded-xl p-4 flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-xs uppercase tracking-wide">Overdue Maint</p>
                    <p class="text-3xl font-bold text-red-700" id="overdueMaintenance">{{ $criticalAlerts->overdue_maintenance ?? 0 }}</p>
                </div>
                <div class="w-10 h-10 bg-red-50 rounded-full flex items-center justify-center"><i class="fas fa-exclamation-triangle text-red-500 text-xl"></i></div>
            </div>
            <div class="stat-card rounded-xl p-4 flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-xs uppercase tracking-wide">Expired Insur.</p>
                    <p class="text-3xl font-bold text-orange-700" id="expiredInsurances">{{ $criticalAlerts->expired_insurances ?? 0 }}</p>
                </div>
                <div class="w-10 h-10 bg-orange-50 rounded-full flex items-center justify-center"><i class="fas fa-file-invoice text-orange-500 text-xl"></i></div>
            </div>
            <div class="stat-card rounded-xl p-4 flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-xs uppercase tracking-wide">Expired Registr.</p>
                    <p class="text-3xl font-bold text-amber-700" id="expiredRegistrations">{{ $criticalAlerts->expired_registrations ?? 0 }}</p>
                </div>
                <div class="w-10 h-10 bg-amber-50 rounded-full flex items-center justify-center"><i class="fas fa-id-card text-amber-500 text-xl"></i></div>
            </div>
            <div class="stat-card rounded-xl p-4 flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-xs uppercase tracking-wide">Needs Attention</p>
                    <p class="text-3xl font-bold text-blue-700" id="needsAttention">{{ $criticalAlerts->vehicles_needing_attention ?? 0 }}</p>
                </div>
                <div class="w-10 h-10 bg-blue-50 rounded-full flex items-center justify-center"><i class="fas fa-tools text-blue-500 text-xl"></i></div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 space-y-6">
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                    <div class="stat-card rounded-xl p-4">
                        <p class="text-gray-500 text-xs">Total Fleet</p>
                        <p class="text-2xl font-black" id="totalVehicles">{{ $totalVehicles ?? 0 }}</p>
                        <span class="text-[11px] text-green-600"><i class="fas fa-truck"></i> Units</span>
                    </div>
                    <div class="stat-card rounded-xl p-4">
                        <p class="text-gray-500 text-xs">Active Drivers</p>
                        <p class="text-2xl font-black" id="activeDrivers">{{ $activeDrivers ?? 0 }}</p>
                        <span class="text-[11px] text-gray-500">Assigned to vehicles</span>
                    </div>
                    <div class="stat-card rounded-xl p-4">
                        <p class="text-gray-500 text-xs">Unassigned Vehicles</p>
                        <p class="text-2xl font-black" id="unassignedVehicles">{{ $unassignedVehicles ?? 0 }}</p>
                        <span class="text-[11px] text-amber-600">Ready for allocation</span>
                    </div>
                    <div class="stat-card rounded-xl p-4">
                        <p class="text-gray-500 text-xs">Utilization Rate</p>
                        <p class="text-2xl font-black"><span id="utilizationRate">{{ $vehicleUtilization->utilization_rate ?? 0 }}</span>%</p>
                        <span class="text-[11px] text-blue-600"><span id="utilizationAssigned">{{ $vehicleUtilization->assigned ?? 0 }}</span>/<span id="utilizationTotal">{{ $vehicleUtilization->total ?? 0 }}</span> assigned</span>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div class="stat-card rounded-xl p-4">
                        <div class="flex justify-between items-center mb-2">
                            <h3 class="font-semibold text-gray-800"><i class="fas fa-calendar-check mr-2 text-blue-600"></i>Maintenance Due</h3>
                            <span class="badge-warning px-2 py-0.5 rounded-full text-xs font-medium"><span id="maintenanceDueVehicles">{{ $maintenanceDueVehicles ?? 0 }}</span> vehicles</span>
                        </div>
                        <p class="text-gray-600 text-sm">Upcoming service within 30 days</p>
                        <div class="mt-3 space-y-2" id="upcomingMaintenanceList">
                            @forelse($upcomingVehicleMaintenance as $m)
                                <div class="flex justify-between text-xs border-b border-slate-100 py-1">
                                    @php
                                        $v = data_get($m, 'vehicle');
                                        $plate = is_string($v) ? $v : data_get($v, 'plate_number', 'N/A');
                                        $model = data_get($v, 'make_model', 'Unknown');
                                        $nextDue = data_get($m, 'next_service_due');
                                    @endphp
                                    <span><span class="font-medium">{{ $plate }}</span> ({{ $model }})</span>
                                    <span class="text-amber-700">Due {{ $nextDue ? \Carbon\Carbon::parse($nextDue)->format('M j, Y') : 'TBD' }}</span>
                                </div>
                            @empty
                                <p class="text-gray-400 text-xs">No upcoming service</p>
                            @endforelse
                        </div>
                    </div>
                    <div class="stat-card rounded-xl p-4">
                        <h3 class="font-semibold text-gray-800 mb-2"><i class="fas fa-coins mr-2 text-green-700"></i>Maintenance Costs</h3>
                        <div class="flex justify-between border-b border-slate-100 py-2">
                            <span class="text-gray-600">This Month</span>
                            <span class="font-bold text-gray-800">GHS <span id="monthlyCost">{{ number_format($maintenanceCosts->monthly_cost ?? 0) }}</span></span>
                        </div>
                        <div class="flex justify-between pt-2">
                            <span class="text-gray-600">Year-to-Date</span>
                            <span class="font-bold text-gray-800">GHS <span id="ytdCost">{{ number_format($maintenanceCosts->ytd_cost ?? 0) }}</span></span>
                        </div>
                    </div>
                </div>

                <div class="stat-card rounded-xl p-4">
                    <h3 class="font-semibold text-gray-800 mb-3"><i class="fas fa-clock mr-2"></i>Recently Added Fleet Units</h3>
                    <div class="overflow-x-auto">
                        <table class="w-full data-table text-xs">
                            <thead>
                                <tr class="text-gray-500 border-b">
                                    <th class="text-left">Plate Number</th>
                                    <th class="text-left">Region</th>
                                    <th class="text-left">Driver</th>
                                    <th class="text-left">Date Added</th>
                                </tr>
                            </thead>
                            <tbody id="recentVehiclesBody">
                                @forelse($recentVehicles as $v)
                                    @php
                                        $regionValue = data_get($v, 'region');
                                        $regionName = is_string($regionValue) ? $regionValue : data_get($v, 'region.name', '-');
                                        $driverValue = data_get($v, 'assignedDriver');
                                        $driverName = is_string($driverValue) ? $driverValue : data_get($v, 'assignedDriver.name');
                                    @endphp
                                    <tr class="hover-row">
                                        <td class="font-medium">{{ data_get($v, 'plate_number', 'N/A') }}</td>
                                        <td>{{ $regionName ?: '-' }}</td>
                                        <td>
                                            @if($driverName)
                                                {{ $driverName }}
                                            @else
                                                <span class="text-gray-400">Unassigned</span>
                                            @endif
                                        </td>
                                        <td>{{ data_get($v, 'created_at', '-') }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="text-center text-gray-400">No recent vehicles</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div class="stat-card rounded-xl p-4">
                        <h3 class="font-semibold text-gray-800 mb-2"><i class="fas fa-chart-simple mr-2"></i>Monthly Activity</h3>
                        <div class="grid grid-cols-3 gap-2 text-center">
                            <div><p class="text-2xl font-bold text-blue-700" id="vehiclesAdded">{{ $monthlySummary->vehicles_added ?? 0 }}</p><span class="text-[11px] text-gray-500">Vehicles added</span></div>
                            <div><p class="text-2xl font-bold text-green-700" id="maintenanceCompleted">{{ $monthlySummary->maintenance_completed ?? 0 }}</p><span class="text-[11px] text-gray-500">Maintenance done</span></div>
                            <div><p class="text-2xl font-bold text-amber-700" id="driversAdded">{{ $monthlySummary->drivers_added ?? 0 }}</p><span class="text-[11px] text-gray-500">Drivers hired</span></div>
                        </div>
                    </div>
                    <div class="stat-card rounded-xl p-4">
                        <h3 class="font-semibold text-gray-800 mb-2"><i class="fas fa-tachometer-alt mr-2"></i>Fuel Efficiency (km/L)</h3>
                        <div class="space-y-2" id="fuelEfficiencyList">
                            @forelse($fuelEfficiency as $f)
                                <div class="flex justify-between items-center text-xs">
                                    <span class="font-medium">{{ $f->vehicle }}</span>
                                    <span class="bg-slate-100 px-2 py-0.5 rounded-full">{{ $f->avg_km_per_litre }} km/L</span>
                                </div>
                            @empty
                                <p class="text-gray-400 text-xs">No fuel data available</p>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>

            <div class="space-y-5">
                <!-- Maintenance Alert Widget -->
                <div class="bg-white rounded-xl shadow-sm p-6 mb-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="font-semibold text-gray-800">
                            <i class="fas fa-bell text-red-500 mr-2"></i>Vehicles Needing Maintenance
                        </h3>
                        <span id="maintenanceCount" class="bg-red-500 text-white px-2 py-1 rounded-full text-xs">0</span>
                    </div>
                    <div id="maintenanceAlertList" class="space-y-3">
                        <div class="text-center py-4 text-gray-500">Loading...</div>
                    </div>
                </div>


                <div class="stat-card rounded-xl p-4">
                    <h3 class="font-semibold text-gray-800 mb-3"><i class="fas fa-map-marker-alt mr-1 text-red-600"></i>Vehicles by Operational Region</h3>
                    <div class="space-y-2" id="vehiclesByRegionList">
@forelse($vehiclesByRegion as $r)
                            @php
                                $total = (int)($totalVehicles ?? 0);
                                $count = (int)($r->count ?? 0);
                                $pct = $total > 0 ? round(($count / $total) * 100, 1) : 0;
                            @endphp
                            <div class="flex justify-between items-center">
                                <span class="text-gray-700 text-sm">{{ $r->region_name }}</span>
                                <div class="flex items-center gap-2">
                                    <div class="w-32 h-2 bg-gray-100 rounded-full overflow-hidden"><div class="h-full bg-blue-600 rounded-full" style="width: {{ $pct }}%"></div></div>
                                    <span class="text-xs font-semibold">{{ $r->count }}</span>
                                </div>
                            </div>
                        @empty
                            <p class="text-gray-400 text-xs">No region data available</p>
                        @endforelse
                    </div>
                    <div class="mt-3 pt-2 text-xs text-gray-500 border-t border-slate-100"><i class="fas fa-database"></i> Across <span id="activeRegions">{{ $activeRegions ?? 0 }}</span> administrative zones</div>
                </div>

                <div class="stat-card rounded-xl p-4">
                    <h3 class="font-semibold text-gray-800 mb-2"><i class="fas fa-file-signature mr-1 text-amber-600"></i>Expiring Documents (60 days)</h3>
                    <div class="space-y-2" id="expiringDocumentsList">
                        @forelse($expiringDocuments as $doc)
                            <div class="flex justify-between items-center border-b border-slate-100 pb-1 text-xs">
                                <div><span class="font-medium">{{ $doc->document_type }}</span> <span class="text-gray-500">{{ optional($doc->vehicle)->plate_number ?? 'N/A' }}</span></div>
                                <span class="badge-warning px-2 py-0.5 rounded-full text-[10px]">{{ \Carbon\Carbon::parse($doc->expiry_date)->format('M j, Y') }}</span>
                            </div>
                        @empty
                            <p class="text-xs text-gray-400">No expiring documents</p>
                        @endforelse
                    </div>
                </div>

                <div class="stat-card rounded-xl p-4">
                    <h3 class="font-semibold text-gray-800 mb-2"><i class="fas fa-building mr-1"></i>Support Infrastructure</h3>
                    <div class="grid grid-cols-2 gap-2 text-center">
                        <div><p class="text-xl font-bold" id="totalAssets">{{ $totalAssets ?? 0 }}</p><span class="text-[11px] text-gray-500">Total Assets</span></div>
                        <div><p class="text-xl font-bold" id="totalOffices">{{ $totalOffices ?? 0 }}</p><span class="text-[11px] text-gray-500">Offices/Stations</span></div>
                        <div><p class="text-xl font-bold" id="maintenanceDueAssets">{{ $maintenanceDueAssets ?? 0 }}</p><span class="text-[11px] text-gray-500">Assets Pending Maint.</span></div>
                        <div><p class="text-xl font-bold" id="activeRegionsBox">{{ $activeRegions ?? 0 }}</p><span class="text-[11px] text-gray-500">Active Regions</span></div>
                    </div>
                </div>

                <div class="stat-card rounded-xl p-4">
                    <h3 class="font-semibold text-gray-800 mb-2"><i class="fas fa-chart-pie mr-1"></i>Fleet Condition Breakdown</h3>
                    <div id="fleetConditionList">
                        @forelse(($vehicleStatus ?? []) as $k => $v)
                            @php
                                $pct = ($totalVehicles ?? 0) > 0 ? round(($v / $totalVehicles) * 100, 1) : 0;
                            @endphp
                            <div class="flex justify-between text-xs py-1">
                                <span>{{ $k }}</span>
                                <span class="font-semibold">{{ $v }} units</span>
                                <div class="w-24 bg-gray-100 rounded-full h-1.5"><div class="bg-blue-600 h-1.5 rounded-full" style="width: {{ $pct }}%"></div></div>
                            </div>
                        @empty
                            <p class="text-gray-400 text-xs">No status data available</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>

    @php
        $dashboardPayload = [
            'criticalAlerts' => $criticalAlerts,
            'totalVehicles' => $totalVehicles,
            'vehiclesByRegion' => $vehiclesByRegion,
            'activeDrivers' => $activeDrivers,
            'unassignedVehicles' => $unassignedVehicles,
            'vehicleUtilization' => $vehicleUtilization,
            'maintenanceCosts' => $maintenanceCosts,
            'maintenanceDueVehicles' => $maintenanceDueVehicles,
            'upcomingVehicleMaintenance' => $upcomingVehicleMaintenance,
            'totalAssets' => $totalAssets,
            'activeRegions' => $activeRegions,
            'maintenanceDueAssets' => $maintenanceDueAssets,
            'totalOffices' => $totalOffices,
            'recentVehicles' => $recentVehicles,
            'vehicleStatus' => $vehicleStatus,
            'fuelEfficiency' => $fuelEfficiency,
            'monthlySummary' => $monthlySummary,
            'expiringDocuments' => $expiringDocuments,
            'mapData' => $mapData,
        ];
    @endphp
    <script>
        const dashboardData = @json($dashboardPayload);

        function setText(id, value) {
            const node = document.getElementById(id);
            if (node) node.textContent = value;
        }

        function formatNumber(value) {
            return Number(value || 0).toLocaleString();
        }

        function formatDate(value) {
            if (!value) return "-";
            const date = new Date(value);
            return Number.isNaN(date.getTime()) ? "-" : date.toLocaleDateString();
        }

        function renderUpcomingMaintenance(items) {
            const root = document.getElementById('upcomingMaintenanceList');
            if (!root) return;
            if (!items || !items.length) {
                root.innerHTML = '<p class="text-gray-400 text-xs">No upcoming service</p>';
                return;
            }
            root.innerHTML = items.map((m) => `
                <div class="flex justify-between text-xs border-b border-slate-100 py-1">
                    <span><span class="font-medium">${m.vehicle?.plate_number || 'N/A'}</span> (${m.vehicle?.make_model || 'Unknown'})</span>
                    <span class="text-amber-700">Due ${formatDate(m.next_service_due)}</span>
                </div>
            `).join('');
        }

        function renderRecentVehicles(items) {
            const root = document.getElementById('recentVehiclesBody');
            if (!root) return;
            if (!items || !items.length) {
                root.innerHTML = '<tr><td colspan="4" class="text-center text-gray-400">No recent vehicles</td></tr>';
                return;
            }
            root.innerHTML = items.map((v) => `
                <tr class="hover-row">
                    <td class="font-medium">${v.plate_number || 'N/A'}</td>
                    <td>${v.region?.name || '-'}</td>
                    <td>${v.assignedDriver?.name || '<span class="text-gray-400">Unassigned</span>'}</td>
                    <td>${v.created_at || '-'}</td>
                </tr>
            `).join('');
        }

        function renderFuelEfficiency(items) {
            const root = document.getElementById('fuelEfficiencyList');
            if (!root) return;
            if (!items || !items.length) {
                root.innerHTML = '<p class="text-gray-400 text-xs">No fuel data available</p>';
                return;
            }
            root.innerHTML = items.map((f) => `
                <div class="flex justify-between items-center text-xs">
                    <span class="font-medium">${f.vehicle}</span>
                    <span class="bg-slate-100 px-2 py-0.5 rounded-full">${f.avg_km_per_litre} km/L</span>
                </div>
            `).join('');
        }

        function renderVehiclesByRegion(items, totalVehiclesCount) {
            const root = document.getElementById('vehiclesByRegionList');
            if (!root) return;
            if (!items || !items.length) {
                root.innerHTML = '<p class="text-gray-400 text-xs">No region data available</p>';
                return;
            }
            root.innerHTML = items.map((r) => {
                const pct = totalVehiclesCount ? ((r.count / totalVehiclesCount) * 100) : 0;
                return `
                    <div class="flex justify-between items-center">
                        <span class="text-gray-700 text-sm">${r.region_name}</span>
                        <div class="flex items-center gap-2">
                            <div class="w-32 h-2 bg-gray-100 rounded-full overflow-hidden"><div class="h-full bg-blue-600 rounded-full" style="width:${pct}%"></div></div>
                            <span class="text-xs font-semibold">${r.count}</span>
                        </div>
                    </div>
                `;
            }).join('');
        }

        function renderExpiringDocuments(items) {
            const root = document.getElementById('expiringDocumentsList');
            if (!root) return;
            if (!items || !items.length) {
                root.innerHTML = '<p class="text-xs text-gray-400">No expiring documents</p>';
                return;
            }
            root.innerHTML = items.map((doc) => `
                <div class="flex justify-between items-center border-b border-slate-100 pb-1 text-xs">
                    <div><span class="font-medium">${doc.document_type}</span> <span class="text-gray-500">${doc.vehicle?.plate_number || 'N/A'}</span></div>
                    <span class="badge-warning px-2 py-0.5 rounded-full text-[10px]">${formatDate(doc.expiry_date)}</span>
                </div>
            `).join('');
        }

        function renderFleetCondition(items, totalVehiclesCount) {
            const root = document.getElementById('fleetConditionList');
            if (!root) return;
            const entries = Object.entries(items || {});
            if (!entries.length) {
                root.innerHTML = '<p class="text-gray-400 text-xs">No status data available</p>';
                return;
            }
            root.innerHTML = entries.map(([label, count]) => {
                const pct = totalVehiclesCount ? ((count / totalVehiclesCount) * 100) : 0;
                return `
                    <div class="flex justify-between text-xs py-1">
                        <span>${label}</span>
                        <span class="font-semibold">${count} units</span>
                        <div class="w-24 bg-gray-100 rounded-full h-1.5"><div class="bg-blue-600 h-1.5 rounded-full" style="width:${pct}%"></div></div>
                    </div>
                `;
            }).join('');
        }

        function applyDashboardData(data) {
            const totalVehiclesCount = Number(data.totalVehicles || 0);
            setText('overdueMaintenance', data.criticalAlerts?.overdue_maintenance || 0);
            setText('expiredInsurances', data.criticalAlerts?.expired_insurances || 0);
            setText('expiredRegistrations', data.criticalAlerts?.expired_registrations || 0);
            setText('needsAttention', data.criticalAlerts?.vehicles_needing_attention || 0);

            setText('totalVehicles', data.totalVehicles || 0);
            setText('activeDrivers', data.activeDrivers || 0);
            setText('unassignedVehicles', data.unassignedVehicles || 0);
            setText('utilizationRate', data.vehicleUtilization?.utilization_rate || 0);
            setText('utilizationAssigned', data.vehicleUtilization?.assigned || 0);
            setText('utilizationTotal', data.vehicleUtilization?.total || 0);

            setText('maintenanceDueVehicles', data.maintenanceDueVehicles || 0);
            setText('monthlyCost', formatNumber(data.maintenanceCosts?.monthly_cost || 0));
            setText('ytdCost', formatNumber(data.maintenanceCosts?.ytd_cost || 0));

            setText('vehiclesAdded', data.monthlySummary?.vehicles_added || 0);
            setText('maintenanceCompleted', data.monthlySummary?.maintenance_completed || 0);
            setText('driversAdded', data.monthlySummary?.drivers_added || 0);

            setText('activeRegions', data.activeRegions || 0);
            setText('activeRegionsBox', data.activeRegions || 0);
            setText('totalAssets', data.totalAssets || 0);
            setText('totalOffices', data.totalOffices || 0);
            setText('maintenanceDueAssets', data.maintenanceDueAssets || 0);

            renderUpcomingMaintenance(data.upcomingVehicleMaintenance || []);
            renderRecentVehicles(data.recentVehicles || []);
            renderFuelEfficiency(data.fuelEfficiency || []);
            renderVehiclesByRegion(data.vehiclesByRegion || [], totalVehiclesCount);
            renderExpiringDocuments(data.expiringDocuments || []);
            renderFleetCondition(data.vehicleStatus || {}, totalVehiclesCount);
        }

        async function refreshDashboard() {
            const refreshBtn = document.getElementById('refreshDashboardBtn');
            const originalIcon = refreshBtn?.innerHTML;
            if (refreshBtn) refreshBtn.innerHTML = '<i class="fas fa-spinner fa-pulse"></i>';

            try {
                const response = await fetch('{{ route("dashboard.refresh") }}', {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
                    }
                });
                const result = await response.json();
                if (result.success && result.data) {
                    applyDashboardData(result.data);
                    Swal.fire('Refreshed', 'Dashboard data updated successfully', 'success');
                } else {
                    throw new Error(result.message || 'Refresh failed');
                }
            } catch (error) {
                console.error('Refresh error:', error);
                Swal.fire('Error', 'Failed to refresh dashboard data', 'error');
            } finally {
                if (refreshBtn) refreshBtn.innerHTML = originalIcon;
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            applyDashboardData(dashboardData);
            document.getElementById('refreshDashboardBtn')?.addEventListener('click', refreshDashboard);
            document.getElementById('logoutBtn')?.addEventListener('click', () => {
                Swal.fire('Logged out', 'Session ended', 'info');
            });
        });


        function loadVehiclesNeedingMaintenance() {
            $.ajax({
                url: '{{ route("maintenance.vehicles-needing") }}',
                method: 'GET',
                success: function(response) {
                    if (response.success) {
                        $('#maintenanceCount').text(response.count);
                        
                        if (response.data.length === 0) {
                            $('#maintenanceAlertList').html(`
                                <div class="text-center py-4 text-green-600">
                                    <i class="fas fa-check-circle text-2xl mb-2"></i>
                                    <p>All vehicles are within maintenance limits</p>
                                </div>
                            `);
                        } else {
                            let html = '';
                            response.data.forEach(vehicle => {
                                let progressColor = vehicle.progress_percentage >= 100 ? 'bg-red-500' : 'bg-orange-500';
                                html += `
                                    <div class="border rounded-lg p-4 hover:shadow-md transition">
                                        <div class="flex justify-between items-start mb-2">
                                            <div>
                                                <h4 class="font-bold text-gray-800">${vehicle.registration_number}</h4>
                                                <p class="text-sm text-gray-500">${vehicle.make} ${vehicle.model}</p>
                                            </div>
                                            <span class="px-2 py-1 bg-red-100 text-red-700 rounded-full text-xs font-semibold">Alert!</span>
                                        </div>
                                        <div class="grid grid-cols-2 gap-4 text-sm mb-3">
                                            <div>
                                                <span class="text-gray-500">Driver:</span>
                                                <span class="font-medium">${vehicle.driver_name}</span>
                                            </div>
                                            <div>
                                                <span class="text-gray-500">Current Mileage:</span>
                                                <span class="font-medium">${vehicle.current_mileage.toLocaleString()} km</span>
                                            </div>
                                            <div>
                                                <span class="text-gray-500">Since Last Service:</span>
                                                <span class="font-medium text-red-600">${vehicle.mileage_since_maintenance.toLocaleString()} km</span>
                                            </div>
                                            <div>
                                                <span class="text-gray-500">Over Limit:</span>
                                                <span class="font-medium text-red-600">${vehicle.excess_mileage.toLocaleString()} km</span>
                                            </div>
                                        </div>
                                        <div class="mb-2">
                                            <div class="flex justify-between text-xs mb-1">
                                                <span>Maintenance Progress</span>
                                                <span class="font-semibold">${vehicle.progress_percentage}%</span>
                                            </div>
                                            <div class="w-full bg-gray-200 rounded-full h-2">
                                                <div class="${progressColor} h-2 rounded-full" style="width: ${Math.min(100, vehicle.progress_percentage)}%"></div>
                                            </div>
                                        </div>
                                        <div class="flex gap-2 mt-3">
                                            <button onclick="scheduleMaintenance(${vehicle.id})" class="flex-1 px-3 py-1 bg-blue-600 text-white rounded-lg text-sm hover:bg-blue-700">
                                                <i class="fas fa-calendar-alt mr-1"></i>Schedule
                                            </button>
                                            <button onclick="acknowledgeAlert(${vehicle.id})" class="flex-1 px-3 py-1 border border-gray-300 rounded-lg text-sm hover:bg-gray-50">
                                                <i class="fas fa-check mr-1"></i>Acknowledge
                                            </button>
                                        </div>
                                    </div>
                                `;
                            });
                            $('#maintenanceAlertList').html(html);
                        }
                    }
                }
            });
        }

        function scheduleMaintenance(vehicleId) {
            // Redirect to maintenance scheduling page
            window.location.href = `/maintenance/schedule/${vehicleId}`;
        }

        function acknowledgeAlert(vehicleId) {
            Swal.fire({
                title: 'Acknowledge Alert',
                text: 'Have you scheduled maintenance for this vehicle?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Yes, I will schedule it'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Call API to acknowledge
                    $.ajax({
                        url: `/maintenance/vehicle/${vehicleId}/acknowledge`,
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                        success: function() {
                            Swal.fire('Acknowledged', 'Maintenance alert acknowledged', 'success');
                            loadVehiclesNeedingMaintenance();
                        }
                    });
                }
            });
        }

        // Load on page load
        $(document).ready(function() {
            loadVehiclesNeedingMaintenance();
        });
    </script>
@endsection
