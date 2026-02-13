<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Monitoring Enabled
    |--------------------------------------------------------------------------
    |
    | Enable or disable request metrics tracking.
    |
    */
    'enabled' => env('MONITORING_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Retention Days
    |--------------------------------------------------------------------------
    |
    | Number of days to keep metrics before cleanup.
    |
    */
    'retention_days' => env('MONITORING_RETENTION_DAYS', 30),

    /*
    |--------------------------------------------------------------------------
    | Alert Thresholds
    |--------------------------------------------------------------------------
    |
    | Define warning and critical thresholds for various metrics.
    |
    */
    'thresholds' => [
        'response_time' => [
            'warning' => 500,   // ms
            'critical' => 2000,
        ],
        'query_count' => [
            'warning' => 20,
            'critical' => 50,
        ],
        'memory' => [
            'warning' => 64,    // MB
            'critical' => 128,
        ],
        'disk_usage' => [
            'warning' => 50,    // %
            'critical' => 80,
        ],
        'failed_jobs' => [
            'warning' => 1,
            'critical' => 5,
        ],
        'error_rate' => [
            'warning' => 1,     // %
            'critical' => 5,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Excluded Paths
    |--------------------------------------------------------------------------
    |
    | Paths to exclude from metrics tracking.
    |
    */
    'exclude_paths' => [
        'admin/monitoring*',
        '_debugbar/*',
        'horizon/*',
        'telescope/*',
        'livewire/*',
        'sanctum/*',
    ],

    /*
    |--------------------------------------------------------------------------
    | Sampling Rate
    |--------------------------------------------------------------------------
    |
    | Percentage of requests to track (1-100).
    | Set to 100 for full tracking, lower for high-traffic apps.
    |
    */
    'sampling_rate' => env('MONITORING_SAMPLING_RATE', 100),
];
