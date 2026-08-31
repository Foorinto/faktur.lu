<?php

namespace Tests\Feature;

use App\Actions\FinalizeInvoiceAction;
use App\Models\BusinessSettings;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * On ne finalise pas une facture sans émetteur identifiable.
 *
 * L'auteur a ouvert une facture de démonstration et n'y a trouvé aucun
 * émetteur (2026-08-29). C'était un artefact de données créées hors du
 * formulaire — mais sa question était la bonne : la finalisation vérifiait que
 * l'enregistrement des réglages EXISTE, pas qu'il dise quelque chose.
 *
 * Un enregistrement vide produit un document sans en-tête, conservé dix ans,
 * sans que rien ne le signale. Une facture qui n'identifie pas son émetteur
 * n'est pas une facture.
 *
 * ⚠️ Le contrôle porte sur le MINIMUM identifiant — un nom et une adresse — et
 * non sur tous les champs exigés par le formulaire. Refuser sur un code postal
 * manquant bloquerait un utilisateur qui facture aujourd'hui, pour un défaut
 * qui ne rend pas le document anonyme.
 */
class FinalizeRequiresIssuerIdentityTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['email_verified_at' => now()]);
        $this->actingAs($this->user);
    }

    private function brouillon(): Invoice
    {
        $client = Client::factory()->create(['user_id' => $this->user->id]);

        $facture = Invoice::factory()->create([
            'user_id' => $this->user->id,
            'client_id' => $client->id,
            'status' => Invoice::STATUS_DRAFT,
            'finalized_at' => null,
            'number' => null,
        ]);

        $facture->items()->create([
            'title' => 'Prestation',
            'quantity' => 1,
            'unit_price' => 100,
            'vat_rate' => 17,
            'total_ht' => 100,
            'position' => 1,
        ]);

        return $facture->fresh();
    }

    private function reglages(array $attributs = []): BusinessSettings
    {
        return BusinessSettings::factory()->assujetti()->create(
            array_merge(['user_id' => $this->user->id], $attributs)
        );
    }

    public function test_complete_settings_let_the_invoice_be_finalized(): void
    {
        $this->reglages();

        $facture = app(FinalizeInvoiceAction::class)->execute($this->brouillon());

        $this->assertNotNull($facture->finalized_at);
        $this->assertNotEmpty($facture->seller_snapshot['company_name'] ?? null);
    }

    public function test_a_nameless_issuer_is_refused(): void
    {
        $this->reglages(['company_name' => '', 'legal_name' => '']);

        $this->expectException(ValidationException::class);

        app(FinalizeInvoiceAction::class)->execute($this->brouillon());
    }

    public function test_an_addressless_issuer_is_refused(): void
    {
        $this->reglages(['address' => '']);

        $this->expectException(ValidationException::class);

        app(FinalizeInvoiceAction::class)->execute($this->brouillon());
    }

    /**
     * Un nom commercial vide passe si la raison sociale est là : c'est elle qui
     * identifie l'entreprise, l'autre n'est qu'une enseigne.
     */
    public function test_the_legal_name_alone_is_enough(): void
    {
        $this->reglages(['company_name' => '', 'legal_name' => 'Isaac Barbier-Guyot']);

        $facture = app(FinalizeInvoiceAction::class)->execute($this->brouillon());

        $this->assertNotNull($facture->finalized_at);
    }

    /**
     * Des espaces ne sont pas un nom. C'est le cas qu'un contrôle sur `empty()`
     * laisserait passer.
     */
    public function test_whitespace_is_not_a_name(): void
    {
        $this->reglages(['company_name' => '   ', 'legal_name' => '  ']);

        $this->expectException(ValidationException::class);

        app(FinalizeInvoiceAction::class)->execute($this->brouillon());
    }

    /**
     * ⚠️ Non-régression : le contrôle ne doit PAS s'étendre aux champs
     * secondaires. Un code postal manquant est un défaut de forme, pas un
     * document anonyme — et bloquer là-dessus empêcherait quelqu'un de
     * facturer aujourd'hui.
     */
    public function test_a_missing_postal_code_does_not_block(): void
    {
        $this->reglages(['postal_code' => '']);

        $facture = app(FinalizeInvoiceAction::class)->execute($this->brouillon());

        $this->assertNotNull($facture->finalized_at);
    }

    /**
     * Aucun réglage du tout : le refus existait déjà, il doit tenir.
     */
    public function test_missing_settings_are_still_refused(): void
    {
        $this->expectException(ValidationException::class);

        app(FinalizeInvoiceAction::class)->execute($this->brouillon());
    }
}
