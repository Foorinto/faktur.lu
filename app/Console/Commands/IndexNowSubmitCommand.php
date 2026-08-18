<?php

namespace App\Console\Commands;

use App\Services\IndexNowService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

/**
 * Signale les URL du site aux moteurs qui honorent IndexNow.
 *
 * Deux usages :
 *  - sans argument, tout le sitemap — à lancer après un déploiement qui touche
 *    au contenu public ;
 *  - avec des URL, seulement celles-là.
 *
 * Les URL sont lues dans les sitemaps publiés plutôt que reconstruites : c'est
 * déjà la liste que le site déclare aux moteurs, et la dériver une seconde fois
 * garantirait qu'un jour les deux divergent.
 */
class IndexNowSubmitCommand extends Command
{
    protected $signature = 'indexnow:submit
                            {urls?* : URL précises à signaler (toutes celles du sitemap par défaut)}
                            {--dry-run : Afficher ce qui serait envoyé, sans rien envoyer}';

    protected $description = 'Signale les URL modifiées à Bing, Yandex et Seznam via IndexNow';

    public function handle(IndexNowService $indexNow): int
    {
        $urls = $this->argument('urls') ?: $this->urlsDuSitemap();

        if ($urls === []) {
            $this->error('Aucune URL à signaler.');

            return self::FAILURE;
        }

        $this->line(sprintf('  %d URL, clé publiée sur %s', count($urls), $indexNow->emplacementDeLaCle()));

        if ($this->option('dry-run')) {
            foreach (array_slice($urls, 0, 10) as $url) {
                $this->line('    '.$url);
            }

            if (count($urls) > 10) {
                $this->line(sprintf('    … et %d autres', count($urls) - 10));
            }

            return self::SUCCESS;
        }

        if (! config('indexnow.enabled')) {
            $this->warn('  IndexNow est désactivé sur cet environnement (INDEXNOW_ENABLED).');

            return self::SUCCESS;
        }

        if (! $indexNow->submit($urls)) {
            $this->error('  Envoi refusé ou impossible — voir le journal.');

            return self::FAILURE;
        }

        $this->info('  Envoyé.');

        return self::SUCCESS;
    }

    /**
     * URL déclarées par les sitemaps du site.
     *
     * @return array<int, string>
     */
    private function urlsDuSitemap(): array
    {
        $base = rtrim((string) config('app.url'), '/');
        $urls = [];

        foreach (['/sitemap-pages.xml', '/sitemap-blog.xml'] as $chemin) {
            try {
                $reponse = Http::timeout(20)->get($base.$chemin);
            } catch (\Throwable $e) {
                $this->warn("  {$chemin} injoignable : ".$e->getMessage());

                continue;
            }

            if (! $reponse->successful()) {
                $this->warn("  {$chemin} : HTTP ".$reponse->status());

                continue;
            }

            preg_match_all('#<loc>\s*(.*?)\s*</loc>#i', $reponse->body(), $trouvees);
            $urls = array_merge($urls, array_map('html_entity_decode', $trouvees[1]));
        }

        return array_values(array_unique($urls));
    }
}
