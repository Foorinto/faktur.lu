<?php

namespace Tests\Feature;

use App\Models\BankBalance;
use App\Models\Client;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\User;
use App\Services\CashflowForecastService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * La prévision de trésorerie.
 *
 * Ce service n'avait AUCUN test, ce qui explique en partie qu'il ait pu rester
 * faux si longtemps. Un client l'a signalé :
 *
 *     « Je remarque qu'une facture émise aujourd'hui ne modifie pas le solde
 *       du jour de trésorerie : c'est un peu perturbant de voir trésorerie net
 *       en négatif lorsqu'on a réalisé un encaissement »
 *
 * Trois défauts derrière cette phrase polie, un test chacun.
 */
class CashflowForecastTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\PlansSeeder::class);

        $this->user = User::factory()->create([
            'email_verified_at' => now(),
            'trial_ends_at' => now()->addDays(14),
        ]);
        $this->actingAs($this->user);
    }

    private function prevision(int $jours = 90): array
    {
        return app(CashflowForecastService::class)->getForecast($jours);
    }

    private function factureAEncaisser(float $ttc, int $dansXJours): Invoice
    {
        $client = Client::factory()->create(['user_id' => $this->user->id]);

        return Invoice::factory()->create([
            'user_id' => $this->user->id,
            'client_id' => $client->id,
            'status' => Invoice::STATUS_SENT,
            'issued_at' => now()->toDateString(),
            'finalized_at' => now()->toDateString(),
            'due_at' => now()->addDays($dansXJours)->toDateString(),
            'total_ht' => $ttc, 'total_vat' => 0, 'total_ttc' => $ttc,
        ]);
    }

    private function releve(float $montant, int $ilYAXJours = 1): BankBalance
    {
        return BankBalance::create([
            'balance_date' => now()->subDays($ilYAXJours)->toDateString(),
            'amount' => $montant,
        ]);
    }

    /**
     * LE défaut central : encaisser faisait BAISSER la prévision.
     *
     * La courbe ne lisait que les factures impayées. Une facture réglée sortait
     * de `unpaid()`, donc son montant disparaissait au moment précis où
     * l'argent arrivait sur le compte.
     */
    public function test_recording_a_payment_does_not_lower_the_forecast(): void
    {
        $this->releve(10000);
        $facture = $this->factureAEncaisser(5000, 10);

        $avant = $this->prevision();

        // Le client règle. C'est le geste qui cassait tout.
        $facture->payments()->create([
            'amount' => 5000,
            'paid_at' => now()->toDateString(),
            'method' => 'transfer',
        ]);
        $facture->update(['status' => Invoice::STATUS_PAID, 'paid_at' => now()->toDateString()]);

        $apres = $this->prevision();

        // À 90 jours, rien ne change : le même argent, simplement déjà arrivé.
        $this->assertSame(
            $avant['summary']['days_90'],
            $apres['summary']['days_90'],
            "Encaisser ne doit rien retirer à la prévision à 90 jours"
        );

        // Et aujourd'hui, le solde MONTE de ce qui vient d'être encaissé.
        $this->assertSame(10000.0, $avant['summary']['current']);
        $this->assertSame(15000.0, $apres['summary']['current']);
    }

    /**
     * Le solde du jour était négatif par construction : au jour 0, le service
     * retirait déjà un trentième des dépenses du mois.
     */
    public function test_todays_balance_is_the_real_balance_not_a_negative_number(): void
    {
        $this->releve(10000);

        // Six mois de dépenses, donc une moyenne mensuelle non nulle : c'est
        // elle qui rendait le jour 0 négatif.
        for ($mois = 1; $mois <= 6; $mois++) {
            Expense::factory()->create([
                'user_id' => $this->user->id,
                'date' => now()->subMonths($mois)->startOfMonth()->toDateString(),
                // ⚠️ HT et taux, pas TTC : le modèle recalcule les montants à
                // l'enregistrement, un `amount_ttc` passé ici est écrasé.
                'amount_ht' => 3000, 'vat_rate' => 0,
            ]);
        }

        $prevision = $this->prevision();

        $this->assertGreaterThan(0, $prevision['totals']['monthly_expense_average']);
        $this->assertSame(10000.0, $prevision['summary']['current']);
        $this->assertSame(0.0, $prevision['timeline'][0]['expense'], 'Le jour 0 ne porte aucune dépense projetée');
        $this->assertSame(0.0, $prevision['timeline'][0]['income'], 'Le jour 0 ne porte aucune recette projetée');
    }

    /**
     * Sans relevé, la courbe reste une variation partant de zéro. Elle doit le
     * DIRE, au lieu d'afficher un faux solde.
     */
    public function test_without_a_statement_the_curve_is_a_variation_and_says_so(): void
    {
        $this->factureAEncaisser(5000, 10);

        $prevision = $this->prevision();

        $this->assertTrue($prevision['has_data']);
        $this->assertFalse($prevision['has_balance']);
        $this->assertNull($prevision['current_balance']);
        $this->assertSame(0.0, $prevision['summary']['current']);
    }

    /**
     * ⚠️ LA régression. Signalée par le client sur la toute première version
     * de ce correctif :
     *
     *     « Avant : +90 jours 39.790 €. Après avoir soldé une facture de
     *       3000 € : +90 jours 36.790 €, et le solde aujourd'hui n'a pas
     *       changé. »
     *
     * J'avais écarté les encaissements datés du JOUR du relevé, pour ne pas
     * compter deux fois un montant déjà présent sur le relevé bancaire. Sauf
     * que le relevé se saisit le jour même : l'encaissement était donc écarté
     * du réel PENDANT que sa facture quittait la projection. L'argent
     * disparaissait des deux côtés à la fois, c'est-à-dire le bug d'origine,
     * reconstitué par sa propre correction.
     */
    public function test_a_payment_on_the_statement_day_does_not_vanish(): void
    {
        // Le relevé daté d'aujourd'hui : le cas de très loin le plus courant.
        BankBalance::create(['balance_date' => now()->toDateString(), 'amount' => 10000]);

        $facture = $this->factureAEncaisser(3000, 20);

        $avant = $this->prevision();

        $facture->payments()->create([
            'amount' => 3000,
            'paid_at' => now()->toDateString(),
            'method' => 'transfer',
        ]);
        $facture->update(['status' => Invoice::STATUS_PAID, 'paid_at' => now()->toDateString()]);

        $apres = $this->prevision();

        $this->assertSame(13000.0, $avant['summary']['days_90']);
        $this->assertSame(13000.0, $apres['summary']['days_90'], 'Les 3 000 € ne doivent pas disparaître');
        $this->assertSame(13000.0, $apres['summary']['current'], "Le solde du jour doit monter de l'encaissement");
    }

    /**
     * Le second trou, de la même famille : une facture réglée à moitié restait
     * projetée pour son montant ENTIER, pendant que l'acompte comptait déjà
     * comme encaissement réel. Le même euro deux fois.
     *
     * La projection porte donc sur le RESTE DÛ, jamais sur le statut.
     */
    public function test_a_partial_payment_is_not_counted_twice(): void
    {
        BankBalance::create(['balance_date' => now()->toDateString(), 'amount' => 10000]);

        $facture = $this->factureAEncaisser(3000, 20);
        $facture->payments()->create([
            'amount' => 1000,
            'paid_at' => now()->toDateString(),
            'method' => 'transfer',
        ]);

        $prevision = $this->prevision();

        // 10 000 au relevé + 1 000 reçus = 11 000 aujourd'hui.
        $this->assertSame(11000.0, $prevision['summary']['current']);

        // Et à 90 jours : 13 000, pas 14 000. Seuls les 2 000 restants sont
        // encore attendus.
        $this->assertSame(13000.0, $prevision['summary']['days_90']);
    }

    /**
     * L'hypothèse la plus généreuse du calcul doit être annoncée.
     *
     * Une facture en retard est projetée à demain. Sur un compte réel dont les
     * onze factures impayées étaient TOUTES en retard, la courbe supposait
     * 29 907 € rentrant sous vingt-quatre heures — ce qui expliquait à soi
     * seul le bond entre aujourd'hui et le mois suivant. Le chiffre n'est pas
     * faux, il ne doit simplement pas se lire comme une promesse.
     */
    public function test_the_overdue_amount_assumed_arriving_tomorrow_is_announced(): void
    {
        $this->releve(10000);

        // Créée directement en retard : une facture finalisée est verrouillée
        // et refuse toute modification, garde-fou légitime.
        $this->factureAEncaisser(4000, -45);
        $this->factureAEncaisser(1500, 20);

        $prevision = $this->prevision();

        // Seul le retard est annoncé, pas la facture qui échoit normalement.
        $this->assertSame(4000.0, $prevision['overdue_total']);

        // Et il est bien compté dans la courbe, pas seulement affiché.
        $this->assertSame(14000.0, $prevision['timeline'][1]['net_cash']);
    }

    /**
     * Le relevé le plus récent fait foi, et un relevé daté du futur ne sert
     * pas de base à un calcul qui part d'aujourd'hui.
     */
    public function test_the_latest_statement_wins_and_future_ones_are_ignored(): void
    {
        $this->releve(10000, 30);
        $this->releve(7500, 2);

        BankBalance::create([
            'balance_date' => now()->addDays(10)->toDateString(),
            'amount' => 99999,
        ]);

        $prevision = $this->prevision();

        $this->assertSame(7500.0, $prevision['summary']['current']);
        $this->assertSame(now()->subDays(2)->toDateString(), $prevision['opening_balance']['date']);
    }

    /**
     * Le parcours complet : je saisis mon solde, et le tableau de bord le
     * reflète immédiatement.
     *
     * Le piège côté interface était le même que côté calcul : le composant
     * copie la prop une seule fois. Sans veille, l'utilisateur saisissait son
     * solde et ne voyait rien changer.
     */
    public function test_saving_a_balance_changes_what_the_dashboard_shows(): void
    {
        $this->factureAEncaisser(5000, 10);

        $this->get(route('dashboard'))
            ->assertInertia(fn ($page) => $page->where('cashflowForecast.has_balance', false));

        $this->post(route('bank-balance.store'), [
            'amount' => 10000,
            'balance_date' => now()->toDateString(),
        ])->assertSessionHasNoErrors();

        $this->get(route('dashboard'))
            ->assertInertia(fn ($page) => $page
                ->where('cashflowForecast.has_balance', true)
                // Entier et non 10000.0 : la valeur traverse une
                // sérialisation JSON avant d'arriver ici, et `where` compare
                // strictement.
                ->where('cashflowForecast.current_balance', 10000)
                ->where('cashflowForecast.summary.current', 10000)
            );
    }

    /**
     * Un utilisateur qui débute — aucune facture, aucune dépense — doit
     * pouvoir saisir son solde d'ouverture.
     *
     * La carte du tableau de bord ne s'affichait que si la prévision avait des
     * données. Sans facture impayée, elle disparaissait, et avec elle le seul
     * endroit où saisir le solde. C'est pourtant le cas que le client
     * décrivait : « indiquer le solde bancaire en début d'activité ».
     */
    public function test_a_brand_new_user_can_still_record_an_opening_balance(): void
    {
        $this->post(route('bank-balance.store'), [
            'amount' => 2500,
            'balance_date' => now()->toDateString(),
            'label' => 'Ouverture',
        ])->assertSessionHasNoErrors();

        $prevision = $this->prevision();

        $this->assertTrue($prevision['has_data'], 'La carte doit avoir de quoi s\'afficher');
        $this->assertTrue($prevision['has_balance']);
        $this->assertSame(2500.0, $prevision['summary']['current']);
    }

    /**
     * Un relevé daté du futur est refusé à la saisie : le calcul part
     * d'aujourd'hui et l'ignorerait de toute façon.
     */
    public function test_a_future_statement_is_refused_at_the_door(): void
    {
        $this->post(route('bank-balance.store'), [
            'amount' => 10000,
            'balance_date' => now()->addDay()->toDateString(),
        ])->assertSessionHasErrors('balance_date');
    }

    /**
     * Le relevé d'un utilisateur ne doit jamais servir de base au calcul d'un
     * autre.
     */
    public function test_a_statement_never_leaks_to_another_user(): void
    {
        $this->releve(10000);

        $autre = User::factory()->create([
            'email_verified_at' => now(),
            'trial_ends_at' => now()->addDays(14),
        ]);
        $this->actingAs($autre);

        $prevision = $this->prevision();

        $this->assertFalse($prevision['has_balance']);
        $this->assertNull($prevision['current_balance']);
    }

    /**
     * Les dépenses réelles depuis le relevé descendent le solde du jour.
     */
    public function test_real_expenses_since_the_statement_lower_todays_balance(): void
    {
        $this->releve(10000, 5);

        Expense::factory()->create([
            'user_id' => $this->user->id,
            'date' => now()->subDays(2)->toDateString(),
            'amount_ht' => 1200, 'vat_rate' => 0,
        ]);

        $prevision = $this->prevision();

        $this->assertSame(8800.0, $prevision['summary']['current']);
        $this->assertSame(1200.0, $prevision['movements_since_balance']['expense']);
    }
}
