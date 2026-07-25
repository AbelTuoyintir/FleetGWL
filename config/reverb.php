<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Reverb Server
    |--------------------------------------------------------------------------
    |
    | This option controls the default server used by Reverb to broadcast
    | events to your client applications. You may configure multiple
    | servers if you need to support multiple broadcast applications.
    |
    */

    'default' => env('REVERB_SERVER', 'reverb'),

    /*
    |--------------------------------------------------------------------------
    | Reverb Servers
    |--------------------------------------------------------------------------
    |
    | Here you may define all of the Reverb "servers" that will be used
    | to broadcast events to your client applications. Each server
    | can be configured with its own host, port, and other options.
    |
    */

    'servers' => [

        'reverb' => [
            'host' => env('REVERB_SERVER_HOST', '0.0.0.0'),
            'port' => env('REVERB_SERVER_PORT', 8080),
            'hostname' => env('REVERB_SERVER_HOSTNAME', 'localhost'),
            'options' => [
                'tls' => [],
            ],
            'max_request_size' => env('REVERB_MAX_REQUEST_SIZE', 10000),
            'scaling' => [
                'enabled' => env('REVERB_SCALING_ENABLED', false),
                'channel' => env('REVERB_SCALING_CHANNEL', 'reverb_scaling'),
                'server' => [
                    'url' => env('REDIS_URL'),
                    'host' => env('REDIS_HOST', '127.0.0.1'),
                    'port' => env('REDIS_PORT', '6379'),
                    'password' => env('REDIS_PASSWORD'),
                    'database' => env('REDIS_DB', '0'),
                ],
            ],
            'pulse_ingester' => env('REVERB_PULSE_INGESTER_ENABLED', false),
            'pulse_ingest_interval' => env('REVERB_PULSE_INGEST_INTERVAL', 15),
            'telescope_ingest_interval' => env('REVERB_TELESCOPE_INGEST_INTERVAL', 15),
            'workers' => [
                'count' => env('REVERB_WORKER_COUNT', 4),
                'activity_timeout' => env('REVERB_WORKER_ACTIVITY_TIMEOUT', 30),
            ],
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Additional Options
    |--------------------------------------------------------------------------
    |
    | Here you may define additional options that will be passed to the
    | Reverb server instance. These options can be used to configure
    | additional aspects of the Reverb server, such as logging.
    |
    */

    'options' => [
        'logging' => [
            'enabled' => env('REVERB_LOGGING_ENABLED', false),
            'channel' => env('REVERB_LOGGING_CHANNEL', 'stack'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Broadcasting Apps
    |--------------------------------------------------------------------------
    |
    | Here you may define the broadcasting apps that will be used by
    | Reverb to broadcast events to your client applications. Each
    | app can be configured with its own credentials and options.
    |
    */

    'apps' => [

        'provider' => 'config',

        'apps' => [
            [
                'app_id' => env('REVERB_APP_ID'),
                'key' => env('REVERB_APP_KEY'),
                'secret' => env('REVERB_APP_SECRET'),
                'capacity' => null,
                'enable_client_messages' => false,
                'enable_statistics' => true,
            ],
        ],

    ],

];

