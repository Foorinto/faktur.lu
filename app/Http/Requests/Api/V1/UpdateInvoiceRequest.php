<?php

namespace App\Http\Requests\Api\V1;

use App\Models\Client;
use App\Rules\BelongsToAuthUser;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Only allow updating draft invoices
        return $this->route('invoice')->isDraft();
    }

    public function rules(): array
    {
        return [
            'client_id' => ['sometimes', 'required', 'integer', new BelongsToAuthUser(Client::class)],
            'title' => ['nullable', 'string', 'max:255'],
            'due_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'footer_message' => ['nullable', 'string', 'max:1000'],
            'vat_mention' => ['nullable', 'string', Rule::in(['franchise', 'reverse_charge', 'intra_eu', 'export', 'none', 'other'])],
            'custom_vat_mention' => ['nullable', 'string', 'max:1000'],
            'currency' => ['sometimes', 'string', 'size:3', Rule::in(['EUR', 'USD', 'GBP', 'CHF'])],
        ];
    }

    public function messages(): array
    {
        return [];
    }
}
