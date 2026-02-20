<?php

namespace App\Services\CompanyLookup;

interface CompanyLookupProviderInterface
{
    /**
     * Search companies by name query.
     *
     * @return CompanyLookupResult[]
     */
    public function searchByName(string $query, int $limit = 10): array;

    /**
     * Search company by VAT number.
     */
    public function searchByVatNumber(string $vatNumber): ?CompanyLookupResult;

    /**
     * Whether this provider supports search-by-name.
     */
    public function supportsNameSearch(): bool;

    /**
     * Get the country code this provider handles.
     */
    public function getCountryCode(): string;
}
