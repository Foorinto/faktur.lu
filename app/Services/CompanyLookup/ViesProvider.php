<?php

namespace App\Services\CompanyLookup;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ViesProvider implements CompanyLookupProviderInterface
{
    public function __construct(
        private string $countryCode,
    ) {
        $this->countryCode = strtoupper($countryCode);
    }

    public function searchByName(string $query, int $limit = 10): array
    {
        return [];
    }

    public function searchByVatNumber(string $vatNumber): ?CompanyLookupResult
    {
        $validation = $this->validateVatNumber($vatNumber);

        if (! $validation->valid || ! $validation->name) {
            return null;
        }

        $addressParts = $this->parseViesAddress($validation->address);

        return new CompanyLookupResult(
            name: $validation->name,
            address: $addressParts['address'] ?? null,
            postalCode: $addressParts['postal_code'] ?? null,
            city: $addressParts['city'] ?? null,
            countryCode: $this->countryCode,
            vatNumber: $validation->vatNumber,
        );
    }

    public function validateVatNumber(string $rawVatNumber): ViesValidationResult
    {
        $vatNumber = preg_replace('/\s+/', '', $rawVatNumber);
        if (str_starts_with(strtoupper($vatNumber), $this->countryCode)) {
            $vatNumber = substr($vatNumber, strlen($this->countryCode));
        }

        $cacheKey = config('company-lookup.cache_prefix') . ':vies:' . $this->countryCode . ':' . $vatNumber;

        return Cache::remember($cacheKey, config('company-lookup.vies.cache_ttl', 86400), function () use ($vatNumber) {
            try {
                $response = Http::timeout(config('company-lookup.vies.timeout', 10))
                    ->post(config('company-lookup.vies.url'), [
                        'countryCode' => $this->countryCode,
                        'vatNumber' => $vatNumber,
                    ]);

                if (! $response->successful()) {
                    Log::warning('[CompanyLookup] VIES API error', [
                        'status' => $response->status(),
                        'country' => $this->countryCode,
                    ]);

                    return ViesValidationResult::invalid();
                }

                $data = $response->json();

                return new ViesValidationResult(
                    valid: $data['valid'] ?? false,
                    name: $data['name'] !== '---' ? ($data['name'] ?? null) : null,
                    address: $data['address'] !== '---' ? ($data['address'] ?? null) : null,
                    countryCode: $this->countryCode,
                    vatNumber: $this->countryCode . $vatNumber,
                    requestDate: $data['requestDate'] ?? null,
                );
            } catch (\Exception $e) {
                Log::error('[CompanyLookup] VIES API exception', [
                    'message' => $e->getMessage(),
                    'country' => $this->countryCode,
                ]);

                return ViesValidationResult::invalid();
            }
        });
    }

    public function supportsNameSearch(): bool
    {
        return false;
    }

    public function getCountryCode(): string
    {
        return $this->countryCode;
    }

    /**
     * Parse a VIES address string into structured components.
     * VIES returns addresses like "RUE EXAMPLE 10\n1234 CITY".
     */
    private function parseViesAddress(?string $address): array
    {
        if (! $address || $address === '---') {
            return [];
        }

        $lines = array_filter(array_map('trim', preg_split('/[\n\r]+/', $address)));

        if (count($lines) === 0) {
            return ['address' => $address];
        }

        // Regex for postal codes: "75001", "L-1855", "B-1000", "D-12345"
        $postalRegex = '/^([A-Z]?-?\d{4,6})\s+(.+)$/';

        if (count($lines) === 1) {
            if (preg_match('/^(.+?)\s+([A-Z]?-?\d{4,6})\s+(.+)$/', $lines[0], $matches)) {
                return [
                    'address' => trim($matches[1]),
                    'postal_code' => $matches[2],
                    'city' => trim($matches[3]),
                ];
            }

            return ['address' => $lines[0]];
        }

        // Multi-line: first line(s) = street, last line = postal + city
        $lastLine = array_pop($lines);
        $streetAddress = implode(', ', $lines);

        if (preg_match($postalRegex, $lastLine, $matches)) {
            return [
                'address' => $streetAddress,
                'postal_code' => $matches[1],
                'city' => trim($matches[2]),
            ];
        }

        return [
            'address' => $streetAddress,
            'city' => $lastLine,
        ];
    }
}
