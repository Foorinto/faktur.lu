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
            'issued_at' => ['nullable', 'date'],
            'due_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'footer_message' => ['nullable', 'string', 'max:10000'],
            // FEAT-098 : moyens de paiement propres à cette facture.
            // Texte libre assumé : le réglage d'entreprise l'est déjà, et le
            // PDF affiche tel quel ce qu'il ne sait pas traduire (« Wero »).
            'payment_methods' => ['nullable', 'array', 'max:10'],
            'payment_methods.*' => ['string', 'max:60'],
            'vat_mention' => ['nullable', 'string', Rule::in(['franchise', 'reverse_charge', 'intra_eu', 'export', 'none', 'other'])],
            'custom_vat_mention' => ['nullable', 'string', 'max:1000'],
            'currency' => ['sometimes', 'string', 'size:3', Rule::in(['EUR', 'USD', 'GBP', 'CHF'])],
            'retention_guarantee_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'retention_release_date' => ['nullable', 'date'],
        ];
    }

    public function messages(): array
    {
        return [];
    }

    /**
     * Un tableau de moyens de paiement vide veut dire la même chose que rien du
     * tout : « suis le réglage d'entreprise ». On le ramène à null pour n'avoir
     * qu'une seule écriture de cette intention en base (FEAT-098).
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('payment_methods') && $this->input('payment_methods') === []) {
            $this->merge(['payment_methods' => null]);
        }
    }}
