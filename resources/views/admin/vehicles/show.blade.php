
    @extends('layouts.app')
    @section('title', 'Vehicle Details - ' . $vehicle->registration_number)
    @section('content')

    
    <style>
        * { font-family: 'Inter', sans-serif; }
        body { background: #f1f5f9; }
        
        .stat-card {
            transition: all 0.2s ease;
            border: 1px solid #e2e8f0;
            background: white;
            border-radius: 12px;
        }
        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px -6px rgba(0,0,0,0.1);
        }
        
        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .status-active { background: #dcfce7; color: #166534; }
        .status-inactive { background: #fee2e2; color: #991b1b; }
        .status-maintenance { background: #ffedd5; color: #9a3412; }
        .status-disposed { background: #f3f4f6; color: #374151; }
        
        .sidebar-fleet {
            position: fixed; top: 0; left: 0;
            height: 100vh; width: 280px;
            background: white;
            border-right: 1px solid #e2e8f0;
            z-index: 40;
            transition: transform 0.25s ease;
            overflow-y: auto;
        }
        .sidebar-closed { transform: translateX(-100%); }
        @media (min-width: 1024px) { .sidebar-fleet { transform: translateX(0); } }
        
        .nav-item-fleet {
            transition: all 0.2s;
            border-radius: 10px;
            cursor: pointer;
        }
        .nav-item-fleet:hover { background-color: #f1f5f9; color: #1e40af; }
        .nav-active-fleet { background-color: #eff6ff; color: #2563eb; font-weight: 500; border-left: 3px solid #3b82f6; }
        
        .overlay-fleet {
            position: fixed; inset: 0;
            background: rgba(0,0,0,0.3);
            backdrop-filter: blur(2px);
            z-index: 35;
            display: none;
        }
        .overlay-open { display: block; }
        
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #f1f5f9;
        }
        .info-label {
            font-size: 13px;
            color: #64748b;
            font-weight: 500;
        }
        .info-value {
            font-size: 14px;
            color: #1e293b;
            font-weight: 500;
        }
        
        .tab-btn {
            transition: all 0.2s;
        }
        .tab-active {
            border-bottom: 2px solid #2563eb;
            color: #1e40af;
            font-weight: 600;
        }
        
        .document-card {
            transition: all 0.2s;
            cursor: pointer;
        }
        .document-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        .expiring-soon {
            border-left: 3px solid #f59e0b;
        }
        .expired {
            border-left: 3px solid #ef4444;
        }
        
        .loading-spinner {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 2px solid #e2e8f0;
            border-radius: 50%;
            border-top-color: #3b82f6;
            animation: spin 0.6s linear infinite;
        }
        @keyframes spin { to { transform: rotate(360deg); } }
        
        @media print {
            .sidebar-fleet, .no-print, header, .action-buttons {
                display: none !important;
            }
            main { margin-left: 0 !important; padding: 0 !important; }
            body { background: white; }
        }
    </style>


<!-- Mobile Overlay -->
<div id="mobileOverlay" class="overlay-fleet"></div>
<!-- Main Content -->
<main class=" min-h-screen p-2">
    <div class="max-w-7xl mx-auto">
        <!-- Header with Actions -->
        <div class="mb-6 flex justify-between items-center flex-wrap gap-4">
            <div>
                <div class="flex items-center gap-3">
                    <a href="{{ route('vehicles.index') }}" class="text-gray-500 hover:text-gray-700" aria-label="Back to vehicles list" title="Back to vehicles list">
                        <i class="fas fa-arrow-left"></i>
                    </a>
                    <h1 class="text-2xl font-bold text-gray-800 flex items-center gap-2">
                        <span id="plateNumber">{{ $vehicle->registration_number }}</span>
                        <button onclick="copyToClipboard('{{ $vehicle->registration_number }}')" class="text-gray-400 hover:text-blue-600 transition-colors" title="Copy Registration Number" aria-label="Copy Registration Number">
                            <i class="far fa-copy text-sm"></i>
                        </button>
                    </h1>
                    <span class="status-badge status-{{ $vehicle->status }}">
                        <i class="fas {{ $vehicle->status == 'active' ? 'fa-check-circle' : ($vehicle->status == 'maintenance' ? 'fa-tools' : 'fa-circle') }}"></i>
                        {{ ucfirst($vehicle->status) }}
                    </span>
                </div>
                <p class="text-gray-500 text-sm mt-1">{{ $vehicle->make }} {{ $vehicle->model }} ({{ $vehicle->year }})</p>
            </div>
            <div class="flex gap-3 no-print">
                <button onclick="window.print()" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg text-sm hover:bg-gray-200 transition">
                    <i class="fas fa-print mr-1"></i>Print
                </button>
                <a href="{{ route('vehicles.edit', $vehicle->id) }}" class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm hover:bg-blue-700 transition">
                    <i class="fas fa-edit mr-1"></i>Edit Vehicle
                </a>
            </div>
        </div>
        
        <!-- Vehicle Image & Quick Info Row -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
            <!-- Vehicle Photo -->
            <div class="bg-white rounded-lg shadow-sm p-4">
                <div class="aspect-video bg-gray-100 rounded-lg flex items-center justify-center overflow-hidden">
                    @if($vehicle->photo)
                        <img src="{{ Storage::url($vehicle->photo) }}" alt="{{ $vehicle->registration_number }}" class="w-full h-full object-cover">
                    @else
                        <div class="text-center">
                            <i class="fas fa-truck text-gray-300 text-6xl mb-2"></i>
                            <p class="text-gray-400 text-sm">No Photo Available</p>
                        </div>
                    @endif
                </div>
            </div>
            
            <!-- Quick Stats Cards -->
            <div class="lg:col-span-2 grid grid-cols-2 gap-4">
                <div class="stat-card p-4">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-tachometer-alt text-blue-600"></i>
                        </div>
                        <div>
                            <p class="text-gray-500 text-xs">Current Mileage</p>
                            <p class="text-xl font-bold">{{ number_format($vehicle->mileage) }} km</p>
                        </div>
                    </div>
                </div>
                <div class="stat-card p-4">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-user text-green-600"></i>
                        </div>
                        <div>
                            <p class="text-gray-500 text-xs">Assigned Driver</p>
                            <p class="text-xl font-bold">{{ $vehicle->driver->name ?? 'Unassigned' }}</p>
                        </div>
                    </div>
                </div>
                <div class="stat-card p-4">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-calendar-alt text-purple-600"></i>
                        </div>
                        <div>
                            <p class="text-gray-500 text-xs">Registration Date</p>
                            <p class="text-xl font-bold">{{ $vehicle->registration_date ? date('M j, Y', strtotime($vehicle->registration_date)) : 'N/A' }}</p>
                        </div>
                    </div>
                </div>
                <div class="stat-card p-4">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-orange-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-file-invoice text-orange-600"></i>
                        </div>
                        <div>
                            <p class="text-gray-500 text-xs">Insurance Expiry</p>
                            <p class="text-xl font-bold {{ $vehicle->insurance_expiry_date && strtotime($vehicle->insurance_expiry_date) < time() ? 'text-red-600' : 'text-gray-800' }}">
                                {{ $vehicle->insurance_expiry_date ? date('M j, Y', strtotime($vehicle->insurance_expiry_date)) : 'N/A' }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Tabs Navigation -->
        <div class="bg-white rounded-t-xl border-b border-gray-200 px-6">
            <div class="flex space-x-8 overflow-x-auto">
                <button data-tab="overview" class="tab-btn py-4 text-sm font-medium text-gray-600 hover:text-blue-600 transition tab-active">
                    <i class="fas fa-info-circle mr-2"></i>Overview
                </button>
                <button data-tab="maintenance" class="tab-btn py-4 text-sm font-medium text-gray-600 hover:text-blue-600 transition">
                    <i class="fas fa-tools mr-2"></i>Maintenance History
                </button>
                <button data-tab="mileage" class="tab-btn py-4 text-sm font-medium text-gray-600 hover:text-blue-600 transition">
                    <i class="fas fa-road mr-2"></i>Mileage Log
                </button>
                <button data-tab="fuel" class="tab-btn py-4 text-sm font-medium text-gray-600 hover:text-blue-600 transition">
                    <i class="fas fa-gas-pump mr-2"></i>Fuel Log
                </button>
                <button data-tab="documents" class="tab-btn py-4 text-sm font-medium text-gray-600 hover:text-blue-600 transition">
                    <i class="fas fa-folder-open mr-2"></i>Documents
                </button>
                <button data-tab="performance" class="tab-btn py-4 text-sm font-medium text-gray-600 hover:text-blue-600 transition">
                    <i class="fas fa-chart-line mr-2"></i>Performance
                </button>
            </div>
        </div>
        
        <div class="bg-white rounded-b-xl shadow-sm p-6">
            <!-- Tab 1: Overview -->
            <div id="overview-tab" class="tab-content">
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Vehicle Information -->
                    <div class="lg:col-span-2">
                        <h3 class="font-semibold text-gray-800 mb-4 pb-2 border-b">
                            <i class="fas fa-truck text-blue-600 mr-2"></i>Vehicle Information
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="info-row"><span class="info-label">Registration Number</span><span class="info-value">{{ $vehicle->registration_number }}</span></div>
                            <div class="info-row"><span class="info-label">Make</span><span class="info-value">{{ $vehicle->make }}</span></div>
                            <div class="info-row"><span class="info-label">Model</span><span class="info-value">{{ $vehicle->model }}</span></div>
                            <div class="info-row"><span class="info-label">Year</span><span class="info-value">{{ $vehicle->year }}</span></div>
                            <div class="info-row"><span class="info-label">Color</span><span class="info-value">{{ $vehicle->color ?? 'N/A' }}</span></div>
                            <div class="info-row"><span class="info-label">Chassis Number</span><span class="info-value">{{ $vehicle->chassis_number }}</span></div>
                            <div class="info-row"><span class="info-label">Engine Number</span><span class="info-value">{{ $vehicle->engine_number ?? 'N/A' }}</span></div>
                            <div class="info-row"><span class="info-label">Vehicle Type</span><span class="info-value">{{ $vehicle->vehicle_type }}</span></div>
                            <div class="info-row"><span class="info-label">Fuel Consumption</span><span class="info-value">{{ $vehicle->fuel_consumption ?? 'N/A' }} km/L</span></div>
                            <div class="info-row"><span class="info-label">Region</span><span class="info-value">{{ $vehicle->region->name ?? 'N/A' }}</span></div>
                        </div>
                    </div>
                    
                    <!-- Financial Information -->
                    <div>
                        <h3 class="font-semibold text-gray-800 mb-4 pb-2 border-b">
                            <i class="fas fa-coins text-green-600 mr-2"></i>Financial Info
                        </h3>
                        <div class="space-y-3">
                            <div class="info-row"><span class="info-label">Purchase Price</span><span class="info-value">GHS {{ number_format($vehicle->purchase_price ?? 0, 2) }}</span></div>
                            <div class="info-row"><span class="info-label">Purchase Date</span><span class="info-value">{{ $vehicle->purchase_date ? date('M j, Y', strtotime($vehicle->purchase_date)) : 'N/A' }}</span></div>
                            <div class="info-row"><span class="info-label">Next Inspection</span><span class="info-value">{{ $vehicle->next_inspection_date ? date('M j, Y', strtotime($vehicle->next_inspection_date)) : 'N/A' }}</span></div>
                            <div class="info-row"><span class="info-label">Owner Name</span><span class="info-value">{{ $vehicle->owner_name ?? 'N/A' }}</span></div>
                            <div class="info-row"><span class="info-label">Owner Contact</span><span class="info-value">{{ $vehicle->owner_contact ?? 'N/A' }}</span></div>
                        </div>
                        
                        <!-- Maintenance Summary -->
                        <div class="mt-6 p-4 bg-gray-50 rounded-lg">
                            <h4 class="font-semibold text-gray-800 text-sm mb-3">Maintenance Summary</h4>
                           
                            <div class="space-y-2">
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-600">Total Maintenance Records</span>
                                    <span class="font-semibold">{{ $maintenanceCount }}</span>
                                </div>
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-600">This Month</span>
                                    <span class="font-semibold text-orange-600">{{ $maintenanceThisMonth }}</span>
                                </div>
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-600">Avg Weekly Mileage</span>
                                    <span class="font-semibold">{{ number_format($averageWeeklyMileage) }} km</span>
                                </div>
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-600">Avg Monthly Mileage</span>
                                    <span class="font-semibold">{{ number_format($averageMonthlyMileage) }} km</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Notes Section -->
                @if($vehicle->notes)
                <div class="mt-6 p-4 bg-yellow-50 rounded-lg border border-yellow-200">
                    <h4 class="font-semibold text-yellow-800 text-sm mb-2">
                        <i class="fas fa-sticky-note mr-2"></i>Notes
                    </h4>
                    <p class="text-yellow-700 text-sm">{{ $vehicle->notes }}</p>
                </div>
                @endif
                
                <!-- Recent Activity -->
                <div class="mt-6">
                    <h3 class="font-semibold text-gray-800 mb-4 pb-2 border-b">
                        <i class="fas fa-history text-gray-600 mr-2"></i>Recent Activity
                    </h3>
                    <div class="space-y-3">
                        @forelse($recentActivity as $activity)
                        <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg">
                            <div class="w-8 h-8 rounded-full {{ str_replace('bg-', 'bg-', $activity['status_class']) }} bg-opacity-20 flex items-center justify-center">
                                <i class="fas {{ $activity['icon'] }} {{ str_replace('text-', 'text-', $activity['status_class']) }}"></i>
                            </div>
                            <div class="flex-1">
                                <p class="font-medium text-gray-800">{{ $activity['title'] }}</p>
                                <p class="text-xs text-gray-500">{{ $activity['date'] }}</p>
                            </div>
                            <span class="text-xs px-2 py-1 rounded-full {{ $activity['status_class'] }}">{{ $activity['type'] }}</span>
                        </div>
                        @empty
                        <p class="text-center text-gray-500 py-8">No recent activity</p>
                        @endforelse
                    </div>
                </div>
            </div>
            
            <!-- Tab 2: Maintenance History -->
            <div id="maintenance-tab" class="tab-content hidden">
                <!-- Maintenance Stats -->
                <div class="flex justify-end mb-5">
                    <button onclick="openCreateMaintenanceModal()" class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm hover:bg-blue-700 transition flex items-center gap-2">
                        <i class="fas fa-plus-circle"></i>
                        <span>Add Maintenance</span>
                    </button>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                    
                    <div class="bg-blue-50 rounded-lg p-4 text-center">
                        <p class="text-2xl font-bold text-blue-700">{{ $maintenanceCount }}</p>
                        <p class="text-xs text-gray-600">Total Services</p>
                    </div>
                    <div class="bg-green-50 rounded-lg p-4 text-center">
                        <p class="text-2xl font-bold text-green-700">{{ $maintenanceLog->where('status', 'completed')->count() }}</p>
                        <p class="text-xs text-gray-600">Completed</p>
                    </div>
                    <div class="bg-yellow-50 rounded-lg p-4 text-center">
                        <p class="text-2xl font-bold text-yellow-700">{{ $maintenanceLog->where('status', 'pending')->count() }}</p>
                        <p class="text-xs text-gray-600">Pending</p>
                    </div>
                    <div class="bg-purple-50 rounded-lg p-4 text-center">
                        <p class="text-2xl font-bold text-purple-700">{{ $maintenanceThisMonth }}</p>
                        <p class="text-xs text-gray-600">This Month</p>
                    </div>
                </div>
                
                <!-- Maintenance Log Table -->
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Service Type</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Description</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Cost (GHS)</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Status</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @forelse($maintenanceLog as $maintenance)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 text-sm">{{ $maintenance->date->format('M j, Y') }}</td>
                                <td class="px-6 py-4 text-sm font-medium">{{ $maintenance->maintenance_type ?? $maintenance->service ?? 'General' }}</td>
                                <td class="px-6 py-4 text-sm text-gray-600">{{ Str::limit($maintenance->description ?? 'N/A', 50) }}</td>
                                <td class="px-6 py-4 text-sm text-right font-semibold">GHS {{ number_format($maintenance->cost ?? 0, 2) }}</td>
                                <td class="px-6 py-4 text-sm text-center">
                                    <span class="px-2 py-1 text-xs rounded-full 
                                        {{ $maintenance->status == 'completed' ? 'bg-green-100 text-green-700' : 
                                           ($maintenance->status == 'pending' ? 'bg-yellow-100 text-yellow-700' : 'bg-gray-100 text-gray-700') }}">
                                        {{ ucfirst($maintenance->status) }}
                                    </span>
                                </td>
                               <td class="px-6 py-4 text-sm text-center">
                                    <a href="{{ route('vehicles.maintenance.details.page', $maintenance->id) }}" 
                                    class="text-blue-600 hover:text-blue-800" target="_blank">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="px-6 py-8 text-center text-gray-500">No maintenance records found</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <div class="mt-6">
                    {{ $maintenanceLog->links() }}
                </div>
            </div>
            
            <!-- Tab 3: Documents -->
            <div id="documents-tab" class="tab-content hidden">
                <div class="flex justify-end">
                    <button onclick="showUploadModal()" class="mt-3 px-4 py-2 bg-blue-600 text-white rounded-lg text-sm transition hover:bg-blue-700 shadow-sm">
                        <i class="fas fa-upload mr-1"></i>Upload Document
                    </button>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @forelse($documents as $document)
                    <?php

use Carbon\Carbon;

                        $expiryDate = $document->expiry_date ? Carbon::parse($document->expiry_date) : null;
                    $isExpiring = $expiryDate && $expiryDate->isFuture() && $expiryDate->diffInDays(now()) <= 30;
                    $isExpired = $expiryDate && $expiryDate->isPast();
                    ?>
                    <div class="document-card bg-white border rounded-lg p-4 hover:shadow-md transition 
                        {{ $isExpiring ? 'expiring-soon' : ($isExpired ? 'expired' : '') }}">
                        <div class="flex items-start justify-between mb-3">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-lg flex items-center justify-center
                                    {{ $document->document_type == 'insurance' ? 'bg-blue-100' : 
                                       ($document->document_type == 'registration' ? 'bg-green-100' : 'bg-gray-100') }}">
                                    <i class="fas {{ $document->document_type == 'insurance' ? 'fa-file-invoice' : 
                                        ($document->document_type == 'registration' ? 'fa-id-card' : 'fa-file') }} 
                                        {{ $document->document_type == 'insurance' ? 'text-blue-600' : 
                                           ($document->document_type == 'registration' ? 'text-green-600' : 'text-gray-600') }}"></i>
                                </div>
                                <div>
                                    <h4 class="font-semibold text-gray-800">{{ ucfirst($document->document_type) }}</h4>
                                    <p class="text-xs text-gray-500">Uploaded: {{ $document->created_at->format('M j, Y') }}</p>
                                </div>
                            </div>
                            @if($isExpiring)
                                <span class="text-xs bg-yellow-100 text-yellow-700 px-2 py-1 rounded-full">Expiring Soon</span>
                            @elseif($isExpired)
                                <span class="text-xs bg-red-100 text-red-700 px-2 py-1 rounded-full">Expired</span>
                            @endif
                        </div>
                        
                        <div class="space-y-2 text-sm">
                            <div class="flex justify-between">
                                <span class="text-gray-500">Document Number:</span>
                                <span class="font-medium">{{ $document->document_number ?? 'N/A' }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-500">Issue Date:</span>
                                <span>{{ $document->issue_date ? date('M j, Y', strtotime($document->issue_date)) : 'N/A' }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-500">Expiry Date:</span>
                                <span class="{{ $isExpired ? 'text-red-600 font-semibold' : ($isExpiring ? 'text-yellow-600' : '') }}">
                                    {{ $document->expiry_date ? date('M j, Y', strtotime($document->expiry_date)) : 'N/A' }}
                                </span>
                            </div>
                        </div>
                        
                        <div class="mt-3 flex gap-2">
                            @if($document->file_path)
                            <a href="{{ Storage::url($document->file_path) }}" target="_blank" class="flex-1 text-center px-3 py-1.5 bg-blue-600 text-white rounded-lg text-xs hover:bg-blue-700">
                                <i class="fas fa-download mr-1"></i>View
                            </a>
                            @endif
                            <button class="flex-1 px-3 py-1.5 border border-gray-300 rounded-lg text-xs hover:bg-gray-50">
                                <i class="fas fa-envelope mr-1"></i>Remind
                            </button>
                        </div>
                    </div>
                    @empty
                    <div class="col-span-3 text-center py-12 text-gray-500">
                        <i class="fas fa-folder-open text-5xl mb-3 opacity-50"></i>
                        <p>No documents uploaded yet</p>
                        <button onclick="showUploadModal()" class="mt-3 px-4 py-2 bg-blue-600 text-white rounded-lg text-sm">
                            <i class="fas fa-upload mr-1"></i>Upload Document
                        </button>
                    </div>
                    @endforelse
                </div>
            </div>
            
            <!-- Tab 4: Performance -->
            <div id="performance-tab" class="tab-content hidden">
                <!-- Mileage Breakdown Chart -->
                <div class="mb-6">
                    <h3 class="font-semibold text-gray-800 mb-4">
                        <i class="fas fa-chart-bar text-blue-600 mr-2"></i>Mileage & Fuel Consumption
                    </h3>
                    <div class="relative h-[350px]">
                        <canvas id="mileageChart"></canvas>
                    </div>
                </div>
                
                <!-- Fuel Efficiency Stats -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                    <div class="bg-green-50 rounded-lg p-4 text-center">
                        <p class="text-gray-600 text-sm">Average Fuel Efficiency</p>
                        <p class="text-2xl font-bold text-green-700">
                            @php
                                $totalDistance = collect($mileageBreakdown)->sum('distance');
                                $totalFuel = collect($mileageBreakdown)->sum('fuel_used');
                                $avgEfficiency = $totalFuel > 0 ? $totalDistance / $totalFuel : 0;
                            @endphp
                            {{ number_format($avgEfficiency, 1) }} km/L
                        </p>
                    </div>
                    <div class="bg-blue-50 rounded-lg p-4 text-center">
                        <p class="text-gray-600 text-sm">Total Distance (Period)</p>
                        <p class="text-2xl font-bold text-blue-700">{{ number_format($totalDistance) }} km</p>
                    </div>
                    <div class="bg-orange-50 rounded-lg p-4 text-center">
                        <p class="text-gray-600 text-sm">Total Fuel Cost (Period)</p>
                        <p class="text-2xl font-bold text-orange-700">GHS {{ number_format(collect($mileageBreakdown)->sum('cost'), 2) }}</p>
                    </div>
                </div>
                
                <!-- Performance Metrics Table -->
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Period</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Mileage (km)</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Distance (km)</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Fuel Used (L)</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Cost (GHS)</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Efficiency (km/L)</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @foreach($mileageBreakdown as $data)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 text-sm font-medium">{{ $data['period'] }}</td>
                                <td class="px-6 py-4 text-sm text-right">{{ number_format($data['mileage']) }}</td>
                                <td class="px-6 py-4 text-sm text-right">{{ number_format($data['distance']) }}</td>
                                <td class="px-6 py-4 text-sm text-right">{{ number_format($data['fuel_used'], 1) }}</td>
                                <td class="px-6 py-4 text-sm text-right font-semibold">GHS {{ number_format($data['cost'], 2) }}</td>
                                <td class="px-6 py-4 text-sm text-right">
                                    @php $efficiency = ($data['distance'] > 0 && $data['fuel_used'] > 0) ? $data['distance'] / $data['fuel_used'] : 0; @endphp
                                    <span class="text-green-600 font-medium">{{ number_format($efficiency, 1) }} km/L</span>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <!-- Tab 4: Mileage Log -->
        <div id="mileage-tab" class="tab-content hidden">
            <div class="flex justify-between items-center mb-6">
                <h3 class="font-semibold text-gray-800">
                    <i class="fas fa-road text-blue-600 mr-2"></i>Mileage Log History
                </h3>
                <button onclick="openMileageModal()" class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm hover:bg-blue-700 transition">
                    <i class="fas fa-plus mr-1"></i>Record Mileage
                </button>
            </div>

            <!-- Mileage Record Modal -->
            <div id="mileageModal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
                <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                    <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" aria-hidden="true" onclick="closeMileageModal()"></div>
                    <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                    
                    <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full border border-slate-200">
                        <div class="absolute top-4 right-4">
                            <button onclick="closeMileageModal()" class="text-slate-400 hover:text-slate-600 transition" aria-label="Close modal" title="Close">
                                <i class="fas fa-times text-xl"></i>
                            </button>
                        </div>
                        
                        <div class="bg-white px-6 pt-6 pb-6">
                            <div class="flex items-center gap-3 mb-6">
                                <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                                    <i class="fas fa-road text-blue-600 text-xl"></i>
                                </div>
                                <div>
                                    <h3 class="text-xl font-bold text-slate-800">Record Mileage</h3>
                                    <p class="text-sm text-slate-500">Log trip mileage for {{ $vehicle->registration_number }}</p>
                                </div>
                            </div>
                            
                            <form id="mileageForm">
                                @csrf
                                <input type="hidden" name="vehicle_id" value="{{ $vehicle->id }}">
                                
                                <div class="space-y-4">
                                    <div>
                                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Driver *</label>
                                        <div class="relative driver-search-group">
                                            <input type="text" class="driver-search-input w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition text-sm" 
                                                   list="driversDataList" placeholder="Search or type driver name..." 
                                                   value="{{ $vehicle->driver->user->name ?? '' }}" autocomplete="off">
                                            <input type="hidden" name="driver_id" class="driver-id-input" value="{{ $vehicle->user_id }}">
                                            <div class="driver-details-box mt-2 p-2 bg-blue-50 border border-blue-100 rounded-xl hidden"></div>
                                        </div>
                                    </div>
                                    
                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Start Mileage (km) *</label>
                                            <input type="number" name="start_mileage" id="formStartMileage" required class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition text-sm" value="{{ $vehicle->mileage ?? 0 }}" step="1">
                                        </div>
                                        <div>
                                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">End Mileage (km) *</label>
                                            <input type="number" name="end_mileage" id="formEndMileage" required class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition text-sm" step="1">
                                        </div>
                                    </div>
                                    
                                    <div>
                                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Distance Traveled (km)</label>
                                        <div class="px-4 py-2.5 bg-gray-100 border border-gray-200 rounded-xl text-sm font-semibold text-green-600" id="formDistanceDisplay">
                                            0 km
                                        </div>
                                    </div>
                                    
                                    <div>
                                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Notes (Optional)</label>
                                        <textarea name="notes" rows="3" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition text-sm" placeholder="Additional notes..."></textarea>
                                    </div>
                                </div>
                                
                                <div class="mt-6 flex justify-end gap-3 pt-4 border-t">
                                    <button type="button" onclick="closeMileageModal()" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition">
                                        Cancel
                                    </button>
                                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition flex items-center gap-2">
                                        <i class="fas fa-save"></i> Save Record
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Mileage Statistics -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                <div class="bg-blue-50 rounded-lg p-4 text-center">
                    <p class="text-2xl font-bold text-blue-700">{{ number_format($vehicle->mileage ?? 0) }}</p>
                    <p class="text-xs text-gray-600">Current Mileage (km)</p>
                </div>
                <div class="bg-green-50 rounded-lg p-4 text-center">
                    <p class="text-2xl font-bold text-green-700">{{ number_format($averageWeeklyMileage) }}</p>
                    <p class="text-xs text-gray-600">Avg Weekly (km)</p>
                </div>
                <div class="bg-purple-50 rounded-lg p-4 text-center">
                    <p class="text-2xl font-bold text-purple-700">{{ number_format($averageMonthlyMileage) }}</p>
                    <p class="text-xs text-gray-600">Avg Monthly (km)</p>
                </div>
                <div class="bg-orange-50 rounded-lg p-4 text-center">
                    <p class="text-2xl font-bold text-orange-700">{{ number_format($totalDistance ?? 0) }}</p>
                    <p class="text-xs text-gray-600">Total Distance (Last 3 Months)</p>
                </div>
            </div>
            
            <!-- Mileage Log Table -->
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Recorded By</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Odometer (km)</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Distance (km)</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Notes</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="mileageLogBody">
                        <tr>
                            <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                                <div class="loading-spinner"></div> Loading mileage records...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination for Mileage -->
            <div id="mileagePagination" class="mt-6"></div>
        </div>

        <!-- Tab 5: Fuel Log -->
        <div id="fuel-tab" class="tab-content hidden">
            <div class="flex justify-between items-center mb-6">
                <h3 class="font-semibold text-gray-800">
                    <i class="fas fa-gas-pump text-green-600 mr-2"></i>Fuel Log History
                </h3>
                <button onclick="openFuelModal()"  class="px-4 py-2 bg-green-600 text-white rounded-lg text-sm hover:bg-green-700 transition">
                    <i class="fas fa-plus mr-1"></i>Record Fueling
                </button>
            </div>

            <!-- Fuel Record Modal -->
            <div id="fuelModal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
                <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                    <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" aria-hidden="true" onclick="closeFuelModal()"></div>
                    <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                    
                    <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full border border-slate-200">
                        <!-- Modal Header -->
                        <div class="absolute top-4 right-4">
                            <button onclick="closeFuelModal()" class="text-slate-400 hover:text-slate-600 transition" aria-label="Close modal" title="Close">
                                <i class="fas fa-times text-xl"></i>
                            </button>
                        </div>
                        
                        <div class="bg-white px-6 pt-6 pb-6">
                            <div class="flex items-center gap-3 mb-6">
                                <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
                                    <i class="fas fa-gas-pump text-green-600 text-xl"></i>
                                </div>
                                <div>
                                    <h3 class="text-xl font-bold text-slate-800">Record Fueling</h3>
                                    <p class="text-sm text-slate-500">Log fuel transaction for {{ $vehicle->registration_number }}</p>
                                </div>
                            </div>
                            
                            <form id="fuelForm" enctype="multipart/form-data">
                                @csrf
                                <input type="hidden" name="vehicle_id" value="{{ $vehicle->id }}">
                                
                                <div class="space-y-4 max-h-[60vh] overflow-y-auto pr-2">
                                    <!-- Date & Odometer Row -->
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Date *</label>
                                            <input type="date" name="date" id="fuelDate" required class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-green-500 outline-none transition text-sm" value="{{ date('Y-m-d') }}">
                                        </div>
                                        <div>
                                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Odometer (km) *</label>
                                            <input type="number" name="odometer" id="fuelOdometer" required class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-green-500 outline-none transition text-sm" placeholder="Current mileage" step="1" value="{{ $vehicle->mileage ?? 0 }}">
                                        </div>
                                    </div>
                                    
                                    <!-- Previous Odometer & Distance Traveled -->
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Previous Odometer (km)</label>
                                            <div id="previousOdometerDisplay" class="w-full px-4 py-2.5 bg-gray-100 border border-gray-200 rounded-xl text-sm text-gray-600">
                                                Loading...
                                            </div>
                                        </div>
                                        <div>
                                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Distance Traveled (km)</label>
                                            <div id="distanceTraveledDisplay" class="w-full px-4 py-2.5 bg-gray-100 border border-gray-200 rounded-xl text-sm text-gray-600">
                                                0 km
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Fuel Quantity & Cost -->
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Fuel Quantity (L) *</label>
                                            <input type="number" name="fuel_quantity" id="fuelQuantity" required class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-green-500 outline-none transition text-sm" step="0.01" placeholder="Liters">
                                        </div>
                                        <div>
                                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Fuel Cost (GHS) *</label>
                                            <input type="number" name="fuel_cost" id="fuelCost" required class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-green-500 outline-none transition text-sm" step="0.01" placeholder="Total cost">
                                        </div>
                                    </div>
                                    
                                    <!-- Price per Unit & Fuel Efficiency -->
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Price per Unit (GHS/L)</label>
                                            <div id="pricePerUnitDisplay" class="w-full px-4 py-2.5 bg-gray-100 border border-gray-200 rounded-xl text-sm text-gray-600">
                                                --
                                            </div>
                                        </div>
                                        <div>
                                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Fuel Efficiency (km/L)</label>
                                            <div id="fuelEfficiencyDisplay" class="w-full px-4 py-2.5 bg-gray-100 border border-gray-200 rounded-xl text-sm text-gray-600">
                                                --
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Fuel Type & Payment Method -->
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Fuel Type *</label>
                                            <select name="fuel_type" id="fuelType" required class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-green-500 outline-none transition text-sm">
                                                <option value="petrol">Petrol</option>
                                                <option value="diesel">Diesel</option>
                                                <option value="electric">Electric</option>
                                                <option value="hybrid">Hybrid</option>
                                                <option value="cng">CNG</option>
                                                <option value="lpg">LPG</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Payment Method</label>
                                            <select name="payment_method" id="paymentMethod" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-green-500 outline-none transition text-sm">
                                                <option value="">Select Method</option>
                                                <option value="cash">Cash</option>
                                                <option value="credit_card">Credit Card</option>
                                                <option value="debit_card">Debit Card</option>
                                                <option value="company_account">Company Account</option>
                                                <option value="fuel_card">Fuel Card</option>
                                                <option value="mobile_payment">Mobile Payment</option>
                                            </select>
                                        </div>
                                    </div>
                                    
                                    <!-- Fuel Station & Location -->
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Fuel Station</label>
                                            <input type="text" name="fuel_station" id="fuelStation" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-green-500 outline-none transition text-sm" placeholder="Station name">
                                        </div>
                                        <div>
                                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Location</label>
                                            <input type="text" name="location" id="location" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-green-500 outline-none transition text-sm" placeholder="City/Town">
                                        </div>
                                    </div>
                                    
                                    <!-- Receipt Number & Driver -->
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Receipt Number</label>
                                            <input type="text" name="receipt_number" id="receiptNumber" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-green-500 outline-none transition text-sm" placeholder="Receipt reference">
                                        </div>
                                        <div>
                                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Driver</label>
                                            <div class="relative driver-search-group">
                                                <input type="text" class="driver-search-input w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-green-500 outline-none transition text-sm" 
                                                       list="driversDataList" placeholder="Search or type driver name..." 
                                                       value="{{ $vehicle->driver->name ?? '' }}" autocomplete="off">
                                                <input type="hidden" name="driver_id" class="driver-id-input" id="driverId" value="{{ $vehicle->assigned_driver_id }}">
                                                <div class="driver-details-box mt-2 p-2 bg-green-50 border border-green-100 rounded-xl hidden"></div>
                                            </div>
                                        </div>
                                    </div>

                                    <datalist id="driversDataList">
                                        @foreach($drivers ?? [] as $driver)
                                            <option value="{{ $driver->name }}" data-id="{{ $driver->id }}">
                                        @endforeach
                                    </datalist>
                                    
                                    <!-- Notes -->
                                    <div>
                                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Notes</label>
                                        <textarea name="notes" id="notes" rows="3" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-green-500 outline-none transition text-sm" placeholder="Additional notes..."></textarea>
                                    </div>
                                    
                                    <!-- Checkboxes -->
                                    <div class="flex gap-6">
                                        <label class="flex items-center gap-2 cursor-pointer">
                                            <input type="checkbox" name="is_full_tank" id="isFullTank" value="1" class="w-4 h-4 text-green-600 rounded focus:ring-green-500">
                                            <span class="text-sm text-gray-700">Full Tank</span>
                                        </label>
                                        <label class="flex items-center gap-2 cursor-pointer">
                                            <input type="checkbox" name="is_maintenance_fuel" id="isMaintenanceFuel" value="1" class="w-4 h-4 text-green-600 rounded focus:ring-green-500">
                                            <span class="text-sm text-gray-700">Maintenance Fuel</span>
                                        </label>
                                    </div>
                                </div>
                                
                                <!-- Modal Footer -->
                                <div class="mt-6 flex justify-end gap-3 pt-4 border-t">
                                    <button type="button" onclick="closeFuelModal()" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition">
                                        Cancel
                                    </button>
                                    <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition flex items-center gap-2">
                                        <i class="fas fa-save"></i> Save Record
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Fuel Statistics -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                <div class="bg-blue-50 rounded-lg p-4 text-center">
                    <p class="text-2xl font-bold text-blue-700" id="totalFuel">0</p>
                    <p class="text-xs text-gray-600">Total Fuel (L)</p>
                </div>
                <div class="bg-green-50 rounded-lg p-4 text-center">
                    <p class="text-2xl font-bold text-green-700" id="totalFuelCost">GHS 0</p>
                    <p class="text-xs text-gray-600">Total Cost</p>
                </div>
                <div class="bg-purple-50 rounded-lg p-4 text-center">
                    <p class="text-2xl font-bold text-purple-700" id="avgFuelEfficiency">0</p>
                    <p class="text-xs text-gray-600">Avg Efficiency (km/L)</p>
                </div>
                <div class="bg-orange-50 rounded-lg p-4 text-center">
                    <p class="text-2xl font-bold text-orange-700" id="avgCostPerKm">GHS 0</p>
                    <p class="text-xs text-gray-600">Avg Cost/km</p>
                </div>
            </div>
            
            <!-- Fuel Log Table -->
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fuel Type</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Quantity (L)</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Cost (GHS)</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Odometer (km)</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Efficiency</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Station</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="fuelLogBody">
                        <tr>
                            <td colspan="8" class="px-6 py-8 text-center text-gray-500">
                                <div class="loading-spinner"></div> Loading fuel records...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination for Fuel -->
            <div id="fuelPagination" class="mt-6"></div>
        </div>
        <!-- Create Maintenance Job Order Modal -->
        <div id="createMaintenanceModal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" aria-hidden="true" onclick="closeMaintenanceModal()"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                
                <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-3xl sm:w-full border border-slate-200">
                    <!-- Modal Header -->
                    <div class="absolute top-4 right-4">
                        <button onclick="closeMaintenanceModal()" class="text-slate-400 hover:text-slate-600 transition" aria-label="Close modal" title="Close">
                            <i class="fas fa-times text-xl"></i>
                        </button>
                    </div>
                    
                    <div class="bg-white px-6 pt-6 pb-6">
                        <div class="flex items-center gap-3 mb-6">
                            <div class="w-12 h-12 bg-orange-100 rounded-full flex items-center justify-center">
                                <i class="fas fa-tools text-orange-600 text-xl"></i>
                            </div>
                            <div>
                                <h3 class="text-xl font-bold text-slate-800">Create Maintenance Job Order</h3>
                                <p class="text-sm text-slate-500">Create a new maintenance request for {{ $vehicle->registration_number }}</p>
                            </div>
                        </div>
                        
                        <form id="maintenanceJobOrderForm" enctype="multipart/form-data">
                            @csrf
                            <input type="hidden" name="vehicle_id" value="{{ $vehicle->id }}">
                            
                            <div class="space-y-4">
                                <!-- Maintenance Type Selection -->
                                <div>
                                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Maintenance Type *</label>
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                                        <label class="relative flex items-start p-3 border-2 rounded-xl cursor-pointer hover:bg-orange-50 transition maintenance-type-card" data-type="general_service">
                                            <input type="radio" name="maintenance_type" value="general_service" class="mt-1 mr-3" required>
                                            <div>
                                                <div class="font-semibold text-gray-800 flex items-center gap-2">
                                                    <i class="fas fa-oil-can text-green-600"></i>
                                                    General Service
                                                </div>
                                                <p class="text-xs text-gray-500 mt-1">Oil change, filter replacement, basic inspection</p>
                                            </div>
                                        </label>
                                        
                                        <label class="relative flex items-start p-3 border-2 rounded-xl cursor-pointer hover:bg-orange-50 transition maintenance-type-card" data-type="specific">
                                            <input type="radio" name="maintenance_type" value="specific" class="mt-1 mr-3">
                                            <div>
                                                <div class="font-semibold text-gray-800 flex items-center gap-2">
                                                    <i class="fas fa-tasks text-blue-600"></i>
                                                    Specific Maintenance
                                                </div>
                                                <p class="text-xs text-gray-500 mt-1">Select specific services from checklist</p>
                                            </div>
                                        </label>
                                        
                                        <label class="relative flex items-start p-3 border-2 rounded-xl cursor-pointer hover:bg-orange-50 transition maintenance-type-card" data-type="both">
                                            <input type="radio" name="maintenance_type" value="both" class="mt-1 mr-3">
                                            <div>
                                                <div class="font-semibold text-gray-800 flex items-center gap-2">
                                                    <i class="fas fa-layer-group text-purple-600"></i>
                                                    Both
                                                </div>
                                                <p class="text-xs text-gray-500 mt-1">General service + specific services</p>
                                            </div>
                                        </label>
                                    </div>
                                </div>
                                
                                 <!-- Checklist Section (shown for specific or both) -->
                                <div id="modalChecklistSection" class="hidden">
                                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Select Services *</label>
                                    <div class="border rounded-lg p-3 max-h-60 overflow-y-auto bg-gray-50">
                                        @if(isset($checklistItems) && $checklistItems->count() > 0)
                                            @php
                                                $groupedItems = $checklistItems->groupBy('category');
                                                $categoryNames = \App\Models\MaintenanceChecklistItem::getCategories();
                                            @endphp
                                            
                                            @foreach($groupedItems as $category => $items)
                                            <div class="mb-3">
                                                <h4 class="font-semibold text-gray-700 text-sm mb-2">{{ $categoryNames[$category]['name'] ?? ucfirst($category) }}</h4>
                                                <div class="space-y-2 ml-4">
                                                    @foreach($items as $item)
                                                    <label class="flex items-center gap-2">
                                                        <input type="checkbox" name="selected_services[]" value="{{ $item->id }}" class="service-checkbox rounded" data-name="{{ $item->item_name }}">
                                                        <span class="text-sm text-gray-700">{{ $item->item_name }}</span>
                                                    </label>
                                                    @endforeach
                                                </div>
                                            </div>
                                            @endforeach
                                        @else
                                            <div class="text-center py-4">
                                                <p class="text-sm text-gray-500 italic">No predefined checklist items found. Use "Other" to specify services.</p>
                                            </div>
                                        @endif
                                        
                                        <div class="mt-2 pt-2 border-t border-gray-200">
                                            <label class="flex items-center gap-2">
                                                <input type="checkbox" id="otherServiceCheck" class="rounded">
                                                <span class="text-sm font-medium text-gray-700">Other (Specify below)</span>
                                            </label>
                                            <input type="text" id="otherServiceInput" class="hidden w-full mt-2 px-3 py-2 border rounded-lg text-sm" placeholder="Enter other service details">
                                        </div>
                                    </div>
                                    <div id="selectedServicesDisplay" class="mt-2 flex flex-wrap gap-2"></div>
                                </div>
                                
                                <!-- Job Details -->
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Description *</label>
                                        <textarea name="description" rows="3" class="w-full px-3 py-2 border rounded-lg text-sm focus:ring-2 focus:ring-orange-500 focus:border-orange-500" placeholder="Describe the issue or maintenance required..." required></textarea>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Technician Notes</label>
                                        <textarea name="technician_notes" rows="3" class="w-full px-3 py-2 border rounded-lg text-sm focus:ring-2 focus:ring-orange-500 focus:border-orange-500" placeholder="Any specific instructions for the mechanic..."></textarea>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Priority</label>
                                        <select name="priority" class="w-full px-3 py-2 border rounded-lg text-sm focus:ring-2 focus:ring-orange-500">
                                            <option value="low">🟢 Low - Can wait</option>
                                            <option value="medium" selected>🔵 Medium - Schedule soon</option>
                                            <option value="high">🟠 High - As soon as possible</option>
                                            <option value="urgent">🔴 Urgent - Immediate attention</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Scheduled Date</label>
                                        <input type="date" name="scheduled_date" class="w-full px-3 py-2 border rounded-lg text-sm" value="{{ date('Y-m-d', strtotime('+3 days')) }}">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Service Provider</label>
                                        <input type="text" name="service_provider" class="w-full px-3 py-2 border rounded-lg text-sm" placeholder="Mechanic shop name">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Driver</label>
                                        <div class="relative driver-search-group">
                                            <input type="text" class="driver-search-input w-full px-3 py-2 border rounded-lg text-sm focus:ring-2 focus:ring-orange-500 focus:border-orange-500 outline-none transition" 
                                                   list="driversDataList" placeholder="Search or type driver name..." 
                                                   value="{{ $vehicle->driver->name ?? '' }}" autocomplete="off">
                                            <input type="hidden" name="driver_id" class="driver-id-input" value="{{ $vehicle->assigned_driver_id }}">
                                            <div class="driver-details-box mt-2 p-2 bg-orange-50 border border-orange-100 rounded-xl hidden"></div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Estimated Cost (Optional) -->
                                <div>
                                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Estimated Cost (Optional)</label>
                                    <input type="number" name="estimated_cost" class="w-full px-3 py-2 border rounded-lg text-sm" step="0.01" placeholder="GHS 0.00">
                                    <p class="text-xs text-gray-400 mt-1">The mechanic will provide final invoice after work completion</p>
                                </div>
                            </div>
                            
                            <!-- Modal Footer -->
                            <div class="mt-6 flex justify-end gap-3 pt-4 border-t">
                                <button type="button" onclick="closeMaintenanceModal()" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition">
                                    Cancel
                                </button>
                                <button type="submit" class="px-4 py-2 bg[#3160ED] text-white rounded-lg hover:bg-orange-700 transition flex items-center gap-2">
                                    <i class="fas fa-paper-plane"></i> Create Job Order
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
// Helper Functions
function formatDate(dateString) {
    if (!dateString) return '—';
    const date = new Date(dateString);
    if (isNaN(date.getTime())) return '—';
    return date.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
}

function numberFormat(number) {
    if (number === null || number === undefined) return '0';
    return new Intl.NumberFormat().format(number);
}

// Chart initialization
let mileageChart = null;

$(document).ready(function() {
    try {
        initMileageChart();
    } catch (e) {
        console.warn("Chart initialization failed, likely due to missing library:", e.message);
    }
    
    // Tab switching
    $('.tab-btn').click(function() {
        let tabId = $(this).data('tab');
        console.log('Switching to tab:', tabId);
        
        $('.tab-btn').removeClass('tab-active text-blue-600 border-blue-600').addClass('text-gray-600');
        $(this).addClass('tab-active text-blue-600 border-blue-600');
        
        $('.tab-content').addClass('hidden');
        $(`#${tabId}-tab`).removeClass('hidden');
        
        if (tabId === 'performance') {
            try {
                setTimeout(() => initMileageChart(), 100);
            } catch (e) {}
        }
        
        if (tabId === 'mileage') {
            console.log('Loading mileage log...');
            loadMileageLog();
        }
        
        if (tabId === 'fuel') {
            console.log('Loading fuel log...');
            loadFuelLog();
        }
    });
});

function initMileageChart() {
    if (mileageChart) mileageChart.destroy();
    
    const mileageData = @json($mileageBreakdown);
    const labels = mileageData.map(item => item.period);
    const distanceValues = mileageData.map(item => item.distance);
    const fuelValues = mileageData.map(item => item.fuel_used);

    const chartCanvas = document.getElementById('mileageChart');
    if (!chartCanvas) return;

    mileageChart = new Chart(chartCanvas, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Distance (km)',
                    data: distanceValues,
                    backgroundColor: 'rgba(37, 99, 235, 0.75)',
                    borderColor: 'rgba(37, 99, 235, 1)',
                    borderWidth: 1,
                    yAxisID: 'y'
                },
                {
                    label: 'Fuel Used (L)',
                    data: fuelValues,
                    type: 'line',
                    borderColor: 'rgba(245, 158, 11, 1)',
                    backgroundColor: 'rgba(245, 158, 11, 0.2)',
                    borderWidth: 2,
                    tension: 0.35,
                    fill: true,
                    yAxisID: 'y1'
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                mode: 'index',
                intersect: false
            },
            plugins: {
                legend: {
                    position: 'top'
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            if (context.dataset.label === 'Distance (km)') {
                                return `${context.dataset.label}: ${Number(context.raw).toLocaleString()} km`;
                            }

                            return `${context.dataset.label}: ${Number(context.raw).toLocaleString()} L`;
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    position: 'left',
                    title: {
                        display: true,
                        text: 'Distance (km)'
                    }
                },
                y1: {
                    beginAtZero: true,
                    position: 'right',
                    grid: {
                        drawOnChartArea: false
                    },
                    title: {
                        display: true,
                        text: 'Fuel (L)'
                    }
                }
            }
        }
    });
}

function viewMaintenance(id) {
    Swal.fire({
        icon: 'info',
        title: 'Maintenance Record',
        text: `Selected record #${id}.`
    });
}

// Document Upload Logic
function showUploadModal() {
    $('#uploadDocumentModal').removeClass('hidden');
    $('body').addClass('overflow-hidden');
}

function hideUploadModal() {
    $('#uploadDocumentModal').addClass('hidden');
    $('body').removeClass('overflow-hidden');
    $('#uploadDocumentForm')[0].reset();
    $('#fileNameDisplay').addClass('hidden');
}

$(document).ready(function() {
    $('#uploadDocumentForm').on('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const submitBtn = $('#submitUpload');
        const originalBtnText = submitBtn.html();
        
        submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Uploading...');
        
        $.ajax({
            url: "{{ route('vehicles.documents.store') }}",
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                'X-Requested-With': 'XMLHttpRequest'
            },
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: response.message,
                        timer: 2000,
                        showConfirmButton: false
                    }).then(() => {
                        window.location.reload(); // Refresh to show the new document
                    });
                    hideUploadModal();
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Upload Failed',
                        text: response.message
                    });
                }
            },
            error: function(xhr) {
                let message = 'An error occurred during upload.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    message = xhr.responseJSON.message;
                }
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: message
                });
            },
            complete: function() {
                submitBtn.prop('disabled', false).html(originalBtnText);
            }
        });
    });
});

// Mileage Log Functions
function loadMileageLog(page = 1) {
    const body = $('#mileageLogBody');
    body.html('<tr><td colspan="6" class="px-6 py-8 text-center text-gray-500"><div class="loading-spinner"></div> Loading mileage records...</td></tr>');
    
    $.ajax({
        url: `{{ route('vehicles.mileage.log', $vehicle->id, false) }}?page=${page}`,
        method: 'GET',
        success: function(response) {
            if (response.success) {
                renderMileageLog(response.data);
                renderMileagePagination(response.pagination);
            } else {
                body.html(`<tr><td colspan="6" class="px-6 py-8 text-center text-red-500">Error: ${response.message || 'Unknown error'}</td></tr>`);
            }
        },
        error: function(xhr) {
            let errorMsg = 'Failed to load mileage records';
            if (xhr.responseJSON && xhr.responseJSON.message) {
                errorMsg += ': ' + xhr.responseJSON.message;
            }
            body.html(`<tr><td colspan="6" class="px-6 py-8 text-center text-red-500">${errorMsg}</td></tr>`);
        }
    });
}

function renderMileageLog(records) {
    if (!records || records.length === 0) {
        $('#mileageLogBody').html('<tr><td colspan="6" class="px-6 py-8 text-center text-gray-500">No mileage records found</td></tr>');
        return;
    }
    
    let html = '';
    records.forEach(record => {
        html += `
            <tr>
                <td class="px-6 py-4 text-sm">${formatDate(record.date)}</td>
                <td class="px-6 py-4 text-sm">${record.recorded_by || 'System'}</td>
                <td class="px-6 py-4 text-sm text-right font-mono">${numberFormat(record.odometer)}</td>
                <td class="px-6 py-4 text-sm text-right">${numberFormat(record.distance)}</td>
                <td class="px-6 py-4 text-sm text-gray-500">${record.notes || '—'}</td>
                <td class="px-6 py-4 text-sm text-center">
                    <button onclick="deleteMileageRecord(${record.id})" class="text-red-600 hover:text-red-800" aria-label="Delete mileage record" title="Delete Record">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            </tr>
        `;
    });
    $('#mileageLogBody').html(html);
}

function renderMileagePagination(pagination) {
    if (!pagination || pagination.last_page <= 1) {
        $('#mileagePagination').empty();
        return;
    }
    
    let html = '<div class="flex justify-center gap-2">';
    for (let i = 1; i <= pagination.last_page; i++) {
        if (i === pagination.current_page) {
            html += `<button class="px-3 py-1 bg-blue-600 text-white rounded">${i}</button>`;
        } else {
            html += `<button onclick="loadMileageLog(${i})" class="px-3 py-1 border rounded hover:bg-gray-50">${i}</button>`;
        }
    }
    html += '</div>';
    $('#mileagePagination').html(html);
}

// Fuel Log Functions
function loadFuelLog(page = 1) {
    const body = $('#fuelLogBody');
    body.html('<tr><td colspan="8" class="px-6 py-8 text-center text-gray-500"><div class="loading-spinner"></div> Loading fuel records...</td></tr>');
    
    $.ajax({
        url: `{{ route('vehicles.fuel.log', $vehicle->id, false) }}?page=${page}`,
        method: 'GET',
        success: function(response) {
            console.log('Fuel log response:', response);
            if (response.success) {
                renderFuelLog(response.data);
                renderFuelPagination(response.pagination);
                updateFuelStats(response.stats);
            } else {
                body.html(`<tr><td colspan="8" class="px-6 py-8 text-center text-red-500">Error: ${response.message || 'Unknown error'}</td></tr>`);
            }
        },
        error: function(xhr) {
            console.error('Fuel log fetch failed:', xhr);
            let errorMsg = 'Failed to load fuel records';
            if (xhr.responseJSON && xhr.responseJSON.message) {
                errorMsg += ': ' + xhr.responseJSON.message;
            }
            body.html(`<tr><td colspan="8" class="px-6 py-8 text-center text-red-500">${errorMsg}</td></tr>`);
        }
    });
}

function renderFuelLog(records) {
    if (!records || records.length === 0) {
        $('#fuelLogBody').html('<tr><td colspan="8" class="px-6 py-8 text-center text-gray-500">No fuel records found</td></tr>');
        return;
    }
    
    let html = '';
    records.forEach(record => {
        const qty = Number(record.fuel_quantity) || 0;
        const cost = Number(record.fuel_cost) || 0;
        const odo = Number(record.odometer) || 0;
        const dist = Number(record.distance_traveled) || 0;
        
        const efficiency = dist > 0 && qty > 0 ? (dist / qty).toFixed(1) : '—';
        html += `
            <tr>
                <td class="px-6 py-4 text-sm">${formatDate(record.date)}</td>
                <td class="px-6 py-4 text-sm">
                    <span class="px-2 py-1 rounded-full text-xs ${getFuelTypeClass(record.fuel_type)}">
                        ${record.fuel_type ? record.fuel_type.toUpperCase() : 'N/A'}
                    </span>
                </td>
                <td class="px-6 py-4 text-sm text-right">${qty.toFixed(1)}</td>
                <td class="px-6 py-4 text-sm text-right font-semibold">GHS ${cost.toFixed(2)}</td>
                <td class="px-6 py-4 text-sm text-right">${numberFormat(odo)}</td>
                <td class="px-6 py-4 text-sm text-right">
                    ${efficiency !== '—' ? `<span class="text-green-600 font-medium">${efficiency} km/L</span>` : '—'}
                </td>
                <td class="px-6 py-4 text-sm">${record.fuel_station || '—'}</td>
                <td class="px-6 py-4 text-sm text-center">
                    <button onclick="deleteFuelRecord(${record.id})" class="text-red-600 hover:text-red-800" aria-label="Delete fuel record" title="Delete Record">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            </tr>
        `;
    });
    $('#fuelLogBody').html(html);
}

function renderFuelPagination(pagination) {
    if (!pagination || pagination.last_page <= 1) {
        $('#fuelPagination').empty();
        return;
    }
    
    let html = '<div class="flex justify-center gap-2">';
    for (let i = 1; i <= pagination.last_page; i++) {
        if (i === pagination.current_page) {
            html += `<button class="px-3 py-1 bg-blue-600 text-white rounded">${i}</button>`;
        } else {
            html += `<button onclick="loadFuelLog(${i})" class="px-3 py-1 border rounded hover:bg-gray-50">${i}</button>`;
        }
    }
    html += '</div>';
    $('#fuelPagination').html(html);
}

function updateFuelStats(stats) {
    if (stats) {
        $('#totalFuel').text(Number(stats.total_fuel || 0).toFixed(1));
        $('#totalFuelCost').text('GHS ' + Number(stats.total_cost || 0).toFixed(2));
        $('#avgFuelEfficiency').text(Number(stats.avg_efficiency || 0).toFixed(1));
        $('#avgCostPerKm').text('GHS ' + Number(stats.avg_cost_per_km || 0).toFixed(2));
    }
}

function getFuelTypeClass(type) {
    const classes = {
        'petrol': 'bg-blue-100 text-blue-700',
        'diesel': 'bg-orange-100 text-orange-700',
        'electric': 'bg-green-100 text-green-700',
        'hybrid': 'bg-purple-100 text-purple-700'
    };
    return classes[type] || 'bg-gray-100 text-gray-700';
}

// Delete functions
function deleteMileageRecord(id) {
    Swal.fire({
        title: 'Delete Record?',
        text: 'This action cannot be undone.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        confirmButtonText: 'Yes, delete'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: `/vehicles/mileage/${id}`,
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                success: function() {
                    Swal.fire('Deleted!', 'Record deleted successfully.', 'success');
                    loadMileageLog();
                },
                error: function() {
                    Swal.fire('Error!', 'Failed to delete record.', 'error');
                }
            });
        }
    });
}

function deleteFuelRecord(id) {
    Swal.fire({
        title: 'Delete Record?',
        text: 'This action cannot be undone.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        confirmButtonText: 'Yes, delete'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: `/vehicles/fuel/${id}`,
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                success: function() {
                    Swal.fire('Deleted!', 'Record deleted successfully.', 'success');
                    loadFuelLog();
                },
                error: function() {
                    Swal.fire('Error!', 'Failed to delete record.', 'error');
                }
            });
        }
    });
}

// Modal functions for adding records
$(document).ready(function() {
    // Calculate distance in form
    $('#formStartMileage, #formEndMileage').on('input', function() {
        let start = parseFloat($('#formStartMileage').val()) || 0;
        let end = parseFloat($('#formEndMileage').val()) || 0;
        let distance = end - start;
        
        if (distance > 0) {
            $('#formDistanceDisplay').text(distance.toFixed(1) + ' km').css('color', '#10b981');
        } else if (distance < 0) {
            $('#formDistanceDisplay').text('Invalid (End must be greater than Start)').css('color', '#ef4444');
        } else {
            $('#formDistanceDisplay').text('0 km').css('color', '#6b7280');
        }
    });
    
    // Handle form submission
    $('#mileageForm').on('submit', function(e) {
        e.preventDefault();
        
        const $form = $(this);
        const $submitBtn = $form.find('button[type="submit"]');
        const originalHtml = $submitBtn.html();

        $submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-2"></i>Saving...');

        let formData = $(this).serialize();
        
        $.ajax({
            url: '{{ route("vehicles.mileage.store") }}',
            method: 'POST',
            data: formData,
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            success: function(response) {
                Swal.fire('Success', 'Mileage recorded successfully', 'success');
                closeMileageModal();
                loadMileageLog();
                location.reload();
            },
            error: function(xhr) {
                Swal.fire('Error', xhr.responseJSON?.message || 'Failed to record mileage', 'error');
            },
            complete: function() {
                $submitBtn.prop('disabled', false).html(originalHtml);
            }
        });
    });
});

function openMileageModal() {
    $('#mileageModal').removeClass('hidden');
    $('body').addClass('overflow-hidden');
    // Set default end mileage
    let currentMileage = {{ $vehicle->mileage ?? 0 }};
    $('#formEndMileage').val(currentMileage + 100);
}

function closeMileageModal() {
    $('#mileageModal').addClass('hidden');
    $('body').removeClass('overflow-hidden');
    $('#mileageForm')[0].reset();
}
// Fuel Modal Functions
let previousOdometerValue = 0;

function openFuelModal() {
    $('#fuelModal').removeClass('hidden');
    $('body').addClass('overflow-hidden');
    fetchPreviousOdometer();
}

function closeFuelModal() {
    $('#fuelModal').addClass('hidden');
    $('body').removeClass('overflow-hidden');
    $('#fuelForm')[0].reset();
    $('#fuelDate').val(new Date().toISOString().split('T')[0]);
}

function fetchPreviousOdometer() {
    $.ajax({
        url: '{{ route("vehicles.fuel.previous", $vehicle->id) }}',
        method: 'GET',
        success: function(response) {
            if (response.previous_odometer) {
                previousOdometerValue = response.previous_odometer;
                $('#previousOdometerDisplay').html(previousOdometerValue.toLocaleString() + ' km');
                if (response.previous_date) {
                    $('#previousOdometerDisplay').append(`<span class="text-xs text-gray-400 ml-2">(${response.previous_date})</span>`);
                }
            } else {
                previousOdometerValue = 0;
                $('#previousOdometerDisplay').html('0 km (First record)');
            }
        },
        error: function() {
            previousOdometerValue = 0;
            $('#previousOdometerDisplay').html('0 km (No previous records)');
        }
    });
}

// Real-time calculations
$(document).ready(function() {
    // Calculate distance, price per unit, and efficiency
    function calculateFuelMetrics() {
        let odometer = parseFloat($('#fuelOdometer').val()) || 0;
        let quantity = parseFloat($('#fuelQuantity').val()) || 0;
        let cost = parseFloat($('#fuelCost').val()) || 0;
        
        // Calculate distance
        let distance = odometer - previousOdometerValue;
        if (distance > 0) {
            $('#distanceTraveledDisplay').html(`<span class="text-green-600 font-medium">${distance.toFixed(1)} km</span>`);
        } else if (previousOdometerValue === 0) {
            $('#distanceTraveledDisplay').html('<span class="text-gray-500">0 km (First record)</span>');
        } else {
            $('#distanceTraveledDisplay').html('<span class="text-red-500">Invalid (Odometer must be greater than previous)</span>');
        }
        
        // Calculate price per unit
        if (quantity > 0 && cost > 0) {
            let pricePerUnit = cost / quantity;
            $('#pricePerUnitDisplay').html(`<span class="text-blue-600 font-medium">GHS ${pricePerUnit.toFixed(2)}</span>`);
        } else {
            $('#pricePerUnitDisplay').html('<span class="text-gray-400">--</span>');
        }
        
        // Calculate fuel efficiency
        if (distance > 0 && quantity > 0) {
            let efficiency = distance / quantity;
            let color = efficiency > 10 ? 'text-green-600' : (efficiency > 5 ? 'text-yellow-600' : 'text-red-600');
            $('#fuelEfficiencyDisplay').html(`<span class="${color} font-medium">${efficiency.toFixed(2)} km/L</span>`);
        } else {
            $('#fuelEfficiencyDisplay').html('<span class="text-gray-400">--</span>');
        }
    }
    
    // Attach event listeners
    $('#fuelOdometer, #fuelQuantity, #fuelCost').on('input', calculateFuelMetrics);
    
    // Form submission
    $('#fuelForm').on('submit', function(e) {
        e.preventDefault();
        
        let formData = $(this).serialize();
        
        // Show loading state on button
        let submitBtn = $(this).find('button[type="submit"]');
        let originalText = submitBtn.html();
        submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Saving...');
        
        $.ajax({
url: '{{ route("vehicles.fuel.store") }}',
            method: 'POST',
            data: formData,
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            success: function(response) {
                // Show success message using a simple toast or alert
                showNotification('success', 'Fuel record added successfully!');
                closeFuelModal();
                if (typeof loadFuelLog === 'function') {
                    loadFuelLog();
                }
                setTimeout(() => location.reload(), 1000);
            },
            error: function(xhr) {
                let errorMessage = 'Failed to add fuel record';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                }
                showNotification('error', errorMessage);
            },
            complete: function() {
                submitBtn.prop('disabled', false).html(originalText);
            }
        });
    });
});

// Simple notification function (you can customize this)
function copyToClipboard(text) {
    if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(text).then(() => {
            showNotification('success', 'Registration number copied to clipboard');
        }).catch(err => {
            console.error('Could not copy text: ', err);
        });
    } else {
        // Fallback for non-secure contexts
        const textArea = document.createElement("textarea");
        textArea.value = text;
        document.body.appendChild(textArea);
        textArea.select();
        try {
            document.execCommand('copy');
            showNotification('success', 'Registration number copied to clipboard');
        } catch (err) {
            console.error('Fallback copy failed: ', err);
        }
        document.body.removeChild(textArea);
    }
}

function showNotification(type, message) {
    // Create notification element
    let notification = $(`
        <div class="fixed top-4 right-4 z-50 animate-slide-in">
            <div class="px-4 py-3 rounded-lg shadow-lg flex items-center gap-3 ${
                type === 'success' ? 'bg-green-500' : 'bg-red-500'
            } text-white">
                <i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'}"></i>
                <span class="text-sm">${message}</span>
                <button onclick="$(this).closest('.fixed').remove()" class="ml-4 text-white hover:text-gray-200" aria-label="Dismiss notification" title="Dismiss">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
    `);
    
    $('body').append(notification);
    
    // Auto remove after 3 seconds
    setTimeout(() => {
        notification.fadeOut(300, function() { $(this).remove(); });
    }, 3000);
}

// Add CSS for slide-in animation
$('<style>')
    .prop('type', 'text/css')
    .html(`
        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        .animate-slide-in {
            animation: slideIn 0.3s ease-out;
        }
    `)
    .appendTo('head');

// Update tab switching to load data when tabs are clicked

   
    
    $(document).on('click', '[data-tab="fuel"]', function() {
        loadFuelLog();
    });
</script>

<!-- Upload Document Modal -->
<div id="uploadDocumentModal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" aria-hidden="true" onclick="hideUploadModal()"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full border border-slate-200">
            <div class="absolute top-4 right-4">
                <button onclick="hideUploadModal()" class="text-slate-400 hover:text-slate-600 transition" aria-label="Close modal" title="Close">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form id="uploadDocumentForm" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="vehicle_id" value="{{ $vehicle->id }}">
                <div class="bg-white px-6 pt-6 pb-6">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="w-10 h-10 bg-blue-50 text-blue-600 rounded-full flex items-center justify-center">
                            <i class="fas fa-file-upload text-lg"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-slate-800" id="modal-title">Upload Document</h3>
                            <p class="text-xs text-slate-500">Add a new document for vehicle {{ $vehicle->registration_number }}</p>
                        </div>
                    </div>
                    
                    <div class="space-y-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Document Title</label>
                            <input type="text" name="title" required placeholder="e.g. Insurance Policy 2024" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none transition text-sm">
                        </div>
                        
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Document Type</label>
                                <select name="document_type" required class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none transition text-sm appearance-none">
                                    @foreach(\App\Models\Document::getDocumentTypes() as $type)
                                        <option value="{{ $type }}">{{ ucfirst(str_replace('_', ' ', $type)) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Ref Number</label>
                                <input type="text" name="reference_number" placeholder="Optional" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none transition text-sm">
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Issue Date</label>
                                <input type="date" name="issue_date" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none transition text-sm">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Expiry Date</label>
                                <input type="date" name="expiry_date" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none transition text-sm">
                            </div>
                        </div>
                        
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Select File</label>
                            <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-slate-200 border-dashed rounded-2xl hover:border-blue-400 transition cursor-pointer bg-slate-50/50 group" onclick="document.getElementById('fileInput').click()">
                                <div class="space-y-1 text-center">
                                    <i class="fas fa-cloud-upload-alt text-2xl text-slate-400 group-hover:text-blue-500 transition mb-2"></i>
                                    <div class="flex text-sm text-slate-600">
                                        <span class="relative cursor-pointer font-semibold text-blue-600 hover:text-blue-500">Upload a file</span>
                                        <p class="pl-1">or drag and drop</p>
                                    </div>
                                    <p class="text-xs text-slate-400">PDF, PNG, JPG up to 10MB</p>
                                    <input id="fileInput" name="files[]" type="file" class="sr-only" required multiple onchange="updateFileNames(this)">
                                    <p id="fileNameDisplay" class="text-xs font-medium text-blue-600 mt-2 hidden"></p>
                                </div>
                            </div>
                        </div>
                        
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Description</label>
                            <textarea name="description" rows="2" placeholder="Additional details..." class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none transition text-sm"></textarea>
                        </div>
                    </div>
                </div>
                <div class="bg-slate-50 px-6 py-4 flex flex-row-reverse gap-3 border-t border-slate-100">
                    <button type="submit" id="submitUpload" class="px-6 py-2.5 bg-blue-600 text-white rounded-xl text-sm font-bold shadow-lg shadow-blue-500/20 hover:bg-blue-700 transition flex items-center gap-2">
                        <span>Start Upload</span>
                        <i class="fas fa-arrow-right text-xs"></i>
                    </button>
                    <button type="button" onclick="hideUploadModal()" class="px-6 py-2.5 bg-white text-slate-600 border border-slate-200 rounded-xl text-sm font-bold hover:bg-slate-50 transition">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function updateFileNames(input) {
        const display = document.getElementById('fileNameDisplay');
        if (input.files && input.files.length > 0) {
            if (input.files.length === 1) {
                display.textContent = input.files[0].name;
            } else {
                display.textContent = input.files.length + ' files selected';
            }
            display.classList.remove('hidden');
        } else {
            display.classList.add('hidden');
        }
    }

    // Maintenance Job Order Functions
function openCreateMaintenanceModal() {
    $('#createMaintenanceModal').removeClass('hidden');
    $('body').addClass('overflow-hidden');
    resetMaintenanceForm();
}

function closeMaintenanceModal() {
    $('#createMaintenanceModal').addClass('hidden');
    $('body').removeClass('overflow-hidden');
    resetMaintenanceForm();
}

function resetMaintenanceForm() {
    $('#maintenanceJobOrderForm')[0].reset();
    $('#modalChecklistSection').addClass('hidden');
    $('#selectedServicesDisplay').empty();
    $('.maintenance-type-card').removeClass('border-orange-500 bg-orange-50');
    $('.service-checkbox').prop('checked', false);
    $('#otherServiceInput').addClass('hidden').val('');
    $('#otherServiceCheck').prop('checked', false);
}

// Handle maintenance type selection styling
$(document).on('click', '.maintenance-type-card', function() {
    $('.maintenance-type-card').removeClass('border-orange-500 bg-orange-50');
    $(this).addClass('border-orange-500 bg-orange-50');
    
    let type = $(this).data('type');
    if (type === 'specific' || type === 'both') {
        $('#modalChecklistSection').removeClass('hidden').fadeIn();
        if (type === 'both') {
            $('#selectedServicesDisplay').prepend('<span class="service-tag bg-green-100 text-green-700 px-2 py-1 rounded-full text-xs">General Service Package</span>');
        }
    } else {
        $('#modalChecklistSection').addClass('hidden').fadeOut();
        $('#selectedServicesDisplay').empty();
    }
});

// Update selected services display
$(document).on('change', '.service-checkbox', function() {
    updateSelectedServicesDisplay();
});

function updateSelectedServicesDisplay() {
    let selected = [];
    $('.service-checkbox:checked').each(function() {
        selected.push($(this).val());
    });
    
    let html = '';
    selected.forEach(service => {
        html += `<span class="service-tag bg-blue-100 text-blue-700 px-2 py-1 rounded-full text-xs flex items-center gap-1">
                    <i class="fas fa-check-circle text-green-600 text-xs"></i>
                    ${service}
                    <button type="button" onclick="removeService('${service}')" class="text-red-500 hover:text-red-700 ml-1" aria-label="Remove service" title="Remove Service">
                        <i class="fas fa-times-circle text-xs"></i>
                    </button>
                </span>`;
    });
    
    $('#selectedServicesDisplay').html(html);
}

function removeService(serviceName) {
    $(`.service-checkbox[value="${serviceName}"]`).prop('checked', false);
    updateSelectedServicesDisplay();
}

// Handle other service input
$('#otherServiceCheck').on('change', function() {
    if ($(this).is(':checked')) {
        $('#otherServiceInput').removeClass('hidden');
    } else {
        $('#otherServiceInput').addClass('hidden');
        $('#otherServiceInput').val('');
    }
});

// Form submission
$('#maintenanceJobOrderForm').on('submit', function(e) {
    e.preventDefault();
    
    let maintenanceType = $('input[name="maintenance_type"]:checked').val();
    if (!maintenanceType) {
        Swal.fire('Error', 'Please select a maintenance type', 'error');
        return;
    }
    
    if ((maintenanceType === 'specific' || maintenanceType === 'both') && $('.service-checkbox:checked').length === 0 && !$('#otherServiceInput').val()) {
        Swal.fire('Warning', 'Please select at least one service from the checklist', 'warning');
        return;
    }
    
    let description = $('textarea[name="description"]').val();
    if (!description) {
        Swal.fire('Error', 'Please provide a description', 'error');
        return;
    }
    
    // Collect selected services
    let selectedServices = [];
    $('.service-checkbox:checked').each(function() {
        selectedServices.push($(this).val());
    });
    
    let otherService = $('#otherServiceInput').val();
    if (otherService) {
        selectedServices.push(otherService);
    }
    
    let formData = {
        vehicle_id: {{ $vehicle->id }},
        maintenance_type: maintenanceType,
        selected_services: selectedServices,
        description: $('textarea[name="description"]').val(),
        technician_notes: $('textarea[name="technician_notes"]').val(),
        priority: $('select[name="priority"]').val(),
        scheduled_date: $('input[name="scheduled_date"]').val(),
        service_provider: $('input[name="service_provider"]').val(),
        driver_id: $('#createMaintenanceModal .driver-id-input').val(),
        estimated_cost: $('input[name="estimated_cost"]').val(),
        _token: '{{ csrf_token() }}'
    };
    
    Swal.fire({
        title: 'Confirm Job Order',
        text: 'Create this maintenance job order?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#3160ED',
        confirmButtonText: 'Yes, create it!'
    }).then((result) => {
        if (result.isConfirmed) {
        const $submitBtn = $('#maintenanceJobOrderForm').find('button[type="submit"]');
        const originalHtml = $submitBtn.html();
        $submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-2"></i>Creating...');

            $.ajax({
                url: '{{ route("vehicles.maintenance.job-order.store", $vehicle->id) }}',
                method: 'POST',
                data: formData,
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                success: function(response) {
                    Swal.fire({
                        title: 'Success!',
                        text: response.message || 'Maintenance job order created successfully!',
                        icon: 'success',
                        confirmButtonColor: '#3160ED'
                    }).then(() => {
                        window.location.reload();
                    });
                },
                error: function(xhr) {
                    console.error('Submission error:', xhr);
                    let errorMessage = 'Failed to create job order. Please try again.';
                    
                    if (xhr.status === 401) {
                        errorMessage = 'Your session has expired. Please log in again.';
                    } else if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    }
                    
                    Swal.fire({
                        title: 'Error!',
                        text: errorMessage,
                        icon: 'error',
                        confirmButtonColor: '#3160ED'
                    });
            },
            complete: function() {
                $submitBtn.prop('disabled', false).html(originalHtml);
                }
            });
        }
    });
});

    
// Driver Search & Details Logic
const allDrivers = @json($drivers ?? []);

function updateDriverDetails(container, driverId) {
    const detailsBox = container.find('.driver-details-box');
    if (!driverId) {
        detailsBox.addClass('hidden').empty();
        return;
    }

    const driver = allDrivers.find(d => d.id == driverId);
    if (driver) {
        let html = `
            <div class="flex flex-col gap-1">
                <div class="flex items-center justify-between">
                    <span class="font-bold text-slate-700">${driver.name}</span>
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase ${driver.status === 'active' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'}">
                        ${driver.status}
                    </span>
                </div>
                <div class="grid grid-cols-2 gap-2 mt-1">
                    <div class="flex items-center gap-1.5 text-slate-500">
                        <i class="fas fa-id-card w-3"></i>
                        <span class="text-[10px]">${driver.license_number || 'No License'}</span>
                    </div>
                    <div class="flex items-center gap-1.5 text-slate-500">
                        <i class="fas fa-phone w-3"></i>
                        <span class="text-[10px]">${driver.emergency_contact_phone || 'No Phone'}</span>
                    </div>
                </div>
            </div>
        `;
        detailsBox.html(html).removeClass('hidden');
    } else {
        detailsBox.addClass('hidden').empty();
    }
}

$(document).on('input', '.driver-search-input', function() {
    const input = $(this);
    const container = input.closest('.driver-search-group');
    const idInput = container.find('.driver-id-input');
    const val = input.val();
    
    const option = $('#driversDataList option').filter(function() {
        return $(this).val() === val;
    });
    
    if (option.length) {
        const id = option.data('id');
        idInput.val(id);
        updateDriverDetails(container, id);
    } else {
        idInput.val('');
        updateDriverDetails(container, null);
    }
});

// Initialize details for already selected drivers
$(document).ready(function() {
    $('.driver-search-group').each(function() {
        const id = $(this).find('.driver-id-input').val();
        if (id) {
            updateDriverDetails($(this), id);
        }
    });
});

/**
 * Copy text to clipboard with fallback and notification
 */
function copyToClipboard(text) {
    if (!navigator.clipboard) {
        fallbackCopyToClipboard(text);
        return;
    }
    navigator.clipboard.writeText(text).then(function() {
        showNotification('success', 'Registration number copied to clipboard!');
    }, function(err) {
        console.error('Could not copy text: ', err);
        fallbackCopyToClipboard(text);
    });
}

function fallbackCopyToClipboard(text) {
    const textArea = document.createElement("textarea");
    textArea.value = text;

    // Ensure textarea is not visible but part of the DOM
    textArea.style.top = "0";
    textArea.style.left = "0";
    textArea.style.position = "fixed";
    textArea.style.opacity = "0";

    document.body.appendChild(textArea);
    textArea.focus();
    textArea.select();

    try {
        const successful = document.execCommand('copy');
        if (successful) {
            showNotification('success', 'Registration number copied to clipboard!');
        } else {
            showNotification('error', 'Failed to copy text.');
        }
    } catch (err) {
        console.error('Fallback: Oops, unable to copy', err);
        showNotification('error', 'Failed to copy text.');
    }

    document.body.removeChild(textArea);
}
</script>


@endsection
