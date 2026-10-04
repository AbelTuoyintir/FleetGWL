<?php

return [
    /*
    |--------------------------------------------------------------------------
    | WebRTC ICE Servers Configuration
    |--------------------------------------------------------------------------
    |
    | STUN and TURN servers used for WebRTC peer connection NAT traversal.
    | STUN servers are public by default; TURN servers require authentication
    | in production.
    |
    */

    'ice_servers' => array_filter([
        [
            'urls' => [
                env('STUN_SERVER_1', 'stun:stun.l.google.com:19302'),
                env('STUN_SERVER_2', 'stun:stun1.l.google.com:19302'),
            ],
        ],
        env('TURN_SERVER_URL') ? [
            'urls' => env('TURN_SERVER_URL'),
            'username' => env('TURN_SERVER_USERNAME', ''),
            'credential' => env('TURN_SERVER_PASSWORD', ''),
        ] : null,
    ]),

    /*
    |--------------------------------------------------------------------------
    | Socket.IO Server Configuration
    |--------------------------------------------------------------------------
    */
    'socket_server' => [
        'url' => env('SOCKET_SERVER_URL', 'http://localhost:6001'),
        'port' => env('SOCKET_PORT', 6001),
    ],
];
