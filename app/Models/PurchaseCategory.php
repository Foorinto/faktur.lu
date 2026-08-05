<?php

namespace App\Models;

use App\Traits\BelongsToUser;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

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

        self::adoptOrphanKeys($user);
    }

    /**
     * Adopte les catégories orphelines trouvées dans les dépenses du compte.
     *
     * La liste des catégories a changé au fil des versions : des dépenses
     * portent des clés qui ne figurent plus nulle part — `office_supplies`,
     * `telecom`, `transport`. Elles s'affichaient sous leur clé brute et
     * n'apparaissaient dans aucun filtre, puisque rien ne les déclarait.
     *
     * Les créer comme catégories rend ces dépenses filtrables, et le libellé
     * renommable — ce qui est précisément l'objet de cette fonctionnalité. On
     * n'écrit toujours rien dans `expenses` : c'est la catégorie qui rejoint la
     * dépense, jamais l'inverse.
     */
    private static function adoptOrphanKeys(User $user): void
    {
        $known = static::withoutUserScope()->where('user_id', $user->id)->pluck('key')->all();

        $orphans = Expense::withoutUserScope()
            ->where('user_id', $user->id)
            ->whereNotNull('category')
            ->distinct()
            ->pluck('category')
            ->reject(fn ($key) => $key === '' || in_array($key, $known, true));

        $next = (int) static::withoutUserScope()->where('user_id', $user->id)->max('sort_order');

        // firstOrCreate et non create : le provisionnement se déclenche au
        // premier affichage de la page, et deux onglets ouverts ensemble
        // tenteraient la même insertion. Laravel rattrape alors la violation
        // d'unicité (createOrFirst) au lieu de renvoyer une erreur 500.
        foreach ($orphans as $key) {
            static::withoutUserScope()->firstOrCreate(
                ['user_id' => $user->id, 'key' => $key],
                [
                    // Un libellé lisible tiré de la clé, que l'utilisateur
                    // renommera : « office_supplies » devient « Office supplies ».
                    'label' => Str::ucfirst(str_replace('_', ' ', $key)),
                    'is_default' => false,
                    'is_active' => true,
                    'sort_order' => ++$next,
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
