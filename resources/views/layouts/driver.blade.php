<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>Driver Portal | Ghana Water Limited</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <!-- Tailwind + Fonts + Icons -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    @vite('resources/css/app.css')
  
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,500;14..32,600;14..32,700&display=swap" rel="stylesheet">
    
    <style>
        * { font-family: 'Inter', system-ui, -apple-system, sans-serif; }
        body { background: #f4f7fc; overflow-x: hidden; }
        
        /* Glassmorphism refined */
        .glass-card { background: rgba(255,255,255,0.85); backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px); border: 1px solid rgba(255,255,255,0.5); }
        .sidebar-glass { background: rgba(255,255,255,0.94); backdrop-filter: blur(16px); border-right: 1px solid rgba(0,0,0,0.05); }
        
        /* Sidebar transitions */
        .sidebar-fleet { position: fixed; top: 0; left: 0; height: 100vh; width: 280px; z-index: 40; transition: transform 0.25s cubic-bezier(0.2, 0.9, 0.4, 1.1); }
        .sidebar-closed { transform: translateX(-100%); }
        .overlay-fleet { position: fixed; inset: 0; background: rgba(0,0,0,0.3); backdrop-filter: blur(2px); z-index: 35; display: none; }
        .overlay-open { display: block; }
        
        /* Navigation items */
        .nav-item-fleet { transition: all 0.2s ease; border-radius: 12px; margin-bottom: 2px; }
        .nav-item-fleet:hover { background: rgba(59,130,246,0.12); color: #1e40af; }
        .nav-active-fleet { background: #eef2ff; color: #2563eb; font-weight: 500; border-left: 3px solid #3b82f6; }
        .submenu-item { padding-left: 2.5rem; transition: all 0.2s; }
        .rotate-180 { transform: rotate(180deg); }
        
        /* custom scroll */
        ::-webkit-scrollbar { width: 5px; }
        ::-webkit-scrollbar-track { background: #eef2f6; border-radius: 10px; }
        ::-webkit-scrollbar-thumb { background: #b9c3d0; border-radius: 10px; }
        
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
        
        /* Notification Bell */
        .notification-bell {
            position: relative;
            cursor: pointer;
        }
        .notification-badge {
            position: absolute;
            top: -8px;
            right: -8px;
            background: #ef4444;
            color: white;
            font-size: 10px;
            font-weight: 600;
            padding: 2px 6px;
            border-radius: 20px;
            min-width: 18px;
            text-align: center;
        }
        .notification-dropdown {
            position: absolute;
            top: 100%;
            right: 0;
            width: 380px;
            max-height: 500px;
            overflow-y: auto;
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1);
            z-index: 50;
            margin-top: 10px;
            display: none;
        }
        .notification-dropdown.show {
            display: block;
            animation: slideDown 0.2s ease;
        }
        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .notification-item {
            padding: 12px 16px;
            border-bottom: 1px solid #f1f5f9;
            transition: all 0.2s;
            cursor: pointer;
        }
        .notification-item:hover {
            background: #f8fafc;
        }
        .notification-item.unread {
            background: #eff6ff;
        }
        .notification-item.unread:hover {
            background: #dbeafe;
        }
    </style>
</head>
<body class="text-gray-800 antialiased">

<!-- MOBILE OVERLAY -->
<div id="mobileOverlay" class="overlay-fleet"></div>

<!-- STICKY HEADER -->
<header class="sticky top-0 z-30 glass-card shadow-sm flex items-center justify-end px-5 py-3 border-b border-white/60">
    <div class="flex items-center gap-4">
        <!-- Notification Bell -->
        <div class="notification-bell" id="notificationBell">
            <i class="fas fa-bell text-gray-500 text-xl hover:text-gray-700 transition"></i>
            <span class="notification-badge" id="notificationCount">0</span>
            <div class="notification-dropdown" id="notificationDropdown">
                <div class="p-3 border-b border-gray-200 bg-gray-50 rounded-t-2xl">
                    <div class="flex justify-between items-center">
                        <h4 class="font-semibold text-gray-800">Notifications</h4>
                        <button id="markAllRead" class="text-xs text-blue-600 hover:text-blue-800">Mark all as read</button>
                    </div>
                </div>
                <div id="notificationList">
                    <div class="text-center py-8 text-gray-500">Loading notifications...</div>
                </div>
                <div class="p-3 border-t border-gray-200 text-center">
                    <a href="#" class="text-xs text-blue-600 hover:text-blue-800">View all notifications</a>
                </div>
            </div>
        </div>
        
        <!-- User Menu -->
        <div class="relative">
            @php
                $userName = Auth::user()->name ?? 'Kwame Asare';
                $nameParts = preg_split('/\s+/', trim($userName));
                $initials = '';
                foreach (array_slice($nameParts, 0, 2) as $part) {
                    $initials .= strtoupper(substr($part, 0, 1));
                }
                $initials = $initials ?: 'KD';
            @endphp
            <button id="userMenuToggle" type="button" class="flex items-center gap-2 bg-slate-50 rounded-full pl-2 pr-3 py-1 border border-slate-200 hover:bg-slate-100 transition">
                <div class="w-7 h-7 bg-green-700 text-white rounded-full flex items-center justify-center text-xs font-bold">{{ $initials }}</div>
                <span class="text-sm font-medium text-gray-700 hidden sm:inline">{{ $userName }}</span>
                <span class="text-[11px] bg-green-100 text-green-700 px-2 py-0.5 rounded-full hidden sm:inline">Driver</span>
                <i class="fas fa-chevron-down text-[10px] text-gray-500"></i>
            </button>

            <div id="userMenuDropdown" class="hidden user-menu-dropdown">
                <a href="{{ route('driver.fuel-mileage.dashboard') }}">
                    <i class="fas fa-tachometer-alt text-blue-600 w-5"></i>
                    <span>Dashboard</span>
                </a>
                <a href="{{ route('driver.profile') }}">
                    <i class="fas fa-user-circle text-purple-600 w-5"></i>
                    <span>My Profile</span>
                </a>
                <button id="refreshDashboardBtn" type="button">
                    <i class="fas fa-sync-alt text-blue-600 w-5"></i>
                    <span>Refresh Data</span>
                </button>
                <a id="logoutBtn" href="{{ route('logout') }}">
                    <i class="fas fa-sign-out-alt text-red-600 w-5"></i>
                    <span>Logout</span>
                </a>
            </div>
        </div>
    </div>
</header>

<!-- SIDEBAR (Driver Only) -->
<aside id="fleetSidebar" class="sidebar-fleet sidebar-closed lg:translate-x-0 sidebar-glass shadow-2xl flex flex-col">
    <!-- brand area -->
    <div class="p-5 border-b border-gray-200/70 flex items-center justify-between">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl flex items-center justify-center shadow-md">
                <img src="/images/gwl-logo.png" alt="GWL Logo" class="object-cover w-full h-full">
            </div>
            <div>
                <h1 class="font-bold text-lg tracking-tight text-gray-800">Driver<span class="text-green-600">Portal</span></h1>
                <p class="text-[10px] text-gray-500 uppercase tracking-wide">Ghana Water Limited</p>
            </div>
        </div>
        <button id="closeSidebarBtn" class="lg:hidden text-gray-500 hover:text-green-600"><i class="fas fa-times text-lg"></i></button>
    </div>

    <!-- Driver Navigation Menu -->
    <nav class="flex-1 px-3 py-5 space-y-1 overflow-y-auto">
        <!-- Dashboard -->
        <a href="{{ route('driver.fuel-mileage.dashboard') }}" data-nav="dashboard" class="nav-item-fleet flex items-center gap-3 px-3 py-2.5 rounded-xl transition">
            <i class="fas fa-chart-line w-5 text-center text-gray-500"></i><span>Dashboard</span>
        </a>

        <!-- Maintenance Requests -->
        <a href="{{ route('driver.fuel-mileage.maintenance.index') }}" data-nav="maintenance" class="nav-item-fleet flex items-center gap-3 px-3 py-2.5 rounded-xl transition">
            <i class="fas fa-tools w-5 text-center text-gray-500"></i><span>Maintenance Requests</span>
        </a>

        <!-- Mileage Logs -->
        <a href="{{ route('driver.fuel-mileage.mileage-logs.index') }}" data-nav="mileage-logs" class="nav-item-fleet flex items-center gap-3 px-3 py-2.5 rounded-xl transition">
            <i class="fas fa-tachometer-alt w-5 text-center text-gray-500"></i><span>Mileage Logs</span>
        </a>

        <!-- Quick Log -->
        <a href="{{ route('driver.fuel-mileage.quick-log') }}" data-nav="quick-log" class="nav-item-fleet flex items-center gap-3 px-3 py-2.5 rounded-xl transition">
            <i class="fas fa-bolt w-5 text-center text-yellow-500"></i><span>Quick Log</span>
        </a>

        <!-- Reports -->
        <a href="{{ route('driver.fuel-mileage.reports') }}" data-nav="reports" class="nav-item-fleet flex items-center gap-3 px-3 py-2.5 rounded-xl transition">
            <i class="fas fa-chart-pie w-5 text-center text-gray-500"></i><span>Reports</span>
        </a>

        <!-- My Vehicle -->
        <a href="#" data-nav="my-vehicle" class="nav-item-fleet flex items-center gap-3 px-3 py-2.5 rounded-xl transition">
            <i class="fas fa-truck w-5 text-center text-gray-500"></i><span>My Vehicle</span>
        </a>
    </nav>

    <div class="p-4 border-t border-gray-200/50 text-[11px] text-gray-400 text-center">
        <i class="fas fa-road mr-1"></i> Driver Portal v1.0 · © 2025
    </div>
</aside>

<!-- MAIN CONTENT DYNAMIC AREA -->
<main id="mainContentArea" class="min-h-screen lg:ml-[280px] p-5 md:p-7 transition-all duration-300">
    <div id="pageContent">
        @yield('content')
    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    // Keep sidebar controls working even when a page doesn't define its own handlers.
    function toggleSubMenu(menuId) {
        const subMenu = document.getElementById(`${menuId}-submenu`);
        const chevron = document.getElementById(`${menuId}-chevron`);

        if (!subMenu) return;
        subMenu.classList.toggle('hidden');
        if (chevron) chevron.classList.toggle('rotate-180');
    }
    window.toggleSubMenu = window.toggleSubMenu || toggleSubMenu;

    document.addEventListener('DOMContentLoaded', () => {
        const sidebar = document.getElementById('fleetSidebar');
        const overlay = document.getElementById('mobileOverlay');
        const openBtn = document.getElementById('menuToggleBtn');
        const closeBtn = document.getElementById('closeSidebarBtn');
        const userMenuToggle = document.getElementById('userMenuToggle');
        const userMenuDropdown = document.getElementById('userMenuDropdown');
        const notificationBell = document.getElementById('notificationBell');
        const notificationDropdown = document.getElementById('notificationDropdown');

        // Sidebar functions
        const openSidebar = () => {
            sidebar?.classList.remove('sidebar-closed');
            overlay?.classList.add('overlay-open');
            document.body.style.overflow = 'hidden';
        };

        const closeSidebar = () => {
            sidebar?.classList.add('sidebar-closed');
            overlay?.classList.remove('overlay-open');
            document.body.style.overflow = '';
        };

        if (openBtn) openBtn.addEventListener('click', openSidebar);
        if (closeBtn) closeBtn.addEventListener('click', closeSidebar);
        if (overlay) overlay.addEventListener('click', closeSidebar);

        // User menu
        const closeUserMenu = () => {
            if (userMenuDropdown) userMenuDropdown.classList.add('hidden');
        };

        if (userMenuToggle) {
            userMenuToggle.addEventListener('click', (event) => {
                event.stopPropagation();
                userMenuDropdown?.classList.toggle('hidden');
            });
        }

        document.addEventListener('click', (event) => {
            if (!userMenuDropdown || !userMenuToggle) return;
            if (userMenuDropdown.contains(event.target) || userMenuToggle.contains(event.target)) return;
            closeUserMenu();
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') closeUserMenu();
        });

        // Notification dropdown
        if (notificationBell) {
            notificationBell.addEventListener('click', (e) => {
                e.stopPropagation();
                notificationDropdown?.classList.toggle('show');
            });
        }

        document.addEventListener('click', (e) => {
            if (notificationDropdown && !notificationBell?.contains(e.target)) {
                notificationDropdown.classList.remove('show');
            }
        });

        // Refresh dashboard data
        const refreshBtn = document.getElementById('refreshDashboardBtn');
        if (refreshBtn) {
            refreshBtn.addEventListener('click', () => {
                window.location.reload();
            });
        }

        // Sample notification data - replace with actual API call
        function loadNotifications() {
            // This should be replaced with your actual API endpoint
            $.ajax({
                url: '/driver/notifications',
                method: 'GET',
                success: function(response) {
                    if (response.success) {
                        updateNotificationUI(response.notifications);
                    }
                },
                error: function() {
                    // Fallback sample data
                    const sampleNotifications = [
                        { id: 1, title: 'Maintenance Reminder', message: 'Your vehicle is due for maintenance in 500 km', type: 'warning', is_read: false, time: '2 hours ago' },
                        { id: 2, title: 'Service Request Approved', message: 'Your maintenance request has been approved', type: 'success', is_read: false, time: '1 day ago' }
                    ];
                    updateNotificationUI(sampleNotifications);
                }
            });
        }

        function updateNotificationUI(notifications) {
            const unreadCount = notifications.filter(n => !n.is_read).length;
            document.getElementById('notificationCount').textContent = unreadCount;
            
            let html = '';
            notifications.forEach(notification => {
                let iconClass = notification.type === 'warning' ? 'fa-exclamation-triangle text-yellow-500' : 
                               (notification.type === 'success' ? 'fa-check-circle text-green-500' : 'fa-info-circle text-blue-500');
                
                html += `
                    <div class="notification-item ${!notification.is_read ? 'unread' : ''}" onclick="markNotificationRead(${notification.id})">
                        <div class="flex gap-3">
                            <i class="fas ${iconClass} mt-0.5"></i>
                            <div class="flex-1">
                                <p class="text-sm font-medium text-gray-800">${notification.title}</p>
                                <p class="text-xs text-gray-500 mt-1">${notification.message}</p>
                                <p class="text-xs text-gray-400 mt-1">${notification.time}</p>
                            </div>
                            ${!notification.is_read ? '<span class="w-2 h-2 bg-blue-500 rounded-full mt-2"></span>' : ''}
                        </div>
                    </div>
                `;
            });
            
            if (notifications.length === 0) {
                html = '<div class="text-center py-8 text-gray-500">No notifications</div>';
            }
            
            document.getElementById('notificationList').innerHTML = html;
        }

        function markNotificationRead(id) {
            $.ajax({
                url: `/driver/notifications/${id}/read`,
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                success: function() {
                    loadNotifications();
                }
            });
        }

        window.markNotificationRead = markNotificationRead;

        document.getElementById('markAllRead')?.addEventListener('click', function() {
            $.ajax({
                url: '/driver/notifications/mark-all-read',
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                success: function() {
                    loadNotifications();
                }
            });
        });

        // Initialize notifications
        loadNotifications();

        // Navigation route mapping for driver
        const navRouteMap = {
            'dashboard': '{{ route("driver.fuel-mileage.dashboard") }}',
            'maintenance': '{{ route("driver.fuel-mileage.maintenance.index") }}',
            'mileage-logs': '{{ route("driver.fuel-mileage.mileage-logs.index") }}',
            'quick-log': '{{ route("driver.fuel-mileage.quick-log") }}',
            'reports': '{{ route("driver.fuel-mileage.reports") }}',
            'my-vehicle': '/driver/vehicle'
        };

        document.querySelectorAll('[data-nav]').forEach((link) => {
            link.addEventListener('click', (event) => {
                const key = link.dataset.nav;
                const target = navRouteMap[key];
                if (!target) return;

                event.preventDefault();
                window.location.href = target;
            });
        });
    });
</script>
</body>
</html>