<?php

namespace Tests\Feature;

use App\Http\Controllers\TranslationsController;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Traductions servies dans un fichier à part.
 *
 * Elles voyageaient dans le HTML de chaque page via la prop Inertia
 * `translations` : 323 Ko bruts, plus de la moitié du document, pour une
 * poignée de clés réellement utilisées. Servies à part et mises en cache par
 * le navigateur, elles ne sont téléchargées qu'une fois.
 *
 * Le point délicat n'est pas le poids mais le PRERENDERING : il capture un
 * snapshot dès qu'un `h1` contient du texte non vide, et une clé brute en est.
 * Le chargement se fait donc avant le montage de l'application, ce qui rend le
 * cas impossible côté navigateur ; ces tests couvrent le contrat côté serveur.
 */
class TranslationsEndpointTest extends TestCase
{
    // La page d'accueil interroge les articles de blog : sans base, elle rend
    // une page d'erreur 500. Un test qui l'inspectait passait alors pour de
    // mauvaises raisons, en trouvant ses propres mots dans la trace.
    use RefreshDatabase;

    /** @return array<int, array<int, string>> */
    public static function locales(): array
    {
        return [['fr'], ['en'], ['de'], ['lb'], ['pt']];
    }

    #[DataProvider('locales')]
    public function test_chaque_langue_est_servie(string $locale): void
    {
        $reponse = $this->get("/lang/{$locale}.json")->assertSuccessful();

        $payload = $reponse->json();

        $this->assertArrayHasKey('app', $payload);
        $this->assertNotEmpty($payload['app']);
    }

    public function test_le_contenu_est_identique_a_celui_du_middleware(): void
    {
        // Les deux doivent produire exactement la même chose : c'est ce qui
        // garantit qu'aucune clé ne disparaît en passant par le fichier.
        foreach (['fr', 'de'] as $locale) {
            $this->assertSame(
                HandleInertiaRequests::translationsFor($locale),
                $this->get("/lang/{$locale}.json")->json()
            );
        }
    }

    public function test_la_reponse_est_mise_en_cache_durablement(): void
    {
        // L'empreinte étant dans l'URL, le contenu ne changera jamais pour
        // cette adresse : un cache immuable et long est donc correct, et c'est
        // tout l'intérêt de l'opération.
        $entete = $this->get('/lang/fr.json')->headers->get('Cache-Control');

        $this->assertStringContainsString('immutable', $entete);
        $this->assertStringContainsString('max-age=31536000', $entete);
    }

    public function test_une_langue_inconnue_est_refusee(): void
    {
        $this->get('/lang/xx.json')->assertNotFound();
    }

    public function test_l_empreinte_change_avec_les_fichiers_de_langue(): void
    {
        $avant = TranslationsController::fingerprint();

        touch(lang_path('fr/app.php'));
        clearstatcache();

        $this->assertNotSame($avant, TranslationsController::fingerprint(),
            "L'empreinte doit changer, sans quoi les navigateurs garderaient d'anciennes traductions en cache.");
    }

    public function test_une_cle_ajoutee_est_servie_sans_purge_de_cache(): void
    {
        // Le défaut qui a motivé ce test : l'empreinte était mise en cache
        // « pour toujours ». Ajouter une clé de traduction ne la changeait donc
        // plus, le navigateur gardait l'ancien fichier, et l'interface affichait
        // la clé brute. Constaté sur `view_client` le 2026-08-08.
        $avant = $this->get('/lang/fr.json')->json('app');

        $this->assertArrayHasKey('view_client', $avant,
            'Une clé présente dans le fichier de langue doit être servie.');

        // L'URL doit elle aussi refléter le contenu, sans quoi un navigateur
        // ayant déjà mis l'ancienne en cache ne redemanderait jamais rien.
        $empreinte = TranslationsController::fingerprint();
        $this->assertStringContainsString($empreinte, $this->get('/fr')->getContent());
    }

    public function test_la_page_ne_transporte_plus_les_traductions(): void
    {
        // Le statut est vérifié explicitement : sans base de données, /fr rend
        // une page d'erreur de 998 Ko qui contient le code source du test, donc
        // les mots qu'on y cherche. Ce test passait ainsi au vert sur une 500.
        $html = $this->get('/fr')->assertSuccessful()->getContent();

        // C'était le poste le plus lourd du document. Sa réapparition ferait
        // regagner 323 Ko à chaque page sans que personne ne s'en aperçoive.
        $this->assertStringContainsString('translationsUrl', $html);
        $this->assertStringNotContainsString('&quot;translations&quot;:', $html);
    }
}
