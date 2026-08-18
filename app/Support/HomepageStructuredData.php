<?php

namespace App\Support;

use Illuminate\Support\Facades\Lang;

/**
 * Builds the homepage JSON-LD graph (Organization/LocalBusiness,
 * SoftwareApplication, FAQPage).
 *
 * Rendered server-side in app.blade.php so Googlebot sees the structured data
 * in the initial HTML, without executing JavaScript. The content mirrors what
 * resources/js/Pages/Welcome.vue used to inject client-side via onMounted -
 * both read the SAME translation keys (resources/lang/{locale}/app.php), so
 * there is no content duplication, only the key lists below.
 */
class HomepageStructuredData
{
    /**
     * FAQ item keys, mirroring the `faqs` array in Welcome.vue.
     * Keep in sync when FAQ items are added or removed.
     */
    private const FAQ_KEYS = [
        'faia', 'tva_luxembourg', 'peppol_obligatoire', 'e_facturation',
        'meilleure_solution', 'cout_logiciel', 'compliant',
        'client_belge', 'credit_notes', 'fiduciaire',
        'hosting', 'controle_aed', 'migration', 'free_trial',
    ];

    /**
     * @return array<int, array<string, mixed>> The three JSON-LD documents.
     */
    public static function build(string $appUrl, string $locale): array
    {
        $t = fn (string $key) => Lang::get("app.landing.$key", [], $locale);

        $organization = [
            '@context' => 'https://schema.org',
            '@type' => ['Organization', 'LocalBusiness'],
            '@id' => "$appUrl/#organization",
            'name' => 'faktur.lu',
            'alternateName' => ['Faktur.lu', 'Faktur'],
            'identifier' => [
                '@type' => 'PropertyValue',
                'propertyID' => 'Wikidata',
                'value' => 'Q139674760',
            ],
            'url' => $appUrl,
            'logo' => "$appUrl/images/logo.png",
            'image' => "$appUrl/images/og-default.png",
            'description' => $t('schema_software_description'),
            'foundingDate' => '2026',
            'slogan' => $t('schema_slogan'),
            // Identité légale, telle qu'elle est publiée sur les mentions
            // légales. Un moteur — ou un modèle — qui évalue la crédibilité
            // d'un éditeur cherche une entité vérifiable : une raison sociale,
            // un registre, un numéro de TVA. Le concurrent direct les affiche,
            // pas nous.
            'legalName' => 'Alexandre Beaudier',
            'vatID' => 'LU37176916',
            'taxID' => 'LU37176916',
            // La commune et le code postal étaient ceux de Luxembourg-Ville,
            // qui n'ont jamais été les nôtres. Une adresse qui contredit les
            // mentions légales est exactement ce qu'un moteur recoupe.
            'address' => [
                '@type' => 'PostalAddress',
                'addressLocality' => 'Dudelange',
                'addressRegion' => 'Luxembourg',
                'postalCode' => 'L-3502',
                'addressCountry' => 'LU',
            ],
            'geo' => [
                '@type' => 'GeoCoordinates',
                'latitude' => '49.4806',
                'longitude' => '6.0875',
            ],
            'areaServed' => [
                ['@type' => 'Country', 'name' => 'Luxembourg'],
                ['@type' => 'Country', 'name' => 'Belgium'],
                ['@type' => 'Country', 'name' => 'France'],
                ['@type' => 'Country', 'name' => 'Germany'],
            ],
            'knowsAbout' => [
                'Luxembourg VAT', 'FAIA reporting', 'Peppol e-invoicing', 'Factur-X',
                'ZUGFeRD', 'B2G e-invoicing Luxembourg', 'AED fiscal compliance', 'SAF-T Luxembourg',
            ],
            'knowsLanguage' => ['fr', 'de', 'en', 'lb', 'pt'],
            'priceRange' => '0-15 EUR/mois',
            'currenciesAccepted' => 'EUR',
            'paymentAccepted' => 'Credit Card, SEPA',
            'sameAs' => [
                'https://www.linkedin.com/company/faktur-lu/',
                'https://www.trustpilot.com/review/faktur.lu',
                'https://www.wikidata.org/wiki/Q139674760',
            ],
        ];

        $software = [
            '@context' => 'https://schema.org',
            '@type' => 'SoftwareApplication',
            '@id' => "$appUrl/#software",
            'name' => 'faktur.lu',
            'alternateName' => 'Faktur',
            'description' => $t('schema_software_description'),
            'applicationCategory' => 'BusinessApplication',
            'applicationSubCategory' => 'Invoicing & Billing Software',
            'operatingSystem' => 'Web, iOS, Android',
            'url' => $appUrl,
            'datePublished' => '2026-01-01',
            'softwareVersion' => '1.0',
            'availableLanguage' => ['fr', 'de', 'en', 'lb', 'pt'],
            'inLanguage' => ['fr-LU', 'de-LU', 'en-GB', 'lb-LU', 'pt-PT'],
            'publisher' => ['@id' => "$appUrl/#organization"],
            'offers' => [
                [
                    '@type' => 'Offer',
                    'name' => $t('schema_offer_free_name'),
                    'price' => '0',
                    'priceCurrency' => 'EUR',
                    'description' => $t('schema_offer_free_description'),
                ],
                [
                    '@type' => 'Offer',
                    'name' => $t('schema_offer_essential_name'),
                    'price' => '5',
                    'priceCurrency' => 'EUR',
                    'description' => $t('schema_offer_essential_description'),
                ],
                [
                    '@type' => 'Offer',
                    'name' => $t('schema_offer_pro_name'),
                    'price' => '15',
                    'priceCurrency' => 'EUR',
                    'description' => $t('schema_offer_pro_description'),
                ],
            ],
            'featureList' => [
                $t('schema_feature_invoices'),
                $t('schema_feature_faia'),
                $t('schema_feature_vat'),
                $t('schema_feature_peppol'),
                $t('schema_feature_facturx'),
                $t('schema_feature_quotes'),
                $t('schema_feature_time'),
                $t('schema_feature_hr'),
                $t('schema_feature_crm'),
                $t('schema_feature_accountant_portal'),
                $t('schema_feature_languages'),
            ],
            'keywords' => 'facturation Luxembourg, FAIA, Peppol, TVA Luxembourg, e-facturation, Factur-X, logiciel facturation PME, logiciel facturation indépendant',
            'audience' => [
                '@type' => 'BusinessAudience',
                'audienceType' => 'Sole traders, SMEs, Freelancers, Accountants',
                'geographicArea' => ['@type' => 'Country', 'name' => 'Luxembourg'],
            ],
        ];

        $faq = [
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'speakable' => [
                '@type' => 'SpeakableSpecification',
                'cssSelector' => ['#faq'],
            ],
            'mainEntity' => array_map(fn (string $key) => [
                '@type' => 'Question',
                'name' => $t("faq.items.$key.question"),
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => $t("faq.items.$key.answer"),
                ],
            ], self::FAQ_KEYS),
        ];

        return [$organization, $software, $faq];
    }
}
