<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>Ghana Water Limited | Vehicle Fleet Management</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <!-- Tailwind + Fonts + Icons -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite('resources/css/app.css')
    @endif
  
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,500;14..32,600;14..32,700&display=swap" rel="stylesheet">
    <style>
        * { font-family: 'Inter', 'Roboto', system-ui, -apple-system, sans-serif; }
        body { background: #f4f4f4; overflow-x: hidden; color: #202124; }
        /* Google Stitch Style */
        .google-card { background: #ffffff; border: 1px solid #e8eaed; border-radius: 8px; box-shadow: 0 1px 2px 0 rgba(60,64,67,.3), 0 1px 3px 1px rgba(60,64,67,.15); }
        .sidebar-google { background: #ffffff; border-right: 1px solid #e8eaed; }
        /* Sidebar transitions */
        .sidebar-fleet { position: fixed; top: 0; left: 0; height: 100vh; width: 280px; z-index: 40; transition: transform 0.2s cubic-bezier(0.4, 0, 0.2, 1); }
        .sidebar-closed { transform: translateX(-100%); }
        .overlay-fleet { position: fixed; inset: 0; background: rgba(32,33,36,0.6); z-index: 35; display: none; }
        .overlay-open { display: block; }
        /* Navigation items */
        .nav-item-fleet { transition: all 0.2s ease; border-radius: 0 24px 24px 0; margin-right: 12px; padding-left: 24px !important; }
        .nav-item-fleet:hover { background: #f8f9fa; color: #1a73e8; }
        .nav-item-fleet:focus-visible { outline: 2px solid #1a73e8; outline-offset: -2px; background: #f8f9fa; }
        .nav-active-fleet { background: #e8f0fe; color: #1a73e8; font-weight: 500; border-left: 4px solid #1a73e8; }
        .submenu-item { padding-left: 2.5rem; transition: all 0.2s; }
        .rotate-180 { transform: rotate(180deg); }
        /* custom scroll */
        ::-webkit-scrollbar { width: 5px; }
        ::-webkit-scrollbar-track { background: #eef2f6; border-radius: 10px; }
        ::-webkit-scrollbar-thumb { background: #b9c3d0; border-radius: 10px; }
        /* card stats */
        .stat-card { transition: transform 0.2s, box-shadow 0.2s; }
        .stat-card:hover { transform: translateY(-3px); box-shadow: 0 15px 30px -12px rgba(0,0,0,0.1); }
        @media (max-width: 768px) { .sidebar-fleet { width: 85%; max-width: 280px; } }
        
        /* User menu styles */
        .user-menu-dropdown {
            position: absolute;
            right: 0;
            top: 100%;
            margin-top: 8px;
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1);
            width: 220px;
            z-index: 50;
            overflow: hidden;
        }
        .user-menu-dropdown a, .user-menu-dropdown button {
            display: flex;
            align-items: center;
            gap: 10px;
            width: 100%;
            padding: 10px 16px;
            text-align: left;
            font-size: 14px;
            transition: all 0.2s;
            cursor: pointer;
        }
        .user-menu-dropdown a:hover, .user-menu-dropdown button:hover {
            background-color: #f8fafc;
        }
    </style>
</head>
<body class="text-gray-800 antialiased text-sm">

<!-- MOBILE OVERLAY -->
<div id="mobileOverlay" class="overlay-fleet"></div>

<!-- STICKY HEADER -->
<header class="sticky top-0 z-30 bg-white shadow-sm flex items-center justify-between lg:justify-end px-5 py-3 border-b border-gray-200">
    <button id="menuToggleBtn" aria-label="Open Sidebar" title="Open Sidebar" aria-expanded="false" aria-controls="fleetSidebar" class="lg:hidden text-gray-600 hover:text-blue-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 rounded p-1">
        <i class="fas fa-bars text-xl"></i>
    </button>
    <div class="relative">
        @php
            $userName = Auth::user()->name ?? 'Kwame Asare';
            $nameParts = preg_split('/\s+/', trim($userName));
            $initials = '';
            foreach (array_slice($nameParts, 0, 2) as $part) {
                $initials .= strtoupper(substr($part, 0, 1));
            }
            $initials = $initials ?: 'KA';
        @endphp
        <button id="userMenuToggle" type="button" aria-expanded="false" aria-controls="userMenuDropdown" class="flex items-center gap-2 bg-gray-50 rounded-full pl-2 pr-3 py-1 border border-gray-200 hover:bg-gray-100 transition focus-visible:ring-2 focus-visible:ring-blue-500 outline-none">
            <div class="w-7 h-7 bg-[#1a73e8] text-white rounded-full flex items-center justify-center text-xs font-bold">{{ $initials }}</div>
            <span class="text-sm font-medium text-gray-700 hidden sm:inline">{{ $userName }}</span>
            <span class="text-[11px] bg-gray-200 text-gray-700 px-2 py-0.5 rounded-full hidden sm:inline">Fleet Admin</span>
            <i class="fas fa-chevron-down text-[10px] text-gray-500"></i>
        </button>

        <div id="userMenuDropdown" class="hidden user-menu-dropdown">
            <button id="refreshDashboardBtn" type="button">
                <i class="fas fa-sync-alt text-[#1a73e8] w-5"></i>
                <span>Refresh Data</span>
            </button>
            <a id="logoutBtn" href="{{ route('logout') }}">
                <i class="fas fa-sign-out-alt text-red-600 w-5"></i>
                <span>Logout</span>
            </a>
        </div>
    </div>
</header>

<!-- SIDEBAR -->
<aside id="fleetSidebar" class="sidebar-fleet sidebar-closed lg:translate-x-0 sidebar-google flex flex-col">
    <!-- brand area -->
    <div class="p-5 border-b border-gray-200 flex items-center justify-between">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg flex items-center justify-center shadow-sm">
                <img src="/images/gwl-logo.png" alt="GWL Logo" class="object-cover w-full h-full">
            </div>
            <div>
                <h1 class="font-bold text-lg tracking-tight text-gray-900">Ghana<span class="text-[#1a73e8]">Water</span></h1>
                <p class="text-[10px] text-gray-500 uppercase tracking-wide">Fleet Intelligence</p>
            </div>
        </div>
        <button id="closeSidebarBtn" aria-label="Close Sidebar" title="Close Sidebar" class="lg:hidden text-gray-500 hover:text-[#1a73e8] focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 rounded p-1"><i class="fas fa-times text-lg"></i></button>
    </div>

    <!-- navigation menu -->
    <nav id="fleetNav" class="flex-1 py-5 space-y-1 overflow-y-auto">
        <!-- Dashboard (Fleet Overview) -->
        <a href="#" data-nav="dashboard" class="nav-item-fleet flex items-center gap-3 px-3 py-2.5 transition">
            <i class="fas fa-chart-line w-5 text-center text-gray-500"></i><span class="text-gray-700">Fleet Overview</span>
        </a>

        <!-- Live Command Center -->
        <a href="#" data-nav="live-tracking" class="nav-item-fleet flex items-center gap-3 px-3 py-2.5 transition">
            <i class="fas fa-satellite-dish w-5 text-center text-[#1a73e8]"></i><span class="font-bold text-gray-700">Live Tracking</span>
            <span class="ml-auto flex h-2 w-2 mr-2">
                <span class="animate-ping absolute inline-flex h-2 w-2 rounded-full bg-blue-400 opacity-75"></span>
                <span class="relative inline-flex rounded-full h-2 w-2 bg-[#1a73e8]"></span>
            </span>
        </a>

        <!-- Vehicle Registry -->
        <div class="space-y-1">
            <button onclick="toggleSubMenu('vehicles')" aria-expanded="false" aria-controls="vehicles-submenu" class="nav-item-fleet w-full flex items-center justify-between px-3 py-2.5 rounded-xl focus:outline-none">
                <div class="flex items-center gap-3"><i class="fas fa-truck-moving w-5 text-center text-gray-500"></i><span>Vehicle Registry</span></div>
                <i class="fas fa-chevron-down text-xs transition-transform" id="vehicles-chevron"></i>
            </button>
            <div id="vehicles-submenu" class="hidden pl-5 space-y-1">
                <a href="#" data-nav="all-vehicles" class="nav-item-fleet submenu-item flex items-center gap-3 px-3 py-2 rounded-lg"><i class="fas fa-list-ul text-sm"></i><span>All Fleet Units</span></a>
                <a href="#" data-nav="add-vehicle" class="nav-item-fleet submenu-item flex items-center gap-3 px-3 py-2 rounded-lg"><i class="fas fa-plus-circle text-sm"></i><span>Register New Vehicle</span></a>
                <a href="#" data-nav="vehicle-status" class="nav-item-fleet submenu-item flex items-center gap-3 px-3 py-2 rounded-lg"><i class="fas fa-chart-simple text-sm"></i><span>Status Overview</span></a>
            </div>
        </div>

        <!-- Location Management (NEW) -->
        <div class="space-y-1">
            <button onclick="toggleSubMenu('locations')" aria-expanded="false" aria-controls="locations-submenu" class="nav-item-fleet w-full flex items-center justify-between px-3 py-2.5 rounded-xl focus:outline-none">
                <div class="flex items-center gap-3"><i class="fas fa-map-marker-alt w-5 text-center text-gray-500"></i><span>Location Management</span></div>
                <i class="fas fa-chevron-down text-xs transition-transform" id="locations-chevron"></i>
            </button>
            <div id="locations-submenu" class="hidden pl-5 space-y-1">
                <a href="#" data-nav="locations" class="nav-item-fleet submenu-item flex items-center gap-3 px-3 py-2 rounded-lg"><i class="fas fa-globe text-sm"></i><span>Manage Locations</span></a>
            </div>
        </div>

        <!-- Fuel Management -->
        <div class="space-y-1">
            <button onclick="toggleSubMenu('fuel')" aria-expanded="false" aria-controls="fuel-submenu" class="nav-item-fleet w-full flex items-center justify-between px-3 py-2.5 rounded-xl focus:outline-none">
                <div class="flex items-center gap-3"><i class="fas fa-gas-pump w-5 text-center text-gray-500"></i><span>Fuel Management</span></div>
                <i class="fas fa-chevron-down text-xs transition-transform" id="fuel-chevron"></i>
            </button>
            <div id="fuel-submenu" class="hidden pl-5 space-y-1">
                <a href="#" data-nav="fuel-logs" class="nav-item-fleet submenu-item flex items-center gap-3 px-3 py-2 rounded-lg"><i class="fas fa-file-alt text-sm"></i><span>Fuel Logs</span></a>
                <a href="#" data-nav="fuel-analytics" class="nav-item-fleet submenu-item flex items-center gap-3 px-3 py-2 rounded-lg"><i class="fas fa-chart-line text-sm"></i><span>Consumption Analytics</span></a>
                <a href="#" data-nav="fuel-cost" class="nav-item-fleet submenu-item flex items-center gap-3 px-3 py-2 rounded-lg"><i class="fas fa-coins text-sm"></i><span>Cost Analysis</span></a>
            </div>
        </div>

        <!-- Mileage Management -->
        <div class="space-y-1">
            <button onclick="toggleSubMenu('mileage')" aria-expanded="false" aria-controls="mileage-submenu" class="nav-item-fleet w-full flex items-center justify-between px-3 py-2.5 rounded-xl focus:outline-none">
                <div class="flex items-center gap-3"><i class="fas fa-tachometer-alt w-5 text-center text-gray-500"></i><span>Mileage Management</span></div>
                <i class="fas fa-chevron-down text-xs transition-transform" id="mileage-chevron"></i>
            </button>
            <div id="mileage-submenu" class="hidden pl-5 space-y-1">
                <a href="#" data-nav="mileage-logs" class="nav-item-fleet submenu-item flex items-center gap-3 px-3 py-2 rounded-lg"><i class="fas fa-file-alt text-sm"></i><span>Mileage Logs</span></a>
                <a href="#" data-nav="mileage-analytics" class="nav-item-fleet submenu-item flex items-center gap-3 px-3 py-2 rounded-lg"><i class="fas fa-chart-line text-sm"></i><span>Mileage Analytics</span></a>
            </div>
        </div>

        <!-- Maintenance & Service -->
        <div class="space-y-1">
            <button onclick="toggleSubMenu('maint')" aria-expanded="false" aria-controls="maint-submenu" class="nav-item-fleet w-full flex items-center justify-between px-3 py-2.5 rounded-xl focus:outline-none">
                <div class="flex items-center gap-3"><i class="fas fa-tools w-5 text-center text-gray-500"></i><span>Maintenance</span></div>
                <i class="fas fa-chevron-down text-xs transition-transform" id="maint-chevron"></i>
            </button>
            <div id="maint-submenu" class="hidden pl-5 space-y-1">
                <a href="#" data-nav="service-schedule" class="nav-item-fleet submenu-item flex items-center gap-3 px-3 py-2 rounded-lg"><i class="fas fa-calendar-alt text-sm"></i><span>Service Schedule</span></a>
                <a href="#" data-nav="maintenance-history" class="nav-item-fleet submenu-item flex items-center gap-3 px-3 py-2 rounded-lg"><i class="fas fa-history text-sm"></i><span>History Log</span></a>
                <a href="#" data-nav="upcoming-reminders" class="nav-item-fleet submenu-item flex items-center gap-3 px-3 py-2 rounded-lg"><i class="fas fa-bell text-sm"></i><span>Upcoming Reminders</span></a>
            </div>
        </div>

        <!-- Driver Management - Make sure data-nav="drivers" is present -->
        <a href="#" data-nav="drivers" class="nav-item-fleet flex items-center gap-3 px-3 py-2.5">
        <i class="fas fa-id-card w-5 text-center text-gray-500"></i><span class="text-gray-700">Driver Hub</span>
        </a>

        <!-- Insurance & Documents -->
        <a href="#" data-nav="documents" class="nav-item-fleet flex items-center gap-3 px-3 py-2.5">
            <i class="fas fa-file-invoice w-5 text-center text-gray-500"></i><span class="text-gray-700">Insurance & Docs</span>
        </a>

        <!-- Fleet Reports -->
        <div class="space-y-1">
            <button onclick="toggleSubMenu('reports')" aria-expanded="false" aria-controls="reports-submenu" class="nav-item-fleet w-full flex items-center justify-between px-3 py-2.5 rounded-xl focus:outline-none">
                <div class="flex items-center gap-3"><i class="fas fa-chart-pie w-5 text-center text-gray-500"></i><span>Fleet Reports</span></div>
                <i class="fas fa-chevron-down text-xs transition-transform" id="reports-chevron"></i>
            </button>
            <div id="reports-submenu" class="hidden pl-5 space-y-1">
                <a href="#" data-nav="vehicle-utilization" class="nav-item-fleet submenu-item flex items-center gap-3 px-3 py-2 rounded-lg"><i class="fas fa-chart-bar text-sm"></i><span>Utilization</span></a>
                <a href="#" data-nav="cost-analysis-report" class="nav-item-fleet submenu-item flex items-center gap-3 px-3 py-2 rounded-lg"><i class="fas fa-dollar-sign text-sm"></i><span>Cost Analysis</span></a>
                <a href="#" data-nav="fuel-efficiency" class="nav-item-fleet submenu-item flex items-center gap-3 px-3 py-2 rounded-lg"><i class="fas fa-tachometer-alt text-sm"></i><span>Fuel Efficiency</span></a>
            </div>
        </div>

        <!-- Fleet Settings -->
        <a href="#" data-nav="settings" class="nav-item-fleet flex items-center gap-3 px-3 py-2.5">
            <i class="fas fa-sliders-h w-5 text-center text-gray-500"></i><span class="text-gray-700">Fleet Settings</span>
        </a>
    </nav>

    <div class="p-4 border-t border-gray-200/50 text-[11px] text-gray-400 text-center">
        <i class="fas fa-road mr-1"></i> FleetPilot v2.4 · © 2025
    </div>
</aside>

<!-- MAIN CONTENT DYNAMIC AREA -->
<main id="mainContentArea" class="min-h-screen lg:ml-[280px] p-5 md:p-7 transition-all duration-300">
    <div id="pageContent">
        @yield('content')
    </div>
</main>

<!-- AI SUPPORT CHAT BOT -->
@include('components.ai-chat-bot')

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    // Keep sidebar controls working even when a page doesn't define its own handlers.
    function toggleSubMenu(menuId) {
        const subMenu = document.getElementById(`${menuId}-submenu`);
        const chevron = document.getElementById(`${menuId}-chevron`);
        const trigger = document.querySelector(`[aria-controls="${menuId}-submenu"]`);

        if (!subMenu) return;
        const isHidden = subMenu.classList.toggle('hidden');
        if (chevron) chevron.classList.toggle('rotate-180');
        if (trigger) trigger.setAttribute('aria-expanded', !isHidden);
    }
    window.toggleSubMenu = window.toggleSubMenu || toggleSubMenu;

    document.addEventListener('DOMContentLoaded', () => {
        const sidebar = document.getElementById('fleetSidebar');
        const overlay = document.getElementById('mobileOverlay');
        const openBtn = document.getElementById('menuToggleBtn');
        const closeBtn = document.getElementById('closeSidebarBtn');
        const userMenuToggle = document.getElementById('userMenuToggle');
        const userMenuDropdown = document.getElementById('userMenuDropdown');

        const openSidebar = () => {
            sidebar?.classList.remove('sidebar-closed');
            overlay?.classList.add('overlay-open');
            document.body.style.overflow = 'hidden';
            openBtn?.setAttribute('aria-expanded', 'true');
            setTimeout(() => closeBtn?.focus(), 100);
        };

        const closeSidebar = () => {
            sidebar?.classList.add('sidebar-closed');
            overlay?.classList.remove('overlay-open');
            document.body.style.overflow = '';
            openBtn?.setAttribute('aria-expanded', 'false');
            openBtn?.focus();
        };

        openBtn?.addEventListener('click', openSidebar);
        closeBtn?.addEventListener('click', closeSidebar);
        overlay?.addEventListener('click', closeSidebar);

        const closeUserMenu = () => {
            if (userMenuDropdown) {
                userMenuDropdown.classList.add('hidden');
                userMenuToggle?.setAttribute('aria-expanded', 'false');
            }
        };

        userMenuToggle?.addEventListener('click', (event) => {
            event.stopPropagation();
            const isHidden = userMenuDropdown?.classList.toggle('hidden');
            userMenuToggle?.setAttribute('aria-expanded', !isHidden);
        });

        document.addEventListener('click', (event) => {
            if (!userMenuDropdown || !userMenuToggle) return;
            if (userMenuDropdown.contains(event.target) || userMenuToggle.contains(event.target)) return;
            closeUserMenu();
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') closeUserMenu();
        });

        // Refresh dashboard data
        const refreshBtn = document.getElementById('refreshDashboardBtn');
        if (refreshBtn) {
            refreshBtn.addEventListener('click', () => {
                window.location.reload();
            });
        }

        // Navigation route mapping - CORRECTED with all routes including drivers
        const navRouteMap = {
            'dashboard': '/dashboard',
            'all-vehicles': '/vehicles?tab=all-vehicles',
            'live-tracking': '/vehicles/tracking',
            'add-vehicle': '/vehicles?tab=add-vehicle',
            'vehicle-status': '/vehicles?tab=status-overview',
            'locations': '/locations',
            'fuel-logs': '/fuel-management?tab=logs',
            'fuel-analytics': '/fuel-management?tab=analytics',
            'fuel-cost': '/fuel-management?tab=cost-analysis',
            'mileage-logs': '/mileage-logs',
            'mileage-analytics': '/mileage-analytics',
            'service-schedule': '/maintenance',
            'maintenance-history': '/maintenance/history',
            'upcoming-reminders': '/maintenance/reminders',
            'drivers': '/drivers',
            'documents': '/documents',
            'vehicle-utilization': '/reports/utilization',
            'cost-analysis-report': '/reports/cost',
            'fuel-efficiency': '/reports/fuel-efficiency',
            'settings': '/settings'
        };

        document.getElementById('fleetNav')?.addEventListener('click', (e) => {
            const link = e.target.closest('[data-nav]');
            if (!link) return;

            const key = link.dataset.nav;
            const target = navRouteMap[key];

            if (target) {
                e.preventDefault();
                window.location.href = target;
            }
        });
    });
</script>
</body>
</html>