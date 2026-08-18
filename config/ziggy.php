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

        /*
         * Routes publiées à un VISITEUR ANONYME.
         *
         * Sur 547 routes et 65 Ko, 390 routes et 45 Ko relèvent de
         * l'application : personne qui lit la page tarifs n'a besoin de la
         * gestion RH, du portail comptable ou de la facturation. Ce poids part
         * pourtant dans chaque page, et dans chacun des 430 snapshots servis
         * aux robots.
         *
         * ⚠️ C'est une liste BLANCHE : une famille oubliée fait lever une
         * exception Ziggy, et la page blanchit sans erreur serveur. Elle a donc
         * été construite par COMPLÉMENT des espaces de noms applicatifs, jamais
         * à la main.
         *
         * Elle ne s'applique qu'aux visiteurs anonymes. Un utilisateur connecté
         * reçoit le filtre par défaut ci-dessus, un administrateur tout.
         */
        'public' => [
            'about.*', 'alternatives.*', 'blog.*', 'contact', 'contact.*',
            'drip.*', 'faia-validator.*', 'features.*', 'for_accountants.*',
            'for_freelances.*', 'for_smes.*', 'glossary.*', 'home', 'home.*',
            'impersonation.*', 'indexnow.*', 'legal.*', 'locale.*', 'login',
            'logout', 'newsletter.*', 'partners.*', 'pricing.*', 'register',
            'sanctum.*', 'sitemap.*', 'storage.*', 'survey.*', 'tools.*',
            'translations.*', 'verification.*', 'why_faktur.*',
            // Parcours d'authentification : mot de passe oublié, défi 2FA.
            'password.*', 'two-factor.login', 'two-factor.login.*',
            // Le layout public lie le tableau de bord derrière un `v-if` sur
            // l'utilisateur connecté. Vue n'évalue donc pas l'expression pour un
            // anonyme — mais faire reposer la survie du site public sur ce
            // détail serait imprudent : déplacer ce lien hors du `v-if`
            // blanchirait toutes les pages.
            'dashboard',
        ],
    ],

];
