<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Lien d'une ligne de facture vers le produit du catalogue (FEAT-116, lot 1).
 *
 * Préalable à la gestion de stock : c'est ce lien, et lui seul, qui permettra de
 * décrémenter le stock à l'émission sans deviner. Une ligne saisie à la main
 * n'a pas de produit ; un produit d'un autre compte ne doit jamais s'y glisser.
 */
class InvoiceItemProductLinkTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['email_verified_at' => now()]);
        $this->actingAs($this->user);
    }

    private function draft(): Invoice
    {
        $client = Client::factory()->create(['user_id' => $this->user->id]);

        return Invoice::factory()->create([
            'user_id' => $this->user->id,
            'client_id' => $client->id,
            'status' => Invoice::STATUS_DRAFT,
        ]);
    }

    public function test_une_ligne_issue_du_catalogue_retient_le_produit(): void
    {
        $invoice = $this->draft();
        $product = Product::factory()->create(['user_id' => $this->user->id]);

        $this->post(route('invoices.items.store', $invoice->id), [
            'product_id' => $product->id,
            'title' => $product->designation,
            'quantity' => 2,
            'unit_price' => 100,
            'vat_rate' => 17,
        ])->assertSessionHasNoErrors();

        $item = $invoice->items()->latest('id')->first();
        $this->assertSame($product->id, $item->product_id);
    }

    public function test_une_ligne_saisie_a_la_main_n_a_pas_de_produit(): void
    {
        $invoice = $this->draft();

        $this->post(route('invoices.items.store', $invoice->id), [
            'title' => 'Prestation sur mesure',
            'quantity' => 1,
            'unit_price' => 500,
            'vat_rate' => 17,
        ])->assertSessionHasNoErrors();

        $item = $invoice->items()->latest('id')->first();
        $this->assertNull($item->product_id);
    }

    public function test_le_produit_d_un_autre_compte_est_refuse(): void
    {
        $invoice = $this->draft();
        $autrui = Product::factory()->create(['user_id' => User::factory()->create()->id]);

        $this->post(route('invoices.items.store', $invoice->id), [
            'product_id' => $autrui->id,
            'title' => 'Tentative',
            'quantity' => 1,
            'unit_price' => 100,
            'vat_rate' => 17,
        ])->assertSessionHasErrors('product_id');

        $this->assertSame(0, $invoice->items()->count());
    }

    public function test_la_modification_peut_dissocier_la_ligne_de_son_produit(): void
    {
        $invoice = $this->draft();
        $product = Product::factory()->create(['user_id' => $this->user->id]);

        $item = InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'product_id' => $product->id,
            'title' => $product->designation,
            'quantity' => 1,
            'unit_price' => 100,
            'vat_rate' => 17,
        ]);

        $this->put(route('invoices.items.update', [$invoice->id, $item->id]), [
            'product_id' => null,
            'title' => 'Devenue une prestation libre',
            'quantity' => 1,
            'unit_price' => 100,
            'vat_rate' => 17,
        ])->assertSessionHasNoErrors();

        $this->assertNull($item->fresh()->product_id);
    }

    public function test_la_duplication_conserve_le_produit_et_le_compte_de_la_ligne(): void
    {
        // Relevé en revue : duplicate() recopiait la ligne sans son lien produit
        // ni son compte comptable — la copie ne décrémentait plus le stock et
        // repartait sur le compte de ventes générique.
        $invoice = $this->draft();
        $product = Product::factory()->create(['user_id' => $this->user->id]);

        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'product_id' => $product->id,
            'pcn_account' => '7061',
            'title' => $product->designation,
            'quantity' => 2,
            'unit_price' => 50,
            'vat_rate' => 17,
        ]);

        $this->post(route('invoices.duplicate', $invoice->id))->assertRedirect();

        $copie = Invoice::where('id', '!=', $invoice->id)->latest('id')->first();
        $ligne = $copie->items()->first();

        $this->assertSame($product->id, $ligne->product_id, 'Le lien produit doit survivre à la duplication.');
        $this->assertSame('7061', $ligne->pcn_account, 'Le compte comptable doit survivre à la duplication.');
    }

    public function test_le_produit_supprime_delie_la_ligne_sans_la_detruire(): void
    {
        $invoice = $this->draft();
        $product = Product::factory()->create(['user_id' => $this->user->id]);

        $item = InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'product_id' => $product->id,
            'title' => $product->designation,
            'quantity' => 1,
            'unit_price' => 100,
            'vat_rate' => 17,
        ]);

        // Suppression définitive du produit (le catalogue est en SoftDeletes ;
        // on force la contrainte SQL en supprimant réellement la ligne).
        $product->forceDelete();

        $item->refresh();
        $this->assertNull($item->product_id, 'La ligne se dissocie du produit supprimé.');
        $this->assertNotNull($item->title, 'La ligne de facture survit à la suppression du produit.');
    }
}
