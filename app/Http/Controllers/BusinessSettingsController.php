<?php

namespace App\Http\Controllers;

use App\Actions\GenerateInvoiceNumberAction;
use App\Actions\GenerateQuoteNumberAction;
use App\Auth\Reauthenticator;
use App\Http\Requests\Api\V1\UpdateBusinessSettingsRequest;
use App\Models\BusinessSettings;
use App\Security\SecurityAlerter;
use App\Services\DocumentNumberFormatter;
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
                'label' => 'Franchise (< '.number_format($franchiseThreshold, 0, ',', ' ').' €/an)',
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
            // FEAT-133 : rappel des mentions obligatoires qui manquent encore. La
            // question n'est jamais préremplie : c'est à l'utilisateur de dire ce
            // qu'il est, pas à une devinette sur son nom ou son RCS.
            'legalMentionsMissing' => $settings ? $settings->missingLegalMentions() : [],
            'settings' => $settings ? array_merge($settings->toArray(), [
                'logo_url' => $settings->logo_url,
                'payment_qrcode_url' => $settings->payment_qrcode_url,
                'franchise_threshold' => $franchiseThreshold,
            ]) : [
                'country_code' => 'LU',
                'franchise_threshold' => 50000,
                'number_format' => DocumentNumberFormatter::DEFAULT_TEMPLATE,
                'invoice_prefix' => GenerateInvoiceNumberAction::DEFAULT_PREFIX_INVOICE,
                'credit_note_prefix' => GenerateInvoiceNumberAction::DEFAULT_PREFIX_CREDIT_NOTE,
                'quote_prefix' => GenerateQuoteNumberAction::DEFAULT_PREFIX,
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
                'placeholders' => DocumentNumberFormatter::PLACEHOLDERS,
                'default_template' => DocumentNumberFormatter::DEFAULT_TEMPLATE,
            ],
        ]);
    }

    /**
     * Update or create the business settings.
     */
    public function update(UpdateBusinessSettingsRequest $request, Reauthenticator $reauthenticator, SecurityAlerter $alerter): RedirectResponse
    {
        $validated = $request->validated();
        $settings = BusinessSettings::getInstance();

        // L'IBAN est ce qu'un voleur de session vient changer : c'est lui qui
        // détourne les paiements des factures à venir. On redemande le mot de
        // passe, et le code 2FA si elle est active, seulement quand il change.
        // Retoucher une couleur de PDF ne doit rien demander.
        $ibanChange = $this->ibanChange($settings, $validated['iban'] ?? null);
        $ancienIban = $settings?->iban;

        if ($ibanChange) {
            $reauthenticator->verify(
                $request->user(),
                $request->input('current_password'),
                $request->input('two_factor_code'),
            );
        }

        if ($settings) {
            $settings->update($validated);
        } else {
            $settings = BusinessSettings::create($validated);
        }

        // Le titulaire est prévenu, avec le lien de gel : si ce n'était pas
        // lui, c'est le moment de le dire. Les IBAN sont masqués, le mail
        // traverse des boîtes qu'on ne contrôle pas.
        if ($ibanChange) {
            $alerter->alert($request->user(), SecurityAlerter::IBAN_CHANGED, [
                'iban_from' => SecurityAlerter::maskIban($ancienIban),
                'iban_to' => SecurityAlerter::maskIban($settings->iban),
            ]);
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

        if (! $settings) {
            return back()->with('error', __('app.business_flash.error_settings_missing'));
        }

        // Delete old logo if exists
        if ($settings->logo_path) {
            Storage::disk('public')->delete($settings->logo_path);
        }

        // Store new logo
        $path = $request->file('logo')->store('logos', 'public');

        // forceFill + save : logo_path n'est plus fillable. Le chemin vient de
        // store() (nom généré par Laravel), jamais de la requête.
        $settings->forceFill(['logo_path' => $path])->save();

        return back()->with('success', __('app.business_flash.logo_updated'));
    }

    /**
     * Upload a payment QR code image (Payconiq, PayPal, etc.).
     */
    public function uploadPaymentQrcode(Request $request, Reauthenticator $reauthenticator, SecurityAlerter $alerter): RedirectResponse
    {
        $request->validate([
            'payment_qrcode' => ['required', 'image', 'mimes:png,jpg,jpeg,webp', 'max:1024'],
        ]);

        // Ce QR code s'imprime sur les factures et encode un compte à créditer :
        // le remplacer revient à changer l'IBAN par l'image. Même règle.
        $reauthenticator->verify(
            $request->user(),
            $request->input('current_password'),
            $request->input('two_factor_code'),
        );

        $settings = BusinessSettings::getInstance();

        if (! $settings) {
            return back()->with('error', __('app.business_flash.error_settings_missing'));
        }

        // Delete old QR code if exists
        if ($settings->payment_qrcode_path) {
            Storage::disk('public')->delete($settings->payment_qrcode_path);
        }

        $path = $request->file('payment_qrcode')->store('payment-qrcodes', 'public');

        $settings->forceFill(['payment_qrcode_path' => $path])->save();

        $alerter->alert($request->user(), SecurityAlerter::PAYMENT_QRCODE_CHANGED);

        return back()->with('success', __('app.business_flash.qrcode_updated'));
    }

    /**
     * Delete the payment QR code image.
     */
    public function deletePaymentQrcode(): RedirectResponse
    {
        $settings = BusinessSettings::getInstance();

        if (! $settings || ! $settings->payment_qrcode_path) {
            return back()->with('error', __('app.business_flash.error_no_qrcode'));
        }

        Storage::disk('public')->delete($settings->payment_qrcode_path);

        $settings->forceFill(['payment_qrcode_path' => null])->save();

        return back()->with('success', __('app.business_flash.qrcode_deleted'));
    }

    /**
     * Delete the logo.
     */
    public function deleteLogo(): RedirectResponse
    {
        $settings = BusinessSettings::getInstance();

        if (! $settings || ! $settings->logo_path) {
            return back()->with('error', __('app.business_flash.error_no_logo'));
        }

        // Delete file
        Storage::disk('public')->delete($settings->logo_path);

        // Clear path in database (forceFill : logo_path hors fillable)
        $settings->forceFill(['logo_path' => null])->save();

        return back()->with('success', __('app.business_flash.logo_deleted'));
    }

    /**
     * L'IBAN soumis diffère-t-il de celui en place ? Le premier IBAN d'un
     * compte compte aussi : un compte sans IBAN où l'on en pose un est
     * exactement le cas d'un détournement sur des factures encore vierges.
     */
    private function ibanChange(?BusinessSettings $settings, ?string $soumis): bool
    {
        $normaliser = fn (?string $iban) => strtoupper((string) preg_replace('/\s+/', '', (string) $iban));

        $actuel = $normaliser($settings?->iban);
        $nouveau = $normaliser($soumis);

        if ($nouveau === '') {
            return false;
        }

        return $actuel !== $nouveau;
    }
}
