<?php

namespace Tests\Feature;

use App\Models\BankBalance;
use App\Models\Expense;
use App\Models\RecurringExpense;
use App\Models\User;
use App\Services\CashflowForecastService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Les charges fixes dans la prévision de trésorerie (FEAT-117, lot 3).
 *
 * Le piège que la fiche annonçait : déclarer son loyer comme charge fixe ne
 * doit pas le faire peser deux fois sur la courbe, une fois lissé dans la
 * moyenne des six derniers mois et une fois à sa date d'échéance. Sans quoi la
 * prévision serait plus fausse après la fonctionnalité qu'avant.
 */
class RecurringExpenseForecastTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['email_verified_at' => now()]);
        $this->actingAs($this->user);

        BankBalance::create(['balance_date' => now()->toDateString(), 'amount' => 10000]);
    }

    private function prevision(int $jours = 90): array
    {
        return app(CashflowForecastService::class)->getForecast($jours);
    }

    private function loyer(array $surcharge = []): RecurringExpense
    {
        return RecurringExpense::factory()->create(array_merge([
            'user_id' => $this->user->id,
            'label' => 'Loyer',
            'frequency' => RecurringExpense::FREQUENCY_MONTHLY,
            'next_expense_date' => now()->addDays(10)->toDateString(),
            'amount_input_mode' => Expense::INPUT_TTC,
            'amount' => 1000,
            'vat_rate' => 0,
            'vat_regime' => Expense::REGIME_EXEMPT,
        ], $surcharge));
    }

    /** Une dépense variable passée, qui doit rester dans la moyenne lissée. */
    private function depenseVariable(float $ttc, int $ilYAXMois): Expense
    {
        return Expense::factory()->create([
            'user_id' => $this->user->id,
            'date' => now()->subMonths($ilYAXMois)->startOfMonth()->addDays(5)->toDateString(),
            'amount_input_mode' => Expense::INPUT_TTC,
            'amount_ttc' => $ttc,
            'vat_rate' => 0,
            'vat_regime' => Expense::REGIME_EXEMPT,
        ]);
    }

    public function test_une_charge_fixe_tombe_a_sa_date_et_nulle_part_ailleurs(): void
    {
        $this->loyer();

        $prevision = $this->prevision(30);
        $jour = collect($prevision['timeline'])->firstWhere('day_number', 10);
        $veille = collect($prevision['timeline'])->firstWhere('day_number', 9);

        $this->assertSame(1000.0, $jour['expense'], 'Le loyer tombe le jour de son échéance.');
        $this->assertSame(0.0, $veille['expense'], 'Et rien la veille : aucune moyenne ne le lisse.');
        $this->assertSame(1000.0, $prevision['totals']['total_expected_recurring']);
        $this->assertSame(1000.0, $prevision['totals']['monthly_recurring']);
    }

    /**
     * LE test que la fiche exigeait.
     *
     * Une charge active et les dépenses qu'elle a déjà produites ne doivent
     * produire qu'une seule fois le montant.
     */
    public function test_une_charge_et_ses_depenses_generees_ne_comptent_qu_une_fois(): void
    {
        $charge = $this->loyer(['next_expense_date' => now()->subMonths(6)->toDateString()]);

        // Six mois de loyers déjà générés par cette charge.
        for ($i = 0; $i < 6; $i++) {
            $this->artisan('recurring-expenses:generate');
        }

        $this->assertSame(6, $charge->fresh()->expenses_generated);

        $prevision = $this->prevision(30);

        $this->assertSame(
            0.0,
            $prevision['totals']['monthly_expense_average'],
            'Les loyers générés sortent de la moyenne : sinon le loyer pèserait deux fois.'
        );

        // Sur trente jours, deux échéances : celle du jour, reportée à demain,
        // et celle du mois suivant. Rien de lissé s'y ajoute — c'est tout
        // l'enjeu : 2 000 de loyers projetés, et pas 2 000 + une moyenne qui
        // contiendrait déjà ces mêmes loyers.
        $this->assertSame(2000.0, $prevision['totals']['total_expected_recurring']);
        $this->assertSame(2000.0, $prevision['totals']['total_expected_expense']);
    }

    public function test_les_depenses_variables_restent_lissees(): void
    {
        // Un achat de matériel exceptionnel n'a pas de rythme : il continue
        // d'être estimé par la moyenne, c'est bien son seul traitement possible.
        $this->depenseVariable(600, 1);
        $this->depenseVariable(600, 2);

        $prevision = $this->prevision(30);

        $this->assertSame(200.0, $prevision['totals']['monthly_expense_average'], '1 200 sur six mois.');
        $this->assertSame(0.0, $prevision['totals']['total_expected_recurring']);
    }

    public function test_une_charge_s_ajoute_aux_depenses_variables_sans_les_remplacer(): void
    {
        $this->depenseVariable(600, 1);
        $this->depenseVariable(600, 2);
        $this->loyer();

        $prevision = $this->prevision(30);

        // 200 par mois de variable, soit 6,6667 par jour sur 30 jours, plus
        // un loyer de 1 000 à sa date.
        $this->assertSame(200.0, $prevision['totals']['monthly_expense_average']);
        $this->assertSame(1200.0, $prevision['totals']['total_expected_expense']);
    }

    public function test_une_charge_suspendue_ne_pese_sur_aucune_prevision(): void
    {
        $this->loyer(['is_active' => false]);

        $prevision = $this->prevision(30);

        $this->assertSame(0.0, $prevision['totals']['total_expected_recurring']);
        $this->assertSame(0.0, $prevision['totals']['total_expected_expense']);
    }

    public function test_une_charge_cesse_d_etre_projetee_apres_sa_date_de_fin(): void
    {
        $this->loyer([
            'next_expense_date' => now()->addDays(5)->toDateString(),
            'ends_at' => now()->addDays(20)->toDateString(),
        ]);

        // Sur 90 jours, seules les échéances antérieures à la fin comptent.
        $prevision = $this->prevision(90);

        $this->assertSame(1000.0, $prevision['totals']['total_expected_recurring'], 'Une seule échéance avant la fin.');
    }

    public function test_une_echeance_deja_passee_est_projetee_a_demain(): void
    {
        // La génération tourne à 6h05 : une échéance d'hier sera honorée, mais
        // le jour 0 doit rester le solde réel.
        $this->loyer(['next_expense_date' => now()->subDays(2)->toDateString()]);

        $prevision = $this->prevision(30);

        $this->assertSame(0.0, collect($prevision['timeline'])->firstWhere('day_number', 0)['expense']);
        $this->assertSame(1000.0, collect($prevision['timeline'])->firstWhere('day_number', 1)['expense']);
    }

    public function test_le_montant_projete_est_le_ttc_reellement_decaisse(): void
    {
        // Saisie en HT : c'est le TTC qui sort du compte.
        $this->loyer([
            'amount_input_mode' => Expense::INPUT_HT,
            'amount' => 1000,
            'vat_rate' => 17,
            'vat_regime' => Expense::REGIME_NATIONAL,
        ]);

        $prevision = $this->prevision(30);

        $this->assertSame(1170.0, $prevision['totals']['total_expected_recurring']);
    }

    public function test_une_charge_trimestrielle_ne_tombe_qu_une_fois_par_trimestre(): void
    {
        $this->loyer([
            'frequency' => RecurringExpense::FREQUENCY_QUARTERLY,
            'next_expense_date' => now()->addDays(5)->toDateString(),
        ]);

        $prevision = $this->prevision(90);

        // Jour 5 et jour 5 + 3 mois : la seconde échéance tombe hors des
        // 90 jours dans la plupart des cas, une seule sinon.
        $echeances = collect($prevision['timeline'])->where('expense', '>', 0)->count();

        $this->assertLessThanOrEqual(2, $echeances);
        $this->assertGreaterThanOrEqual(1, $echeances);
    }

    public function test_la_charge_d_un_autre_compte_n_entre_dans_aucune_prevision(): void
    {
        $autre = User::factory()->create();
        RecurringExpense::factory()->create([
            'user_id' => $autre->id,
            'next_expense_date' => now()->addDays(3)->toDateString(),
            'amount' => 5000,
        ]);

        $prevision = $this->prevision(30);

        $this->assertSame(0.0, $prevision['totals']['total_expected_recurring']);
    }
}
