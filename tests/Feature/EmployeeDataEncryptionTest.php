<?php

namespace Tests\Feature;

use App\Models\HR\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Chiffrement au repos des données personnelles des salariés.
 *
 * L'hébergement est mutualisé et ne garantit pas le chiffrement du disque. La
 * table `employees` porte de quoi reconstituer une identité complète — date de
 * naissance, nationalité, adresse et téléphone personnels, contact d'urgence —
 * sur des personnes qui ne sont pas clientes du service et n'ont rien signé.
 *
 * Ces tests tiennent les deux bouts : illisible en base, transparent à l'usage.
 */
class EmployeeDataEncryptionTest extends TestCase
{
    use RefreshDatabase;

    /** Champs chiffrés dont la valeur est une chaîne simple. */
    private const CHAMPS = [
        'nationality' => 'Luxembourgeoise',
        'phone_perso' => '+352 621 123 456',
        'email_perso' => 'prive@example.test',
        'address' => '13, Rue du Stade John F. Kennedy',
        'city' => 'Dudelange',
        'postal_code' => 'L-3502',
    ];

    private function employe(): Employee
    {
        $this->actingAs(User::factory()->create());

        return Employee::factory()->create(array_merge(self::CHAMPS, [
            'emergency_contact' => ['nom' => 'Marie Test', 'telephone' => '+352 691 000 000'],
            'bank_iban' => 'LU28 0019 4006 4475 0000',
        ]));
    }

    public function test_personal_data_is_unreadable_in_the_database(): void
    {
        $employe = $this->employe();

        $brut = DB::table('employees')->where('id', $employe->id)->first();

        foreach (self::CHAMPS as $champ => $valeur) {
            $this->assertNotSame(
                $valeur,
                $brut->{$champ},
                "Le champ {$champ} est encore lisible en clair dans la table."
            );

            $this->assertSame(
                $valeur,
                Crypt::decryptString($brut->{$champ}),
                "Le champ {$champ} doit se déchiffrer sur la valeur d'origine."
            );
        }

        $this->assertStringNotContainsString('Marie Test', (string) $brut->emergency_contact);
    }

    public function test_reading_through_the_model_is_transparent(): void
    {
        $employe = $this->employe()->fresh();

        foreach (self::CHAMPS as $champ => $valeur) {
            $this->assertSame($valeur, $employe->{$champ});
        }

        $this->assertSame('Marie Test', $employe->emergency_contact['nom']);
        $this->assertSame('LU28 0019 4006 4475 0000', $employe->bank_iban);
    }

    /**
     * Les champs volontairement laissés en clair doivent le rester : chiffrer
     * le salaire casserait l'arithmétique, chiffrer la date de naissance
     * casserait le cast dont dépendent les anniversaires du tableau de bord.
     */
    public function test_computable_fields_stay_in_the_clear(): void
    {
        $this->actingAs(User::factory()->create());

        $employe = Employee::factory()->create([
            'salary_gross' => 4250.50,
            'birth_date' => '1986-02-28',
        ]);

        $brut = DB::table('employees')->where('id', $employe->id)->first();

        $this->assertStringContainsString('4250', (string) $brut->salary_gross);
        $this->assertStringContainsString('1986-02-28', (string) $brut->birth_date);

        $this->assertSame('28/02/1986', $employe->fresh()->birth_date->format('d/m/Y'));
        $this->assertEqualsWithDelta(4250.50, (float) $employe->fresh()->salary_gross, 0.01);
    }

    /**
     * Un champ vide ne doit pas devenir une chaîne chiffrée : la colonne
     * resterait « remplie » aux yeux de toute vérification de complétude.
     */
    public function test_empty_values_stay_empty(): void
    {
        $this->actingAs(User::factory()->create());

        $employe = Employee::factory()->create(['phone_perso' => null, 'email_perso' => null]);

        $brut = DB::table('employees')->where('id', $employe->id)->first();

        $this->assertNull($brut->phone_perso);
        $this->assertNull($brut->email_perso);
        $this->assertNull($employe->fresh()->phone_perso);
    }

    /**
     * Le garde-fou du garde-fou. Si un jour quelqu'un ajoute un `where` sur un
     * champ chiffré, il ne trouvera rien — le vecteur d'initialisation étant
     * aléatoire, deux fois la même valeur donne deux chiffrés différents. Ce
     * test documente la limite plutôt que de la laisser découvrir en
     * production.
     */
    public function test_an_encrypted_field_cannot_be_searched_in_sql(): void
    {
        $this->employe();

        $this->assertSame(
            0,
            DB::table('employees')->where('city', 'Dudelange')->count(),
            'Une recherche SQL sur un champ chiffré ne peut pas aboutir : filtrer après chargement.'
        );
    }
}
