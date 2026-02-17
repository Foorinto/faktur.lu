<?php

namespace App\Http\Requests\Api\V1;

use App\Models\Client;
use App\Models\InvoiceItem;
use App\Rules\BelongsToAuthUser;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'client_id' => ['required', 'integer', new BelongsToAuthUser(Client::class)],
            'title' => ['nullable', 'string', 'max:255'],
            'due_at' => ['nullable', 'date', 'after_or_equal:today'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'currency' => ['nullable', 'string', 'size:3', Rule::in(['EUR', 'USD', 'GBP', 'CHF'])],
            'items' => ['nullable', 'array'],
            'items.*.title' => ['required_with:items', 'string', 'max:255'],
            'items.*.description' => ['nullable', 'string', 'max:2000'],
            'items.*.quantity' => ['required_with:items', 'numeric', 'min:0.0001'],
            'items.*.unit' => ['nullable', 'string', Rule::in(array_keys(InvoiceItem::getUnits()))],
            'items.*.unit_price' => ['required_with:items', 'numeric', 'min:0'],
            // Allow any valid VAT rate (0-100%) - country-specific rates are validated at display level
            'items.*.vat_rate' => ['required_with:items', 'numeric', 'min:0', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'client_id.required' => 'Le client est obligatoire.',
            'items.*.vat_rate.min' => 'Le taux de TVA ne peut pas être négatif.',
            'items.*.vat_rate.max' => 'Le taux de TVA ne peut pas dépasser 100%.',
        ];
    }
}
