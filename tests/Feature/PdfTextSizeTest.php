<?php

namespace Tests\Feature;

use App\Models\BusinessSettings;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\User;
use App\Services\InvoicePdfService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Taille du texte des documents PDF (FEAT-109).
 *
 * Deux exigences se tiennent en tension et sont testées ensemble :
 *
 * - le réglage doit **agir** : un utilisateur qui choisit « grande » doit voir
 *   son document grossir, hiérarchie typographique conservée ;
 * - il ne doit **jamais rejouer le passé** : réimprimer une facture émise doit
 *   redonner le même document, à la virgule près. Même principe que la remise
 *   permanente de FEAT-108, pour la même raison.
 */
class PdfTextSizeTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['email_verified_at' => now()]);
        $this->actingAs($this->user);
        BusinessSettings::factory()->create(['user_id' => $this->user->id]);
    }

    private function draft(): Invoice
    {
        $client = Client::factory()->create(['user_id' => $this->user->id]);

        $invoice = Invoice::factory()->create([
            'user_id' => $this->user->id,
            'client_id' => $client->id,
            'status' => Invoice::STATUS_DRAFT,
            'seller_snapshot' => null,
        ]);

        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'title' => 'Prestation',
            'quantity' => 1,
            'unit_price' => 100,
            'vat_rate' => 17,
        ]);

        return $invoice->fresh();
    }

    /** @return array<int, float> Tailles en points présentes dans le rendu, triées. */
    private function fontSizes(string $html): array
    {
        preg_match_all('/font-size:\s*([0-9.]+)pt/', $html, $matches);

        $sizes = array_map('floatval', array_unique($matches[1]));
        sort($sizes);

        return $sizes;
    }

    private function renderDraft(Invoice $invoice): string
    {
        return app(InvoicePdfService::class)->previewDraft($invoice);
    }

    private function setSize(?string $size): void
    {
        BusinessSettings::getInstance()->update(['pdf_text_size' => $size]);
    }

    // --- Le réglage agit --------------------------------------------------------

    public function test_le_reglage_grandit_reellement_le_texte(): void
    {
        $invoice = $this->draft();

        $this->setSize('normal');
        $normal = $this->fontSizes($this->renderDraft($invoice));

        $this->setSize('large');
        $large = $this->fontSizes($this->renderDraft($invoice));

        $this->assertNotEmpty($normal);
        $this->assertSame(count($normal), count($large), 'Le nombre de tailles distinctes doit être conservé.');
        $this->assertGreaterThan(max($normal), max($large));
        $this->assertGreaterThan(min($normal), min($large));
    }

    public function test_la_hierarchie_typographique_est_conservee(): void
    {
        $invoice = $this->draft();

        $this->setSize('normal');
        $normal = $this->fontSizes($this->renderDraft($invoice));

        $this->setSize('xlarge');
        $xlarge = $this->fontSizes($this->renderDraft($invoice));

        // Un facteur unique : chaque taille garde son rang, et le rapport entre
        // la plus grande et la plus petite reste identique. C'est ce qu'une
        // redéfinition taille par taille aurait cassé.
        $this->assertEqualsWithDelta(
            max($normal) / min($normal),
            max($xlarge) / min($xlarge),
            0.01
        );
    }

    public function test_aucune_taille_absolue_n_echappe_au_reglage(): void
    {
        $invoice = $this->draft();

        $this->setSize('normal');
        $normal = $this->fontSizes($this->renderDraft($invoice));

        $this->setSize('xlarge');
        $xlarge = $this->fontSizes($this->renderDraft($invoice));

        $attendu = array_map(
            fn (float $pt) => round($pt * BusinessSettings::PDF_TEXT_SIZES['xlarge'], 2),
            $normal
        );

        // Comparaison terme à terme : une taille oubliée dans le gabarit
        // resterait à sa valeur d'origine et ferait tomber ce test, là où une
        // simple comparaison des extrêmes ne l'aurait pas vue.
        $this->assertEquals($attendu, $xlarge);
    }

    // --- Le passé ne bouge pas ---------------------------------------------------

    public function test_une_facture_emise_avant_ce_reglage_se_rend_a_l_identique(): void
    {
        $invoice = $this->draft();

        $this->setSize('normal');
        $avant = $this->fontSizes($this->renderDraft($invoice));

        // Instantané d'une facture finalisée avant FEAT-109 : la clé n'y figure
        // pas. Le document doit se rendre comme il l'a toujours fait.
        $snapshot = BusinessSettings::getInstance()->toSnapshot();
        unset($snapshot['pdf_text_size']);
        $invoice->update(['status' => Invoice::STATUS_FINALIZED, 'seller_snapshot' => $snapshot]);

        $this->setSize('xlarge');

        $rendu = app(InvoicePdfService::class)->preview($invoice->fresh());

        $this->assertSame($avant, $this->fontSizes($rendu));
    }

    public function test_une_facture_finalisee_garde_la_taille_figee_dans_son_instantane(): void
    {
        $invoice = $this->draft();

        $this->setSize('large');
        $snapshot = BusinessSettings::getInstance()->fresh()->toSnapshot();
        $invoice->update(['status' => Invoice::STATUS_FINALIZED, 'seller_snapshot' => $snapshot]);

        $fige = $this->fontSizes(app(InvoicePdfService::class)->preview($invoice->fresh()));

        // Le réglage change ensuite : la facture émise ne doit pas suivre.
        $this->setSize('normal');

        $this->assertSame($fige, $this->fontSizes(app(InvoicePdfService::class)->preview($invoice->fresh())));
    }

    public function test_l_instantane_transporte_la_taille(): void
    {
        $this->setSize('xlarge');

        $this->assertSame('xlarge', BusinessSettings::getInstance()->fresh()->toSnapshot()['pdf_text_size']);
    }

    // --- Réglage et validation ---------------------------------------------------

    public function test_une_taille_inconnue_est_refusee(): void
    {
        $this->putJson(route('settings.business.update'), [
            'legal_name' => 'Test SARL',
            'email' => 'test@example.com',
            'pdf_text_size' => 'gigantesque',
        ])->assertStatus(422);
    }

    public function test_une_taille_absente_retombe_sur_normale(): void
    {
        $invoice = $this->draft();

        $this->setSize('normal');
        $normal = $this->fontSizes($this->renderDraft($invoice));

        $this->setSize(null);

        $this->assertSame($normal, $this->fontSizes($this->renderDraft($invoice)));
    }
}
