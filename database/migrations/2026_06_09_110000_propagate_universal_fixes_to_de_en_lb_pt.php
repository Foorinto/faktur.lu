<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Propagation des corrections de l'audit FR aux 4 autres langues (DE/EN/LB/PT).
 *
 * Phase 1 : corrections UNIVERSELLES (str_replace sur motifs identiques dans toutes les langues).
 *   - Chiffres (seuils, tranches, taux)
 *   - Numéros d'articles de loi (LIVA, directive 2006/112/CE)
 *   - URLs, codes (schéma Peppol, format FAIA, dates)
 *
 * Phase 2 (migration suivante) : renames de slugs + redirects 301 pour les 8
 * articles « Article 21/61 LIVA » qui changent en « Article 17/63 LIVA ».
 *
 * Phase 3 (à venir si besoin) : traductions des mentions LIVA localisées
 * (ex. "TVA non applicable" → "MwSt nicht anwendbar", etc.).
 *
 * Idempotent : str_replace silencieux sur contenu déjà corrigé.
 */
return new class extends Migration
{
    /**
     * Liste des remplacements universels.
     * Format : [pattern à chercher => remplacement]
     *
     * Ces patterns sont valides dans TOUTES les langues car les chiffres et
     * numéros d'articles de loi sont identiques quelle que soit la langue.
     */
    private const UNIVERSAL_REPLACEMENTS = [
        // === SEUIL FRANCHISE TVA 35 → 50 k€ ===
        '35 000 EUR' => '50 000 EUR',
        '35 000 €' => '50 000 €',
        '35.000 EUR' => '50.000 EUR',
        '35.000 €' => '50.000 €',
        '35,000 EUR' => '50,000 EUR',
        '35,000 €' => '50,000 €',

        // === RÉFÉRENCES D'ARTICLES DE LOI (LIVA) ===
        // Franchise : 56ter/60bis → 57bis (Luxembourg)
        'Article 56 ter' => 'Article 57bis',
        'Article 56ter' => 'Article 57bis',
        'Artikel 56 ter' => 'Artikel 57bis',
        'Artikel 56ter' => 'Artikel 57bis',
        'Artigo 56 ter' => 'Artigo 57bis',
        'Artigo 56ter' => 'Artigo 57bis',
        'art. 56 ter' => 'art. 57bis',
        'art. 56ter' => 'art. 57bis',
        'Article 60bis' => 'Article 57bis',
        'Artikel 60bis' => 'Artikel 57bis',
        'Artigo 60bis' => 'Artigo 57bis',
        'art. 60bis' => 'art. 57bis',
        'article 60bis' => 'article 57bis',

        // Autoliquidation : Article 44 directive → Article 196 directive
        // (pour la mention obligatoire ; art. 44 = lieu d'imposition, art. 196 = redevable)
        'Autoliquidation - Article 44 de la directive 2006/112/CE'
            => 'Autoliquidation — Article 196 de la directive 2006/112/CE',
        'Autoliquidation, Article 44 de la directive 2006/112/CE'
            => 'Autoliquidation — Article 196 de la directive 2006/112/CE',
        'Reverse Charge - Article 44 of Directive 2006/112/EC'
            => 'Reverse Charge — Article 196 of Directive 2006/112/EC',
        'Reverse-Charge - Artikel 44 der Richtlinie 2006/112/EG'
            => 'Reverse-Charge — Artikel 196 der Richtlinie 2006/112/EG',
        'Inversão do sujeito passivo - Artigo 44.º da Diretiva 2006/112/CE'
            => 'Autoliquidação — Artigo 196.º da Diretiva 2006/112/CE',

        // === SCHÉMA PEPPOL (universel) ===
        'schéma 0184' => 'schéma 9938',
        'scheme 0184' => 'scheme 9938',
        'Schema 0184' => 'Schema 9938',
        'esquema 0184' => 'esquema 9938',
        '0184)' => '9938)',

        // === ViDA — Date précise (1er juillet 2030 / 2030-07-01) ===
        // Le pattern "2028-2030" → "1er juillet 2030" / "1 July 2030" / etc.
        'à partir de 2028-2030' => 'à compter du 1er juillet 2030',
        'à partir de 2028' => 'à compter du 1er juillet 2030',
        'starting in 2028-2030' => 'starting on 1 July 2030',
        'starting in 2028' => 'starting on 1 July 2030',
        'starting from 2028' => 'starting on 1 July 2030',
        'ab 2028-2030' => 'ab dem 1. Juli 2030',
        'ab 2028' => 'ab dem 1. Juli 2030',
        'a partir de 2028-2030' => 'a partir de 1 de julho de 2030',
        'a partir de 2028' => 'a partir de 1 de julho de 2030',
        'ab 2028' => 'ab dem 1. Juli 2030',

        // === FAIA — Version 2.01 publiée en 2020 (et non 2022) ===
        'FAIA_v2.01_2022.xsd' => 'FAIA_v2.01.xsd',

        // === PEPPOL — pays (« plus de 35 » → 100+) ===
        'plus de 35 pays' => 'plus de 100 pays',
        'more than 35 countries' => 'more than 100 countries',
        'in mehr als 35 Ländern' => 'in mehr als 100 Ländern',
        'em mais de 35 países' => 'em mais de 100 países',

        // === SANCTIONS AED inventées « 10 % à 50 % » / « jusqu'à 200 % » ===
        // → Barème officiel 250-10000 € + référence art. 77/80 LIVA
        'pénalités (10 % à 50 %)' => 'amendes administratives 250 € à 10 000 € (art. 77 LIVA)',
        'pénalités (10% à 50%)' => 'amendes administratives 250 € à 10 000 € (art. 77 LIVA)',
        'penalties (10% to 50%)' => 'administrative fines from €250 to €10,000 (Art. 77 LIVA)',
        'Strafen (10 % bis 50 %)' => 'Verwaltungsstrafen 250 € bis 10 000 € (Art. 77 LIVA)',
        'penalidades (10% a 50%)' => 'multas administrativas de 250 € a 10 000 € (art. 77 LIVA)',

        // === Tribunal administratif → Tribunal d'arrondissement (juridiction TVA) ===
        'Tribunal administratif dans les 3 mois' => 'Tribunal d\'arrondissement dans les 3 mois',
        'Administrative Court within 3 months' => 'District Court (Tribunal d\'arrondissement) within 3 months',
        'Verwaltungsgericht innerhalb von 3 Monaten' => 'Bezirksgericht (Tribunal d\'arrondissement) innerhalb von 3 Monaten',
        'Tribunal Administrativo no prazo de 3 meses' => 'Tribunal d\'arrondissement no prazo de 3 meses',

        // === Délai contestation 30 jours → 3 mois ===
        'délai de 30 jours pour contester' => 'délai de 3 mois pour contester',
        '30-day deadline to challenge' => '3-month deadline to challenge',
        '30-Tage-Frist zur Anfechtung' => '3-Monats-Frist zur Anfechtung',
        'prazo de 30 dias para contestar' => 'prazo de 3 meses para contestar',

        // === BE — Cotisations INASTI 2026 (universel numérique) ===
        '73 447,52' => '75 024,54',
        '73 447.52' => '75 024,54',
        '73,447.52' => '75,024.54',
        '108 238,40' => '110 562,42',
        '108 238.40' => '110 562,42',
        '108,238.40' => '110,562.42',

        // === Taux intérêt retard ~12% → ~10% (BCE 2,15% + 8) ===
        'environ 12% en 2026' => 'environ 10 % en 2026 (BCE 2,15 + 8 points)',
        'environ 12,5% en 2026' => 'environ 10 % en 2026 (BCE 2,15 + 8 points)',
        'around 12% in 2026' => 'around 10% in 2026 (ECB 2.15 + 8 points)',
        'around 12.5% in 2026' => 'around 10% in 2026 (ECB 2.15 + 8 points)',
        'etwa 12% im Jahr 2026' => 'etwa 10 % im Jahr 2026 (EZB 2,15 + 8 Punkte)',
        'etwa 12,5% im Jahr 2026' => 'etwa 10 % im Jahr 2026 (EZB 2,15 + 8 Punkte)',
        'cerca de 12% em 2026' => 'cerca de 10 % em 2026 (BCE 2,15 + 8 pontos)',
        'cerca de 12,5% em 2026' => 'cerca de 10 % em 2026 (BCE 2,15 + 8 pontos)',
    ];

    public function up(): void
    {
        $locales = ['de', 'en', 'lb', 'pt'];
        $totalUpdated = 0;
        $statsByLocale = [];

        foreach ($locales as $locale) {
            $statsByLocale[$locale] = 0;
            $posts = DB::table('blog_posts')
                ->where('locale', $locale)
                ->get(['id', 'slug', 'content']);

            foreach ($posts as $post) {
                $newContent = str_replace(
                    array_keys(self::UNIVERSAL_REPLACEMENTS),
                    array_values(self::UNIVERSAL_REPLACEMENTS),
                    $post->content
                );

                if ($newContent !== $post->content) {
                    DB::table('blog_posts')->where('id', $post->id)->update([
                        'content' => $newContent,
                        'updated_at' => now(),
                    ]);
                    $statsByLocale[$locale]++;
                    $totalUpdated++;
                }
            }
        }

        foreach ($statsByLocale as $loc => $n) {
            echo "  → [$loc] {$n} article(s) corrigé(s)" . PHP_EOL;
        }
        echo "  → Total : {$totalUpdated} article(s) modifié(s) sur 4 langues" . PHP_EOL;
    }

    public function down(): void
    {
        // Pas de rollback.
    }
};
