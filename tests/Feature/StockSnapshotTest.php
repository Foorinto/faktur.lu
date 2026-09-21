<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use App\Services\StockSnapshot;
use Database\Seeders\PlansSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * L'état du stock groupé, et son accord avec le modèle.
 *
 * Deux chemins calculent le même stock : les méthodes du modèle, pour un
 * article isolé, et `StockSnapshot`, pour une page entière. S'ils divergeaient,
 * la fiche d'un article et la liste du stock afficheraient deux chiffres
 * différents pour la même marchandise, sans que rien ne le signale.
 */
class StockSnapshotTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PlansSeeder::class);

        $this->user = User::factory()->create([
            'email_verified_at' => now(),
            'trial_ends_at' => now()->addDays(14),
        ]);
        $this->actingAs($this->user);
    }

    private function article(array $surcharge = []): Product
    {
        return Product::factory()->create(array_merge([
            'user_id' => $this->user->id,
            'track_stock' => true,
        ], $surcharge));
    }

    private function mouvement(Product $p, float $quantite, ?float $cout = null, string $type = 'in'): void
    {
        StockMovement::create([
            'user_id' => $this->user->id,
            'product_id' => $p->id,
            'type' => $type,
            'quantity' => $quantite,
            'unit_cost' => $cout,
            'date' => now()->toDateString(),
        ]);
    }

    public function test_l_etat_groupe_dit_la_meme_chose_que_le_modele(): void
    {
        $famille = $this->article(['designation' => 'Souris', 'reference' => 'SOU']);
        $this->post(route('products.variants.store', $famille), ['labels' => "Blanche\nVerte"]);

        $variantes = $famille->fresh()->variants;
        $variantes->each(fn ($v) => $v->update(['track_stock' => true]));

        // Des cas qui se ressemblent peu : une entrée valorisée, une sans coût,
        // une sortie, et une déclinaison sans aucun mouvement.
        $this->mouvement($famille, 10, 4);
        $this->mouvement($variantes[0], 200, 3);
        $this->mouvement($variantes[0], 50);
        $this->mouvement($variantes[0], -20, null, 'out');

        $seul = $this->article(['designation' => 'Écran', 'reference' => 'ECR']);
        $this->mouvement($seul, 7, 120);

        $tous = Product::where('track_stock', true)->get();
        $etat = new StockSnapshot($tous);

        foreach ($tous as $p) {
            $id = (int) $p->id;

            $this->assertEqualsWithDelta(
                $p->currentStock(), $etat->stock($id), 0.0001,
                "stock propre de {$p->designation}"
            );
            $this->assertEqualsWithDelta(
                $p->stockValue(), $etat->valeur($id), 0.01,
                "valeur propre de {$p->designation}"
            );
            $this->assertEqualsWithDelta(
                $p->stockDeLaFamille(), $etat->stockDeLaFamille($id), 0.0001,
                "stock de famille de {$p->designation}"
            );
            $this->assertEqualsWithDelta(
                $p->valeurDeLaFamille(), $etat->valeurDeLaFamille($id), 0.01,
                "valeur de famille de {$p->designation}"
            );
            $this->assertSame(
                $p->isFamily(), $etat->estUneFamille($id),
                "nature de {$p->designation}"
            );
        }
    }

    public function test_le_seuil_d_alerte_donne_le_meme_verdict_des_deux_cotes(): void
    {
        $famille = $this->article(['designation' => 'Souris', 'stock_alert_threshold' => 5]);
        $this->post(route('products.variants.store', $famille), ['labels' => 'Blanche']);
        $variante = $famille->fresh()->variants->first();
        $variante->update(['track_stock' => true, 'stock_alert_threshold' => 5]);

        // Famille fournie par sa nuance, nuance elle-même au-dessus du seuil.
        $this->mouvement($variante, 40);

        $tous = Product::where('track_stock', true)->get();
        $etat = new StockSnapshot($tous);

        foreach ($tous as $p) {
            $this->assertSame(
                $p->isLowOnStock(), $etat->estSousLeSeuil($p),
                "verdict de seuil pour {$p->designation}"
            );
        }

        // Et le verdict attendu : ni l'une ni l'autre n'est en rupture.
        $this->assertFalse($etat->estSousLeSeuil($famille->fresh()));
    }

    public function test_le_cout_d_un_article_sans_entree_valorisee_vaut_zero(): void
    {
        $article = $this->article();
        $this->mouvement($article, 30);

        $etat = new StockSnapshot(Product::where('track_stock', true)->get());

        $this->assertEqualsWithDelta(30, $etat->stock((int) $article->id), 0.0001);
        $this->assertEqualsWithDelta(0, $etat->valeur((int) $article->id), 0.0001);
        $this->assertEqualsWithDelta($article->stockValue(), $etat->valeur((int) $article->id), 0.01);
    }

    public function test_une_declinaison_hors_de_la_liste_compte_quand_meme_dans_sa_famille(): void
    {
        // Le tableau de bord ne charge que les articles à seuil : si la
        // déclinaison n'en a pas, elle manquerait au total de sa famille et
        // l'alerte se déclencherait sur un stock incomplet.
        $famille = $this->article(['designation' => 'Souris', 'stock_alert_threshold' => 5]);
        $this->post(route('products.variants.store', $famille), ['labels' => 'Blanche']);
        $variante = $famille->fresh()->variants->first();
        $variante->update(['track_stock' => true, 'stock_alert_threshold' => null]);
        $this->mouvement($variante, 40);

        $aSeuil = Product::where('track_stock', true)->whereNotNull('stock_alert_threshold')->get();
        $this->assertCount(1, $aSeuil);

        $etat = new StockSnapshot($aSeuil);

        $this->assertEqualsWithDelta(40, $etat->stockDeLaFamille((int) $famille->id), 0.0001);
        $this->assertFalse($etat->estSousLeSeuil($famille->fresh()));
    }

    public function test_le_nombre_de_requetes_ne_grandit_pas_avec_le_catalogue(): void
    {
        // La garde qui compte : c'est la croissance qui tue une page, pas le
        // chiffre absolu. Deux comptes distincts plutôt qu'un vidage, les
        // mouvements de stock retenant leurs articles par clé étrangère.
        $compter = function (int $familles): int {
            $proprietaire = User::factory()->create([
                'email_verified_at' => now(),
                'trial_ends_at' => now()->addDays(14),
            ]);
            $this->actingAs($proprietaire);

            for ($i = 0; $i < $familles; $i++) {
                $f = Product::factory()->create([
                    'user_id' => $proprietaire->id,
                    'designation' => "Article {$i}",
                    'reference' => "A{$i}",
                    'track_stock' => true,
                ]);
                $this->mouvementPour($proprietaire, $f, 5, 2);

                foreach (['A', 'B'] as $label) {
                    $v = Product::create([
                        'user_id' => $proprietaire->id,
                        'parent_id' => $f->id,
                        'designation' => $f->designation,
                        'variant_label' => $label,
                        'unit_price_ht' => 1,
                        'vat_rate' => 17,
                        'track_stock' => true,
                    ]);
                    $this->mouvementPour($proprietaire, $v, 10, 3);
                }
            }

            $produits = Product::where('track_stock', true)->get();

            DB::flushQueryLog();
            DB::enableQueryLog();
            new StockSnapshot($produits);
            $n = count(DB::getQueryLog());
            DB::disableQueryLog();

            return $n;
        };

        $petit = $compter(2);
        $grand = $compter(20);

        $this->assertSame($petit, $grand, 'le coût doit être le même à 2 et à 20 familles');
        $this->assertLessThanOrEqual(3, $grand);
    }

    private function mouvementPour(User $proprietaire, Product $p, float $quantite, ?float $cout = null): void
    {
        StockMovement::create([
            'user_id' => $proprietaire->id,
            'product_id' => $p->id,
            'type' => 'in',
            'quantity' => $quantite,
            'unit_cost' => $cout,
            'date' => now()->toDateString(),
        ]);
    }
}
