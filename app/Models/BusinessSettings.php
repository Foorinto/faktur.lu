<?php

namespace App\Models;

use App\Traits\Auditable;
use App\Traits\BelongsToUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BusinessSettings extends Model
{
    use HasFactory, BelongsToUser, Auditable;

    protected $fillable = [
        'company_name',
        'legal_name',
        'address',
        'postal_code',
        'city',
        'country_code',
        'activity_type', // services, goods, mixed (for France thresholds)
        'vat_number',
        'peppol_endpoint_id',
        'peppol_endpoint_scheme',
        'matricule',
        'rcs_number',
        'establishment_authorization',
        'iban',
        'bic',
        'bank_name',
        'vat_regime',
        'default_hourly_rate',
        'default_invoice_footer',
        'default_vat_mention',
        'default_custom_vat_mention',
        'default_pdf_color',
        'phone',
        'show_phone_on_invoice',
        'email',
        'show_email_on_invoice',
        'show_payment_qrcode',
        'payment_qrcode_path',
        'default_payment_methods',
        'logo_path',
        // Custom document numbering (Invoice / Credit note / Quote)
        'number_format',
        'invoice_prefix',
        'credit_note_prefix',
        'quote_prefix',
        'invoice_starting_number',
        'credit_note_starting_number',
        'quote_starting_number',
        'number_padding',
        // Shared calendar (HR module Pro)
        'shared_calendar_enabled',
    ];

    public const NUMBERING_TYPE_INVOICE = 'invoice';
    public const NUMBERING_TYPE_CREDIT_NOTE = 'credit_note';
    public const NUMBERING_TYPE_QUOTE = 'quote';

    public const NUMBERING_TYPES = [
        self::NUMBERING_TYPE_INVOICE,
        self::NUMBERING_TYPE_CREDIT_NOTE,
        self::NUMBERING_TYPE_QUOTE,
    ];

    /**
     * Default PDF color (violet).
     */
    public const DEFAULT_PDF_COLOR = '#7c3aed';

    /**
     * Preset color options for PDF.
     */
    public const PDF_COLOR_PRESETS = [
        '#7c3aed' => 'Violet (défaut)',
        '#2563eb' => 'Bleu',
        '#0891b2' => 'Cyan',
        '#059669' => 'Vert',
        '#ca8a04' => 'Jaune/Or',
        '#ea580c' => 'Orange',
        '#dc2626' => 'Rouge',
        '#be185d' => 'Rose',
        '#4b5563' => 'Gris',
        '#1e293b' => 'Bleu marine',
    ];

    /**
     * Peppol endpoint scheme codes (ISO 6523 ICD).
     */
    public const PEPPOL_SCHEMES = [
        '0184' => 'Luxembourg VAT (LU)',
        '0009' => 'France SIRET',
        '0225' => 'France SIREN (facturation électronique)',
        '0088' => 'EAN/GLN (international)',
        '0208' => 'Belgium enterprise number',
        '0007' => 'Sweden Org Number',
        '0192' => 'Denmark CVR',
        '0106' => 'Netherlands KvK',
        '0190' => 'Netherlands OIN',
    ];

    /**
     * VAT mention type keys (used for form validation).
     * Actual mention texts are country-specific and loaded from config.
     */
    public const VAT_MENTION_TYPES = [
        'franchise',
        'reverse_charge',
        'intra_eu',
        'export',
        'none',
        'other',
    ];

    /**
     * Fallback VAT mentions (Luxembourg) for backwards compatibility.
     * @deprecated Use getVatMentions() instead
     */
    public const VAT_MENTIONS = [
        'franchise' => 'TVA non applicable, art. 57 du Code de la TVA luxembourgeois (Régime de franchise de taxe)',
        'reverse_charge' => 'Autoliquidation - Article 44 de la directive 2006/112/CE',
        'intra_eu' => 'Exonération de TVA - Livraison intracommunautaire (Art. 43 du Code de la TVA)',
        'export' => 'Exonération de TVA - Exportation (Art. 43 du Code de la TVA)',
        'none' => 'Aucune mention',
        'other' => 'Autre (texte personnalisé)',
    ];

    protected $casts = [
        'vat_regime' => 'string',
        'default_hourly_rate' => 'decimal:2',
        'show_email_on_invoice' => 'boolean',
        'show_phone_on_invoice' => 'boolean',
        'show_payment_qrcode' => 'boolean',
        'default_payment_methods' => 'array',
        'shared_calendar_enabled' => 'boolean',
    ];

    /**
     * Get the business settings for the authenticated user.
     * Returns null if no settings exist or no user is authenticated.
     */
    public static function getInstance(): ?self
    {
        if (!auth()->check()) {
            return null;
        }

        return static::first();
    }

    /**
     * Get the business settings for a specific user.
     */
    public static function getForUser(int|User $user): ?self
    {
        $userId = $user instanceof User ? $user->id : $user;

        return static::withoutGlobalScope('user')
            ->where('user_id', $userId)
            ->first();
    }

    /**
     * Check if business settings have been configured for the authenticated user.
     */
    public static function isConfigured(): bool
    {
        if (!auth()->check()) {
            return false;
        }

        return static::exists();
    }

    /**
     * Get or create business settings for the authenticated user.
     */
    public static function getOrCreate(): self
    {
        $instance = static::getInstance();

        if (!$instance) {
            $instance = static::create([
                'user_id' => auth()->id(),
            ]);
        }

        return $instance;
    }

    /**
     * Determine whether numbering settings (format, prefix, starting_number) for a
     * given document type can still be edited for the given year. They become locked
     * as soon as at least one document of that type has been finalised by this user
     * for that year - this guarantees the Article 61 LIVA continuous numbering rule.
     */
    public function canEditNumbering(string $type, ?int $year = null): bool
    {
        $year = $year ?? now()->year;
        $userId = $this->user_id;

        return match ($type) {
            self::NUMBERING_TYPE_INVOICE => ! Invoice::withoutGlobalScope('user')
                ->where('user_id', $userId)
                ->where('type', Invoice::TYPE_INVOICE)
                ->whereYear('finalized_at', $year)
                ->whereNotNull('finalized_at')
                ->exists(),
            self::NUMBERING_TYPE_CREDIT_NOTE => ! Invoice::withoutGlobalScope('user')
                ->where('user_id', $userId)
                ->where('type', Invoice::TYPE_CREDIT_NOTE)
                ->whereYear('finalized_at', $year)
                ->whereNotNull('finalized_at')
                ->exists(),
            self::NUMBERING_TYPE_QUOTE => ! Quote::withoutGlobalScope('user')
                ->where('user_id', $userId)
                ->whereYear('created_at', $year)
                ->whereNotNull('reference')
                ->exists(),
            default => true,
        };
    }

    /**
     * Returns a map [type => bool] describing which numbering settings are still
     * editable for the given year. Used by the Settings UI to disable fields
     * contextually and by the controller to refuse changes server-side.
     */
    public function numberingEditability(?int $year = null): array
    {
        $year = $year ?? now()->year;

        return [
            self::NUMBERING_TYPE_INVOICE => $this->canEditNumbering(self::NUMBERING_TYPE_INVOICE, $year),
            self::NUMBERING_TYPE_CREDIT_NOTE => $this->canEditNumbering(self::NUMBERING_TYPE_CREDIT_NOTE, $year),
            self::NUMBERING_TYPE_QUOTE => $this->canEditNumbering(self::NUMBERING_TYPE_QUOTE, $year),
        ];
    }

    /**
     * Count of finalized documents for a given type and year. Used in the UI to display
     * a contextual message: "Vous avez déjà émis N facture(s) en 2026."
     */
    public function finalizedCountFor(string $type, ?int $year = null): int
    {
        $year = $year ?? now()->year;
        $userId = $this->user_id;

        return match ($type) {
            self::NUMBERING_TYPE_INVOICE => Invoice::withoutGlobalScope('user')
                ->where('user_id', $userId)
                ->where('type', Invoice::TYPE_INVOICE)
                ->whereYear('finalized_at', $year)
                ->whereNotNull('finalized_at')
                ->count(),
            self::NUMBERING_TYPE_CREDIT_NOTE => Invoice::withoutGlobalScope('user')
                ->where('user_id', $userId)
                ->where('type', Invoice::TYPE_CREDIT_NOTE)
                ->whereYear('finalized_at', $year)
                ->whereNotNull('finalized_at')
                ->count(),
            self::NUMBERING_TYPE_QUOTE => Quote::withoutGlobalScope('user')
                ->where('user_id', $userId)
                ->whereYear('created_at', $year)
                ->whereNotNull('reference')
                ->count(),
            default => 0,
        };
    }

    /**
     * Generate a snapshot of the business settings for invoice immutability.
     * This data will be stored in the invoice to preserve the state at creation time.
     */
    /** Supported payment methods for invoices (FEAT-098). */
    public const PAYMENT_METHODS = ['transfer', 'payconiq', 'cash', 'card', 'check'];

    /**
     * Effective payment methods to display on invoices.
     * Defaults to bank transfer when nothing is configured (preserves legacy behaviour).
     *
     * @return array<int, string>
     */
    public function getEffectivePaymentMethods(): array
    {
        $methods = is_array($this->default_payment_methods) ? $this->default_payment_methods : [];
        $valid = array_values(array_intersect($methods, self::PAYMENT_METHODS));

        return $valid !== [] ? $valid : ['transfer'];
    }

    public function toSnapshot(): array
    {
        return [
            'company_name' => $this->company_name,
            'legal_name' => $this->legal_name,
            'address' => $this->address,
            'postal_code' => $this->postal_code,
            'city' => $this->city,
            'country_code' => $this->country_code,
            'vat_number' => $this->vat_number,
            'peppol_endpoint_id' => $this->peppol_endpoint_id,
            'peppol_endpoint_scheme' => $this->peppol_endpoint_scheme,
            'matricule' => $this->matricule,
            'rcs_number' => $this->rcs_number,
            'establishment_authorization' => $this->establishment_authorization,
            'iban' => $this->iban,
            'bic' => $this->bic,
            'bank_name' => $this->bank_name,
            'vat_regime' => $this->vat_regime,
            'phone' => $this->phone,
            'show_phone_on_invoice' => $this->show_phone_on_invoice,
            'email' => $this->email,
            'show_email_on_invoice' => $this->show_email_on_invoice,
            'show_payment_qrcode' => $this->show_payment_qrcode,
            'payment_qrcode_path' => $this->payment_qrcode_path,
            'default_payment_methods' => $this->getEffectivePaymentMethods(),
            'logo_path' => $this->logo_path,
            'pdf_color' => $this->getEffectivePdfColor(),
        ];
    }

    /**
     * Get the full Peppol endpoint identifier (scheme:id format).
     */
    public function getPeppolEndpointAttribute(): ?string
    {
        if ($this->peppol_endpoint_id && $this->peppol_endpoint_scheme) {
            return "{$this->peppol_endpoint_scheme}:{$this->peppol_endpoint_id}";
        }

        return null;
    }

    /**
     * Check if a Peppol endpoint is configured.
     */
    public function hasPeppolEndpoint(): bool
    {
        return !empty($this->peppol_endpoint_id) && !empty($this->peppol_endpoint_scheme);
    }

    /**
     * Get the list of Peppol scheme options for forms.
     */
    public static function getPeppolSchemeOptions(): array
    {
        $options = [];
        foreach (self::PEPPOL_SCHEMES as $code => $label) {
            $options[] = [
                'value' => $code,
                'label' => "{$code} - {$label}",
            ];
        }
        return $options;
    }

    /**
     * Get the full path to the logo file for PDF generation.
     */
    public function getLogoFullPathAttribute(): ?string
    {
        if (!$this->logo_path) {
            return null;
        }

        return storage_path('app/public/' . $this->logo_path);
    }

    /**
     * Get the public URL to the payment QR code image.
     */
    public function getPaymentQrcodeUrlAttribute(): ?string
    {
        if (!$this->payment_qrcode_path) {
            return null;
        }

        return asset('storage/' . $this->payment_qrcode_path);
    }

    /**
     * Get the public URL to the logo.
     */
    public function getLogoUrlAttribute(): ?string
    {
        if (!$this->logo_path) {
            return null;
        }

        return asset('storage/' . $this->logo_path);
    }

    /**
     * Get the formatted address for display.
     */
    public function getFormattedAddressAttribute(): string
    {
        return implode("\n", array_filter([
            $this->address,
            "{$this->postal_code} {$this->city}",
            $this->country_code !== 'LU' ? $this->country_code : null,
        ]));
    }

    /**
     * Check if the business is VAT exempt (franchise regime).
     */
    public function isVatExempt(): bool
    {
        return $this->vat_regime === 'franchise';
    }

    /**
     * Check if the business is VAT registered (assujetti regime).
     */
    public function isVatRegistered(): bool
    {
        return $this->vat_regime === 'assujetti';
    }

    /**
     * Get the default VAT mention text.
     */
    public function getDefaultVatMentionTextAttribute(): ?string
    {
        if (!$this->default_vat_mention || $this->default_vat_mention === 'none') {
            return null;
        }

        if ($this->default_vat_mention === 'other') {
            return $this->default_custom_vat_mention;
        }

        // Use country-specific mention
        $mentions = $this->getVatMentions();
        return $mentions[$this->default_vat_mention] ?? null;
    }

    /**
     * Get the list of VAT mention options for forms.
     */
    /**
     * Get VAT mentions for a specific country.
     */
    public static function getVatMentionsForCountry(?string $countryCode = 'LU'): array
    {
        $countryCode = $countryCode ?? 'LU';
        $countryMentions = config("countries.{$countryCode}.vat_mentions", []);

        // Merge with static options (none, other)
        return array_merge($countryMentions, [
            'none' => 'Aucune mention',
            'other' => 'Autre (texte personnalisé)',
        ]);
    }

    /**
     * Get VAT mentions for this business's country.
     */
    public function getVatMentions(): array
    {
        return self::getVatMentionsForCountry($this->country_code);
    }

    /**
     * Get the list of VAT mention options for forms.
     * Uses the authenticated user's business country if available.
     */
    public static function getVatMentionOptions(?string $countryCode = null): array
    {
        // If no country code provided, try to get from current user's settings
        if ($countryCode === null) {
            $settings = self::getInstance();
            $countryCode = $settings?->country_code ?? 'LU';
        }

        $mentions = self::getVatMentionsForCountry($countryCode);

        $options = [];
        foreach ($mentions as $key => $label) {
            $options[] = [
                'value' => $key,
                'label' => $label,
            ];
        }
        return $options;
    }

    /**
     * Get the list of PDF color presets for forms.
     */
    public static function getPdfColorPresets(): array
    {
        $presets = [];
        foreach (self::PDF_COLOR_PRESETS as $color => $label) {
            $presets[] = [
                'value' => $color,
                'label' => $label,
            ];
        }
        return $presets;
    }

    /**
     * Get the effective PDF color.
     */
    public function getEffectivePdfColor(): string
    {
        return $this->default_pdf_color ?? self::DEFAULT_PDF_COLOR;
    }

    /**
     * Return a version of the given hex color that stays legible on a white
     * PDF (text + colored band). Only darkens colors that are too light
     * (white, pale pastels) using perceived luminance, so vivid brand colors
     * already legible (e.g. the default purple) are left untouched.
     *
     * @param  string  $hex      Color like "#rrggbb" (with or without '#').
     * @param  float   $tooLight Perceived-luminance threshold above which a color is darkened (0..1).
     * @param  float   $target   Perceived luminance to darken too-light colors down to (0..1).
     */
    public static function legibleColor(string $hex, float $tooLight = 0.6, float $target = 0.4): string
    {
        $clean = ltrim(trim($hex), '#');

        // Only handle #rrggbb; anything else is returned unchanged (defensive).
        if (!preg_match('/^[0-9A-Fa-f]{6}$/', $clean)) {
            return $hex;
        }

        $r = hexdec(substr($clean, 0, 2));
        $g = hexdec(substr($clean, 2, 2));
        $b = hexdec(substr($clean, 4, 2));

        // Perceived luminance (0..1) — weighted for human eye sensitivity.
        $lum = (0.299 * $r + 0.587 * $g + 0.114 * $b) / 255;

        if ($lum <= $tooLight) {
            return '#' . strtolower($clean);
        }

        // Scale all channels toward black until the perceived luminance hits the
        // target. Scaling equally preserves the hue (white -> neutral grey).
        $factor = $target / max($lum, 0.0001);

        $r = (int) round($r * $factor);
        $g = (int) round($g * $factor);
        $b = (int) round($b * $factor);

        return sprintf('#%02x%02x%02x', $r, $g, $b);
    }

    /**
     * Get the country configuration for this business.
     */
    public function getCountryConfig(): array
    {
        $countryCode = $this->country_code ?? 'LU';

        return config("countries.{$countryCode}", config('countries.LU'));
    }

    /**
     * Get the VAT rates available for this business's country.
     */
    public function getVatRates(): array
    {
        $config = $this->getCountryConfig();

        return $config['vat_rates'] ?? [];
    }

    /**
     * Get the default VAT rate for this business's country.
     */
    public function getDefaultVatRate(): float
    {
        $config = $this->getCountryConfig();

        return $config['default_vat_rate'] ?? 17.0;
    }

    /**
     * Get the franchise threshold for this business.
     * For France, this depends on activity_type (services vs goods).
     */
    public function getFranchiseThreshold(): int
    {
        $config = $this->getCountryConfig();
        $franchise = $config['franchise'] ?? [];

        // For countries with single threshold (LU, BE, DE)
        if (($franchise['threshold_type'] ?? 'single') === 'single' || ($franchise['threshold_type'] ?? 'single') === 'previous_year') {
            return $franchise['threshold'] ?? 50000;
        }

        // For France with services/goods thresholds
        return match ($this->activity_type) {
            'goods' => $franchise['threshold_goods'] ?? 85000,
            'services', 'mixed' => $franchise['threshold_services'] ?? 37500,
            default => $franchise['threshold_services'] ?? $franchise['threshold'] ?? 37500,
        };
    }

    /**
     * Get the franchise legal mention for this business's country.
     */
    public function getFranchiseMention(): string
    {
        $config = $this->getCountryConfig();

        return $config['franchise']['mention'] ?? self::VAT_MENTIONS['franchise'];
    }

    /**
     * Get the franchise legal reference for this business's country.
     */
    public function getFranchiseLegalReference(): string
    {
        $config = $this->getCountryConfig();

        return $config['franchise']['legal_reference'] ?? 'Art. 57 du Code de la TVA luxembourgeois';
    }

    /**
     * Get the VAT number format regex for this business's country.
     */
    public function getVatNumberFormat(): string
    {
        $config = $this->getCountryConfig();

        return $config['vat_number']['format'] ?? '/^LU\d{8}$/';
    }

    /**
     * Check if the VAT number is valid for this business's country.
     */
    public function isVatNumberValid(?string $vatNumber = null): bool
    {
        $vatNumber = $vatNumber ?? $this->vat_number;

        if (empty($vatNumber)) {
            return false;
        }

        $format = $this->getVatNumberFormat();

        return (bool) preg_match($format, $vatNumber);
    }

    /**
     * Get the list of supported countries for forms.
     */
    public static function getSupportedCountries(): array
    {
        $countries = config('countries', []);
        $options = [];

        foreach ($countries as $code => $config) {
            $options[] = [
                'value' => $code,
                'label' => $config['name'],
                'flag' => self::getCountryFlag($code),
            ];
        }

        return $options;
    }

    /**
     * Get the flag emoji for a country code.
     */
    public static function getCountryFlag(string $countryCode): string
    {
        return match (strtoupper($countryCode)) {
            'LU' => '🇱🇺',
            'FR' => '🇫🇷',
            'BE' => '🇧🇪',
            'DE' => '🇩🇪',
            default => '🏳️',
        };
    }

    /**
     * Get activity type options for forms (for France).
     */
    public static function getActivityTypeOptions(): array
    {
        return [
            ['value' => 'services', 'label' => 'Services (prestations intellectuelles)', 'threshold' => 37500],
            ['value' => 'goods', 'label' => 'Vente de biens', 'threshold' => 85000],
            ['value' => 'mixed', 'label' => 'Mixte (services + biens)', 'threshold' => 37500],
        ];
    }
}
