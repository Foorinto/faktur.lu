<?php

namespace App\Rules;

use App\Models\Product;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Le parent d'une variante doit exister, appartenir au compte, et ne pas être
 * lui-même une variante (FEAT-120).
 *
 * La profondeur est limitée à un : sans cela le catalogue devient un arbre, son
 * affichage un problème, et « la famille de la famille » une question sans
 * réponse sur une facture.
 *
 * Deuxième interdit, quand on modifie un article : une famille qui porte déjà
 * des variantes ne peut pas devenir elle-même une variante. Ses propres
 * variantes se retrouveraient au deuxième niveau sans que personne l'ait voulu.
 */
class VariantParentIsValid implements ValidationRule
{
    public function __construct(private readonly ?int $productId = null) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (empty($value)) {
            return;
        }

        // Le scope global filtre déjà par utilisateur : un parent d'un autre
        // compte est introuvable, donc refusé.
        $parent = Product::find($value);

        if ($parent === null) {
            $fail(__('app.products.variant_parent_unknown'));

            return;
        }

        if ($parent->isVariant()) {
            $fail(__('app.products.variant_parent_is_variant'));

            return;
        }

        if ($this->productId !== null && (int) $value === $this->productId) {
            $fail(__('app.products.variant_parent_is_self'));

            return;
        }

        if ($this->productId !== null && Product::where('parent_id', $this->productId)->exists()) {
            $fail(__('app.products.variant_has_own_variants'));
        }
    }
}
