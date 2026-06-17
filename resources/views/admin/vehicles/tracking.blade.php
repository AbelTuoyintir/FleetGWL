@extends('layouts.app')

@section('title', 'Live Vehicle Tracking')

@section('content')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
<style>
    #map { height: 600px; width: 100%; border-radius: 12px; z-index: 10; }
    .vehicle-list-item { cursor: pointer; transition: background 0.2s; }
    .vehicle-list-item:hover { background-color: #f3f4f6; }
    .status-active { color: #10b981; }
    .status-inactive { color: #6b7280; }
    .status-maintenance { color: #f59e0b; }
    .status-disposed { color: #ef4444; }
    .leaflet-div-icon {
        background: transparent;
        border: none;
    }
    .custom-marker {
        background-color: #2563eb;
        border: 2px solid white;
        border-radius: 50%;
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 2px 5px rgba(0,0,0,0.3);
    }
</style>

<div class="space-y-6">
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Live Vehicle Tracking</h1>
            <p class="text-gray-500 text-sm">Monitor real-time locations of all fleet units</p>
        </div>
        <div class="flex gap-2">
            <button onclick="refreshMap()" class="bg-white border border-gray-300 px-4 py-2 rounded-lg text-sm font-medium hover:bg-gray-50 transition">
                <i class="fas fa-sync-alt mr-2"></i>Refresh Map
            </button>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
        <!-- Sidebar: Vehicle List -->
        <div class="lg:col-span-1 bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden flex flex-col" style="max-height: 600px;">
            <div class="p-4 border-b border-gray-100">
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400">
                        <i class="fas fa-search"></i>
                    </span>
                    <input type="text" id="vehicleSearch" class="block w-full pl-10 pr-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="Search vehicle...">
                </div>
            </div>
            <div id="vehicleList" class="flex-1 overflow-y-auto divide-y divide-gray-50">
                <!-- Vehicles will be populated here -->
                <div class="p-8 text-center text-gray-400">
                    <i class="fas fa-spinner fa-spin text-2xl mb-2"></i>
                    <p>Loading vehicles...</p>
                </div>
            </div>
        </div>

        <!-- Map Area -->
        <div class="lg:col-span-3">
            <div class="bg-white p-2 rounded-xl shadow-sm border border-gray-100">
                <div id="map"></div>
            </div>
        </div>
    </div>
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<script>
    let map;
    let markers = {};
    let vehiclesData = [];

    document.addEventListener('DOMContentLoaded', function() {
        initMap();
        loadVehicles();

        document.getElementById('vehicleSearch').addEventListener('input', function(e) {
            filterVehicles(e.target.value);
        });
    });

    function initMap() {
        // Center on Ghana
        map = L.map('map').setView([5.6037, -0.1870], 13);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors'
        }).addTo(map);
    }

    function loadVehicles() {
        fetch('{{ route("vehicles.tracking.data") }}')
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    vehiclesData = result.data;
                    updateVehicleList(vehiclesData);
                    updateMapMarkers(vehiclesData);
                }
            })
            .catch(error => {
                console.error('Error loading vehicles:', error);
                document.getElementById('vehicleList').innerHTML = `
                    <div class="p-8 text-center text-red-500">
                        <i class="fas fa-exclamation-circle text-2xl mb-2"></i>
                        <p>Failed to load vehicles</p>
                    </div>
                `;
            });
    }

    function updateVehicleList(vehicles) {
        const listContainer = document.getElementById('vehicleList');
        if (vehicles.length === 0) {
            listContainer.innerHTML = '<div class="p-8 text-center text-gray-400">No vehicles found</div>';
            return;
        }

        listContainer.innerHTML = vehicles.map(v => `
            <div class="vehicle-list-item p-4" onclick="focusVehicle(${v.id}, ${v.current_latitude}, ${v.current_longitude})">
                <div class="flex justify-between items-start mb-1">
                    <span class="font-bold text-gray-800">${v.registration_number}</span>
                    <i class="fas fa-circle text-[8px] status-${v.status}"></i>
                </div>
                <div class="text-xs text-gray-500 mb-2">${v.make} ${v.model}</div>
                <div class="flex items-center text-[10px] text-gray-400">
                    <i class="fas fa-user-circle mr-1"></i>
                    <span>${v.assigned_driver ? v.assigned_driver.name : 'Unassigned'}</span>
                </div>
            </div>
        `).join('');
    }

    function updateMapMarkers(vehicles) {
        // Clear existing markers
        Object.values(markers).forEach(m => map.removeLayer(m));
        markers = {};

        vehicles.forEach(v => {
            if (v.current_latitude && v.current_longitude) {
                const icon = L.divIcon({
                    className: 'custom-div-icon',
                    html: `<div class="custom-marker" style="width: 30px; height: 30px; background-color: ${getStatusColor(v.status)}">
                            <i class="fas fa-truck-moving text-xs"></i>
                           </div>`,
                    iconSize: [30, 30],
                    iconAnchor: [15, 15]
                });

                const marker = L.marker([v.current_latitude, v.current_longitude], { icon: icon })
                    .addTo(map)
                    .bindPopup(`
                        <div class="p-2">
                            <div class="font-bold text-lg mb-1">${v.registration_number}</div>
                            <div class="text-sm text-gray-600 mb-2">${v.make} ${v.model}</div>
                            <div class="grid grid-cols-2 gap-x-4 gap-y-1 text-xs">
                                <span class="text-gray-400">Status:</span>
                                <span class="font-medium capitalize text-${getStatusColorName(v.status)}-600">${v.status}</span>
                                <span class="text-gray-400">Driver:</span>
                                <span class="font-medium">${v.assigned_driver ? v.assigned_driver.name : 'Unassigned'}</span>
                                <span class="text-gray-400">Last Seen:</span>
                                <span class="font-medium">${v.last_seen_at ? new Date(v.last_seen_at).toLocaleTimeString() : 'N/A'}</span>
                            </div>
                            <div class="mt-3">
                                <a href="/vehicles/${v.id}" class="text-blue-600 font-medium text-xs hover:underline">View Details →</a>
                            </div>
                        </div>
                    `);

                markers[v.id] = marker;
            }
        });

        // Fit map to markers if there are any
        const markerGroup = L.featureGroup(Object.values(markers));
        if (Object.keys(markers).length > 0) {
            map.fitBounds(markerGroup.getBounds().pad(0.1));
        }
    }

    function focusVehicle(id, lat, lng) {
        if (lat && lng) {
            map.setView([lat, lng], 16);
            if (markers[id]) {
                markers[id].openPopup();
            }
        }
    }

    function filterVehicles(query) {
        const filtered = vehiclesData.filter(v =>
            v.registration_number.toLowerCase().includes(query.toLowerCase()) ||
            v.make.toLowerCase().includes(query.toLowerCase()) ||
            v.model.toLowerCase().includes(query.toLowerCase())
        );
        updateVehicleList(filtered);
    }

    function refreshMap() {
        loadVehicles();
    }

    function getStatusColor(status) {
        switch(status) {
            case 'active': return '#10b981';
            case 'maintenance': return '#f59e0b';
            case 'inactive': return '#6b7280';
            case 'disposed': return '#ef4444';
            default: return '#2563eb';
        }
    }

    function getStatusColorName(status) {
        switch(status) {
            case 'active': return 'emerald';
            case 'maintenance': return 'amber';
            case 'inactive': return 'slate';
            case 'disposed': return 'red';
            default: return 'blue';
        }
    }
</script>
@endsection
