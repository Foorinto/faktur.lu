<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Une inscription ne doit pas dépendre d'un serveur de messagerie.
 *
 * Le compte est créé avant les deux envois — vérification d'adresse et
 * notification d'administration. Laisser remonter l'exception laissait
 * l'utilisateur devant une erreur, non connecté, et avec une adresse désormais
 * prise : il ne pouvait même pas réessayer. Un relais SMTP indisponible
 * quelques minutes suffisait à perdre l'inscription ET l'adresse.
 *
 * Découvert le 2026-08-20 en constatant qu'aucun compte n'était créable en
 * local faute de configuration mail.
 */
class RegistrationSurvivesMailFailureTest extends TestCase
{
    use RefreshDatabase;

    private function inscrire(): \Illuminate\Testing\TestResponse
    {
        return $this->post('/register', [
            'name' => 'Malgré la panne',
            'email' => 'malgre-la-panne@exemple.lu',
            'password' => 'Alex1234$$$$',
            'password_confirmation' => 'Alex1234$$$$',
            'terms' => true,
            'dpa' => true,
            'homepage_url' => '',
            'form_loaded_at' => microtime(true) - 30,
        ]);
    }

    public function test_an_unreachable_mail_server_does_not_lose_the_account(): void
    {
        config(['admin.support_email' => 'admin@faktur.lu']);

        // Tout envoi échoue, comme le ferait un relais injoignable.
        Mail::shouldReceive('to')->andThrow(new \RuntimeException('Connection could not be established'));

        $this->inscrire()->assertRedirect(route('register.thank-you'));

        $utilisateur = User::where('email', 'malgre-la-panne@exemple.lu')->first();

        $this->assertNotNull($utilisateur, "Le compte doit exister malgré l'échec d'envoi.");
        $this->assertTrue(auth()->check(), "L'utilisateur doit être connecté, sinon son adresse est prise pour rien.");
        $this->assertSame($utilisateur->id, auth()->id());
        $this->assertSame('sector', $utilisateur->onboarding_step);
    }
}
