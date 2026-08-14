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
        'pdf_text_size',
        'pdf_logo_size',
        'phone',
        'show_phone_on_invoice',
        'email',
        'show_email_on_invoice',
        'show_payment_qrcode',
        'payment_qrcode_path',
        'default_payment_methods',
        'payment_instructions',
        'show_payment_conditions',
        'late_penalty_text',
        'recovery_fee_amount',
        'discount_terms',
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
     * Tailles de texte proposées pour les PDF, et leur facteur d'échelle.
     *
     * Un **facteur** appliqué à l'ensemble du gabarit, et non une liste de
     * tailles à redéfinir une par une : le document compte une quinzaine de
     * tailles qui forment une hiérarchie (titre, intertitre, corps, mentions).
     * Les redéfinir séparément la casserait au premier oubli ; une échelle la
     * conserve par construction.
     *
     * Les paliers restent modestes à dessein. Au delà de 1,3 une facture d'une
     * quinzaine de lignes déborde sur une deuxième page, ce qui déplace le pied
     * de page et les totaux.
     */
    public const PDF_TEXT_SIZES = [
        'normal' => 1.0,
        'large' => 1.15,
        'xlarge' => 1.3,
    ];

    public const DEFAULT_PDF_TEXT_SIZE = 'normal';

    /**
     * Tailles de logo proposées, et leur facteur appliqué au gabarit de base
     * (120 x 60 px), soit de 90x45 à 204x102.
     *
     * Réglage distinct de celui du texte, à dessein : la lisibilité d'un logo
     * tient à sa forme et à son niveau de détail, pas à la taille des
     * caractères qui l'entourent. Un logotype simple reste lisible en petit ;
     * un logo chargé de texte ne l'est pas, quelle que soit la police du reste.
     *
     * Les paliers sont plus larges que ceux du texte : un logo trop grand
     * déséquilibre l'en-tête sans rien casser, là où un texte trop grand fait
     * déborder le document sur une deuxième page.
     */
    public const PDF_LOGO_SIZES = [
        'small' => 0.75,
        'normal' => 1.0,
        'large' => 1.35,
        'xlarge' => 1.7,
    ];

    public const DEFAULT_PDF_LOGO_SIZE = 'normal';

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
        // 9938 et non 0184 : ce dernier est danois. Le libellé « Luxembourg VAT »
        // porté par 0184 a conduit les utilisateurs à choisir le Danemark.
        '9938' => 'Luxembourg VAT (LU)',
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
        'franchise' => 'TVA non applicable - Article 57bis de la loi modifiée du 12 février 1979 (Régime de franchise de taxe)',
        // Art. 196 (redevable = le preneur), pas art. 44 (lieu d'imposition).
        'reverse_charge' => 'Autoliquidation - Article 196 de la directive 2006/112/CE',
        'intra_eu' => 'Exonération de TVA - Livraison intracommunautaire (Art. 43 du Code de la TVA)',
        'export' => 'Exonération de TVA - Exportation (Art. 43 du Code de la TVA)',
        'none' => 'Aucune mention',
        'other' => 'Autre (texte personnalisé)',
    ];

    protected $casts = [
        'vat_regime' => 'string',
        'iban' => 'encrypted', // chiffré au repos (RGPD) ; déchiffrement transparent à la lecture
        'default_hourly_rate' => 'decimal:2',
        'show_email_on_invoice' => 'boolean',
        'show_phone_on_invoice' => 'boolean',
        'show_payment_qrcode' => 'boolean',
        'default_payment_methods' => 'array',
        'show_payment_conditions' => 'boolean',
        'recovery_fee_amount' => 'decimal:2',
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
     * for that year - this guarantees the Article 63 LIVA continuous numbering rule.
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
    /**
     * Legacy predefined keys still recognised for label translation on the PDF.
     * Payment methods are now free-text (FEAT-098), so this list is only used to
     * translate historical values; new entries are stored as plain labels.
     */
    public const PAYMENT_METHODS = ['transfer', 'payconiq', 'cash', 'card', 'check'];

    /**
     * Effective payment methods to display on invoices (free-text labels).
     * Defaults to bank transfer when nothing is configured (preserves legacy behaviour).
     *
     * @return array<int, string>
     */
    public function getEffectivePaymentMethods(): array
    {
        $methods = is_array($this->default_payment_methods) ? $this->default_payment_methods : [];
        $methods = array_values(array_filter(
            array_map(fn ($m) => trim((string) $m), $methods),
            fn ($m) => $m !== ''
        ));

        return $methods !== [] ? $methods : ['transfer'];
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
            'payment_instructions' => $this->payment_instructions,
            'show_payment_conditions' => $this->show_payment_conditions ?? true,
            'late_penalty_text' => $this->late_penalty_text,
            'recovery_fee_amount' => $this->recovery_fee_amount,
            'discount_terms' => $this->discount_terms,
            'logo_path' => $this->logo_path,
            'pdf_color' => $this->getEffectivePdfColor(),
            // Figé dans l'instantané au même titre que la couleur : réimprimer
            // une facture émise doit redonner exactement le même document.
            'pdf_text_size' => $this->pdf_text_size ?? self::DEFAULT_PDF_TEXT_SIZE,
            'pdf_logo_size' => $this->pdf_logo_size ?? self::DEFAULT_PDF_LOGO_SIZE,
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
     * Fonction de mise à l'échelle des tailles du gabarit PDF.
     *
     * Renvoie une fonction pt → chaîne CSS, à passer à la vue. dompdf ne gère
     * pas les variables CSS : les valeurs doivent être calculées ici et écrites
     * en dur dans le style.
     *
     * Une taille inconnue (réglage supprimé, instantané d'une facture émise
     * avant cette fonctionnalité) retombe sur l'échelle 1 : le document se rend
     * alors exactement comme avant.
     */
    /** Facteur d'échelle du logo sur les documents PDF. */
    public static function pdfLogoScale(?string $size): float
    {
        return self::PDF_LOGO_SIZES[$size] ?? self::PDF_LOGO_SIZES[self::DEFAULT_PDF_LOGO_SIZE];
    }

    public static function pdfFontSizer(?string $size): \Closure
    {
        $scale = self::PDF_TEXT_SIZES[$size] ?? self::PDF_TEXT_SIZES[self::DEFAULT_PDF_TEXT_SIZE];

        return static function (float $pt) use ($scale): string {
            // Deux décimales suffisent en typographie, et évitent d'écrire
            // « 9.199999999999999pt » dans la feuille de style.
            return rtrim(rtrim(number_format($pt * $scale, 2, '.', ''), '0'), '.').'pt';
        };
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

        return $config['franchise']['legal_reference'] ?? 'Article 57bis de la loi modifiée du 12 février 1979';
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
     * Pays d'établissement proposés à la saisie : le Luxembourg, et lui seul.
     *
     * `config/countries.php` décrit toujours quatre pays, et ce n'est pas une
     * incohérence : ces grilles servent aux ACHATS, pour retrouver le taux d'un
     * fournisseur allemand ou français. Ce qui est fermé ici, c'est la
     * possibilité de déclarer sa PROPRE entreprise ailleurs qu'au Luxembourg.
     *
     * La distinction est le cœur du sujet. Facturer un client belge, acheter
     * chez Amazon.de, autoliquider une acquisition intracommunautaire : tout
     * cela reste, parce que c'est le quotidien d'une entreprise luxembourgeoise.
     * Ce qui disparaît, c'est de faire passer faktur.lu pour un outil belge ou
     * allemand — une promesse que le code ne tenait pas, faute d'export TVA
     * pour ces administrations.
     *
     * Rouvrir revient à rétablir cette liste : rien n'a été supprimé ailleurs.
     */
    public const PAYS_ETABLISSEMENT = 'LU';

    public static function getSupportedCountries(): array
    {
        $code = self::PAYS_ETABLISSEMENT;

        return [[
            'value' => $code,
            'label' => config("countries.{$code}.name", 'Luxembourg'),
            'flag' => self::getCountryFlag($code),
        ]];
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
