<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Backup Enabled
    |--------------------------------------------------------------------------
    */
    'enabled' => env('BACKUP_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Backup Schedule
    |--------------------------------------------------------------------------
    | Time at which the daily backup runs (24h format, server timezone).
    */
    'schedule_time' => env('BACKUP_SCHEDULE_TIME', '03:00'),

    /*
    |--------------------------------------------------------------------------
    | Local Storage
    |--------------------------------------------------------------------------
    */
    'local' => [
        'path' => env('BACKUP_LOCAL_PATH', storage_path('app/backups')),
        'retention_days' => (int) env('BACKUP_RETENTION_LOCAL', 7),
    ],

    /*
    |--------------------------------------------------------------------------
    | Encryption
    |--------------------------------------------------------------------------
    | Backups are encrypted with AES-256-CBC before upload.
    | If no key is set, encryption is skipped.
    */
    'encryption_key' => env('BACKUP_ENCRYPTION_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Cloud Storage (via rclone)
    |--------------------------------------------------------------------------
    | rclone must be installed and configured with the named remote.
    | Run `rclone config` to set up your remote (pCloud, S3, SFTP, etc.).
    | See https://rclone.org/docs/ for supported providers.
    */
    'cloud' => [
        'enabled' => env('BACKUP_CLOUD_ENABLED', false),
        'remote' => env('BACKUP_CLOUD_REMOTE', 'pcloud'),       // rclone remote name
        'path' => env('BACKUP_CLOUD_PATH', '/Backups/Facturation'), // path on the remote
        'retention_days' => (int) env('BACKUP_CLOUD_RETENTION_DAYS', 30),
    ],

    /*
    |--------------------------------------------------------------------------
    | Notifications
    |--------------------------------------------------------------------------
    */
    'notify_on_failure' => env('BACKUP_NOTIFY_ON_FAILURE', true),
    'notify_on_success' => env('BACKUP_NOTIFY_ON_SUCCESS', false),
    'notification_email' => env('BACKUP_NOTIFICATION_EMAIL'),

    /*
    |--------------------------------------------------------------------------
    | Database
    |--------------------------------------------------------------------------
    | Which database connection to back up.
    */
    'database_connection' => env('BACKUP_DB_CONNECTION', env('DB_CONNECTION', 'mysql')),
];
