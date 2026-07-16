<?php

namespace Tests\Feature;

use App\Mail\NewSupportReplyNotification;
use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class SupportReplyNotifiesAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_reply_sends_notification_to_admin(): void
    {
        Mail::fake();
        config(['admin.support_email' => 'admin@faktur.lu']);

        $user = User::factory()->create();
        $ticket = SupportTicket::create([
            'user_id' => $user->id,
            'reference' => SupportTicket::generateReference(),
            'subject' => 'Question sur la facturation',
            'category' => 'general',
            'status' => 'open',
        ]);
        $ticket->messages()->create([
            'sender_type' => User::class,
            'sender_id' => $user->id,
            'content' => 'Message initial',
        ]);

        $this->actingAs($user)
            ->post(route('support.reply', ['locale' => 'fr', 'ticket' => $ticket]), [
                'message' => 'Merci, autre question complémentaire...',
            ])
            ->assertRedirect();

        Mail::assertQueued(NewSupportReplyNotification::class, function ($mail) {
            return $mail->hasTo('admin@faktur.lu');
        });
    }

    public function test_reply_notification_email_renders(): void
    {
        $user = User::factory()->create(['name' => 'Jean-Emmanuel']);
        $ticket = SupportTicket::create([
            'user_id' => $user->id,
            'reference' => SupportTicket::generateReference(),
            'subject' => 'Sauvegarde des articles',
            'category' => 'general',
            'status' => 'open',
        ]);
        $ticket->load('user');

        $rendered = (new NewSupportReplyNotification($ticket, 'Comment sauvegarder mes articles ?'))->render();

        $this->assertStringContainsString('Jean-Emmanuel', $rendered);
        $this->assertStringContainsString($ticket->reference, $rendered);
        $this->assertStringContainsString('Comment sauvegarder mes articles', $rendered);
    }

    public function test_no_admin_email_when_support_email_unset(): void
    {
        Mail::fake();
        config(['admin.support_email' => null]);

        $user = User::factory()->create();
        $ticket = SupportTicket::create([
            'user_id' => $user->id,
            'reference' => SupportTicket::generateReference(),
            'subject' => 'Autre question',
            'category' => 'general',
            'status' => 'open',
        ]);
        $ticket->messages()->create([
            'sender_type' => User::class,
            'sender_id' => $user->id,
            'content' => 'Message initial',
        ]);

        $this->actingAs($user)
            ->post(route('support.reply', ['locale' => 'fr', 'ticket' => $ticket]), [
                'message' => 'Réponse client',
            ]);

        Mail::assertNothingQueued();
    }
}
