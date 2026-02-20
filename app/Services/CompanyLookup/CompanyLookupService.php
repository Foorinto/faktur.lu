<?php

namespace App\Services\CompanyLookup;

class CompanyLookupService
{
    /**
     * Search companies by name for a given country.
     *
     * @return CompanyLookupResult[]
     */
    public function searchByName(string $query, string $countryCode, int $limit = 10): array
    {
        $provider = $this->getProvider($countryCode);

        if (! $provider->supportsNameSearch()) {
            return [];
        }

        return $provider->searchByName($query, $limit);
    }

    /**
     * Search company by VAT number.
     */
    public function searchByVatNumber(string $vatNumber, string $countryCode): ?CompanyLookupResult
    {
        return $this->getProvider($countryCode)->searchByVatNumber($vatNumber);
    }

    /**
     * Validate a VAT number via VIES.
     */
    public function validateVat(string $vatNumber, string $countryCode): ViesValidationResult
    {
        return (new ViesProvider($countryCode))->validateVatNumber($vatNumber);
    }

    /**
     * Check whether a country supports name-based search.
     */
    public function supportsNameSearch(string $countryCode): bool
    {
        return $this->getProvider($countryCode)->supportsNameSearch();
    }

    private function getProvider(string $countryCode): CompanyLookupProviderInterface
    {
        return match (strtoupper($countryCode)) {
            'FR' => new FranceProvider,
            default => new ViesProvider($countryCode),
        };
    }
}
