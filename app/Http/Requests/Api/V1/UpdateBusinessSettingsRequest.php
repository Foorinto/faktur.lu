<?php

namespace App\Http\Requests\Api\V1;

use App\Models\BusinessSettings;
use App\Services\DocumentNumberFormatter;
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
            'country_code' => ['required', 'string', 'size:2', Rule::in(config('billing.supported_countries', ['LU', 'FR', 'BE', 'DE']))],
            'activity_type' => ['nullable', 'string', Rule::in(['services', 'goods', 'mixed'])],
            'vat_number' => [
                Rule::requiredIf($this->input('vat_regime') === 'assujetti'),
                'nullable',
                'string',
                'max:20',
                // Accepts EU VAT formats: LU12345678, FR12345678901, DE123456789, BE0123456789, etc.
                function ($attribute, $value, $fail) {
                    // Only validate format if value is provided
                    if (!empty($value) && !preg_match('/^[A-Z]{2}[A-Z0-9]{2,12}$/', strtoupper($value))) {
                        $fail('Le numéro de TVA doit être au format européen (ex: LU12345678, FR12345678901).');
                    }
                },
            ],
            // Identifiant fiscal national - format selon le pays du vendeur.
            'matricule' => ['required', 'string', function ($attribute, $value, $fail) {
                $country = $this->input('country_code', 'LU');
                $pattern = match ($country) {
                    'FR' => '/^\d{9}$|^\d{14}$/',  // SIREN (9 chiffres) ou SIRET (14)
                    'BE' => '/^\d{10}$/',          // n° d'entreprise BCE
                    'LU' => '/^\d{11,13}$/',       // matricule luxembourgeois
                    default => '/^.{1,20}$/',      // Allemagne & autres : souple
                };
                if (!preg_match($pattern, $value)) {
                    $fail(match ($country) {
                        'FR' => 'Le SIREN doit contenir 9 chiffres, ou le SIRET 14 chiffres.',
                        'BE' => 'Le numéro d\'entreprise doit contenir 10 chiffres.',
                        'LU' => 'Le matricule doit contenir entre 11 et 13 chiffres.',
                        default => 'Identifiant fiscal invalide.',
                    });
                }
            }],
            // RCS number: Letter + digits (ex: A12345, B98765)
            'rcs_number' => ['nullable', 'string', 'max:20', 'regex:/^[A-Z]\d+$/'],
            // Establishment authorization from Ministry of Economy
            'establishment_authorization' => ['nullable', 'string', 'max:50'],
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
            'default_invoice_footer' => ['nullable', 'string', 'max:10000'],
            'default_vat_mention' => ['nullable', 'string', Rule::in(['franchise', 'reverse_charge', 'intra_eu', 'export', 'none', 'other'])],
            'default_custom_vat_mention' => ['nullable', 'string', 'max:1000'],
            'default_pdf_color' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            // FEAT-109 : un choix fermé, pas un coefficient libre. La valeur
            // atterrit sur un document légal ; une échelle arbitraire pourrait
            // le rendre illisible ou le faire déborder sans prévenir.
            'pdf_text_size' => ['nullable', Rule::in(array_keys(\App\Models\BusinessSettings::PDF_TEXT_SIZES))],
            'phone' => ['nullable', 'string', 'max:20'],
            'show_phone_on_invoice' => ['boolean'],
            'email' => ['required', 'email', 'max:255'],
            'show_email_on_invoice' => ['boolean'],
            'show_payment_qrcode' => ['boolean'],
            'default_payment_methods' => ['nullable', 'array', 'max:12'],
            'default_payment_methods.*' => ['string', 'max:60'],
            'payment_instructions' => ['nullable', 'string', 'max:10000'],
            'show_payment_conditions' => ['boolean'],
            'late_penalty_text' => ['nullable', 'string', 'max:255'],
            'recovery_fee_amount' => ['nullable', 'numeric', 'min:0', 'max:99999'],
            'discount_terms' => ['nullable', 'string', 'max:255'],
            'logo_path' => ['nullable', 'string', 'max:255'],
            'peppol_endpoint_scheme' => ['nullable', 'string', 'max:4'],
            'peppol_endpoint_id' => ['nullable', 'string', 'max:50'],
            // Custom numbering - fields are optional in the form, server enforces the lock
            // per type via withValidator() below so finalized years cannot be tampered with.
            'number_format' => ['nullable', 'string', 'max:100', function ($attribute, $value, $fail) {
                if ($value === null || $value === '') {
                    return;
                }
                $errors = DocumentNumberFormatter::validateTemplate($value);
                if (! empty($errors)) {
                    if (in_array('empty_template', $errors, true)) {
                        $fail('Le format ne peut pas être vide.');
                    } elseif (in_array('too_long', $errors, true)) {
                        $fail('Le format ne peut pas dépasser 100 caractères.');
                    } elseif (in_array('missing_number_placeholder', $errors, true)) {
                        $fail('Le format doit obligatoirement contenir {number}.');
                    } else {
                        $unknown = array_filter($errors, fn ($e) => str_starts_with($e, 'unknown_placeholder:'));
                        if (! empty($unknown)) {
                            $placeholder = explode(':', reset($unknown))[1] ?? '';
                            $fail('Placeholder inconnu : {' . $placeholder . '}. Placeholders supportés : {prefix}, {year}, {yy}, {month}, {day}, {number}, {client_name}.');
                        }
                    }
                }
            }],
            'invoice_prefix' => ['nullable', 'string', 'max:20', 'regex:/^[A-Za-z0-9\-_\/]+$/'],
            'credit_note_prefix' => ['nullable', 'string', 'max:20', 'regex:/^[A-Za-z0-9\-_\/]+$/'],
            'quote_prefix' => ['nullable', 'string', 'max:20', 'regex:/^[A-Za-z0-9\-_\/]+$/'],
            'invoice_starting_number' => ['nullable', 'integer', 'min:1', 'max:999999'],
            'credit_note_starting_number' => ['nullable', 'integer', 'min:1', 'max:999999'],
            'quote_starting_number' => ['nullable', 'integer', 'min:1', 'max:999999'],
            'number_padding' => ['nullable', 'integer', 'min:1', 'max:10'],
        ];
    }

    /**
     * After base validation, refuse any numbering change that would tamper with a year
     * that already has finalized documents (Article 63 LIVA continuous numbering).
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $settings = BusinessSettings::getInstance();
            if (! $settings || ! $settings->exists) {
                return;
            }

            $year = now()->year;
            $editability = $settings->numberingEditability($year);

            // Map each submitted field to the numbering type that gates it
            $perTypeFields = [
                BusinessSettings::NUMBERING_TYPE_INVOICE => ['invoice_prefix', 'invoice_starting_number'],
                BusinessSettings::NUMBERING_TYPE_CREDIT_NOTE => ['credit_note_prefix', 'credit_note_starting_number'],
                BusinessSettings::NUMBERING_TYPE_QUOTE => ['quote_prefix', 'quote_starting_number'],
            ];

            foreach ($perTypeFields as $type => $fields) {
                if ($editability[$type]) {
                    continue;
                }
                foreach ($fields as $field) {
                    if ($this->has($field) && (string) $this->input($field) !== (string) ($settings->{$field} ?? '')) {
                        $validator->errors()->add(
                            $field,
                            'Verrouillé pour ' . $year . ' : vous avez déjà émis des documents de ce type cette année.',
                        );
                    }
                }
            }

            // The format and padding are shared across all three document types;
            // they are locked as soon as ANY type is locked.
            $anyLocked = in_array(false, $editability, true);
            if ($anyLocked) {
                foreach (['number_format', 'number_padding'] as $sharedField) {
                    if ($this->has($sharedField) && (string) $this->input($sharedField) !== (string) ($settings->{$sharedField} ?? '')) {
                        $validator->errors()->add(
                            $sharedField,
                            'Verrouillé pour ' . $year . ' : modifier le format casserait la continuité de la numérotation déjà émise (Article 63 LIVA).',
                        );
                    }
                }
            }
        });
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
        // Normalize VAT number to uppercase
        if ($this->has('vat_number') && $this->input('vat_number')) {
            $this->merge([
                'vat_number' => strtoupper(preg_replace('/\s+/', '', $this->input('vat_number'))),
            ]);
        }
    }

    public function messages(): array
    {
        return [
            'matricule.regex' => 'Le matricule doit contenir entre 11 et 13 chiffres.',
            'vat_number.required' => 'Le numéro de TVA est obligatoire pour le régime assujetti.',
            'rcs_number.regex' => 'Le numéro RCS doit commencer par une lettre suivie de chiffres (ex: A12345).',
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
            'rcs_number' => 'numéro RCS',
            'establishment_authorization' => 'autorisation d\'établissement',
            'iban' => 'IBAN',
            'bic' => 'BIC',
            'vat_regime' => 'régime TVA',
            'phone' => 'téléphone',
            'email' => 'email',
            'logo_path' => 'logo',
        ];
    }
}
