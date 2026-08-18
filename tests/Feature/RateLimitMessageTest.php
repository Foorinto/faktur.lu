<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Une limite de débit doit se dire, pas se déguiser en panne.
 *
 * Nos limiteurs définissent tous leur propre réponse — « Trop de tentatives
 * d'inscription. », par exemple. Laravel remonte alors cette réponse à travers
 * la pile au moyen d'une `HttpResponseException`, qui n'est pas une erreur mais
 * un mécanisme de contrôle de flux.
 *
 * Le filet global l'ignorait dans sa liste d'exclusions. Il l'attrapait donc
 * comme une exception imprévue et la remplaçait par « Une erreur inattendue
 * s'est produite… code XXXX-YYYY ». Conséquences : le message écrit pour
 * l'utilisateur n'atteignait personne, celui-ci croyait à une panne, et il
 * contactait le support en citant un code qui ne désignait qu'une limite
 * volontaire.
 *
 * Constaté sur l'inscription le 2026-08-18, mais le défaut valait pour la
 * connexion, la réinitialisation de mot de passe, la 2FA et le formulaire de
 * contact.
 */
class RateLimitMessageTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Signature de ce que produit le filet global : un code de référence de la
     * forme XXXX-YYYY. On vise le code et non la phrase, qui change de langue.
     */
    private const CODE_DE_REFERENCE = '/\b[A-Z0-9]{4}-[A-Z0-9]{4}\b/';

    private function inscrire(string $email): \Illuminate\Testing\TestResponse
    {
        return $this->post('/register', [
            'name' => 'Essai',
            'email' => $email,
            'password' => 'Alex1234$$$$',
            'password_confirmation' => 'Alex1234$$$$',
            'terms' => true,
            'dpa' => true,
            'homepage_url' => '',
            'form_loaded_at' => microtime(true) - 30,
        ]);
    }

    public function test_a_throttled_registration_states_its_reason(): void
    {
        // APP_DEBUG=false : c'est la condition d'activation du filet global.
        config(['app.debug' => false]);

        // Le limiteur autorise trois inscriptions par heure et par IP.
        for ($i = 1; $i <= 3; $i++) {
            $this->inscrire("essai{$i}@exemple.lu");
        }

        $reponse = $this->inscrire('quatrieme@exemple.lu');

        $reponse->assertStatus(429);
        $reponse->assertSee("Trop de tentatives d'inscription.", false);
        $this->assertDoesNotMatchRegularExpression(self::CODE_DE_REFERENCE, $reponse->getContent(),
            'Une limite de débit ne doit pas produire de code de support : ce n\'est pas une panne.');
    }

    /** Le filet doit continuer de couvrir ce pour quoi il existe. */
    public function test_an_unexpected_error_still_gets_a_reference_code(): void
    {
        config(['app.debug' => false]);

        \Illuminate\Support\Facades\Route::get('/_essai-panne', fn () => throw new \RuntimeException('base de données injoignable'))
            ->middleware('web');

        $this->from('/fr')->get('/_essai-panne');

        $this->assertMatchesRegularExpression(
            self::CODE_DE_REFERENCE,
            (string) session('error'),
            'Une exception imprévue doit toujours produire un message avec code de référence.'
        );
    }
}
