<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Accounting\PcnAccountService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Comptes de charges du plan comptable normalisé.
 *
 * Le fichier `resources/data/pcn-class6.json` est généré par `pcn:build` depuis
 * le classeur de la CNC. Ces tests le traitent comme une donnée de production :
 * s'ils tombent après une régénération, c'est le fichier qu'il faut examiner.
 */
class PcnAccountTest extends TestCase
{
    use RefreshDatabase;

    private function pcn(): PcnAccountService
    {
        return app(PcnAccountService::class);
    }

    public function test_la_liste_ne_contient_que_des_comptes_de_charges(): void
    {
        $accounts = $this->pcn()->all();

        $this->assertGreaterThan(200, $accounts->count(), 'La classe 6 compte 260 comptes imputables.');

        foreach ($accounts as $account) {
            $this->assertStringStartsWith('6', $account['ref'], 'Seule la classe 6 — les charges — a sa place ici.');
            $this->assertNotSame('', $account['fr'], "Le compte {$account['ref']} n'a pas de libellé.");
        }
    }

    public function test_les_simples_titres_sont_ecartes(): void
    {
        // « 611 Loyers et charges locatives » et « 614 Primes d'assurance » sont
        // des regroupements : aucune écriture ne peut y aller. Les proposer
        // serait une invitation à l'erreur.
        foreach (['6', '61', '611', '614', '612'] as $regroupement) {
            $this->assertFalse(
                $this->pcn()->exists($regroupement),
                "Le regroupement {$regroupement} ne doit pas être proposé."
            );
        }

        // Leurs feuilles, elles, sont bien là.
        foreach (['61112', '6113', '6146', '61228'] as $imputable) {
            $this->assertTrue($this->pcn()->exists($imputable), "Le compte imputable {$imputable} manque.");
        }
    }

    public function test_chaque_suggestion_pointe_vers_un_compte_reel(): void
    {
        // Garde-fou principal du dictionnaire de mots-clés : un numéro saisi de
        // mémoire, ou devenu obsolète après une régénération du fichier,
        // proposerait un compte inexistant à l'utilisateur.
        $reflection = new \ReflectionClass(PcnAccountService::class);
        $map = $reflection->getConstant('KEYWORD_MAP');

        $this->assertNotEmpty($map);

        foreach ($map as $keyword => $ref) {
            $this->assertTrue(
                $this->pcn()->exists($ref),
                "Le mot-clé « {$keyword} » renvoie vers {$ref}, qui n'existe pas ou n'est pas imputable."
            );
        }
    }

    public function test_la_suggestion_comprend_le_vocabulaire_courant(): void
    {
        $pcn = $this->pcn();

        $this->assertSame('61112', $pcn->suggestFor('Loyer'));
        $this->assertSame('6113', $pcn->suggestFor('Charges locatives'));
        $this->assertSame('61342', $pcn->suggestFor('Honoraires comptables'));
        $this->assertSame('61333', $pcn->suggestFor('Frais bancaires'));
        $this->assertSame('61845', $pcn->suggestFor('Électricité'));
        $this->assertNull($pcn->suggestFor('Zzzz inconnu'));
    }

    public function test_la_correspondance_la_plus_longue_l_emporte(): void
    {
        // « charges locatives » doit gagner sur un éventuel « charges » seul.
        $this->assertSame('6113', $this->pcn()->suggestFor('Charges locatives du bureau'));
    }

    public function test_la_recherche_repond_au_numero_comme_au_libelle(): void
    {
        $pcn = $this->pcn();

        $parNumero = collect($pcn->search('6113'))->pluck('ref');
        $this->assertContains('6113', $parNumero);

        $parLibelle = collect($pcn->search('assurance'))->pluck('ref');
        $this->assertContains('6146', $parLibelle, 'La recherche textuelle doit trouver les assurances.');
    }

    public function test_les_libelles_suivent_la_langue_sauf_lb_et_pt(): void
    {
        $pcn = $this->pcn();

        $this->assertSame('Electricité', $pcn->find('61845', 'fr')['label']);
        $this->assertNotSame($pcn->find('61845', 'fr')['label'], $pcn->find('61845', 'de')['label']);

        // Le PCN n'existe pas en luxembourgeois ni en portugais : on sert le
        // français plutôt que d'inventer une traduction réglementaire.
        $this->assertSame($pcn->find('61845', 'fr')['label'], $pcn->find('61845', 'lb')['label']);
        $this->assertSame($pcn->find('61845', 'fr')['label'], $pcn->find('61845', 'pt')['label']);
    }

    public function test_l_endpoint_sert_la_recherche_et_la_suggestion(): void
    {
        $this->actingAs(User::factory()->create(['email_verified_at' => now()]));

        $this->getJson(route('settings.pcn-accounts', ['q' => 'assurance']))
            ->assertSuccessful()
            ->assertJsonStructure(['accounts' => [['ref', 'label', 'parent']]]);

        $this->getJson(route('settings.pcn-accounts', ['suggest' => 'Loyer']))
            ->assertSuccessful()
            ->assertJsonPath('suggestion.ref', '61112');
    }
}
