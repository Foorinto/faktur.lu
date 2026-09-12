<?php

namespace Tests\Feature\Security;

use App\Mail\CrmEmail;
use App\Models\Client;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * CLI-1 — le corps d'un e-mail CRM, rédigé par l'utilisateur, est rendu en HTML
 * brut dans un mail sortant. Il doit être assaini pour qu'un script ou un lien
 * hostile ne parte pas depuis l'infrastructure de la plateforme.
 */
class CrmEmailSanitizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_crm_email_body_is_sanitised(): void
    {
        $user = User::factory()->create();
        $client = Client::factory()->create(['user_id' => $user->id]);

        $mail = new CrmEmail(
            client: $client,
            emailSubject: 'Bonjour',
            emailBody: '<p>Bonjour</p><script>alert(1)</script><a href="javascript:evil()">x</a>',
        );

        $rendu = $mail->render();

        $this->assertStringNotContainsString('<script>', $rendu);
        $this->assertStringNotContainsString('javascript:', $rendu);
        $this->assertStringContainsString('Bonjour', $rendu);
    }
}
