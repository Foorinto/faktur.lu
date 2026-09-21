<?php

namespace App\Http\Requests;

use App\Models\InvoiceItem;
use App\Models\Product;
use App\Rules\PcnAccountExists;
use App\Rules\VariantParentIsValid;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'designation' => ['required', 'string', 'max:255'],
            'parent_id' => ['nullable', 'integer', new VariantParentIsValid($this->route('product')?->id)],
            // Une variante sans libellé ne se distingue pas de ses soeurs.
            'variant_label' => ['nullable', 'required_with:parent_id', 'string', 'max:100'],
            // L'axe ne vit que sur la famille : « Nuance », « Taille », « Format ».
            'variant_axis_label' => ['nullable', 'string', 'max:50'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'description' => ['nullable', 'string', 'max:2000'],
            'reference' => ['nullable', 'string', 'max:100'],
            'type' => ['nullable', Rule::in(Product::TYPES)],
            'unit_price_ht' => ['required', 'numeric', 'min:0', 'max:9999999999'],
            'vat_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            // Compte du plan comptable normalisé, vérifié contre le catalogue :
            // un compte inventé ne se manifesterait qu'à l'export, chez la
            // fiduciaire, sur des écritures déjà émises.
            'pcn_account' => ['nullable', 'string', 'max:10', new PcnAccountExists],
            'unit' => ['nullable', 'string', Rule::in(array_keys(InvoiceItem::getUnits()))],
            'is_active' => ['boolean'],
            'track_stock' => ['boolean'],
            'stock_alert_threshold' => ['nullable', 'numeric', 'min:0', 'max:9999999999'],
        ];
    }

    protected function prepareForValidation(): void
    {
        // Suivi de stock réservé aux produits (FEAT-116).
        $tracks = $this->boolean('track_stock') && $this->input('type') === Product::TYPE_PRODUCT;

        $this->merge([
            'is_active' => $this->boolean('is_active', true),
            'track_stock' => $tracks,
            'stock_alert_threshold' => $tracks ? $this->input('stock_alert_threshold') : null,
        ]);
    }
}
