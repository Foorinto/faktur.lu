<?php

namespace App\Http\Requests;

use App\Models\InvoiceItem;
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
            'description' => ['nullable', 'string', 'max:2000'],
            'reference' => ['nullable', 'string', 'max:100'],
            'type' => ['nullable', Rule::in(\App\Models\Product::TYPES)],
            'unit_price_ht' => ['required', 'numeric', 'min:0', 'max:9999999999'],
            'vat_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            // Compte du plan comptable normalisé, vérifié contre le catalogue :
            // un compte inventé ne se manifesterait qu'à l'export, chez la
            // fiduciaire, sur des écritures déjà émises.
            'pcn_account' => ['nullable', 'string', 'max:10', new \App\Rules\PcnAccountExists],
            'unit' => ['nullable', 'string', Rule::in(array_keys(InvoiceItem::getUnits()))],
            'is_active' => ['boolean'],
            'track_stock' => ['boolean'],
            'stock_alert_threshold' => ['nullable', 'numeric', 'min:0', 'max:9999999999'],
        ];
    }

    protected function prepareForValidation(): void
    {
        // Suivi de stock réservé aux produits (FEAT-116).
        $tracks = $this->boolean('track_stock') && $this->input('type') === \App\Models\Product::TYPE_PRODUCT;

        $this->merge([
            'is_active' => $this->boolean('is_active', true),
            'track_stock' => $tracks,
            'stock_alert_threshold' => $tracks ? $this->input('stock_alert_threshold') : null,
        ]);
    }
}
