<?php

namespace Tests\Feature;

use App\Http\Controllers\TranslationsController;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Les textes du site suivent le nom de la marque.
 *
 * Près de mille phrases le citaient en dur. Elles portent désormais le marqueur
 * `:app`, rempli automatiquement des deux côtés : par le traducteur pour le
 * serveur, au moment de produire le JSON pour le navigateur.
 *
 * ⚠️ L'injection est GLOBALE, et c'est ce qui la rend sûre. Compter sur chaque
 * site d'appel pour passer le paramètre, sur mille phrases, c'était accepter
 * qu'un oubli affiche « :app » en clair à un client.
 */
class BrandNameInTranslationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_translator_fills_the_brand_in_every_language(): void
    {
        config(['marque.nom' => 'kolux.lu']);

        foreach (['fr', 'de', 'en', 'lb', 'pt'] as $langue) {
            $this->app->setLocale($langue);

            $sujet = __('app.mail_subject_newsletter_confirmation');

            $this->assertStringContainsString('kolux.lu', $sujet, "Langue {$langue}");
            $this->assertStringNotContainsString(':app', $sujet, "Langue {$langue}");
        }
    }

    /**
     * Un appel qui passe son propre nom garde la main.
     */
    public function test_an_explicit_parameter_wins(): void
    {
        config(['marque.nom' => 'kolux.lu']);
        $this->app->setLocale('fr');

        $this->assertStringContainsString(
            'autrechose.lu',
            __('app.mail_subject_newsletter_confirmation', ['app' => 'autrechose.lu'])
        );
    }

    /**
     * Le marqueur ne doit jamais atteindre le navigateur.
     */
    public function test_the_browser_payload_carries_no_marker(): void
    {
        config(['marque.nom' => 'kolux.lu']);

        foreach (['fr', 'de', 'en', 'lb', 'pt'] as $langue) {
            $charge = json_encode(HandleInertiaRequests::translationsFor($langue));

            $this->assertStringNotContainsString(':app', $charge, "Langue {$langue}");
            $this->assertStringContainsString('kolux.lu', $charge, "Langue {$langue}");
        }
    }

    /**
     * ⚠️ L'empreinte du cache doit suivre le nom.
     *
     * Le JSON des traductions est servi avec un cache immuable d'un an, dont
     * l'URL porte cette empreinte. Si elle ne dépendait que de la date des
     * fichiers, un changement de dénomination laisserait les navigateurs
     * afficher l'ancien nom pendant un an.
     */
    public function test_the_cache_fingerprint_follows_the_brand(): void
    {
        config(['marque.nom' => 'faktur.lu']);
        $avant = TranslationsController::fingerprint();

        config(['marque.nom' => 'kolux.lu']);
        $apres = TranslationsController::fingerprint();

        $this->assertNotSame($avant, $apres);
    }

    /**
     * ⚠️ Le vocabulaire allemand et luxembourgeois est intact.
     *
     * « fakturieren », « Fakturatioun », « Fakturierung » signifient facturer.
     * Le site en compte 88 occurrences dans ses fichiers de langue. Un
     * remplacement sur « faktur » les aurait massacrés dans deux langues.
     */
    public function test_german_and_luxembourgish_vocabulary_survived(): void
    {
        $mots = 0;

        foreach (['de', 'lb'] as $langue) {
            $contenu = file_get_contents(lang_path("{$langue}/app.php"));
            $mots += preg_match_all('/Fakturatioun|fakturéier|fakturier|Fakturierung/iu', $contenu);
        }

        $this->assertGreaterThan(50, $mots, 'Le vocabulaire allemand et luxembourgeois a été abîmé.');
    }

    /**
     * Les clés de traduction gardent leur nom : ce sont des identifiants
     * internes, jamais affichés, et les renommer casserait chaque appel.
     */
    public function test_translation_keys_were_left_alone(): void
    {
        $contenu = file_get_contents(lang_path('fr/app.php'));

        $this->assertStringContainsString("'why_faktur'", $contenu);
        $this->assertStringContainsString("'col_faktur'", $contenu);
    }

    /**
     * Plus aucune phrase ne cite le nom en dur.
     */
    public function test_no_sentence_hardcodes_the_brand_any_more(): void
    {
        foreach (['fr', 'de', 'en', 'lb', 'pt'] as $langue) {
            $contenu = file_get_contents(lang_path("{$langue}/app.php"));

            $this->assertSame(
                0,
                preg_match_all('/[Ff]aktur\.lu/', $contenu),
                "Le fichier {$langue} cite encore le nom en dur."
            );
        }
    }
}
