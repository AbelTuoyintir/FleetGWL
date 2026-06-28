@extends('layouts.app')

@section('title', 'Live Vehicle Tracking')

@section('content')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
<style>
    #map { height: 700px; width: 100%; border-radius: 12px; z-index: 10; background: #f8fafc; }
    .vehicle-list-item { cursor: pointer; transition: all 0.2s; border-left: 4px solid transparent; }
    .vehicle-list-item:hover { background-color: #f8fafc; }
    .vehicle-list-item.active { background-color: #eff6ff; border-left-color: #3b82f6; }

    .leaflet-marker-icon {
        transition: transform 0.8s linear;
    }
    .car-marker {
        transition: transform 0.4s ease-in-out;
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

    .focus-marker {
        animation: focus-ring 1.5s infinite;
    }

    @keyframes focus-ring {
        0% { transform: scale(1); opacity: 1; border: 2px solid #3b82f6; }
        100% { transform: scale(2.5); opacity: 0; border: 4px solid #3b82f6; }
    }

    .focus-indicator {
        position: absolute;
        width: 40px;
        height: 40px;
        border-radius: 50%;
        pointer-events: none;
        z-index: -1;
    }

    .pulse {
        display: block;
        width: 10px;
        height: 10px;
        border-radius: 50%;
        background: #10b981;
        cursor: pointer;
        box-shadow: 0 0 0 rgba(16, 185, 129, 0.4);
        animation: pulse 2s infinite;
    }

    @keyframes pulse {
        0% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7); }
        70% { box-shadow: 0 0 0 10px rgba(16, 185, 129, 0); }
        100% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
    }

    .pulse-blue { background: #3b82f6; box-shadow: 0 0 0 rgba(59, 130, 246, 0.4); animation: pulse-blue 2s infinite; }
    @keyframes pulse-blue {
        0% { box-shadow: 0 0 0 0 rgba(59, 130, 246, 0.7); }
        70% { box-shadow: 0 0 0 10px rgba(59, 130, 246, 0); }
        100% { box-shadow: 0 0 0 0 rgba(59, 130, 246, 0); }
    }

    .live-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background-color: #ef4444;
        display: inline-block;
        margin-right: 4px;
        animation: blink 1s ease-in-out infinite;
    }

    @keyframes blink {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.3; }
    }

    .sidebar-moving-pulse {
        position: relative;
    }
    .sidebar-moving-pulse::after {
        content: '';
        position: absolute;
        top: 0; left: 0; right: 0; bottom: 0;
        background: rgba(59, 130, 246, 0.05);
        animation: sidebar-pulse 2s infinite;
        pointer-events: none;
    }

    @keyframes sidebar-pulse {
        0% { opacity: 0.5; }
        50% { opacity: 1; }
        100% { opacity: 0.5; }
    }
</style>

<div class="space-y-6">
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Fleet Command Center</h1>
            <p class="text-gray-500 text-sm">Real-time telematics and live vehicle positioning</p>
        </div>
        <div class="flex flex-wrap gap-3">
            <div id="connectionStatus" class="flex items-center px-3 py-1 bg-green-100 text-green-700 rounded-full text-xs font-medium">
                <span class="relative flex h-2 w-2 mr-2">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-2 w-2 bg-green-500"></span>
                </span>
                LIVE UPDATING
            </div>
            <div class="flex bg-white border border-gray-200 rounded-lg p-1 shadow-sm">
                <button onclick="setMapTheme('light')" class="px-3 py-1 text-xs font-medium rounded-md hover:bg-gray-100 transition" id="theme-light">Light</button>
                <button onclick="setMapTheme('dark')" class="px-3 py-1 text-xs font-medium rounded-md hover:bg-gray-100 transition" id="theme-dark">Dark</button>
                <button onclick="setMapTheme('satellite')" class="px-3 py-1 text-xs font-medium rounded-md hover:bg-gray-100 transition" id="theme-satellite">Satellite</button>
            </div>
            <button onclick="fitAllVehicles()" class="bg-blue-50 border border-blue-200 px-4 py-2 rounded-lg text-sm font-bold text-blue-700 hover:bg-blue-100 transition shadow-sm">
                <i class="fas fa-compress-alt mr-2"></i>Fit All
            </button>
            <button onclick="refreshMap()" class="bg-white border border-gray-300 px-4 py-2 rounded-lg text-sm font-medium hover:bg-gray-50 transition shadow-sm">
                <i class="fas fa-sync-alt mr-2"></i>Sync
            </button>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
        <!-- Sidebar -->
        <div class="lg:col-span-1 flex flex-col gap-4">
            <!-- Summary Stats -->
            <div class="grid grid-cols-3 gap-2">
                <div class="bg-white p-2 rounded-lg border border-gray-200 shadow-sm text-center">
                    <p class="text-[10px] font-bold text-blue-600 uppercase">Moving</p>
                    <p id="statMoving" class="text-lg font-black text-gray-900">0</p>
                </div>
                <div class="bg-white p-2 rounded-lg border border-gray-200 shadow-sm text-center">
                    <p class="text-[10px] font-bold text-emerald-600 uppercase">Idle</p>
                    <p id="statIdle" class="text-lg font-black text-gray-900">0</p>
                </div>
                <div class="bg-white p-2 rounded-lg border border-gray-200 shadow-sm text-center">
                    <p class="text-[10px] font-bold text-gray-400 uppercase">Offline</p>
                    <p id="statOffline" class="text-lg font-black text-gray-900">0</p>
                </div>
            </div>

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
                        <option value="all">All Units</option>
                        <option value="moving">Moving Only</option>
                        <option value="idle">Idle Only</option>
                        <option value="offline">Offline Only</option>
                    </select>
                </div>
            </div>

            <!-- Vehicle List -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden flex flex-col flex-1" style="max-height: 500px;">
                <div class="p-3 bg-gray-50 border-b border-gray-200 flex justify-between items-center">
                    <span class="text-xs font-bold text-gray-500 uppercase tracking-wider">Fleet List</span>
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

                <!-- Live Activity Ticker -->
                <div id="activityTicker" class="absolute top-6 right-6 z-[1000] w-64 space-y-2 pointer-events-none">
                    <!-- Ticker items go here -->
                </div>

                <!-- Map Overlay: Vehicle Detail Card -->
                <div id="miniDetailCard" class="absolute bottom-6 left-6 z-[1000] bg-white rounded-2xl shadow-2xl border border-gray-100 w-80 hidden overflow-hidden transform transition-all duration-500 ease-out translate-y-4 opacity-0">
                    <div id="cardHeader" class="bg-gray-900 p-5 text-white">
                        <div class="flex justify-between items-start">
                            <div>
                                <h3 id="cardReg" class="text-2xl font-black tracking-tight leading-none mb-1">---</h3>
                                <p id="cardModel" class="text-[10px] uppercase tracking-widest opacity-60 font-bold">---</p>
                            </div>
                            <button onclick="closeDetailCard()" class="text-white/40 hover:text-white transition"><i class="fas fa-times"></i></button>
                        </div>
                    </div>

                    <div class="p-5">
                        <div class="flex items-end gap-3 mb-6">
                            <div class="flex-1">
                                <p class="text-[10px] text-gray-400 font-bold uppercase tracking-widest mb-1">Current Speed</p>
                                <div class="flex items-baseline gap-1">
                                    <span id="cardSpeed" class="text-5xl font-black text-gray-900 leading-none">0</span>
                                    <span class="text-gray-400 font-bold">km/h</span>
                                </div>
                            </div>
                            <div id="cardIgnitionBadge" class="px-3 py-1.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-emerald-100 text-emerald-700">
                                Ignition On
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4 mb-6">
                            <div class="space-y-1.5">
                                <div class="flex justify-between text-[10px] font-bold uppercase tracking-wider">
                                    <span class="text-gray-400">Fuel Level</span>
                                    <span id="cardFuelText" class="text-gray-900">0%</span>
                                </div>
                                <div class="h-2 w-full bg-gray-100 rounded-full overflow-hidden">
                                    <div id="cardFuelBar" class="h-full bg-blue-600 transition-all duration-1000" style="width: 0%"></div>
                                </div>
                            </div>
                            <div class="space-y-1.5">
                                <div class="flex justify-between text-[10px] font-bold uppercase tracking-wider">
                                    <span class="text-gray-400">Battery</span>
                                    <span id="cardBattery" class="text-gray-900">12.4V</span>
                                </div>
                                <div class="h-2 w-full bg-gray-100 rounded-full overflow-hidden">
                                    <div class="h-full bg-emerald-500" style="width: 85%"></div>
                                </div>
                            </div>
                        </div>

                        <div class="bg-gray-50 rounded-xl p-4 space-y-3 mb-6 border border-gray-100">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-full bg-white shadow-sm flex items-center justify-center text-gray-400">
                                    <i class="fas fa-user-tie"></i>
                                </div>
                                <div class="flex-1">
                                    <p class="text-[9px] text-gray-400 font-bold uppercase tracking-wider">Assigned Driver</p>
                                    <p id="cardDriver" class="text-sm font-bold text-gray-900 leading-none">---</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-full bg-white shadow-sm flex items-center justify-center text-gray-400">
                                    <i class="fas fa-map-marker-alt"></i>
                                </div>
                                <div class="flex-1">
                                    <p class="text-[9px] text-gray-400 font-bold uppercase tracking-wider">Last Reported Location</p>
                                    <p id="cardLastSeen" class="text-sm font-bold text-gray-900 leading-none">Just now</p>
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <button id="historyBtn" class="bg-gray-100 text-gray-900 py-3 rounded-xl text-xs font-black uppercase tracking-wider hover:bg-gray-200 transition">
                                <i class="fas fa-route mr-2"></i>History
                            </button>
                            <button id="followBtn" onclick="toggleFollow()" class="bg-blue-600 text-white py-3 rounded-xl text-xs font-black uppercase tracking-wider hover:bg-blue-700 transition shadow-lg shadow-blue-200">
                                <i class="fas fa-crosshairs mr-2"></i>Follow
                            </button>
                        </div>
                        <a id="detailsLink" href="#" class="block w-full mt-3 text-center text-[10px] font-bold text-gray-400 uppercase tracking-widest hover:text-blue-600 transition">
                            View Detailed Logistics Profile
                        </a>
                    </div>
                </div>

                <!-- History Legend -->
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
    let following = false;
    let activeTileLayer;

    const mapThemes = {
        light: 'https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png',
        dark: 'https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png',
        satellite: 'https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}'
    };

    document.addEventListener('DOMContentLoaded', function() {
        initMap();
        fetchData().then(() => {
            // Auto-focus if ID is in URL
            const urlParams = new URLSearchParams(window.location.search);
            const focusId = urlParams.get('id');
            if (focusId) {
                setTimeout(() => focusVehicle(parseInt(focusId)), 1000);
            }
        });

        updateInterval = setInterval(fetchData, 5000);

        document.getElementById('vehicleSearch').addEventListener('input', e => filterVehicles());
        document.getElementById('statusFilter').addEventListener('change', e => filterVehicles());
    });

    function initMap() {
        map = L.map('map', {
            zoomControl: false,
            preferCanvas: true
        }).setView([5.6037, -0.1870], 13);

        setMapTheme('light');

        L.control.zoom({ position: 'bottomright' }).addTo(map);
    }

    function setMapTheme(theme) {
        if (activeTileLayer) map.removeLayer(activeTileLayer);

        activeTileLayer = L.tileLayer(mapThemes[theme], {
            attribution: '&copy; OpenStreetMap contributors',
            maxZoom: 20
        }).addTo(map);

        // Update UI buttons
        ['light', 'dark', 'satellite'].forEach(t => {
            const btn = document.getElementById(`theme-${t}`);
            if (btn) {
                if (t === theme) {
                    btn.classList.add('bg-blue-600', 'text-white', 'shadow-sm');
                    btn.classList.remove('hover:bg-gray-100');
                } else {
                    btn.classList.remove('bg-blue-600', 'text-white', 'shadow-sm');
                    btn.classList.add('hover:bg-gray-100');
                }
            }
        });
    }

    function fetchData() {
        const statusIndicator = document.getElementById('connectionStatus');

        const oldDataMap = new Map(vehiclesData.map(v => [v.id, { ...v }]));

        return fetch('{{ route("vehicles.tracking.data") }}')
            .then(res => {
                if (!res.ok) throw new Error('Network response was not ok');
                return res.json();
            })
            .then(result => {
                if (result.success) {
                    const newData = result.data;

                    // Detect changes for activity ticker
                    newData.forEach(newV => {
                        const oldV = oldDataMap.get(newV.id);
                        if (oldV) {
                            if (oldV.speed === 0 && newV.speed > 0) {
                                addActivityLog(newV.registration_number, 'Started moving', 'blue');
                            } else if (oldV.speed > 0 && newV.speed === 0) {
                                addActivityLog(newV.registration_number, 'Stopped', 'emerald');
                            }
                        }
                    });

                    vehiclesData = newData;
                    updateUI();

                    if (statusIndicator) {
                        statusIndicator.className = "flex items-center px-3 py-1 bg-green-100 text-green-700 rounded-full text-xs font-medium";
                        statusIndicator.innerHTML = `
                            <span class="relative flex h-2 w-2 mr-2">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-2 w-2 bg-green-500"></span>
                            </span>
                            LIVE UPDATING
                        `;
                    }
                }
            })
            .catch(err => {
                console.error("Update failed", err);
                if (statusIndicator) {
                    statusIndicator.className = "flex items-center px-3 py-1 bg-red-100 text-red-700 rounded-full text-xs font-medium";
                    statusIndicator.innerHTML = `
                        <i class="fas fa-exclamation-circle mr-2"></i>
                        CONNECTION LOST
                    `;
                }
            });
    }

    function getTimeAgo(dateString) {
        if (!dateString) return 'Never';
        const date = new Date(dateString);
        const now = new Date();
        const seconds = Math.floor((now - date) / 1000);

        if (seconds < 60) return 'Just now';
        const minutes = Math.floor(seconds / 60);
        if (minutes < 60) return `${minutes}m ago`;
        const hours = Math.floor(minutes / 60);
        if (hours < 24) return `${hours}h ago`;
        return date.toLocaleDateString();
    }

    function updateUI() {
        const query = document.getElementById('vehicleSearch').value.toLowerCase();
        const status = document.getElementById('statusFilter').value;

        // Calculate Stats
        const moving = vehiclesData.filter(v => v.speed > 0).length;
        const idle = vehiclesData.filter(v => v.speed === 0 && (new Date() - new Date(v.last_seen_at)) < 300000).length;
        const offline = vehiclesData.filter(v => (new Date() - new Date(v.last_seen_at)) >= 300000).length;

        document.getElementById('statMoving').innerText = moving;
        document.getElementById('statIdle').innerText = idle;
        document.getElementById('statOffline').innerText = offline;

        const filtered = vehiclesData.filter(v => {
            const matchesSearch = v.registration_number.toLowerCase().includes(query) ||
                                (v.assigned_driver && v.assigned_driver.name.toLowerCase().includes(query));

            let matchesStatus = true;
            const isOffline = (new Date() - new Date(v.last_seen_at)) >= 300000;

            if (status === 'moving') matchesStatus = v.speed > 0;
            else if (status === 'idle') matchesStatus = v.speed === 0 && !isOffline;
            else if (status === 'offline') matchesStatus = isOffline;

            return matchesSearch && matchesStatus;
        });

        document.getElementById('vehicleCount').innerText = filtered.length;
        updateVehicleList(filtered);
        updateMarkers(filtered);

        if (selectedVehicleId) {
            const selected = vehiclesData.find(v => v.id === selectedVehicleId);
            if (selected) {
                updateDetailCard(selected);
                if (following) {
                    map.panTo([selected.current_latitude, selected.current_longitude]);
                }
            }
        }
    }

    function updateVehicleList(vehicles) {
        const container = document.getElementById('vehicleList');
        container.innerHTML = vehicles.map(v => {
            const isOffline = (new Date() - new Date(v.last_seen_at)) >= 300000;
            const isMoving = v.speed > 0;
            const statusColor = isOffline ? 'bg-gray-400' : (isMoving ? 'bg-blue-500' : 'bg-emerald-500');
            const movingPulseClass = isMoving ? 'sidebar-moving-pulse' : '';

            return `
                <div class="vehicle-list-item p-3 ${selectedVehicleId === v.id ? 'active' : ''} ${movingPulseClass}" onclick="focusVehicle(${v.id})">
                    <div class="flex justify-between items-start mb-1">
                        <div class="flex flex-col">
                            <div class="flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full ${statusColor}"></span>
                                <span class="font-bold text-gray-900 leading-tight">${v.registration_number}</span>
                                ${isMoving ? '<span class="live-dot"></span>' : ''}
                            </div>
                            <span class="text-[10px] text-gray-500 uppercase font-medium ml-4">${v.make} ${v.model}</span>
                        </div>
                        <div class="flex flex-col items-end">
                            <span class="text-[10px] font-black ${v.ignition === 'on' ? 'text-emerald-600' : 'text-gray-400'} uppercase">
                                ${v.ignition === 'on' ? 'Ignition On' : 'Ignition Off'}
                            </span>
                            <span class="text-[11px] font-bold ${isMoving ? 'text-blue-600' : 'text-gray-900'}">${v.speed} km/h</span>
                        </div>
                    </div>

                    <div class="mt-2 flex items-center justify-between">
                        <div class="flex flex-col">
                            <div class="flex items-center text-[10px]">
                                <i class="fas fa-user-circle mr-1 ${v.assigned_driver && v.assigned_driver.user && v.assigned_driver.user.online_status === 'online' ? 'text-green-500' : 'text-gray-400'}"></i>
                                <span class="text-gray-600 truncate max-w-[100px] font-medium">${v.assigned_driver ? v.assigned_driver.name : 'Unassigned'}</span>
                            </div>
                            <span class="text-[9px] text-gray-400 ml-4">${getTimeAgo(v.last_seen_at)}</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="w-12 h-1 bg-gray-100 rounded-full overflow-hidden">
                                <div class="h-full ${v.fuel_level < 20 ? 'bg-red-500' : (v.fuel_level < 50 ? 'bg-orange-500' : 'bg-emerald-500')}" style="width: ${v.fuel_level}%"></div>
                            </div>
                            <span class="text-[9px] font-bold text-gray-500">${v.fuel_level}%</span>
                        </div>
                    </div>
                </div>
            `;
        }).join('');
    }

    function escapeHtml(unsafe) {
        return unsafe
             .replace(/&/g, "&amp;")
             .replace(/</g, "&lt;")
             .replace(/>/g, "&gt;")
             .replace(/"/g, "&quot;")
             .replace(/'/g, "&#039;");
    }

    function getVehicleSvg(type, color) {
        type = type ? type.toLowerCase() : '';
        if (type === 'truck') {
            return `
                <svg width="40" height="40" viewBox="0 0 100 100">
                    <!-- Truck Body shadow -->
                    <rect x="25" y="15" width="50" height="75" rx="4" fill="rgba(0,0,0,0.15)" />
                    <!-- Trailer -->
                    <rect x="28" y="35" width="44" height="50" rx="2" fill="${color}" stroke="#1e293b" stroke-width="2" />
                    <!-- Cabin -->
                    <rect x="30" y="18" width="40" height="20" rx="4" fill="${color}" stroke="#1e293b" stroke-width="2" />
                    <!-- Windshield -->
                    <rect x="34" y="20" width="32" height="10" rx="2" fill="rgba(255,255,255,0.3)" />
                    <!-- Wheels (Simulated as parts of body for simplicity in rotate) -->
                    <rect x="25" y="30" width="5" height="10" fill="#333" />
                    <rect x="70" y="30" width="5" height="10" fill="#333" />
                </svg>`;
        } else if (type === 'suv') {
            return `
                <svg width="40" height="40" viewBox="0 0 100 100">
                    <rect x="25" y="20" width="50" height="65" rx="8" fill="rgba(0,0,0,0.15)" />
                    <rect x="28" y="18" width="44" height="64" rx="10" fill="${color}" stroke="#1e293b" stroke-width="2.5" />
                    <rect x="32" y="30" width="36" height="35" rx="4" fill="rgba(255,255,255,0.3)" />
                    <!-- Roof Rails -->
                    <rect x="30" y="35" width="2" height="30" fill="#1e293b" />
                    <rect x="68" y="35" width="2" height="30" fill="#1e293b" />
                </svg>`;
        } else if (type === 'van' || type === 'bus') {
            return `
                <svg width="40" height="40" viewBox="0 0 100 100">
                    <rect x="25" y="15" width="50" height="75" rx="4" fill="rgba(0,0,0,0.15)" />
                    <rect x="28" y="18" width="44" height="70" rx="6" fill="${color}" stroke="#1e293b" stroke-width="2" />
                    <!-- Large Windows -->
                    <rect x="32" y="25" width="36" height="15" rx="2" fill="rgba(255,255,255,0.3)" />
                    <rect x="32" y="45" width="36" height="35" rx="2" fill="rgba(255,255,255,0.2)" />
                </svg>`;
        } else if (type === 'pickup') {
            return `
                <svg width="40" height="40" viewBox="0 0 100 100">
                    <rect x="28" y="22" width="44" height="64" rx="12" fill="rgba(0,0,0,0.15)" />
                    <!-- Cabin -->
                    <rect x="30" y="20" width="40" height="35" rx="6" fill="${color}" stroke="#1e293b" stroke-width="2" />
                    <!-- Bed -->
                    <rect x="30" y="55" width="40" height="25" rx="2" fill="${color}" stroke="#1e293b" stroke-width="2" />
                    <!-- Windshield -->
                    <rect x="34" y="25" width="32" height="15" rx="2" fill="rgba(255,255,255,0.3)" />
                </svg>`;
        }
        // Default Saloon Car
        return `
            <svg width="40" height="40" viewBox="0 0 100 100">
                <rect x="28" y="22" width="44" height="64" rx="12" fill="rgba(0,0,0,0.15)" />
                <rect x="30" y="20" width="40" height="60" rx="10" fill="${color}" stroke="#1e293b" stroke-width="2" />
                <rect x="34" y="32" width="32" height="24" rx="4" fill="rgba(255,255,255,0.3)" />
                <path d="M34 32 L30 25 L70 25 L66 32 Z" fill="rgba(255,255,255,0.2)" />
                <rect x="33" y="21" width="8" height="4" rx="1" fill="#fef08a" />
                <rect x="59" y="21" width="8" height="4" rx="1" fill="#fef08a" />
                <rect x="34" y="76" width="8" height="3" rx="1" fill="#ef4444" />
                <rect x="58" y="76" width="8" height="3" rx="1" fill="#ef4444" />
            </svg>`;
    }

    function updateMarkers(vehicles) {
        vehicles.forEach(v => {
            if (!v.current_latitude) return;

            const pos = [v.current_latitude, v.current_longitude];
            const isMoving = v.speed > 0;
            const isOffline = (new Date() - new Date(v.last_seen_at)) >= 300000;
            const markerColor = isOffline ? '#94a3b8' : (isMoving ? '#3b82f6' : '#10b981');

            if (markers[v.id]) {
                markers[v.id].setLatLng(pos);
                const markerEl = markers[v.id].getElement();
                if (markerEl) {
                    const icon = markerEl.querySelector('.car-marker');
                    if (icon) {
                        icon.style.transform = `rotate(${v.heading}deg)`;
                        // Update SVG if color changed (e.g. status change)
                        const svgContainer = icon.querySelector('svg');
                        if (svgContainer) {
                            icon.innerHTML = getVehicleSvg(v.vehicle_type, markerColor);
                        }
                    }

                    const pulseEl = markerEl.querySelector('.pulse-indicator');
                    if (pulseEl) {
                        pulseEl.className = `pulse-indicator absolute top-0 left-0 ${isMoving ? 'pulse-blue' : (isOffline ? '' : 'pulse')}`;
                    }
                }
            } else {
                const icon = L.divIcon({
                    className: 'custom-div-icon',
                    html: `
                        <div class="relative car-marker-container">
                            <div id="focus-${v.id}" class="focus-indicator ${selectedVehicleId === v.id ? 'focus-marker' : ''}"></div>
                            <div class="pulse-indicator absolute top-0 left-0 ${isMoving ? 'pulse-blue' : (isOffline ? '' : 'pulse')}"></div>
                            <div class="car-marker" style="transform: rotate(${v.heading}deg)">
                                ${getVehicleSvg(v.vehicle_type, markerColor)}
                            </div>
                            <div class="absolute -top-8 left-1/2 -translate-x-1/2 marker-label">${escapeHtml(v.registration_number)}</div>
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
        following = false;
        const vehicle = vehiclesData.find(v => v.id === id);
        if (!vehicle) return;

        map.flyTo([vehicle.current_latitude, vehicle.current_longitude], 17, {
            duration: 1.5
        });

        updateDetailCard(vehicle);
        updateUI();

        // Add focus animation class to the marker's focus indicator
        setTimeout(() => {
            const indicator = document.getElementById(`focus-${id}`);
            if (indicator) {
                indicator.classList.add('focus-marker');
            }
        }, 1500);
    }

    function toggleFollow() {
        following = !following;
        const btn = document.getElementById('followBtn');
        if (following) {
            btn.innerHTML = '<i class="fas fa-stop-circle mr-1"></i> Stop Following';
            btn.classList.replace('bg-blue-600', 'bg-red-600');
            btn.classList.replace('hover:bg-blue-700', 'hover:bg-red-700');
        } else {
            btn.innerHTML = '<i class="fas fa-crosshairs mr-1"></i> Follow';
            btn.classList.replace('bg-red-600', 'bg-blue-600');
            btn.classList.replace('hover:bg-red-700', 'hover:bg-blue-700');
        }
    }

    function updateDetailCard(v) {
        const card = document.getElementById('miniDetailCard');
        if (card.classList.contains('hidden')) {
            card.classList.remove('hidden');
            setTimeout(() => {
                card.style.opacity = '1';
                card.style.transform = 'translateY(0)';
            }, 10);
        }

        document.getElementById('cardReg').innerText = v.registration_number;
        document.getElementById('cardModel').innerText = `${v.make} ${v.model}`;
        document.getElementById('cardSpeed').innerText = v.speed;

        const ignitionBadge = document.getElementById('cardIgnitionBadge');
        ignitionBadge.innerText = v.ignition === 'on' ? 'Ignition On' : 'Ignition Off';
        ignitionBadge.className = `px-3 py-1.5 rounded-full text-[10px] font-black uppercase tracking-wider ${v.ignition === 'on' ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-200 text-gray-500'}`;

        document.getElementById('cardFuelText').innerText = `${v.fuel_level}%`;
        const fuelBar = document.getElementById('cardFuelBar');
        fuelBar.style.width = `${v.fuel_level}%`;
        fuelBar.className = `h-full transition-all duration-1000 ${v.fuel_level < 20 ? 'bg-red-500' : (v.fuel_level < 50 ? 'bg-orange-500' : 'bg-blue-600')}`;

        document.getElementById('cardBattery').innerText = `${Number(v.battery).toFixed(1)}V`;

        document.getElementById('cardDriver').innerText = v.assigned_driver ? v.assigned_driver.name : 'Unassigned';
        document.getElementById('cardLastSeen').innerText = getTimeAgo(v.last_seen_at);
        document.getElementById('detailsLink').href = `/vehicles/${v.id}`;

        document.getElementById('historyBtn').onclick = () => loadHistory(v.id);
    }

    function closeDetailCard() {
        const card = document.getElementById('miniDetailCard');
        card.style.opacity = '0';
        card.style.transform = 'translateY(16px)';
        setTimeout(() => {
            card.classList.add('hidden');
        }, 500);

        selectedVehicleId = null;
        following = false;
        updateUI();
        clearHistory();
    }

    function addActivityLog(reg, action, color) {
        const ticker = document.getElementById('activityTicker');
        const item = document.createElement('div');

        // Use full class names to ensure Tailwind compiler picks them up
        const colorClasses = {
            blue: 'border-blue-500',
            emerald: 'border-emerald-500'
        };
        const borderClass = colorClasses[color] || 'border-gray-500';

        item.className = `bg-white/90 backdrop-blur shadow-lg border-l-4 ${borderClass} p-3 rounded-r-lg transform transition-all duration-500 translate-x-full opacity-0`;
        item.innerHTML = `
            <div class="flex items-center gap-2">
                <span class="font-black text-gray-900 text-xs">${reg}</span>
                <span class="text-[10px] text-gray-500 font-medium">${action}</span>
            </div>
        `;
        ticker.prepend(item);

        setTimeout(() => {
            item.classList.remove('translate-x-full', 'opacity-0');
        }, 100);

        setTimeout(() => {
            item.classList.add('translate-x-full', 'opacity-0');
            setTimeout(() => item.remove(), 500);
        }, 5000);
    }

    function fitAllVehicles() {
        const activeMarkers = Object.values(markers);
        if (activeMarkers.length === 0) return;

        const group = new L.featureGroup(activeMarkers);
        map.fitBounds(group.getBounds(), { padding: [50, 50] });
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
