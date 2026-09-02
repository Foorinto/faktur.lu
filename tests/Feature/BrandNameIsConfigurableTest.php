<?php

namespace Tests\Feature;

use App\Support\HomepageStructuredData;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Le nom de la marque vient de la configuration, pas du code.
 *
 * Un changement de dénomination est engagé (accord amiable du 2026-09-01). Le
 * nom était écrit en dur à plus de quatre cents endroits ; ces tests couvrent
 * les usages d'IDENTITÉ, ceux où une valeur périmée produit un vrai défaut :
 * expéditeur des courriels, données structurées lues par les moteurs, en-tête
 * du fichier FAIA remis à l'AED, libellé porté sur les factures d'abonnement.
 *
 * ⚠️ Ces tests ne vérifient pas que le mot « faktur » a disparu du dépôt. Il
 * reste dans les textes de prose des traductions, où il sera remplacé
 * mécaniquement le jour du changement. Distinguer les deux est délibéré :
 * paramétrer un millier de phrases coûterait plus cher que de les remplacer,
 * et rendrait les traductions illisibles.
 */
class BrandNameIsConfigurableTest extends TestCase
{
    use RefreshDatabase;

    /** Bascule le nom, comme le fera le jour J une seule ligne de .env. */
    private function sousLeNom(string $nom, callable $verification): void
    {
        config([
            'marque.nom' => $nom,
            'marque.domaine' => $nom,
            'marque.email_expediteur' => 'factures@'.$nom,
            // Les profils externes changent aussi, mais à la main : leurs
            // identifiants ne se déduisent pas du domaine.
            'marque.reseaux' => ['https://www.linkedin.com/company/'.str_replace('.', '-', $nom).'/'],
        ]);

        $verification();
    }

    public function test_the_configuration_derives_everything_from_one_value(): void
    {
        // L'adresse de contact se déduit du domaine : rien à changer deux fois.
        $this->assertStringContainsString(config('marque.domaine'), config('marque.email_contact'));

        // ⚠️ Pas d'assertion sur `email_expediteur` : il lit MAIL_FROM_ADDRESS,
        // renseigné séparément dans .env et donc indépendant du domaine. Ce
        // test échouait dès qu'on basculait APP_NAME pour essayer. Le repli,
        // lui, est vérifié par test_the_default_sender_follows_the_domain.
        $this->assertNotEmpty(config('marque.email_expediteur'));
    }

    /**
     * Les champs d'IDENTITÉ des données structurées suivent la configuration.
     *
     * ⚠️ Pas la prose : les réponses de la foire aux questions citent le nom
     * dans des phrases entières, et viennent des traductions. Elles seront
     * remplacées mécaniquement le jour du changement. Exiger ici qu'aucune
     * occurrence ne subsiste ferait échouer un test sur un travail qui n'est
     * pas celui de cette étape.
     */
    public function test_the_structured_data_identity_follows_the_configured_name(): void
    {
        $this->sousLeNom('kolux.lu', function () {
            $donnees = HomepageStructuredData::build('https://kolux.lu', 'fr');
            $noms = [];

            array_walk_recursive($donnees, function ($valeur, $cle) use (&$noms) {
                if ($cle === 'name' && is_string($valeur)) {
                    $noms[] = $valeur;
                }
            });

            // Le nom du produit, tel que les moteurs le liront.
            $this->assertContains('kolux.lu', $noms);
            $this->assertNotContains('faktur.lu', $noms);

            // Et les profils externes, qui se changent à la main.
            // ⚠️ JSON_UNESCAPED_SLASHES : sans lui, json_encode produit
            // « linkedin.com\/company\/faktur » et l'aiguille ne trouve
            // jamais rien. L'assertion passait quelle que soit la valeur.
            $this->assertStringNotContainsString(
                'linkedin.com/company/faktur',
                json_encode($donnees, JSON_UNESCAPED_SLASHES)
            );
        });
    }

    /**
     * L'en-tête du fichier FAIA déclare le logiciel émetteur. Un nom périmé
     * dans un fichier remis à l'AED serait une déclaration inexacte.
     */
    public function test_the_faia_header_follows_the_configured_name(): void
    {
        $source = file_get_contents(base_path('app/Services/AuditExportService.php'));

        $this->assertStringContainsString("addChild('SoftwareCompanyName', config('marque.nom'))", $source);
        $this->assertStringNotContainsString("addChild('SoftwareCompanyName', 'faktur.lu')", $source);
    }

    /**
     * L'adresse d'expédition par défaut : un courriel parti d'un domaine qui
     * n'existe plus ne revient pas, il disparaît.
     */
    public function test_the_default_sender_follows_the_domain(): void
    {
        $this->sousLeNom('kolux.lu', function () {
            config(['mail.from.address' => null]);

            $reglages = new \App\Models\EmailSettings;

            $this->assertSame('factures@kolux.lu', $reglages->getEffectiveFromAddress());
        });
    }

    /**
     * Les usages d'identité dans app/ ne doivent plus écrire le nom en dur.
     * C'est le garde-fou qui empêche d'en réintroduire un par distraction.
     */
    public function test_no_identity_usage_hardcodes_the_name_any_more(): void
    {
        $fichiers = [
            'app/Support/HomepageStructuredData.php',
            'app/Services/AuditExportService.php',
            'app/Http/Controllers/Accountant/AccountantExportController.php',
            'app/Http/Controllers/SubscriptionController.php',
            'app/Models/EmailSettings.php',
        ];

        foreach ($fichiers as $fichier) {
            $this->assertStringNotContainsString(
                "'faktur.lu'",
                file_get_contents(base_path($fichier)),
                "{$fichier} écrit encore le nom de la marque en dur."
            );
        }
    }
}
