<?php

namespace Tests\Feature;

use App\Models\Import\ImportSession;
use App\Models\Product;
use App\Models\User;
use App\Services\Import\ProductImportService;
use Database\Seeders\PlansSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Import du catalogue d'articles.
 *
 * L'essentiel se joue dans les normalisations : un catalogue exporté d'un autre
 * outil n'arrive jamais au format attendu. Prix à virgule, symbole monétaire,
 * taux avec pourcentage, unités en toutes lettres — si l'import ne les absorbe
 * pas, l'utilisateur doit reformater son fichier, et c'est précisément la
 * friction qui fait abandonner un import.
 */
class ProductImportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->seed(PlansSeeder::class);
    }

    private function service(): ProductImportService
    {
        return app(ProductImportService::class);
    }

    private function proUser(): User
    {
        return User::factory()->create([
            'trial_ends_at' => now()->addDays(14),
            'email_verified_at' => now(),
        ]);
    }

    private function freeUser(): User
    {
        return User::factory()->create([
            'trial_ends_at' => now()->subMonth(),
            'email_verified_at' => now(),
        ]);
    }

    private function putCsv(string $name, string $content): string
    {
        Storage::put($name, $content);

        return $name;
    }

    private function importSession(User $user, string $path, array $mapping, string $strategy = 'skip'): ImportSession
    {
        return ImportSession::create([
            'user_id' => $user->id,
            'type' => 'products',
            'filename' => basename($path),
            'storage_path' => $path,
            'mapping' => $mapping,
            'duplicate_strategy' => $strategy,
            'status' => 'preview',
        ]);
    }

    private const MAPPING = [
        'designation' => 'designation',
        'reference' => 'reference',
        'type' => 'type',
        'prix' => 'unit_price_ht',
        'tva' => 'vat_rate',
        'unite' => 'unit',
    ];

    // --- Détection des colonnes ----------------------------------------------

    public function test_les_entetes_courants_sont_reconnus(): void
    {
        $mapping = $this->service()->autoDetectMapping([
            'Désignation', 'Référence', 'Prix HT', 'Taux TVA', 'Unité', 'Famille', 'Colonne inconnue',
        ]);

        $this->assertSame('designation', $mapping['Désignation']);
        $this->assertSame('reference', $mapping['Référence']);
        $this->assertSame('unit_price_ht', $mapping['Prix HT']);
        $this->assertSame('vat_rate', $mapping['Taux TVA']);
        $this->assertSame('unit', $mapping['Unité']);
        $this->assertSame('type', $mapping['Famille']);
        $this->assertSame('ignore', $mapping['Colonne inconnue']);
    }

    public function test_une_colonne_article_ne_bascule_pas_vers_la_famille(): void
    {
        // « Article » contient « art » : sans précaution, la colonne principale
        // partirait dans le champ « famille ».
        $mapping = $this->service()->autoDetectMapping(['Article', 'Prix']);

        $this->assertSame('designation', $mapping['Article']);
    }

    // --- Normalisations -------------------------------------------------------

    public function test_les_prix_ecrits_a_l_europeenne_sont_compris(): void
    {
        $user = $this->proUser();

        $csv = "designation,prix\n"
            .'"Virgule décimale","1 234,56"'."\n"
            .'"Point milliers","1.234,56"'."\n"
            .'"Format anglo","1,234.56"'."\n"
            .'"Avec symbole","129,90 €"'."\n";

        $path = $this->putCsv('prix.csv', $csv);
        $this->service()->import($this->importSession($user, $path, ['designation' => 'designation', 'prix' => 'unit_price_ht']));

        foreach (['Virgule décimale' => 1234.56, 'Point milliers' => 1234.56, 'Format anglo' => 1234.56, 'Avec symbole' => 129.90] as $name => $expected) {
            $this->assertEquals(
                $expected,
                (float) Product::withoutUserScope()->where('designation', $name)->value('unit_price_ht'),
                "Prix mal interprété pour « {$name} »."
            );
        }
    }

    public function test_les_unites_et_familles_ecrites_en_toutes_lettres_sont_traduites(): void
    {
        $user = $this->proUser();

        $csv = "designation,reference,type,prix,tva,unite\n"
            ."Conseil,SRV-1,prestation,750,17,jour\n"
            ."Micro,PRD-1,produit,129,17,pièce\n"
            ."Rédaction,SRV-2,service,0.5,17,mot\n";

        $path = $this->putCsv('unites.csv', $csv);
        $this->service()->import($this->importSession($user, $path, self::MAPPING));

        $conseil = Product::withoutUserScope()->where('reference', 'SRV-1')->first();
        $this->assertSame('day', $conseil->unit);
        $this->assertSame(Product::TYPE_SERVICE, $conseil->type);

        $micro = Product::withoutUserScope()->where('reference', 'PRD-1')->first();
        $this->assertSame('piece', $micro->unit);
        $this->assertSame(Product::TYPE_PRODUCT, $micro->type);

        $this->assertSame('word', Product::withoutUserScope()->where('reference', 'SRV-2')->value('unit'));
    }

    public function test_un_taux_avec_pourcentage_est_accepte(): void
    {
        $user = $this->proUser();

        $csv = "designation,prix,tva\nAvec signe,100,\"17 %\"\n";
        $path = $this->putCsv('taux.csv', $csv);

        $this->service()->import($this->importSession($user, $path, [
            'designation' => 'designation', 'prix' => 'unit_price_ht', 'tva' => 'vat_rate',
        ]));

        $this->assertEquals(17, (float) Product::withoutUserScope()->where('designation', 'Avec signe')->value('vat_rate'));
    }

    // --- Validation et doublons ----------------------------------------------

    public function test_une_ligne_sans_designation_ou_sans_prix_part_en_erreur(): void
    {
        $user = $this->proUser();

        $csv = "designation,prix\n"
            .",100\n"            // pas de désignation
            ."Sans prix,\n"      // pas de prix
            ."Correct,42\n";

        $path = $this->putCsv('erreurs.csv', $csv);
        $session = $this->importSession($user, $path, ['designation' => 'designation', 'prix' => 'unit_price_ht']);

        $result = $this->service()->validateAndPreview($session);

        $this->assertCount(1, $result['valid']);
        $this->assertCount(2, $result['errors']);
    }

    public function test_le_doublon_se_detecte_par_reference_puis_par_designation(): void
    {
        $user = $this->proUser();
        Product::factory()->create(['user_id' => $user->id, 'designation' => 'Ancien micro', 'reference' => 'PRD-1']);
        Product::factory()->create(['user_id' => $user->id, 'designation' => 'Conseil', 'reference' => null]);

        $csv = "designation,reference,prix\n"
            ."Nom different,PRD-1,10\n"   // doublon par référence
            ."Conseil,,20\n"              // doublon par désignation
            ."Nouveau,PRD-9,30\n";

        $path = $this->putCsv('doublons.csv', $csv);
        $session = $this->importSession($user, $path, [
            'designation' => 'designation', 'reference' => 'reference', 'prix' => 'unit_price_ht',
        ]);

        $result = $this->service()->validateAndPreview($session);

        $this->assertCount(1, $result['valid']);
        $this->assertCount(2, $result['duplicates']);
    }

    public function test_la_strategie_update_met_a_jour_sans_creer(): void
    {
        $user = $this->proUser();
        $existing = Product::factory()->create([
            'user_id' => $user->id, 'designation' => 'Ancien', 'reference' => 'PRD-1', 'unit_price_ht' => 10,
        ]);

        $csv = "designation,reference,prix\nAncien,PRD-1,99\n";
        $path = $this->putCsv('maj.csv', $csv);

        $this->service()->import($this->importSession($user, $path, [
            'designation' => 'designation', 'reference' => 'reference', 'prix' => 'unit_price_ht',
        ], 'update'));

        $this->assertEquals(99, (float) $existing->fresh()->unit_price_ht);
        $this->assertSame(1, Product::withoutUserScope()->where('user_id', $user->id)->count());
    }

    // --- Plafond du plan ------------------------------------------------------

    public function test_l_import_s_arrete_au_plafond_du_plan_gratuit(): void
    {
        $user = $this->freeUser();
        Product::factory()->count(8)->create(['user_id' => $user->id]); // 8/10

        $csv = "designation,prix\n";
        foreach (range(1, 5) as $i) {
            $csv .= "Article {$i},{$i}0\n";
        }

        $path = $this->putCsv('plafond.csv', $csv);
        $session = $this->importSession($user, $path, ['designation' => 'designation', 'prix' => 'unit_price_ht']);

        $this->service()->import($session);
        $session->refresh();

        $this->assertSame(2, $session->imported_count, 'Seules les 2 places restantes doivent être utilisées.');
        $this->assertSame(10, Product::withoutUserScope()->where('user_id', $user->id)->count());
        $this->assertNotEmpty($session->errors, 'Les lignes refusées doivent être expliquées.');
    }

    // --- Modèle téléchargeable ------------------------------------------------

    public function test_le_modele_csv_est_servi_avec_un_bom_utf8(): void
    {
        $this->actingAs($this->proUser());

        $response = $this->get(route('products.import.template'));
        $response->assertSuccessful();

        $content = $response->streamedContent();

        // Sans le BOM, Excel ouvre le fichier en ANSI et casse les accents :
        // l'utilisateur croirait le modèle fautif.
        $this->assertStringStartsWith("\xEF\xBB\xBF", $content);
        $this->assertStringContainsString('designation', $content);
        $this->assertStringContainsString('unit_price_ht', $content);
    }

    // --- Chaîne complète ------------------------------------------------------

    public function test_l_assistant_fonctionne_de_bout_en_bout(): void
    {
        $user = $this->proUser();
        $this->actingAs($user);

        // Fichier volontairement « sale » : en-têtes en allemand, prix à
        // virgule, unité en toutes lettres — le cas réel d'un export.
        $csv = "Bezeichnung;Referenz;Preis;MwSt;Einheit\n"
            ."Beratung;SRV-1;750,00;17;jour\n"
            ."Mikrofon;PRD-1;129,90;17;pièce\n";

        $upload = $this->post(route('products.import.upload'), [
            'file' => \Illuminate\Http\UploadedFile::fake()->createWithContent('katalog.csv', $csv),
        ]);
        $upload->assertSuccessful();

        $sessionId = $upload->json('session.id');
        $mapping = $upload->json('session.mapping');

        // La détection doit avoir reconnu les colonnes allemandes seule.
        $this->assertSame('designation', $mapping['Bezeichnung']);
        $this->assertSame('unit_price_ht', $mapping['Preis']);
        $this->assertSame('vat_rate', $mapping['MwSt']);

        $this->post(route('products.import.mapping', $sessionId), ['mapping' => $mapping])
            ->assertSuccessful()
            ->assertJsonPath('session.valid_rows', 2);

        $this->post(route('products.import.process', $sessionId), ['duplicate_strategy' => 'skip'])
            ->assertSuccessful()
            ->assertJsonPath('session.imported_count', 2);

        $beratung = Product::withoutUserScope()->where('reference', 'SRV-1')->first();
        $this->assertEquals(750.0, (float) $beratung->unit_price_ht);
        $this->assertSame('day', $beratung->unit);
    }

    // --- Cloisonnement --------------------------------------------------------

    public function test_une_session_d_un_autre_compte_est_refusee(): void
    {
        $owner = $this->proUser();
        $intruder = $this->proUser();

        $session = $this->importSession($owner, $this->putCsv('x.csv', "designation,prix\nA,1\n"), []);

        $this->actingAs($intruder)
            ->getJson(route('products.import.status', $session->id))
            ->assertForbidden();
    }
}
