<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Analytics Provider
    |--------------------------------------------------------------------------
    |
    | TWIPLA is a privacy-friendly analytics service hosted in Germany (EU).
    | With Maximum Privacy Mode enabled, no cookies are used, making it
    | GDPR compliant without requiring a cookie consent banner.
    |
    | Register at: https://www.twipla.com
    |
    */

    'enabled' => env('APP_ENV') === 'production',

    'twipla' => [
        'site_id' => env('TWIPLA_SITE_ID'),
    ],

];
