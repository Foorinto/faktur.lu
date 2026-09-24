<?php

namespace Tests\Feature;

use App\Mail\InvoiceMail;
use App\Models\BusinessSettings;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Envoi d'une facture avec copie à soi-même (retour du 2026-09-24).
 *
 * Le client recevait deux mails, dont un adressé à lui ET à l'utilisateur
 * avec le PDF en double : le même objet mail servait aux deux envois, et un
 * Mailable accumule ses destinataires et ré-attache ses pièces jointes à
 * chaque envoi. Chaque envoi a désormais son propre objet.
 */
class InvoiceEmailCopyTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Invoice $invoice;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['email' => 'moi@exemple.lu', 'email_verified_at' => now()]);
        $this->actingAs($this->user);
        BusinessSettings::factory()->create(['user_id' => $this->user->id]);

        $client = Client::factory()->create(['user_id' => $this->user->id, 'email' => 'client@exemple.lu']);
        $this->invoice = Invoice::factory()->create([
            'user_id' => $this->user->id,
            'client_id' => $client->id,
            'status' => Invoice::STATUS_FINALIZED,
            'issued_at' => now(),
        ]);
    }

    public function test_la_copie_a_soi_meme_part_dans_un_mail_separe_sans_le_client(): void
    {
        Mail::fake();

        $this->post(route('invoices.send-email', $this->invoice), [
            'recipient_email' => 'client@exemple.lu',
            'subject' => 'Votre facture',
            'send_copy_to_self' => true,
        ])->assertRedirect()->assertSessionHasNoErrors();

        Mail::assertSent(InvoiceMail::class, 2);
        $envois = Mail::sent(InvoiceMail::class)->values();

        // Deux objets distincts : un objet réutilisé cumule les destinataires
        // et ré-attache le PDF au second envoi.
        $this->assertNotSame($envois[0], $envois[1]);

        $this->assertCount(1, $envois[0]->to);
        $this->assertTrue($envois[0]->hasTo('client@exemple.lu'));
        $this->assertFalse($envois[0]->hasTo('moi@exemple.lu'));

        $this->assertCount(1, $envois[1]->to);
        $this->assertTrue($envois[1]->hasTo('moi@exemple.lu'));
        $this->assertFalse($envois[1]->hasTo('client@exemple.lu'));
    }

    public function test_chaque_mail_reellement_construit_a_un_destinataire_et_un_seul_pdf(): void
    {
        // Sans Mail::fake : le transport « array » construit vraiment les
        // messages, pièces jointes comprises. C'est ici que le PDF en double
        // se voyait.
        $this->post(route('invoices.send-email', $this->invoice), [
            'recipient_email' => 'client@exemple.lu',
            'subject' => 'Votre facture',
            'send_copy_to_self' => true,
        ])->assertRedirect()->assertSessionHasNoErrors();

        $messages = Mail::mailer()->getSymfonyTransport()->messages();
        $this->assertCount(2, $messages);

        $destinataires = $messages->map(fn ($m) => collect($m->getOriginalMessage()->getTo())->map->getAddress()->all())->all();
        $this->assertSame([['client@exemple.lu'], ['moi@exemple.lu']], $destinataires);

        foreach ($messages as $message) {
            $this->assertCount(1, $message->getOriginalMessage()->getAttachments(), 'un seul PDF par mail');
        }
    }

    public function test_sans_copie_un_seul_mail_part_au_client(): void
    {
        Mail::fake();

        $this->post(route('invoices.send-email', $this->invoice), [
            'recipient_email' => 'client@exemple.lu',
            'subject' => 'Votre facture',
        ])->assertRedirect()->assertSessionHasNoErrors();

        Mail::assertSent(InvoiceMail::class, 1);
        Mail::assertSent(InvoiceMail::class, fn (InvoiceMail $mail) => count($mail->to) === 1 && $mail->hasTo('client@exemple.lu'));
    }
}
