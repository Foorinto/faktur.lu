<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Analytics Provider
    |--------------------------------------------------------------------------
    |
    | Matomo is a self-hosted, privacy-friendly analytics platform.
    | With cookieless tracking enabled, no cookies are used, making it
    | GDPR compliant without requiring a cookie consent banner.
    |
    */

    'enabled' => env('APP_ENV') === 'production',

    'matomo' => [
        'url' => env('MATOMO_URL'),
        'site_id' => env('MATOMO_SITE_ID', '1'),
    ],

];
