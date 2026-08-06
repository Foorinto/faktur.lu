<?php

namespace Tests\Feature;

use App\Mail\TrialEndingSoon;
use App\Models\Client;
use App\Models\Product;
use App\Models\User;
use App\Services\PlanService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Fin d'essai : rendre le dépassement lisible (FEAT-105).
 *
 * Un compte qui a saisi 50 articles pendant son essai retombe sur un plan
 * plafonné à 10. Rien n'est supprimé, seule la création est bloquée, mais le
 * moment était illisible : compteur « 50 / 10 », aucune alerte préalable sur le
 * catalogue, et un rappel de fin d'essai qui vendait des fonctionnalités sans
 * citer un seul chiffre du compte.
 *
 * La ligne à ne pas franchir est testée en premier : pour un logiciel de
 * facturation, la confiance est le produit. Un utilisateur qui croit ses
 * données prises en otage ne s'abonne pas, il part.
 */
class TrialEndQuotaVisibilityTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\PlansSeeder::class);

        // Sans essai en cours ni abonnement : plan Gratuit, 10 articles.
        $this->user = User::factory()->create([
            'email_verified_at' => now(),
            'trial_ends_at' => null,
        ]);
        $this->actingAs($this->user);
    }

    private function products(int $count): void
    {
        Product::factory()->count($count)->create(['user_id' => $this->user->id]);
    }

    private function plans(): PlanService
    {
        return app(PlanService::class);
    }

    // --- La ligne à ne pas franchir ----------------------------------------------

    public function test_aucun_article_n_est_masque_au_dela_du_plafond(): void
    {
        $this->products(15);

        $reponse = $this->get(route('products.index'))->assertSuccessful();

        $reponse->assertInertia(fn ($page) => $page
            ->where('quota.used', 15)
            ->where('quota.limit', 10)
        );

        // Les quinze restent listés : le plafond bloque la création, pas la
        // consultation ni la facturation.
        $this->assertSame(15, Product::where('user_id', $this->user->id)->count());
    }

    // --- Le compteur -------------------------------------------------------------

    public function test_le_depassement_est_transmis_a_la_page(): void
    {
        $this->products(12);

        $this->get(route('products.index'))->assertInertia(fn ($page) => $page
            ->where('quota.used', 12)
            ->where('quota.limit', 10)
        );
    }

    // --- Les alertes -------------------------------------------------------------

    public function test_le_catalogue_declenche_une_alerte_avant_le_mur(): void
    {
        // 8 sur 10 : le seuil de 80 % est atteint, l'utilisateur est prévenu
        // avant de buter dessus.
        $this->products(8);

        $types = array_column($this->plans()->getQuotaAlerts($this->user), 'type');

        $this->assertContains('products', $types);
    }

    public function test_le_catalogue_ne_declenche_rien_quand_il_est_loin_du_plafond(): void
    {
        $this->products(3);

        $types = array_column($this->plans()->getQuotaAlerts($this->user), 'type');

        $this->assertNotContains('products', $types, 'Un bandeau permanent deviendrait un décor.');
    }

    public function test_l_alerte_signale_un_plafond_atteint(): void
    {
        $this->products(11);

        $alerte = collect($this->plans()->getQuotaAlerts($this->user))->firstWhere('type', 'products');

        $this->assertTrue($alerte['reached']);
        $this->assertSame(11, $alerte['used']);
        $this->assertSame(0, $alerte['remaining']);
    }

    public function test_les_statistiques_comptent_les_articles(): void
    {
        $this->products(4);

        $stats = $this->plans()->getUsageStats($this->user);

        $this->assertSame(4, $stats['products']['used']);
        $this->assertSame(10, $stats['products']['limit']);
    }

    // --- Ce que le plan Gratuit changerait ---------------------------------------

    public function test_l_impact_ne_remonte_que_ce_qui_depasse_vraiment(): void
    {
        $this->products(52);
        Client::factory()->count(3)->create(['user_id' => $this->user->id]);

        $impact = $this->plans()->freePlanImpact($this->user);

        // 52 articles dépassent les 10 ; 3 clients sur 10 ne dépassent pas.
        $this->assertSame([['type' => 'products', 'used' => 52, 'limit' => 10]], $impact);
    }

    public function test_un_compte_sobre_ne_recoit_aucun_impact(): void
    {
        $this->products(2);

        // Fabriquer une contrainte inexistante se retournerait contre nous à la
        // première vérification par l'utilisateur.
        $this->assertSame([], $this->plans()->freePlanImpact($this->user));
    }

    public function test_l_impact_cumule_plusieurs_compteurs(): void
    {
        $this->products(52);
        Client::factory()->count(14)->create(['user_id' => $this->user->id]);

        $types = array_column($this->plans()->freePlanImpact($this->user), 'type');

        $this->assertEqualsCanonicalizing(['products', 'clients'], $types);
    }

    // --- Le courriel de rappel ----------------------------------------------------

    public function test_le_rappel_cite_les_chiffres_du_compte(): void
    {
        $this->products(52);

        $rendu = (new TrialEndingSoon($this->user, 3))->render();

        // Le fait, pas l'argumentaire : « 52 articles, le plan Gratuit en
        // autorise 10 ».
        $this->assertStringContainsString('52', $rendu);
        $this->assertStringContainsString('10', $rendu);
        $this->assertStringContainsString(__('app.email_trial_your_usage_title'), $rendu);
    }

    public function test_le_rappel_rassure_avant_d_annoncer_les_chiffres(): void
    {
        $this->products(52);

        $rendu = (new TrialEndingSoon($this->user, 3))->render();

        $reassurance = mb_strpos($rendu, __('app.email_trial_usage_reassurance'));
        $chiffres = mb_strpos($rendu, __('app.email_trial_your_usage_intro'));

        $this->assertNotFalse($reassurance, 'La réassurance doit figurer dans le courriel.');
        $this->assertNotFalse($chiffres);

        // L'ordre est le point : annoncer un plafond sans dire d'abord ce
        // qu'il advient des données laisse imaginer une perte. La crainte naît
        // de l'ambiguïté, pas du chiffre.
        $this->assertLessThan(
            $chiffres,
            $reassurance,
            'La réassurance doit précéder les chiffres, pas les suivre.'
        );
    }

    public function test_le_rappel_annonce_les_prix_reels_des_plans(): void
    {
        $rendu = (new TrialEndingSoon($this->user, 3))->render();

        // Ces chiffres vivaient dans les traductions et y avaient dérivé : le
        // courriel annonçait 4 € et 9 € là où les plans facturent 5 € et 15 €.
        // Annoncer moins que ce qui sera prélevé se paie en confiance.
        $this->assertStringContainsString(
            (string) \App\Models\Plan::essentiel()->price_monthly_euros,
            $rendu
        );
        $this->assertStringContainsString(
            (string) \App\Models\Plan::pro()->price_monthly_euros,
            $rendu
        );
        $this->assertStringNotContainsString('4€', $rendu);
        $this->assertStringNotContainsString('9€', $rendu);
    }

    public function test_le_rappel_annonce_les_vrais_plafonds_d_essentiel(): void
    {
        $rendu = (new TrialEndingSoon($this->user, 3))->render();

        $essentiel = \App\Models\Plan::essentiel();

        // 100 clients et 50 factures, et non 10 et 20 comme l'affirmait le
        // texte figé : sous-vendre son propre plan au moment de convertir.
        $this->assertStringContainsString((string) $essentiel->getLimit('max_clients'), $rendu);
        $this->assertStringContainsString((string) $essentiel->getLimit('max_invoices_per_month'), $rendu);
    }

    public function test_le_rappel_n_invente_rien_pour_un_compte_sobre(): void
    {
        $this->products(2);

        $rendu = (new TrialEndingSoon($this->user, 3))->render();

        $this->assertStringNotContainsString(__('app.email_trial_your_usage_title'), $rendu);
    }
}
