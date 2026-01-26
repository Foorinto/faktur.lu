<?php

namespace App\Http\Requests\Api\V1;

use App\Models\QuoteItem;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateQuoteItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Only allow updating items on editable quotes
        return $this->route('quote')->canEdit();
    }

    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'quantity' => ['sometimes', 'required', 'numeric', 'min:0.0001'],
            'unit' => ['nullable', 'string', Rule::in(array_keys(QuoteItem::getUnits()))],
            'unit_price' => ['sometimes', 'required', 'numeric', 'min:0'],
            'vat_rate' => ['sometimes', 'required', 'numeric', Rule::in([0, 3, 8, 14, 17])],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'vat_rate.in' => 'Le taux de TVA doit être 0%, 3%, 8%, 14% ou 17%.',
        ];
    }
}
