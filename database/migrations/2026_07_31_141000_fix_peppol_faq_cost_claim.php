<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Suite de la correction du claim Peppol : deux réponses de FAQ
 * promettaient encore la transmission comme disponible, alors que la
 * section principale venait d'être corrigée. Les laisser aurait rendu
 * l'article contradictoire avec lui-même.
 *
 * Français : « l'envoi Peppol est inclus dans votre abonnement (quota
 * selon le plan) ».
 * Anglais  : « The Essentiel plan includes 10 submissions/month and the
 * Pro plan offers unlimited submissions. »
 *
 * Ces chiffres correspondent bien au code (max_peppol_per_month : 10
 * puis illimité), mais décrivent une capacité dont le go-live est
 * toujours au backlog (FEAT-096). La réponse porte désormais sur ce qui
 * est réellement disponible : la génération du fichier, incluse sans
 * surcoût, et le réseau Peppol lui-même qui ne facture pas l'émetteur à
 * l'envoi.
 *
 * Les versions allemande et luxembourgeoise n'ont pas de FAQ (5 H2
 * seulement, dérive de traduction traitée séparément) et la version
 * portugaise ne pose pas cette question.
 */
return new class extends Migration
{
    private const KEY = 'peppol-b2g-luxembourg-guide-complet-2026';

    private const REPLACEMENTS = [
        'fr' => [
            "Avec faktur.lu, l'envoi Peppol est inclus dans votre abonnement (quota selon le plan).",
            "La génération du fichier Peppol BIS 3.0 est incluse dans votre abonnement faktur.lu, sans surcoût par facture. Le réseau Peppol lui-même ne facture pas l'émetteur : le coût éventuel dépend du point d'accès utilisé pour l'acheminement.",
        ],
        'en' => [
            'With faktur.lu, Peppol submission is included in your subscription. The Essentiel plan includes 10 submissions/month and the Pro plan offers unlimited submissions.',
            'Generating the Peppol BIS 3.0 file is included in your faktur.lu subscription, with no per-invoice surcharge. The Peppol network itself does not bill the sender: any cost depends on the access point used to route the document.',
        ],
    ];

    public function up(): void
    {
        foreach (self::REPLACEMENTS as $locale => [$old, $new]) {
            $post = DB::table('blog_posts')
                ->where('translation_key', self::KEY)
                ->where('locale', $locale)
                ->first(['id', 'content']);

            if (! $post || ! str_contains($post->content, $old)) {
                continue;
            }

            DB::table('blog_posts')->where('id', $post->id)->update([
                'content' => str_replace($old, $new, $post->content),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        // Contenu rédactionnel : la version antérieure annonçait des
        // quotas d'envoi pour une transmission non encore en production.
    }
};
