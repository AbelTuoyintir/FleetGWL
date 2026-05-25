{{-- resources/views/vehicles/partials/vehicle-svg.blade.php --}}
@php
    $colors = [
        'pickup' => ['primary' => '#3B82F6', 'secondary' => '#60A5FA', 'dark' => '#2563EB'],
        'suv' => ['primary' => '#10B981', 'secondary' => '#34D399', 'dark' => '#059669'],
        'truck' => ['primary' => '#F59E0B', 'secondary' => '#FBBF24', 'dark' => '#D97706'],
        'tanker' => ['primary' => '#EF4444', 'secondary' => '#FCA5A5', 'dark' => '#DC2626'],
        'bus' => ['primary' => '#8B5CF6', 'secondary' => '#A78BFA', 'dark' => '#6D28D9'],
        'van' => ['primary' => '#06B6D4', 'secondary' => '#22D3EE', 'dark' => '#0891B2'],
        'sedan' => ['primary' => '#6366F1', 'secondary' => '#818CF8', 'dark' => '#4F46E5'],
        'motorcycle' => ['primary' => '#EF4444', 'secondary' => '#FCA5A5', 'dark' => '#DC2626'],
        'default' => ['primary' => '#6B7280', 'secondary' => '#9CA3AF', 'dark' => '#4B5563'],
    ];
    
    $color = $colors[$type] ?? $colors['default'];
@endphp

@switch($type)
    @case('pickup')
    <svg width="200" height="150" viewBox="0 0 200 150" fill="none" xmlns="http://www.w3.org/2000/svg">
        <defs>
            <linearGradient id="pickupGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                <stop offset="0%" style="stop-color:{{ $color['secondary'] }};stop-opacity:1" />
                <stop offset="100%" style="stop-color:{{ $color['primary'] }};stop-opacity:1" />
            </linearGradient>
        </defs>
        <!-- Main Body -->
        <rect x="10" y="70" width="180" height="50" rx="8" fill="url(#pickupGrad)" stroke="{{ $color['dark'] }}" stroke-width="2"/>
        <!-- Cabin -->
        <rect x="30" y="40" width="100" height="35" rx="4" fill="{{ $color['secondary'] }}" stroke="{{ $color['dark'] }}" stroke-width="2"/>
        <rect x="35" y="45" width="90" height="25" rx="2" fill="#EFF6FF"/>
        <!-- Bed/Cargo Area -->
        <rect x="120" y="60" width="60" height="40" rx="4" fill="#DBEAFE" stroke="{{ $color['dark'] }}" stroke-width="1.5"/>
        <!-- Wheels -->
        <circle cx="50" cy="120" r="20" fill="#374151" stroke="#1F2937" stroke-width="2"/>
        <circle cx="50" cy="120" r="8" fill="#9CA3AF"/>
        <circle cx="150" cy="120" r="20" fill="#374151" stroke="#1F2937" stroke-width="2"/>
        <circle cx="150" cy="120" r="8" fill="#9CA3AF"/>
        <!-- Windows -->
        <rect x="15" y="68" width="25" height="15" rx="3" fill="{{ $color['dark'] }}"/>
        <rect x="45" y="68" width="25" height="15" rx="3" fill="{{ $color['dark'] }}"/>
        <!-- Headlight -->
        <path d="M130 75 L145 75 L145 95 L130 95 Z" fill="#FCD34D" stroke="#F59E0B" stroke-width="1"/>
        <!-- Registration Number Display -->
        <text x="65" y="85" font-size="8" fill="{{ $color['dark'] }}" text-anchor="middle" font-weight="bold">{{ substr($vehicle->registration_number ?? 'PICKUP', 0, 8) }}</text>
    </svg>
    @break
    
    @case('tanker')
    <svg width="200" height="150" viewBox="0 0 200 150" fill="none" xmlns="http://www.w3.org/2000/svg">
        <defs>
            <linearGradient id="tankerGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                <stop offset="0%" style="stop-color:{{ $color['secondary'] }};stop-opacity:1" />
                <stop offset="100%" style="stop-color:{{ $color['primary'] }};stop-opacity:1" />
            </linearGradient>
        </defs>
        <!-- Main Tank Body -->
        <ellipse cx="95" cy="85" rx="70" ry="30" fill="url(#tankerGrad)" stroke="{{ $color['dark'] }}" stroke-width="2"/>
        <!-- Cabin -->
        <rect x="20" y="55" width="70" height="35" rx="6" fill="{{ $color['secondary'] }}" stroke="{{ $color['dark'] }}" stroke-width="2"/>
        <rect x="25" y="60" width="60" height="25" rx="3" fill="#FEE2E2"/>
        <!-- Support Structure -->
        <rect x="5" y="105" width="185" height="8" rx="2" fill="#4B5563"/>
        <!-- Wheels -->
        <circle cx="45" cy="125" r="20" fill="#374151" stroke="#1F2937" stroke-width="2"/>
        <circle cx="45" cy="125" r="8" fill="#9CA3AF"/>
        <circle cx="155" cy="125" r="20" fill="#374151" stroke="#1F2937" stroke-width="2"/>
        <circle cx="155" cy="125" r="8" fill="#9CA3AF"/>
        <!-- Tank Details -->
        <rect x="95" y="72" width="50" height="15" rx="3" fill="#FEE2E2" stroke="{{ $color['dark'] }}" stroke-width="1"/>
        <text x="120" y="84" font-size="10" fill="{{ $color['dark'] }}" text-anchor="middle" font-weight="bold">WATER</text>
        <!-- Text Label -->
        <rect x="15" y="100" width="25" height="15" rx="3" fill="#7F1D1D"/>
        <text x="27" y="111" font-size="7" fill="white" text-anchor="middle">GWCL</text>
    </svg>
    @break
    
    @case('suv')
    <svg width="200" height="150" viewBox="0 0 200 150" fill="none" xmlns="http://www.w3.org/2000/svg">
        <defs>
            <linearGradient id="suvGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                <stop offset="0%" style="stop-color:{{ $color['secondary'] }};stop-opacity:1" />
                <stop offset="100%" style="stop-color:{{ $color['primary'] }};stop-opacity:1" />
            </linearGradient>
        </defs>
        <!-- Main Body -->
        <rect x="15" y="65" width="170" height="50" rx="10" fill="url(#suvGrad)" stroke="{{ $color['dark'] }}" stroke-width="2"/>
        <!-- Cabin -->
        <path d="M25 65 L35 40 L65 40 L75 65 Z" fill="{{ $color['secondary'] }}" stroke="{{ $color['dark'] }}" stroke-width="1.5"/>
        <path d="M135 65 L145 40 L175 40 L185 65 Z" fill="{{ $color['secondary'] }}" stroke="{{ $color['dark'] }}" stroke-width="1.5"/>
        <!-- Roof -->
        <rect x="40" y="35" width="110" height="30" rx="6" fill="{{ $color['secondary'] }}" stroke="{{ $color['dark'] }}" stroke-width="2"/>
        <rect x="45" y="40" width="100" height="20" rx="3" fill="#EEF2FF"/>
        <!-- Wheels -->
        <circle cx="45" cy="115" r="22" fill="#374151" stroke="#1F2937" stroke-width="2"/>
        <circle cx="45" cy="115" r="9" fill="#9CA3AF"/>
        <circle cx="155" cy="115" r="22" fill="#374151" stroke="#1F2937" stroke-width="2"/>
        <circle cx="155" cy="115" r="9" fill="#9CA3AF"/>
        <!-- Details -->
        <rect x="20" y="63" width="30" height="18" rx="4" fill="{{ $color['dark'] }}"/>
        <rect x="55" y="63" width="30" height="18" rx="4" fill="{{ $color['dark'] }}"/>
        <!-- Registration -->
        <text x="100" y="95" font-size="8" fill="{{ $color['dark'] }}" text-anchor="middle" font-weight="bold">{{ substr($vehicle->registration_number ?? 'SUV', 0, 8) }}</text>
    </svg>
    @break
    
    @case('truck')
    <svg width="200" height="150" viewBox="0 0 200 150" fill="none" xmlns="http://www.w3.org/2000/svg">
        <defs>
            <linearGradient id="truckGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                <stop offset="0%" style="stop-color:{{ $color['secondary'] }};stop-opacity:1" />
                <stop offset="100%" style="stop-color:{{ $color['primary'] }};stop-opacity:1" />
            </linearGradient>
        </defs>
        <!-- Cargo Area -->
        <rect x="5" y="65" width="150" height="55" rx="6" fill="url(#truckGrad)" stroke="{{ $color['dark'] }}" stroke-width="2"/>
        <!-- Cabin -->
        <rect x="20" y="40" width="100" height="30" rx="4" fill="{{ $color['secondary'] }}" stroke="{{ $color['dark'] }}" stroke-width="2"/>
        <rect x="25" y="45" width="90" height="20" rx="2" fill="#FEF3C7"/>
        <!-- Container Box -->
        <rect x="150" y="55" width="45" height="45" rx="4" fill="{{ $color['secondary'] }}" stroke="{{ $color['dark'] }}" stroke-width="1.5"/>
        <!-- Wheels -->
        <circle cx="40" cy="120" r="22" fill="#374151" stroke="#1F2937" stroke-width="2"/>
        <circle cx="40" cy="120" r="9" fill="#9CA3AF"/>
        <circle cx="140" cy="120" r="22" fill="#374151" stroke="#1F2937" stroke-width="2"/>
        <circle cx="140" cy="120" r="9" fill="#9CA3AF"/>
        <!-- Details -->
        <rect x="10" y="63" width="50" height="20" rx="3" fill="{{ $color['dark'] }}"/>
        <rect x="155" y="68" width="35" height="20" rx="3" fill="#FCD34D"/>
        <text x="172" y="82" font-size="7" fill="{{ $color['dark'] }}" text-anchor="middle">CARGO</text>
    </svg>
    @break
    
    @case('bus')
    <svg width="200" height="150" viewBox="0 0 200 150" fill="none" xmlns="http://www.w3.org/2000/svg">
        <defs>
            <linearGradient id="busGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                <stop offset="0%" style="stop-color:{{ $color['secondary'] }};stop-opacity:1" />
                <stop offset="100%" style="stop-color:{{ $color['primary'] }};stop-opacity:1" />
            </linearGradient>
        </defs>
        <!-- Main Body -->
        <rect x="10" y="55" width="180" height="65" rx="8" fill="url(#busGrad)" stroke="{{ $color['dark'] }}" stroke-width="2"/>
        <!-- Windows Row -->
        <rect x="20" y="60" width="30" height="30" rx="4" fill="#DDD6FE" stroke="{{ $color['dark'] }}" stroke-width="1"/>
        <rect x="55" y="60" width="30" height="30" rx="4" fill="#DDD6FE" stroke="{{ $color['dark'] }}" stroke-width="1"/>
        <rect x="90" y="60" width="30" height="30" rx="4" fill="#DDD6FE" stroke="{{ $color['dark'] }}" stroke-width="1"/>
        <rect x="125" y="60" width="30" height="30" rx="4" fill="#DDD6FE" stroke="{{ $color['dark'] }}" stroke-width="1"/>
        <rect x="160" y="60" width="25" height="30" rx="4" fill="#DDD6FE" stroke="{{ $color['dark'] }}" stroke-width="1"/>
        <!-- Wheels -->
        <circle cx="40" cy="120" r="18" fill="#374151" stroke="#1F2937" stroke-width="2"/>
        <circle cx="40" cy="120" r="7" fill="#9CA3AF"/>
        <circle cx="160" cy="120" r="18" fill="#374151" stroke="#1F2937" stroke-width="2"/>
        <circle cx="160" cy="120" r="7" fill="#9CA3AF"/>
        <!-- Destination Sign -->
        <rect x="65" y="35" width="70" height="15" rx="3" fill="#FCD34D" stroke="#F59E0B" stroke-width="1"/>
        <text x="100" y="46" font-size="7" fill="#78350F" text-anchor="middle" font-weight="bold">FLEET PILOT</text>
    </svg>
    @break
    
    @default
    <svg width="200" height="150" viewBox="0 0 200 150" fill="none" xmlns="http://www.w3.org/2000/svg">
        <defs>
            <linearGradient id="defaultGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                <stop offset="0%" style="stop-color:{{ $color['secondary'] }};stop-opacity:1" />
                <stop offset="100%" style="stop-color:{{ $color['primary'] }};stop-opacity:1" />
            </linearGradient>
        </defs>
        <!-- Standard Vehicle Body -->
        <rect x="15" y="65" width="170" height="50" rx="8" fill="url(#defaultGrad)" stroke="{{ $color['dark'] }}" stroke-width="2"/>
        <!-- Cabin -->
        <rect x="30" y="40" width="90" height="30" rx="5" fill="{{ $color['secondary'] }}" stroke="{{ $color['dark'] }}" stroke-width="2"/>
        <rect x="35" y="45" width="80" height="20" rx="3" fill="#F3F4F6"/>
        <!-- Wheels -->
        <circle cx="45" cy="115" r="20" fill="#374151" stroke="#1F2937" stroke-width="2"/>
        <circle cx="45" cy="115" r="8" fill="#9CA3AF"/>
        <circle cx="155" cy="115" r="20" fill="#374151" stroke="#1F2937" stroke-width="2"/>
        <circle cx="155" cy="115" r="8" fill="#9CA3AF"/>
        <!-- Registration -->
        <text x="100" y="95" font-size="10" fill="white" text-anchor="middle" font-weight="bold">{{ substr($vehicle->registration_number ?? 'VEHICLE', 0, 10) }}</text>
        <!-- Headlight/Taillight -->
        <path d="M170 75 L185 75 L185 95 L170 95 Z" fill="#FCD34D" stroke="#F59E0B" stroke-width="1"/>
    </svg>
    @break
@endswitch