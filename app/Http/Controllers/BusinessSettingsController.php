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
        $countryCode = $settings?->country_code ?? 'LU';
        $countryConfig = config("countries.{$countryCode}", config('countries.LU'));

        // Build VAT regimes with country-specific threshold
        $franchiseThreshold = $settings?->getFranchiseThreshold() ?? $countryConfig['franchise']['threshold'] ?? 50000;
        $vatRegimes = [
            [
                'value' => 'franchise',
                'label' => "Franchise (< " . number_format($franchiseThreshold, 0, ',', ' ') . " €/an)",
                'description' => 'Exonéré de TVA',
            ],
            [
                'value' => 'assujetti',
                'label' => 'Assujetti',
                'description' => 'TVA collectée et déductible',
            ],
        ];

        $currentYear = now()->year;
        $numberingEditability = $settings?->numberingEditability($currentYear) ?? [
            BusinessSettings::NUMBERING_TYPE_INVOICE => true,
            BusinessSettings::NUMBERING_TYPE_CREDIT_NOTE => true,
            BusinessSettings::NUMBERING_TYPE_QUOTE => true,
        ];
        $numberingFinalizedCounts = $settings ? [
            BusinessSettings::NUMBERING_TYPE_INVOICE => $settings->finalizedCountFor(BusinessSettings::NUMBERING_TYPE_INVOICE, $currentYear),
            BusinessSettings::NUMBERING_TYPE_CREDIT_NOTE => $settings->finalizedCountFor(BusinessSettings::NUMBERING_TYPE_CREDIT_NOTE, $currentYear),
            BusinessSettings::NUMBERING_TYPE_QUOTE => $settings->finalizedCountFor(BusinessSettings::NUMBERING_TYPE_QUOTE, $currentYear),
        ] : [
            BusinessSettings::NUMBERING_TYPE_INVOICE => 0,
            BusinessSettings::NUMBERING_TYPE_CREDIT_NOTE => 0,
            BusinessSettings::NUMBERING_TYPE_QUOTE => 0,
        ];

        return Inertia::render('Settings/Business', [
            'settings' => $settings ? array_merge($settings->toArray(), [
                'logo_url' => $settings->logo_url,
                'payment_qrcode_url' => $settings->payment_qrcode_url,
                'franchise_threshold' => $franchiseThreshold,
            ]) : [
                'country_code' => 'LU',
                'franchise_threshold' => 50000,
                'number_format' => \App\Services\DocumentNumberFormatter::DEFAULT_TEMPLATE,
                'invoice_prefix' => \App\Actions\GenerateInvoiceNumberAction::DEFAULT_PREFIX_INVOICE,
                'credit_note_prefix' => \App\Actions\GenerateInvoiceNumberAction::DEFAULT_PREFIX_CREDIT_NOTE,
                'quote_prefix' => \App\Actions\GenerateQuoteNumberAction::DEFAULT_PREFIX,
                'number_padding' => 3,
            ],
            'countries' => BusinessSettings::getSupportedCountries(),
            'countriesConfig' => config('countries'),
            'activityTypes' => BusinessSettings::getActivityTypeOptions(),
            'vatRegimes' => $vatRegimes,
            'vatMentionOptions' => BusinessSettings::getVatMentionOptions(),
            'pdfColorPresets' => BusinessSettings::getPdfColorPresets(),
            'defaultPdfColor' => BusinessSettings::DEFAULT_PDF_COLOR,
            'peppolSchemes' => BusinessSettings::getPeppolSchemeOptions(),
            'numbering' => [
                'editability' => $numberingEditability,
                'finalized_counts' => $numberingFinalizedCounts,
                'current_year' => $currentYear,
                'placeholders' => \App\Services\DocumentNumberFormatter::PLACEHOLDERS,
                'default_template' => \App\Services\DocumentNumberFormatter::DEFAULT_TEMPLATE,
            ],
        ]);
    }

    /**
     * Update or create the business settings.
     */
    public function update(UpdateBusinessSettingsRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $settings = BusinessSettings::getInstance();

        if ($settings) {
            $settings->update($validated);
        } else {
            BusinessSettings::create($validated);
        }

        return back()->with('success', __('app.business_flash.settings_saved'));
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
            return back()->with('error', __('app.business_flash.error_settings_missing'));
        }

        // Delete old logo if exists
        if ($settings->logo_path) {
            Storage::disk('public')->delete($settings->logo_path);
        }

        // Store new logo
        $path = $request->file('logo')->store('logos', 'public');

        $settings->update(['logo_path' => $path]);

        return back()->with('success', __('app.business_flash.logo_updated'));
    }

    /**
     * Upload a payment QR code image (Payconiq, PayPal, etc.).
     */
    public function uploadPaymentQrcode(Request $request): RedirectResponse
    {
        $request->validate([
            'payment_qrcode' => ['required', 'image', 'mimes:png,jpg,jpeg,webp', 'max:1024'],
        ]);

        $settings = BusinessSettings::getInstance();

        if (!$settings) {
            return back()->with('error', __('app.business_flash.error_settings_missing'));
        }

        // Delete old QR code if exists
        if ($settings->payment_qrcode_path) {
            Storage::disk('public')->delete($settings->payment_qrcode_path);
        }

        $path = $request->file('payment_qrcode')->store('payment-qrcodes', 'public');

        $settings->update(['payment_qrcode_path' => $path]);

        return back()->with('success', __('app.business_flash.qrcode_updated'));
    }

    /**
     * Delete the payment QR code image.
     */
    public function deletePaymentQrcode(): RedirectResponse
    {
        $settings = BusinessSettings::getInstance();

        if (!$settings || !$settings->payment_qrcode_path) {
            return back()->with('error', __('app.business_flash.error_no_qrcode'));
        }

        Storage::disk('public')->delete($settings->payment_qrcode_path);

        $settings->update(['payment_qrcode_path' => null]);

        return back()->with('success', __('app.business_flash.qrcode_deleted'));
    }

    /**
     * Delete the logo.
     */
    public function deleteLogo(): RedirectResponse
    {
        $settings = BusinessSettings::getInstance();

        if (!$settings || !$settings->logo_path) {
            return back()->with('error', __('app.business_flash.error_no_logo'));
        }

        // Delete file
        Storage::disk('public')->delete($settings->logo_path);

        // Clear path in database
        $settings->update(['logo_path' => null]);

        return back()->with('success', __('app.business_flash.logo_deleted'));
    }
}
