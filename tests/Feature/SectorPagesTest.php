<?php

namespace Tests\Feature;

use App\Http\Controllers\SectorPageController;
use App\Models\SectorLead;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Pages sectorielles et recueil des manifestations d'intérêt.
 *
 * Ces pages sont des instruments de mesure : elles doivent dire si quelqu'un
 * cherche une solution de facturation pour son métier au Luxembourg, et ce qui
 * lui coûte du temps. Personne ici ne peut trancher autrement, faute de contact
 * dans ces secteurs.
 *
 * D'où deux exigences que les tests fixent. Les pages doivent exister là où le
 * sitemap les annonce — la leçon de `/pt/contacto`, onze pages introuvables. Et
 * elles doivent dire ce qui n'existe PAS : laisser espérer un pack métier ferait
 * venir des gens déçus, et fausserait la mesure elle-même.
 */
class SectorPagesTest extends TestCase
{
    use RefreshDatabase;

    private function reponse(array $surcharges = []): array
    {
        return array_merge([
            'sector' => 'health',
            'email' => 'infirmiere@exemple.lu',
            'message' => 'Recopier les mêmes actes chaque semaine.',
            'homepage_url' => '',
            'form_loaded_at' => microtime(true) - 30,
        ], $surcharges);
    }

    public function test_every_sector_page_is_reachable(): void
    {
        foreach (SectorPageController::allPaths() as $chemin) {
            $this->get($chemin)->assertOk();
        }

        // Un slug d'une autre langue sous ce chemin doit être refusé, sinon la
        // même page existerait à plusieurs adresses non publiées — un doublon
        // de contenu fabriqué par la route elle-même.
        $this->get('/de/rechnungssoftware-infirmier-luxemburg')->assertNotFound();
        $this->get('/fr/logiciel-facturation-krankenpfleger-luxembourg')->assertNotFound();
    }

    /**
     * Un slug à tiret casse la route sans que rien ne l'explique.
     *
     * L'URL est `logiciel-facturation-{metier}-luxembourg`, et Symfony compile
     * le paramètre en `[^/]++`, possessif : il ne revient jamais en arrière. Un
     * slug contenant un tiret avale donc le `-luxembourg` final. « artisan »
     * passait, « agence-immobiliere » renvoyait 404.
     */
    public function test_no_published_slug_contains_a_hyphen(): void
    {
        foreach (SectorPageController::slugTable() as $cle => $slugs) {
            $this->assertSame(
                array_keys(SectorPageController::URL_PATTERNS),
                array_keys($slugs),
                "Le métier « {$cle} » n'a pas de slug dans toutes les langues."
            );

            foreach ($slugs as $langue => $slug) {
                $this->assertStringNotContainsString('-', $slug,
                    "Le slug « {$slug} » ({$langue}) contient un tiret : la route ne correspondra plus.");

                $this->assertMatchesRegularExpression('/^[a-z]+$/', $slug,
                    "Le slug « {$slug} » ({$langue}) doit être un seul mot sans accent ni majuscule.");
            }
        }

        // Deux métiers qui partageraient un slug dans une même langue
        // rendraient l'une des deux pages inatteignable.
        foreach (array_keys(SectorPageController::URL_PATTERNS) as $langue) {
            $slugs = array_map(fn (array $s) => $s[$langue], SectorPageController::slugTable());

            $this->assertSame(array_unique($slugs), $slugs,
                "Deux métiers partagent un slug en {$langue}.");
        }
    }

    /**
     * Chaque page doit être reliée depuis le site, pas seulement déclarée au
     * sitemap.
     *
     * Les cinq pages « alternative à », publiées de la même façon depuis des
     * mois et jamais reliées à rien, n'ont pas reçu UNE SEULE impression sur la
     * période mesurée. Google découvre par les liens autant que par les
     * sitemaps : une page orpheline reste « détectée, actuellement non
     * indexée », et l'expérience qu'elle devait servir ne mesure alors rien.
     */
    public function test_every_page_is_linked_from_the_public_site(): void
    {
        $layout = file_get_contents(resource_path('js/Layouts/MarketingLayout.vue'));

        preg_match("/const metiersPublies = \[(.*?)\];/s", $layout, $trouve);

        $this->assertNotEmpty($trouve, 'La liste du pied de page est introuvable dans MarketingLayout.');

        // Les deux styles de guillemets : prettier normalise le fichier en
        // guillemets doubles, et une reformulation automatique ne doit pas
        // faire dire au test que plus aucune page n'est reliée.
        preg_match_all('/[\'"]([a-z]+)[\'"]/', $trouve[1], $liens);

        $manquants = array_diff(SectorPageController::pageSlugs(), $liens[1]);

        $this->assertSame([], array_values($manquants), sprintf(
            "Ces pages ne sont reliées depuis nulle part : elles resteront « détectée, non indexée ».\n  - %s\n",
            implode("\n  - ", $manquants)
        ));
    }

    /** Et l'inverse : un lien vers une page qui n'existe plus serait un 404. */
    public function test_the_footer_links_no_page_that_does_not_exist(): void
    {
        $layout = file_get_contents(resource_path('js/Layouts/MarketingLayout.vue'));
        preg_match("/const metiersPublies = \[(.*?)\];/s", $layout, $trouve);
        // Les deux styles de guillemets : prettier normalise le fichier en
        // guillemets doubles, et une reformulation automatique ne doit pas
        // faire dire au test que plus aucune page n'est reliée.
        preg_match_all('/[\'"]([a-z]+)[\'"]/', $trouve[1], $liens);

        $fantomes = array_diff($liens[1], SectorPageController::pageSlugs());

        $this->assertSame([], array_values($fantomes),
            'Le pied de page renvoie vers une page sectorielle inexistante : '.implode(', ', $fantomes));
    }

    public function test_an_unknown_trade_is_not_found(): void
    {
        $this->get('/fr/logiciel-facturation-astrologue-luxembourg')->assertNotFound();
    }

    /**
     * Chaque page annonce un secteur connu du sélecteur d'inscription : c'est
     * ce lien qui permettra de rapprocher les réponses des comptes créés.
     */
    public function test_each_page_targets_a_known_sector(): void
    {
        foreach (SectorPageController::pageSlugs() as $slug) {
            $props = $this->get("/fr/logiciel-facturation-{$slug}-luxembourg")
                ->viewData('page')['props'];

            $this->assertContains($props['sector'], User::BUSINESS_SECTORS,
                "La page {$slug} vise un secteur inconnu du sélecteur.");
        }
    }

    public function test_an_answer_is_recorded(): void
    {
        $this->post(route('sector-lead.store'), $this->reponse())
            ->assertRedirect();

        $lead = SectorLead::firstOrFail();

        $this->assertSame('health', $lead->sector);
        $this->assertSame('infirmiere@exemple.lu', $lead->email);
        $this->assertFalse($lead->wants_newsletter, "Le consentement newsletter doit être explicite.");
    }

    /**
     * L'email est exigé.
     *
     * Il était facultatif au motif qu'une réponse anonyme comptait autant pour
     * la mesure. C'était faux : le volume se mesure déjà par les impressions de
     * recherche. Ce que ce formulaire apporte de spécifique, c'est un contact
     * dans un secteur où nous n'en avons aucun, et de quoi prévenir la personne
     * le jour où le pack qu'elle a réclamé existe.
     */
    public function test_an_answer_without_an_email_is_refused(): void
    {
        $this->post(route('sector-lead.store'), $this->reponse(['email' => null]))
            ->assertSessionHasErrors('email');

        $this->assertSame(0, SectorLead::count());
    }

    public function test_an_unknown_sector_is_refused(): void
    {
        $this->post(route('sector-lead.store'), $this->reponse(['sector' => 'astrologie']))
            ->assertSessionHasErrors('sector');

        $this->assertSame(0, SectorLead::count());
    }

    /**
     * Le sitemap ne doit jamais annoncer une page qui n'existe pas : la liste
     * vient du contrôleur, pas d'une seconde énumération.
     */
    public function test_the_sitemap_lists_exactly_the_published_pages(): void
    {
        $xml = $this->get('/sitemap-pages.xml')->assertOk()->getContent();

        foreach (SectorPageController::allPaths() as $chemin) {
            // Le chemin seul : l'hôte vient d'APP_URL et diffère entre les
            // tests et la production.
            $this->assertStringContainsString("{$chemin}</loc>", $xml,
                "Le sitemap ne déclare pas {$chemin}.");

            $this->assertStringContainsString('hreflang="x-default"', $xml,
                'Les pages sectorielles doivent déclarer leurs équivalents.');
        }
    }

    /**
     * Chaque page dit ce qui n'existe pas encore. Sans cette mention, une page
     * métier laisse espérer un outil taillé pour le métier.
     */
    public function test_each_page_states_what_does_not_exist_yet(): void
    {
        foreach (SectorPageController::pageSlugs() as $slug) {
            $this->assertTrue(
                \Illuminate\Support\Facades\Lang::has("app.sector_pages.{$slug}.honesty", 'fr'),
                "La page {$slug} doit dire ce qui n'existe pas encore."
            );
        }
    }

    /**
     * Chaque page est traduite en entier, dans les cinq langues.
     *
     * `Lang::has()` reçoit `false` en troisième argument. Sans lui, il retombe
     * sur la langue de repli et répond « oui » pour l'allemand en lisant le
     * français : le test certifierait des traductions inexistantes, et la page
     * afficherait du français à un lecteur allemand sans que rien n'échoue.
     */
    public function test_every_page_is_fully_translated(): void
    {
        $clesParMetier = [
            'footer_label', 'page_title', 'meta_description', 'h1', 'intro',
            'point_1', 'point_2', 'point_3', 'honesty', 'kicker_label',
            'context_1', 'context_2', 'context_3',
        ];

        $clesGeneriques = [
            'footer_title', 'what_exists', 'cta_generic', 'cta_link', 'kicker',
            'purpose_title', 'purpose', 'purpose_effort', 'not_yet', 'context_title',
        ];

        $manquantes = [];

        foreach (array_keys(SectorPageController::URL_PATTERNS) as $langue) {
            foreach ($clesGeneriques as $cle) {
                if (! \Illuminate\Support\Facades\Lang::has("app.sector_pages.{$cle}", $langue, false)) {
                    $manquantes[] = "{$langue} : sector_pages.{$cle}";
                }
            }

            foreach (SectorPageController::pageKeys() as $metier) {
                foreach ($clesParMetier as $cle) {
                    if (! \Illuminate\Support\Facades\Lang::has("app.sector_pages.{$metier}.{$cle}", $langue, false)) {
                        $manquantes[] = "{$langue} : sector_pages.{$metier}.{$cle}";
                    }
                }
            }
        }

        $this->assertSame([], $manquantes,
            "Traductions manquantes — la page servirait du français sous une autre langue :\n  - "
            .implode("\n  - ", $manquantes)."\n"
        );
    }

    /**
     * Le JavaScript et le PHP tiennent la même table de slugs.
     *
     * `useLocalizedRoute.js` duplique la table du contrôleur, parce que le pied
     * de page doit construire ces liens sans aller-retour serveur. La
     * duplication est assumée ; sa dérive ne l'est pas. Elle serait silencieuse
     * dans les deux sens : un lien de pied de page vers une URL qui n'existe
     * pas, ou une page publiée que plus rien ne relie.
     */
    public function test_the_javascript_slug_table_matches_the_controller(): void
    {
        $source = file_get_contents(resource_path('js/Composables/useLocalizedRoute.js'));

        preg_match('/export const SECTOR_SLUGS = \{(.*?)\n\};/s', $source, $bloc);

        $this->assertNotEmpty($bloc, 'SECTOR_SLUGS introuvable — le composable a été restructuré.');

        $jsSlugs = [];

        foreach (explode("\n", $bloc[1]) as $ligne) {
            if (! preg_match('/^\s*([a-z_]+):\s*\{(.*)\},?\s*$/', $ligne, $m)) {
                continue;
            }

            preg_match_all('/([a-z]{2}):\s*[\'"]([a-z]+)[\'"]/', $m[2], $paires, PREG_SET_ORDER);

            foreach ($paires as $paire) {
                $jsSlugs[$m[1]][$paire[1]] = $paire[2];
            }
        }

        $this->assertSame(SectorPageController::slugTable(), $jsSlugs,
            'La table de useLocalizedRoute.js a dérivé de SectorPageController.'
        );

        // Les motifs d'URL, eux aussi dupliqués.
        preg_match('/export const SECTOR_URL_PATTERNS = \{(.*?)\n\};/s', $source, $motifs);

        $this->assertNotEmpty($motifs, 'SECTOR_URL_PATTERNS introuvable.');

        preg_match_all('/([a-z]{2}):\s*[\'"]([^\'"]+)[\'"]/', $motifs[1], $paires, PREG_SET_ORDER);

        $jsMotifs = [];

        foreach ($paires as $paire) {
            $jsMotifs[$paire[1]] = $paire[2];
        }

        $this->assertSame(SectorPageController::URL_PATTERNS, $jsMotifs,
            'Les motifs d\'URL du JavaScript ont dérivé de ceux du contrôleur.'
        );
    }

    /**
     * Le but de la page doit se lire avant le formulaire.
     *
     * Sans ce cadrage, la page s'ouvrait comme une page produit — un titre qui
     * promet un logiciel, puis un formulaire sans explication — et le visiteur
     * devait deviner qu'on lui demandait son avis. Une page qui ne dit pas
     * pourquoi elle pose une question n'obtient pas de réponse.
     *
     * Le contrôle porte sur le gabarit et non sur la réponse HTTP : la page est
     * rendue par Inertia, ses textes arrivent côté client, et le HTML servi ne
     * contient aucune des phrases qu'on voudrait vérifier.
     */
    public function test_each_page_explains_why_it_asks(): void
    {
        $gabarit = file_get_contents(resource_path('js/Pages/Sectors/Show.vue'));

        // `sector_pages.purpose` et non `purpose_title` : on cherche le
        // paragraphe, pas son titre. Les guillemets ne sont pas dans l'ancre,
        // prettier normalisant le fichier en guillemets doubles.
        $but = mb_strpos($gabarit, 'sector_pages.purpose"');
        $formulaire = mb_strpos($gabarit, '<SectorInterestForm');

        $this->assertNotFalse($but, "La page doit dire pourquoi elle existe.");
        $this->assertNotFalse($formulaire, 'Le formulaire a disparu de la page.');

        $this->assertLessThan($formulaire, $but,
            'Le but doit être énoncé avant le formulaire, pas après.'
        );

        // L'argumentaire produit vient après le formulaire : un visiteur qui
        // repart sans avoir lu la liste des fonctionnalités n'a rien coûté ;
        // un visiteur qui repart sans avoir répondu, si.
        $this->assertGreaterThan($formulaire, mb_strpos($gabarit, "sector_pages.what_exists"),
            "L'argumentaire doit rester après le formulaire."
        );
    }

    /**
     * Chaque métier porte trois particularités qui lui sont propres.
     *
     * C'est ce qui distingue cinq pages sectorielles de cinq variantes du même
     * argumentaire. Un moteur de recherche traite les secondes comme des
     * doublons, et un lecteur aussi — il n'apprend rien qu'il ne pouvait lire
     * sur la page d'accueil.
     */
    public function test_each_page_carries_content_specific_to_its_trade(): void
    {
        $vus = [];

        foreach (SectorPageController::pageSlugs() as $slug) {
            foreach (['context_1', 'context_2', 'context_3'] as $rang) {
                $cle = "app.sector_pages.{$slug}.{$rang}";

                $this->assertTrue(
                    \Illuminate\Support\Facades\Lang::has($cle, 'fr'),
                    "La page {$slug} doit porter une particularité luxembourgeoise en {$rang}."
                );

                $texte = trim(__($cle, [], 'fr'));

                $this->assertGreaterThan(80, mb_strlen($texte),
                    "{$slug}.{$rang} est trop court pour apprendre quoi que ce soit."
                );

                $this->assertArrayNotHasKey($texte, $vus, sprintf(
                    'Texte identique entre %s et %s : ce sont alors deux fois la même page.',
                    $vus[$texte] ?? '?', "{$slug}.{$rang}"
                ));

                $vus[$texte] = "{$slug}.{$rang}";
            }
        }
    }

    /**
     * L'accroche nomme le métier, et le nom doit exister.
     *
     * `kicker` interpole `:metier` ; sans `kicker_label`, la page afficherait
     * le marqueur brut au premier écran.
     */
    public function test_each_page_names_its_trade_in_the_kicker(): void
    {
        foreach (SectorPageController::pageSlugs() as $slug) {
            $this->assertTrue(
                \Illuminate\Support\Facades\Lang::has("app.sector_pages.{$slug}.kicker_label", 'fr'),
                "La page {$slug} doit nommer son métier dans l'accroche."
            );
        }

        // `kicker` ne dit rien tout seul : il faut que le gabarit lui passe
        // le nom du métier, sans quoi « :metier » s'affiche tel quel.
        $gabarit = file_get_contents(resource_path('js/Pages/Sectors/Show.vue'));

        $this->assertMatchesRegularExpression(
            '/sector_pages\.kicker[\'"],\s*\{\s*metier:/',
            $gabarit,
            "L'accroche doit recevoir le nom du métier en paramètre."
        );
    }
}
