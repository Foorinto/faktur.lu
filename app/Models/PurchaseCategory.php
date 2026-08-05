<?php

namespace App\Models;

use App\Traits\BelongsToUser;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Catégorie de dépense définie par l'utilisateur.
 *
 * Remplace la liste figée de `Expense::getCategories()`, pensée pour un
 * freelance du numérique et sans loyer ni charges.
 *
 * Deux notions à ne pas confondre :
 * - la **clé** (`key`) est ce que stocke `expenses.category`. Elle est
 *   immuable : la changer détacherait toutes les dépenses passées ;
 * - le **libellé** (`label`) est ce que voit l'utilisateur, librement modifiable.
 */
class PurchaseCategory extends Model
{
    use BelongsToUser, HasFactory;

    protected $fillable = [
        'key',
        'label',
        'pcn_account',
        'is_default',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Les neuf catégories historiques, dans leur ordre d'origine.
     *
     * Les clés reprennent à l'identique les constantes `Expense::CATEGORY_*` :
     * c'est ce qui permet aux dépenses déjà enregistrées de rester rattachées
     * sans aucune migration de données.
     */
    public const DEFAULT_KEYS = [
        'hardware',
        'software',
        'hosting',
        'office',
        'travel',
        'training',
        'professional_services',
        'telecommunications',
        'other',
    ];

    /**
     * Toute écriture invalide la mémoire de requête d'Expense : sans cela, une
     * catégorie renommée continuerait d'apparaître sous son ancien libellé
     * jusqu'à la fin de la requête.
     */
    protected static function booted(): void
    {
        static::saved(fn () => Expense::forgetCategoryMapCache());
        static::deleted(fn () => Expense::forgetCategoryMapCache());
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('label');
    }

    /**
     * Provisionne les catégories par défaut d'un compte, une fois pour toutes.
     *
     * Appelée paresseusement au premier accès plutôt que par une migration :
     * un compte qui n'ouvre jamais la page ne se voit rien créer, et
     * `Expense::getCategories()` retombe alors sur les constantes traduites.
     * Rien ne casse pour lui.
     *
     * Idempotente : `firstOrCreate` sur la contrainte unique (user_id, key).
     */
    public static function ensureDefaultsFor(User $user): void
    {
        // La locale de l'utilisateur au moment du provisionnement fige les
        // libellés. C'est assumé : ce sont désormais SES libellés, qu'il peut
        // renommer — les retraduire à chaque changement de langue écraserait
        // ses modifications.
        foreach (self::DEFAULT_KEYS as $index => $key) {
            static::withoutUserScope()->firstOrCreate(
                ['user_id' => $user->id, 'key' => $key],
                [
                    'label' => __('app.expense_categories.'.$key),
                    'is_default' => true,
                    'is_active' => true,
                    'sort_order' => $index,
                ]
            );
        }
    }

    /**
     * Catégories d'un compte sous forme [clé => libellé], provisionnement compris.
     *
     * @return array<string, string>
     */
    public static function mapFor(User $user, bool $activeOnly = true): array
    {
        static::ensureDefaultsFor($user);

        return static::withoutUserScope()
            ->where('user_id', $user->id)
            ->when($activeOnly, fn ($q) => $q->active())
            ->ordered()
            ->pluck('label', 'key')
            ->all();
    }
}
