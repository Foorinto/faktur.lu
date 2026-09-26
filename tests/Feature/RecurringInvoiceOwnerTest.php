<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\RecurringInvoice;
use App\Models\User;
use Database\Seeders\PlansSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Le robot des factures récurrentes crée chaque facture avec son
 * propriétaire dès l'insertion.
 *
 * Constaté le 26/09/2026 : il insérait la facture sans propriétaire puis
 * l'attribuait au compte. En ligne de commande personne n'est connecté,
 * donc rien ne remplit user_id à la création ; SQLite (ici) l'acceptait,
 * MySQL refusait l'insertion et aucune facture récurrente n'a jamais été
 * générée en production. SQLite ne voyant rien, on regarde la facture au
 * moment même de sa création.
 */
class RecurringInvoiceOwnerTest extends TestCase
{
    use RefreshDatabase;

    public function test_la_facture_recurrente_nait_avec_son_proprietaire(): void
    {
        $this->seed(PlansSeeder::class);
        $user = User::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $client = Client::factory()->create(['user_id' => $user->id]);
        RecurringInvoice::create([
            'user_id' => $user->id, 'client_id' => $client->id, 'title' => 'Forfait mensuel',
            'frequency' => RecurringInvoice::FREQUENCY_MONTHLY, 'next_invoice_date' => now()->subDay()->toDateString(),
            'is_active' => true, 'auto_finalize' => false, 'auto_send' => false,
            'payment_delay_days' => 30, 'currency' => 'EUR',
        ]);

        $proprietaireALInsertion = [];
        Invoice::creating(function (Invoice $facture) use (&$proprietaireALInsertion) {
            $proprietaireALInsertion[] = $facture->user_id;
        });

        $this->artisan('recurring:generate')->assertExitCode(0);

        $this->assertSame([$user->id], $proprietaireALInsertion);
        $this->assertSame(1, Invoice::withoutUserScope()->where('user_id', $user->id)->count());
    }
}
