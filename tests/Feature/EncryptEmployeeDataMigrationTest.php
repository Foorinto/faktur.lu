<?php

namespace Tests\Feature;

use App\Models\HR\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Conversion des données déjà en base.
 *
 * C'est la partie qui touche la production. Les lignes existantes contiennent
 * du texte en clair : sans conversion, chaque lecture échouerait sur une
 * exception de déchiffrement et le module RH serait mort, pas dégradé.
 *
 * On éprouve donc la migration sur des lignes écrites en clair — l'état réel du
 * serveur avant déploiement.
 */
class EncryptEmployeeDataMigrationTest extends TestCase
{
    use RefreshDatabase;

    private const FICHIER = 'database/migrations/2026_08_13_120000_encrypt_employee_personal_data.php';

    private function migration(): object
    {
        return require base_path(self::FICHIER);
    }

    /**
     * Écrit une ligne en clair, comme elle l'était avant la migration. On
     * contourne le modèle : son cast chiffrerait à l'écriture, et il n'y aurait
     * plus rien à convertir.
     */
    private function employeEnClair(array $valeurs): int
    {
        $this->actingAs(User::factory()->create());
        $employe = Employee::factory()->create();

        DB::table('employees')->where('id', $employe->id)->update($valeurs);

        return $employe->id;
    }

    public function test_existing_plaintext_rows_are_converted(): void
    {
        $id = $this->employeEnClair([
            'nationality' => 'Portugaise',
            'city' => 'Esch-sur-Alzette',
            'address' => '5, Rue de la Gare',
        ]);

        $this->migration()->up();

        $brut = DB::table('employees')->where('id', $id)->first();

        $this->assertNotSame('Portugaise', $brut->nationality, 'La valeur doit avoir été chiffrée.');
        $this->assertSame('Portugaise', Crypt::decryptString($brut->nationality));
        $this->assertSame('Esch-sur-Alzette', Crypt::decryptString($brut->city));

        // Et surtout : le modèle doit relire, sans exception.
        $employe = Employee::withoutUserScope()->find($id);
        $this->assertSame('Portugaise', $employe->nationality);
        $this->assertSame('5, Rue de la Gare', $employe->address);
    }

    /**
     * Le risque le plus concret d'une reprise de données : la rejouer. Un
     * double chiffrement rendrait les valeurs illisibles sans erreur visible.
     */
    public function test_running_it_twice_does_not_double_encrypt(): void
    {
        $id = $this->employeEnClair(['nationality' => 'Belge']);

        $this->migration()->up();
        $premier = DB::table('employees')->where('id', $id)->value('nationality');

        $this->migration()->up();
        $second = DB::table('employees')->where('id', $id)->value('nationality');

        $this->assertSame($premier, $second, 'La seconde exécution ne doit rien retoucher.');
        $this->assertSame('Belge', Crypt::decryptString($second));
    }

    public function test_null_columns_are_left_alone(): void
    {
        $id = $this->employeEnClair([
            'nationality' => null,
            'phone_perso' => null,
            'city' => 'Differdange',
        ]);

        $this->migration()->up();

        $brut = DB::table('employees')->where('id', $id)->first();

        $this->assertNull($brut->nationality, 'Une colonne vide ne doit pas devenir une chaîne chiffrée.');
        $this->assertNull($brut->phone_perso);
        $this->assertSame('Differdange', Crypt::decryptString($brut->city));
    }

    /**
     * La réversibilité n'est pas un luxe : c'est le filet si la conversion se
     * passe mal en production.
     */
    public function test_it_can_be_rolled_back_to_plaintext(): void
    {
        $id = $this->employeEnClair(['nationality' => 'Française', 'city' => 'Thionville']);

        $migration = $this->migration();
        $migration->up();
        $migration->down();

        $brut = DB::table('employees')->where('id', $id)->first();

        $this->assertSame('Française', $brut->nationality);
        $this->assertSame('Thionville', $brut->city);
    }

    /**
     * Une colonne trop étroite tronquerait le chiffré, et la valeur serait
     * perdue sans qu'aucune erreur ne le signale.
     */
    public function test_the_columns_can_hold_a_ciphertext(): void
    {
        $long = str_repeat('Grand-Duché de Luxembourg ', 8);

        $id = $this->employeEnClair(['address' => $long]);

        $this->migration()->up();

        $this->assertSame(
            $long,
            Crypt::decryptString(DB::table('employees')->where('id', $id)->value('address')),
            'La colonne doit accepter un chiffré sans le tronquer.'
        );
    }
}
