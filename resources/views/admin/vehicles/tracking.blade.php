@extends('layouts.app')

@section('title', 'Live Vehicle Tracking')

@section('content')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
<style>
    #map { height: 700px; width: 100%; border-radius: 12px; z-index: 10; background: #f8fafc; }
    .vehicle-list-item { cursor: pointer; transition: all 0.2s; border-left: 4px solid transparent; }
    .vehicle-list-item:hover { background-color: #f8fafc; }
    .vehicle-list-item.active { background-color: #eff6ff; border-left-color: #3b82f6; }

    .status-active { color: #10b981; }
    .status-inactive { color: #6b7280; }
    .status-maintenance { color: #f59e0b; }

    .pulse {
        border-radius: 50%;
        height: 14px;
        width: 14px;
        position: absolute;
        left: -2px;
        top: -2px;
        animation: pulsate 2s ease-out infinite;
        opacity: 0;
        border: 3px solid #3b82f6;
    }

    @keyframes pulsate {
        0% { transform: scale(0.1, 0.1); opacity: 0.0; }
        50% { opacity: 1.0; }
        100% { transform: scale(1.2, 1.2); opacity: 0.0; }
    }

    .uber-marker {
        transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .marker-label {
        background: white;
        border: 1px solid #e2e8f0;
        border-radius: 4px;
        padding: 2px 6px;
        font-weight: 600;
        font-size: 10px;
        white-space: nowrap;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
</style>

<div class="space-y-6">
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Fleet Command Center</h1>
            <p class="text-gray-500 text-sm">Real-time telematics and live vehicle positioning</p>
        </div>
        <div class="flex gap-3">
            <div id="connectionStatus" class="flex items-center px-3 py-1 bg-green-100 text-green-700 rounded-full text-xs font-medium">
                <span class="relative flex h-2 w-2 mr-2">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-2 w-2 bg-green-500"></span>
                </span>
                LIVE UPDATING
            </div>
            <button onclick="refreshMap()" class="bg-white border border-gray-300 px-4 py-2 rounded-lg text-sm font-medium hover:bg-gray-50 transition shadow-sm">
                <i class="fas fa-sync-alt mr-2"></i>Sync
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
        map = L.map('map', {
            zoomControl: false
        }).setView([5.6037, -0.1870], 13);

        L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors &copy; <a href="https://carto.com/attributions">CARTO</a>',
            subdomains: 'abcd',
            maxZoom: 20
        }).addTo(map);

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
            <div class="vehicle-list-item p-3 ${selectedVehicleId === v.id ? 'active' : ''}" onclick="focusVehicle(${v.id})">
                <div class="flex justify-between items-start mb-1">
                    <span class="font-bold text-gray-900">${v.registration_number}</span>
                    <span class="text-[10px] font-black ${v.is_on_trip ? 'text-blue-600' : 'text-green-500'} uppercase">
                        ${v.is_on_trip ? 'On Trip' : 'Available'}
                    </span>
                </div>
                <div class="flex justify-between items-center text-[11px] mb-1">
                    <span class="text-gray-500">${v.make} ${v.model}</span>
                    <span class="text-gray-900 font-bold">${v.speed} km/h</span>
                </div>
                <div class="flex items-center text-[10px]">
                    <i class="fas fa-user-circle mr-1 ${v.assigned_driver && v.assigned_driver.online_status === 'online' ? 'text-green-500' : 'text-gray-400'}"></i>
                    <span class="text-gray-400 truncate">${v.assigned_driver ? v.assigned_driver.name : 'Unassigned'}</span>
                </div>
            </div>
        `).join('');
    }

    function updateMarkers(vehicles) {
        vehicles.forEach(v => {
            if (!v.current_latitude) return;

            const pos = [v.current_latitude, v.current_longitude];

            if (markers[v.id]) {
                // Smooth transition
                markers[v.id].setLatLng(pos);
                // Update rotation if we had a heading (simulated)
                const markerEl = markers[v.id].getElement();
                if (markerEl) {
                    const icon = markerEl.querySelector('.uber-marker');
                    if (icon) icon.style.transform = `rotate(${v.heading}deg)`;
                }
            } else {
                const icon = L.divIcon({
                    className: 'custom-div-icon',
                    html: `
                        <div class="relative">
                            <div class="pulse"></div>
                            <div class="uber-marker" style="transform: rotate(${v.heading}deg)">
                                <svg width="36" height="36" viewBox="0 0 100 100">
                                    <circle cx="50" cy="50" r="40" fill="${v.is_on_trip ? '#2563eb' : '#10b981'}" stroke="white" stroke-width="8" />
                                    <path d="M50 20 L70 70 L50 60 L30 70 Z" fill="white" />
                                </svg>
                            </div>
                            <div class="absolute -top-8 left-1/2 -translate-x-1/2 marker-label">${v.registration_number}</div>
                        </div>
                    `,
                    iconSize: [36, 36],
                    iconAnchor: [18, 18]
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
