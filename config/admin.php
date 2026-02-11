<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Admin URL Prefix
    |--------------------------------------------------------------------------
    |
    | This is the secret URL prefix for accessing the admin panel.
    | Keep this value secret and change it regularly.
    |
    */

    'url_prefix' => env('ADMIN_URL_PREFIX', 'admin-secret-' . md5(env('APP_KEY', 'default'))),

    /*
    |--------------------------------------------------------------------------
    | Admin Credentials
    |--------------------------------------------------------------------------
    |
    | Admin credentials are stored in environment variables for security.
    | The password should be hashed using Hash::make().
    |
    */

    'username' => env('ADMIN_USERNAME', 'admin@faktur.lu'),
    'password_hash' => env('ADMIN_PASSWORD_HASH'),
    '2fa_secret' => env('ADMIN_2FA_SECRET'),
    '2fa_recovery_codes' => env('ADMIN_2FA_RECOVERY_CODES'),

    /*
    |--------------------------------------------------------------------------
    | Session Configuration
    |--------------------------------------------------------------------------
    |
    | Configure the admin session lifetime and cookie settings.
    |
    */

    'session_lifetime' => env('ADMIN_SESSION_LIFETIME', 30), // minutes
    'session_cookie' => 'admin_session',

    /*
    |--------------------------------------------------------------------------
    | Security Settings
    |--------------------------------------------------------------------------
    |
    | Configure rate limiting and IP blocking for the admin panel.
    |
    */

    'max_login_attempts' => env('ADMIN_MAX_LOGIN_ATTEMPTS', 3),
    'ip_block_duration' => env('ADMIN_IP_BLOCK_DURATION', 60), // minutes
    'rate_limit_per_minute' => env('ADMIN_RATE_LIMIT', 5),

    /*
    |--------------------------------------------------------------------------
    | Support Notification Email
    |--------------------------------------------------------------------------
    |
    | Email address to receive new support ticket notifications.
    |
    */

    'support_email' => env('ADMIN_SUPPORT_EMAIL', env('ADMIN_USERNAME', 'admin@faktur.lu')),

];
