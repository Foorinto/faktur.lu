<?php

namespace App\Services\CompanyLookup;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FranceProvider implements CompanyLookupProviderInterface
{
    public function searchByName(string $query, int $limit = 10): array
    {
        $cacheKey = config('company-lookup.cache_prefix') . ':fr:name:' . md5($query . $limit);

        return Cache::remember($cacheKey, config('company-lookup.france.cache_ttl', 3600), function () use ($query, $limit) {
            try {
                $response = Http::timeout(config('company-lookup.france.timeout', 10))
                    ->get(config('company-lookup.france.url'), [
                        'q' => $query,
                        'page' => 1,
                        'per_page' => $limit,
                    ]);

                if (! $response->successful()) {
                    Log::warning('[CompanyLookup] France API error', ['status' => $response->status()]);

                    return [];
                }

                return $this->parseResults($response->json('results', []));
            } catch (\Exception $e) {
                Log::error('[CompanyLookup] France API exception', ['message' => $e->getMessage()]);

                return [];
            }
        });
    }

    public function searchByVatNumber(string $vatNumber): ?CompanyLookupResult
    {
        // Extract SIREN from French VAT: FR + 2-char key + 9-digit SIREN
        $clean = preg_replace('/\s+/', '', $vatNumber);
        if (str_starts_with(strtoupper($clean), 'FR')) {
            $clean = substr($clean, 2);
        }
        $siren = substr($clean, 2, 9);

        if (strlen($siren) !== 9 || ! ctype_digit($siren)) {
            return null;
        }

        $results = $this->searchByName($siren, 1);

        return $results[0] ?? null;
    }

    public function supportsNameSearch(): bool
    {
        return true;
    }

    public function getCountryCode(): string
    {
        return 'FR';
    }

    /**
     * @return CompanyLookupResult[]
     */
    private function parseResults(array $results): array
    {
        return array_values(array_filter(array_map(function (array $item) {
            $name = $item['nom_complet'] ?? $item['nom_raison_sociale'] ?? '';
            if (empty($name)) {
                return null;
            }

            $siege = $item['siege'] ?? [];

            // Build street address from available fields
            $address = $siege['adresse'] ?? null;
            if (! $address && isset($siege['numero_voie'], $siege['type_voie'], $siege['libelle_voie'])) {
                $address = trim("{$siege['numero_voie']} {$siege['type_voie']} {$siege['libelle_voie']}");
            }

            return new CompanyLookupResult(
                name: $name,
                address: $address,
                postalCode: $siege['code_postal'] ?? null,
                city: $siege['libelle_commune'] ?? $siege['commune'] ?? null,
                countryCode: 'FR',
                vatNumber: $item['numero_tva_intra'] ?? null,
                registrationNumber: $item['siren'] ?? null,
            );
        }, $results)));
    }
}
