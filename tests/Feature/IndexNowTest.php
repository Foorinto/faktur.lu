<?php

namespace Tests\Feature;

use App\Models\BlogPost;
use App\Services\IndexNowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Notification IndexNow.
 *
 * Le protocole repose sur une clé publiée en clair sur le domaine : c'est cette
 * publication, et elle seule, qui prouve aux moteurs qu'on le contrôle. Si le
 * fichier disparaît, chaque envoi est refusé en 403 — silencieusement, puisque
 * rien dans l'interface n'en dépend.
 */
class IndexNowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['indexnow.enabled' => true, 'app.url' => 'https://faktur.lu', 'indexnow.notify_from_console' => true]);
        Http::preventStrayRequests();
        Http::fake(['api.indexnow.org/*' => Http::response('', 200)]);
    }

    /** Article minimal : il n'existe pas de factory pour ce modèle. */
    private function article(array $attributs = []): BlogPost
    {
        return BlogPost::create(array_merge([
            'locale' => 'fr',
            'title' => 'Un article',
            'slug' => 'un-article',
            'excerpt' => 'Résumé.',
            'content' => '<p>Contenu.</p>',
            'status' => 'published',
            'published_at' => now()->subMinute(),
        ], $attributs));
    }

    public function test_the_key_file_is_served_in_plain_text(): void
    {
        $cle = config('indexnow.key');

        $this->get("/{$cle}.txt")
            ->assertOk()
            ->assertHeader('Content-Type', 'text/plain; charset=UTF-8')
            ->assertSee($cle, false);
    }

    /** Toute autre adresse doit rester introuvable, sans révéler la clé. */
    public function test_a_wrong_key_is_not_found(): void
    {
        $this->get('/mauvaise-cle-quelconque.txt')->assertNotFound();
    }

    public function test_the_payload_carries_the_key_location(): void
    {
        app(IndexNowService::class)->submit(['https://faktur.lu/fr/tarifs']);

        Http::assertSent(function ($requete) {
            $corps = $requete->data();

            return $corps['host'] === 'faktur.lu'
                && $corps['key'] === config('indexnow.key')
                && $corps['keyLocation'] === 'https://faktur.lu/'.config('indexnow.key').'.txt'
                && $corps['urlList'] === ['https://faktur.lu/fr/tarifs'];
        });
    }

    /**
     * Une URL étrangère fait rejeter le lot ENTIER par l'API. On l'écarte donc
     * plutôt que de perdre l'envoi.
     */
    public function test_a_foreign_url_is_dropped_rather_than_sinking_the_batch(): void
    {
        app(IndexNowService::class)->submit([
            'https://faktur.lu/fr/tarifs',
            'https://exemple.com/piege',
        ]);

        Http::assertSent(fn ($r) => $r->data()['urlList'] === ['https://faktur.lu/fr/tarifs']);
    }

    public function test_nothing_is_sent_when_disabled(): void
    {
        config(['indexnow.enabled' => false]);

        $this->assertFalse(app(IndexNowService::class)->submit(['https://faktur.lu/fr']));

        Http::assertNothingSent();
    }

    /** Publier un article le signale ; un brouillon ne signale rien. */
    public function test_publishing_an_article_notifies_the_engines(): void
    {
        $this->article(['locale' => 'de', 'slug' => 'mein-artikel', 'status' => 'published', 'published_at' => now()->subMinute()]);

        Http::assertSent(fn ($r) => $r->data()['urlList'] === ['https://faktur.lu/de/blog/mein-artikel']);
    }

    /**
     * Consulter un article l'enregistre — `incrementViews()` est appelé à
     * chaque affichage. Sans ce filtre, chaque visite déclencherait un appel
     * HTTP et signalerait aux moteurs un changement qui n'en est pas un.
     */
    public function test_reading_an_article_notifies_nothing(): void
    {
        $post = $this->article();
        Http::fake(['api.indexnow.org/*' => Http::response('', 200)]);

        for ($i = 0; $i < 5; $i++) {
            $post->incrementViews();
        }

        Http::assertNothingSent();
    }

    /**
     * Une migration ou une commande qui réécrit les articles en lot ne doit pas
     * suspendre un déploiement sous deux cents appels HTTP.
     */
    public function test_a_console_run_notifies_nothing_by_default(): void
    {
        config(['indexnow.notify_from_console' => false]);

        $this->article(['slug' => 'reecrit-par-une-migration']);

        Http::assertNothingSent();
    }

    public function test_a_draft_notifies_nothing(): void
    {
        $this->article(['status' => 'draft', 'published_at' => null]);

        Http::assertNothingSent();
    }

    /**
     * Un envoi qui échoue ne doit jamais interrompre ce qui l'a déclenché :
     * publier est l'action de l'utilisateur, prévenir Bing n'en est que la
     * conséquence.
     */
    public function test_an_unreachable_api_does_not_break_publishing(): void
    {
        Http::fake(['api.indexnow.org/*' => fn () => throw new \RuntimeException('réseau coupé')]);

        $post = $this->article(['status' => 'published', 'published_at' => now()->subMinute()]);

        $this->assertDatabaseHas('blog_posts', ['id' => $post->id]);
    }
}
