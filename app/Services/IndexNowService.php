<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Notification des moteurs de recherche via le protocole IndexNow.
 *
 * Un sitemap dit « voici mes pages » et attend qu'on vienne les chercher.
 * IndexNow dit « celle-ci vient de changer » : l'exploration est déclenchée
 * plutôt que subie.
 *
 * ⚠️ Google n'y participe pas. Le protocole est porté par Microsoft et honoré
 * par Bing, Yandex, Seznam et Naver. Il ne remplace donc rien du travail
 * d'indexation Google — mais Bing est l'index sur lequel s'appuie la recherche
 * de ChatGPT, ce qui en fait un levier de visibilité auprès des IA.
 *
 * La clé n'est pas un secret : le protocole exige qu'elle soit publiée en clair
 * sur le domaine, et c'est cette publication qui fait office de preuve de
 * propriété.
 */
class IndexNowService
{
    /**
     * Signale une ou plusieurs URL comme modifiées.
     *
     * @param  array<int, string>  $urls  URL absolues, sur le domaine du site.
     * @return bool  Faux si rien n'a été envoyé — désactivé, ou aucune URL valable.
     */
    public function submit(array $urls): bool
    {
        if (! config('indexnow.enabled')) {
            return false;
        }

        $hote = parse_url(config('app.url'), PHP_URL_HOST);

        if (! $hote) {
            Log::warning('IndexNow : APP_URL ne contient pas d\'hôte exploitable.');

            return false;
        }

        // Une URL d'un autre domaine fait rejeter TOUT le lot par l'API : on
        // écarte donc les intruses plutôt que de perdre l'envoi entier.
        $urls = array_values(array_unique(array_filter(
            $urls,
            fn ($url) => is_string($url) && parse_url($url, PHP_URL_HOST) === $hote
        )));

        if ($urls === []) {
            return false;
        }

        $envoye = false;

        foreach (array_chunk($urls, (int) config('indexnow.batch_size', 500)) as $lot) {
            $envoye = $this->envoyerLot($hote, $lot) || $envoye;
        }

        return $envoye;
    }

    /**
     * Un envoi qui échoue ne doit jamais interrompre ce qui l'a déclenché.
     *
     * Publier un article reste l'action de l'utilisateur ; prévenir Bing n'en
     * est qu'une conséquence. Une API injoignable ne peut pas faire échouer une
     * publication — d'où le journal plutôt que l'exception.
     *
     * @param  array<int, string>  $urls
     */
    private function envoyerLot(string $hote, array $urls): bool
    {
        try {
            $reponse = Http::timeout(10)->acceptJson()->post(config('indexnow.endpoint'), [
                'host' => $hote,
                'key' => config('indexnow.key'),
                'keyLocation' => $this->emplacementDeLaCle(),
                'urlList' => $urls,
            ]);
        } catch (\Throwable $e) {
            Log::warning('IndexNow : envoi impossible.', ['erreur' => $e->getMessage(), 'urls' => count($urls)]);

            return false;
        }

        if ($reponse->successful()) {
            Log::info('IndexNow : '.count($urls).' URL signalée(s).');

            return true;
        }

        // 403 = clé introuvable ou invalide à l'adresse annoncée ; 422 = URL
        // hors du domaine déclaré. Les deux méritent d'être lisibles au journal,
        // parce qu'aucun des deux ne se voit autrement.
        Log::warning('IndexNow : refus du moteur.', [
            'statut' => $reponse->status(),
            'corps' => mb_substr($reponse->body(), 0, 300),
        ]);

        return false;
    }

    /** URL publique du fichier de clé, telle que l'API la vérifiera. */
    public function emplacementDeLaCle(): string
    {
        return rtrim(config('app.url'), '/').'/'.config('indexnow.key').'.txt';
    }
}
