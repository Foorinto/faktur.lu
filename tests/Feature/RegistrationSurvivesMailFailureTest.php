<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Notifications\VerifyEmailNotification;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
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

    /**
     * Le revers du filet : en avalant les exceptions d'envoi, on court le risque
     * que les mails cessent de partir sans que rien ne le signale. Ce test tient
     * l'autre bout — le cas nominal doit continuer d'envoyer.
     *
     * Noter la différence de traitement, qui n'est pas anodine. La vérification
     * d'adresse part de façon SYNCHRONE : c'est elle qui aurait fait échouer une
     * inscription lors d'une panne SMTP en production. La notification
     * d'administration, elle, est mise en file — elle ne touche pas au réseau
     * pendant la requête, sauf en local où la connexion `sync` exécute tout
     * immédiatement.
     */
    public function test_a_working_mail_server_still_sends_both(): void
    {
        config(['admin.support_email' => 'admin@faktur.lu']);

        Notification::fake();
        Mail::fake();

        $this->inscrire()->assertRedirect(route('register.thank-you'));

        $utilisateur = User::where('email', 'malgre-la-panne@exemple.lu')->firstOrFail();

        Notification::assertSentTo($utilisateur, VerifyEmailNotification::class);
        Mail::assertQueued(\App\Mail\NewUserRegisteredNotification::class);
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
