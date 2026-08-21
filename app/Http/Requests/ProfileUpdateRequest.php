<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($this->user()->id),
            ],
            'locale' => ['sometimes', 'string', Rule::in(['fr', 'de', 'en', 'lb', 'pt'])],
            // Secteur d'activité, modifiable après coup — l'écran d'inscription
            // le promet. `sometimes` parce que le formulaire de profil ne le
            // porte pas toujours : l'omettre ne doit pas l'effacer.
            'business_sector' => ['sometimes', 'nullable', 'string', Rule::in(\App\Models\User::BUSINESS_SECTORS)],
        ];
    }
}
