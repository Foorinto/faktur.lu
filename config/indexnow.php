<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Clé IndexNow
    |--------------------------------------------------------------------------
    |
    | La clé n'est PAS un secret : le protocole exige qu'elle soit publiée en
    | clair à l'adresse indiquée par `key_location`, et c'est précisément cette
    | publication qui prouve qu'on contrôle le domaine. La commiter est donc
    | sans risque — et évite d'avoir à toucher au .env de production.
    |
    | La changer invalide la vérification tant que le nouveau fichier n'est pas
    | en ligne.
    |
    */
    'key' => env('INDEXNOW_KEY', '405e199542218dc4594cd94238a0123e'),

    /*
    |--------------------------------------------------------------------------
    | Point d'entrée
    |--------------------------------------------------------------------------
    |
    | api.indexnow.org relaie aux moteurs participants — Bing, Yandex, Seznam,
    | Naver. Google n'y participe pas : cette notification ne remplace donc pas
    | le sitemap, elle accélère seulement les moteurs qui l'acceptent.
    |
    */
    'endpoint' => env('INDEXNOW_ENDPOINT', 'https://api.indexnow.org/indexnow'),

    /*
    |--------------------------------------------------------------------------
    | Activation
    |--------------------------------------------------------------------------
    |
    | Désactivé hors production : notifier un moteur depuis un poste de
    | développement ou depuis staging soumettrait des URL qui n'existent pas,
    | ou pire, l'adresse de staging elle-même.
    |
    */
    'enabled' => env('INDEXNOW_ENABLED', env('APP_ENV') === 'production'),

    /*
    |--------------------------------------------------------------------------
    | Plafond par envoi
    |--------------------------------------------------------------------------
    |
    | Le protocole accepte 10 000 URL par requête. On reste très en dessous :
    | le site en compte 430, et un envoi plus petit se rejoue plus facilement.
    |
    */
    'batch_size' => 500,

    /*
    |--------------------------------------------------------------------------
    | Notifier depuis la console
    |--------------------------------------------------------------------------
    |
    | Quarante migrations touchent au blog, et `blog:optimize-links` réécrit les
    | articles en lot : chacune déclencherait autant d'appels HTTP synchrones, à
    | dix secondes de délai chacun. Un déploiement s'en trouverait suspendu, et
    | les moteurs recevraient deux cents notifications pour un seul changement
    | de maillage interne.
    |
    | La publication se faisant depuis l'administration, donc en HTTP, couper la
    | console ne retire rien d'utile. Pour un envoi en masse délibéré,
    | `indexnow:submit` existe.
    |
    */
    'notify_from_console' => env('INDEXNOW_NOTIFY_FROM_CONSOLE', false),

];
