<?php

namespace App\Http\Requests\Api\V1;

use App\Models\QuoteItem;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreQuoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'client_id' => ['required', 'exists:clients,id'],
            'valid_until' => ['nullable', 'date', 'after_or_equal:today'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'currency' => ['nullable', 'string', 'size:3', Rule::in(['EUR', 'USD', 'GBP', 'CHF'])],
            'items' => ['nullable', 'array'],
            'items.*.title' => ['required_with:items', 'string', 'max:255'],
            'items.*.description' => ['nullable', 'string', 'max:2000'],
            'items.*.quantity' => ['required_with:items', 'numeric', 'min:0.0001'],
            'items.*.unit' => ['nullable', 'string', Rule::in(array_keys(QuoteItem::getUnits()))],
            'items.*.unit_price' => ['required_with:items', 'numeric', 'min:0'],
            'items.*.vat_rate' => ['required_with:items', 'numeric', Rule::in([0, 3, 8, 14, 17])],
        ];
    }

    public function messages(): array
    {
        return [
            'client_id.required' => 'Le client est obligatoire.',
            'client_id.exists' => 'Le client sélectionné n\'existe pas.',
            'valid_until.after_or_equal' => 'La date de validité doit être aujourd\'hui ou dans le futur.',
            'items.*.vat_rate.in' => 'Le taux de TVA doit être 0%, 3%, 8%, 14% ou 17%.',
        ];
    }
}
