<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Article « Relancer un client qui ne paie pas ».
 *
 * Dernier des 38 articles du blog. Trois corrections.
 *
 * 1. TEXTE DE LOI INCOMPLET. L'article attribuait la majoration de 8 points et
 *    l'indemnité de 40 euros à la « loi du 18 avril 2004 ». Ces deux règles
 *    viennent en réalité de la loi du 29 mars 2013, qui a modifié celle de
 *    2004 pour transposer la directive 2011/7/UE. L'article « délais de
 *    paiement », corrigé plus tôt, avertit expressément que « les articles qui
 *    citent encore le seul texte de 2004 décrivent un régime dépassé » : les
 *    deux se contredisaient.
 *
 * 2. TAUX PÉRIMÉ PAR LE CALENDRIER. Le taux de 10,15 % était donné comme
 *    « applicable au 1er semestre 2026 ». Ce semestre est clos. Le chiffre
 *    devient un exemple du mécanisme (BCE + 8 points), et le lecteur est
 *    renvoyé au Ministère de la Justice pour le taux en cours — même parti
 *    pris que l'article « délais de paiement ».
 *
 * 3. REVENDICATION PRODUIT SANS SA CONDITION. « Avec faktur.lu, vous pouvez
 *    automatiser ce premier rappel » et le CTA « Automatisez vos relances » :
 *    les relances automatiques par email relèvent du plan PRO
 *    (`email_reminders`), ce que le texte ne disait nulle part. Même correction
 *    que dans l'article « automatiser sa facturation ».
 */
return new class extends Migration
{
    private const KEY = 'relancer-client-impaye-luxembourg';

    /**
     * locale => [
     *   [avant, après] pour la loi,
     *   [avant, après] pour le taux,
     *   note de plan insérée après le conseil,
     * ]
     */
    private function fixes(): array
    {
        return [
            'fr' => [
                ['<li><strong>Indemnité forfaitaire</strong> : 40 € pour frais de recouvrement (sans justificatif, art. 6 de la loi)</li>',
                 "<li><strong>Indemnité forfaitaire</strong> : 40 € pour frais de recouvrement, sans justificatif</li>\n    <li>La majoration de 8 points et cette indemnité viennent de la <strong>loi du 29 mars 2013</strong>, qui a modifié celle de 2004 pour transposer la directive 2011/7/UE</li>"],
                ['<li><strong>Taux applicable au 1<sup>er</sup> semestre 2026 : 10,15 %</strong> (BCE 2,15 % + 8)</li>',
                 '<li>À titre d\'illustration, le taux du 1<sup>er</sup> semestre 2026 s\'établissait à <strong>10,15 %</strong> (BCE 2,15 % + 8) - le taux change chaque semestre, vérifiez celui en cours avant de chiffrer</li>'],
                '<p class="text-sm text-slate-500"><em>Les relances automatiques par email font partie du plan Pro.</em></p>',
            ],
            'de' => [
                ['<li><strong>Pauschalentschädigung</strong>: 40 € für Beitreibungskosten (ohne Nachweis, Art. 6 des Gesetzes)</li>',
                 "<li><strong>Pauschalentschädigung</strong>: 40 € für Beitreibungskosten, ohne Nachweis</li>\n    <li>Der Zuschlag von 8 Punkten und diese Entschädigung stammen aus dem <strong>Gesetz vom 29. März 2013</strong>, das jenes von 2004 zur Umsetzung der Richtlinie 2011/7/EU geändert hat</li>"],
                ['<li><strong>Satz im 1. Halbjahr 2026: 10,15 %</strong> (EZB 2,15 % + 8)</li>',
                 '<li>Zur Veranschaulichung: im 1. Halbjahr 2026 lag der Satz bei <strong>10,15 %</strong> (EZB 2,15 % + 8) - er ändert sich halbjährlich, prüfen Sie den aktuellen vor jeder Bezifferung</li>'],
                '<p class="text-sm text-slate-500"><em>Automatische E-Mail-Mahnungen gehören zum Pro-Tarif.</em></p>',
            ],
            'en' => [
                ['<li><strong>Fixed compensation</strong>: EUR 40 for recovery costs (no evidence needed, art. 6 of the law)</li>',
                 "<li><strong>Fixed compensation</strong>: EUR 40 for recovery costs, with no evidence needed</li>\n    <li>The 8-point margin and this compensation come from the <strong>law of 29 March 2013</strong>, which amended the 2004 law to transpose directive 2011/7/EU</li>"],
                ['<li><strong>Rate for the first half of 2026: 10.15%</strong> (ECB 2.15% + 8)</li>',
                 '<li>By way of illustration, the rate for the first half of 2026 stood at <strong>10.15%</strong> (ECB 2.15% + 8) — it changes every six months, so check the current one before putting a figure on a claim</li>'],
                '<p class="text-sm text-slate-500"><em>Automatic email reminders are part of the Pro plan.</em></p>',
            ],
            'lb' => [
                ["<li><strong>Forfaitaresch Entschiedegung</strong>: 40 € fir Recouvrementskäschten (ouni Beleg, Art. 6 vum Gesetz)</li>",
                 "<li><strong>Forfaitaresch Entschiedegung</strong>: 40 € fir Recouvrementskäschten, ouni Beleg</li>\n    <li>D'Majoratioun vun 8 Punkten an dës Entschiedegung kommen aus dem <strong>Gesetz vum 29. Mäerz 2013</strong>, dat dat vun 2004 geännert huet fir d'Richtlinn 2011/7/EU ëmzesetzen</li>"],
                ['<li><strong>Saz am 1. Hallefjoer 2026: 10,15 %</strong> (EZB 2,15 % + 8)</li>',
                 "<li>Als Illustratioun: am 1. Hallefjoer 2026 louch de Saz bei <strong>10,15 %</strong> (EZB 2,15 % + 8) - en ännert all Hallefjoer, kontrolléiert deen aktuellen ier Dir eppes bezifferten</li>"],
                '<p class="text-sm text-slate-500"><em>Automatesch E-Mail-Mahnunge gehéieren zum Plang Pro.</em></p>',
            ],
            'pt' => [
                ['<li><strong>Indemnização fixa</strong>: 40 € de custos de cobrança (sem justificativo, art. 6 da lei)</li>',
                 "<li><strong>Indemnização fixa</strong>: 40 € de custos de cobrança, sem justificativo</li>\n    <li>A majoração de 8 pontos e esta indemnização decorrem da <strong>lei de 29 de março de 2013</strong>, que alterou a de 2004 para transpor a diretiva 2011/7/UE</li>"],
                ['<li><strong>Taxa no 1.º semestre de 2026: 10,15 %</strong> (BCE 2,15 % + 8)</li>',
                 '<li>A título de exemplo, a taxa do 1.º semestre de 2026 situava-se em <strong>10,15 %</strong> (BCE 2,15 % + 8) — muda todos os semestres, verifique a que está em vigor antes de quantificar</li>'],
                '<p class="text-sm text-slate-500"><em>As cobranças automáticas por email fazem parte do plano Pro.</em></p>',
            ],
        ];
    }

    /** Le conseil produit, après lequel s'insère la réserve de plan. */
    private const TIP_ANCHOR = [
        'fr' => 'envoie un email de relance automatique.</p>',
        'de' => 'versendet automatisch eine Zahlungserinnerung.</p>',
        'en' => 'sends a reminder email automatically.</p>',
        'lb' => 'schéckt automatesch eng Mahnung per E-Mail.</p>',
        'pt' => 'envia automaticamente um e-mail de cobrança.</p>',
    ];

    public function up(): void
    {
        foreach ($this->fixes() as $locale => [$law, $rate, $planNote]) {
            $post = DB::table('blog_posts')
                ->where('translation_key', self::KEY)
                ->where('locale', $locale)
                ->first(['id', 'content']);

            if (! $post) {
                continue;
            }

            $content = $post->content;
            $applied = 0;

            foreach ([$law, $rate] as [$from, $to]) {
                if (str_contains($content, $from)) {
                    $content = str_replace($from, $to, $content);
                    $applied++;
                } else {
                    echo "  !! {$locale} : motif introuvable — ".mb_substr($from, 0, 50)."\n";
                }
            }

            $anchor = self::TIP_ANCHOR[$locale];

            if (str_contains($content, $anchor) && ! str_contains($content, 'Pro')) {
                $content = str_replace($anchor, $anchor."\n\n".$planNote, $content);
                $applied++;
            } elseif (str_contains($content, $anchor) && ! str_contains($content, 'slate-500"><em>')) {
                $content = str_replace($anchor, $anchor."\n\n".$planNote, $content);
                $applied++;
            }

            DB::table('blog_posts')->where('id', $post->id)->update([
                'content' => $content,
                'updated_at' => now(),
            ]);

            echo "  {$locale} : {$applied}/3 correction(s)\n";
        }
    }

    public function down(): void
    {
        // Réattribuer les règles de 2013 au texte de 2004 n'aurait pas de sens.
    }
};
