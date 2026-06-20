@extends('layouts.app')

@section('title', 'Live Vehicle Tracking')

@section('content')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
<style>
    #map { height: 750px; width: 100%; border-radius: 16px; z-index: 10; background: #f1f5f9; box-shadow: inset 0 2px 4px rgba(0,0,0,0.05); }
    .vehicle-list-item { cursor: pointer; transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1); border-left: 4px solid transparent; margin: 4px 8px; border-radius: 8px; }
    .vehicle-list-item:hover { background-color: #f1f5f9; transform: translateX(2px); }
    .vehicle-list-item.active { background-color: #f0f7ff; border-left-color: #2563eb; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }

    .pulse {
        border-radius: 50%;
        height: 20px;
        width: 20px;
        position: absolute;
        left: -2px;
        top: -2px;
        animation: pulsate 2s ease-out infinite;
        opacity: 0;
        border: 4px solid #3b82f6;
    }

    @keyframes pulsate {
        0% { transform: scale(0.1, 0.1); opacity: 0.0; }
        50% { opacity: 0.8; }
        100% { transform: scale(1.5, 1.5); opacity: 0.0; }
    }

    .car-marker-container {
        transition: all 0.8s cubic-bezier(0.4, 0, 0.2, 1);
        filter: drop-shadow(0 4px 6px rgba(0,0,0,0.15));
    }

    .marker-label {
        background: rgba(15, 23, 42, 0.9);
        color: white;
        border-radius: 4px;
        padding: 2px 8px;
        font-weight: 700;
        font-size: 11px;
        white-space: nowrap;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        border: 1px solid rgba(255,255,255,0.1);
        pointer-events: none;
    }

    /* Custom Leaflet Control Layer Styling */
    .leaflet-control-layers {
        border: none !important;
        border-radius: 12px !important;
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1) !important;
    }
    .leaflet-control-layers-expanded {
        padding: 10px !important;
        background: rgba(255,255,255,0.95) !important;
        backdrop-filter: blur(8px);
    }
</style>

<div class="space-y-6">
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <h1 class="text-3xl font-black text-slate-900 tracking-tight">Fleet Intelligence</h1>
            <p class="text-slate-500 font-medium">Real-time global positioning & telematics dashboard</p>
        </div>
        <div class="flex items-center gap-3 w-full md:w-auto">
            <div id="connectionStatus" class="flex items-center px-4 py-2 bg-indigo-50 text-indigo-700 rounded-xl text-xs font-bold border border-indigo-100 shadow-sm">
                <span class="relative flex h-3 w-3 mr-3">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-indigo-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-3 w-3 bg-indigo-600"></span>
                </span>
                LIVE TELEMETRY
            </div>
            <button onclick="refreshMap()" class="bg-white border border-slate-200 px-4 py-2 rounded-xl text-sm font-bold text-slate-700 hover:bg-slate-50 transition shadow-sm flex items-center">
                <i class="fas fa-sync-alt mr-2 text-indigo-500"></i>Sync
            </button>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
        <!-- Sidebar -->
        <div class="lg:col-span-1 flex flex-col gap-4">
            <!-- Search & Filters -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
                <div class="relative mb-3">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400">
                        <i class="fas fa-search"></i>
                    </span>
                    <input type="text" id="vehicleSearch" class="block w-full pl-10 pr-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-blue-500" placeholder="Search registration, driver...">
                </div>
                <div class="flex gap-2">
                    <select id="statusFilter" class="w-full text-xs border-gray-200 rounded-lg py-1">
                        <option value="all">All Status</option>
                        <option value="active">Active</option>
                        <option value="maintenance">In Shop</option>
                    </select>
                </div>
            </div>

            <!-- Vehicle List -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden flex flex-col flex-1" style="max-height: 550px;">
                <div class="p-3 bg-gray-50 border-b border-gray-200 flex justify-between items-center">
                    <span class="text-xs font-bold text-gray-500 uppercase tracking-wider">Online Units</span>
                    <span id="vehicleCount" class="bg-blue-100 text-blue-700 text-[10px] px-2 py-0.5 rounded-full font-bold">0</span>
                </div>
                <div id="vehicleList" class="flex-1 overflow-y-auto divide-y divide-gray-100">
                    <!-- Vehicles go here -->
                </div>
            </div>
        </div>

        <!-- Map Container -->
        <div class="lg:col-span-3 space-y-4">
            <div class="bg-white p-1 rounded-xl shadow-md border border-gray-200 relative">
                <div id="map"></div>

                <!-- Map Overlay: Vehicle Detail Card (Hidden by default) -->
                <div id="miniDetailCard" class="absolute bottom-6 left-6 z-[1000] bg-white rounded-xl shadow-2xl border border-gray-100 p-4 w-72 hidden transform transition-all duration-300">
                    <div class="flex justify-between items-start mb-3">
                        <div>
                            <h3 id="cardReg" class="text-lg font-bold text-gray-900">---</h3>
                            <p id="cardModel" class="text-xs text-gray-500">---</p>
                        </div>
                        <button onclick="closeDetailCard()" class="text-gray-400 hover:text-gray-600"><i class="fas fa-times"></i></button>
                    </div>
                    <div class="grid grid-cols-2 gap-3 mb-4">
                        <div class="bg-blue-50 p-2 rounded-lg">
                            <p class="text-[10px] text-blue-600 font-bold uppercase">Speed</p>
                            <p id="cardSpeed" class="text-lg font-black text-blue-900">0 <span class="text-[10px] font-normal">km/h</span></p>
                        </div>
                        <div class="bg-green-50 p-2 rounded-lg">
                            <p class="text-[10px] text-green-600 font-bold uppercase">Status</p>
                            <p id="cardStatus" class="text-sm font-bold text-green-900">Available</p>
                        </div>
                    </div>
                    <div class="space-y-2 text-xs text-gray-600">
                        <div class="flex justify-between">
                            <span>Driver:</span>
                            <span id="cardDriver" class="font-bold text-gray-900">---</span>
                        </div>
                        <div class="flex justify-between">
                            <span>Last Update:</span>
                            <span id="cardLastSeen" class="text-gray-400">Just now</span>
                        </div>
                    </div>
                    <div class="mt-4 pt-4 border-t border-gray-100 flex gap-2">
                        <button id="historyBtn" class="flex-1 bg-gray-900 text-white py-2 rounded-lg text-xs font-bold hover:bg-black transition">
                            <i class="fas fa-route mr-1"></i> History
                        </button>
                        <a id="detailsLink" href="#" class="flex-1 bg-white border border-gray-200 text-center py-2 rounded-lg text-xs font-bold hover:bg-gray-50 transition">
                            Details
                        </a>
                    </div>
                </div>

                <!-- History Legend (Hidden) -->
                <div id="historyLegend" class="absolute top-6 right-6 z-[1000] bg-white/90 backdrop-blur-md rounded-lg shadow-lg border border-gray-200 p-3 hidden">
                    <div class="flex items-center gap-2 mb-2">
                        <div class="h-1 w-8 bg-blue-500 rounded"></div>
                        <span class="text-[10px] font-bold uppercase text-gray-500">Route History (24h)</span>
                    </div>
                    <button onclick="clearHistory()" class="w-full text-[10px] bg-red-50 text-red-600 font-bold py-1 rounded">Stop Playback</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<script>
    let map;
    let markers = {};
    let vehiclesData = [];
    let historyPolyline = null;
    let updateInterval;
    let selectedVehicleId = null;

    document.addEventListener('DOMContentLoaded', function() {
        initMap();
        fetchData();

        // Polling every 5 seconds for smooth updates
        updateInterval = setInterval(fetchData, 5000);

        document.getElementById('vehicleSearch').addEventListener('input', e => filterVehicles());
        document.getElementById('statusFilter').addEventListener('change', e => filterVehicles());
    });

    function initMap() {
        const street = L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
            attribution: '&copy; CARTO'
        });

        const dark = L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
            attribution: '&copy; CARTO'
        });

        const satellite = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
            attribution: 'Tiles &copy; Esri'
        });

        map = L.map('map', {
            zoomControl: false,
            layers: [street]
        }).setView([5.6037, -0.1870], 13);

        const baseMaps = {
            "<span class='text-xs font-bold'><i class='fas fa-map mr-1'></i> Street</span>": street,
            "<span class='text-xs font-bold'><i class='fas fa-moon mr-1'></i> Dark</span>": dark,
            "<span class='text-xs font-bold'><i class='fas fa-satellite mr-1'></i> Satellite</span>": satellite
        };

        L.control.layers(baseMaps, null, { position: 'topright' }).addTo(map);
        L.control.zoom({ position: 'bottomright' }).addTo(map);
    }

    function fetchData() {
        fetch('{{ route("vehicles.tracking.data") }}')
            .then(res => res.json())
            .then(result => {
                if (result.success) {
                    vehiclesData = result.data;
                    updateUI();
                }
            })
            .catch(err => console.error("Update failed", err));
    }

    function updateUI() {
        const query = document.getElementById('vehicleSearch').value.toLowerCase();
        const status = document.getElementById('statusFilter').value;

        const filtered = vehiclesData.filter(v => {
            const matchesSearch = v.registration_number.toLowerCase().includes(query) ||
                                (v.assigned_driver && v.assigned_driver.name.toLowerCase().includes(query));
            const matchesStatus = status === 'all' || v.status === status;
            return matchesSearch && matchesStatus;
        });

        document.getElementById('vehicleCount').innerText = filtered.length;
        updateVehicleList(filtered);
        updateMarkers(filtered);

        if (selectedVehicleId) {
            const selected = vehiclesData.find(v => v.id === selectedVehicleId);
            if (selected) updateDetailCard(selected);
        }
    }

    function updateVehicleList(vehicles) {
        const container = document.getElementById('vehicleList');
        container.innerHTML = vehicles.map(v => `
            <div class="vehicle-list-item p-4 shadow-sm border border-slate-100 ${selectedVehicleId === v.id ? 'active ring-2 ring-indigo-500 ring-inset' : ''}" onclick="focusVehicle(${v.id})">
                <div class="flex justify-between items-start mb-2">
                    <span class="font-black text-slate-900 text-base">${v.registration_number}</span>
                    <span class="flex items-center text-[10px] font-bold px-2 py-0.5 rounded-full ${v.is_on_trip ? 'bg-indigo-100 text-indigo-700' : 'bg-emerald-100 text-emerald-700'}">
                        <span class="h-1.5 w-1.5 rounded-full mr-1.5 ${v.is_on_trip ? 'bg-indigo-500' : 'bg-emerald-500'}"></span>
                        ${v.is_on_trip ? 'ON TRIP' : 'IDLE'}
                    </span>
                </div>
                <div class="flex justify-between items-center text-xs mb-3">
                    <span class="text-slate-500 font-medium">${v.make} ${v.model}</span>
                    <div class="text-slate-900 font-black bg-slate-100 px-2 py-0.5 rounded">${v.speed} <span class="text-[9px] font-normal text-slate-500">km/h</span></div>
                </div>
                <div class="flex items-center justify-between">
                    <div class="flex items-center text-[11px] font-semibold text-slate-600">
                        <div class="relative mr-2">
                             <i class="fas fa-user-circle text-lg ${v.assigned_driver && v.assigned_driver.online_status === 'online' ? 'text-emerald-500' : 'text-slate-300'}"></i>
                             ${v.assigned_driver && v.assigned_driver.online_status === 'online' ? '<span class="absolute -bottom-0.5 -right-0.5 w-2 h-2 bg-emerald-500 border border-white rounded-full"></span>' : ''}
                        </div>
                        <span class="truncate max-w-[100px]">${v.assigned_driver ? v.assigned_driver.name : 'No Driver'}</span>
                    </div>
                    <div class="flex gap-1">
                        <span class="w-1.5 h-1.5 rounded-full bg-slate-200"></span>
                        <span class="w-1.5 h-1.5 rounded-full bg-slate-200"></span>
                        <span class="w-1.5 h-1.5 rounded-full bg-slate-200"></span>
                    </div>
                </div>
            </div>
        `).join('');
    }

    function updateMarkers(vehicles) {
        vehicles.forEach(v => {
            if (!v.current_latitude) return;

            const pos = [v.current_latitude, v.current_longitude];
            const color = v.is_on_trip ? '#6366f1' : '#10b981';

            if (markers[v.id]) {
                markers[v.id].setLatLng(pos);
                const markerEl = markers[v.id].getElement();
                if (markerEl) {
                    const icon = markerEl.querySelector('.car-marker-container');
                    if (icon) icon.style.transform = `rotate(${v.heading}deg)`;

                    const svg = markerEl.querySelector('svg path');
                    if (svg) svg.setAttribute('fill', color);
                }
            } else {
                const icon = L.divIcon({
                    className: 'custom-div-icon',
                    html: `
                        <div class="relative">
                            <div class="pulse" style="border-color: ${color}"></div>
                            <div class="car-marker-container" style="transform: rotate(${v.heading}deg)">
                                <svg width="40" height="40" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M50,10 L85,90 L50,75 L15,90 Z" fill="${color}" stroke="#ffffff" stroke-width="6" stroke-linejoin="round" />
                                </svg>
                            </div>
                            <div class="absolute -top-10 left-1/2 -translate-x-1/2 marker-label">${v.registration_number}</div>
                        </div>
                    `,
                    iconSize: [40, 40],
                    iconAnchor: [20, 20]
                });

                markers[v.id] = L.marker(pos, { icon: icon }).addTo(map)
                    .on('click', () => focusVehicle(v.id));
            }
        });
    }

    function focusVehicle(id) {
        selectedVehicleId = id;
        const vehicle = vehiclesData.find(v => v.id === id);
        if (!vehicle) return;

        map.flyTo([vehicle.current_latitude, vehicle.current_longitude], 16, {
            duration: 1.5
        });

        updateDetailCard(vehicle);
        updateUI(); // Refresh list styles
    }

    function updateDetailCard(v) {
        const card = document.getElementById('miniDetailCard');
        card.classList.remove('hidden');

        document.getElementById('cardReg').innerText = v.registration_number;
        document.getElementById('cardModel').innerText = `${v.make} ${v.model}`;
        document.getElementById('cardSpeed').innerHTML = `${v.speed} <span class="text-[10px] font-normal">km/h</span>`;
        document.getElementById('cardStatus').innerText = v.is_on_trip ? 'On Trip' : 'Available';
        document.getElementById('cardStatus').parentElement.className = `p-2 rounded-lg ${v.is_on_trip ? 'bg-blue-50 text-blue-900' : 'bg-green-50 text-green-900'}`;
        document.getElementById('cardDriver').innerText = v.assigned_driver ? v.assigned_driver.name : 'Unassigned';
        document.getElementById('detailsLink').href = `/vehicles/${v.id}`;

        document.getElementById('historyBtn').onclick = () => loadHistory(v.id);
    }

    function closeDetailCard() {
        document.getElementById('miniDetailCard').classList.add('hidden');
        selectedVehicleId = null;
        updateUI();
        clearHistory();
    }

    function loadHistory(id) {
        fetch(`/vehicles/tracking/${id}/history?hours=24`)
            .then(res => res.json())
            .then(result => {
                if (result.success && result.data.length > 1) {
                    clearHistory();
                    const path = result.data.map(p => [p.latitude, p.longitude]);

                    historyPolyline = L.polyline(path, {
                        color: '#3b82f6',
                        weight: 4,
                        opacity: 0.6,
                        dashArray: '10, 10',
                        lineJoin: 'round'
                    }).addTo(map);

                    map.fitBounds(historyPolyline.getBounds(), { padding: [50, 50] });
                    document.getElementById('historyLegend').classList.remove('hidden');
                } else {
                    alert("No historical data found for this period.");
                }
            });
    }

    function clearHistory() {
        if (historyPolyline) {
            map.removeLayer(historyPolyline);
            historyPolyline = null;
        }
        document.getElementById('historyLegend').classList.add('hidden');
    }

    function refreshMap() {
        fetchData();
    }

    function filterVehicles() {
        updateUI();
    }
</script>
@endsection
