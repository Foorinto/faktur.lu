<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreClientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:500'],
            'postal_code' => ['nullable', 'string', 'max:10'],
            'city' => ['nullable', 'string', 'max:255'],
            'country_code' => ['required', 'string', 'size:2'],
            'vat_number' => [
                'nullable',
                'string',
                'max:20',
                'regex:/^[A-Z]{2}[A-Z0-9]{2,18}$/',
            ],
            'registration_number' => ['nullable', 'string', 'max:50'],
            'type' => ['required', Rule::in(['b2b', 'b2c'])],
            'currency' => ['required', 'string', 'size:3', Rule::in(['EUR', 'USD', 'GBP', 'CHF'])],
            'phone' => ['nullable', 'string', 'max:20'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'locale' => ['nullable', 'string', Rule::in(['fr', 'de', 'en', 'lb'])],
        ];
    }

    public function messages(): array
    {
        return [
            'vat_number.regex' => 'Le numéro de TVA doit être au format européen (ex: LU12345678, FR12345678901).',
            'currency.in' => 'La devise doit être EUR, USD, GBP ou CHF.',
            'type.in' => 'Le type doit être b2b (Entreprise) ou b2c (Particulier).',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nom',
            'contact_name' => 'nom du contact',
            'email' => 'email',
            'address' => 'adresse',
            'postal_code' => 'code postal',
            'city' => 'ville',
            'country_code' => 'pays',
            'vat_number' => 'numéro de TVA',
            'registration_number' => 'numéro d\'identification',
            'type' => 'type de client',
            'currency' => 'devise',
            'phone' => 'téléphone',
            'notes' => 'notes',
        ];
    }
}
