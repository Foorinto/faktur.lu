<?php

namespace Tests\Unit\Actions;

use App\Actions\GenerateQuoteNumberAction;
use App\Models\BusinessSettings;
use App\Models\Client;
use App\Models\Quote;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Numérotation des devis.
 *
 * La séquence doit être continue et ne jamais réattribuer une référence déjà
 * consommée — y compris par un devis supprimé, dont la référence reste dans
 * l'index unique.
 */
class GenerateQuoteNumberActionTest extends TestCase
{
    use RefreshDatabase;

    private GenerateQuoteNumberAction $action;
    private User $user;
    private Client $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->action = app(GenerateQuoteNumberAction::class);

        $this->user = User::factory()->create();
        $this->actingAs($this->user);

        BusinessSettings::factory()->create();
        $this->client = Client::factory()->create(['user_id' => $this->user->id]);
    }

    /** Il n'existe pas de QuoteFactory : on crée le devis directement. */
    private function makeQuote(int $sequence, ?string $createdAt = null): Quote
    {
        $quote = new Quote();
        $quote->forceFill([
            'user_id' => $this->user->id,
            'client_id' => $this->client->id,
            'reference' => 'DEV-TEST-'.$sequence.'-'.uniqid(),
            'sequence_number' => $sequence,
            'status' => 'draft',
            'currency' => 'EUR',
            'created_at' => $createdAt ?? now(),
            'updated_at' => now(),
        ])->save();

        return $quote->refresh();
    }

    public function test_the_first_quote_of_the_year_starts_the_sequence(): void
    {
        $generated = $this->action->execute(null, now()->year);

        $this->assertSame(1, $generated['sequence_number']);
        $this->assertNotEmpty($generated['reference']);
    }

    public function test_the_sequence_continues_from_the_highest_existing_number(): void
    {
        $this->makeQuote(1);
        $this->makeQuote(2);

        $this->assertSame(3, $this->action->execute(null, now()->year)['sequence_number']);
    }

    public function test_a_deleted_quote_still_reserves_its_number(): void
    {
        $this->makeQuote(1);
        $deleted = $this->makeQuote(2);
        $deleted->delete();

        // La référence d'un devis supprimé reste dans l'index unique : la
        // réattribuer ferait échouer la création suivante.
        $this->assertSame(3, $this->action->execute(null, now()->year)['sequence_number']);
    }

    public function test_the_configured_starting_number_is_honoured(): void
    {
        // Cas d'une reprise depuis un autre outil : on continue la numérotation
        // existante plutôt que de repartir à 1.
        BusinessSettings::getInstance()->update(['quote_starting_number' => 250]);

        $this->assertSame(250, $this->action->execute(null, now()->year)['sequence_number']);
    }

    public function test_each_year_has_its_own_sequence(): void
    {
        $this->makeQuote(7, now()->subYear()->format('Y-m-d H:i:s'));

        // Un devis de l'an dernier ne doit pas décaler le compteur de cette année.
        $this->assertSame(1, $this->action->execute(null, now()->year)['sequence_number']);
    }

    public function test_another_company_sequence_is_ignored(): void
    {
        $this->makeQuote(9);

        // Isolation multi-tenant : chaque entreprise a sa propre numérotation.
        $other = User::factory()->create();
        $this->actingAs($other);
        BusinessSettings::factory()->create();

        $this->assertSame(1, $this->action->execute(null, now()->year)['sequence_number']);
    }

    public function test_the_preview_does_not_consume_a_number(): void
    {
        $first = $this->action->preview(null, now()->year);
        $second = $this->action->preview(null, now()->year);

        // L'aperçu affiché sur l'écran de création ne doit rien réserver.
        $this->assertSame($first, $second);
        $this->assertSame(1, $this->action->execute(null, now()->year)['sequence_number']);
    }
}
