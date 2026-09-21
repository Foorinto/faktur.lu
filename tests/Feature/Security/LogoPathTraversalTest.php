<?php

namespace Tests\Feature\Security;

use App\Models\BusinessSettings;
use App\Models\User;
use App\Services\InvoicePdfService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * CRIT-1 — Traversée de chemin via logo_path → lecture de fichiers arbitraires.
 *
 * Un logo_path accepté comme chaîne libre (ex. « ../../../.env »), écrit en base
 * puis lu par file_get_contents('storage/app/public/'.$logoPath), exposait le
 * contenu de n'importe quel fichier dans l'aperçu HTML de facture.
 *
 * Deux verrous testés : le champ n'est plus modifiable par la requête, et la
 * lecture du logo est bornée au disque public.
 */
class LogoPathTraversalTest extends TestCase
{
    use RefreshDatabase;

    private function payloadValide(array $extra = []): array
    {
        return array_merge([
            'company_name' => 'ACME',
            'legal_name' => 'ACME SARL',
            'address' => '1 rue du Test',
            'postal_code' => '1111',
            'city' => 'Luxembourg',
            'country_code' => 'LU',
            'vat_regime' => 'franchise',
            'matricule' => '12345678901',
            'iban' => 'LU280019400644750000',
            'bic' => 'BCEELULL',
            'email' => 'acme@example.com',
        ], $extra);
    }

    public function test_logo_path_cannot_be_set_through_settings_update(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        BusinessSettings::factory()->create(['logo_path' => null]);

        $this->put(route('settings.business.update'), $this->payloadValide([
            'logo_path' => '../../../.env',
            // Réauthentification à l'acte : la charge utile pose un IBAN, le
            // mot de passe est demandé.
            'current_password' => 'password',
        ]))->assertSessionHasNoErrors();

        $this->assertNull(
            BusinessSettings::first()->logo_path,
            'logo_path ne doit jamais être défini par le corps de la requête'
        );
    }

    public function test_logo_reader_refuses_traversal_and_absolute_paths(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $service = app(InvoicePdfService::class);
        $method = new \ReflectionMethod($service, 'getLogoDataUri');
        $method->setAccessible(true);

        foreach (['../../../.env', '/etc/passwd', '..\\..\\.env', ''] as $mauvais) {
            $this->assertNull(
                $method->invoke($service, $mauvais),
                "Le lecteur de logo doit refuser : {$mauvais}"
            );
        }
    }

    public function test_invoice_preview_never_leaks_env_through_logo(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $settings = BusinessSettings::factory()->create();

        // Tentative directe de fixer un chemin de traversée (forceFill simule
        // une valeur malveillante déjà stockée : la lecture doit la neutraliser).
        $settings->forceFill(['logo_path' => '../../../.env'])->save();

        $service = app(InvoicePdfService::class);
        $method = new \ReflectionMethod($service, 'getLogoDataUri');
        $method->setAccessible(true);

        $result = $method->invoke($service, $settings->fresh()->logo_path);
        $this->assertNull($result, 'Un logo_path malveillant stocké ne doit rien lire');
    }
}
