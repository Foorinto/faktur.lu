<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Rendu dynamique : qui reçoit le HTML pré-rendu.
 *
 * Le middleware sert un instantané statique à une LISTE FERMÉE de robots. Tout
 * ce qui n'y figure pas reçoit la coquille du SPA — un titre « faktur.lu » et
 * aucun contenu.
 *
 * Cette liste s'est révélée incomplète deux fois : le 8 août pour des robots
 * d'IA qui indexaient du vide, puis le 18 août pour `Google-InspectionTool`,
 * l'agent de l'inspection d'URL de Search Console. Ce dernier cas était le plus
 * traître : tester une page dans Search Console montrait une page vide alors que
 * Googlebot recevait le contenu. Le diagnostic contredisait la réalité.
 */
class PrerenderedForCrawlersTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Le middleware n'agit que sur une route existante : le test doit donc
     * viser une vraie page publique, et l'instantané réel de cette page est
     * écrasé le temps de l'essai.
     *
     * D'où la sauvegarde. La première version de ce test écrivait dans
     * `prerendered/fr/index.html` sans rien restaurer — et comme le déploiement
     * rsync ces fichiers vers la production après les avoir régénérés, un échec
     * de régénération aurait envoyé le contenu de test sur le site.
     */
    private const CHEMIN_ESSAI = 'fr';

    private ?string $sauvegarde = null;

    private bool $existait = false;

    private function fichierInstantane(): string
    {
        return public_path('prerendered/'.self::CHEMIN_ESSAI.'/index.html');
    }

    private function preparerInstantane(): void
    {
        $fichier = $this->fichierInstantane();

        if (! is_dir(dirname($fichier))) {
            mkdir(dirname($fichier), 0755, true);
        }

        if (is_file($fichier)) {
            $this->existait = true;
            $this->sauvegarde = file_get_contents($fichier);
        }

        file_put_contents($fichier, '<html><head><title>Contenu pré-rendu</title></head><body><h1>Facturation</h1></body></html>');
    }

    protected function tearDown(): void
    {
        $fichier = $this->fichierInstantane();

        if ($this->existait && $this->sauvegarde !== null) {
            file_put_contents($fichier, $this->sauvegarde);
        } elseif (is_file($fichier)) {
            unlink($fichier);
        }

        $this->sauvegarde = null;
        $this->existait = false;

        parent::tearDown();
    }

    public static function robotsServis(): array
    {
        return [
            'Googlebot' => ['Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)'],
            'Search Console' => ['Mozilla/5.0 (compatible; Google-InspectionTool/1.0;)'],
            'Bingbot' => ['Mozilla/5.0 (compatible; bingbot/2.0; +http://www.bing.com/bingbot.htm)'],
            'GPTBot' => ['Mozilla/5.0 (compatible; GPTBot/1.2; +https://openai.com/gptbot)'],
            'OAI-SearchBot' => ['Mozilla/5.0 (compatible; OAI-SearchBot/1.0)'],
            'ClaudeBot' => ['Mozilla/5.0 (compatible; ClaudeBot/1.0)'],
            'PerplexityBot' => ['Mozilla/5.0 (compatible; PerplexityBot/1.0)'],
        ];
    }

    /**
     * @dataProvider robotsServis
     */
    public function test_a_crawler_receives_the_prerendered_snapshot(string $userAgent): void
    {
        $this->preparerInstantane();

        $this->withHeaders(['User-Agent' => $userAgent])
            ->get('/'.self::CHEMIN_ESSAI)
            ->assertOk()
            ->assertHeader('X-Prerendered', '1')
            ->assertSee('Contenu pré-rendu', false);
    }

    /**
     * Un humain n'est jamais concerné : c'est la condition qui distingue le
     * rendu dynamique du cloaking assumé.
     */
    public function test_a_human_is_never_served_a_snapshot(): void
    {
        $this->preparerInstantane();

        $reponse = $this->withHeaders([
            'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 Chrome/120 Safari/537.36',
        ])->get('/'.self::CHEMIN_ESSAI);

        $reponse->assertHeaderMissing('X-Prerendered');
    }

    /** Une navigation Inertia ne doit jamais recevoir de HTML statique. */
    public function test_an_inertia_navigation_is_never_intercepted(): void
    {
        $this->preparerInstantane();

        $this->withHeaders([
            'User-Agent' => 'Mozilla/5.0 (compatible; Googlebot/2.1)',
            'X-Inertia' => 'true',
        ])->get('/'.self::CHEMIN_ESSAI)->assertHeaderMissing('X-Prerendered');
    }
}
