@extends('layouts.app')

@section('title', 'Live Vehicle Tracking')

@section('content')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
<style>
    #map { height: 700px; width: 100%; border-radius: 12px; z-index: 10; background: #f8fafc; }
    .vehicle-list-item { cursor: pointer; transition: all 0.2s; border-left: 4px solid transparent; }
    .vehicle-list-item:hover { background-color: #f8fafc; }
    .vehicle-list-item.active { background-color: #eff6ff; border-left-color: #3b82f6; }

    /* Map Controls Customization */
    .leaflet-control-layers { border: none !important; border-radius: 12px !important; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1) !important; overflow: hidden; }
    .leaflet-control-layers-list { padding: 8px; font-family: 'Inter', sans-serif; font-size: 11px; font-weight: 600; }

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
        transition: all 4.8s linear; /* Slightly less than polling interval for smoothness */
        filter: drop-shadow(0 4px 6px rgba(0,0,0,0.2));
    }

    .uber-marker svg {
        transition: transform 0.5s ease-out;
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
        <div class="flex items-center gap-3">
            <div id="themeSwitcher" class="bg-white border border-gray-200 p-1 rounded-lg flex shadow-sm">
                <button onclick="setMapTheme('light')" id="btn-theme-light" class="px-3 py-1.5 rounded-md text-[10px] font-bold transition theme-btn-active">LIGHT</button>
                <button onclick="setMapTheme('dark')" id="btn-theme-dark" class="px-3 py-1.5 rounded-md text-[10px] font-bold transition text-gray-400 hover:text-gray-600">DARK</button>
                <button onclick="setMapTheme('satellite')" id="btn-theme-satellite" class="px-3 py-1.5 rounded-md text-[10px] font-bold transition text-gray-400 hover:text-gray-600">SATELLITE</button>
            </div>
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
                    <div class="grid grid-cols-2 gap-3 mb-3">
                        <div class="bg-blue-50 p-2 rounded-lg border border-blue-100">
                            <p class="text-[9px] text-blue-600 font-bold uppercase">Speed</p>
                            <p id="cardSpeed" class="text-lg font-black text-blue-900">0 <span class="text-[10px] font-normal">km/h</span></p>
                        </div>
                        <div class="bg-emerald-50 p-2 rounded-lg border border-emerald-100">
                            <p class="text-[9px] text-emerald-600 font-bold uppercase">Ignition</p>
                            <p id="cardIgnition" class="text-sm font-bold text-emerald-900">On</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3 mb-4">
                        <div class="bg-gray-50 p-2 rounded-lg border border-gray-100">
                            <p class="text-[9px] text-gray-500 font-bold uppercase">Fuel Level</p>
                            <div class="flex items-center gap-2 mt-0.5">
                                <div class="flex-1 h-1.5 bg-gray-200 rounded-full overflow-hidden">
                                    <div id="cardFuelBar" class="h-full bg-orange-500" style="width: 0%"></div>
                                </div>
                                <span id="cardFuelText" class="text-[10px] font-bold text-gray-700">0%</span>
                            </div>
                        </div>
                        <div class="bg-gray-50 p-2 rounded-lg border border-gray-100">
                            <p class="text-[9px] text-gray-500 font-bold uppercase">Battery</p>
                            <p id="cardBattery" class="text-sm font-bold text-gray-800">12.4V</p>
                        </div>
                    </div>
                    <div class="space-y-2 text-xs text-gray-600">
                        <div class="flex justify-between">
                            <span>Driver:</span>
                            <span id="cardDriver" class="font-bold text-gray-900">---</span>
                        </div>
                    <div class="flex justify-between">
                        <span>Est. Arrival:</span>
                        <span id="cardETA" class="font-bold text-gray-900">---</span>
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
    let tileLayers = {};
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
            zoomControl: false,
            attributionControl: false
        }).setView([5.6037, -0.1870], 13);

        tileLayers.light = L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
            subdomains: 'abcd',
            maxZoom: 20
        });

        tileLayers.dark = L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
            subdomains: 'abcd',
            maxZoom: 20
        });

        tileLayers.satellite = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
            maxZoom: 19
        });

        tileLayers.light.addTo(map);
        L.control.zoom({ position: 'bottomright' }).addTo(map);
    }

    function setMapTheme(theme) {
        Object.values(tileLayers).forEach(layer => map.removeLayer(layer));
        tileLayers[theme].addTo(map);

        // Update buttons
        ['light', 'dark', 'satellite'].forEach(t => {
            const btn = document.getElementById(`btn-theme-${t}`);
            if (t === theme) {
                btn.className = 'px-3 py-1.5 rounded-md text-[10px] font-bold transition bg-blue-600 text-white shadow-sm';
            } else {
                btn.className = 'px-3 py-1.5 rounded-md text-[10px] font-bold transition text-gray-400 hover:text-gray-600';
            }
        });
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
                    <div class="flex flex-col">
                        <span class="font-bold text-gray-900 leading-tight">${v.registration_number}</span>
                        <span class="text-[10px] text-gray-500 uppercase font-medium">${v.make} ${v.model}</span>
                    </div>
                    <div class="flex flex-col items-end">
                        <span class="text-[10px] font-black ${v.ignition === 'on' ? 'text-emerald-600' : 'text-gray-400'} uppercase">
                            ${v.ignition === 'on' ? 'Ignition On' : 'Ignition Off'}
                        </span>
                        <span class="text-[11px] font-bold text-gray-900">${v.speed} km/h</span>
                    </div>
                </div>

                <div class="mt-2 flex items-center justify-between">
                    <div class="flex items-center text-[10px]">
                        <i class="fas fa-user-circle mr-1 ${v.assigned_driver && v.assigned_driver.online_status === 'online' ? 'text-green-500' : 'text-gray-400'}"></i>
                        <span class="text-gray-400 truncate max-w-[80px]">${v.assigned_driver ? v.assigned_driver.name : 'Unassigned'}</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <div class="w-12 h-1 bg-gray-100 rounded-full overflow-hidden">
                            <div class="h-full ${v.fuel_level < 20 ? 'bg-red-500' : 'bg-emerald-500'}" style="width: ${v.fuel_level}%"></div>
                        </div>
                        <span class="text-[9px] font-bold text-gray-500">${v.fuel_level}%</span>
                    </div>
                </div>
            </div>
        `).join('');
    }

    function updateMarkers(vehicles) {
        vehicles.forEach(v => {
            if (!v.current_latitude) return;

            const pos = [v.current_latitude, v.current_longitude];
            const markerColor = v.is_on_trip ? '#2563eb' : '#10b981';

            if (markers[v.id]) {
                // Smooth transition
                markers[v.id].setLatLng(pos);
                // Update rotation and color
                const markerEl = markers[v.id].getElement();
                if (markerEl) {
                    const iconContainer = markerEl.querySelector('.uber-marker');
                    if (iconContainer) iconContainer.style.transform = `rotate(${v.heading}deg)`;

                    const carBody = markerEl.querySelector('.car-body');
                    if (carBody) carBody.setAttribute('fill', markerColor);
                }
            } else {
                const icon = L.divIcon({
                    className: 'custom-div-icon',
                    html: `
                        <div class="relative">
                            <div class="pulse" style="border-color: ${markerColor}"></div>
                            <div class="uber-marker" style="transform: rotate(${v.heading}deg)">
                                <svg width="40" height="40" viewBox="0 0 100 100" preserveAspectRatio="xMidYMid meet">
                                    <!-- Car Shadow -->
                                    <ellipse cx="50" cy="55" rx="30" ry="15" fill="rgba(0,0,0,0.1)" />
                                    <!-- Car Body -->
                                    <path class="car-body" d="M30,20 L70,20 C85,20 90,30 90,50 C90,70 85,80 70,80 L30,80 C15,80 10,70 10,50 C10,30 15,20 30,20 Z" fill="${markerColor}" stroke="white" stroke-width="4" />
                                    <!-- Windshield -->
                                    <path d="M35,30 L65,30 C70,30 72,35 72,40 L72,40 C72,45 70,50 65,50 L35,50 C30,50 28,45 28,40 L28,40 C28,35 30,30 35,30 Z" fill="rgba(255,255,255,0.3)" />
                                    <!-- Roof Line -->
                                    <path d="M30,55 L70,55" stroke="rgba(255,255,255,0.2)" stroke-width="2" />
                                    <!-- Headlights -->
                                    <circle cx="82" cy="35" r="5" fill="white" opacity="0.8" />
                                    <circle cx="82" cy="65" r="5" fill="white" opacity="0.8" />
                                </svg>
                            </div>
                            <div class="absolute -top-8 left-1/2 -translate-x-1/2 marker-label">${v.registration_number}</div>
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

        const ignitionEl = document.getElementById('cardIgnition');
        ignitionEl.innerText = v.ignition === 'on' ? 'Ignition On' : 'Ignition Off';
        ignitionEl.parentElement.className = `p-2 rounded-lg border ${v.ignition === 'on' ? 'bg-emerald-50 text-emerald-900 border-emerald-100' : 'bg-gray-100 text-gray-600 border-gray-200'}`;

        document.getElementById('cardFuelText').innerText = `${v.fuel_level}%`;
        const fuelBar = document.getElementById('cardFuelBar');
        fuelBar.style.width = `${v.fuel_level}%`;
        fuelBar.className = `h-full ${v.fuel_level < 20 ? 'bg-red-500' : (v.fuel_level < 50 ? 'bg-orange-500' : 'bg-green-500')}`;

        document.getElementById('cardBattery').innerText = `${v.battery}V`;
        document.getElementById('cardETA').innerText = v.is_on_trip ? `${v.eta} mins` : 'N/A';

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
