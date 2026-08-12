<?php

namespace App\Http\Requests\Api\V1;

use App\Models\Expense;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreExpenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Le mode de saisie décide lequel des deux montants est obligatoire.
     *
     * Absent de la requête — l'API existante, par exemple — on retombe sur le
     * HT, qui était la seule saisie possible jusqu'ici.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'amount_input_mode' => $this->input('amount_input_mode', Expense::INPUT_HT),
        ]);
    }

    public function rules(): array
    {
        $isTtc = $this->input('amount_input_mode') === Expense::INPUT_TTC;

        return [
            'date' => ['required', 'date', 'before_or_equal:today'],
            'provider_name' => ['required', 'string', 'max:255'],
            'supplier_country' => ['nullable', 'string', Rule::in(array_column(Expense::getSupplierCountries(), 'code'))],
            'category' => ['required', 'string', Rule::in(array_keys(Expense::categoryMap(activeOnly: false)))],
            'amount_input_mode' => ['required', Rule::in([Expense::INPUT_HT, Expense::INPUT_TTC])],
            'amount_ht' => [$isTtc ? 'nullable' : 'required', 'numeric', 'min:0.01'],
            'amount_ttc' => [$isTtc ? 'required' : 'nullable', 'numeric', 'min:0.01'],
            'vat_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'vat_regime' => ['nullable', 'string', Rule::in(array_keys(Expense::getVatRegimes()))],
            'reverse_charge_vat_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'description' => ['nullable', 'string', 'max:2000'],
            'is_deductible' => ['boolean'],
            'payment_method' => ['nullable', 'string', Rule::in(array_keys(Expense::getPaymentMethods()))],
            'reference' => ['nullable', 'string', 'max:100'],
            'attachment' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:10240'], // 10MB max
        ];
    }

    public function messages(): array
    {
        return [
            'date.required' => 'La date est obligatoire.',
            'date.before_or_equal' => 'La date ne peut pas être dans le futur.',
            'provider_name.required' => 'Le nom du fournisseur est obligatoire.',
            'category.required' => 'La catégorie est obligatoire.',
            'category.in' => 'La catégorie sélectionnée n\'est pas valide.',
            'amount_ht.required' => 'Le montant HT est obligatoire.',
            'amount_ht.min' => 'Le montant HT doit être supérieur à 0.',
            'amount_ttc.required' => 'Le montant TTC est obligatoire.',
            'amount_ttc.min' => 'Le montant TTC doit être supérieur à 0.',
            'attachment.mimes' => 'Le fichier doit être un PDF ou une image (JPG, PNG, WebP).',
            'attachment.max' => 'Le fichier ne doit pas dépasser 10 Mo.',
        ];
    }
}
