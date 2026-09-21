<?php

namespace App\Http\Requests;

use App\Auth\Reauthenticator;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ProfileUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    /**
     * L'adresse e-mail soumise diffère-t-elle de celle du compte ?
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function () {
            if (! $this->changeDAdresse()) {
                return;
            }

            // Lève une ValidationException portée par `current_password` ou
            // `two_factor_code` : la modale du profil affiche l'un et l'autre.
            app(Reauthenticator::class)->verify(
                $this->user(),
                $this->input('current_password'),
                $this->input('two_factor_code'),
            );
        });
    }

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
            'business_sector' => ['sometimes', 'nullable', 'string', Rule::in(User::BUSINESS_SECTORS)],
            // Changer l'adresse e-mail exige le mot de passe courant, et le code
            // 2FA si elle est active : une session ouverte mais volée ne doit
            // pas pouvoir préparer une prise de contrôle en silence. La
            // vérification elle-même vit dans `withValidator`, par le
            // Reauthenticator commun à tous les gestes sensibles.
            //
            // ⚠️ Ces règles ne doivent s'appliquer QUE dans ce cas. Écrites à
            // côté d'un `requiredIf`, elles s'évaluaient aussi quand l'adresse
            // ne changeait pas : le formulaire envoie toujours le champ, vide,
            // que `ConvertEmptyStringsToNull` transforme en null — présent,
            // donc validé, donc refusé. Changer son nom ou sa langue répondait
            // « Le mot de passe est incorrect » sans que rien ne l'ait demandé.
            'current_password' => $this->changeDAdresse() ? ['required'] : ['nullable'],
            'two_factor_code' => ['nullable', 'string'],
        ];
    }
}
