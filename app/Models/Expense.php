<?php

namespace App\Models;

use App\Helpers\DatabaseHelper;
use App\Traits\Auditable;
use App\Traits\BelongsToUser;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Expense extends Model implements HasMedia
{
    use HasFactory, SoftDeletes, InteractsWithMedia, BelongsToUser, Auditable;

    public const CATEGORY_HARDWARE = 'hardware';
    public const CATEGORY_SOFTWARE = 'software';
    public const CATEGORY_HOSTING = 'hosting';
    public const CATEGORY_OFFICE = 'office';
    public const CATEGORY_TRAVEL = 'travel';
    public const CATEGORY_TRAINING = 'training';
    public const CATEGORY_PROFESSIONAL_SERVICES = 'professional_services';
    public const CATEGORY_TELECOMMUNICATIONS = 'telecommunications';
    public const CATEGORY_OTHER = 'other';

    public const PAYMENT_CASH = 'cash';
    public const PAYMENT_CARD = 'card';
    public const PAYMENT_TRANSFER = 'transfer';
    public const PAYMENT_CHECK = 'check';

    /**
     * Unité dans laquelle le montant a été saisi.
     *
     * Une facture de fournisseur en ligne n'affiche souvent que le total payé :
     * obliger à saisir le HT revient à demander une division à la main.
     */
    public const INPUT_HT = 'ht';
    public const INPUT_TTC = 'ttc';

    /**
     * Nature de la TVA portée par l'achat.
     *
     * C'est ce champ, et non le taux, qui décide si la TVA payée est
     * récupérable dans la déclaration luxembourgeoise.
     */
    public const REGIME_NATIONAL = 'national';
    public const REGIME_REVERSE_CHARGE = 'reverse_charge';
    public const REGIME_FOREIGN_VAT = 'foreign_vat';
    public const REGIME_EXEMPT = 'exempt';

    protected $fillable = [
        'date',
        'provider_name',
        'supplier_country',
        'category',
        'amount_ht',
        'vat_rate',
        'vat_regime',
        'reverse_charge_vat_rate',
        'reverse_charge_vat',
        'amount_vat',
        'amount_ttc',
        'amount_input_mode',
        'description',
        'is_deductible',
        'payment_method',
        'reference',
    ];

    protected $casts = [
        'date' => 'date:Y-m-d',
        'amount_ht' => 'decimal:4',
        'amount_vat' => 'decimal:4',
        'amount_ttc' => 'decimal:4',
        'vat_rate' => 'decimal:2',
        'reverse_charge_vat_rate' => 'decimal:2',
        'reverse_charge_vat' => 'decimal:4',
        'is_deductible' => 'boolean',
    ];

    protected $attributes = [
        'supplier_country' => 'LU',
        'vat_regime' => self::REGIME_NATIONAL,
        'amount_input_mode' => self::INPUT_HT,
    ];

    /**
     * Boot the model.
     */
    protected static function booted(): void
    {
        // Auto-calculate VAT and TTC when saving
        static::saving(function (Expense $expense) {
            $expense->applyVatRegime();
            $expense->calculateAmounts();
        });
    }

    /**
     * Register media collections.
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('attachments')
            ->acceptsMimeTypes(['application/pdf', 'image/jpeg', 'image/png', 'image/webp'])
            ->singleFile();
    }

    /**
     * Tire les conséquences du régime de TVA sur les autres champs.
     *
     * Ces règles sont appliquées ici, et non dans le formulaire, parce que
     * l'API écrit dans la même table : une dépense créée hors interface doit
     * produire les mêmes totaux qu'une dépense saisie à l'écran.
     */
    public function applyVatRegime(): void
    {
        switch ($this->vat_regime) {
            case self::REGIME_FOREIGN_VAT:
                // La TVA d'un autre État membre ne se déduit pas au
                // Luxembourg : elle se récupère par la procédure de
                // remboursement de la directive 2008/9/CE. La laisser
                // déductible gonflerait la TVA récupérable du récapitulatif
                // fiscal d'un montant que l'AED n'attend pas.
                $this->is_deductible = false;
                break;

            case self::REGIME_REVERSE_CHARGE:
                // La facture du fournisseur ne porte aucune TVA. Un taux
                // résiduel, hérité d'une saisie précédente, inventerait une
                // taxe qui n'a jamais été payée.
                $this->vat_rate = 0;

                // Mais l'acheteur, lui, doit déclarer la TVA de SON pays sur
                // cet achat. À défaut d'indication, le taux normal : c'est
                // celui qui s'applique dans l'immense majorité des cas.
                $this->reverse_charge_vat_rate ??= self::defaultReverseChargeRate();
                break;

            case self::REGIME_EXEMPT:
                $this->vat_rate = 0;
                break;
        }

        // Hors autoliquidation, aucune TVA à s'auto-facturer. Le nettoyage est
        // explicite : sans lui, changer le régime d'une dépense déjà
        // enregistrée laisserait son montant autoliquidé dans la déclaration.
        if ($this->vat_regime !== self::REGIME_REVERSE_CHARGE) {
            $this->reverse_charge_vat_rate = null;
            $this->reverse_charge_vat = 0;
        }
    }

    /**
     * Taux normal du pays de l'entreprise, appliqué par défaut à un achat
     * autoliquidé.
     */
    public static function defaultReverseChargeRate(): float
    {
        $settings = BusinessSettings::getInstance();
        $country = $settings?->country_code ?: 'LU';

        return (float) config("countries.{$country}.default_vat_rate", 17);
    }

    /**
     * Calculate VAT and TTC amounts.
     */
    public function calculateAmounts(): void
    {
        $vatRate = (string) ($this->vat_rate ?? '0');
        $vatMultiplier = bcdiv($vatRate, '100', 6);

        if ($this->amount_input_mode === self::INPUT_TTC) {
            // Saisie en TTC : on descend du total vers la base, jamais
            // l'inverse. Le TTC est le montant réellement débité — il doit
            // rester au centime près celui du relevé bancaire, quitte à ce que
            // la TVA absorbe la fraction d'arrondi.
            $amountTtc = bcadd((string) ($this->amount_ttc ?? '0'), '0', 4);

            $this->amount_ht = bcdiv($amountTtc, bcadd('1', $vatMultiplier, 6), 4);
            $this->amount_vat = bcsub($amountTtc, $this->amount_ht, 4);
            $this->amount_ttc = $amountTtc;

            $this->calculateReverseChargeVat();

            return;
        }

        $amountHt = (string) ($this->amount_ht ?? '0');

        $this->amount_vat = bcmul($amountHt, $vatMultiplier, 4);
        $this->amount_ttc = bcadd($amountHt, $this->amount_vat, 4);

        $this->calculateReverseChargeVat();
    }

    /**
     * TVA que l'acheteur se facture à lui-même sur un achat autoliquidé.
     *
     * Elle se calcule sur la base hors taxe, et reste étrangère au TTC : rien
     * de plus n'a été débité, le fournisseur n'a facturé que le HT.
     */
    public function calculateReverseChargeVat(): void
    {
        if ($this->vat_regime !== self::REGIME_REVERSE_CHARGE) {
            $this->reverse_charge_vat = 0;

            return;
        }

        $rate = bcdiv((string) ($this->reverse_charge_vat_rate ?? '0'), '100', 6);

        $this->reverse_charge_vat = bcmul((string) ($this->amount_ht ?? '0'), $rate, 4);
    }

    /**
     * Régimes de TVA proposés à la saisie.
     *
     * @return array<string, string>
     */
    public static function getVatRegimes(): array
    {
        return [
            self::REGIME_NATIONAL => __('app.expense_vat_regimes.national'),
            self::REGIME_REVERSE_CHARGE => __('app.expense_vat_regimes.reverse_charge'),
            self::REGIME_FOREIGN_VAT => __('app.expense_vat_regimes.foreign_vat'),
            self::REGIME_EXEMPT => __('app.expense_vat_regimes.exempt'),
        ];
    }

    /**
     * Libellé du régime de TVA.
     */
    public function getVatRegimeLabelAttribute(): string
    {
        return self::getVatRegimes()[$this->vat_regime] ?? $this->vat_regime;
    }

    /**
     * Code réservé aux fournisseurs hors Union européenne.
     *
     * La colonne ne fait que deux caractères et la liste ISO ne prévoit rien
     * pour « ailleurs » : `XX` est le code libre de la norme, précisément
     * prévu pour cet usage.
     */
    public const COUNTRY_NON_EU = 'XX';

    /**
     * Pays proposés à la saisie : les Vingt-Sept, plus le reste du monde.
     *
     * @return array<int, array{code: string, name: string}>
     */
    public static function getSupplierCountries(): array
    {
        $countries = \App\Services\VatCalculationService::getEuCountriesWithNames();

        // Le service les rend triés par code ISO : « Allemagne » se retrouvait
        // sous DE, entre Tchéquie et Danemark. On cherche un pays par son nom,
        // pas par son code.
        //
        // Le tri ignore les accents : une comparaison octet par octet
        // renverrait « Grèce » et « Tchéquie » après « Suède », les caractères
        // accentués se classant au-delà de l'alphabet ASCII.
        $key = fn (string $name) => mb_strtolower(
            strtr($name, ['à' => 'a', 'â' => 'a', 'ç' => 'c', 'é' => 'e', 'è' => 'e', 'ê' => 'e', 'î' => 'i', 'ï' => 'i', 'ô' => 'o', 'û' => 'u', 'ù' => 'u'])
        );

        usort($countries, fn ($a, $b) => $key($a['name']) <=> $key($b['name']));

        // « Hors UE » ferme la liste : ce n'est pas un pays, il n'a pas à
        // s'intercaler dans l'ordre alphabétique.
        $countries[] = ['code' => self::COUNTRY_NON_EU, 'name' => __('app.expense_country_non_eu')];

        return $countries;
    }

    /**
     * Nom du pays du fournisseur.
     */
    public function getSupplierCountryNameAttribute(): ?string
    {
        if (! $this->supplier_country) {
            return null;
        }

        foreach (self::getSupplierCountries() as $country) {
            if ($country['code'] === $this->supplier_country) {
                return $country['name'];
            }
        }

        return $this->supplier_country;
    }

    /**
     * Mémoire de requête des catégories.
     *
     * `getCategoryLabelAttribute()` est appelé une fois par ligne affichée :
     * sans ce cache, lister vingt dépenses déclencherait vingt lectures de la
     * table des catégories.
     *
     * @var array<string, array<string, string>>
     */
    protected static array $categoryMapCache = [];

    /**
     * Catégories proposées à la saisie (actives uniquement).
     *
     * Conserve sa signature d'origine : elle est utilisée par les FormRequests
     * et par ExpenseController.
     */
    public static function getCategories(): array
    {
        return self::categoryMap();
    }

    /**
     * Catégories de l'utilisateur courant.
     *
     * `$activeOnly = false` sert à deux usages où masquer une catégorie
     * désactivée serait faux : afficher le libellé d'une dépense ancienne, et
     * valider la modification d'une dépense dont la catégorie a depuis été
     * retirée de la liste de saisie.
     *
     * @return array<string, string>
     */
    public static function categoryMap(bool $activeOnly = true): array
    {
        $user = auth()->user();

        if (! $user) {
            return self::builtInCategories();
        }

        $cacheKey = $user->id.($activeOnly ? ':active' : ':all');

        return self::$categoryMapCache[$cacheKey] ??= PurchaseCategory::mapFor($user, $activeOnly);
    }

    /**
     * Vide la mémoire de requête, après une écriture sur les catégories.
     */
    public static function forgetCategoryMapCache(): void
    {
        self::$categoryMapCache = [];
    }

    /**
     * Les neuf catégories d'origine, encore servies aux comptes qui n'ont pas
     * déclenché le provisionnement — et hors contexte authentifié.
     */
    public static function builtInCategories(): array
    {
        return [
            self::CATEGORY_HARDWARE => __('app.expense_categories.hardware'),
            self::CATEGORY_SOFTWARE => __('app.expense_categories.software'),
            self::CATEGORY_HOSTING => __('app.expense_categories.hosting'),
            self::CATEGORY_OFFICE => __('app.expense_categories.office'),
            self::CATEGORY_TRAVEL => __('app.expense_categories.travel'),
            self::CATEGORY_TRAINING => __('app.expense_categories.training'),
            self::CATEGORY_PROFESSIONAL_SERVICES => __('app.expense_categories.professional_services'),
            self::CATEGORY_TELECOMMUNICATIONS => __('app.expense_categories.telecommunications'),
            self::CATEGORY_OTHER => __('app.expense_categories.other'),
        ];
    }

    /**
     * Get all available payment methods.
     */
    public static function getPaymentMethods(): array
    {
        return [
            self::PAYMENT_CARD => __('app.payment_methods.card'),
            self::PAYMENT_TRANSFER => __('app.payment_methods.transfer'),
            self::PAYMENT_CASH => __('app.payment_methods.cash'),
            self::PAYMENT_CHECK => __('app.payment_methods.check'),
        ];
    }

    /**
     * Get the category label.
     */
    public function getCategoryLabelAttribute(): string
    {
        // Les catégories désactivées sont incluses : une dépense de l'an dernier
        // doit garder un libellé lisible même si la catégorie a été retirée de
        // la liste de saisie depuis.
        return self::categoryMap(activeOnly: false)[$this->category]
            ?? self::builtInCategories()[$this->category]
            ?? $this->category;
    }

    /**
     * Get the payment method label.
     */
    public function getPaymentMethodLabelAttribute(): ?string
    {
        if (!$this->payment_method) {
            return null;
        }
        return self::getPaymentMethods()[$this->payment_method] ?? $this->payment_method;
    }

    /**
     * Get the attachment URL if exists.
     */
    public function getAttachmentUrlAttribute(): ?string
    {
        $media = $this->getFirstMedia('attachments');
        return $media ? $media->getUrl() : null;
    }

    /**
     * Get the attachment filename if exists.
     */
    public function getAttachmentFilenameAttribute(): ?string
    {
        $media = $this->getFirstMedia('attachments');
        return $media ? $media->file_name : null;
    }

    /**
     * Check if expense has an attachment.
     */
    public function hasAttachment(): bool
    {
        return $this->getFirstMedia('attachments') !== null;
    }

    /**
     * Scope for filtering by category.
     */
    public function scopeCategory(Builder $query, string $category): Builder
    {
        return $query->where('category', $category);
    }

    /**
     * Scope for filtering by date range.
     */
    public function scopeDateBetween(Builder $query, string $startDate, string $endDate): Builder
    {
        return $query->whereBetween('date', [$startDate, $endDate]);
    }

    /**
     * Scope for filtering by month.
     */
    public function scopeForMonth(Builder $query, int $year, int $month): Builder
    {
        return $query->whereYear('date', $year)->whereMonth('date', $month);
    }

    /**
     * Scope for filtering by year.
     */
    public function scopeForYear(Builder $query, int $year): Builder
    {
        return $query->whereYear('date', $year);
    }

    /**
     * Scope for deductible expenses only.
     */
    public function scopeDeductible(Builder $query): Builder
    {
        return $query->where('is_deductible', true);
    }

    /**
     * Get summary statistics for a period.
     */
    public static function getSummary(?int $year = null, ?int $month = null): array
    {
        $query = static::query();

        if ($year) {
            $query->whereYear('date', $year);
        }
        if ($month) {
            $query->whereMonth('date', $month);
        }

        $totals = $query->selectRaw('
            SUM(amount_ht) as total_ht,
            SUM(amount_vat) as total_vat,
            SUM(amount_ttc) as total_ttc,
            COUNT(*) as count
        ')->first();

        $byCategory = static::query()
            ->when($year, fn ($q) => $q->whereYear('date', $year))
            ->when($month, fn ($q) => $q->whereMonth('date', $month))
            ->selectRaw('category, SUM(amount_ht) as total_ht, SUM(amount_vat) as total_vat, COUNT(*) as count')
            ->groupBy('category')
            ->get()
            ->keyBy('category')
            ->toArray();

        return [
            'total_ht' => $totals->total_ht ?? 0,
            'total_vat' => $totals->total_vat ?? 0,
            'total_ttc' => $totals->total_ttc ?? 0,
            'count' => $totals->count ?? 0,
            'by_category' => $byCategory,
        ];
    }

    /**
     * Get monthly summary for a year.
     */
    public static function getMonthlySummary(int $year): array
    {
        $monthExpr = DatabaseHelper::month('date');

        return static::query()
            ->whereYear('date', $year)
            ->selectRaw("
                {$monthExpr} as month,
                SUM(amount_ht) as total_ht,
                SUM(amount_vat) as total_vat,
                SUM(amount_ttc) as total_ttc,
                COUNT(*) as count
            ")
            ->groupByRaw($monthExpr)
            ->orderByRaw($monthExpr)
            ->get()
            ->keyBy('month')
            ->toArray();
    }

    /**
     * Scope for global search.
     */
    public function scopeSearch(Builder $query, ?string $search): Builder
    {
        if (empty($search)) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($search) {
            $q->where('provider_name', 'like', "%{$search}%")
              ->orWhere('description', 'like', "%{$search}%");
        });
    }
}
