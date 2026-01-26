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

    public function rules(): array
    {
        return [
            'date' => ['required', 'date', 'before_or_equal:today'],
            'provider_name' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', Rule::in(array_keys(Expense::getCategories()))],
            'amount_ht' => ['required', 'numeric', 'min:0.01'],
            'vat_rate' => ['required', 'numeric', Rule::in([0, 3, 8, 14, 17])],
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
            'vat_rate.in' => 'Le taux de TVA doit être 0%, 3%, 8%, 14% ou 17%.',
            'attachment.mimes' => 'Le fichier doit être un PDF ou une image (JPG, PNG, WebP).',
            'attachment.max' => 'Le fichier ne doit pas dépasser 10 Mo.',
        ];
    }
}
