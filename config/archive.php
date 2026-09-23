<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Archivage PDF/A automatique des factures (FEAT-126)
    |--------------------------------------------------------------------------
    |
    | Chaque facture ou avoir finalisé est archivé aussitôt : PDF converti en
    | PDF/A par Ghostscript, empreinte SHA-256 inscrite au journal d'audit
    | scellé, copie chiffrée hors site chaque nuit. Conservation dix ans, pour
    | tous les plans : c'est une obligation légale, pas une option.
    |
    */

    // Archiver à la finalisation (sinon, seul le rattrapage nocturne archive).
    'auto' => (bool) env('ARCHIVE_AUTO', true),

    // pdfa-1b (recommandé), pdfa-3b (pièces jointes possibles) ou pdf.
    'format' => env('ARCHIVE_FORMAT', 'pdfa-1b'),

    // Rattrapage nocturne : factures finalisées sans archive (l'existant au
    // déploiement, ou un échec à la finalisation). Bornes par passage.
    'catch_up' => [
        'limit' => (int) env('ARCHIVE_CATCH_UP_LIMIT', 200),
        'seconds' => (int) env('ARCHIVE_CATCH_UP_SECONDS', 300),
    ],

    'export' => [
        // Copie hors site via rclone (dépôt et binaire de config/backup.php),
        // dans un sous-dossier, chaque fichier chiffré avec la clé des
        // sauvegardes. Jamais en clair : ce sont des factures nominatives.
        'cloud' => (bool) env('ARCHIVE_EXPORT_CLOUD', env('BACKUP_CLOUD_ENABLED', false)),
        'cloud_subdir' => env('ARCHIVE_EXPORT_CLOUD_SUBDIR', 'archives'),
        'limit' => (int) env('ARCHIVE_EXPORT_LIMIT', 500),
    ],

    'notification_email' => env('ARCHIVE_NOTIFICATION_EMAIL', env('BACKUP_NOTIFICATION_EMAIL')),

];
