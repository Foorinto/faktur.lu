<?php

namespace Tests\Feature;

use App\Models\BusinessSettings;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\User;
use App\Services\PeppolExportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Schéma d'identifiant Peppol des parties luxembourgeoises.
 *
 * Le Luxembourg est le code EAS **9938**. Le dépôt en portait deux autres,
 * cohérents entre eux et faux tous les deux : l'interface proposait `0184`
 * sous le libellé « Luxembourg VAT », qui est en réalité le Danemark, et le
 * code le convertissait vers `9934`, qui est la Croatie. Le défaut a survécu
 * parce qu'aucun test ne regardait la valeur produite : le XML restait bien
 * formé, seul le destinataire était faux.
 *
 * Les identifiants de test publiés par l'État luxembourgeois
 * (`9938:LU10889245-TEST`) tranchent la question.
 */
class PeppolSchemeTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['email_verified_at' => now()]);
        $this->actingAs($this->user);
    }

    /**
     * Facture prête à l'export, avec les schémas demandés de part et d'autre.
     */
    private function invoice(?string $sellerScheme, ?string $buyerScheme, string $buyerCountry = 'LU'): Invoice
    {
        BusinessSettings::factory()->create([
            'user_id' => $this->user->id,
            'country_code' => 'LU',
            'vat_number' => 'LU12345678',
            'peppol_endpoint_id' => 'LU12345678',
            'peppol_endpoint_scheme' => $sellerScheme,
        ]);

        $client = Client::factory()->create([
            'user_id' => $this->user->id,
            'country_code' => $buyerCountry,
            'peppol_endpoint_id' => 'LU87654321',
            'peppol_endpoint_scheme' => $buyerScheme,
        ]);

        // Facture émise : l'export Peppol refuse les brouillons, qui n'ont pas
        // encore de numéro. Les instantanés restent nuls pour que vendeur et
        // client soient résolus depuis les données vivantes, c'est-à-dire par
        // le chemin qui portait le défaut.
        $invoice = Invoice::factory()->create([
            'user_id' => $this->user->id,
            'client_id' => $client->id,
            'status' => Invoice::STATUS_FINALIZED,
            'number' => 'F-2026-001',
            'issued_at' => now(),
            'seller_snapshot' => null,
            'buyer_snapshot' => null,
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

    /** @return array<int, string> Les schemeID portés par les EndpointID, dans l'ordre. */
    private function endpointSchemes(Invoice $invoice): array
    {
        $xml = app(PeppolExportService::class)->generate($invoice);

        preg_match_all('/<cbc:EndpointID[^>]*schemeID="([^"]+)"/', $xml, $matches);

        return $matches[1];
    }

    // --- Le cas nominal ----------------------------------------------------------

    public function test_une_partie_luxembourgeoise_sans_schema_est_adressee_en_9938(): void
    {
        // Le cas de loin le plus fréquent : la colonne est nulle pour la quasi
        // totalité des comptes, tout passe donc par la valeur par défaut.
        $schemes = $this->endpointSchemes($this->invoice(null, null));

        $this->assertSame(['9938', '9938'], $schemes);
    }

    public function test_le_schema_luxembourgeois_est_bien_9938(): void
    {
        $this->assertSame('9938', PeppolExportService::SCHEME_LU_VAT);
    }

    // --- Réparation des valeurs déjà enregistrées --------------------------------

    /** @return array<int, array<int, string>> */
    public static function schemasErrones(): array
    {
        return [
            'danois proposé sous le libellé Luxembourg' => ['0184'],
            'croate écrit par l\'ancienne conversion' => ['9934'],
        ];
    }

    #[DataProvider('schemasErrones')]
    public function test_un_schema_errone_deja_enregistre_est_repare(string $errone): void
    {
        // Des utilisateurs ont enregistré ces valeurs en suivant l'interface.
        // Les corriger à la volée évite de leur demander une reprise manuelle.
        $schemes = $this->endpointSchemes($this->invoice($errone, $errone));

        $this->assertSame(['9938', '9938'], $schemes);
    }

    // --- Ne pas corriger au-delà du Luxembourg -----------------------------------

    public function test_un_participant_danois_garde_son_schema(): void
    {
        // 0184 est légitime pour le Danemark : la réparation ne doit pas
        // s'appliquer hors des parties luxembourgeoises, sinon elle casserait
        // les destinataires étrangers en réparant les nationaux.
        $schemes = $this->endpointSchemes($this->invoice(null, '0184', 'DK'));

        $this->assertSame('9938', $schemes[0], 'Le vendeur luxembourgeois reste en 9938.');
        $this->assertSame('0184', $schemes[1], 'Le client danois garde 0184.');
    }

    public function test_un_participant_croate_garde_son_schema(): void
    {
        $schemes = $this->endpointSchemes($this->invoice(null, '9934', 'HR'));

        $this->assertSame('9934', $schemes[1], 'Le client croate garde 9934.');
    }

    public function test_un_client_belge_garde_son_schema(): void
    {
        $schemes = $this->endpointSchemes($this->invoice(null, null, 'BE'));

        $this->assertSame(['9938', '0208'], $schemes);
    }

    // --- Ce que l'interface propose ----------------------------------------------

    public function test_la_liste_proposee_designe_le_luxembourg_par_9938(): void
    {
        $schemes = BusinessSettings::PEPPOL_SCHEMES;

        $this->assertArrayHasKey('9938', $schemes);
        $this->assertStringContainsString('Luxembourg', $schemes['9938']);
    }

    public function test_aucun_schema_etranger_n_est_libelle_luxembourg(): void
    {
        // La cause première : `0184 => 'Luxembourg VAT (LU)'` dans la liste
        // déroulante. Tant que ce test tient, l'erreur ne peut pas revenir.
        foreach (BusinessSettings::PEPPOL_SCHEMES as $code => $label) {
            if (str_contains($label, 'Luxembourg')) {
                // (string) parce que PHP transforme une clé numérique en entier.
                $this->assertSame(
                    PeppolExportService::SCHEME_LU_VAT,
                    (string) $code,
                    "Le code {$code} est présenté comme luxembourgeois alors qu'il ne l'est pas."
                );
            }
        }
    }
}
