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

        // Attentes, en secondes, avant chaque relecture du distant après un
        // envoi. pCloud n'indexe pas instantanément : interrogé aussitôt, il
        // répond une liste où le fichier ne figure pas encore, et la
        // vérification concluait à tort à une panne.
        'verify_delays' => [2, 5, 10, 20],

        // Chemin du binaire rclone.
        //
        // Sur un hébergement mutualisé, rclone est souvent installé dans
        // ~/bin, qui n'appartient pas au PATH des processus lancés par le
        // cron : appeler « rclone » tout court échouerait alors chaque nuit,
        // sans que rien ne le signale côté interface.
        'binary' => env('BACKUP_RCLONE_BINARY', 'rclone'),
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

    /*
    |--------------------------------------------------------------------------
    | Binaire mysqldump
    |--------------------------------------------------------------------------
    | Même précaution que pour rclone : le PATH d'un processus lancé par le cron
    | est réduit au strict minimum et ne contient pas forcément le répertoire où
    | vit mysqldump. Appelé sans chemin absolu, le dump échouerait chaque nuit —
    | et l'échec survenant avant la première écriture dans le journal, il ne
    | laisserait aucune trace exploitable.
    */
    'mysqldump_binary' => env('BACKUP_MYSQLDUMP_BINARY', 'mysqldump'),
];
