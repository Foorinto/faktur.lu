<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * One-shot idempotent seeder to fix existing blog_posts content:
 *
 *   1. Update old "3 factures/mois" mentions to match the new Free plan limit (10/mois).
 *      The free plan was bumped to 10 invoices/month — blog content seeded before the
 *      change still cites the old number.
 *
 *   2. Fix accent-stripped French words in FR blog posts (e.g. "preparer" → "préparer").
 *      Some legacy content was seeded without diacritics.
 *
 * Safe to run multiple times: the pattern won't match on already-fixed content.
 */
class UpdateBlogContentFixesSeeder extends Seeder
{
    public function run(): void
    {
        $this->fixOldPlanLimits();
        $this->fixFrenchAccents();
    }

    /**
     * Update mentions of "3 factures/mois" → "10 factures/mois" across all locales.
     */
    protected function fixOldPlanLimits(): void
    {
        $replacements = [
            // FR
            '3 factures/mois' => '10 factures/mois',
            '3 factures / mois' => '10 factures / mois',
            '5 clients, 3 factures' => '10 clients, 10 factures',
            // EN
            '3 invoices/month' => '10 invoices/month',
            '3 invoices / month' => '10 invoices / month',
            '5 clients, 3 invoices' => '10 clients, 10 invoices',
            // DE
            '3 Rechnungen/Monat' => '10 Rechnungen/Monat',
            '3 Rechnungen / Monat' => '10 Rechnungen / Monat',
            '5 Kunden, 3 Rechnungen' => '10 Kunden, 10 Rechnungen',
            // LB
            '3 Rechnungen/Mount' => '10 Rechnungen/Mount',
            '5 Clienten, 3 Rechnungen' => '10 Clienten, 10 Rechnungen',
            // PT
            '3 faturas/mês' => '10 faturas/mês',
            '3 faturas / mês' => '10 faturas / mês',
            '5 clientes, 3 faturas' => '10 clientes, 10 faturas',
        ];

        $updatedTotal = 0;
        foreach ($replacements as $needle => $replacement) {
            $rows = DB::table('blog_posts')
                ->where('content', 'like', '%' . $needle . '%')
                ->select(['id', 'content'])
                ->get();

            foreach ($rows as $row) {
                $new = str_replace($needle, $replacement, $row->content);
                if ($new !== $row->content) {
                    DB::table('blog_posts')->where('id', $row->id)->update(['content' => $new]);
                    $updatedTotal++;
                }
            }
        }

        if ($updatedTotal > 0) {
            $this->command?->info("Plan limit fixes: {$updatedTotal} post(s) updated.");
        }
    }

    /**
     * Fix common missing accents in FR blog posts via word-boundary regex.
     * Only processes locale='fr' rows.
     */
    protected function fixFrenchAccents(): void
    {
        // Map of unaccented → accented form. Word-boundary regex is applied case-insensitively
        // (with preservation of original case).
        $accentMap = [
            'frequent' => 'fréquent',
            'frequente' => 'fréquente',
            'frequentes' => 'fréquentes',
            'frequents' => 'fréquents',
            'controle' => 'contrôle',
            'controles' => 'contrôles',
            'controler' => 'contrôler',
            'controlee' => 'contrôlée',
            'verifier' => 'vérifier',
            'verifie' => 'vérifié',
            'verifiez' => 'vérifiez',
            'verification' => 'vérification',
            'declarer' => 'déclarer',
            'declare' => 'déclaré',
            'declaration' => 'déclaration',
            'preparer' => 'préparer',
            'prepare' => 'préparé',
            'arriere' => 'arrière',
            'derniere' => 'dernière',
            'derniers' => 'derniers',
            'recente' => 'récente',
            'recent' => 'récent',
            'recents' => 'récents',
            'donnees' => 'données',
            'annee' => 'année',
            'annees' => 'années',
            'numero' => 'numéro',
            'numeros' => 'numéros',
            'etre' => 'être',
            'reglement' => 'règlement',
            'considere' => 'considéré',
            'consideree' => 'considérée',
            'economie' => 'économie',
            'economies' => 'économies',
            'experience' => 'expérience',
            'reussite' => 'réussite',
            'systeme' => 'système',
            'systemes' => 'systèmes',
            'methode' => 'méthode',
            'methodes' => 'méthodes',
            'reference' => 'référence',
            'references' => 'références',
            'caractere' => 'caractère',
            'caracteres' => 'caractères',
            'completer' => 'compléter',
            'completee' => 'complétée',
            'modifie' => 'modifié',
            'modifiee' => 'modifiée',
            'achete' => 'acheté',
            'genere' => 'généré',
            'generer' => 'générer',
            'enregistre' => 'enregistré',
            'specifique' => 'spécifique',
            'specifiques' => 'spécifiques',
            'integre' => 'intégré',
            'integree' => 'intégrée',
            'integrer' => 'intégrer',
        ];

        $posts = DB::table('blog_posts')
            ->where('locale', 'fr')
            ->select(['id', 'content', 'excerpt', 'title'])
            ->get();

        $updated = 0;

        foreach ($posts as $post) {
            $fields = ['content' => $post->content, 'excerpt' => $post->excerpt, 'title' => $post->title];
            $changed = false;

            foreach ($fields as $name => $value) {
                if ($value === null) continue;
                $new = $this->applyAccentFixes($value, $accentMap);
                if ($new !== $value) {
                    $fields[$name] = $new;
                    $changed = true;
                }
            }

            if ($changed) {
                DB::table('blog_posts')->where('id', $post->id)->update([
                    'content' => $fields['content'],
                    'excerpt' => $fields['excerpt'],
                    'title' => $fields['title'],
                ]);
                $updated++;
            }
        }

        if ($updated > 0) {
            $this->command?->info("FR accent fixes: {$updated} post(s) updated.");
        }
    }

    /**
     * Apply word-boundary case-preserving replacements.
     */
    protected function applyAccentFixes(string $text, array $map): string
    {
        foreach ($map as $bad => $good) {
            // Word-boundary case-insensitive match preserving the first-letter case of the original.
            $text = preg_replace_callback(
                '/\b(' . preg_quote($bad, '/') . ')\b/iu',
                function ($matches) use ($good) {
                    $original = $matches[1];
                    // Preserve case of first character only (covers "Annee" → "Année", "ANNEE" → "ANNÉE-ish" is rare)
                    if (mb_strtoupper(mb_substr($original, 0, 1)) === mb_substr($original, 0, 1)) {
                        return mb_strtoupper(mb_substr($good, 0, 1)) . mb_substr($good, 1);
                    }
                    return $good;
                },
                $text
            );
        }
        return $text;
    }
}
