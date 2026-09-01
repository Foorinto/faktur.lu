<?php

namespace App\Console\Commands;

use App\Models\BlogPost;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Remplace l'ancien nom de marque dans le contenu éditorial en base.
 *
 * Le nom apparaît 461 fois dans les 181 articles du blog. Ce contenu vit en
 * base, pas dans le code : ni la configuration ni un déploiement ne le
 * changent.
 *
 * ⚠️ LE PIÈGE, et la raison d'être de cette commande plutôt que d'un
 * chercher-remplacer : « fakturieren », « Fakturatioun », « fakturéieren »,
 * « Fakturierung » sont des mots ALLEMANDS et LUXEMBOURGEOIS qui signifient
 * facturer. Remplacer la chaîne « faktur » les massacrerait dans deux des cinq
 * langues du site.
 *
 * Vérifié le 2026-09-01 sur le contenu réel : la marque n'apparaît JAMAIS
 * seule, toujours sous la forme « faktur.lu ». Le remplacement porte donc sur
 * cette forme exacte, et sur elle seule.
 */
class RenommerLaMarqueCommand extends Command
{
    protected $signature = 'marque:renommer
        {nouveau : le nouveau nom, par exemple kolux.lu}
        {--ancien=faktur.lu : le nom à remplacer}
        {--reel : écrire réellement en base ; sans cette option, rien n\'est modifié}';

    protected $description = "Remplace l'ancien nom de marque dans les articles du blog";

    /** Champs susceptibles de porter le nom. */
    private const CHAMPS = ['title', 'excerpt', 'content', 'meta_title', 'meta_description'];

    public function handle(): int
    {
        $ancien = (string) $this->option('ancien');
        $nouveau = (string) $this->argument('nouveau');
        $reel = (bool) $this->option('reel');

        if ($ancien === '' || $nouveau === '' || $ancien === $nouveau) {
            $this->error('Les deux noms doivent être renseignés et différents.');

            return self::FAILURE;
        }

        $colonnes = array_values(array_filter(
            self::CHAMPS,
            fn ($champ) => \Schema::hasColumn('blog_posts', $champ)
        ));

        $articles = 0;
        $occurrences = 0;

        BlogPost::query()->orderBy('id')->chunkById(100, function ($lot) use (
            $colonnes, $ancien, $nouveau, $reel, &$articles, &$occurrences
        ) {
            foreach ($lot as $article) {
                $modifications = [];

                foreach ($colonnes as $colonne) {
                    $valeur = $article->{$colonne};

                    if (! is_string($valeur) || $valeur === '') {
                        continue;
                    }

                    $remplace = $this->remplacer($valeur, $ancien, $nouveau, $compte);

                    if ($compte > 0) {
                        $modifications[$colonne] = $remplace;
                        $occurrences += $compte;
                    }
                }

                if ($modifications === []) {
                    continue;
                }

                $articles++;

                if ($reel) {
                    DB::table('blog_posts')->where('id', $article->id)->update($modifications);
                }
            }
        });

        $this->line(sprintf(
            '  %s : %d occurrence(s) dans %d article(s)',
            $reel ? 'Remplacé' : 'À remplacer (essai à blanc)',
            $occurrences,
            $articles
        ));

        if (! $reel) {
            $this->comment('  Rien n\'a été modifié. Relancez avec --reel pour écrire.');
        }

        return self::SUCCESS;
    }

    /**
     * Remplace le nom, et lui seul.
     *
     * La casse initiale est respectée : une phrase commençant par « Faktur.lu »
     * doit continuer de commencer par une majuscule.
     */
    private function remplacer(string $texte, string $ancien, string $nouveau, ?int &$compte = null): string
    {
        $motif = '/(?<![\p{L}\-])'.preg_quote($ancien, '/').'/iu';

        return preg_replace_callback(
            $motif,
            function ($correspondance) use ($nouveau) {
                $trouve = $correspondance[0];

                // « Faktur.lu » en début de phrase reste capitalisé.
                return ctype_upper(mb_substr($trouve, 0, 1))
                    ? mb_strtoupper(mb_substr($nouveau, 0, 1)).mb_substr($nouveau, 1)
                    : $nouveau;
            },
            $texte,
            -1,
            $compte
        );
    }
}
