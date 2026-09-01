<?php

/*
|--------------------------------------------------------------------------
| Identité de la marque
|--------------------------------------------------------------------------
|
| Source unique du nom et du domaine du service. Le nom est écrit en dur à
| plus de quatre cents endroits dans le code et les traductions, ce qui rend
| tout changement de dénomination long et risqué.
|
| Un changement de nom est engagé (voir Planning). Ce fichier existe pour que
| ce jour-là il ne reste qu'une valeur à modifier, et non une recherche à
| travers le dépôt.
|
| ⚠️ Tout dérive de APP_NAME et APP_URL, déjà présents. Ne pas introduire une
| seconde source de vérité : deux valeurs finissent toujours par diverger.
|
*/

$nom = env('APP_NAME', 'faktur.lu');

return [
    // Nom affiché : « faktur.lu ». Sert dans les titres, les courriels, les
    // pieds de page, les données structurées.
    'nom' => $nom,

    // Domaine, sans protocole. Sert aux adresses électroniques et aux liens
    // écrits en clair dans les contenus.
    'domaine' => env('BRAND_DOMAIN', $nom),

    // Adresse d'expédition par défaut des documents envoyés aux clients.
    'email_expediteur' => env('MAIL_FROM_ADDRESS', 'factures@'.env('BRAND_DOMAIN', $nom)),

    // Adresse de contact publiée sur le site.
    'email_contact' => env('BRAND_CONTACT_EMAIL', 'contact@'.env('BRAND_DOMAIN', $nom)),

    // Profils externes cités dans les données structurées lues par les
    // moteurs de recherche. Ils ne se déduisent PAS du domaine : l'identifiant
    // d'un profil LinkedIn est ce qu'il est. Ils figurent ici pour que le
    // changement de nom ne demande de relire qu'un seul fichier.
    'reseaux' => [
        'https://www.linkedin.com/company/faktur-lu/',
        'https://www.trustpilot.com/review/faktur.lu',
    ],
];
