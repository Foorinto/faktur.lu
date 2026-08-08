<?php

namespace Tests\Feature;

use App\Http\Middleware\ServePrerendered;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Reconnaissance des robots par `ServePrerendered`.
 *
 * Le site est une application monopage : le serveur renvoie une coquille et le
 * navigateur construit la page. Les robots reconnus reçoivent à la place un
 * snapshot HTML complet. Un robot NON reconnu indexe donc un document sans
 * titre ni contenu, ce qui est pire que de ne pas être indexé du tout.
 *
 * Constaté le 2026-08-08 en production : meta-externalagent, le robot de Meta
 * AI, recevait « faktur.lu » comme titre. D'où l'élargissement de la liste et
 * ce test.
 *
 * Le sens inverse compte tout autant : un navigateur pris pour un robot
 * recevrait un HTML figé au lieu de l'application. Les deux jeux sont donc
 * vérifiés.
 */
class BotDetectionTest extends TestCase
{
    /**
     * Passe par `shouldServeSnapshot`, la méthode réellement en service, plutôt
     * que par la seule expression régulière : elle vérifie aussi la méthode
     * HTTP et écarte les navigations Inertia. Tester la vraie porte d'entrée
     * vaut mieux que tester un morceau de sa serrure.
     */
    private function estRobot(string $userAgent): bool
    {
        $requete = \Illuminate\Http\Request::create('/fr', 'GET');
        $requete->headers->set('User-Agent', $userAgent);

        $methode = new \ReflectionMethod(ServePrerendered::class, 'shouldServeSnapshot');
        $methode->setAccessible(true);

        return $methode->invoke(new ServePrerendered(), $requete);
    }

    /** @return array<string, array<int, string>> */
    public static function robots(): array
    {
        $agents = [
            'Googlebot' => 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)',
            'Bingbot' => 'Mozilla/5.0 (compatible; bingbot/2.0; +http://www.bing.com/bingbot.htm)',
            'GPTBot' => 'Mozilla/5.0 (compatible; GPTBot/1.0; +https://openai.com/gptbot)',
            'ClaudeBot' => 'Mozilla/5.0 (compatible; ClaudeBot/1.0; +claudebot@anthropic.com)',
            'PerplexityBot' => 'Mozilla/5.0 (compatible; PerplexityBot/1.0)',
            // Ajoutés le 2026-08-08.
            'Meta AI' => 'meta-externalagent/1.1 (+https://developers.facebook.com/docs/sharing/webmasters/crawler)',
            'Meta fetcher' => 'meta-externalfetcher/1.1',
            'DuckDuckGo AI' => 'Mozilla/5.0 (compatible; DuckAssistBot/1.0)',
            'You.com' => 'Mozilla/5.0 (compatible; YouBot/1.0)',
            'Allen Institute' => 'Mozilla/5.0 (compatible; Ai2Bot/1.0)',
            'Diffbot' => 'Mozilla/5.0 (compatible; Diffbot/0.1)',
            'Vertex AI' => 'Mozilla/5.0 (compatible; Google-CloudVertexBot/1.0)',
        ];

        return array_map(fn ($ua) => [$ua], $agents);
    }

    /** @return array<string, array<int, string>> */
    public static function humains(): array
    {
        $agents = [
            'Chrome macOS' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0 Safari/537.36',
            'Safari iPhone' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Mobile/15E148 Safari/604.1',
            'Firefox Windows' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:121.0) Gecko/20100101 Firefox/121.0',
            'Edge' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0 Safari/537.36 Edg/120.0',
            'Chrome Android' => 'Mozilla/5.0 (Linux; Android 14) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0 Mobile Safari/537.36',
        ];

        return array_map(fn ($ua) => [$ua], $agents);
    }

    #[DataProvider('robots')]
    public function test_les_robots_sont_reconnus(string $userAgent): void
    {
        $this->assertTrue(
            $this->estRobot($userAgent),
            "Non reconnu, ce robot indexerait un document sans titre ni contenu : {$userAgent}"
        );
    }

    #[DataProvider('humains')]
    public function test_les_navigateurs_ne_sont_pas_pris_pour_des_robots(string $userAgent): void
    {
        $this->assertFalse(
            $this->estRobot($userAgent),
            "Pris pour un robot, ce navigateur recevrait un HTML figé au lieu de l'application : {$userAgent}"
        );
    }

    public function test_un_agent_vide_n_est_pas_un_robot(): void
    {
        $this->assertFalse($this->estRobot(''));
    }
}
