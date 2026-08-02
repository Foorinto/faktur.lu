<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Article « Facturer un projet : forfait ou régie ».
 *
 * REVENDICATION FAUSSE. Le CTA annonçait « factures d'acompte et factures de
 * projet ». Aucune fonctionnalité de facture d'acompte n'existe : la table
 * `invoices` ne porte ni colonne `deposit`, ni `acompte`, ni `advance`, et
 * Invoice ne définit que deux types, TYPE_INVOICE et TYPE_CREDIT_NOTE. Le
 * terme n'apparaît dans l'application que comme entrée de GLOSSAIRE, c'est-à-
 * dire comme définition — pas comme fonction.
 *
 * Le suivi du temps, lui, existe bien, mais relève du plan Essentiel
 * (time_tracking). Le CTA le présentait sans réserve.
 *
 * AJOUT FACTUEL. La section sur les acomptes conseillait d'en demander sans
 * dire ce que cela déclenche fiscalement. L'article 63, paragraphe 5, LIVA est
 * clair : en cas d'acompte sur une prestation non achevée, la facture est due
 * « au plus tard lors de l'encaissement de cet acompte » — donc sans attendre
 * le 15 du mois suivant. C'est une conséquence directe du conseil donné.
 *
 * PARITÉ. Les quatre traductions n'avaient qu'un lien contre trois : les
 * renvois vers l'article « devis » et l'article « délais de paiement »
 * manquaient. Ajoutés avec les slugs propres à chaque langue.
 */
return new class extends Migration
{
    private const KEY = 'facturation-projet-forfait-regie-luxembourg';

    /** locale => [cta_avant, cta_après, note_plan, acompte_tva] */
    private function content(): array
    {
        return [
            'fr' => [
                'Suivi du temps, factures d\'acompte et factures de projet conformes, en quelques clics.',
                'Suivi du temps, factures de projet conformes et relevés d\'heures prêts à joindre, en quelques clics.',
                '<p class="text-sm text-slate-500"><em>Le suivi du temps est disponible à partir du plan Essentiel.</em></p>',
                '<p>Un point souvent ignoré : un acompte déclenche l\'obligation de facturer. L\'<strong>article 63, paragraphe 5, LIVA</strong> impose d\'émettre la facture <strong>dès l\'encaissement de l\'acompte</strong>, sans attendre le 15 du mois suivant comme pour une prestation achevée.</p>',
            ],
            'de' => [
                'Zeiterfassung, Anzahlungs- und Projektrechnungen - konform und in wenigen Klicks.',
                'Zeiterfassung, konforme Projektrechnungen und beilagefertige Stundennachweise - in wenigen Klicks.',
                '<p class="text-sm text-slate-500"><em>Die Zeiterfassung ist ab dem Essentiel-Tarif verfügbar.</em></p>',
                '<p>Ein oft übersehener Punkt: eine Anzahlung löst die Rechnungspflicht aus. <strong>Artikel 63 Absatz 5 LIVA</strong> verlangt die Rechnung <strong>bereits bei Vereinnahmung der Anzahlung</strong>, ohne den 15. des Folgemonats abzuwarten, wie es bei einer abgeschlossenen Leistung der Fall wäre.</p>',
            ],
            'en' => [
                'Time tracking, deposit and project invoices - compliant and in a few clicks.',
                'Time tracking, compliant project invoices and timesheets ready to attach - in a few clicks.',
                '<p class="text-sm text-slate-500"><em>Time tracking is available from the Essentiel plan.</em></p>',
                '<p>A point often missed: an advance payment triggers the obligation to invoice. <strong>Article 63(5) LIVA</strong> requires the invoice <strong>as soon as the advance is collected</strong>, without waiting for the 15th of the following month as you would for a completed service.</p>',
            ],
            'lb' => [
                'Zäiterfaassung, Akontos- a Projetrechnungen - konform an a wéinege Klicken.',
                'Zäiterfaassung, konform Projetrechnungen a Stonnenopstellungen prett fir bäizeleeën - a wéinege Klicken.',
                '<p class="text-sm text-slate-500"><em>De Zäitsuivi ass vum Plang Essentiel un disponibel.</em></p>',
                '<p>E Punkt, deen dacks iwwersi gëtt: en Akont léist d\'Fakturatiounsflicht aus. Den <strong>Artikel 63, Paragraf 5, LIVA</strong> verlaangt d\'Rechnung <strong>scho beim Encaissement vum Akont</strong>, ouni den 15. vum Mount duerno ofzewaarden, wéi et bei enger ofgeschlossener Leeschtung de Fall wier.</p>',
            ],
            'pt' => [
                'Registo de tempo, faturas de adiantamento e de projeto - conformes e em poucos cliques.',
                'Registo de tempo, faturas de projeto conformes e mapas de horas prontos a anexar - em poucos cliques.',
                '<p class="text-sm text-slate-500"><em>O registo de tempo está disponível a partir do plano Essentiel.</em></p>',
                '<p>Um ponto muitas vezes ignorado: um adiantamento desencadeia a obrigação de faturar. O <strong>artigo 63, n.º 5, LIVA</strong> exige a fatura <strong>logo no recebimento do adiantamento</strong>, sem esperar pelo dia 15 do mês seguinte como sucede numa prestação concluída.</p>',
            ],
        ];
    }

    /** Titres de CTA à neutraliser (ils annoncent aussi les acomptes). */
    private const CTA_TITLES = [
        'fr' => ['Forfait, régie, acomptes : faktur.lu gère tout', 'Forfait ou régie : faktur.lu suit votre projet'],
        'de' => ['Festpreis, Aufwand, Anzahlungen: faktur.lu erledigt alles', 'Festpreis oder Aufwand: faktur.lu begleitet Ihr Projekt'],
        'en' => ['Fixed price, time, deposits: faktur.lu handles it all', 'Fixed price or time and materials: faktur.lu follows your project'],
        'lb' => ['Forfait, Opwand, Akonten: faktur.lu erleedegt alles', 'Forfait oder Opwand: faktur.lu begleet Äre Projet'],
        'pt' => ['Preço fixo, tempo, adiantamentos: o faktur.lu trata de tudo', 'Preço fixo ou por tempo: o faktur.lu acompanha o seu projeto'],
    ];

    /** Liens internes manquants dans les traductions : [devis, délais]. */
    private const LINKS = [
        'de' => ['professionelles-angebot-luxemburg-erstellen', 'Angebot', 'zahlungsfristen-luxemburg-rechtlicher-rahmen-2026', 'Zahlungsfristen'],
        'en' => ['create-professional-quote-luxembourg', 'quote', 'payment-terms-luxembourg-legal-framework-2026', 'payment terms'],
        'lb' => ['professionellen-devis-letzebuerg-maachen', 'Devis', 'bezuelungsfristen-letzebuerg-rechtleche-kader-2026', 'Bezuelungsfristen'],
        'pt' => ['fazer-orcamento-profissional-luxemburgo', 'orçamento', 'prazos-de-pagamento-no-luxemburgo-quadro-legal-2026', 'prazos de pagamento'],
    ];

    public function up(): void
    {
        foreach ($this->content() as $locale => [$ctaFrom, $ctaTo, $planNote, $vatNote]) {
            $post = DB::table('blog_posts')
                ->where('translation_key', self::KEY)
                ->where('locale', $locale)
                ->first(['id', 'content']);

            if (! $post) {
                continue;
            }

            $content = $post->content;

            // 1. CTA : texte et titre.
            $content = str_replace($ctaFrom, $ctaTo, $content);
            [$titleFrom, $titleTo] = self::CTA_TITLES[$locale];
            $content = str_replace($titleFrom, $titleTo, $content);

            // 2. Réserve de plan, juste avant le CTA.
            if (! str_contains($content, 'Essentiel</em>')) {
                $content = preg_replace(
                    '~(<div class="bg-primary-50 rounded-xl p-6 mt-8">)~',
                    $planNote."\n\n".'$1',
                    $content,
                    1
                );
            }

            // 3. Conséquence TVA de l'acompte, à la fin de la section acomptes.
            if (! str_contains($content, '63')) {
                $content = preg_replace(
                    '~(\n\n<div class="bg-primary-50 rounded-xl p-6 mt-8">)~',
                    "\n\n".$vatNote.'$1',
                    $content,
                    1
                );
            }

            // 4. Liens internes manquants (traductions uniquement).
            if (isset(self::LINKS[$locale])) {
                [$quoteSlug, $quoteWord, $termsSlug, $termsWord] = self::LINKS[$locale];

                foreach ([[$quoteWord, $quoteSlug], [$termsWord, $termsSlug]] as [$word, $slug]) {
                    if (str_contains($content, '/blog/'.$slug)) {
                        continue;
                    }

                    $link = '<a href="/'.$locale.'/blog/'.$slug.'">'.$word.'</a>';
                    $pos = mb_strpos($content, $word);

                    if ($pos !== false) {
                        $content = mb_substr($content, 0, $pos).$link.mb_substr($content, $pos + mb_strlen($word));
                    }
                }
            }

            DB::table('blog_posts')->where('id', $post->id)->update([
                'content' => $content,
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        // Réintroduire une fonctionnalité inexistante n'aurait pas de sens.
    }
};
