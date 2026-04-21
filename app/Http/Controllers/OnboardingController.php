<?php

namespace App\Http\Controllers;

use App\Models\BusinessSettings;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use Illuminate\Http\Request;
use Inertia\Inertia;

class OnboardingController extends Controller
{
    /**
     * Affiche le wizard d'onboarding.
     */
    public function show(Request $request)
    {
        $user = $request->user();
        $business = BusinessSettings::where('user_id', $user->id)->first();

        return Inertia::render('Onboarding/Wizard', [
            'currentStep' => $user->onboarding_step ?? 'company',
            'business' => $business,
        ]);
    }

    /**
     * Étape 1 : Sauvegarde les informations entreprise.
     */
    public function saveCompany(Request $request)
    {
        $data = $request->validate([
            'company_name' => 'required|string|max:255',
            'vat_number' => 'nullable|string|max:50',
            'matricule' => 'nullable|string|max:50',
            'iban' => 'nullable|string|max:50',
            'bic' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:255',
            'postal_code' => 'nullable|string|max:20',
            'city' => 'nullable|string|max:100',
            'country_code' => 'nullable|string|size:2',
        ]);

        BusinessSettings::updateOrCreate(
            ['user_id' => $request->user()->id],
            array_merge($data, [
                'country_code' => $data['country_code'] ?? 'LU',
                'legal_name' => $data['company_name'],
                'matricule' => $data['matricule'] ?? '',
                'iban' => $data['iban'] ?? '',
                'bic' => $data['bic'] ?? '',
                'email' => $request->user()->email,
                'address' => $data['address'] ?? '',
                'postal_code' => $data['postal_code'] ?? '',
                'city' => $data['city'] ?? '',
            ])
        );

        $request->user()->update(['onboarding_step' => 'branding']);

        return response()->json(['success' => true, 'next_step' => 'branding']);
    }

    /**
     * Étape 2 : Sauvegarde le branding (logo, couleur).
     */
    public function saveBranding(Request $request)
    {
        $request->validate([
            'logo' => 'nullable|file|mimes:jpg,jpeg,png,svg,webp|max:2048',
            'default_pdf_color' => 'nullable|string|max:50',
        ], [
            'logo.uploaded' => 'Le fichier est trop volumineux. Taille maximum : 2 Mo.',
            'logo.max' => 'Le fichier est trop volumineux. Taille maximum : 2 Mo.',
            'logo.mimes' => 'Format non supporté. Utilisez JPG, PNG, SVG ou WEBP.',
            'logo.file' => 'Le fichier uploadé est invalide.',
        ]);

        $business = BusinessSettings::firstOrCreate(['user_id' => $request->user()->id]);

        if ($request->hasFile('logo')) {
            $path = $request->file('logo')->store('logos/' . $request->user()->id, 'public');
            $business->logo_path = $path;
        }

        if ($request->filled('default_pdf_color')) {
            $business->default_pdf_color = $request->input('default_pdf_color');
        }

        $business->save();
        $request->user()->update(['onboarding_step' => 'client']);

        return response()->json(['success' => true, 'next_step' => 'client']);
    }

    /**
     * Étape 3 : Sauvegarde le premier client.
     */
    public function saveClient(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string|max:255',
            'postal_code' => 'nullable|string|max:20',
            'city' => 'nullable|string|max:100',
            'country_code' => 'nullable|string|size:2',
            'vat_number' => 'nullable|string|max:50',
        ]);

        $client = Client::create(array_merge($data, [
            'user_id' => $request->user()->id,
            'type' => 'b2b',
            'status' => 'active',
            'currency' => 'EUR',
            'country_code' => $data['country_code'] ?? 'LU',
        ]));

        $request->user()->update(['onboarding_step' => 'invoice']);

        return response()->json([
            'success' => true,
            'next_step' => 'invoice',
            'client_id' => $client->id,
        ]);
    }

    /**
     * Étape 4 : Crée la première facture (brouillon).
     */
    public function saveInvoice(Request $request)
    {
        $data = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'title' => 'required|string|max:255',
            'unit_price' => 'required|numeric|min:0',
            'vat_rate' => 'nullable|numeric|min:0|max:100',
        ]);

        $client = Client::where('user_id', $request->user()->id)->findOrFail($data['client_id']);

        $invoice = Invoice::create([
            'client_id' => $client->id,
            'currency' => $client->currency ?? 'EUR',
            'issued_at' => now()->toDateString(),
            'due_at' => now()->addDays(30)->toDateString(),
            'status' => Invoice::STATUS_DRAFT,
        ]);

        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'title' => $data['title'],
            'quantity' => 1,
            'unit_price' => $data['unit_price'],
            'vat_rate' => $data['vat_rate'] ?? 17,
            'sort_order' => 0,
        ]);

        $request->user()->update([
            'onboarding_step' => 'success',
            'onboarding_completed_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'next_step' => 'success',
            'invoice_id' => $invoice->id,
        ]);
    }

    /**
     * Skip définitivement l'onboarding.
     */
    public function skip(Request $request)
    {
        $request->user()->update([
            'onboarding_skipped' => true,
            'onboarding_completed_at' => now(),
        ]);

        return response()->json(['success' => true]);
    }

    /**
     * Marque l'onboarding comme terminé.
     */
    public function complete(Request $request)
    {
        $request->user()->update([
            'onboarding_completed_at' => now(),
        ]);

        return response()->json(['success' => true]);
    }

    /**
     * Masque la checklist du dashboard.
     */
    public function dismissChecklist(Request $request)
    {
        $request->user()->update([
            'onboarding_checklist_dismissed' => true,
        ]);

        return response()->json(['success' => true]);
    }
}
