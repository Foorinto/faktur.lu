<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Peppol Access Point Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for sending invoices via the Peppol network.
    | Supported providers: simulation, storecove
    |
    */

    'enabled' => env('PEPPOL_ENABLED', false),

    'provider' => env('PEPPOL_PROVIDER', 'simulation'),

    /*
    |--------------------------------------------------------------------------
    | Storecove Configuration
    |--------------------------------------------------------------------------
    |
    | Storecove is a certified Peppol Access Point with a simple REST API.
    | https://www.storecove.com/docs/
    |
    */

    'storecove' => [
        'api_key' => env('STORECOVE_API_KEY'),
        'api_url' => env('STORECOVE_API_URL', 'https://api.storecove.com/api/v2'),
        'legal_entity_id' => env('STORECOVE_LEGAL_ENTITY_ID'),
        'sandbox' => env('STORECOVE_SANDBOX', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Simulation Configuration
    |--------------------------------------------------------------------------
    |
    | Used for development and testing without a real Access Point account.
    |
    */

    'simulation' => [
        'delay_seconds' => env('PEPPOL_SIMULATION_DELAY', 2),
        'success_rate' => env('PEPPOL_SIMULATION_SUCCESS_RATE', 0.95), // 95% success rate
    ],

    /*
    |--------------------------------------------------------------------------
    | Retry Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for job retries when transmission fails.
    |
    */

    'retry' => [
        'max_attempts' => 3,
        'backoff_seconds' => 60,
    ],

];
