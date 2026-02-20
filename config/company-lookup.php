<?php

return [
    /*
    |--------------------------------------------------------------------------
    | VIES VAT Validation
    |--------------------------------------------------------------------------
    | European Commission VIES REST API for VAT number validation.
    | Returns company name and address for valid VAT numbers.
    */
    'vies' => [
        'url' => 'https://ec.europa.eu/taxation_customs/vies/rest-api/check-vat-number',
        'timeout' => 10,
        'cache_ttl' => 86400, // 24 hours
    ],

    /*
    |--------------------------------------------------------------------------
    | France — API Recherche Entreprises
    |--------------------------------------------------------------------------
    | Free API from data.gouv.fr — no API key required.
    | Supports full-text search by company name.
    */
    'france' => [
        'url' => 'https://recherche-entreprises.api.gouv.fr/search',
        'timeout' => 10,
        'cache_ttl' => 3600, // 1 hour
        'per_page' => 10,
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache
    |--------------------------------------------------------------------------
    */
    'cache_prefix' => 'company_lookup',
];
