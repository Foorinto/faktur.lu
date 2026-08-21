<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;

/**
 * Pages sectorielles, dans les cinq langues.
 *
 * Ces pages sont des INSTRUMENTS DE MESURE avant d'être du contenu. Elles
 * doivent dire si quelqu'un cherche une solution de facturation pour son métier
 * au Luxembourg, et ce qui lui coûte du temps — deux choses que personne ici ne
 * peut trancher autrement, faute de contact dans ces secteurs.
 *
 * ⚠️ Elles décrivent ce qui EXISTE. Aucune ne promet une nomenclature CNS ou un
 * devis de chantier : ces packs n'existent pas, et les annoncer serait mentir à
 * la personne même qu'on cherche à convaincre.
 *
 * La page freelance n'est pas ici : `/fr/pour-freelances` existe déjà et sert
 * de témoin. Sans point de comparaison, on ne saurait pas si quarante
 * impressions par mois sont beaucoup ou rien.
 */
class SectorPageController extends Controller
{
    /**
     * Chemin de la page dans chaque langue.
     *
     * L'URL est traduite en entier, métier compris : une page allemande servie
     * sous une adresse française se classe mal sur ce que les gens tapent
     * réellement, et l'adresse est l'un des rares signaux qu'un moteur lit
     * avant même d'avoir rendu la page. Même choix que `/a-propos`,
     * `/ueber-uns`, `/about`.
     *
     * @var array<string, string>
     */
    public const URL_PATTERNS = [
        'fr' => 'logiciel-facturation-{metier}-luxembourg',
        'de' => 'rechnungssoftware-{metier}-luxemburg',
        'en' => 'invoicing-software-{metier}-luxembourg',
        'lb' => 'rechnungssoftware-{metier}-letzebuerg',
        'pt' => 'software-faturacao-{metier}-luxemburgo',
    ];

    /**
     * Pages publiées, indexées par leur clé canonique.
     *
     * La clé sert deux fois : elle relie la page à ses traductions, sous
     * `sector_pages.<cle>`, et son `sector` doit figurer dans
     * `User::BUSINESS_SECTORS`, qui relie une réponse de formulaire au secteur
     * choisi à l'inscription.
     *
     * ⚠️ Les slugs doivent tenir en UN SEUL MOT, sans tiret, dans TOUTES les
     * langues.
     *
     * Le chemin est `…-{metier}-…`, et Symfony compile le paramètre en
     * `[^/]++` — un quantificateur POSSESSIF, qui ne revient jamais en arrière.
     * Un slug contenant un tiret avale donc le suffixe, et la route cesse de
     * correspondre. « agence-immobiliere » a échoué pour cette seule raison,
     * quand « artisan » passait. Le piège se reproduit à l'identique dans
     * chaque langue : « einzelhandel » passe, « einzel-handel » non.
     *
     * @var array<string, array{sector: string, slugs: array<string, string>}>
     */
    protected array $pages = [
        'infirmier' => [
            'sector' => 'health',
            'slugs' => [
                'fr' => 'infirmier',
                'de' => 'krankenpfleger',
                'en' => 'nurses',
                'lb' => 'fleegepersonal',
                'pt' => 'enfermeiro',
            ],
        ],
        'artisan' => [
            'sector' => 'construction',
            'slugs' => [
                'fr' => 'artisan',
                'de' => 'handwerker',
                'en' => 'tradesmen',
                'lb' => 'handwierker',
                'pt' => 'construcao',
            ],
        ],
        'immobilier' => [
            'sector' => 'real_estate',
            'slugs' => [
                'fr' => 'immobilier',
                'de' => 'immobilienmakler',
                'en' => 'realtors',
                'lb' => 'immobilien',
                'pt' => 'imobiliaria',
            ],
        ],
        'commerce' => [
            'sector' => 'retail',
            'slugs' => [
                'fr' => 'commerce',
                'de' => 'einzelhandel',
                'en' => 'retailers',
                'lb' => 'handel',
                'pt' => 'comercio',
            ],
        ],
        'restaurant' => [
            'sector' => 'hospitality',
            'slugs' => [
                'fr' => 'restaurant',
                'de' => 'gastronomie',
                'en' => 'restaurants',
                'lb' => 'restaurant',
                'pt' => 'restaurante',
            ],
        ],
    ];

    /**
     * Clés canoniques des pages publiées.
     *
     * Source unique partagée avec le sitemap, le pied de page et les tests,
     * pour qu'aucun ne puisse déclarer une URL qui n'existe pas. Le portugais
     * annonçait `/pt/contacto` sans route correspondante ; la leçon a coûté
     * onze pages introuvables dans Search Console.
     *
     * @return array<int, string>
     */
    public static function pageKeys(): array
    {
        return array_keys((new self())->pages);
    }

    /**
     * Conservé sous son ancien nom : le sitemap et plusieurs tests l'appellent.
     *
     * @return array<int, string>
     *
     * @deprecated Utiliser pageKeys(), qui dit ce que la valeur est réellement.
     */
    public static function pageSlugs(): array
    {
        return self::pageKeys();
    }

    /**
     * Slug d'URL d'un métier dans une langue donnée.
     */
    public static function slugFor(string $cle, string $locale): ?string
    {
        return (new self())->pages[$cle]['slugs'][$locale] ?? null;
    }

    /**
     * Chemin complet, langue comprise, tel qu'il apparaît dans le sitemap.
     */
    public static function pathFor(string $cle, string $locale): ?string
    {
        $slug = self::slugFor($cle, $locale);

        if ($slug === null || ! isset(self::URL_PATTERNS[$locale])) {
            return null;
        }

        return '/'.$locale.'/'.str_replace('{metier}', $slug, self::URL_PATTERNS[$locale]);
    }

    /**
     * Toutes les URL publiées, pour le sitemap et pour les tests.
     *
     * @return array<int, string>
     */
    public static function allPaths(): array
    {
        $chemins = [];

        foreach (array_keys(self::URL_PATTERNS) as $locale) {
            foreach (self::pageKeys() as $cle) {
                $chemins[] = self::pathFor($cle, $locale);
            }
        }

        return $chemins;
    }

    /**
     * Table des slugs, pour la comparer à celle que tient le JavaScript.
     *
     * @return array<string, array<string, string>>
     */
    public static function slugTable(): array
    {
        return array_map(fn (array $page) => $page['slugs'], (new self())->pages);
    }

    /**
     * Clé canonique correspondant à un chemin, s'il s'agit d'une page métier.
     *
     * Sert au changement de langue : `/de/rechnungssoftware-krankenpfleger-luxemburg`
     * doit mener à `/fr/logiciel-facturation-infirmier-luxembourg`, et non au
     * même chemin sous un autre préfixe — qui n'existe pas.
     *
     * @param  string  $chemin  Chemin SANS le préfixe de langue.
     */
    public static function keyFromPath(string $locale, string $chemin): ?string
    {
        $motif = self::URL_PATTERNS[$locale] ?? null;

        if ($motif === null) {
            return null;
        }

        $regex = '#^'.str_replace(preg_quote('{metier}', '#'), '([a-z]+)', preg_quote($motif, '#')).'$#';

        if (! preg_match($regex, trim($chemin, '/'), $trouve)) {
            return null;
        }

        return self::keyFromSlug($locale, $trouve[1]);
    }

    /**
     * Retrouve la clé canonique à partir du slug rencontré dans l'URL.
     *
     * Le slug est comparé dans la langue de l'URL SEULEMENT : sans cela,
     * `/de/rechnungssoftware-infirmier-luxemburg` répondrait 200 et créerait
     * un doublon de contenu sous une adresse que personne n'a publiée.
     */
    public static function keyFromSlug(string $locale, string $slug): ?string
    {
        foreach ((new self())->pages as $cle => $page) {
            if (($page['slugs'][$locale] ?? null) === $slug) {
                return $cle;
            }
        }

        return null;
    }

    public function show(string $locale, string $metier): Response
    {
        $cle = self::keyFromSlug($locale, $metier);

        abort_if($cle === null, 404);

        return Inertia::render('Sectors/Show', [
            'metier' => $cle,
            'sector' => $this->pages[$cle]['sector'],
        ]);
    }

}
