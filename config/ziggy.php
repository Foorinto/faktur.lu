<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Routes retirées de la table publiée
    |--------------------------------------------------------------------------
    |
    | La directive @routes sérialise TOUTES les routes nommées dans le HTML de
    | chaque page — 590 chez nous, soit 70 Ko sur 173 Ko servis à un visiteur
    | anonyme. Les routes d'administration en faisaient partie, et avec elles le
    | préfixe secret du panneau : `panel-…` apparaissait quarante-quatre fois
    | dans le code source de la page d'accueil.
    |
    | Ce n'était pas une faille — le panneau exige `auth`, `verified` et
    | `admin.user`, et redirige vers /login. Mais l'obscurité du chemin, ajoutée
    | pour écarter les scanners automatisés, ne valait plus rien.
    |
    | ⚠️ Toute route nommée `admin.*` disparaît donc côté client pour les
    | non-administrateurs. Un appel à `route('admin.…')` depuis un composant que
    | verrait un utilisateur ordinaire lèverait une exception Ziggy. C'est
    | précisément le cas qui a fait sortir l'arrêt d'usurpation de ce préfixe.
    |
    */
    'except' => ['admin.*'],

    /*
    |--------------------------------------------------------------------------
    | Groupes
    |--------------------------------------------------------------------------
    |
    | Un groupe REMPLACE le filtre ci-dessus. « admin » vaut donc toutes les
    | routes, y compris les siennes : c'est le seul cas où le préfixe a le droit
    | d'atterrir dans la page.
    |
    */
    'groups' => [
        'admin' => ['*'],
    ],

];
