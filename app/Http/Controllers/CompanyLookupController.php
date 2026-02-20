<?php

namespace App\Http\Controllers;

use App\Services\CompanyLookup\CompanyLookupService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CompanyLookupController extends Controller
{
    public function __construct(
        private CompanyLookupService $lookupService,
    ) {}

    /**
     * Search companies by name.
     */
    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'query' => ['required', 'string', 'min:2', 'max:100'],
            'country_code' => ['required', 'string', 'size:2'],
        ]);

        $query = $request->input('query');
        $countryCode = strtoupper($request->input('country_code'));

        if (! $this->lookupService->supportsNameSearch($countryCode)) {
            return response()->json([
                'results' => [],
                'supports_name_search' => false,
            ]);
        }

        $results = $this->lookupService->searchByName($query, $countryCode);

        return response()->json([
            'results' => array_map(fn ($r) => $r->toArray(), $results),
            'supports_name_search' => true,
        ]);
    }

    /**
     * Validate a VAT number via VIES and return company info with parsed address.
     */
    public function validateVat(Request $request): JsonResponse
    {
        $request->validate([
            'vat_number' => ['required', 'string', 'min:4', 'max:20', 'regex:/^[A-Z]{2}[A-Z0-9]{2,18}$/'],
        ]);

        $vatNumber = strtoupper(preg_replace('/\s+/', '', $request->input('vat_number')));
        $countryCode = substr($vatNumber, 0, 2);

        $result = $this->lookupService->validateVat($vatNumber, $countryCode);
        $data = $result->toArray();

        // Also return parsed company data for auto-fill
        if ($result->valid) {
            $company = $this->lookupService->searchByVatNumber($vatNumber, $countryCode);
            if ($company) {
                $data['company'] = $company->toArray();
            }
        }

        return response()->json($data);
    }
}
