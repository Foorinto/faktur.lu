<?php

namespace Tests\Feature;

use App\Actions\FinalizeInvoiceAction;
use App\Mail\InvoiceMail;
use App\Models\BusinessSettings;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * Mentions légales obligatoires selon la forme d'exercice (FEAT-133).
 *
 * Une société ou un commerçant en nom propre doit imprimer son RCS ; toute
 * activité soumise à l'autorisation d'établissement doit en imprimer le
 * numéro. Une profession libérale n'a pas de RCS, et une activité qui ne
 * relève pas de l'autorisation le déclare d'une case. Les comptes existants
 * ne sont pas bloqués : ils voient un rappel.
 */
class LegalMentionsTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['email_verified_at' => now()]);
        $this->actingAs($this->user);
    }

    private function formulaire(array $surcharge = []): array
    {
        return array_merge([
            'company_name' => 'Peinture Muller',
            'legal_name' => 'Peinture Muller SARL',
            'address' => '1 rue du Test',
            'postal_code' => 'L-1234',
            'city' => 'Luxembourg',
            'country_code' => 'LU',
            'matricule' => '2020123456789',
            'vat_regime' => 'franchise',
            'iban' => 'LU280019400644750000',
            'bic' => 'BCEELULL',
            'email' => 'contact@muller.lu',
            // L'IBAN passe de rien à une valeur : la réauthentification à l'acte
            // demande le mot de passe (ReauthenticationTest).
            'current_password' => 'password',
            'exercise_form' => 'company',
            'rcs_number' => 'B123456',
            'establishment_authorization' => '10012345',
            'no_establishment_authorization' => false,
        ], $surcharge);
    }

    public function test_une_societe_doit_donner_son_rcs_et_son_autorisation(): void
    {
        $this->put(route('settings.business.update'), $this->formulaire(['rcs_number' => '', 'establishment_authorization' => '']))
            ->assertSessionHasErrors(['rcs_number', 'establishment_authorization']);

        $this->put(route('settings.business.update'), $this->formulaire())
            ->assertSessionHasNoErrors();

        $reglages = BusinessSettings::withoutGlobalScopes()->where('user_id', $this->user->id)->first();
        $this->assertSame('company', $reglages->exercise_form);
        $this->assertSame([], $reglages->missingLegalMentions());
    }

    public function test_un_commercant_en_nom_propre_doit_donner_son_rcs(): void
    {
        $this->put(route('settings.business.update'), $this->formulaire(['exercise_form' => 'sole_trader', 'rcs_number' => '']))
            ->assertSessionHasErrors('rcs_number')
            ->assertSessionDoesntHaveErrors('establishment_authorization');
    }

    public function test_une_profession_liberale_n_a_ni_rcs_ni_autorisation_a_fournir(): void
    {
        // Retour d'Alexandre : un libéral doit pouvoir enregistrer sans rien
        // d'autre, l'autorisation reste facultative pour lui.
        $this->put(route('settings.business.update'), $this->formulaire(['exercise_form' => 'liberal', 'rcs_number' => '', 'establishment_authorization' => '']))
            ->assertSessionHasNoErrors();
    }

    public function test_une_societe_dispensee_d_autorisation_le_declare_d_une_case(): void
    {
        $this->put(route('settings.business.update'), $this->formulaire(['establishment_authorization' => '', 'no_establishment_authorization' => true]))
            ->assertSessionHasNoErrors();
    }

    public function test_la_forme_d_exercice_est_obligatoire_dans_le_formulaire(): void
    {
        $this->put(route('settings.business.update'), $this->formulaire(['exercise_form' => '']))
            ->assertSessionHasErrors('exercise_form');

        $this->put(route('settings.business.update'), $this->formulaire(['exercise_form' => 'autre']))
            ->assertSessionHasErrors('exercise_form');
    }

    public function test_les_mentions_manquantes_sont_calculees_selon_la_forme(): void
    {
        // Sans réponse, on ne sait pas ce qui s'applique : on demande la réponse, rien d'autre.
        $sans_reponse = new BusinessSettings(['country_code' => 'LU', 'vat_regime' => 'assujetti', 'vat_number' => null]);
        $this->assertSame(['exercise_form', 'vat_number'], $sans_reponse->missingLegalMentions());

        $commercant = new BusinessSettings(['country_code' => 'LU', 'exercise_form' => 'sole_trader', 'vat_regime' => 'franchise', 'rcs_number' => 'A12345']);
        $this->assertSame(['establishment_authorization'], $commercant->missingLegalMentions());

        $societe = new BusinessSettings(['country_code' => 'LU', 'exercise_form' => 'company', 'vat_regime' => 'franchise', 'no_establishment_authorization' => true]);
        $this->assertSame(['rcs_number'], $societe->missingLegalMentions());

        $liberal = new BusinessSettings(['country_code' => 'LU', 'exercise_form' => 'liberal', 'vat_regime' => 'franchise']);
        $this->assertSame([], $liberal->missingLegalMentions());
    }

    public function test_les_ecrans_recoivent_le_rappel_sans_rien_preremplir(): void
    {
        BusinessSettings::factory()->create(['user_id' => $this->user->id, 'exercise_form' => null, 'no_establishment_authorization' => false, 'rcs_number' => 'B55555', 'vat_regime' => 'franchise']);

        $this->get(route('settings.business.edit'))->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Settings/Business')
            ->missing('suggestedExerciseForm')
            ->where('settings.exercise_form', null)
            ->where('legalMentionsMissing', ['exercise_form']));

        $client = Client::factory()->create(['user_id' => $this->user->id]);
        $invoice = Invoice::factory()->create(['user_id' => $this->user->id, 'client_id' => $client->id]);
        $this->get(route('invoices.show', $invoice))->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Invoices/Show')
            ->where('legalMentionsMissing', ['exercise_form']));
    }

    public function test_la_finalisation_est_refusee_tant_que_les_mentions_manquent(): void
    {
        BusinessSettings::factory()->create(['user_id' => $this->user->id, 'exercise_form' => 'company', 'rcs_number' => null, 'establishment_authorization' => null, 'no_establishment_authorization' => false, 'vat_regime' => 'franchise']);
        $client = Client::factory()->create(['user_id' => $this->user->id]);
        $brouillon = Invoice::factory()->create(['user_id' => $this->user->id, 'client_id' => $client->id, 'status' => Invoice::STATUS_DRAFT]);
        InvoiceItem::create(['invoice_id' => $brouillon->id, 'title' => 'Prestation', 'quantity' => 1, 'unit_price' => 100, 'vat_rate' => 17]);

        try {
            app(FinalizeInvoiceAction::class)->execute($brouillon);
            $this->fail('La finalisation aurait dû être refusée.');
        } catch (ValidationException $e) {
            $message = collect($e->errors())->flatten()->first();
            $this->assertStringContainsString('le numéro RCS', $message);
            $this->assertStringContainsString('l\'autorisation d\'établissement', $message);
            $this->assertStringContainsString('déjà finalisées ne sont pas concernées', $message);
        }
        $this->assertSame(Invoice::STATUS_DRAFT, $brouillon->fresh()->status);

        // Mentions complétées : la même facture se finalise.
        BusinessSettings::withoutGlobalScopes()->where('user_id', $this->user->id)->update(['rcs_number' => 'B123456', 'establishment_authorization' => '10012345']);
        app(FinalizeInvoiceAction::class)->execute($brouillon->fresh());
        $this->assertNotNull($brouillon->fresh()->finalized_at);
    }

    public function test_une_facture_deja_finalisee_s_envoie_meme_sans_mentions(): void
    {
        Mail::fake();
        BusinessSettings::factory()->create(['user_id' => $this->user->id, 'exercise_form' => null, 'vat_regime' => 'franchise']);
        $client = Client::factory()->create(['user_id' => $this->user->id, 'email' => 'client@exemple.lu']);
        $finalisee = Invoice::factory()->create(['user_id' => $this->user->id, 'client_id' => $client->id, 'status' => Invoice::STATUS_FINALIZED, 'issued_at' => now()]);

        $this->post(route('invoices.send-email', $finalisee), ['recipient_email' => 'client@exemple.lu', 'subject' => 'Facture'])
            ->assertRedirect()->assertSessionHasNoErrors();
        Mail::assertSent(InvoiceMail::class, 1);
    }

    public function test_un_compte_a_jour_ne_voit_aucun_rappel(): void
    {
        BusinessSettings::factory()->create(['user_id' => $this->user->id, 'exercise_form' => 'liberal', 'no_establishment_authorization' => true, 'vat_regime' => 'franchise']);

        $this->get(route('settings.business.edit'))->assertInertia(fn (AssertableInertia $page) => $page
            ->where('legalMentionsMissing', []));
    }
}
