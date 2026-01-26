<?php

namespace App\Http\Requests\Api\V1;

use App\Models\QuoteItem;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreQuoteItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Only allow adding items to editable quotes
        return $this->route('quote')->canEdit();
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'quantity' => ['required', 'numeric', 'min:0.0001'],
            'unit' => ['nullable', 'string', Rule::in(array_keys(QuoteItem::getUnits()))],
            'unit_price' => ['required', 'numeric', 'min:0'],
            'vat_rate' => ['required', 'numeric', Rule::in([0, 3, 8, 14, 17])],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Le titre est obligatoire.',
            'title.max' => 'Le titre ne peut pas dépasser 255 caractères.',
            'description.max' => 'La description ne peut pas dépasser 2000 caractères.',
            'quantity.required' => 'La quantité est obligatoire.',
            'quantity.min' => 'La quantité doit être supérieure à 0.',
            'unit_price.required' => 'Le prix unitaire est obligatoire.',
            'vat_rate.required' => 'Le taux de TVA est obligatoire.',
            'vat_rate.in' => 'Le taux de TVA doit être 0%, 3%, 8%, 14% ou 17%.',
        ];
    }
}
