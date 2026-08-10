<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Import\ImportSession;
use App\Models\Invoice;
use App\Models\Plan;
use App\Models\RecurringInvoice;
use App\Models\User;
use App\Services\Import\ClientImportService;
use App\Services\PlanService;
use Database\Seeders\PlansSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Chemins qui contournaient les quotas annoncés sur la page tarifs.
 *
 * Chacun de ces tests correspond à un trou constaté : une création de document
 * ou de client qui aboutissait sans jamais consulter le plan. Ils échouent si
 * le contrôle est retiré, pas seulement si la valeur change.
 */
class QuotaBypassTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PlansSeeder::class);
    }

    /** Essai expiré, aucun abonnement => plan Gratuit (5 factures/mois, 10 clients). */
    private function freeUser(): User
    {
        return User::factory()->create([
            'trial_ends_at' => now()->subMonth(),
            'email_verified_at' => now(),
        ]);
    }

    private function makeInvoices(User $user, int $count, string $type = Invoice::TYPE_INVOICE): void
    {
        $client = Client::factory()->create(['user_id' => $user->id]);

        for ($i = 0; $i < $count; $i++) {
            Invoice::factory()->create([
                'user_id' => $user->id,
                'client_id' => $client->id,
                'status' => Invoice::STATUS_DRAFT,
                'type' => $type,
            ]);
        }
    }

    // --- Avoirs --------------------------------------------------------------

    public function test_un_avoir_ne_consomme_pas_de_place_de_facture(): void
    {
        $user = $this->freeUser();
        $this->makeInvoices($user, 4);
        $this->makeInvoices($user, 3, Invoice::TYPE_CREDIT_NOTE);

        // 4 factures sur 5 : corriger trois erreurs n'a rien coûté.
        $this->assertTrue(app(PlanService::class)->canCreateInvoice($user));

        $stats = app(PlanService::class)->getUsageStats($user);
        $this->assertSame(4, $stats['invoices_this_month']['used'], 'Le compteur affiché doit ignorer les avoirs.');
    }

    public function test_un_avoir_ne_rend_pas_la_place_de_la_facture_annulee(): void
    {
        $user = $this->freeUser();
        $this->makeInvoices($user, 5);
        $this->makeInvoices($user, 5, Invoice::TYPE_CREDIT_NOTE);

        // Sinon la boucle facture → avoir → facture serait infinie.
        $this->assertFalse(app(PlanService::class)->canCreateInvoice($user));
    }

    // --- Factures récurrentes ------------------------------------------------

    private function makeDueRecurring(User $user): RecurringInvoice
    {
        $client = Client::factory()->create(['user_id' => $user->id]);

        return RecurringInvoice::create([
            'user_id' => $user->id,
            'client_id' => $client->id,
            'title' => 'Forfait mensuel',
            'frequency' => RecurringInvoice::FREQUENCY_MONTHLY,
            'next_invoice_date' => now()->subDay()->toDateString(),
            'is_active' => true,
            'auto_finalize' => false,
            'auto_send' => false,
            'payment_delay_days' => 30,
            'currency' => 'EUR',
        ]);
    }

    public function test_le_cron_recurrent_ne_depasse_pas_le_plafond_mensuel(): void
    {
        $user = $this->freeUser();
        $this->makeInvoices($user, 5); // plafond Gratuit atteint
        $recurring = $this->makeDueRecurring($user);

        $this->artisan('recurring:generate')->assertExitCode(0);

        $this->assertSame(
            5,
            Invoice::withoutUserScope()->where('user_id', $user->id)->count(),
            'Le cron ne doit pas créer de facture au-delà du plafond.'
        );

        // L'échéance n'a pas avancé : la facture est reportée, pas perdue.
        $this->assertSame(
            $recurring->next_invoice_date->toDateString(),
            $recurring->fresh()->next_invoice_date->toDateString()
        );
    }

    public function test_le_cron_recurrent_genere_normalement_sous_le_plafond(): void
    {
        $user = $this->freeUser();
        $this->makeInvoices($user, 1);
        $recurring = $this->makeDueRecurring($user);

        $this->artisan('recurring:generate')->assertExitCode(0);

        $this->assertSame(2, Invoice::withoutUserScope()->where('user_id', $user->id)->count());
        $this->assertTrue($recurring->fresh()->next_invoice_date->isFuture());
    }

    public function test_creer_une_recurrence_demande_le_plan_essentiel(): void
    {
        $user = $this->freeUser();
        $this->actingAs($user);

        $this->post(route('recurring-invoices.store'), [])
            ->assertRedirect(route('subscription.index'));

        // Mais la liste reste accessible : on doit pouvoir désactiver
        // une récurrence héritée d'un plan supérieur.
        $this->get(route('recurring-invoices.index'))->assertSuccessful();
    }

    public function test_la_recurrence_est_bien_incluse_dans_essentiel_et_pro(): void
    {
        $this->assertContains('recurring_invoices', Plan::essentiel()->features);
        $this->assertContains('recurring_invoices', Plan::pro()->features);
        $this->assertNotContains('recurring_invoices', Plan::free()->features);
    }

    // --- Import de clients ---------------------------------------------------

    public function test_l_import_de_clients_s_arrete_au_plafond(): void
    {
        Storage::fake('local');

        $user = $this->freeUser();
        Client::factory()->count(8)->create(['user_id' => $user->id]); // 8/10

        $csv = "name,email\n";
        foreach (range(1, 5) as $i) {
            $csv .= "Client {$i},client{$i}@exemple.lu\n";
        }
        Storage::put('imports/clients.csv', $csv);

        $session = ImportSession::create([
            'user_id' => $user->id,
            'type' => 'clients',
            'filename' => 'clients.csv',
            'storage_path' => 'imports/clients.csv',
            'headers' => ['name', 'email'],
            'mapping' => ['name' => 'name', 'email' => 'email'],
            'duplicate_strategy' => 'skip',
            'status' => 'preview',
        ]);

        app(ClientImportService::class)->import($session);

        $session->refresh();

        $this->assertSame(2, $session->imported_count, 'Seules les 2 places restantes doivent être utilisées.');
        $this->assertSame(10, Client::where('user_id', $user->id)->count());
        $this->assertNotEmpty($session->errors, 'Les lignes refusées doivent être expliquées, pas disparaître.');
    }

    // --- Cohérence entre la page tarifs et les plans -------------------------

    public function test_les_quotas_annonces_correspondent_aux_plans(): void
    {
        // Les nombres du tableau comparatif sont écrits en dur dans
        // resources/js/Pages/Pricing.vue. Ce test est le garde-fou contre la
        // dérive : si vous changez une valeur ici, changez-la aussi là-bas.
        $expected = [
            'free' => ['max_clients' => 10, 'max_invoices_per_month' => 5, 'max_quotes_per_month' => 5,
                'max_emails_per_month' => 10, 'max_expenses_per_month' => 10, 'max_products' => 10],
            'essentiel' => ['max_clients' => 100, 'max_invoices_per_month' => 50, 'max_quotes_per_month' => 20,
                'max_emails_per_month' => 100, 'max_expenses_per_month' => 30, 'max_active_projects' => 10,
                'max_products' => null, 'max_collaborators_per_project' => 5],
            'pro' => ['max_clients' => null, 'max_invoices_per_month' => null, 'max_quotes_per_month' => null,
                'max_emails_per_month' => null, 'max_expenses_per_month' => null, 'max_active_projects' => null,
                'max_products' => null, 'max_collaborators_per_project' => 10],
        ];

        foreach ($expected as $name => $limits) {
            $plan = Plan::where('name', $name)->firstOrFail();

            foreach ($limits as $key => $value) {
                $this->assertSame($value, $plan->getLimit($key), "{$name}.{$key} ne correspond plus à la page tarifs.");
            }
        }
    }

    public function test_aucun_quota_peppol_ne_reste_declare_sans_etre_applique(): void
    {
        // La seule route qui porte plan.limit:peppol exige aussi
        // plan.feature:peppol_transmission, réservé à Pro. Une valeur sur
        // Essentiel ne serait donc jamais évaluée — et surtout jamais annoncée.
        $this->assertNull(Plan::essentiel()->getLimit('max_peppol_per_month'));
    }

    public function test_le_plan_pro_plafonne_les_transmissions_peppol(): void
    {
        // Ce quota valait null, donc illimité, sur un plan à 15 EUR/mois, alors
        // que chaque transmission se paie au point d'accès. Au tarif le plus
        // élevé que nous paierions (0,30 EUR le document), 50 est le seuil
        // exact où la marge s'annule : au-delà, un client qui réussit devient
        // une perte. Ce test interdit le retour silencieux à l'illimité.
        $this->assertSame(50, Plan::pro()->getLimit('max_peppol_per_month'));
    }
}
