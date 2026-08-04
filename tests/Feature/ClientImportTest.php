<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Import\ImportSession;
use App\Models\User;
use App\Services\Import\ClientImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

/**
 * Tests de caractérisation de l'import de clients.
 *
 * Ils ne décrivent pas un comportement souhaité mais le comportement ACTUEL,
 * afin qu'il survive à l'extraction de la base commune partagée avec l'import
 * d'articles. Ce service faisait 509 lignes sans la moindre couverture : c'est
 * ce vide, et non le refactor, qui constituait le risque.
 *
 * Si l'un d'eux tombe après une modification de SpreadsheetImportService, c'est
 * que le refactor a changé quelque chose — pas que le test est trop strict.
 */
class ClientImportTest extends TestCase
{
    use RefreshDatabase;

    private function service(): ClientImportService
    {
        return app(ClientImportService::class);
    }

    /** Compte en essai => plan Pro => aucun plafond de clients qui viendrait fausser les tests. */
    private function user(): User
    {
        return User::factory()->create([
            'trial_ends_at' => now()->addDays(14),
            'email_verified_at' => now(),
        ]);
    }

    /** Écrit le fichier sur le disque simulé et renvoie son chemin RELATIF (celui que stocke la session). */
    private function putCsv(string $name, string $content): string
    {
        Storage::put($name, $content);

        return $name;
    }

    /** @param  array<string, array<int, array<int, string>>>  $sheets  nom => lignes */
    private function putXlsx(string $name, array $sheets): string
    {
        $spreadsheet = new Spreadsheet;
        $spreadsheet->removeSheetByIndex(0);

        foreach ($sheets as $title => $rows) {
            $sheet = $spreadsheet->createSheet();
            $sheet->setTitle($title);
            $sheet->fromArray($rows, null, 'A1');
        }

        Storage::put($name, '');
        (new Xlsx($spreadsheet))->save(Storage::path($name));

        return $name;
    }

    private function importSession(User $user, string $relativePath, array $mapping, string $strategy = 'skip'): ImportSession
    {
        return ImportSession::create([
            'user_id' => $user->id,
            'type' => 'clients',
            'filename' => basename($relativePath),
            'storage_path' => $relativePath,
            'mapping' => $mapping,
            'duplicate_strategy' => $strategy,
            'status' => 'preview',
        ]);
    }

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    // --- Lecture du fichier --------------------------------------------------

    public function test_parse_file_extrait_les_entetes_et_un_apercu_de_cinq_lignes(): void
    {
        $csv = "nom,email\n";
        foreach (range(1, 7) as $i) {
            $csv .= "Client {$i},client{$i}@exemple.lu\n";
        }

        $parsed = $this->service()->parseFile(Storage::path($this->putCsv('clients.csv', $csv)));

        $this->assertSame(['nom', 'email'], $parsed['headers']);
        $this->assertCount(5, $parsed['preview_data'], 'L\'aperçu est plafonné à 5 lignes.');
        $this->assertSame(7, $parsed['total_rows']);
    }

    public function test_la_feuille_la_plus_remplie_est_choisie(): void
    {
        // Une feuille « Notes » quasi vide précède la vraie feuille de données :
        // le lecteur doit retenir la seconde, pas la première rencontrée.
        $path = $this->putXlsx('deux-feuilles.xlsx', [
            'Notes' => [['Brouillon']],
            'Donnees' => [
                ['nom', 'email', 'ville'],
                ['Alpha', 'alpha@exemple.lu', 'Luxembourg'],
                ['Beta', 'beta@exemple.lu', 'Esch'],
            ],
        ]);

        $parsed = $this->service()->parseFile(Storage::path($path));

        $this->assertSame(['nom', 'email', 'ville'], $parsed['headers']);
        $this->assertSame(2, $parsed['total_rows']);
    }

    public function test_les_lignes_de_titre_en_tete_sont_ignorees(): void
    {
        // Cas courant d'un export : une ligne de titre, une ligne vide, puis
        // les vrais en-têtes. La première ligne comptant au moins deux cellules
        // remplies fait office d'en-tête.
        $csv = "Export du 01/01/2026,\n,\nnom,email\nAlpha,alpha@exemple.lu\n";

        $parsed = $this->service()->parseFile(Storage::path($this->putCsv('titre.csv', $csv)));

        $this->assertSame(['nom', 'email'], $parsed['headers']);
        $this->assertSame(1, $parsed['total_rows']);
    }

    // --- Détection automatique des colonnes ----------------------------------

    public function test_les_entetes_francais_anglais_et_allemands_sont_reconnus(): void
    {
        $mapping = $this->service()->autoDetectMapping([
            'Nom de société', 'E-mail', 'Téléphone', 'N° TVA', 'Ville', 'Land', 'Colonne inconnue',
        ]);

        $this->assertSame('name', $mapping['Nom de société']);
        $this->assertSame('email', $mapping['E-mail']);
        $this->assertSame('phone', $mapping['Téléphone']);
        $this->assertSame('vat_number', $mapping['N° TVA']);
        $this->assertSame('city', $mapping['Ville']);
        $this->assertSame('country_code', $mapping['Land']);
        $this->assertSame('ignore', $mapping['Colonne inconnue'], 'Une colonne non reconnue doit être ignorée, pas devinée.');
    }

    // --- Validation et doublons ----------------------------------------------

    public function test_les_doublons_sont_detectes_par_email_puis_par_nom(): void
    {
        $user = $this->user();
        Client::factory()->create(['user_id' => $user->id, 'name' => 'Ancien', 'email' => 'connu@exemple.lu']);
        Client::factory()->create(['user_id' => $user->id, 'name' => 'Beta', 'email' => null]);

        $csv = "nom,email\n"
            ."Alpha,connu@exemple.lu\n"   // doublon par email, malgré un nom différent
            ."Beta,autre@exemple.lu\n"    // doublon par nom, malgré un email différent
            ."Gamma,gamma@exemple.lu\n";  // nouveau

        $path = $this->putCsv('doublons.csv', $csv);
        $session = $this->importSession($user, $path, ['nom' => 'name', 'email' => 'email']);

        $result = $this->service()->validateAndPreview($session);

        $this->assertCount(1, $result['valid']);
        $this->assertCount(2, $result['duplicates']);
        $this->assertCount(0, $result['errors']);
        $this->assertSame(3, $session->fresh()->total_rows);
    }

    public function test_une_ligne_sans_nom_ou_avec_un_email_invalide_part_en_erreur(): void
    {
        $user = $this->user();

        $csv = "nom,email\n"
            .",orphelin@exemple.lu\n"     // nom manquant
            ."Delta,pas-un-email\n"       // email invalide
            ."Epsilon,epsilon@exemple.lu\n";

        $path = $this->putCsv('erreurs.csv', $csv);
        $session = $this->importSession($user, $path, ['nom' => 'name', 'email' => 'email']);

        $result = $this->service()->validateAndPreview($session);

        $this->assertCount(1, $result['valid']);
        $this->assertCount(2, $result['errors']);
    }

    // --- Import --------------------------------------------------------------

    public function test_la_strategie_skip_laisse_l_existant_intact(): void
    {
        $user = $this->user();
        $existing = Client::factory()->create([
            'user_id' => $user->id, 'name' => 'Ancien', 'email' => 'connu@exemple.lu', 'city' => 'Esch',
        ]);

        $csv = "nom,email,ville\nAncien,connu@exemple.lu,Luxembourg\nNouveau,nouveau@exemple.lu,Diekirch\n";
        $path = $this->putCsv('skip.csv', $csv);
        $session = $this->importSession($user, $path, ['nom' => 'name', 'email' => 'email', 'ville' => 'city'], 'skip');

        $this->service()->import($session);

        $this->assertSame('Esch', $existing->fresh()->city, 'La stratégie skip ne doit rien réécrire.');
        $this->assertSame(1, $session->fresh()->imported_count);
        $this->assertSame(1, $session->fresh()->skipped_count);
    }

    public function test_la_strategie_update_met_a_jour_le_client_existant(): void
    {
        $user = $this->user();
        $existing = Client::factory()->create([
            'user_id' => $user->id, 'name' => 'Ancien', 'email' => 'connu@exemple.lu', 'city' => 'Esch',
        ]);

        $csv = "nom,email,ville\nAncien,connu@exemple.lu,Luxembourg\n";
        $path = $this->putCsv('update.csv', $csv);
        $session = $this->importSession($user, $path, ['nom' => 'name', 'email' => 'email', 'ville' => 'city'], 'update');

        $this->service()->import($session);

        $this->assertSame('Luxembourg', $existing->fresh()->city);
        $this->assertSame(1, $session->fresh()->updated_count);
        $this->assertSame(0, $session->fresh()->imported_count);
    }

    public function test_la_strategie_create_ajoute_un_second_client_de_meme_email(): void
    {
        $user = $this->user();
        Client::factory()->create(['user_id' => $user->id, 'name' => 'Ancien', 'email' => 'connu@exemple.lu']);

        $csv = "nom,email\nAncien,connu@exemple.lu\n";
        $path = $this->putCsv('create.csv', $csv);
        $session = $this->importSession($user, $path, ['nom' => 'name', 'email' => 'email'], 'create');

        $this->service()->import($session);

        $this->assertSame(2, Client::where('user_id', $user->id)->count());
        $this->assertSame(1, $session->fresh()->imported_count);
    }

    public function test_le_pays_est_normalise_en_code_iso(): void
    {
        $user = $this->user();

        $csv = "nom,pays\nAlpha,Belgique\nBeta,DE\nGamma,Pays inconnu\n";
        $path = $this->putCsv('pays.csv', $csv);
        $session = $this->importSession($user, $path, ['nom' => 'name', 'pays' => 'country_code']);

        $this->service()->import($session);

        $this->assertSame('BE', Client::where('name', 'Alpha')->value('country_code'));
        $this->assertSame('DE', Client::where('name', 'Beta')->value('country_code'));
        $this->assertSame('LU', Client::where('name', 'Gamma')->value('country_code'), 'Un pays non reconnu retombe sur LU.');
    }
}
