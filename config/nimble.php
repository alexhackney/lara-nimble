<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Nimble Streamer Connection Settings
    |--------------------------------------------------------------------------
    |
    | Configure your Nimble Streamer server connection details here. These
    | settings are used to establish communication with the Nimble API.
    |
    */

    'host' => env('NIMBLE_HOST', 'localhost'),

    'port' => env('NIMBLE_PORT', 8082),

    'protocol' => env('NIMBLE_PROTOCOL', 'http'),

    /*
    |--------------------------------------------------------------------------
    | Authentication
    |--------------------------------------------------------------------------
    |
    | If your Nimble server is configured with a management token, provide
    | it here. Leave null if authentication is not required.
    |
    */

    'token' => env('NIMBLE_TOKEN'),

    /*
    |--------------------------------------------------------------------------
    | Request Settings
    |--------------------------------------------------------------------------
    |
    | Configure HTTP request behavior including timeouts and retry logic.
    |
    */

    'timeout' => env('NIMBLE_TIMEOUT', 30),

    'connect_timeout' => env('NIMBLE_CONNECT_TIMEOUT', 10),

    'retry_times' => env('NIMBLE_RETRY_TIMES', 3),

    'retry_sleep' => env('NIMBLE_RETRY_SLEEP', 100),

    /*
    |--------------------------------------------------------------------------
    | Logging
    |--------------------------------------------------------------------------
    |
    | Enable request/response logging for debugging purposes. Be careful
    | in production as this may log sensitive information.
    |
    */

    'log_requests' => env('NIMBLE_LOG_REQUESTS', false),

    'log_channel' => env('NIMBLE_LOG_CHANNEL', 'stack'),

    /*
    |--------------------------------------------------------------------------
    | Response Caching
    |--------------------------------------------------------------------------
    |
    | When enabled, frequently polled read endpoints (live stream status,
    | server status) are cached for a short TTL so dashboards refreshing
    | every second do not hammer the Nimble API. Disabled by default;
    | note that StreamService::exists()/find() also serve cached data
    | while enabled. 'store' of null uses your default cache store.
    |
    */

    'cache' => [
        'enabled' => env('NIMBLE_CACHE_ENABLED', false),
        'ttl' => env('NIMBLE_CACHE_TTL', 2), // seconds
        'store' => env('NIMBLE_CACHE_STORE'),
    ],

    /*
    |--------------------------------------------------------------------------
    | SSL/TLS Verification
    |--------------------------------------------------------------------------
    |
    | Configure SSL certificate verification. Set to false to disable
    | verification (not recommended for production).
    |
    */

    'verify_ssl' => env('NIMBLE_VERIFY_SSL', true),

];
