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
    | Cache
    |--------------------------------------------------------------------------
    |
    | Configure response caching to reduce API calls. This is useful for
    | frequently accessed, rarely changing data like server status.
    |
    */

    'cache_enabled' => env('NIMBLE_CACHE_ENABLED', false),

    'cache_driver' => env('NIMBLE_CACHE_DRIVER', 'file'),

    'cache_ttl' => env('NIMBLE_CACHE_TTL', 60), // seconds

    'cache_prefix' => env('NIMBLE_CACHE_PREFIX', 'nimble'),

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
