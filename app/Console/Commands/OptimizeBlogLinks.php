<?php

namespace App\Console\Commands;

use App\Models\BlogPost;
use Illuminate\Console\Command;

class OptimizeBlogLinks extends Command
{
    protected $signature = 'blog:optimize-links';
    protected $description = 'Ajoute des liens internes et mentions produit subtiles aux articles du blog';

    protected array $callouts = [
        'faia-luxembourg-fichier-audit-informatise-guide' => [
            'after' => 'format FAIA 2.01',
            'html' => '<div class="bg-emerald-50 border border-emerald-200 rounded-xl p-5 my-6"><p class="text-sm text-emerald-800"><strong>Bon à savoir :</strong> Avec faktur.lu, votre fichier FAIA est généré automatiquement à chaque facture. Vous pouvez aussi le vérifier gratuitement avec notre <a href="/fr/validateur-faia" class="text-emerald-600 underline hover:text-emerald-700">validateur FAIA en ligne</a>.</p></div>',
        ],
        'tva-luxembourg-taux-calcul-obligations' => [
            'after' => 'taux normal de 17%',
            'html' => '<div class="bg-emerald-50 border border-emerald-200 rounded-xl p-5 my-6"><p class="text-sm text-emerald-800"><strong>Bon à savoir :</strong> faktur.lu applique automatiquement le bon taux de TVA selon le type de client (national, intracommunautaire, hors UE). Plus besoin de calculer manuellement.</p></div>',
        ],
        'freelance-luxembourg-facturer-conformite' => [
            'after' => 'mentions obligatoires',
            'html' => '<div class="bg-emerald-50 border border-emerald-200 rounded-xl p-5 my-6"><p class="text-sm text-emerald-800"><strong>Bon à savoir :</strong> En tant que freelance, faktur.lu gère pour vous la numérotation séquentielle, les mentions légales et l\'export FAIA. <a href="/fr/tarifs" class="text-emerald-600 underline hover:text-emerald-700">À partir de 5€/mois</a>.</p></div>',
        ],
        'mentions-obligatoires-facture-luxembourg' => [
            'after' => 'numéro de TVA',
            'html' => '<div class="bg-emerald-50 border border-emerald-200 rounded-xl p-5 my-6"><p class="text-sm text-emerald-800"><strong>Bon à savoir :</strong> Avec un logiciel de facturation comme faktur.lu, toutes les mentions obligatoires sont ajoutées automatiquement à vos factures. Aucun risque d\'oubli.</p></div>',
        ],
        'guide-complet-facturation-luxembourg-2026' => [
            'after' => 'règles de facturation',
            'html' => '<div class="bg-emerald-50 border border-emerald-200 rounded-xl p-5 my-6"><p class="text-sm text-emerald-800"><strong>Bon à savoir :</strong> faktur.lu est conçu spécifiquement pour respecter toutes les règles de facturation luxembourgeoises : numérotation, mentions légales, TVA, FAIA. <a href="/fr" class="text-emerald-600 underline hover:text-emerald-700">Essayez gratuitement</a>.</p></div>',
        ],
        'peppol-b2g-luxembourg-guide-complet-2026' => [
            'after' => 'Peppol',
            'html' => '<div class="bg-emerald-50 border border-emerald-200 rounded-xl p-5 my-6"><p class="text-sm text-emerald-800"><strong>Bon à savoir :</strong> faktur.lu permet d\'envoyer vos factures via le réseau Peppol directement depuis l\'application, sans configuration technique.</p></div>',
        ],
        'excel-vs-logiciel-facturation-pourquoi-switch' => [
            'after' => 'logiciel de facturation',
            'html' => '<div class="bg-emerald-50 border border-emerald-200 rounded-xl p-5 my-6"><p class="text-sm text-emerald-800"><strong>Prêt à passer le cap ?</strong> faktur.lu est le logiciel de facturation le plus abordable au Luxembourg (à partir de 5€/mois), avec export FAIA automatique et conformité garantie. <a href="/fr" class="text-emerald-600 underline hover:text-emerald-700">Essai gratuit 14 jours</a>.</p></div>',
        ],
        'controle-fiscal-luxembourg-comment-preparer' => [
            'after' => 'contrôle fiscal',
            'html' => '<div class="bg-emerald-50 border border-emerald-200 rounded-xl p-5 my-6"><p class="text-sm text-emerald-800"><strong>Bon à savoir :</strong> Avec faktur.lu, votre fichier FAIA est toujours prêt pour un contrôle. Vérifiez-le à tout moment avec notre <a href="/fr/validateur-faia" class="text-emerald-600 underline hover:text-emerald-700">validateur FAIA gratuit</a>.</p></div>',
        ],
        'archivage-factures-luxembourg-duree-legale-format' => [
            'after' => 'archivage',
            'html' => '<div class="bg-emerald-50 border border-emerald-200 rounded-xl p-5 my-6"><p class="text-sm text-emerald-800"><strong>Bon à savoir :</strong> Le plan Pro de faktur.lu inclut l\'archivage PDF/A de vos factures, conforme aux exigences de conservation sur 10 ans.</p></div>',
        ],
        'factur-x-zugferd-facturation-electronique-europeenne' => [
            'after' => 'Factur-X',
            'html' => '<div class="bg-emerald-50 border border-emerald-200 rounded-xl p-5 my-6"><p class="text-sm text-emerald-800"><strong>Bon à savoir :</strong> faktur.lu génère des factures au format Factur-X / ZUGFeRD, facilitant vos échanges avec des clients en France et en Allemagne.</p></div>',
        ],
        'tva-intracommunautaire-guide-entreprises-luxembourgeoises' => [
            'after' => 'intracommunautaire',
            'html' => '<div class="bg-emerald-50 border border-emerald-200 rounded-xl p-5 my-6"><p class="text-sm text-emerald-800"><strong>Bon à savoir :</strong> faktur.lu détecte automatiquement le scénario TVA (national, intracommunautaire, hors UE) et applique les bonnes mentions sur vos factures.</p></div>',
        ],
        'automatiser-facturation-7-conseils-gagner-temps' => [
            'after' => 'automatiser',
            'html' => '<div class="bg-emerald-50 border border-emerald-200 rounded-xl p-5 my-6"><p class="text-sm text-emerald-800"><strong>Bon à savoir :</strong> faktur.lu automatise la numérotation, le calcul TVA, l\'export FAIA et les relances de paiement. <a href="/fr" class="text-emerald-600 underline hover:text-emerald-700">Testez gratuitement pendant 14 jours</a>.</p></div>',
        ],
    ];

    protected array $internalLinks = [
        'faia-luxembourg-fichier-audit-informatise-guide' => [
            ['slug' => 'controle-fiscal-luxembourg-comment-preparer', 'anchor' => 'contrôle fiscal'],
            ['slug' => 'archivage-factures-luxembourg-duree-legale-format', 'anchor' => 'archivage des factures'],
        ],
        'tva-luxembourg-taux-calcul-obligations' => [
            ['slug' => 'tva-intracommunautaire-guide-entreprises-luxembourgeoises', 'anchor' => 'TVA intracommunautaire'],
            ['slug' => 'franchise-tva-luxembourg-seuil-obligations-regime-normal', 'anchor' => 'franchise de TVA'],
        ],
        'freelance-luxembourg-facturer-conformite' => [
            ['slug' => 'mentions-obligatoires-facture-luxembourg', 'anchor' => 'mentions obligatoires'],
            ['slug' => 'faia-luxembourg-fichier-audit-informatise-guide', 'anchor' => 'fichier FAIA'],
            ['slug' => '5-erreurs-frequentes-facture-freelance-luxembourg', 'anchor' => '5 erreurs fréquentes'],
        ],
        'guide-complet-facturation-luxembourg-2026' => [
            ['slug' => 'tva-luxembourg-taux-calcul-obligations', 'anchor' => 'TVA au Luxembourg'],
            ['slug' => 'mentions-obligatoires-facture-luxembourg', 'anchor' => 'mentions obligatoires'],
            ['slug' => 'faia-luxembourg-fichier-audit-informatise-guide', 'anchor' => 'export FAIA'],
        ],
        'controle-fiscal-luxembourg-comment-preparer' => [
            ['slug' => 'faia-luxembourg-fichier-audit-informatise-guide', 'anchor' => 'fichier FAIA'],
            ['slug' => 'archivage-factures-luxembourg-duree-legale-format', 'anchor' => 'archivage légal'],
        ],
        'excel-vs-logiciel-facturation-pourquoi-switch' => [
            ['slug' => 'automatiser-facturation-7-conseils-gagner-temps', 'anchor' => 'automatiser votre facturation'],
            ['slug' => 'choisir-logiciel-facturation-luxembourg-comparatif', 'anchor' => 'choisir le bon logiciel'],
        ],
        'peppol-b2g-luxembourg-guide-complet-2026' => [
            ['slug' => 'factur-x-zugferd-facturation-electronique-europeenne', 'anchor' => 'Factur-X / ZUGFeRD'],
        ],
        'factur-x-zugferd-facturation-electronique-europeenne' => [
            ['slug' => 'peppol-b2g-luxembourg-guide-complet-2026', 'anchor' => 'réseau Peppol'],
            ['slug' => 'facturer-etranger-depuis-luxembourg', 'anchor' => 'facturer à l\'étranger'],
        ],
        '5-erreurs-frequentes-facture-freelance-luxembourg' => [
            ['slug' => 'freelance-luxembourg-facturer-conformite', 'anchor' => 'guide freelance'],
            ['slug' => 'mentions-obligatoires-facture-luxembourg', 'anchor' => 'mentions obligatoires'],
        ],
        'relancer-client-impaye-luxembourg' => [
            ['slug' => 'delais-paiement-luxembourg-cadre-legal-2026', 'anchor' => 'délais de paiement légaux'],
        ],
        'note-de-credit-luxembourg-comment-etablir' => [
            ['slug' => 'guide-complet-facturation-luxembourg-2026', 'anchor' => 'guide complet de facturation'],
        ],
        'franchise-tva-luxembourg-seuil-obligations-regime-normal' => [
            ['slug' => 'tva-luxembourg-taux-calcul-obligations', 'anchor' => 'taux de TVA au Luxembourg'],
            ['slug' => 'freelance-luxembourg-facturer-conformite', 'anchor' => 'facturer en tant que freelance'],
        ],
    ];

    public function handle(): int
    {
        $updated = 0;
        $skipped = 0;

        // Add callout boxes
        foreach ($this->callouts as $slug => $callout) {
            $post = BlogPost::where('slug', $slug)->first();

            if (!$post) {
                $this->warn("Article non trouvé : {$slug}");
                $skipped++;
                continue;
            }

            // Skip if already has callout
            if (str_contains($post->content, 'Bon à savoir') || str_contains($post->content, 'Prêt à passer le cap')) {
                $this->line("  Déjà optimisé : {$slug}");
                $skipped++;
                continue;
            }

            $content = $post->content;

            // Insert callout after first occurrence of keyword
            $pos = stripos($content, $callout['after']);
            if ($pos !== false) {
                // Find end of current paragraph
                $endP = strpos($content, '</p>', $pos);
                if ($endP !== false) {
                    $insertPos = $endP + 4;
                    $content = substr($content, 0, $insertPos) . "\n" . $callout['html'] . "\n" . substr($content, $insertPos);
                }
            } else {
                // Fallback: insert after first <h2> section
                $h2Pos = strpos($content, '</h2>');
                if ($h2Pos !== false) {
                    $endP = strpos($content, '</p>', $h2Pos);
                    if ($endP !== false) {
                        $insertPos = $endP + 4;
                        $content = substr($content, 0, $insertPos) . "\n" . $callout['html'] . "\n" . substr($content, $insertPos);
                    }
                }
            }

            $post->update(['content' => $content]);
            $this->info("Callout ajouté : {$slug}");
            $updated++;
        }

        // Add internal links section
        foreach ($this->internalLinks as $slug => $links) {
            $post = BlogPost::where('slug', $slug)->first();

            if (!$post) {
                continue;
            }

            // Skip if already has "À lire aussi" section from this command
            if (str_contains($post->content, 'À lire aussi sur faktur.lu')) {
                $this->line("  Liens déjà présents : {$slug}");
                continue;
            }

            $linksHtml = '<div class="bg-slate-50 border border-slate-200 rounded-xl p-5 my-6">';
            $linksHtml .= '<p class="font-semibold text-slate-900 mb-3">À lire aussi sur faktur.lu :</p>';
            $linksHtml .= '<ul class="space-y-1">';

            foreach ($links as $link) {
                $target = BlogPost::where('slug', $link['slug'])->first();
                if ($target) {
                    $linksHtml .= '<li><a href="/fr/blog/' . $link['slug'] . '" class="text-primary-500 hover:text-primary-600 text-sm">' . $target->title . ' →</a></li>';
                }
            }

            $linksHtml .= '</ul></div>';

            // Insert before the last </p> or at the end
            $content = $post->content;
            $lastP = strrpos($content, '</p>');
            if ($lastP !== false) {
                $content = substr($content, 0, $lastP) . '</p>' . "\n" . $linksHtml . "\n" . substr($content, $lastP + 4);
            } else {
                $content .= "\n" . $linksHtml;
            }

            $post->update(['content' => $content]);
            $this->info("Liens internes ajoutés : {$slug}");
            $updated++;
        }

        $this->info("\nTerminé : {$updated} articles mis à jour, {$skipped} ignorés.");

        return 0;
    }
}
