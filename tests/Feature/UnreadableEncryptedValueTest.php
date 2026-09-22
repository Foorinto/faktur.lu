<?php

namespace Tests\Feature;

use App\Casts\EncryptedIban;
use App\Casts\EncryptedText;
use App\Models\BusinessSettings;
use App\Models\HR\Employee;
use App\Models\User;
use App\Security\EncryptedValueRepair;
use Database\Seeders\PlansSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Une valeur chiffrée que l'application ne sait pas lire ne casse plus la page.
 *
 * Les migrations de chiffrement (les IBAN en juillet 2026, les données des
 * salariés en août) avaient laissé les valeurs vides telles quelles ; le cast
 * `encrypted` de Laravel lève une exception dessus, et pour ces comptes le
 * tableau de bord, les réglages ou la fiche du salarié répondaient 500.
 * Constaté en production le 2026-09-22 par un « The payload is invalid ».
 *
 * Ce qui est protégé ici : une valeur vide ou en clair se lit sans exception,
 * l'écriture chiffre toujours, la réparation remet les lignes anciennes au
 * propre sans rien perdre, et le journal masque toujours ces valeurs.
 */
class UnreadableEncryptedValueTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PlansSeeder::class);

        $this->user = User::factory()->create([
            'email_verified_at' => now(),
            'trial_ends_at' => now()->addDays(14),
        ]);
        $this->actingAs($this->user);
    }

    private function reglagesAvecIbanBrut(?string $brut): BusinessSettings
    {
        $reglages = BusinessSettings::factory()->create(['user_id' => $this->user->id, 'iban' => 'LU280019400644750000']);
        DB::table('business_settings')->where('id', $reglages->id)->update(['iban' => $brut]);

        return $reglages->fresh();
    }

    /** Un salarié tel que la migration d'août l'a laissé : des vides bruts, et une valeur en clair. */
    private function salarieAncien(): Employee
    {
        $salarie = Employee::factory()->for($this->user)->create([
            'nationality' => 'Luxembourgeoise',
            'city' => 'Esch-sur-Alzette',
            'bank_iban' => 'LU280019400644750000',
        ]);
        DB::table('employees')->where('id', $salarie->id)->update([
            'city' => '',
            'bank_iban' => '',
            'benefits' => '',
            'nationality' => 'Luxembourgeoise',
            'emergency_contact' => '{"name":"Jean Muller","phone":"+352 621 000 000"}',
        ]);

        return $salarie->fresh();
    }

    // --- La lecture ------------------------------------------------------------

    public function test_un_iban_vide_se_lit_comme_absent(): void
    {
        $this->assertNull($this->reglagesAvecIbanBrut('')->iban);
    }

    public function test_un_iban_en_clair_d_avant_le_chiffrement_se_lit(): void
    {
        $this->assertSame('LU280019400644750000', $this->reglagesAvecIbanBrut('lu28 0019 4006 4475 0000')->iban);
    }

    public function test_un_iban_chiffre_se_lit_comme_avant(): void
    {
        $this->assertSame('LU280019400644750000', $this->reglagesAvecIbanBrut(Crypt::encryptString('LU280019400644750000'))->iban);
    }

    public function test_une_valeur_illisible_qui_n_est_pas_un_iban_vaut_null(): void
    {
        $this->assertNull($this->reglagesAvecIbanBrut('n importe quoi')->iban);
    }

    public function test_les_donnees_d_un_salarie_ancien_se_lisent(): void
    {
        $salarie = $this->salarieAncien();

        $this->assertNull($salarie->city);
        $this->assertNull($salarie->bank_iban);
        $this->assertNull($salarie->benefits);
        $this->assertSame('Luxembourgeoise', $salarie->nationality);
        $this->assertSame(['name' => 'Jean Muller', 'phone' => '+352 621 000 000'], $salarie->emergency_contact);
    }

    public function test_un_chiffre_illisible_vaut_null_plutot_que_du_charabia(): void
    {
        // Un chiffré d'une autre clé : ni lisible, ni en clair. On ne l'affiche pas.
        $salarie = Employee::factory()->for($this->user)->create(['city' => 'Esch-sur-Alzette']);
        DB::table('employees')->where('id', $salarie->id)->update(['city' => 'eyJpdiI6InBhcyBsYSBib25uZSBjbGUifQ==']);

        $this->assertNull($salarie->fresh()->city);
    }

    // --- Les pages -------------------------------------------------------------

    public function test_le_tableau_de_bord_et_les_reglages_repondent_avec_un_iban_vide(): void
    {
        $this->reglagesAvecIbanBrut('');

        $this->get(route('dashboard'))->assertOk();
        $this->get(route('settings.business.edit'))->assertOk();
    }

    public function test_le_tableau_de_bord_et_les_reglages_repondent_avec_un_iban_en_clair(): void
    {
        $this->reglagesAvecIbanBrut('LU280019400644750000');

        $this->get(route('dashboard'))->assertOk();
        $this->get(route('settings.business.edit'))->assertOk();
    }

    public function test_la_liste_et_la_fiche_du_salarie_repondent_avec_des_donnees_anciennes(): void
    {
        $salarie = $this->salarieAncien();

        $this->get(route('hr.employees.index'))->assertOk();
        $this->get(route('hr.employees.show', $salarie))->assertOk();
        $this->get(route('hr.employees.edit', $salarie))->assertOk();
    }

    // --- L'écriture ------------------------------------------------------------

    public function test_l_ecriture_chiffre_tout_le_vide_compris(): void
    {
        $reglages = BusinessSettings::factory()->create(['user_id' => $this->user->id, 'iban' => 'LU280019400644750000']);

        $brut = DB::table('business_settings')->where('id', $reglages->id)->value('iban');
        $this->assertStringStartsWith('eyJ', $brut);
        $this->assertSame('LU280019400644750000', Crypt::decryptString($brut));

        // Le vide se stocke chiffré : la colonne refuse le NULL, et une chaîne
        // vide brute est exactement ce qui cassait la lecture.
        $reglages->update(['iban' => '']);
        $brutVide = DB::table('business_settings')->where('id', $reglages->id)->value('iban');
        $this->assertStringStartsWith('eyJ', $brutVide);
        $this->assertSame('', Crypt::decryptString($brutVide));
        $this->assertNull($reglages->fresh()->iban);
    }

    public function test_un_champ_de_salarie_s_ecrit_chiffre_et_null_reste_null(): void
    {
        $salarie = Employee::factory()->for($this->user)->create(['city' => 'Esch-sur-Alzette', 'phone_perso' => null]);

        $brut = DB::table('employees')->where('id', $salarie->id)->first();
        $this->assertStringStartsWith('eyJ', $brut->city);
        $this->assertSame('Esch-sur-Alzette', Crypt::decryptString($brut->city));
        $this->assertNull($brut->phone_perso);
    }

    // --- La réparation ---------------------------------------------------------

    public function test_la_reparation_remet_les_lignes_anciennes_au_propre(): void
    {
        $vide = $this->reglagesAvecIbanBrut('');
        $clair = BusinessSettings::factory()->create(['user_id' => User::factory()->create()->id, 'iban' => 'LU120010001234567891']);
        DB::table('business_settings')->where('id', $clair->id)->update(['iban' => 'lu12 0010 0012 3456 7891']);
        $bon = BusinessSettings::factory()->create(['user_id' => User::factory()->create()->id, 'iban' => 'LU280019400644750000']);
        $brutBon = DB::table('business_settings')->where('id', $bon->id)->value('iban');
        $inconnu = BusinessSettings::factory()->create(['user_id' => User::factory()->create()->id, 'iban' => 'LU280019400644750000']);
        DB::table('business_settings')->where('id', $inconnu->id)->update(['iban' => 'pas un iban']);
        $salarie = $this->salarieAncien();

        $bilan = (new EncryptedValueRepair)->run();

        $this->assertSame(['vides' => 1, 'chiffres' => 1, 'inconnus' => 1], $bilan['business_settings']);
        $brutVide = DB::table('business_settings')->where('id', $vide->id)->value('iban');
        $this->assertSame('', Crypt::decryptString($brutVide));
        $this->assertNull($vide->fresh()->iban);
        $this->assertSame('LU120010001234567891', $clair->fresh()->iban);
        $this->assertStringStartsWith('eyJ', DB::table('business_settings')->where('id', $clair->id)->value('iban'));
        // Une ligne déjà chiffrée n'est pas réécrite, l'inconnue est laissée.
        $this->assertSame($brutBon, DB::table('business_settings')->where('id', $bon->id)->value('iban'));
        $this->assertSame('pas un iban', DB::table('business_settings')->where('id', $inconnu->id)->value('iban'));

        // Le salarié : trois vides (ville, IBAN, avantages), deux valeurs en
        // clair (nationalité, contact d'urgence).
        $this->assertSame(['vides' => 3, 'chiffres' => 2, 'inconnus' => 0], $bilan['employees']);
        $brut = DB::table('employees')->where('id', $salarie->id)->first();
        foreach (['city', 'bank_iban', 'benefits', 'nationality', 'emergency_contact'] as $champ) {
            $this->assertStringStartsWith('eyJ', $brut->{$champ}, $champ);
        }
        $this->assertSame('Luxembourgeoise', Crypt::decryptString($brut->nationality));
        $salarie = $salarie->fresh();
        $this->assertNull($salarie->city);
        $this->assertNull($salarie->benefits);
        $this->assertSame('Luxembourgeoise', $salarie->nationality);
        $this->assertSame('Jean Muller', $salarie->emergency_contact['name']);
    }

    public function test_la_reparation_est_idempotente(): void
    {
        $this->reglagesAvecIbanBrut('');
        $this->salarieAncien();
        (new EncryptedValueRepair)->run();

        $bilan = (new EncryptedValueRepair)->run();

        $this->assertSame(['vides' => 0, 'chiffres' => 0, 'inconnus' => 0], $bilan['business_settings']);
        $this->assertSame(['vides' => 0, 'chiffres' => 0, 'inconnus' => 0], $bilan['employees']);
    }

    // --- Le journal ------------------------------------------------------------

    public function test_le_journal_masque_toujours_les_valeurs_chiffrees_avec_les_casts_maison(): void
    {
        // Le journal reconnaît les attributs chiffrés par le nom du cast :
        // un cast maison doit porter le marqueur, sinon la valeur repart en clair.
        $reglages = BusinessSettings::factory()->create(['user_id' => $this->user->id, 'iban' => 'LU280019400644750000']);
        $reglages->update(['iban' => 'LU120010001234567891']);

        $brut = DB::table('audit_logs')->where('action', 'BusinessSettings.updated')->latest('id')->value('new_values');
        $this->assertStringNotContainsString('LU120010001234567891', $brut);
        $this->assertSame('LU12 **** **** **** 7891', json_decode($brut, true)['iban']);

        $salarie = Employee::factory()->for($this->user)->create(['city' => 'Esch-sur-Alzette']);
        $salarie->update(['city' => 'Differdange']);

        $brut = DB::table('audit_logs')->where('action', 'Employee.updated')->latest('id')->value('new_values');
        $this->assertStringNotContainsString('Differdange', $brut);
        $this->assertSame('***', json_decode($brut, true)['city']);
    }

    public function test_les_casts_reconnaissent_un_iban_et_un_chiffre(): void
    {
        $this->assertTrue(EncryptedIban::looksLikeIban('lu28 0019 4006 4475 0000'));
        $this->assertFalse(EncryptedIban::looksLikeIban(''));
        $this->assertFalse(EncryptedIban::looksLikeIban('eyJpdiI6'));
        $this->assertTrue(EncryptedText::looksEncrypted(Crypt::encryptString('x')));
        $this->assertFalse(EncryptedText::looksEncrypted('Luxembourgeoise'));
    }
}
