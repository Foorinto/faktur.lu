<?php

namespace App\Services;

use App\Models\BusinessSettings;
use App\Models\Client;

class VatCalculationService
{
    /**
     * EU member state country codes (ISO 3166-1 alpha-2).
     */
    public const EU_COUNTRIES = [
        'AT', // Austria
        'BE', // Belgium
        'BG', // Bulgaria
        'CY', // Cyprus
        'CZ', // Czech Republic
        'DE', // Germany
        'DK', // Denmark
        'EE', // Estonia
        'ES', // Spain
        'FI', // Finland
        'FR', // France
        'GR', // Greece
        'HR', // Croatia
        'HU', // Hungary
        'IE', // Ireland
        'IT', // Italy
        'LT', // Lithuania
        'LU', // Luxembourg
        'LV', // Latvia
        'MT', // Malta
        'NL', // Netherlands
        'PL', // Poland
        'PT', // Portugal
        'RO', // Romania
        'SE', // Sweden
        'SI', // Slovenia
        'SK', // Slovakia
    ];

    /**
     * VAT scenarios with their configurations.
     * Note: 'rate' values are defaults and will be replaced dynamically
     * based on the seller's country configuration.
     */
    public const VAT_SCENARIOS = [
        'B2B_INTRA_EU' => [
            'label' => 'B2B Intracommunautaire',
            'description' => 'Client professionnel UE (hors pays du vendeur)',
            'rate' => 0,
            'mention' => 'reverse_charge',
            'color' => 'blue',
        ],
        'B2B_DOMESTIC' => [
            'label' => 'B2B National',
            'description' => 'Client professionnel dans le pays du vendeur',
            'rate' => null, // Will be set dynamically
            'mention' => null,
            'color' => 'green',
        ],
        'B2C_DOMESTIC' => [
            'label' => 'B2C National',
            'description' => 'Client particulier dans le pays du vendeur',
            'rate' => null, // Will be set dynamically
            'mention' => null,
            'color' => 'green',
        ],
        // Legacy aliases for backward compatibility
        'B2B_LU' => [
            'label' => 'B2B Luxembourg',
            'description' => 'Client professionnel luxembourgeois',
            'rate' => 17,
            'mention' => null,
            'color' => 'green',
            'alias' => 'B2B_DOMESTIC',
        ],
        'B2C_LU' => [
            'label' => 'B2C Luxembourg',
            'description' => 'Client particulier luxembourgeois',
            'rate' => 17,
            'mention' => null,
            'color' => 'green',
            'alias' => 'B2C_DOMESTIC',
        ],
        'FRANCHISE' => [
            'label' => 'Franchise de TVA',
            'description' => 'Exonéré de TVA (régime franchise)',
            'rate' => 0,
            'mention' => 'franchise',
            'color' => 'gray',
        ],
        'EXPORT' => [
            'label' => 'Export hors UE',
            'description' => 'Client hors Union Européenne',
            'rate' => 0,
            'mention' => 'export',
            'color' => 'purple',
        ],
    ];

    /**
     * Default VAT rate (Luxembourg standard).
     * @deprecated Use getStandardVatRate() instead for multi-country support.
     */
    public const STANDARD_VAT_RATE = 17;

    /**
     * Get the standard VAT rate for a business's country.
     */
    public function getStandardVatRate(?BusinessSettings $settings = null): float
    {
        $settings = $settings ?? BusinessSettings::getInstance();

        if ($settings) {
            return $settings->getDefaultVatRate();
        }

        return config('billing.default_vat_rate', 17.0);
    }

    /**
     * Get all available VAT rates for a business's country.
     */
    public function getVatRatesForBusiness(?BusinessSettings $settings = null): array
    {
        $settings = $settings ?? BusinessSettings::getInstance();

        if ($settings) {
            return $settings->getVatRates();
        }

        // Fallback to Luxembourg rates
        return config('countries.LU.vat_rates', []);
    }

    /**
     * Determine the VAT scenario for a client.
     */
    public function determineScenario(Client $client, ?BusinessSettings $settings = null): array
    {
        $settings = $settings ?? BusinessSettings::getInstance();
        $sellerCountry = $settings?->country_code ?? 'LU';

        // If seller is VAT exempt (franchise regime), always return FRANCHISE scenario
        if ($settings && $settings->isVatExempt()) {
            return $this->getScenarioWithDetails('FRANCHISE', $settings);
        }

        $clientCountry = $client->country_code ?? $sellerCountry;
        $isB2B = $client->type === 'b2b';
        $hasVatNumber = !empty($client->vat_number);

        // Domestic client (same country as seller)
        if ($clientCountry === $sellerCountry) {
            return $this->getScenarioWithDetails($isB2B ? 'B2B_DOMESTIC' : 'B2C_DOMESTIC', $settings);
        }

        // EU client (not same country as seller)
        if ($this->isEuCountry($clientCountry)) {
            // B2B with VAT number = reverse charge
            if ($isB2B && $hasVatNumber) {
                return $this->getScenarioWithDetails('B2B_INTRA_EU', $settings);
            }

            // B2B without VAT number or B2C = apply domestic VAT
            return $this->getScenarioWithDetails($isB2B ? 'B2B_DOMESTIC' : 'B2C_DOMESTIC', $settings);
        }

        // Non-EU client = export
        return $this->getScenarioWithDetails('EXPORT', $settings);
    }

    /**
     * Get the VAT scenario based on client data array (for use with snapshots).
     */
    public function determineScenarioFromData(array $clientData, ?BusinessSettings $settings = null): array
    {
        $settings = $settings ?? BusinessSettings::getInstance();
        $sellerCountry = $settings?->country_code ?? 'LU';

        // If seller is VAT exempt (franchise regime), always return FRANCHISE scenario
        if ($settings && $settings->isVatExempt()) {
            return $this->getScenarioWithDetails('FRANCHISE', $settings);
        }

        $clientCountry = $clientData['country_code'] ?? $sellerCountry;
        $isB2B = ($clientData['type'] ?? 'b2b') === 'b2b';
        $hasVatNumber = !empty($clientData['vat_number']);

        // Domestic client (same country as seller)
        if ($clientCountry === $sellerCountry) {
            return $this->getScenarioWithDetails($isB2B ? 'B2B_DOMESTIC' : 'B2C_DOMESTIC', $settings);
        }

        // EU client (not same country as seller)
        if ($this->isEuCountry($clientCountry)) {
            // B2B with VAT number = reverse charge
            if ($isB2B && $hasVatNumber) {
                return $this->getScenarioWithDetails('B2B_INTRA_EU', $settings);
            }

            // B2B without VAT number or B2C = apply domestic VAT
            return $this->getScenarioWithDetails($isB2B ? 'B2B_DOMESTIC' : 'B2C_DOMESTIC', $settings);
        }

        // Non-EU client = export
        return $this->getScenarioWithDetails('EXPORT', $settings);
    }

    /**
     * Check if a country code is in the EU.
     */
    public function isEuCountry(string $countryCode): bool
    {
        return in_array(strtoupper($countryCode), self::EU_COUNTRIES);
    }

    /**
     * Check if a country code is in the EU but not Luxembourg.
     */
    public function isIntraEuCountry(string $countryCode): bool
    {
        $code = strtoupper($countryCode);
        return $code !== 'LU' && $this->isEuCountry($code);
    }

    /**
     * Get scenario details with the key.
     * Dynamically sets the VAT rate based on the business's country.
     */
    public function getScenarioWithDetails(string $scenarioKey, ?BusinessSettings $settings = null): array
    {
        $scenario = self::VAT_SCENARIOS[$scenarioKey] ?? self::VAT_SCENARIOS['B2B_DOMESTIC'];

        // Get the standard VAT rate for the seller's country
        $standardRate = $this->getStandardVatRate($settings);

        // Set dynamic rate for domestic scenarios
        if (in_array($scenarioKey, ['B2B_DOMESTIC', 'B2C_DOMESTIC', 'B2B_LU', 'B2C_LU'])) {
            $scenario['rate'] = $standardRate;
        }

        // Update description with country name
        if ($settings && in_array($scenarioKey, ['B2B_DOMESTIC', 'B2C_DOMESTIC'])) {
            $countryConfig = $settings->getCountryConfig();
            $countryName = $countryConfig['name'] ?? 'Luxembourg';
            $scenario['label'] = str_replace('National', $countryName, $scenario['label']);
            $scenario['description'] = str_replace('dans le pays du vendeur', "au {$countryName}", $scenario['description']);
        }

        return array_merge(['key' => $scenarioKey], $scenario);
    }

    /**
     * Get the VAT mention text for a scenario.
     * Uses country-specific mention for franchise regime.
     */
    public function getVatMentionText(string $mentionKey, ?BusinessSettings $settings = null): ?string
    {
        if (!$mentionKey || $mentionKey === 'none') {
            return null;
        }

        // For franchise, use country-specific mention
        if ($mentionKey === 'franchise' && $settings) {
            return $settings->getFranchiseMention();
        }

        return BusinessSettings::VAT_MENTIONS[$mentionKey] ?? null;
    }

    /**
     * Get all VAT scenarios for display.
     */
    public static function getAllScenarios(?BusinessSettings $settings = null): array
    {
        $scenarios = [];
        $standardRate = $settings?->getDefaultVatRate() ?? 17.0;

        foreach (self::VAT_SCENARIOS as $key => $scenario) {
            // Skip legacy aliases
            if (isset($scenario['alias'])) {
                continue;
            }

            // Set dynamic rate for domestic scenarios
            if (in_array($key, ['B2B_DOMESTIC', 'B2C_DOMESTIC'])) {
                $scenario['rate'] = $standardRate;
            }

            $scenarios[] = array_merge(['key' => $key], $scenario);
        }

        return $scenarios;
    }

    /**
     * Get the list of EU countries with names.
     */
    public static function getEuCountriesWithNames(): array
    {
        $names = [
            'AT' => 'Autriche',
            'BE' => 'Belgique',
            'BG' => 'Bulgarie',
            'CY' => 'Chypre',
            'CZ' => 'Tchéquie',
            'DE' => 'Allemagne',
            'DK' => 'Danemark',
            'EE' => 'Estonie',
            'ES' => 'Espagne',
            'FI' => 'Finlande',
            'FR' => 'France',
            'GR' => 'Grèce',
            'HR' => 'Croatie',
            'HU' => 'Hongrie',
            'IE' => 'Irlande',
            'IT' => 'Italie',
            'LT' => 'Lituanie',
            'LU' => 'Luxembourg',
            'LV' => 'Lettonie',
            'MT' => 'Malte',
            'NL' => 'Pays-Bas',
            'PL' => 'Pologne',
            'PT' => 'Portugal',
            'RO' => 'Roumanie',
            'SE' => 'Suède',
            'SI' => 'Slovénie',
            'SK' => 'Slovaquie',
        ];

        $countries = [];
        foreach (self::EU_COUNTRIES as $code) {
            $countries[] = [
                'code' => $code,
                'name' => $names[$code] ?? $code,
            ];
        }

        return $countries;
    }

    /**
     * Validate a VAT number format for a given country.
     */
    public function validateVatNumber(string $vatNumber, ?string $countryCode = null): bool
    {
        // Remove spaces and convert to uppercase
        $vatNumber = strtoupper(preg_replace('/\s+/', '', $vatNumber));

        if (empty($vatNumber)) {
            return true; // Empty is valid (optional field)
        }

        // If country code provided, check it matches the VAT prefix
        if ($countryCode && strlen($vatNumber) >= 2) {
            $vatPrefix = substr($vatNumber, 0, 2);
            if ($vatPrefix !== strtoupper($countryCode)) {
                return false;
            }
        }

        // Try to use country-specific format from config
        if ($countryCode) {
            $countryConfig = config("countries.{$countryCode}");
            if ($countryConfig && isset($countryConfig['vat_number']['format'])) {
                return (bool) preg_match($countryConfig['vat_number']['format'], $vatNumber);
            }
        }

        // Basic format validation: 2 letters followed by alphanumeric
        return (bool) preg_match('/^[A-Z]{2}[A-Z0-9]{2,12}$/', $vatNumber);
    }

    /**
     * Get the VAT number example for a country.
     */
    public function getVatNumberExample(string $countryCode): string
    {
        $countryConfig = config("countries.{$countryCode}");

        return $countryConfig['vat_number']['example'] ?? 'LU12345678';
    }
}
