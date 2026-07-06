<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * SEO / IA — ajoute un encart "En bref" (résumé AI-readable) en tête des articles
 * FR piliers + comparatif (striking distance). Objectif : maximiser les featured
 * snippets Google ET la citabilité par les IA (ChatGPT/Perplexity), qui privilégient
 * un résumé structuré en haut de page. Faits calqués sur l'audit blog (art. 63,
 * TVA 17/14/8/3, franchise 50 000 €, FAIA 2.01, conservation 10 ans, autoliq. art. 17/196).
 *
 * Idempotent (marqueur data-ai-summary), guardé, additif. Aucun impact code/route/DB-schema.
 * FR uniquement pour l'instant (à répliquer aux 4 autres langues si l'effet est positif).
 */
return new class extends Migration
{
    public function up(): void
    {
        $out = fn ($s) => print($s . "\n");

        $box = function (array $items): string {
            $lis = implode('', array_map(fn ($i) => '<li>' . $i . '</li>', $items));

            return '<div data-ai-summary class="bg-slate-50 border border-slate-200 rounded-xl p-5 my-4">'
                . '<p class="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-2">En bref</p>'
                . '<ul class="list-disc pl-5 space-y-1 text-slate-700 text-sm">' . $lis . '</ul>'
                . '</div>';
        };

        $summaries = [
            'guide-complet-facturation-luxembourg-2026' => $box([
                'Toute facture doit porter les <strong>mentions obligatoires de l\'article 63 LIVA</strong> (identité vendeur/client, n° de TVA, numéro séquentiel unique, taux et montants).',
                '<strong>4 taux de TVA</strong> au Luxembourg : 17 % (normal), 14 % (intermédiaire), 8 % (réduit), 3 % (super-réduit).',
                '<strong>Numérotation séquentielle continue</strong>, sans trou ni doublon.',
                '<strong>Conservation 10 ans</strong> ; le fichier <strong>FAIA 2.01</strong> est exigible par l\'AED lors d\'un contrôle.',
                '<strong>Franchise de TVA</strong> possible sous <strong>50 000 € HT/an</strong> (seuil en vigueur depuis 2025).',
            ]),
            'mentions-obligatoires-facture-luxembourg' => $box([
                'Les mentions obligatoires relèvent de l\'<strong>article 63 LIVA</strong> : identité et adresse des parties, numéros de TVA, date, <strong>numéro séquentiel unique</strong>, désignation, base HT, taux, montant de TVA, total TTC.',
                'Une facture <strong>non conforme</strong> peut être rejetée par le client et entraîner une <strong>amende AED (250 € à 10 000 €, art. 77 LIVA)</strong>.',
                'Mentions conditionnelles : <strong>autoliquidation</strong> (art. 17 LIVA / art. 196 directive), franchise, exonérations.',
                '<strong>Conservation 10 ans</strong> des factures et pièces comptables.',
            ]),
            'choisir-logiciel-facturation-luxembourg-comparatif' => $box([
                '<strong>6 critères</strong> pour choisir : conformité LU (FAIA 2.01, mentions art. 63, 4 taux de TVA, franchise 50 000 €), cycle complet (devis, avoirs, récurrentes), simplicité, prix tout compris, sécurité (hébergement UE, 2FA), intégration fiduciaire.',
                'Le <strong>filtre n°1</strong> : l\'export <strong>FAIA 2.01</strong>, exigible par l\'AED en contrôle — un outil qui ne le génère pas est éliminatoire.',
                'Attention aux prix d\'appel : comparez ce qui est <strong>réellement inclus</strong> (FAIA, Peppol, nombre de factures), pas seulement le tarif affiché.',
            ]),
        ];

        foreach ($summaries as $tk => $html) {
            $post = DB::table('blog_posts')->where('locale', 'fr')->where('translation_key', $tk)->first();
            if (! $post) { $out("  [$tk] ⚠️ article NON TROUVÉ"); continue; }
            if (str_contains($post->content, 'data-ai-summary')) { $out("  [$tk] ⏭ encart déjà présent"); continue; }

            $content = $html . "\n" . $post->content;

            // Correction d'une coquille du pilier au passage ("expliqué" -> "explique").
            $content = str_replace('ce guide vous expliqué tout', 'ce guide vous explique tout', $content);

            DB::table('blog_posts')->where('id', $post->id)->update([
                'content' => $content,
                'updated_at' => now(),
            ]);
            $out("  [$tk] ✅ encart En bref ajouté");
        }
    }

    public function down(): void {}
};
