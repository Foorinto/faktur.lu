<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\PurchaseCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Gestion des catégories de dépenses.
 *
 * Le cloisonnement repose sur le scope global de BelongsToUser : une catégorie
 * appartenant à un autre compte ne correspond à aucune ligne, plutôt que d'être
 * modifiée.
 */
class PurchaseCategoryController extends Controller
{
    public function index(Request $request): Response
    {
        PurchaseCategory::ensureDefaultsFor($request->user());

        // Le nombre de dépenses par catégorie décide de ce que l'interface
        // propose : une catégorie utilisée ne peut être que désactivée.
        $usage = Expense::query()
            ->selectRaw('category, COUNT(*) as total')
            ->groupBy('category')
            ->pluck('total', 'category');

        $categories = PurchaseCategory::ordered()->get()->map(fn ($category) => [
            'id' => $category->id,
            'key' => $category->key,
            'label' => $category->label,
            'pcn_account' => $category->pcn_account,
            'is_default' => $category->is_default,
            'is_active' => $category->is_active,
            'sort_order' => $category->sort_order,
            'expenses_count' => (int) ($usage[$category->key] ?? 0),
        ]);

        return Inertia::render('Settings/PurchaseCategories/Index', [
            'categories' => $categories,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'label' => ['required', 'string', 'max:100'],
            'pcn_account' => ['nullable', 'string', 'max:10'],
        ]);

        PurchaseCategory::create([
            'key' => $this->uniqueKey($request, $validated['label']),
            'label' => $validated['label'],
            'pcn_account' => $validated['pcn_account'] ?? null,
            'is_default' => false,
            'is_active' => true,
            'sort_order' => (int) PurchaseCategory::max('sort_order') + 1,
        ]);

        return back()->with('success', __('app.purchase_categories.created'));
    }

    /**
     * La clé n'est JAMAIS modifiable : elle est ce que stocke
     * `expenses.category`, donc l'ancre de tout l'historique. Le libellé, lui,
     * se renomme librement — y compris sur une catégorie par défaut.
     */
    public function update(Request $request, PurchaseCategory $purchaseCategory): RedirectResponse
    {
        $validated = $request->validate([
            'label' => ['required', 'string', 'max:100'],
            'pcn_account' => ['nullable', 'string', 'max:10'],
            'is_active' => ['boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ]);

        $purchaseCategory->update($validated);

        return back()->with('success', __('app.purchase_categories.updated'));
    }

    /**
     * Supprime une catégorie — à condition qu'aucune dépense ne s'y rattache.
     *
     * Autrement, les dépenses concernées pointeraient vers le vide et
     * perdraient leur libellé. Dans ce cas l'interface propose la
     * désactivation, qui les retire de la saisie sans toucher à l'historique.
     */
    public function destroy(PurchaseCategory $purchaseCategory): RedirectResponse
    {
        if (Expense::where('category', $purchaseCategory->key)->exists()) {
            return back()->with('error', __('app.purchase_categories.in_use'));
        }

        $purchaseCategory->delete();

        return back()->with('success', __('app.purchase_categories.deleted'));
    }

    /**
     * Dérive une clé stable du libellé, unique pour ce compte.
     *
     * Le suffixe numérique évite la collision quand deux libellés produisent le
     * même identifiant — « Loyer » et « loyer » par exemple.
     */
    private function uniqueKey(Request $request, string $label): string
    {
        $base = Str::slug($label, '_') ?: 'categorie';
        $base = Str::limit($base, 50, '');
        $key = $base;
        $suffix = 2;

        while (PurchaseCategory::where('key', $key)->exists()) {
            $key = $base.'_'.$suffix++;
        }

        return $key;
    }
}
