<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\BusinessSettings;
use App\Models\HR\Employee;
use App\Models\User;
use App\Security\AuditLogMasker;
use App\Security\Mask;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Le journal d'audit ne garde jamais en clair ce que la base chiffre.
 *
 * Jusqu'au 2026-09-22, chaque modification d'IBAN déposait l'ancienne valeur
 * en clair dans le journal, et chaque fiche de salarié créée y déposait
 * l'adresse, le téléphone, la nationalité. Le chiffrement au repos ne valait
 * donc que jusqu'au premier changement. Ce qui est protégé ici : les entrées
 * nouvelles sont masquées à la source, et la reprise des anciennes est
 * idempotente.
 */
class AuditLogMaskingTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

    private function dernier(string $action): AuditLog
    {
        $entree = AuditLog::where('action', $action)->latest('id')->first();
        $this->assertNotNull($entree, "aucune entrée {$action}");

        return $entree;
    }

    public function test_un_changement_d_iban_est_journalise_masque(): void
    {
        $reglages = BusinessSettings::factory()->create(['user_id' => $this->user->id, 'iban' => 'LU280019400644750000']);

        $reglages->update(['iban' => 'LU120010001234567891']);

        $entree = $this->dernier('BusinessSettings.updated');
        $this->assertSame('LU28 **** **** **** 0000', $entree->old_values['iban']);
        $this->assertSame('LU12 **** **** **** 7891', $entree->new_values['iban']);
    }

    public function test_la_creation_des_reglages_ne_depose_pas_l_iban_en_clair(): void
    {
        BusinessSettings::factory()->create(['user_id' => $this->user->id, 'iban' => 'LU280019400644750000']);

        $entree = $this->dernier('BusinessSettings.created');
        $this->assertSame('LU28 **** **** **** 0000', $entree->new_values['iban']);
    }

    public function test_les_donnees_personnelles_d_un_salarie_ne_sortent_jamais_en_clair(): void
    {
        $salarie = Employee::create([
            'user_id' => $this->user->id,
            'first_name' => 'Anne',
            'last_name' => 'Muller',
            'contract_type' => 'cdi',
            'contract_start' => '2026-01-01',
            'address' => '12 rue de la Gare',
            'phone_perso' => '+352 621 000 000',
            'nationality' => 'luxembourgeoise',
            'bank_iban' => 'LU280019400644750000',
        ]);

        $creation = $this->dernier('Employee.created');
        $this->assertSame(Mask::HIDDEN, $creation->new_values['address']);
        $this->assertSame(Mask::HIDDEN, $creation->new_values['phone_perso']);
        $this->assertSame(Mask::HIDDEN, $creation->new_values['nationality']);
        $this->assertSame('LU28 **** **** **** 0000', $creation->new_values['bank_iban']);
        // Ce qui n'est pas chiffré reste lisible : le journal sert encore.
        $this->assertSame('Anne', $creation->new_values['first_name']);

        $salarie->update(['address' => '3 place d\'Armes']);

        $modification = $this->dernier('Employee.updated');
        $this->assertSame(Mask::HIDDEN, $modification->old_values['address']);
        $this->assertSame(Mask::HIDDEN, $modification->new_values['address']);

        $salarie->delete();

        $suppression = $this->dernier('Employee.deleted');
        $this->assertSame(Mask::HIDDEN, $suppression->old_values['address']);
    }

    public function test_le_journal_ne_contient_ni_clair_ni_chiffre(): void
    {
        // Ni le texte en clair, ni le texte chiffré que porte getChanges() :
        // l'un fuit, l'autre est illisible et inutile.
        $reglages = BusinessSettings::factory()->create(['user_id' => $this->user->id, 'iban' => 'LU280019400644750000']);
        $reglages->update(['iban' => 'LU120010001234567891']);

        $brut = DB::table('audit_logs')->where('action', 'BusinessSettings.updated')->latest('id')->value('new_values');

        $this->assertStringNotContainsString('LU120010001234567891', $brut);
        $this->assertStringNotContainsString('eyJpdiI6', $brut, 'le texte chiffré de Laravel commence toujours ainsi');
        // Et le masque porte bien sur la valeur déchiffrée : masquer le texte
        // chiffré donnerait des étoiles autour d'un début illisible.
        $this->assertSame('LU12 **** **** **** 7891', json_decode($brut, true)['iban']);
    }

    // --- La reprise des anciennes lignes --------------------------------------

    private function ligneAncienne(array $old, array $new): int
    {
        return DB::table('audit_logs')->insertGetId([
            'user_id' => $this->user->id,
            'action' => 'BusinessSettings.updated',
            'old_values' => json_encode($old),
            'new_values' => json_encode($new),
            'status' => 'success',
            'created_at' => now(),
        ]);
    }

    public function test_la_reprise_masque_les_lignes_ecrites_en_clair(): void
    {
        $id = $this->ligneAncienne(
            ['iban' => 'LU280019400644750000', 'company_name' => 'Avant'],
            ['iban' => 'LU120010001234567891', 'company_name' => 'Après', 'address' => '1 rue Secrète'],
        );

        $reecrites = (new AuditLogMasker)->run();

        $this->assertSame(1, $reecrites);
        $ligne = DB::table('audit_logs')->find($id);
        $old = json_decode($ligne->old_values, true);
        $new = json_decode($ligne->new_values, true);
        $this->assertSame('LU28 **** **** **** 0000', $old['iban']);
        $this->assertSame('LU12 **** **** **** 7891', $new['iban']);
        $this->assertSame(Mask::HIDDEN, $new['address']);
        // Le reste de la ligne n'a pas bougé.
        $this->assertSame('Avant', $old['company_name']);
        $this->assertSame('Après', $new['company_name']);
    }

    public function test_la_reprise_est_idempotente(): void
    {
        $this->ligneAncienne(['iban' => 'LU280019400644750000'], ['iban' => 'LU120010001234567891']);

        (new AuditLogMasker)->run();
        $apresUne = DB::table('audit_logs')->orderByDesc('id')->value('new_values');

        $reecrites = (new AuditLogMasker)->run();
        $apresDeux = DB::table('audit_logs')->orderByDesc('id')->value('new_values');

        $this->assertSame(0, $reecrites);
        $this->assertSame($apresUne, $apresDeux);
        $this->assertSame('LU12 **** **** **** 7891', json_decode($apresDeux, true)['iban']);
    }

    public function test_la_reprise_laisse_les_lignes_sans_donnee_sensible(): void
    {
        $this->ligneAncienne(['company_name' => 'Avant'], ['company_name' => 'Après']);

        $this->assertSame(0, (new AuditLogMasker)->run());
    }

    public function test_le_masque_d_iban_ne_se_masque_pas_deux_fois(): void
    {
        $this->assertSame('LU28 **** **** **** 0000', Mask::iban('LU28 **** **** **** 0000'));
        $this->assertSame('LU28 **** **** **** 0000', Mask::iban('LU280019400644750000'));
        $this->assertSame('***', Mask::value('address', 'n\'importe quoi'));
        $this->assertNull(Mask::value('address', null));
    }
}
