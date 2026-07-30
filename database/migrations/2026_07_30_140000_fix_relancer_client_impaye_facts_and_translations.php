<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Article « relancer-client-impaye-luxembourg » — vérification factuelle et
 * traductions intégrales.
 *
 * ERREUR CORRIGÉE : quatre traductions sur cinq annonçaient un taux d'intérêt
 * de retard « d'environ 12 % en 2026 ». Le taux légal réel pour les
 * transactions commerciales est de 10,15 % au 1er semestre 2026
 * (BCE 2,15 % + 8 points), publié par le Ministère de la Justice.
 * Sur un article traitant précisément des intérêts de retard, un écart de
 * près de 2 points est directement exploitable par un lecteur qui facturerait
 * ces intérêts à son client.
 *
 * Le français annonçait le bon chiffre mais le datait du 2e semestre 2025.
 * La formulation retenue partout : la FORMULE (BCE + 8), le taux daté du
 * semestre, et un renvoi explicite à la source officielle — le taux étant
 * révisé tous les six mois, seul le chiffre daté vieillit.
 *
 * FAITS VÉRIFIÉS (2026-07-30) et confirmés exacts :
 * - Loi du 18 avril 2004 sur les délais de paiement
 * - Indemnité forfaitaire de 40 € (art. 6)
 * - Délais par défaut 30 jours B2B/B2G, 60 jours max par contrat
 * - Compétence de la Justice de paix pour les créances ≤ 15 000 €
 * - Intérêts courant dès l'échéance, sans mise en demeure préalable
 *
 * PARITÉ : les traductions faisaient 3 400 à 4 600 caractères contre 7 237 en
 * français — près de la moitié du contenu manquait (intérêts de retard,
 * recouvrement, bonnes pratiques, sources). Elles sont désormais complètes.
 *
 * ⚠️ La version luxembourgeoise mérite une relecture par un locuteur natif.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach ($this->contents() as $locale => $data) {
            DB::table('blog_posts')
                ->where('translation_key', 'relancer-client-impaye-luxembourg')
                ->where('locale', $locale)
                ->update([
                    'content' => $data['content'],
                    'updated_at' => now(),
                ]);
        }
    }

    public function down(): void
    {
        // Contenu rédactionnel : pas de retour arrière automatique. Les versions
        // antérieures contenaient une erreur factuelle, les restaurer n'aurait
        // pas de sens.
    }

    /** Bloc « intérêts de retard », identique en substance dans les 5 langues. */
    private function rateBlock(string $locale): string
    {
        $link = 'https://mj.gouvernement.lu/fr/service-citoyens/taux-interet-legal.html';

        return match ($locale) {
            'fr' => <<<HTML
<h2>Les intérêts de retard au Luxembourg</h2>

<p>Au Luxembourg, les intérêts de retard sont régis par la <strong>loi du 18 avril 2004</strong> sur les délais de paiement :</p>

<ul>
    <li><strong>Transactions B2B et B2G</strong> : taux d'intérêt = <strong>taux directeur BCE + 8 points de pourcentage</strong></li>
    <li><strong>Taux applicable au 1<sup>er</sup> semestre 2026 : 10,15 %</strong> (BCE 2,15 % + 8)</li>
    <li><strong>Indemnité forfaitaire</strong> : 40 € pour frais de recouvrement (sans justificatif, art. 6 de la loi)</li>
    <li>Délais légaux par défaut : 30 jours B2B et B2G (max 60 jours B2B par contrat si non abusif)</li>
</ul>

<p>⚠️ Ce taux est <strong>révisé chaque semestre</strong> et publié au Mémorial. Vérifiez toujours <a href="{$link}" target="_blank" rel="noopener">le taux en vigueur</a> au moment de votre relance.</p>
HTML,
            'de' => <<<HTML
<h2>Verzugszinsen in Luxemburg</h2>

<p>In Luxemburg werden Verzugszinsen durch das <strong>Gesetz vom 18. April 2004</strong> über Zahlungsfristen geregelt:</p>

<ul>
    <li><strong>B2B- und B2G-Transaktionen</strong>: Zinssatz = <strong>EZB-Leitzins + 8 Prozentpunkte</strong></li>
    <li><strong>Satz im 1. Halbjahr 2026: 10,15 %</strong> (EZB 2,15 % + 8)</li>
    <li><strong>Pauschale Entschädigung</strong>: 40 € für Beitreibungskosten (ohne Nachweis, Art. 6 des Gesetzes)</li>
    <li>Gesetzliche Standardfristen: 30 Tage B2B und B2G (max. 60 Tage B2B vertraglich, sofern nicht missbräuchlich)</li>
</ul>

<p>⚠️ Dieser Satz wird <strong>halbjährlich angepasst</strong> und im Mémorial veröffentlicht. Prüfen Sie stets <a href="{$link}" target="_blank" rel="noopener">den geltenden Satz</a> zum Zeitpunkt Ihrer Mahnung.</p>
HTML,
            'en' => <<<HTML
<h2>Late payment interest in Luxembourg</h2>

<p>In Luxembourg, late payment interest is governed by the <strong>Law of 18 April 2004</strong> on payment terms:</p>

<ul>
    <li><strong>B2B and B2G transactions</strong>: interest rate = <strong>ECB refinancing rate + 8 percentage points</strong></li>
    <li><strong>Rate for the first half of 2026: 10.15%</strong> (ECB 2.15% + 8)</li>
    <li><strong>Fixed compensation</strong>: €40 for recovery costs (no supporting document required, art. 6 of the Law)</li>
    <li>Default legal terms: 30 days B2B and B2G (max 60 days B2B by contract, if not abusive)</li>
</ul>

<p>⚠️ This rate is <strong>revised every six months</strong> and published in the Mémorial. Always check <a href="{$link}" target="_blank" rel="noopener">the rate currently in force</a> when sending your reminder.</p>
HTML,
            'lb' => <<<HTML
<h2>Verzuchszënsen zu Lëtzebuerg</h2>

<p>Zu Lëtzebuerg sinn d'Verzuchszënsen duerch d'<strong>Gesetz vum 18. Abrëll 2004</strong> iwwer d'Bezuelfristen geregelt:</p>

<ul>
    <li><strong>B2B- a B2G-Transaktiounen</strong>: Zënssaz = <strong>EZB-Leetzëns + 8 Prozentpunkten</strong></li>
    <li><strong>Saz am 1. Hallefjoer 2026: 10,15 %</strong> (EZB 2,15 % + 8)</li>
    <li><strong>Forfaitaire Entschiedegung</strong>: 40 € fir Bäitreiwungskäschten (ouni Beleg, Art. 6 vum Gesetz)</li>
    <li>Gesetzlech Standardfristen: 30 Deeg B2B a B2G (max. 60 Deeg B2B duerch Kontrakt, wann net mëssbräuchlech)</li>
</ul>

<p>⚠️ Dëse Saz gëtt <strong>all Hallefjoer ugepasst</strong> a gëtt am Mémorial publizéiert. Kuckt ëmmer <a href="{$link}" target="_blank" rel="noopener">de gëltege Saz</a> nom Moment vun Ärer Mahnung.</p>
HTML,
            default => <<<HTML
<h2>Juros de mora no Luxemburgo</h2>

<p>No Luxemburgo, os juros de mora são regidos pela <strong>lei de 18 de abril de 2004</strong> sobre os prazos de pagamento:</p>

<ul>
    <li><strong>Transações B2B e B2G</strong>: taxa de juro = <strong>taxa diretora do BCE + 8 pontos percentuais</strong></li>
    <li><strong>Taxa no 1.º semestre de 2026: 10,15 %</strong> (BCE 2,15 % + 8)</li>
    <li><strong>Indemnização fixa</strong>: 40 € para despesas de cobrança (sem justificativo, art. 6.º da lei)</li>
    <li>Prazos legais por defeito: 30 dias B2B e B2G (máx. 60 dias B2B por contrato, se não abusivo)</li>
</ul>

<p>⚠️ Esta taxa é <strong>revista semestralmente</strong> e publicada no Mémorial. Verifique sempre <a href="{$link}" target="_blank" rel="noopener">a taxa em vigor</a> no momento da sua cobrança.</p>
HTML,
        };
    }

    /** @return array<string, array{content: string}> */
    private function contents(): array
    {
        return [
            'fr' => ['content' => $this->fr()],
            'de' => ['content' => $this->de()],
            'en' => ['content' => $this->en()],
            'lb' => ['content' => $this->lb()],
            'pt' => ['content' => $this->pt()],
        ];
    }

    private function fr(): string
    {
        $rate = $this->rateBlock('fr');

        return <<<HTML
<p class="lead">Les factures impayées sont le cauchemar de tout entrepreneur. Au Luxembourg, la <a href="https://legilux.public.lu/eli/etat/leg/loi/2004/04/18/n8/jo" target="_blank" rel="noopener">loi du 18 avril 2004</a> encadre les délais de paiement et les intérêts de retard. Voici comment gérer les relances efficacement, du rappel amical à la mise en demeure.</p>

<h2>Étape 1 : Le rappel amical (J+7 après échéance)</h2>

<p>Un simple oubli ? C'est souvent le cas. Le premier rappel doit être <strong>courtois et professionnel</strong> :</p>

<ul>
    <li>Envoyez un email rappelant le numéro et le montant de la facture</li>
    <li>Joignez une copie de la facture originale</li>
    <li>Proposez un nouveau délai de paiement si nécessaire</li>
    <li>Restez factuel et cordial</li>
</ul>

<p><strong>Conseil :</strong> avec faktur.lu, vous pouvez automatiser ce premier rappel. Le système détecte les factures en retard et envoie un email de relance automatique.</p>

<h2>Étape 2 : La relance formelle (J+15)</h2>

<p>Si le premier rappel reste sans réponse, envoyez une <strong>relance plus formelle</strong> :</p>

<ul>
    <li>Mentionnez clairement le retard de paiement</li>
    <li>Rappelez les conditions de paiement convenues</li>
    <li>Indiquez que des intérêts de retard pourront être appliqués</li>
    <li>Fixez un délai précis (ex : 8 jours)</li>
</ul>

<h2>Étape 3 : La mise en demeure (J+30)</h2>

<p>La mise en demeure est un document formel qui constitue une <strong>preuve juridique</strong>. Bien qu'aucune forme légale ne soit imposée au Luxembourg, l'envoi par <strong>lettre recommandée avec accusé de réception</strong> est fortement recommandé. Elle doit contenir :</p>

<ul>
    <li>La mention <strong>« Mise en demeure »</strong> en objet</li>
    <li>Le détail des factures impayées (numéros, montants, dates)</li>
    <li>Le montant total dû (principal + intérêts de retard éventuels + indemnité forfaitaire 40 €)</li>
    <li>Un <strong>délai de 8 jours</strong> pour régulariser (durée d'usage)</li>
    <li>La mention que vous vous réservez le droit d'engager des poursuites</li>
</ul>

<div class="bg-blue-50 border-l-4 border-blue-500 p-4 my-6">
    <p class="font-semibold">À noter</p>
    <p>Sous la loi du 18 avril 2004, les intérêts de retard commencent à courir dès l'échéance contractuelle, <strong>sans qu'une mise en demeure formelle soit requise</strong>. La mise en demeure reste néanmoins utile pour engager la voie judiciaire et constituer la preuve de la créance.</p>
</div>

{$rate}

<h2>Étape 4 : Le recouvrement (J+60)</h2>

<p>Si toutes les relances échouent, plusieurs options s'offrent à vous :</p>

<ul>
    <li><strong>Société de recouvrement</strong> : elle se charge des démarches moyennant une commission (15-25 %)</li>
    <li><strong>Injonction de payer</strong> : procédure simplifiée devant le juge de paix (pour les créances ≤ 15 000 €)</li>
    <li><strong>Assignation au tribunal</strong> : pour les montants plus importants</li>
    <li><strong>Médiation commerciale</strong> : solution alternative, plus rapide et moins coûteuse</li>
</ul>

<h2>Bonnes pratiques pour éviter les impayés</h2>

<ul>
    <li><strong>Facturez rapidement</strong> : plus vous attendez, plus le risque d'impayé augmente</li>
    <li><strong>Conditions claires</strong> : mentionnez les délais et modalités de paiement sur chaque facture</li>
    <li><strong>Acompte</strong> : demandez un acompte de 30-50 % pour les grosses prestations</li>
    <li><strong>Relances automatiques</strong> : utilisez un logiciel comme faktur.lu pour ne jamais oublier de relancer</li>
    <li><strong>Vérifiez la solvabilité</strong> : pour les nouveaux clients, consultez le <a href="https://www.lbr.lu/" target="_blank" rel="noopener">RCS Luxembourg (LBR)</a></li>
</ul>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">À vérifier chaque année</p>
    <p>Le taux d'intérêt légal évolue semestriellement et l'indemnité forfaitaire peut être revue. Cette page est mise à jour régulièrement, mais consultez <a href="https://mj.gouvernement.lu/fr/service-citoyens/taux-interet-legal.html" target="_blank" rel="noopener">le taux en vigueur</a> au moment de votre relance.</p>
</div>

<h2>Sources officielles</h2>

<ul>
    <li><a href="https://legilux.public.lu/eli/etat/leg/loi/2004/04/18/n8/jo" target="_blank" rel="noopener">Loi du 18 avril 2004 sur les délais de paiement</a></li>
    <li><a href="https://guichet.public.lu/fr/entreprises/gestion-juridique-comptabilite/facturation/encaissement/interets-retard.html" target="_blank" rel="noopener">Guichet.lu - Intérêts de retard</a></li>
    <li><a href="https://mj.gouvernement.lu/fr/service-citoyens/taux-interet-legal.html" target="_blank" rel="noopener">Ministère de la Justice - Taux d'intérêt légal en vigueur</a></li>
    <li><a href="https://justice.public.lu/fr/creances/recouvrement-creances.html" target="_blank" rel="noopener">Justice.lu - Recouvrement de créances</a></li>
</ul>

<p class="text-sm text-slate-500 mt-6"><em>Article mis à jour le 30 juillet 2026.</em></p>

<div class="mt-8 p-4 bg-slate-50 rounded-xl"><h3 class="text-base font-semibold text-slate-900 mb-3">Articles connexes</h3><ul class="space-y-1"><li><a href="/fr/blog/delais-paiement-luxembourg-cadre-legal-2026" class="text-primary-500 hover:text-primary-600 text-sm">Délais de paiement légaux LU →</a></li><li><a href="/fr/blog/automatiser-facturation-7-conseils-gagner-temps" class="text-primary-500 hover:text-primary-600 text-sm">Automatiser sa facturation →</a></li></ul></div>

<div class="bg-primary-50 rounded-xl p-6 mt-8">
    <h3 class="text-lg font-semibold text-primary-900 mb-2">Automatisez vos relances avec faktur.lu</h3>
    <p class="text-primary-800 mb-4">faktur.lu détecte automatiquement les factures en retard et envoie des relances par email. Ne laissez plus aucune facture impayée tomber dans l'oubli.</p>
    <a href="/register" class="inline-block bg-primary-500 text-white font-semibold px-6 py-3 rounded-xl hover:bg-primary-600">Essayer gratuitement 14 jours</a>
</div>
HTML;
    }

    private function de(): string
    {
        $rate = $this->rateBlock('de');

        return <<<HTML
<p class="lead">Unbezahlte Rechnungen sind der Albtraum jedes Unternehmers. In Luxemburg regelt das <a href="https://legilux.public.lu/eli/etat/leg/loi/2004/04/18/n8/jo" target="_blank" rel="noopener">Gesetz vom 18. April 2004</a> die Zahlungsfristen und Verzugszinsen. So gehen Sie beim Mahnen wirkungsvoll vor – von der freundlichen Erinnerung bis zur förmlichen Inverzugsetzung.</p>

<h2>Schritt 1: Die freundliche Erinnerung (T+7 nach Fälligkeit)</h2>

<p>Nur vergessen? Das ist häufig der Fall. Die erste Erinnerung sollte <strong>höflich und professionell</strong> sein:</p>

<ul>
    <li>Senden Sie eine E-Mail mit Rechnungsnummer und Betrag</li>
    <li>Fügen Sie eine Kopie der Originalrechnung bei</li>
    <li>Schlagen Sie bei Bedarf eine neue Zahlungsfrist vor</li>
    <li>Bleiben Sie sachlich und freundlich</li>
</ul>

<p><strong>Tipp:</strong> Mit faktur.lu lässt sich diese erste Erinnerung automatisieren. Das System erkennt überfällige Rechnungen und versendet automatisch eine Zahlungserinnerung.</p>

<h2>Schritt 2: Die förmliche Mahnung (T+15)</h2>

<p>Bleibt die erste Erinnerung unbeantwortet, senden Sie eine <strong>förmlichere Mahnung</strong>:</p>

<ul>
    <li>Weisen Sie klar auf den Zahlungsverzug hin</li>
    <li>Erinnern Sie an die vereinbarten Zahlungsbedingungen</li>
    <li>Weisen Sie darauf hin, dass Verzugszinsen anfallen können</li>
    <li>Setzen Sie eine konkrete Frist (z. B. 8 Tage)</li>
</ul>

<h2>Schritt 3: Die Inverzugsetzung (T+30)</h2>

<p>Die Inverzugsetzung ist ein förmliches Dokument und stellt einen <strong>rechtlichen Nachweis</strong> dar. Zwar schreibt Luxemburg keine Form vor, der Versand per <strong>Einschreiben mit Rückschein</strong> ist jedoch dringend zu empfehlen. Sie muss enthalten:</p>

<ul>
    <li>Den Betreff <strong>„Inverzugsetzung"</strong></li>
    <li>Die Aufstellung der unbezahlten Rechnungen (Nummern, Beträge, Daten)</li>
    <li>Den Gesamtbetrag (Hauptforderung + etwaige Verzugszinsen + Pauschale von 40 €)</li>
    <li>Eine <strong>Frist von 8 Tagen</strong> zur Begleichung (übliche Dauer)</li>
    <li>Den Hinweis, dass Sie sich rechtliche Schritte vorbehalten</li>
</ul>

<div class="bg-blue-50 border-l-4 border-blue-500 p-4 my-6">
    <p class="font-semibold">Wichtig</p>
    <p>Nach dem Gesetz vom 18. April 2004 laufen Verzugszinsen bereits ab der vertraglichen Fälligkeit, <strong>ohne dass eine förmliche Inverzugsetzung erforderlich wäre</strong>. Sie bleibt dennoch nützlich, um den Rechtsweg zu beschreiten und die Forderung zu belegen.</p>
</div>

{$rate}

<h2>Schritt 4: Die Beitreibung (T+60)</h2>

<p>Bleiben alle Mahnungen erfolglos, stehen Ihnen mehrere Wege offen:</p>

<ul>
    <li><strong>Inkassounternehmen</strong>: übernimmt die Schritte gegen eine Provision (15–25 %)</li>
    <li><strong>Zahlungsbefehl</strong>: vereinfachtes Verfahren vor dem Friedensgericht (für Forderungen ≤ 15 000 €)</li>
    <li><strong>Klage vor Gericht</strong>: bei höheren Beträgen</li>
    <li><strong>Wirtschaftsmediation</strong>: schnellere und günstigere Alternative</li>
</ul>

<h2>Bewährte Praktiken gegen Zahlungsausfälle</h2>

<ul>
    <li><strong>Zügig fakturieren</strong>: je länger Sie warten, desto höher das Ausfallrisiko</li>
    <li><strong>Klare Bedingungen</strong>: Zahlungsfristen und -modalitäten auf jeder Rechnung angeben</li>
    <li><strong>Anzahlung</strong>: bei größeren Aufträgen 30–50 % im Voraus verlangen</li>
    <li><strong>Automatische Mahnungen</strong>: eine Software wie faktur.lu nutzen, um keine Mahnung zu vergessen</li>
    <li><strong>Bonität prüfen</strong>: bei Neukunden das <a href="https://www.lbr.lu/" target="_blank" rel="noopener">Handelsregister Luxemburg (LBR)</a> konsultieren</li>
</ul>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">Jährlich zu prüfen</p>
    <p>Der gesetzliche Zinssatz ändert sich halbjährlich, und die Pauschale kann angepasst werden. Diese Seite wird regelmäßig aktualisiert – prüfen Sie dennoch <a href="https://mj.gouvernement.lu/fr/service-citoyens/taux-interet-legal.html" target="_blank" rel="noopener">den geltenden Satz</a> zum Zeitpunkt Ihrer Mahnung.</p>
</div>

<h2>Offizielle Quellen</h2>

<ul>
    <li><a href="https://legilux.public.lu/eli/etat/leg/loi/2004/04/18/n8/jo" target="_blank" rel="noopener">Gesetz vom 18. April 2004 über Zahlungsfristen</a></li>
    <li><a href="https://guichet.public.lu/fr/entreprises/gestion-juridique-comptabilite/facturation/encaissement/interets-retard.html" target="_blank" rel="noopener">Guichet.lu – Verzugszinsen</a></li>
    <li><a href="https://mj.gouvernement.lu/fr/service-citoyens/taux-interet-legal.html" target="_blank" rel="noopener">Justizministerium – geltender gesetzlicher Zinssatz</a></li>
    <li><a href="https://justice.public.lu/fr/creances/recouvrement-creances.html" target="_blank" rel="noopener">Justice.lu – Forderungsbeitreibung</a></li>
</ul>

<p class="text-sm text-slate-500 mt-6"><em>Artikel aktualisiert am 30. Juli 2026.</em></p>

<div class="mt-8 p-4 bg-slate-50 rounded-xl"><h3 class="text-base font-semibold text-slate-900 mb-3">Verwandte Artikel</h3><ul class="space-y-1"><li><a href="/de/blog/delais-paiement-luxembourg-cadre-legal-2026" class="text-primary-500 hover:text-primary-600 text-sm">Gesetzliche Zahlungsfristen LU →</a></li><li><a href="/de/blog/automatiser-facturation-7-conseils-gagner-temps" class="text-primary-500 hover:text-primary-600 text-sm">Fakturierung automatisieren →</a></li></ul></div>

<div class="bg-primary-50 rounded-xl p-6 mt-8">
    <h3 class="text-lg font-semibold text-primary-900 mb-2">Automatisieren Sie Ihre Mahnungen mit faktur.lu</h3>
    <p class="text-primary-800 mb-4">faktur.lu erkennt überfällige Rechnungen automatisch und versendet Mahnungen per E-Mail. Keine unbezahlte Rechnung gerät mehr in Vergessenheit.</p>
    <a href="/register" class="inline-block bg-primary-500 text-white font-semibold px-6 py-3 rounded-xl hover:bg-primary-600">14 Tage kostenlos testen</a>
</div>
HTML;
    }

    private function en(): string
    {
        $rate = $this->rateBlock('en');

        return <<<HTML
<p class="lead">Unpaid invoices are every business owner's nightmare. In Luxembourg, the <a href="https://legilux.public.lu/eli/etat/leg/loi/2004/04/18/n8/jo" target="_blank" rel="noopener">Law of 18 April 2004</a> governs payment terms and late payment interest. Here is how to chase payment effectively, from a friendly reminder to a formal notice.</p>

<h2>Step 1: The friendly reminder (D+7 after the due date)</h2>

<p>Simply forgotten? That is often the case. The first reminder should be <strong>courteous and professional</strong>:</p>

<ul>
    <li>Send an email restating the invoice number and amount</li>
    <li>Attach a copy of the original invoice</li>
    <li>Offer a new payment deadline if needed</li>
    <li>Stay factual and cordial</li>
</ul>

<p><strong>Tip:</strong> with faktur.lu you can automate this first reminder. The system detects overdue invoices and sends a reminder email automatically.</p>

<h2>Step 2: The formal reminder (D+15)</h2>

<p>If the first reminder goes unanswered, send a <strong>more formal reminder</strong>:</p>

<ul>
    <li>State the payment delay clearly</li>
    <li>Restate the agreed payment terms</li>
    <li>Indicate that late payment interest may be applied</li>
    <li>Set a precise deadline (e.g. 8 days)</li>
</ul>

<h2>Step 3: The formal notice (D+30)</h2>

<p>The formal notice (<em>mise en demeure</em>) is an official document that constitutes <strong>legal evidence</strong>. Although Luxembourg imposes no particular form, sending it by <strong>registered letter with acknowledgement of receipt</strong> is strongly recommended. It must contain:</p>

<ul>
    <li>The heading <strong>"Formal notice"</strong></li>
    <li>Details of the unpaid invoices (numbers, amounts, dates)</li>
    <li>The total amount due (principal + any late payment interest + the €40 fixed compensation)</li>
    <li>An <strong>8-day deadline</strong> to settle (customary period)</li>
    <li>A statement that you reserve the right to take legal action</li>
</ul>

<div class="bg-blue-50 border-l-4 border-blue-500 p-4 my-6">
    <p class="font-semibold">Worth knowing</p>
    <p>Under the Law of 18 April 2004, late payment interest starts accruing from the contractual due date, <strong>without any formal notice being required</strong>. The formal notice nonetheless remains useful to open the way to legal proceedings and to evidence the debt.</p>
</div>

{$rate}

<h2>Step 4: Debt recovery (D+60)</h2>

<p>If every reminder fails, several options are open to you:</p>

<ul>
    <li><strong>Debt collection agency</strong>: handles the process for a commission (15–25%)</li>
    <li><strong>Order for payment</strong>: simplified procedure before the Justice of the Peace (for claims ≤ €15,000)</li>
    <li><strong>Court proceedings</strong>: for larger amounts</li>
    <li><strong>Commercial mediation</strong>: an alternative that is faster and less costly</li>
</ul>

<h2>Best practices to avoid unpaid invoices</h2>

<ul>
    <li><strong>Invoice promptly</strong>: the longer you wait, the higher the risk of non-payment</li>
    <li><strong>Clear terms</strong>: state payment deadlines and methods on every invoice</li>
    <li><strong>Deposit</strong>: ask for a 30–50% deposit on large engagements</li>
    <li><strong>Automatic reminders</strong>: use software such as faktur.lu so you never forget to chase</li>
    <li><strong>Check solvency</strong>: for new clients, consult the <a href="https://www.lbr.lu/" target="_blank" rel="noopener">Luxembourg Trade Register (LBR)</a></li>
</ul>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">To check every year</p>
    <p>The legal interest rate changes every six months and the fixed compensation may be revised. This page is updated regularly, but do check <a href="https://mj.gouvernement.lu/fr/service-citoyens/taux-interet-legal.html" target="_blank" rel="noopener">the rate in force</a> when you send your reminder.</p>
</div>

<h2>Official sources</h2>

<ul>
    <li><a href="https://legilux.public.lu/eli/etat/leg/loi/2004/04/18/n8/jo" target="_blank" rel="noopener">Law of 18 April 2004 on payment terms</a></li>
    <li><a href="https://guichet.public.lu/fr/entreprises/gestion-juridique-comptabilite/facturation/encaissement/interets-retard.html" target="_blank" rel="noopener">Guichet.lu – Late payment interest</a></li>
    <li><a href="https://mj.gouvernement.lu/fr/service-citoyens/taux-interet-legal.html" target="_blank" rel="noopener">Ministry of Justice – Legal interest rate in force</a></li>
    <li><a href="https://justice.public.lu/fr/creances/recouvrement-creances.html" target="_blank" rel="noopener">Justice.lu – Debt recovery</a></li>
</ul>

<p class="text-sm text-slate-500 mt-6"><em>Article updated on 30 July 2026.</em></p>

<div class="mt-8 p-4 bg-slate-50 rounded-xl"><h3 class="text-base font-semibold text-slate-900 mb-3">Related articles</h3><ul class="space-y-1"><li><a href="/en/blog/delais-paiement-luxembourg-cadre-legal-2026" class="text-primary-500 hover:text-primary-600 text-sm">Legal payment terms in LU →</a></li><li><a href="/en/blog/automatiser-facturation-7-conseils-gagner-temps" class="text-primary-500 hover:text-primary-600 text-sm">Automating your invoicing →</a></li></ul></div>

<div class="bg-primary-50 rounded-xl p-6 mt-8">
    <h3 class="text-lg font-semibold text-primary-900 mb-2">Automate your reminders with faktur.lu</h3>
    <p class="text-primary-800 mb-4">faktur.lu automatically detects overdue invoices and sends email reminders. Never let an unpaid invoice slip through the cracks again.</p>
    <a href="/register" class="inline-block bg-primary-500 text-white font-semibold px-6 py-3 rounded-xl hover:bg-primary-600">Try free for 14 days</a>
</div>
HTML;
    }

    private function lb(): string
    {
        $rate = $this->rateBlock('lb');

        return <<<HTML
<p class="lead">Onbezuelte Rechnunge sinn den Albdram vun all Entrepreneur. Zu Lëtzebuerg reegelt d'<a href="https://legilux.public.lu/eli/etat/leg/loi/2004/04/18/n8/jo" target="_blank" rel="noopener">Gesetz vum 18. Abrëll 2004</a> d'Bezuelfristen an d'Verzuchszënsen. Hei ass, wéi Dir effikass mahnt – vun der frëndlecher Erënnerung bis zur formeller Mise en demeure.</p>

<h2>Schrëtt 1: Déi frëndlech Erënnerung (D+7 no der Fälligkeet)</h2>

<p>Just vergiess? Dat ass dacks de Fall. Déi éischt Erënnerung soll <strong>héiflech a professionell</strong> sinn:</p>

<ul>
    <li>Schéckt eng E-Mail mat der Rechnungsnummer an dem Betrag</li>
    <li>Leet eng Kopie vun der Originalrechnung bäi</li>
    <li>Proposéiert eng nei Bezuelfrist, wann néideg</li>
    <li>Bleift sachlech a frëndlech</li>
</ul>

<p><strong>Tipp:</strong> Mat faktur.lu kënnt Dir dës éischt Erënnerung automatiséieren. De System erkennt iwwerfälleg Rechnungen a schéckt automatesch eng Mahnung per E-Mail.</p>

<h2>Schrëtt 2: Déi formell Mahnung (D+15)</h2>

<p>Wann déi éischt Erënnerung ouni Äntwert bleift, schéckt eng <strong>méi formell Mahnung</strong>:</p>

<ul>
    <li>Weist kloer op de Bezuelverzuch hin</li>
    <li>Erënnert un déi vereinbart Bezuelkonditiounen</li>
    <li>Weist drop hin, datt Verzuchszënse kënne berechent ginn</li>
    <li>Setzt eng präzis Frist (z. B. 8 Deeg)</li>
</ul>

<h2>Schrëtt 3: D'Mise en demeure (D+30)</h2>

<p>D'Mise en demeure ass e formellt Dokument an eng <strong>juristesch Beweismëttel</strong>. Och wann zu Lëtzebuerg keng Form virgeschriwwen ass, ass de Versand per <strong>Recommandé mat Réckschäin</strong> staark ze recommandéieren. Si muss enthalen:</p>

<ul>
    <li>D'Mentioun <strong>„Mise en demeure"</strong> am Betreff</li>
    <li>D'Detailer vun den onbezuelte Rechnungen (Nummeren, Betrag, Datumen)</li>
    <li>De Gesamtbetrag (Haaptfuerderung + eventuell Verzuchszënsen + forfaitaire Entschiedegung vun 40 €)</li>
    <li>Eng <strong>Frist vun 8 Deeg</strong> fir ze bezuelen (üblech Dauer)</li>
    <li>Den Hiweis, datt Dir Iech riichtlech Schrëtt virbehalt</li>
</ul>

<div class="bg-blue-50 border-l-4 border-blue-500 p-4 my-6">
    <p class="font-semibold">Wichteg</p>
    <p>Nom Gesetz vum 18. Abrëll 2004 lafen d'Verzuchszënse scho vun der vertraglecher Fälligkeet un, <strong>ouni datt eng formell Mise en demeure néideg wier</strong>. Si bleift awer nëtzlech, fir de Riichtswee ze goen an d'Fuerderung ze beleeën.</p>
</div>

{$rate}

<h2>Schrëtt 4: D'Bäitreiwung (D+60)</h2>

<p>Wa keng Mahnung eppes bréngt, hutt Dir méi Méiglechkeeten:</p>

<ul>
    <li><strong>Inkassosgesellschaft</strong>: iwwerhëlt d'Schrëtt géint eng Kommissioun (15–25 %)</li>
    <li><strong>Bezuelbefehl</strong>: vereinfacht Prozedur virum Friddensgeriicht (fir Fuerderungen ≤ 15 000 €)</li>
    <li><strong>Klo virum Geriicht</strong>: bei méi héije Beträg</li>
    <li><strong>Wirtschaftsmediatioun</strong>: méi séier a méi bëlleg Alternativ</li>
</ul>

<h2>Gutt Praktike fir Onbezuelten ze vermeiden</h2>

<ul>
    <li><strong>Séier fakturéieren</strong>: wat Dir méi laang waart, wat de Risiko méi grouss gëtt</li>
    <li><strong>Kloer Konditiounen</strong>: Bezuelfristen a -modalitéiten op all Rechnung uginn</li>
    <li><strong>Akontozuelung</strong>: bei grousse Missiounen 30–50 % am Viraus froen</li>
    <li><strong>Automatesch Mahnungen</strong>: eng Software wéi faktur.lu benotzen, fir keng Mahnung ze vergiessen</li>
    <li><strong>Solvabilitéit préiwen</strong>: bei neie Clienten den <a href="https://www.lbr.lu/" target="_blank" rel="noopener">Handelsregëster Lëtzebuerg (LBR)</a> konsultéieren</li>
</ul>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">All Joer ze préiwen</p>
    <p>De gesetzleche Zënssaz ännert all Hallefjoer, an d'forfaitaire Entschiedegung ka ugepasst ginn. Dës Säit gëtt reegelméisseg aktualiséiert – kuckt awer <a href="https://mj.gouvernement.lu/fr/service-citoyens/taux-interet-legal.html" target="_blank" rel="noopener">de gëltege Saz</a> am Moment vun Ärer Mahnung.</p>
</div>

<h2>Offiziell Quellen</h2>

<ul>
    <li><a href="https://legilux.public.lu/eli/etat/leg/loi/2004/04/18/n8/jo" target="_blank" rel="noopener">Gesetz vum 18. Abrëll 2004 iwwer d'Bezuelfristen</a></li>
    <li><a href="https://guichet.public.lu/fr/entreprises/gestion-juridique-comptabilite/facturation/encaissement/interets-retard.html" target="_blank" rel="noopener">Guichet.lu – Verzuchszënsen</a></li>
    <li><a href="https://mj.gouvernement.lu/fr/service-citoyens/taux-interet-legal.html" target="_blank" rel="noopener">Justizministère – gëltege gesetzleche Zënssaz</a></li>
    <li><a href="https://justice.public.lu/fr/creances/recouvrement-creances.html" target="_blank" rel="noopener">Justice.lu – Fuerderungsbäitreiwung</a></li>
</ul>

<p class="text-sm text-slate-500 mt-6"><em>Artikel aktualiséiert den 30. Juli 2026.</em></p>

<div class="mt-8 p-4 bg-slate-50 rounded-xl"><h3 class="text-base font-semibold text-slate-900 mb-3">Verbonnen Artikelen</h3><ul class="space-y-1"><li><a href="/lb/blog/delais-paiement-luxembourg-cadre-legal-2026" class="text-primary-500 hover:text-primary-600 text-sm">Gesetzlech Bezuelfristen zu LU →</a></li><li><a href="/lb/blog/automatiser-facturation-7-conseils-gagner-temps" class="text-primary-500 hover:text-primary-600 text-sm">Fakturatioun automatiséieren →</a></li></ul></div>

<div class="bg-primary-50 rounded-xl p-6 mt-8">
    <h3 class="text-lg font-semibold text-primary-900 mb-2">Automatiséiert Är Mahnunge mat faktur.lu</h3>
    <p class="text-primary-800 mb-4">faktur.lu erkennt automatesch iwwerfälleg Rechnungen a schéckt Mahnunge per E-Mail. Keng onbezuelte Rechnung geet méi vergiess.</p>
    <a href="/register" class="inline-block bg-primary-500 text-white font-semibold px-6 py-3 rounded-xl hover:bg-primary-600">14 Deeg gratis testen</a>
</div>
HTML;
    }

    private function pt(): string
    {
        $rate = $this->rateBlock('pt');

        return <<<HTML
<p class="lead">As faturas por pagar são o pesadelo de qualquer empresário. No Luxemburgo, a <a href="https://legilux.public.lu/eli/etat/leg/loi/2004/04/18/n8/jo" target="_blank" rel="noopener">lei de 18 de abril de 2004</a> enquadra os prazos de pagamento e os juros de mora. Eis como gerir as cobranças com eficácia, do lembrete amigável à interpelação formal.</p>

<h2>Etapa 1: O lembrete amigável (D+7 após o vencimento)</h2>

<p>Simples esquecimento? É frequentemente o caso. O primeiro lembrete deve ser <strong>cortês e profissional</strong>:</p>

<ul>
    <li>Envie um e-mail relembrando o número e o montante da fatura</li>
    <li>Anexe uma cópia da fatura original</li>
    <li>Proponha um novo prazo de pagamento, se necessário</li>
    <li>Mantenha-se factual e cordial</li>
</ul>

<p><strong>Conselho:</strong> com o faktur.lu pode automatizar este primeiro lembrete. O sistema deteta as faturas em atraso e envia automaticamente um e-mail de cobrança.</p>

<h2>Etapa 2: A cobrança formal (D+15)</h2>

<p>Se o primeiro lembrete ficar sem resposta, envie uma <strong>cobrança mais formal</strong>:</p>

<ul>
    <li>Refira claramente o atraso de pagamento</li>
    <li>Relembre as condições de pagamento acordadas</li>
    <li>Indique que poderão ser aplicados juros de mora</li>
    <li>Fixe um prazo preciso (por exemplo, 8 dias)</li>
</ul>

<h2>Etapa 3: A interpelação formal (D+30)</h2>

<p>A interpelação formal (<em>mise en demeure</em>) é um documento oficial que constitui uma <strong>prova jurídica</strong>. Embora o Luxemburgo não imponha qualquer forma legal, o envio por <strong>carta registada com aviso de receção</strong> é vivamente recomendado. Deve conter:</p>

<ul>
    <li>A menção <strong>«Interpelação formal»</strong> no assunto</li>
    <li>O detalhe das faturas por pagar (números, montantes, datas)</li>
    <li>O montante total devido (capital + eventuais juros de mora + indemnização fixa de 40 €)</li>
    <li>Um <strong>prazo de 8 dias</strong> para regularizar (duração habitual)</li>
    <li>A menção de que se reserva o direito de intentar uma ação judicial</li>
</ul>

<div class="bg-blue-50 border-l-4 border-blue-500 p-4 my-6">
    <p class="font-semibold">A reter</p>
    <p>Ao abrigo da lei de 18 de abril de 2004, os juros de mora começam a correr a partir do vencimento contratual, <strong>sem que seja necessária uma interpelação formal</strong>. Esta continua, no entanto, a ser útil para abrir a via judicial e comprovar o crédito.</p>
</div>

{$rate}

<h2>Etapa 4: A cobrança coerciva (D+60)</h2>

<p>Se todas as cobranças falharem, dispõe de várias opções:</p>

<ul>
    <li><strong>Empresa de cobrança</strong>: encarrega-se das diligências mediante comissão (15–25 %)</li>
    <li><strong>Injunção de pagamento</strong>: procedimento simplificado perante o juiz de paz (para créditos ≤ 15 000 €)</li>
    <li><strong>Ação em tribunal</strong>: para montantes mais elevados</li>
    <li><strong>Mediação comercial</strong>: alternativa mais rápida e menos onerosa</li>
</ul>

<h2>Boas práticas para evitar incumprimentos</h2>

<ul>
    <li><strong>Fature rapidamente</strong>: quanto mais esperar, maior o risco de incumprimento</li>
    <li><strong>Condições claras</strong>: indique prazos e modalidades de pagamento em cada fatura</li>
    <li><strong>Adiantamento</strong>: peça 30–50 % de sinal nas prestações de maior dimensão</li>
    <li><strong>Cobranças automáticas</strong>: use um software como o faktur.lu para nunca se esquecer de cobrar</li>
    <li><strong>Verifique a solvabilidade</strong>: para novos clientes, consulte o <a href="https://www.lbr.lu/" target="_blank" rel="noopener">Registo Comercial do Luxemburgo (LBR)</a></li>
</ul>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">A verificar todos os anos</p>
    <p>A taxa de juro legal evolui semestralmente e a indemnização fixa pode ser revista. Esta página é atualizada regularmente, mas consulte <a href="https://mj.gouvernement.lu/fr/service-citoyens/taux-interet-legal.html" target="_blank" rel="noopener">a taxa em vigor</a> no momento da sua cobrança.</p>
</div>

<h2>Fontes oficiais</h2>

<ul>
    <li><a href="https://legilux.public.lu/eli/etat/leg/loi/2004/04/18/n8/jo" target="_blank" rel="noopener">Lei de 18 de abril de 2004 sobre os prazos de pagamento</a></li>
    <li><a href="https://guichet.public.lu/fr/entreprises/gestion-juridique-comptabilite/facturation/encaissement/interets-retard.html" target="_blank" rel="noopener">Guichet.lu – Juros de mora</a></li>
    <li><a href="https://mj.gouvernement.lu/fr/service-citoyens/taux-interet-legal.html" target="_blank" rel="noopener">Ministério da Justiça – Taxa de juro legal em vigor</a></li>
    <li><a href="https://justice.public.lu/fr/creances/recouvrement-creances.html" target="_blank" rel="noopener">Justice.lu – Cobrança de créditos</a></li>
</ul>

<p class="text-sm text-slate-500 mt-6"><em>Artigo atualizado a 30 de julho de 2026.</em></p>

<div class="mt-8 p-4 bg-slate-50 rounded-xl"><h3 class="text-base font-semibold text-slate-900 mb-3">Artigos relacionados</h3><ul class="space-y-1"><li><a href="/pt/blog/delais-paiement-luxembourg-cadre-legal-2026" class="text-primary-500 hover:text-primary-600 text-sm">Prazos de pagamento legais no LU →</a></li><li><a href="/pt/blog/automatiser-facturation-7-conseils-gagner-temps" class="text-primary-500 hover:text-primary-600 text-sm">Automatizar a faturação →</a></li></ul></div>

<div class="bg-primary-50 rounded-xl p-6 mt-8">
    <h3 class="text-lg font-semibold text-primary-900 mb-2">Automatize as suas cobranças com o faktur.lu</h3>
    <p class="text-primary-800 mb-4">O faktur.lu deteta automaticamente as faturas em atraso e envia cobranças por e-mail. Nunca mais deixe uma fatura por pagar cair no esquecimento.</p>
    <a href="/register" class="inline-block bg-primary-500 text-white font-semibold px-6 py-3 rounded-xl hover:bg-primary-600">Experimentar 14 dias grátis</a>
</div>
HTML;
    }
};
