<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BusinessSettings extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_name',
        'legal_name',
        'address',
        'postal_code',
        'city',
        'country_code',
        'vat_number',
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
        'email',
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
    ];

    /**
     * Get the singleton instance of BusinessSettings.
     * Creates a new instance if none exists.
     */
    public static function getInstance(): ?self
    {
        return static::first();
    }

    /**
     * Check if business settings have been configured.
     */
    public static function isConfigured(): bool
    {
        return static::exists();
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
            'matricule' => $this->matricule,
            'rcs_number' => $this->rcs_number,
            'establishment_authorization' => $this->establishment_authorization,
            'iban' => $this->iban,
            'bic' => $this->bic,
            'bank_name' => $this->bank_name,
            'vat_regime' => $this->vat_regime,
            'phone' => $this->phone,
            'email' => $this->email,
            'logo_path' => $this->logo_path,
            'pdf_color' => $this->getEffectivePdfColor(),
        ];
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
