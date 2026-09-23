<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Journal d'audit démontrable (FEAT-125)
    |--------------------------------------------------------------------------
    |
    | Chaque entrée est scellée : une empreinte de son contenu et de
    | l'empreinte précédente. Une entrée modifiée ou supprimée après coup casse
    | la chaîne, et audit:verify le dit. Une copie signée part chaque nuit hors
    | site, sur le même dépôt rclone que les sauvegardes.
    |
    */

    // Conservation des entrées, en jours (5 ans). Au-delà, audit:prune les
    // supprime, à condition qu'elles aient été scellées et exportées, et pose
    // une ancre pour que la chaîne reste vérifiable.
    'retention_days' => (int) env('AUDIT_RETENTION_DAYS', 1825),

    // Au-delà de cet âge, une entrée non scellée est un signal : le scellement
    // (audit:seal, chaque minute) ne tourne plus.
    'seal_max_age_minutes' => (int) env('AUDIT_SEAL_MAX_AGE_MINUTES', 10),

    'export' => [
        'enabled' => (bool) env('AUDIT_EXPORT_ENABLED', true),
        // Hors site via rclone (dépôt et binaire de config/backup.php), dans un
        // sous-dossier. Sans clé de chiffrement des sauvegardes, l'export reste
        // local : le journal contient des adresses IP et des valeurs masquées.
        'cloud' => (bool) env('AUDIT_EXPORT_CLOUD', env('BACKUP_CLOUD_ENABLED', false)),
        'cloud_subdir' => env('AUDIT_EXPORT_CLOUD_SUBDIR', 'audit'),
        'local_path' => env('AUDIT_EXPORT_LOCAL_PATH', storage_path('app/audit-exports')),
        'local_retention_days' => (int) env('AUDIT_EXPORT_LOCAL_RETENTION', 30),
    ],

    // Destinataire des alertes (chaîne rompue, export en échec). À défaut,
    // celui des sauvegardes.
    'notification_email' => env('AUDIT_NOTIFICATION_EMAIL', env('BACKUP_NOTIFICATION_EMAIL')),

];
