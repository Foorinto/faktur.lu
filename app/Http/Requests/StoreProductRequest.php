<?php

namespace App\Http\Requests;

use App\Models\InvoiceItem;
use App\Rules\SalesVatRateAllowed;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductRequest extends FormRequest
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
            'vat_rate' => ['required', 'numeric', 'min:0', 'max:100', new SalesVatRateAllowed],
            // Compte du plan comptable normalisé, vérifié contre le catalogue :
            // un compte inventé ne se manifesterait qu'à l'export, chez la
            // fiduciaire, sur des écritures déjà émises.
            'pcn_account' => ['nullable', 'string', 'max:10', new \App\Rules\PcnAccountExists],
            'unit' => ['nullable', 'string', Rule::in(array_keys(InvoiceItem::getUnits()))],
            'is_active' => ['boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->boolean('is_active', true),
        ]);
    }
}
