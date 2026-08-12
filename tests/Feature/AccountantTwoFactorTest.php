<?php

namespace Tests\Feature;

use App\Models\Accountant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Laravel\Fortify\Actions\EnableTwoFactorAuthentication;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

/**
 * Second facteur du portail comptable.
 *
 * Un comptable accède aux données de plusieurs entreprises : sur ce compte,
 * le mot de passe seul protège plus de monde qu'ailleurs. Le second facteur y
 * est donc imposé, et non proposé.
 *
 * Ce qui se joue ici tient en deux affirmations. Tant que le second facteur
 * n'est pas confirmé, aucune donnée n'est atteignable. Et une fois confirmé,
 * le mot de passe seul n'ouvre plus la session — sans quoi le dispositif ne
 * serait qu'une formalité posée sur un facteur unique.
 */
class AccountantTwoFactorTest extends TestCase
{
    use RefreshDatabase;

    private const MOT_DE_PASSE = 'Correct-Horse-Battery-42!';

    private function comptable(bool $avecDoubleFacteur = false): Accountant
    {
        $comptable = Accountant::create([
            'name' => 'Fiduciaire Test',
            'email' => 'comptable@example.test',
            'password' => Hash::make(self::MOT_DE_PASSE),
            'email_verified_at' => now(),
        ]);

        if ($avecDoubleFacteur) {
            app(EnableTwoFactorAuthentication::class)($comptable);
            $comptable->forceFill(['two_factor_confirmed_at' => now()])->save();
            $comptable->refresh();
        }

        return $comptable;
    }

    private function codeValide(Accountant $comptable): string
    {
        return app(Google2FA::class)->getCurrentOtp(decrypt($comptable->two_factor_secret));
    }

    /**
     * Ouvre une session comptable SANS déplacer le guard par défaut.
     *
     * `actingAs($x, 'accountant')` ferait de ce guard le guard courant, ce que
     * la production ne fait jamais : le portail comptable vit à côté du guard
     * « web », il ne le remplace pas. Le raccourci ferait passer des tests sur
     * une configuration qui n'existe nulle part.
     */
    private function connecte(Accountant $comptable): static
    {
        Auth::guard('accountant')->setUser($comptable);

        return $this;
    }

    public function test_a_password_alone_no_longer_opens_a_session(): void
    {
        $comptable = $this->comptable(avecDoubleFacteur: true);

        $this->post(route('accountant.login.submit'), [
            'email' => $comptable->email,
            'password' => self::MOT_DE_PASSE,
        ])->assertRedirect(route('accountant.two-factor.challenge'));

        $this->assertGuest('accountant');
    }

    public function test_the_right_code_opens_the_session(): void
    {
        $comptable = $this->comptable(avecDoubleFacteur: true);

        $this->post(route('accountant.login.submit'), [
            'email' => $comptable->email,
            'password' => self::MOT_DE_PASSE,
        ]);

        $this->post(route('accountant.two-factor.verify'), [
            'code' => $this->codeValide($comptable),
        ])->assertRedirect(route('accountant.dashboard'));

        $this->assertAuthenticatedAs($comptable, 'accountant');
    }

    public function test_a_wrong_code_leaves_the_door_shut(): void
    {
        $comptable = $this->comptable(avecDoubleFacteur: true);

        $this->post(route('accountant.login.submit'), [
            'email' => $comptable->email,
            'password' => self::MOT_DE_PASSE,
        ]);

        $this->post(route('accountant.two-factor.verify'), ['code' => '000000'])
            ->assertSessionHasErrors('code');

        $this->assertGuest('accountant');
    }

    /**
     * Le défi ne doit pas être atteignable sans avoir prouvé le mot de passe :
     * ce serait une porte ouverte sur la seule connaissance d'un code à six
     * chiffres.
     */
    public function test_the_challenge_cannot_be_reached_without_a_password(): void
    {
        $this->get(route('accountant.two-factor.challenge'))
            ->assertRedirect(route('accountant.login'));
    }

    public function test_a_recovery_code_works_once_and_only_once(): void
    {
        $comptable = $this->comptable(avecDoubleFacteur: true);
        $code = $comptable->recoveryCodes()[0];

        $this->post(route('accountant.login.submit'), [
            'email' => $comptable->email,
            'password' => self::MOT_DE_PASSE,
        ]);

        $this->post(route('accountant.two-factor.verify'), ['recovery_code' => $code])
            ->assertRedirect(route('accountant.dashboard'));
        $this->assertAuthenticatedAs($comptable, 'accountant');

        // Le même code, une seconde fois, doit être refusé.
        $this->post(route('accountant.logout'));
        $this->post(route('accountant.login.submit'), [
            'email' => $comptable->email,
            'password' => self::MOT_DE_PASSE,
        ]);

        $this->post(route('accountant.two-factor.verify'), ['recovery_code' => $code])
            ->assertSessionHasErrors('code');
        $this->assertGuest('accountant');
    }

    public function test_an_accountant_without_two_factor_reaches_no_client_data(): void
    {
        $comptable = $this->comptable();

        $this->connecte($comptable)
            ->get(route('accountant.dashboard'))
            ->assertRedirect(route('accountant.two-factor.enroll'));
    }

    /**
     * L'écran d'enrôlement doit rester accessible, sinon la redirection
     * tournerait en boucle et le compte serait enfermé dehors.
     */
    public function test_the_enrollment_screen_stays_reachable(): void
    {
        $comptable = $this->comptable();

        $this->connecte($comptable)
            ->get(route('accountant.two-factor.enroll'))
            ->assertOk();

        $this->assertNotNull($comptable->fresh()->two_factor_secret, 'Un secret doit être généré pour afficher le QR code.');
        $this->assertFalse(
            $comptable->fresh()->hasEnabledTwoFactorAuthentication(),
            'Un secret non confirmé ne doit pas valoir activation.'
        );
    }

    public function test_confirming_the_enrollment_opens_the_portal(): void
    {
        $comptable = $this->comptable();

        $this->connecte($comptable)->get(route('accountant.two-factor.enroll'));
        $comptable->refresh();

        $this->connecte($comptable)
            ->post(route('accountant.two-factor.confirm'), ['code' => $this->codeValide($comptable)])
            ->assertRedirect(route('accountant.two-factor.recovery'));

        $this->assertTrue($comptable->fresh()->hasEnabledTwoFactorAuthentication());

        $this->connecte($comptable->fresh())
            ->get(route('accountant.dashboard'))
            ->assertOk();
    }

    public function test_logging_out_is_always_possible(): void
    {
        $comptable = $this->comptable();

        $this->connecte($comptable)
            ->post(route('accountant.logout'))
            ->assertRedirect(route('accountant.login'));

        $this->assertGuest('accountant');
    }
}
