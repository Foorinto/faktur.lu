<?php

namespace App\Http\Controllers;

use App\Http\Requests\Api\V1\UpdateBusinessSettingsRequest;
use App\Models\BusinessSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class BusinessSettingsController extends Controller
{
    /**
     * Display the business settings form.
     */
    public function edit(): Response
    {
        $settings = BusinessSettings::getInstance();

        return Inertia::render('Settings/Business', [
            'settings' => $settings ? array_merge($settings->toArray(), [
                'logo_url' => $settings->logo_url,
            ]) : null,
            'vatRegimes' => [
                ['value' => 'franchise', 'label' => 'Franchise (< 35 000 €/an)', 'description' => 'Exonéré de TVA'],
                ['value' => 'assujetti', 'label' => 'Assujetti', 'description' => 'TVA collectée et déductible'],
            ],
            'vatMentionOptions' => BusinessSettings::getVatMentionOptions(),
            'pdfColorPresets' => BusinessSettings::getPdfColorPresets(),
            'defaultPdfColor' => BusinessSettings::DEFAULT_PDF_COLOR,
            'peppolSchemes' => BusinessSettings::getPeppolSchemeOptions(),
        ]);
    }

    /**
     * Update or create the business settings.
     */
    public function update(UpdateBusinessSettingsRequest $request): RedirectResponse
    {
        $settings = BusinessSettings::getInstance();

        if ($settings) {
            $settings->update($request->validated());
        } else {
            BusinessSettings::create($request->validated());
        }

        return back()->with('success', 'Paramètres enregistrés avec succès.');
    }

    /**
     * Upload a new logo.
     */
    public function uploadLogo(Request $request): RedirectResponse
    {
        $request->validate([
            'logo' => ['required', 'image', 'mimes:png,jpg,jpeg,svg,webp', 'max:2048'],
        ], [
            'logo.required' => 'Veuillez sélectionner un fichier.',
            'logo.image' => 'Le fichier doit être une image.',
            'logo.mimes' => 'Le logo doit être au format PNG, JPG, SVG ou WebP.',
            'logo.max' => 'Le logo ne doit pas dépasser 2 Mo.',
        ]);

        $settings = BusinessSettings::getInstance();

        if (!$settings) {
            return back()->with('error', 'Veuillez d\'abord configurer les paramètres de l\'entreprise.');
        }

        // Delete old logo if exists
        if ($settings->logo_path) {
            Storage::disk('public')->delete($settings->logo_path);
        }

        // Store new logo
        $path = $request->file('logo')->store('logos', 'public');

        $settings->update(['logo_path' => $path]);

        return back()->with('success', 'Logo mis à jour avec succès.');
    }

    /**
     * Delete the logo.
     */
    public function deleteLogo(): RedirectResponse
    {
        $settings = BusinessSettings::getInstance();

        if (!$settings || !$settings->logo_path) {
            return back()->with('error', 'Aucun logo à supprimer.');
        }

        // Delete file
        Storage::disk('public')->delete($settings->logo_path);

        // Clear path in database
        $settings->update(['logo_path' => null]);

        return back()->with('success', 'Logo supprimé.');
    }
}
