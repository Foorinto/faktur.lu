<?php

/**
 * Configuration des pays supportés par faktur.lu
 *
 * Chaque pays contient :
 * - name : Nom du pays
 * - currency : Devise (ISO 4217)
 * - vat_rates : Taux de TVA disponibles
 * - franchise : Configuration du régime de franchise
 * - vat_number_format : Regex de validation du numéro de TVA
 * - vat_number_example : Exemple de numéro de TVA
 * - fiscal_export : Format d'export fiscal supporté
 */

return [
    'LU' => [
        'name' => 'Luxembourg',
        'name_en' => 'Luxembourg',
        'currency' => 'EUR',
        'vat_rates' => [
            ['value' => 17, 'label' => '17% (Standard)', 'label_short' => '17%', 'type' => 'standard', 'default' => true],
            ['value' => 14, 'label' => '14% (Intermédiaire)', 'label_short' => '14%', 'type' => 'intermediate'],
            ['value' => 8, 'label' => '8% (Réduit)', 'label_short' => '8%', 'type' => 'reduced'],
            ['value' => 3, 'label' => '3% (Super-réduit)', 'label_short' => '3%', 'type' => 'super_reduced'],
            ['value' => 0, 'label' => '0% (Exonéré)', 'label_short' => '0%', 'type' => 'exempt'],
        ],
        'default_vat_rate' => 17,
        'franchise' => [
            'enabled' => true,
            'threshold' => 35000,
            'threshold_type' => 'single', // single, services_goods
            'legal_reference' => 'Art. 57 du Code de la TVA luxembourgeois',
            'mention' => 'TVA non applicable, art. 57 du Code de la TVA luxembourgeois (Régime de franchise de taxe)',
            'effect' => 'immediate', // immediate, next_month, next_year
            'declaration_delay_days' => 15,
        ],
        'vat_number' => [
            'format' => '/^LU\d{8}$/',
            'example' => 'LU12345678',
            'prefix' => 'LU',
            'length' => 8,
        ],
        'fiscal_identifiers' => [
            'primary' => [
                'field' => 'matricule',
                'label' => 'Matricule',
                'placeholder' => '0000000000000',
                'help' => '13 chiffres, délivré par l\'Administration des Contributions Directes',
                'maxlength' => 13,
                'required' => true,
            ],
            'secondary' => [
                'field' => 'rcs_number',
                'label' => 'N° RCS Luxembourg',
                'placeholder' => 'B123456',
                'help' => 'Registre de Commerce et des Sociétés (si applicable)',
                'maxlength' => 20,
                'required' => false,
            ],
            'has_establishment_authorization' => true,
        ],
        'fiscal_export' => [
            'type' => 'faia',
            'name' => 'FAIA (SAF-T Luxembourg)',
            'description' => 'Fichier d\'Audit Informatisé AED',
        ],
        'locale' => 'fr_LU',
        'date_format' => 'd/m/Y',
        'number_format' => [
            'decimal_separator' => ',',
            'thousands_separator' => ' ',
        ],
        'vat_mentions' => [
            'franchise' => 'TVA non applicable, art. 57 du Code de la TVA luxembourgeois (Régime de franchise de taxe)',
            'reverse_charge' => 'Autoliquidation - Article 44 de la directive 2006/112/CE',
            'intra_eu' => 'Exonération de TVA - Livraison intracommunautaire (Art. 43 du Code de la TVA luxembourgeois)',
            'export' => 'Exonération de TVA - Exportation (Art. 43 du Code de la TVA luxembourgeois)',
        ],
    ],

    'FR' => [
        'name' => 'France',
        'name_en' => 'France',
        'currency' => 'EUR',
        'vat_rates' => [
            ['value' => 20, 'label' => '20% (Normal)', 'label_short' => '20%', 'type' => 'standard', 'default' => true],
            ['value' => 10, 'label' => '10% (Intermédiaire)', 'label_short' => '10%', 'type' => 'intermediate'],
            ['value' => 5.5, 'label' => '5,5% (Réduit)', 'label_short' => '5,5%', 'type' => 'reduced'],
            ['value' => 2.1, 'label' => '2,1% (Super-réduit)', 'label_short' => '2,1%', 'type' => 'super_reduced'],
            ['value' => 0, 'label' => '0% (Exonéré)', 'label_short' => '0%', 'type' => 'exempt'],
        ],
        'default_vat_rate' => 20,
        'franchise' => [
            'enabled' => true,
            'threshold' => 37500, // Services (micro-BNC)
            'threshold_services' => 37500,
            'threshold_goods' => 85000, // Ventes de biens (micro-BIC)
            'threshold_services_major' => 39100, // Seuil majoré services
            'threshold_goods_major' => 93500, // Seuil majoré biens
            'threshold_type' => 'services_goods',
            'legal_reference' => 'Art. 293 B du CGI',
            'mention' => 'TVA non applicable, art. 293 B du CGI',
            'effect' => 'immediate_with_tolerance',
            'declaration_delay_days' => 30,
        ],
        'vat_number' => [
            'format' => '/^FR[A-Z0-9]{2}\d{9}$/',
            'example' => 'FR12345678901',
            'prefix' => 'FR',
            'length' => 11, // 2 caractères de clé + 9 chiffres SIREN
        ],
        'fiscal_identifiers' => [
            'primary' => [
                'field' => 'matricule',
                'label' => 'SIRET',
                'placeholder' => '12345678901234',
                'help' => '14 chiffres (SIREN + NIC)',
                'maxlength' => 14,
                'required' => true,
            ],
            'secondary' => [
                'field' => 'rcs_number',
                'label' => 'RCS',
                'placeholder' => 'Paris B 123 456 789',
                'help' => 'Registre du Commerce et des Sociétés (ville + numéro)',
                'maxlength' => 30,
                'required' => false,
            ],
            'has_establishment_authorization' => false,
        ],
        'fiscal_export' => [
            'type' => 'fec',
            'name' => 'FEC (Fichier des Écritures Comptables)',
            'description' => 'Export comptable obligatoire pour contrôle fiscal',
        ],
        'locale' => 'fr_FR',
        'date_format' => 'd/m/Y',
        'number_format' => [
            'decimal_separator' => ',',
            'thousands_separator' => ' ',
        ],
        'vat_mentions' => [
            'franchise' => 'TVA non applicable, art. 293 B du Code général des impôts',
            'reverse_charge' => 'Autoliquidation de la TVA - Art. 283-2 du CGI',
            'intra_eu' => 'Exonération de TVA - Livraison intracommunautaire, art. 262 ter-I du CGI',
            'export' => 'Exonération de TVA - Exportation, art. 262-I du CGI',
        ],
    ],

    'BE' => [
        'name' => 'Belgique',
        'name_en' => 'Belgium',
        'currency' => 'EUR',
        'vat_rates' => [
            ['value' => 21, 'label' => '21% (Normal)', 'label_short' => '21%', 'type' => 'standard', 'default' => true],
            ['value' => 12, 'label' => '12% (Intermédiaire)', 'label_short' => '12%', 'type' => 'intermediate'],
            ['value' => 6, 'label' => '6% (Réduit)', 'label_short' => '6%', 'type' => 'reduced'],
            ['value' => 0, 'label' => '0% (Exonéré)', 'label_short' => '0%', 'type' => 'exempt'],
        ],
        'default_vat_rate' => 21,
        'franchise' => [
            'enabled' => true,
            'threshold' => 25000,
            'threshold_type' => 'single',
            'legal_reference' => 'Art. 56bis du Code TVA belge',
            'mention' => 'Petite entreprise assujettie au régime de la franchise de taxe - TVA non applicable',
            'effect' => 'next_month', // Effet le 1er du mois suivant
            'declaration_delay_days' => 15,
        ],
        'vat_number' => [
            'format' => '/^BE0\d{9}$/',
            'example' => 'BE0123456789',
            'prefix' => 'BE',
            'length' => 10,
        ],
        'fiscal_identifiers' => [
            'primary' => [
                'field' => 'matricule',
                'label' => 'N° d\'entreprise BCE',
                'placeholder' => '0123.456.789',
                'help' => '10 chiffres, Banque-Carrefour des Entreprises',
                'maxlength' => 12,
                'required' => true,
            ],
            'secondary' => [
                'field' => 'rcs_number',
                'label' => 'N° RPM',
                'placeholder' => 'Bruxelles 0123.456.789',
                'help' => 'Registre des Personnes Morales (si applicable)',
                'maxlength' => 30,
                'required' => false,
            ],
            'has_establishment_authorization' => false,
        ],
        'fiscal_export' => [
            'type' => 'listing_tva',
            'name' => 'Listing TVA annuel',
            'description' => 'Listing des clients assujettis + déclarations périodiques',
        ],
        'locale' => 'fr_BE',
        'date_format' => 'd/m/Y',
        'number_format' => [
            'decimal_separator' => ',',
            'thousands_separator' => '.',
        ],
        'vat_mentions' => [
            'franchise' => 'Petite entreprise assujettie au régime de la franchise de taxe - TVA non applicable (Art. 56bis du Code TVA)',
            'reverse_charge' => 'Autoliquidation - Art. 51 du Code de la TVA belge',
            'intra_eu' => 'Exonération de TVA - Livraison intracommunautaire (Art. 39bis du Code de la TVA)',
            'export' => 'Exonération de TVA - Exportation (Art. 39 du Code de la TVA)',
        ],
    ],

    'DE' => [
        'name' => 'Allemagne',
        'name_en' => 'Germany',
        'currency' => 'EUR',
        'vat_rates' => [
            ['value' => 19, 'label' => '19% (Normal)', 'label_short' => '19%', 'type' => 'standard', 'default' => true],
            ['value' => 7, 'label' => '7% (Ermäßigt)', 'label_short' => '7%', 'type' => 'reduced'],
            ['value' => 0, 'label' => '0% (Steuerfrei)', 'label_short' => '0%', 'type' => 'exempt'],
        ],
        'default_vat_rate' => 19,
        'franchise' => [
            'enabled' => true,
            'threshold' => 22000, // CA année N-1
            'threshold_forecast' => 50000, // CA prévisionnel année N
            'threshold_type' => 'previous_year', // Basé sur l'année précédente
            'legal_reference' => '§ 19 UStG (Umsatzsteuergesetz)',
            'mention' => 'Gemäß § 19 UStG wird keine Umsatzsteuer berechnet (Kleinunternehmerregelung)',
            'mention_fr' => 'Conformément au § 19 UStG, aucune TVA n\'est facturée (régime des petits entrepreneurs)',
            'effect' => 'next_year', // Effet à partir de l'année suivante
            'effect_immediate_threshold' => 50000, // Si dépassé, effet immédiat
            'declaration_delay_days' => 0, // Pas de délai, effet automatique
        ],
        'vat_number' => [
            'format' => '/^DE\d{9}$/',
            'example' => 'DE123456789',
            'prefix' => 'DE',
            'length' => 9,
        ],
        'fiscal_identifiers' => [
            'primary' => [
                'field' => 'matricule',
                'label' => 'Steuernummer',
                'placeholder' => '123/456/78901',
                'help' => 'Numéro fiscal délivré par le Finanzamt',
                'maxlength' => 20,
                'required' => true,
            ],
            'secondary' => [
                'field' => 'rcs_number',
                'label' => 'Handelsregister',
                'placeholder' => 'HRB 12345, Amtsgericht München',
                'help' => 'Registre du commerce (si applicable)',
                'maxlength' => 50,
                'required' => false,
            ],
            'has_establishment_authorization' => false,
        ],
        'fiscal_export' => [
            'type' => 'gdpdu',
            'name' => 'GDPdU / GoBD',
            'description' => 'Export comptable conforme GoBD pour contrôle fiscal',
        ],
        'locale' => 'de_DE',
        'date_format' => 'd.m.Y',
        'number_format' => [
            'decimal_separator' => ',',
            'thousands_separator' => '.',
        ],
        'vat_mentions' => [
            'franchise' => 'Gemäß § 19 UStG wird keine Umsatzsteuer berechnet (Kleinunternehmerregelung)',
            'reverse_charge' => 'Steuerschuldnerschaft des Leistungsempfängers - § 13b UStG',
            'intra_eu' => 'Steuerfreie innergemeinschaftliche Lieferung - § 4 Nr. 1b UStG',
            'export' => 'Steuerfreie Ausfuhrlieferung - § 4 Nr. 1a UStG',
        ],
    ],
];
