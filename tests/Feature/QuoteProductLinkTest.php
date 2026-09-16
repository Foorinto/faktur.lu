<?php

namespace Tests\Feature;

use App\Actions\ConvertQuoteToInvoiceAction;
use App\Actions\FinalizeInvoiceAction;
use App\Models\BusinessSettings;
use App\Models\Client;
use App\Models\Product;
use App\Models\Quote;
use App\Models\User;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Lien d'une ligne de devis vers le produit du catalogue (FEAT-116, suite de revue).
 *
 * Relevé lors de la repasse : un produit devisé puis facturé arrivait sur la
 * facture en texte libre, et le stock ne bougeait pas à l'émission. Le lien
 * doit survivre à toute la chaîne : saisie du devis, conversion, émission.
 */
class QuoteProductLinkTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['email_verified_at' => now()]);
        $this->actingAs($this->user);
        BusinessSettings::factory()->assujetti()->create(['user_id' => $this->user->id]);
    }

    private function devis(): Quote
    {
        $client = Client::factory()->create(['user_id' => $this->user->id]);

        return Quote::create([
            'client_id' => $client->id,
            'currency' => 'EUR',
            'status' => Quote::STATUS_ACCEPTED,
            'total_ht' => 0, 'total_vat' => 0, 'total_ttc' => 0,
        ]);
    }

    public function test_une_ligne_de_devis_issue_du_catalogue_retient_le_produit(): void
    {
        $devis = $this->devis();
        $devis->update(['status' => Quote::STATUS_DRAFT]);
        $product = Product::factory()->create(['user_id' => $this->user->id]);

        $this->post(route('quotes.items.store', $devis->id), [
            'product_id' => $product->id,
            'title' => $product->designation,
            'quantity' => 2,
            'unit_price' => 100,
            'vat_rate' => 17,
        ])->assertSessionHasNoErrors();

        $this->assertSame($product->id, $devis->items()->latest('id')->first()->product_id);
    }

    public function test_le_produit_d_un_autre_compte_est_refuse_sur_un_devis(): void
    {
        $devis = $this->devis();
        $devis->update(['status' => Quote::STATUS_DRAFT]);
        $autrui = Product::factory()->create(['user_id' => User::factory()->create()->id]);

        $this->post(route('quotes.items.store', $devis->id), [
            'product_id' => $autrui->id,
            'title' => 'Tentative',
            'quantity' => 1,
            'unit_price' => 100,
            'vat_rate' => 17,
        ])->assertSessionHasErrors('product_id');
    }

    public function test_la_conversion_en_facture_transporte_le_produit(): void
    {
        $devis = $this->devis();
        $product = Product::factory()->create(['user_id' => $this->user->id]);

        $devis->items()->create([
            'product_id' => $product->id,
            'title' => $product->designation,
            'quantity' => 1, 'unit_price' => 100, 'vat_rate' => 17, 'total_ht' => 100, 'position' => 1,
        ]);

        $facture = app(ConvertQuoteToInvoiceAction::class)->execute($devis->fresh());

        $this->assertSame($product->id, $facture->items()->first()->product_id);
    }

    public function test_un_produit_devise_puis_facture_sort_du_stock_a_l_emission(): void
    {
        // Toute la chaîne, telle que le client la vit : devis depuis le
        // catalogue → facture → émission → le stock baisse.
        $product = Product::factory()->create([
            'user_id' => $this->user->id,
            'type' => Product::TYPE_PRODUCT,
            'track_stock' => true,
        ]);
        app(StockService::class)->recordEntry($product, 10, 20.0);

        $devis = $this->devis();
        $devis->items()->create([
            'product_id' => $product->id,
            'title' => $product->designation,
            'quantity' => 3, 'unit_price' => 100, 'vat_rate' => 17, 'total_ht' => 300, 'position' => 1,
        ]);

        $facture = app(ConvertQuoteToInvoiceAction::class)->execute($devis->fresh());
        $this->assertSame(10.0, $product->fresh()->currentStock(), 'Un brouillon ne bouge rien.');

        app(FinalizeInvoiceAction::class)->execute($facture->fresh(['items', 'client']));

        $this->assertSame(7.0, $product->fresh()->currentStock(), '10 en stock - 3 devisés et facturés.');
    }
}
