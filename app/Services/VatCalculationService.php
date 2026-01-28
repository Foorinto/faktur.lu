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
     */
    public const VAT_SCENARIOS = [
        'B2B_INTRA_EU' => [
            'label' => 'B2B Intracommunautaire',
            'description' => 'Client professionnel UE (hors Luxembourg)',
            'rate' => 0,
            'mention' => 'reverse_charge',
            'color' => 'blue',
        ],
        'B2B_LU' => [
            'label' => 'B2B Luxembourg',
            'description' => 'Client professionnel luxembourgeois',
            'rate' => 17,
            'mention' => null,
            'color' => 'green',
        ],
        'B2C_LU' => [
            'label' => 'B2C Luxembourg',
            'description' => 'Client particulier luxembourgeois',
            'rate' => 17,
            'mention' => null,
            'color' => 'green',
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
     * Standard VAT rate for Luxembourg.
     */
    public const STANDARD_VAT_RATE = 17;

    /**
     * Determine the VAT scenario for a client.
     */
    public function determineScenario(Client $client, ?BusinessSettings $settings = null): array
    {
        $settings = $settings ?? BusinessSettings::getInstance();

        // If seller is VAT exempt (franchise regime), always return FRANCHISE scenario
        if ($settings && $settings->isVatExempt()) {
            return $this->getScenarioWithDetails('FRANCHISE');
        }

        $countryCode = $client->country_code ?? 'LU';
        $isB2B = $client->type === 'b2b';
        $hasVatNumber = !empty($client->vat_number);

        // Luxembourg client
        if ($countryCode === 'LU') {
            return $this->getScenarioWithDetails($isB2B ? 'B2B_LU' : 'B2C_LU');
        }

        // EU client (not Luxembourg)
        if ($this->isEuCountry($countryCode)) {
            // B2B with VAT number = reverse charge
            if ($isB2B && $hasVatNumber) {
                return $this->getScenarioWithDetails('B2B_INTRA_EU');
            }

            // B2B without VAT number or B2C = apply Luxembourg VAT
            return $this->getScenarioWithDetails($isB2B ? 'B2B_LU' : 'B2C_LU');
        }

        // Non-EU client = export
        return $this->getScenarioWithDetails('EXPORT');
    }

    /**
     * Get the VAT scenario based on client data array (for use with snapshots).
     */
    public function determineScenarioFromData(array $clientData, ?BusinessSettings $settings = null): array
    {
        $settings = $settings ?? BusinessSettings::getInstance();

        // If seller is VAT exempt (franchise regime), always return FRANCHISE scenario
        if ($settings && $settings->isVatExempt()) {
            return $this->getScenarioWithDetails('FRANCHISE');
        }

        $countryCode = $clientData['country_code'] ?? 'LU';
        $isB2B = ($clientData['type'] ?? 'b2b') === 'b2b';
        $hasVatNumber = !empty($clientData['vat_number']);

        // Luxembourg client
        if ($countryCode === 'LU') {
            return $this->getScenarioWithDetails($isB2B ? 'B2B_LU' : 'B2C_LU');
        }

        // EU client (not Luxembourg)
        if ($this->isEuCountry($countryCode)) {
            // B2B with VAT number = reverse charge
            if ($isB2B && $hasVatNumber) {
                return $this->getScenarioWithDetails('B2B_INTRA_EU');
            }

            // B2B without VAT number or B2C = apply Luxembourg VAT
            return $this->getScenarioWithDetails($isB2B ? 'B2B_LU' : 'B2C_LU');
        }

        // Non-EU client = export
        return $this->getScenarioWithDetails('EXPORT');
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
     */
    public function getScenarioWithDetails(string $scenarioKey): array
    {
        $scenario = self::VAT_SCENARIOS[$scenarioKey] ?? self::VAT_SCENARIOS['B2B_LU'];
        return array_merge(['key' => $scenarioKey], $scenario);
    }

    /**
     * Get the VAT mention text for a scenario.
     */
    public function getVatMentionText(string $mentionKey): ?string
    {
        if (!$mentionKey || $mentionKey === 'none') {
            return null;
        }

        return BusinessSettings::VAT_MENTIONS[$mentionKey] ?? null;
    }

    /**
     * Get all VAT scenarios for display.
     */
    public static function getAllScenarios(): array
    {
        $scenarios = [];
        foreach (self::VAT_SCENARIOS as $key => $scenario) {
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

        // Basic format validation: 2 letters followed by alphanumeric
        return (bool) preg_match('/^[A-Z]{2}[A-Z0-9]{2,12}$/', $vatNumber);
    }
}
