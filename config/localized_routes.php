<?php

/**
 * Localized route slugs for each language.
 *
 * Structure: 'route_name' => ['locale' => 'slug']
 *
 * IMPORTANT: keep this in sync with:
 * - resources/js/Composables/useLocalizedRoute.js (frontend)
 * - app/Http/Controllers/LocaleController.php LOCALIZED_SLUGS (locale switcher)
 */

return [
    'features.index' => [
        'fr' => 'fonctionnalites',
        'de' => 'funktionen',
        'en' => 'features',
        'lb' => 'funktiounen',
        'pt' => 'funcionalidades',
    ],

    'features.show' => [
        'fr' => 'fonctionnalites',
        'de' => 'funktionen',
        'en' => 'features',
        'lb' => 'funktiounen',
        'pt' => 'funcionalidades',
    ],

    'about' => [
        'fr' => 'a-propos',
        'de' => 'ueber-uns',
        'en' => 'about',
        'lb' => 'iwwer-eis',
        'pt' => 'sobre',
    ],

    'why_faktur' => [
        'fr' => 'pourquoi-faktur-lu',
        'de' => 'warum-faktur-lu',
        'en' => 'why-faktur-lu',
        'lb' => 'firwat-faktur-lu',
        'pt' => 'porque-faktur-lu',
    ],

    'partners' => [
        'fr' => 'partenaires',
        'de' => 'partner',
        'en' => 'partners',
        'lb' => 'partneren',
        'pt' => 'parceiros',
    ],

    'for_freelances' => [
        'fr' => 'pour-freelances',
        'de' => 'fuer-freelancer',
        'en' => 'for-freelancers',
        'lb' => 'fir-freelancer',
        'pt' => 'para-freelancers',
    ],

    'for_smes' => [
        'fr' => 'pour-pme',
        'de' => 'fuer-kmu',
        'en' => 'for-smes',
        'lb' => 'fir-kmu',
        'pt' => 'para-pme',
    ],

    'contact' => [
        'fr' => 'contact',
        'de' => 'contact',
        'en' => 'contact',
        'lb' => 'contact',
        'pt' => 'contacto',
    ],

    'pricing' => [
        'fr' => 'tarifs',
        'de' => 'preise',
        'en' => 'pricing',
        'lb' => 'präisser',
        'pt' => 'precos',
    ],

    'faia-validator' => [
        'fr' => 'validateur-faia',
        'de' => 'faia-validator',
        'en' => 'faia-validator',
        'lb' => 'faia-validator',
        'pt' => 'validador-faia',
    ],

    'tools' => [
        'fr' => 'outils',
        'de' => 'werkzeuge',
        'en' => 'tools',
        'lb' => 'handgeschir',
        'pt' => 'ferramentas',
    ],

    'tools.vat_calculator' => [
        'fr' => 'outils/calculateur-tva',
        'de' => 'werkzeuge/mwst-rechner',
        'en' => 'tools/vat-calculator',
        'lb' => 'handgeschir/tva-rechner',
        'pt' => 'ferramentas/calculadora-iva',
    ],

    'tools.vat_exemption' => [
        'fr' => 'outils/franchise-tva',
        'de' => 'werkzeuge/mwst-befreiung',
        'en' => 'tools/vat-exemption',
        'lb' => 'handgeschir/tva-befreiung',
        'pt' => 'ferramentas/isencao-iva',
    ],

    'tools.iban_validator' => [
        'fr' => 'outils/validateur-iban',
        'de' => 'werkzeuge/iban-pruefer',
        'en' => 'tools/iban-validator',
        'lb' => 'handgeschir/iban-validateur',
        'pt' => 'ferramentas/validador-iban',
    ],

    'tools.invoice_generator' => [
        'fr' => 'outils/generateur-facture',
        'de' => 'werkzeuge/rechnungsgenerator',
        'en' => 'tools/invoice-generator',
        'lb' => 'handgeschir/rechnungsgenerator',
        'pt' => 'ferramentas/gerador-fatura',
    ],

    'tools.templates' => [
        'fr' => 'outils/modeles-facture',
        'de' => 'werkzeuge/vorlagen',
        'en' => 'tools/templates',
        'lb' => 'handgeschir/modellen',
        'pt' => 'ferramentas/modelos',
    ],

    'legal.mentions' => [
        'fr' => 'mentions-legales',
        'de' => 'impressum',
        'en' => 'legal-notice',
        'lb' => 'impressum',
        'pt' => 'aviso-legal',
    ],

    'legal.privacy' => [
        'fr' => 'confidentialite',
        'de' => 'datenschutz',
        'en' => 'privacy',
        'lb' => 'dateschutz',
        'pt' => 'privacidade',
    ],

    'legal.terms' => [
        'fr' => 'cgu',
        'de' => 'agb',
        'en' => 'terms',
        'lb' => 'agb',
        'pt' => 'termos',
    ],

    'legal.cookies' => [
        'fr' => 'cookies',
        'de' => 'cookies',
        'en' => 'cookies',
        'lb' => 'cookies',
        'pt' => 'cookies',
    ],

    'blog.index' => [
        'fr' => 'blog',
        'de' => 'blog',
        'en' => 'blog',
        'lb' => 'blog',
        'pt' => 'blog',
    ],

    'blog.category' => [
        'fr' => 'blog/categorie',
        'de' => 'blog/kategorie',
        'en' => 'blog/category',
        'lb' => 'blog/kategorie',
        'pt' => 'blog/categoria',
    ],

    'blog.tag' => [
        'fr' => 'blog/tag',
        'de' => 'blog/tag',
        'en' => 'blog/tag',
        'lb' => 'blog/tag',
        'pt' => 'blog/tag',
    ],
];
