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
        'logo_path',
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
        '0088' => 'EAN/GLN (international)',
        '0208' => 'Belgium enterprise number',
        '0007' => 'Sweden Org Number',
        '0192' => 'Denmark CVR',
        '0106' => 'Netherlands KvK',
        '0190' => 'Netherlands OIN',
    ];

    /**
     * Available VAT mention types.
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
     * Generate a snapshot of the business settings for invoice immutability.
     * This data will be stored in the invoice to preserve the state at creation time.
     */
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

        return self::VAT_MENTIONS[$this->default_vat_mention] ?? null;
    }

    /**
     * Get the list of VAT mention options for forms.
     */
    public static function getVatMentionOptions(): array
    {
        $options = [];
        foreach (self::VAT_MENTIONS as $key => $label) {
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
}
