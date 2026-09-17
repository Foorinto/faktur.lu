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
    /**
     * L'adresse e-mail soumise diffère-t-elle de celle du compte ?
     */
    private function changeDAdresse(): bool
    {
        $soumise = $this->input('email');

        return $soumise !== null
            && strtolower((string) $soumise) !== strtolower((string) $this->user()->email);
    }

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
            // Changer l'adresse e-mail exige le mot de passe courant : une
            // session ouverte mais volée ne doit pas pouvoir préparer une prise
            // de contrôle en silence.
            //
            // ⚠️ La règle `current_password` ne doit s'appliquer QUE dans ce cas.
            // Écrite à côté d'un `requiredIf`, elle s'évaluait aussi quand
            // l'adresse ne changeait pas : le formulaire envoie toujours le
            // champ, vide, que `ConvertEmptyStringsToNull` transforme en null —
            // présent, donc validé, donc refusé. Changer son nom ou sa langue
            // répondait « Le mot de passe est incorrect » sans que rien ne
            // l'ait demandé.
            'current_password' => $this->changeDAdresse()
                ? ['required', 'current_password']
                : ['nullable'],
        ];
    }
}
