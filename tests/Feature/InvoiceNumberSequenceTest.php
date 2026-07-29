<?php

namespace Tests\Feature;

use App\Actions\GenerateInvoiceNumberAction;
use App\Models\BusinessSettings;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Numérotation séquentielle des factures.
 *
 * L'index unique (user_id, number) ignore deleted_at : une facture supprimée
 * conserve donc son numéro en base. Le calcul de séquence doit en tenir compte,
 * sous peine de réattribuer un numéro déjà pris — et de faire échouer la
 * finalisation sur une violation de contrainte.
 */
class InvoiceNumberSequenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_sequence_skips_numbers_consumed_by_deleted_invoices(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        BusinessSettings::factory()->create();
        $client = Client::factory()->create(['user_id' => $user->id]);

        // Trois factures émises, la dernière portant la séquence 3.
        foreach ([1, 2, 3] as $sequence) {
            Invoice::factory()->create([
                'user_id' => $user->id,
                'client_id' => $client->id,
                'status' => Invoice::STATUS_FINALIZED,
                'finalized_at' => now(),
                'sequence_number' => $sequence,
                'number' => 'INV-'.now()->year.'-'.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT),
            ]);
        }

        // La dernière est supprimée : son numéro reste dans l'index unique.
        // Le modèle interdit la suppression d'une facture finalisée : on simule
        // une ligne retirée hors parcours normal (admin, intervention directe).
        Invoice::withoutEvents(fn () => Invoice::where('sequence_number', 3)->first()->delete());

        $generated = app(GenerateInvoiceNumberAction::class)
            ->execute(null, now()->year, Invoice::TYPE_INVOICE);

        // La séquence doit continuer à 4, sans repasser par le 3 déjà consommé.
        $this->assertSame(4, $generated['sequence_number']);
        $this->assertNotSame(
            'INV-'.now()->year.'-0003',
            $generated['number'],
            'Un numéro déjà attribué ne doit jamais être réémis.'
        );
    }

    public function test_finalizing_after_a_deletion_does_not_collide(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        BusinessSettings::factory()->create();
        $client = Client::factory()->create(['user_id' => $user->id]);

        $issued = Invoice::factory()->create([
            'user_id' => $user->id,
            'client_id' => $client->id,
            'status' => Invoice::STATUS_FINALIZED,
            'finalized_at' => now(),
            'sequence_number' => 11,
            'number' => 'INV-'.now()->year.'-0011',
        ]);
        Invoice::withoutEvents(fn () => $issued->delete());

        $generated = app(GenerateInvoiceNumberAction::class)
            ->execute(null, now()->year, Invoice::TYPE_INVOICE);

        // Le numéro proposé ne doit pas déjà exister, supprimé ou non.
        $this->assertSame(0, Invoice::withTrashed()
            ->where('number', $generated['number'])
            ->count());
    }
}
