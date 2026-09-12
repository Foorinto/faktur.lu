<?php

namespace Tests\Feature\Security;

use App\Models\User;
use App\Notifications\EmailChangedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * AUTH-1 — changement d'e-mail sans mot de passe courant ni notification.
 *
 * Une session ouverte suffisait à changer l'adresse (et donc à préparer une
 * prise de contrôle). Désormais le mot de passe courant est exigé, et
 * l'ancienne adresse est prévenue.
 */
class EmailChangeRequiresPasswordTest extends TestCase
{
    use RefreshDatabase;

    private const MDP = 'Correct-Horse-Battery-42!';

    private function user(): User
    {
        return User::factory()->create([
            'email' => 'titulaire@example.com',
            'password' => Hash::make(self::MDP),
            'email_verified_at' => now(),
        ]);
    }

    public function test_email_change_without_password_is_rejected(): void
    {
        $user = $this->user();

        $this->actingAs($user)
            ->patch(route('profile.update'), [
                'name' => $user->name,
                'email' => 'pirate@example.com',
            ])
            ->assertSessionHasErrors('current_password');

        $this->assertSame('titulaire@example.com', $user->fresh()->email);
    }

    public function test_email_change_with_password_succeeds_and_notifies_old_address(): void
    {
        Notification::fake();
        $user = $this->user();

        $this->actingAs($user)
            ->patch(route('profile.update'), [
                'name' => $user->name,
                'email' => 'nouvelle@example.com',
                'current_password' => self::MDP,
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame('nouvelle@example.com', $user->fresh()->email);

        Notification::assertSentOnDemand(
            EmailChangedNotification::class,
            fn ($notification, $channels, $notifiable) => $notifiable->routes['mail'] === 'titulaire@example.com'
        );
    }

    public function test_changing_only_the_name_does_not_require_password(): void
    {
        $user = $this->user();

        $this->actingAs($user)
            ->patch(route('profile.update'), [
                'name' => 'Nouveau Nom',
                'email' => $user->email,
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame('Nouveau Nom', $user->fresh()->name);
    }
}
