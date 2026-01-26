<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBusinessSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'company_name' => ['required', 'string', 'max:255'],
            'legal_name' => ['required', 'string', 'max:255'],
            'address' => ['required', 'string', 'max:500'],
            'postal_code' => ['required', 'string', 'max:10'],
            'city' => ['required', 'string', 'max:255'],
            'country_code' => ['required', 'string', 'size:2'],
            'vat_number' => [
                'nullable',
                'string',
                'max:20',
                // Accepts EU VAT formats: LU12345678, FR12345678901, DE123456789, BE0123456789, etc.
                'regex:/^[A-Z]{2}[A-Z0-9]{2,12}$/',
                Rule::requiredIf($this->input('vat_regime') === 'assujetti'),
            ],
            // Luxembourg matricule is 13 digits (11 digits + 2 check digits)
            'matricule' => ['required', 'string', 'regex:/^\d{11,13}$/'],
            // IBAN: 2 letter country code + 2 check digits + up to 30 alphanumeric (allows spaces)
            'iban' => ['required', 'string', 'max:42', function ($attribute, $value, $fail) {
                // Remove spaces for validation
                $iban = strtoupper(preg_replace('/\s+/', '', $value));
                if (!preg_match('/^[A-Z]{2}\d{2}[A-Z0-9]{10,30}$/', $iban)) {
                    $fail('L\'IBAN n\'est pas valide.');
                }
            }],
            // BIC/SWIFT: 8-11 characters (flexible to accommodate various formats)
            'bic' => ['required', 'string', 'max:11', function ($attribute, $value, $fail) {
                $bic = strtoupper(preg_replace('/\s+/', '', $value));
                // Accept 8, 10, or 11 character BICs
                // Format: 4 letters (bank) + 2 letters (country) + 2-5 alphanumeric (location + optional branch)
                if (!preg_match('/^[A-Z]{4}[A-Z]{2}[A-Z0-9]{2,5}$/', $bic)) {
                    $fail('Le BIC/SWIFT n\'est pas valide (8 à 11 caractères).');
                }
            }],
            'bank_name' => ['nullable', 'string', 'max:255'],
            'vat_regime' => ['required', Rule::in(['assujetti', 'franchise'])],
            'default_hourly_rate' => ['nullable', 'numeric', 'min:0', 'max:9999.99'],
            'default_invoice_footer' => ['nullable', 'string', 'max:1000'],
            'default_vat_mention' => ['nullable', 'string', Rule::in(['franchise', 'reverse_charge', 'intra_eu', 'export', 'none', 'other'])],
            'default_custom_vat_mention' => ['nullable', 'string', 'max:1000'],
            'default_pdf_color' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['required', 'email', 'max:255'],
            'logo_path' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Normalize IBAN and BIC by removing spaces for storage
        if ($this->has('iban')) {
            $this->merge([
                'iban' => strtoupper(preg_replace('/\s+/', '', $this->input('iban'))),
            ]);
        }
        if ($this->has('bic')) {
            $this->merge([
                'bic' => strtoupper(preg_replace('/\s+/', '', $this->input('bic'))),
            ]);
        }
    }

    public function messages(): array
    {
        return [
            'matricule.regex' => 'Le matricule doit contenir entre 11 et 13 chiffres.',
            'vat_number.regex' => 'Le numéro de TVA doit être au format européen (ex: LU12345678, FR12345678901, DE123456789).',
            'vat_number.required_if' => 'Le numéro de TVA est obligatoire pour le régime assujetti.',
        ];
    }

    public function attributes(): array
    {
        return [
            'company_name' => 'nom commercial',
            'legal_name' => 'nom légal',
            'address' => 'adresse',
            'postal_code' => 'code postal',
            'city' => 'ville',
            'country_code' => 'code pays',
            'vat_number' => 'numéro de TVA',
            'matricule' => 'matricule',
            'iban' => 'IBAN',
            'bic' => 'BIC',
            'vat_regime' => 'régime TVA',
            'phone' => 'téléphone',
            'email' => 'email',
            'logo_path' => 'logo',
        ];
    }
}
