<?php

namespace App\Http\Requests\Api\V1;

use App\Models\Client;
use App\Rules\BelongsToAuthUser;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateQuoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Only allow updating quotes that can be edited
        return $this->route('quote')->canEdit();
    }

    public function rules(): array
    {
        return [
            'client_id' => ['sometimes', 'required', 'integer', new BelongsToAuthUser(Client::class)],
            'valid_until' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'currency' => ['sometimes', 'string', 'size:3', Rule::in(['EUR', 'USD', 'GBP', 'CHF'])],
        ];
    }

    public function messages(): array
    {
        return [];
    }
}
