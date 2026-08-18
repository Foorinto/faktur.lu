<?php

namespace App\Observers;

use App\Models\BlogPost;
use App\Services\IndexNowService;

/**
 * Signale un article aux moteurs dès qu'il devient public.
 *
 * Un sitemap attend d'être relu ; IndexNow prévient. Sur un blog qui publie
 * quelques articles par mois, la différence se compte en jours d'indexation.
 *
 * ⚠️ Google n'honore pas ce protocole : la notification vaut pour Bing, Yandex
 * et Seznam. Elle ne remplace donc pas le sitemap, elle le double sur les
 * moteurs qui l'acceptent.
 */
class BlogPostObserver
{
    public function __construct(private readonly IndexNowService $indexNow) {}

    public function saved(BlogPost $post): void
    {
        if (! $this->estPublic($post)) {
            return;
        }

        // On ne notifie qu'au moment où quelque chose de visible a bougé : une
        // correction de faute rendrait sinon un signal aussi fréquent
        // qu'insignifiant, et les moteurs plafonnent les envois.
        if (! $post->wasChanged(['status', 'published_at', 'title', 'slug', 'content', 'excerpt'])
            && ! $post->wasRecentlyCreated) {
            return;
        }

        $this->indexNow->submit([$this->url($post)]);
    }

    private function estPublic(BlogPost $post): bool
    {
        return $post->status === 'published'
            && $post->published_at !== null
            && $post->published_at->lessThanOrEqualTo(now());
    }

    /**
     * L'URL publique de l'article, dans sa langue.
     *
     * Construite ici plutôt que par le générateur de routes : celui-ci dépend
     * de la langue courante de la requête, or un article peut être enregistré
     * depuis une interface d'administration servie dans une autre langue.
     */
    private function url(BlogPost $post): string
    {
        return sprintf('%s/%s/blog/%s', rtrim((string) config('app.url'), '/'), $post->locale, $post->slug);
    }
}
