<?php

namespace App\Http\Controllers;

use App\Models\AccountingSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AccountingSettingsController extends Controller
{
    /**
     * Get the current accounting settings.
     */
    public function edit(Request $request): JsonResponse
    {
        $settings = AccountingSetting::getForUser($request->user());

        return response()->json($settings);
    }

    /**
     * Update accounting settings.
     */
    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'sales_account' => ['required', 'string', 'max:20'],
            'vat_collected_accounts' => ['required', 'array'],
            'vat_collected_accounts.*' => ['required', 'string', 'max:20'],
            'clients_account' => ['required', 'string', 'max:20'],
            'bank_account' => ['required', 'string', 'max:20'],
            'sales_journal' => ['required', 'string', 'max:10'],
            'client_prefix' => ['required', 'string', 'max:10'],
        ]);

        $settings = AccountingSetting::getForUser($request->user());
        $settings->update($validated);

        return response()->json([
            'message' => 'Paramètres comptables mis à jour.',
            'settings' => $settings->fresh(),
        ]);
    }
}
