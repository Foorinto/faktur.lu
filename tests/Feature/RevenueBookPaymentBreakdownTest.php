<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Ventilation des encaissements par moyen, dans le livre de recettes (FEAT-114).
 *
 * C'est la demande d'origine du client : « avoir le calcul des différents modes
 * de paiement pour la comptabilité ».
 *
 * Le point délicat n'est pas la somme, c'est la PÉRIODE. Cette ventilation lit
 * les encaissements à leur date réelle, quand la liste des factures ci-dessus
 * lit `paid_at`, qui vaut la date du dernier règlement. Une facture réglée à
 * cheval sur deux mois se répartit donc ici, et pas là.
 */
class RevenueBookPaymentBreakdownTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Essai en cours : le livre de recettes est derrière
        // `plan.feature:accounting_exports`, absent du plan gratuit.
        $this->user = User::factory()->create([
            'email_verified_at' => now(),
            'trial_ends_at' => now()->addDays(14),
        ]);
    }

    private function facture(float $ttc, string $statut = Invoice::STATUS_SENT): Invoice
    {
        $client = Client::factory()->create(['user_id' => $this->user->id]);

        return Invoice::factory()->create([
            'user_id' => $this->user->id,
            'client_id' => $client->id,
            'status' => $statut,
            'finalized_at' => now()->subMonths(3),
            'total_ht' => $ttc, 'total_vat' => 0, 'total_ttc' => $ttc,
        ]);
    }

    public function test_the_breakdown_groups_amounts_by_method(): void
    {
        $facture = $this->facture(1000);

        $facture->payments()->createMany([
            ['amount' => 300, 'paid_at' => '2026-03-10', 'method' => 'cash'],
            ['amount' => 200, 'paid_at' => '2026-03-15', 'method' => 'cash'],
            ['amount' => 500, 'paid_at' => '2026-03-20', 'method' => 'transfer'],
        ]);

        $this->actingAs($this->user)
            ->get('/reports/revenue-book?start_date=2026-03-01&end_date=2026-03-31')
            ->assertOk()
            ->assertInertia(function ($page) {
                $lignes = collect($page->toArray()['props']['parMoyenDePaiement']['lignes']);

                $especes = $lignes->firstWhere('method', 'cash');
                $virement = $lignes->firstWhere('method', 'transfer');

                $this->assertSame(500.0, (float) $especes['total']);
                $this->assertSame(2, $especes['nombre']);
                $this->assertSame(500.0, (float) $virement['total']);
                $this->assertSame(1, $virement['nombre']);
            });
    }

    /**
     * Le point qui justifie de lire les encaissements plutôt que les factures.
     *
     * 300 € en mars, 700 € en avril : la facture porte un `paid_at` d'avril et
     * la liste lui attribue 1 000 € en avril. La ventilation, elle, place
     * l'argent dans le mois où il est rentré.
     */
    public function test_a_payment_straddling_two_months_falls_in_the_right_one(): void
    {
        $facture = $this->facture(1000);

        $facture->payments()->createMany([
            ['amount' => 300, 'paid_at' => '2026-03-25', 'method' => 'cash'],
            ['amount' => 700, 'paid_at' => '2026-04-05', 'method' => 'transfer'],
        ]);

        $this->actingAs($this->user)
            ->get('/reports/revenue-book?start_date=2026-03-01&end_date=2026-03-31')
            ->assertOk()
            ->assertInertia(function ($page) {
                $ventilation = $page->toArray()['props']['parMoyenDePaiement'];

                $this->assertSame(300.0, (float) $ventilation['total'],
                    'Mars ne doit porter que les 300 € réellement encaissés en mars.'
                );
            });
    }

    /**
     * Les encaissements sans moyen se comptent, et se nomment.
     */
    public function test_payments_without_a_method_are_shown_as_unknown(): void
    {
        $facture = $this->facture(400);

        $facture->payments()->create(['amount' => 400, 'paid_at' => '2026-03-12', 'method' => null]);

        $this->actingAs($this->user)
            ->get('/reports/revenue-book?start_date=2026-03-01&end_date=2026-03-31')
            ->assertOk()
            ->assertInertia(function ($page) {
                $ligne = collect($page->toArray()['props']['parMoyenDePaiement']['lignes'])->first();

                $this->assertNull($ligne['method']);
                $this->assertSame(__('app.payment_methods.unknown'), $ligne['label']);
            });
    }

    /**
     * Les encaissements d'un autre utilisateur n'entrent pas dans le compte.
     */
    // --- Le PDF, pièce à joindre à un dépôt d'espèces -------------------------

    private function texteDuPdf(string $debut, string $fin): string
    {
        $reponse = $this->actingAs($this->user)
            ->get("/reports/revenue-book/pdf?start_date={$debut}&end_date={$fin}");

        $reponse->assertOk();

        $fichier = tempnam(sys_get_temp_dir(), 'lr').'.pdf';
        file_put_contents($fichier, $reponse->getContent());
        $texte = shell_exec('pdftotext '.escapeshellarg($fichier).' - 2>/dev/null') ?: '';
        @unlink($fichier);

        return $texte;
    }

    /**
     * La demande d'origine : sans la ventilation, le PDF affiche un total et ne
     * prouve rien à un banquier qui voit arriver un dépôt d'espèces.
     */
    public function test_le_pdf_porte_la_ventilation_par_moyen_de_paiement(): void
    {
        $facture = $this->facture(1000, Invoice::STATUS_PAID);
        $facture->update(['paid_at' => '2026-03-20']);

        $facture->payments()->createMany([
            ['amount' => 400, 'paid_at' => '2026-03-10', 'method' => 'cash'],
            ['amount' => 600, 'paid_at' => '2026-03-20', 'method' => 'transfer'],
        ]);

        $texte = $this->texteDuPdf('2026-03-01', '2026-03-31');

        if ($texte === '') {
            $this->markTestSkipped('pdftotext absent : lecture du PDF impossible.');
        }

        $this->assertStringContainsString(__('app.pdf_payment_methods_title'), $texte);
        $this->assertStringContainsString('400,00', $texte, 'Le total encaissé en espèces.');
        $this->assertStringContainsString('600,00', $texte, 'Le total encaissé par virement.');
    }

    /**
     * Le détail des espèces, et d'elles seules : c'est la pièce jointe au dépôt.
     */
    public function test_le_pdf_detaille_les_encaissements_en_especes(): void
    {
        $facture = $this->facture(500, Invoice::STATUS_PAID);
        $facture->update(['paid_at' => '2026-03-18']);

        $facture->payments()->createMany([
            ['amount' => 200, 'paid_at' => '2026-03-05', 'method' => 'cash'],
            ['amount' => 300, 'paid_at' => '2026-03-18', 'method' => 'cash'],
        ]);

        $texte = $this->texteDuPdf('2026-03-01', '2026-03-31');

        if ($texte === '') {
            $this->markTestSkipped('pdftotext absent : lecture du PDF impossible.');
        }

        $this->assertStringContainsString(__('app.pdf_cash_detail_title'), $texte);
        $this->assertStringContainsString('05/03/2026', $texte);
        $this->assertStringContainsString('18/03/2026', $texte);
        $this->assertStringContainsString('200,00', $texte);
        $this->assertStringContainsString('300,00', $texte);
    }

    /**
     * Celui qui ne manipule pas d'espèces ne doit pas voir la section.
     */
    public function test_sans_especes_le_pdf_ne_porte_aucun_detail(): void
    {
        $facture = $this->facture(800, Invoice::STATUS_PAID);
        $facture->update(['paid_at' => '2026-03-12']);

        $facture->payments()->create(['amount' => 800, 'paid_at' => '2026-03-12', 'method' => 'transfer']);

        $texte = $this->texteDuPdf('2026-03-01', '2026-03-31');

        if ($texte === '') {
            $this->markTestSkipped('pdftotext absent : lecture du PDF impossible.');
        }

        $this->assertStringContainsString(__('app.pdf_payment_methods_title'), $texte);
        $this->assertStringNotContainsString(__('app.pdf_cash_detail_title'), $texte);
    }

    /**
     * ⚠️ Le détail des espèces est une requête neuve, hors du service commun.
     * Une fuite y mettrait les encaissements d'un autre compte dans un document
     * remis à une banque.
     */
    public function test_le_detail_des_especes_ne_montre_que_ses_propres_encaissements(): void
    {
        $autre = User::factory()->create(['email_verified_at' => now()]);
        $clientAutre = Client::factory()->create(['user_id' => $autre->id]);
        $factureAutre = Invoice::factory()->create([
            'user_id' => $autre->id,
            'client_id' => $clientAutre->id,
            'status' => Invoice::STATUS_PAID,
            'finalized_at' => now()->subMonths(3),
            'paid_at' => '2026-03-10',
            'total_ht' => 999, 'total_vat' => 0, 'total_ttc' => 999,
        ]);
        $factureAutre->payments()->create(['amount' => 999, 'paid_at' => '2026-03-10', 'method' => 'cash']);

        $mienne = $this->facture(100, Invoice::STATUS_PAID);
        $mienne->update(['paid_at' => '2026-03-11']);
        $mienne->payments()->create(['amount' => 100, 'paid_at' => '2026-03-11', 'method' => 'cash']);

        $texte = $this->texteDuPdf('2026-03-01', '2026-03-31');

        if ($texte === '') {
            $this->markTestSkipped('pdftotext absent : lecture du PDF impossible.');
        }

        $this->assertStringContainsString('100,00', $texte);
        $this->assertStringNotContainsString('999,00', $texte, "Les espèces d'un autre compte n'apparaissent jamais.");
        $this->assertStringNotContainsString($clientAutre->name, $texte);
    }

    /**
     * Une période sans le moindre encaissement ne doit ni casser le PDF ni
     * afficher de section vide.
     */
    public function test_une_periode_sans_encaissement_produit_un_pdf_sans_sections(): void
    {
        $texte = $this->texteDuPdf('2026-01-01', '2026-01-31');

        if ($texte === '') {
            $this->markTestSkipped('pdftotext absent : lecture du PDF impossible.');
        }

        $this->assertStringNotContainsString(__('app.pdf_payment_methods_title'), $texte);
        $this->assertStringNotContainsString(__('app.pdf_cash_detail_title'), $texte);
    }

    /**
     * Les six clés ajoutées existent dans les cinq langues, et le PDF les rend.
     *
     * @dataProvider langues
     */
    public function test_le_pdf_est_traduit_dans_toutes_les_langues(string $locale): void
    {
        $this->user->update(['locale' => $locale]);
        app()->setLocale($locale);

        $facture = $this->facture(300, Invoice::STATUS_PAID);
        $facture->update(['paid_at' => '2026-03-08']);
        $facture->payments()->create(['amount' => 300, 'paid_at' => '2026-03-08', 'method' => 'cash']);

        $texte = $this->texteDuPdf('2026-03-01', '2026-03-31');

        if ($texte === '') {
            $this->markTestSkipped('pdftotext absent : lecture du PDF impossible.');
        }

        foreach (['pdf_payment_methods_title', 'pdf_payment_method', 'pdf_payment_count', 'pdf_payment_share', 'pdf_cash_detail_title', 'pdf_cash_total'] as $cle) {
            $traduction = __("app.{$cle}", [], $locale);
            $this->assertNotSame("app.{$cle}", $traduction, "Clé absente en {$locale} : {$cle}");
            $this->assertStringContainsString($traduction, $texte, "Non rendu en {$locale} : {$cle}");
        }
    }

    public static function langues(): array
    {
        return [['fr'], ['de'], ['en'], ['lb'], ['pt']];
    }

    public function test_the_breakdown_is_scoped_to_the_user(): void
    {
        $this->facture(500)->payments()->create([
            'amount' => 500, 'paid_at' => '2026-03-10', 'method' => 'cash',
        ]);

        $autre = User::factory()->create([
            'email_verified_at' => now(),
            'trial_ends_at' => now()->addDays(14),
        ]);
        $clientAutre = Client::factory()->create(['user_id' => $autre->id]);
        Invoice::factory()->create([
            'user_id' => $autre->id, 'client_id' => $clientAutre->id,
            'status' => Invoice::STATUS_SENT, 'finalized_at' => now()->subMonths(3),
            'total_ht' => 9999, 'total_vat' => 0, 'total_ttc' => 9999,
        ])->payments()->create(['amount' => 9999, 'paid_at' => '2026-03-11', 'method' => 'cash']);

        $this->actingAs($this->user)
            ->get('/reports/revenue-book?start_date=2026-03-01&end_date=2026-03-31')
            ->assertOk()
            ->assertInertia(function ($page) {
                $this->assertSame(
                    500.0,
                    (float) $page->toArray()['props']['parMoyenDePaiement']['total'],
                    "Les encaissements d'un autre utilisateur ne doivent pas être comptés."
                );
            });
    }
}
