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
            // Acompte demandé à la commande : un pourcentage ou une somme.
            // ⚠️ Une demande, pas un encaissement : les totaux ne bougent pas.
            'deposit_type' => ['nullable', 'in:percent,amount'],
            'deposit_value' => ['nullable', 'numeric', 'min:0', 'max:999999'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'currency' => ['sometimes', 'string', 'size:3', Rule::in(['EUR', 'USD', 'GBP', 'CHF'])],
            'vat_mention' => ['nullable', 'string', 'max:50'],
            'custom_vat_mention' => ['nullable', 'string', 'max:500'],
            'footer_message' => ['nullable', 'string', 'max:10000'],
        ];
    }

    public function messages(): array
    {
        return [];
    }
}
