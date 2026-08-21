<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;

/**
 * Pages sectorielles — en français uniquement, comme les pages « alternative à ».
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
     * Pages publiées, indexées par slug d'URL.
     *
     * La clé `sector` doit figurer dans `User::BUSINESS_SECTORS` : c'est elle
     * qui relie une réponse de formulaire au secteur choisi à l'inscription.
     *
     * @var array<string, array{sector: string}>
     */
    protected array $pages = [
        'infirmier' => ['sector' => 'health'],
        'artisan' => ['sector' => 'construction'],
    ];

    /**
     * Slugs publiés — source unique partagée avec le sitemap, pour qu'il ne
     * puisse pas déclarer une URL qui n'existe pas. Le portugais annonçait
     * `/pt/contacto` sans route correspondante ; la leçon a coûté onze pages
     * introuvables dans Search Console.
     *
     * @return array<int, string>
     */
    public static function pageSlugs(): array
    {
        return array_keys((new self())->pages);
    }

    public function show(string $locale, string $metier): Response
    {
        abort_unless(isset($this->pages[$metier]), 404);

        return Inertia::render('Sectors/Show', [
            'metier' => $metier,
            'sector' => $this->pages[$metier]['sector'],
        ]);
    }
}
