<?php

namespace Tests\Feature;

use App\Models\Expense;
use App\Models\RecurringExpense;
use App\Models\RecurringInvoice;
use App\Models\User;
use Database\Seeders\PlansSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Charges fixes récurrentes (FEAT-117) : le moteur.
 *
 * Le principe que ces tests protègent : une charge fixe ne porte aucun montant
 * prévisionnel à additionner, elle fabrique une dépense ordinaire. Tout ce qui
 * pourrait faire naître une dépense en trop, ou aucune, est vérifié ici.
 */
class RecurringExpenseTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    private function charge(array $attributs = []): RecurringExpense
    {
        return RecurringExpense::factory()->create(array_merge([
            'user_id' => $this->user->id,
            'label' => 'Loyer du bureau',
            'provider_name' => 'Immo Lux Sàrl',
            'category' => Expense::CATEGORY_OTHER,
            'amount_input_mode' => Expense::INPUT_HT,
            'amount' => 1000,
            'vat_rate' => 17,
            'vat_regime' => Expense::REGIME_NATIONAL,
            'frequency' => RecurringExpense::FREQUENCY_MONTHLY,
            'next_expense_date' => now()->toDateString(),
        ], $attributs));
    }

    // --- Génération ----------------------------------------------------------

    public function test_une_charge_due_fait_naitre_une_depense_ordinaire(): void
    {
        $charge = $this->charge();

        $this->artisan('recurring-expenses:generate')->assertExitCode(0);

        $depense = Expense::withoutGlobalScope('user')->sole();

        $this->assertSame($this->user->id, $depense->user_id);
        $this->assertSame($charge->id, $depense->recurring_expense_id);
        $this->assertSame('Immo Lux Sàrl', $depense->provider_name);
        $this->assertSame('1000.0000', $depense->amount_ht);
        $this->assertSame('170.0000', $depense->amount_vat, 'La dépense refait elle-même le calcul de TVA.');
        $this->assertSame('1170.0000', $depense->amount_ttc);
    }

    public function test_la_depense_porte_la_date_de_l_echeance_pas_celle_de_la_generation(): void
    {
        // Un serveur en retard ne doit pas déplacer le loyer dans le mois
        // suivant : l'écriture reste à sa place dans le récapitulatif fiscal.
        $echeance = now()->subDays(3)->toDateString();
        $this->charge(['next_expense_date' => $echeance]);

        $this->artisan('recurring-expenses:generate');

        $depense = Expense::withoutGlobalScope('user')->sole();
        $this->assertSame($echeance, $depense->date->toDateString());
    }

    public function test_une_saisie_en_ttc_reste_au_centime_du_releve_bancaire(): void
    {
        $this->charge([
            'amount_input_mode' => Expense::INPUT_TTC,
            'amount' => 1170,
            'vat_rate' => 17,
        ]);

        $this->artisan('recurring-expenses:generate');

        $depense = Expense::withoutGlobalScope('user')->sole();
        $this->assertSame('1170.0000', $depense->amount_ttc);
        $this->assertSame('1000.0000', $depense->amount_ht);
    }

    public function test_la_commande_ne_genere_pas_deux_fois_la_meme_echeance(): void
    {
        $this->charge();

        $this->artisan('recurring-expenses:generate');
        $this->artisan('recurring-expenses:generate');

        $this->assertSame(1, Expense::withoutGlobalScope('user')->count());
    }

    public function test_l_echeance_avance_d_une_periode_et_le_compteur_monte(): void
    {
        $charge = $this->charge(['next_expense_date' => '2026-03-01']);

        $this->artisan('recurring-expenses:generate');

        $charge->refresh();
        $this->assertSame('2026-04-01', $charge->next_expense_date->toDateString());
        $this->assertSame(1, $charge->expenses_generated);
        $this->assertNotNull($charge->last_expense_id);
    }

    // --- Ce qui ne doit rien générer -----------------------------------------

    public function test_une_charge_suspendue_ne_genere_rien(): void
    {
        $charge = $this->charge(['is_active' => false]);

        $this->artisan('recurring-expenses:generate');

        $this->assertSame(0, Expense::withoutGlobalScope('user')->count());
        $this->assertSame('2026', substr((string) $charge->fresh()->next_expense_date, 0, 4), 'Son échéance ne bouge pas non plus.');
        $this->assertSame(0, $charge->fresh()->expenses_generated);
    }

    public function test_une_charge_terminee_ne_genere_rien(): void
    {
        $this->charge([
            'next_expense_date' => now()->toDateString(),
            'ends_at' => now()->subMonth()->toDateString(),
        ]);

        $this->artisan('recurring-expenses:generate');

        $this->assertSame(0, Expense::withoutGlobalScope('user')->count());
    }

    public function test_une_echeance_future_attend_son_tour(): void
    {
        $this->charge(['next_expense_date' => now()->addWeek()->toDateString()]);

        $this->artisan('recurring-expenses:generate');

        $this->assertSame(0, Expense::withoutGlobalScope('user')->count());
    }

    public function test_la_charge_s_eteint_quand_elle_depasse_sa_date_de_fin(): void
    {
        $charge = $this->charge([
            'next_expense_date' => now()->toDateString(),
            'ends_at' => now()->addDays(10)->toDateString(),
        ]);

        $this->artisan('recurring-expenses:generate');

        $charge->refresh();
        $this->assertFalse($charge->is_active, 'La prochaine échéance dépasse la fin : la charge s\'arrête d\'elle-même.');
        $this->assertSame(1, $charge->expenses_generated, 'La dépense due a bien été générée avant l\'extinction.');
    }

    // --- Quota et isolation --------------------------------------------------

    public function test_le_quota_reporte_la_charge_sans_perdre_la_depense(): void
    {
        // Avancer l'échéance malgré le refus perdrait la dépense pour de bon :
        // une charge manquante fausse la comptabilité autant qu'une en trop.
        $this->seed(PlansSeeder::class);

        $pauvre = User::factory()->create(['trial_ends_at' => now()->subMonth()]);
        Expense::factory()->count(10)->create(['user_id' => $pauvre->id]);

        $charge = RecurringExpense::factory()->create([
            'user_id' => $pauvre->id,
            'next_expense_date' => '2026-03-01',
            'frequency' => RecurringExpense::FREQUENCY_MONTHLY,
        ]);

        $this->artisan('recurring-expenses:generate');

        $charge->refresh();
        $this->assertSame('2026-03-01', $charge->next_expense_date->toDateString(), 'La charge reste due.');
        $this->assertSame(0, $charge->expenses_generated);
        $this->assertSame(10, Expense::withoutGlobalScope('user')->where('user_id', $pauvre->id)->count());
    }

    public function test_chaque_depense_revient_au_bon_compte(): void
    {
        $autre = User::factory()->create();

        $this->charge();
        RecurringExpense::factory()->create([
            'user_id' => $autre->id,
            'provider_name' => 'POST Luxembourg',
            'next_expense_date' => now()->toDateString(),
        ]);

        $this->artisan('recurring-expenses:generate');

        $this->assertSame(1, Expense::withoutGlobalScope('user')->where('user_id', $this->user->id)->count());
        $this->assertSame(1, Expense::withoutGlobalScope('user')->where('user_id', $autre->id)->count());
    }

    // --- Le rythme partagé ---------------------------------------------------

    /**
     * @dataProvider rythmes
     */
    public function test_le_rythme_avance_de_la_bonne_periode(string $frequence, string $attendu): void
    {
        $charge = $this->charge([
            'frequency' => $frequence,
            'next_expense_date' => '2026-01-31',
        ]);

        $this->assertSame($attendu, $charge->calculateNextDate()->toDateString());
    }

    public static function rythmes(): array
    {
        return [
            'hebdomadaire' => [RecurringExpense::FREQUENCY_WEEKLY, '2026-02-07'],
            'mensuel, fin de mois courte' => [RecurringExpense::FREQUENCY_MONTHLY, '2026-02-28'],
            'trimestriel, fin de mois courte' => [RecurringExpense::FREQUENCY_QUARTERLY, '2026-04-30'],
            'annuel' => [RecurringExpense::FREQUENCY_YEARLY, '2027-01-31'],
        ];
    }

    public function test_une_charge_de_fin_de_mois_ne_saute_jamais_un_mois(): void
    {
        // Le défaut d'origine : le 31 janvier plus un mois donnait le 3 mars.
        // Février était sauté — un loyer disparu, et une facture non émise côté
        // ventes — puis l'échéance restait collée au 3 pour toujours.
        $charge = $this->charge(['next_expense_date' => '2026-01-31']);

        $mois = [];

        for ($i = 0; $i < 4; $i++) {
            $charge->forceFill(['next_expense_date' => $charge->calculateNextDate()]);
            $mois[] = $charge->next_expense_date->format('Y-m');
        }

        $this->assertSame(['2026-02', '2026-03', '2026-04', '2026-05'], $mois);
    }

    public function test_les_factures_recurrentes_gardent_exactement_le_meme_rythme(): void
    {
        // Le rythme est désormais partagé : ce test garantit que la mise en
        // commun n'a rien changé au comportement des factures récurrentes.
        $recurrente = new RecurringInvoice([
            'frequency' => RecurringInvoice::FREQUENCY_QUARTERLY,
            'next_invoice_date' => '2026-01-31',
        ]);
        $recurrente->is_active = true;

        $this->assertSame('2026-04-30', $recurrente->calculateNextDate()->toDateString());
        $this->assertSame('next_invoice_date', RecurringInvoice::nextDateColumn());
        $this->assertSame('invoices_generated', RecurringInvoice::generatedCountColumn());
        $this->assertSame(RecurringExpense::FREQUENCIES, RecurringInvoice::FREQUENCIES);
    }
}
