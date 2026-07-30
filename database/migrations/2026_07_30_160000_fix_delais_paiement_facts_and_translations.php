<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Article « delais-paiement-luxembourg-cadre-legal-2026 » —
 * vérification factuelle et traductions intégrales.
 *
 * TROIS CORRECTIONS :
 *
 * 1. OMISSION MATÉRIELLE — l'article présentait « taux BCE + 8 points »
 *    comme LE taux des intérêts de retard, sans préciser qu'il ne vaut
 *    qu'entre professionnels. Face à un client particulier, c'est le taux
 *    d'intérêt légal qui s'applique : 3,75 % en 2026, contre 10,15 % en
 *    commercial. Un indépendant facturant des consommateurs pouvait donc
 *    réclamer un taux près de trois fois trop élevé — réclamation
 *    contestable et crédibilité entamée face au client.
 *
 * 2. BASE LÉGALE INCOMPLÈTE — seule la « loi du 18 avril 2004 » était
 *    citée. Or les +8 points et l'indemnité de 40 € viennent de la
 *    directive 2011/7/UE, transposée par la loi du 29 mars 2013 qui a
 *    modifié celle de 2004 (le régime de 2004, issu de la directive
 *    2000/35/CE, prévoyait +7 points et pas d'indemnité forfaitaire).
 *
 * 3. TAUX VAGUE ET DATÉ — « ~10 % annuel (BCE 2,15 % + 8 points au
 *    2e semestre 2025) » dans un article intitulé « cadre légal 2026 ».
 *    Remplacé par le dernier taux officiellement publié, daté, avec la
 *    formule et le lien vers la source pour le semestre en cours.
 *
 * FAITS VÉRIFIÉS et confirmés exacts (guichet.lu, Ministère de la Justice,
 * Chambre de Commerce) :
 * - B2B : 30 jours par défaut, 60 jours maximum contractuel, au-delà
 *   uniquement en l'absence d'abus manifeste.
 * - B2G : 30 jours, extension à 60 jours maximum si objectivement
 *   justifiée par la nature du contrat ; vérification plafonnée à 30 jours.
 * - Point de départ du délai : réception de la facture, ou réception des
 *   biens/services, ou acceptation/vérification.
 * - Intérêts dus automatiquement, sans mise en demeure.
 * - Indemnité forfaitaire de 40 €, sans justificatif, frais réels
 *   supérieurs réclamables sur pièces.
 *
 * ⚠️ Le taux commercial est révisé chaque semestre au Mémorial B. Celui du
 * 2e semestre 2026 n'était pas encore publié à la rédaction : l'article
 * donne donc la formule, le dernier taux daté et le lien officiel, plutôt
 * qu'un chiffre qui se périmerait.
 *
 * ⚠️ La version luxembourgeoise mérite une relecture par un locuteur natif.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['fr' => $this->fr(), 'de' => $this->de(), 'en' => $this->en(), 'lb' => $this->lb(), 'pt' => $this->pt()] as $locale => $content) {
            DB::table('blog_posts')
                ->where('translation_key', 'delais-paiement-luxembourg-cadre-legal-2026')
                ->where('locale', $locale)
                ->update(['content' => $content, 'updated_at' => now()]);
        }
    }

    public function down(): void
    {
        // Contenu rédactionnel : la version antérieure omettait la
        // distinction professionnel/consommateur, la restaurer serait
        // réintroduire l'erreur.
    }

    private function fr(): string
    {
        return <<<'HTML'
<p class="lead">Au Luxembourg, les délais de paiement sont encadrés par la loi. Que vous facturiez à une entreprise, à une administration ou à un particulier, voici les règles à connaître pour protéger votre trésorerie.</p>

<h2>Délais légaux de paiement</h2>

<h3>Transactions B2B (entre entreprises)</h3>

<p>La <strong>loi modifiée du 18 avril 2004</strong> relative aux délais de paiement et aux intérêts de retard fixe les règles suivantes :</p>

<ul>
    <li><strong>Délai par défaut</strong> : 30 jours à compter de la réception de la facture</li>
    <li><strong>Délai maximum contractuel</strong> : 60 jours</li>
    <li>Les parties peuvent convenir d'un délai <strong>supérieur à 60 jours</strong> uniquement si cela ne constitue pas un <strong>abus manifeste</strong> à l'égard du créancier</li>
</ul>

<p>Cette loi a été <strong>modifiée par la loi du 29 mars 2013</strong>, qui transpose la directive 2011/7/UE. C'est cette révision qui a porté la majoration à 8 points et créé l'indemnité forfaitaire de 40 EUR : les articles qui citent encore le seul texte de 2004 décrivent un régime dépassé.</p>

<h3>Transactions B2G (avec le secteur public)</h3>

<p>Les délais sont plus stricts pour les administrations :</p>

<ul>
    <li><strong>Délai maximum</strong> : 30 jours à compter de la réception de la facture</li>
    <li>Ce délai peut être <strong>étendu à 60 jours maximum</strong> si l'allongement est expressément prévu au contrat et objectivement justifié par la nature de celui-ci</li>
    <li>La procédure d'acceptation ou de vérification ne peut excéder <strong>30 jours</strong></li>
</ul>

<h2>À partir de quand le délai court-il ?</h2>

<p>Le délai de paiement commence à courir à partir de :</p>

<ul>
    <li>La <strong>date de réception de la facture</strong> par le débiteur</li>
    <li>Ou la <strong>date de réception des biens/services</strong>, si la date de la facture est incertaine ou si celle-ci a été envoyée avant</li>
    <li>Ou la <strong>date d'acceptation ou de vérification</strong>, lorsqu'une telle procédure est prévue par le contrat ou la loi</li>
</ul>

<p><strong>Conseil :</strong> mentionnez toujours la <strong>date d'échéance</strong> clairement sur votre facture. Avec faktur.lu, la date d'échéance est calculée automatiquement (30 jours par défaut, personnalisable).</p>

<h2>Intérêts de retard</h2>

<p>En cas de retard, des intérêts sont dus <strong>automatiquement</strong>, sans qu'il soit nécessaire d'envoyer une mise en demeure. Le taux n'est en revanche <strong>pas le même selon la qualité de votre client</strong> — c'est la confusion la plus fréquente.</p>

<h3>Votre client est un professionnel</h3>

<ul>
    <li><strong>Formule</strong> : taux de référence de la BCE publié au Mémorial B en début de semestre, <strong>majoré de 8 points de pourcentage</strong></li>
    <li><strong>1<sup>er</sup> semestre 2026</strong> : <strong>10,15 %</strong> (2,15 % + 8)</li>
    <li>Le taux est <strong>révisé chaque semestre</strong> : vérifiez celui en vigueur avant de chiffrer une réclamation</li>
    <li>Les intérêts courent à partir du jour suivant la date d'échéance</li>
</ul>

<h3>Votre client est un particulier</h3>

<p>Le régime des transactions commerciales ne s'applique pas. C'est le <strong>taux d'intérêt légal</strong> qui joue, fixé annuellement : <strong>3,75 % pour 2026</strong>.</p>

<div class="bg-blue-50 border-l-4 border-blue-500 p-4 my-6">
    <p class="font-semibold">L'écart est important</p>
    <p>10,15 % contre 3,75 % : réclamer le taux commercial à un consommateur, c'est demander près du triple de ce qui est dû. La réclamation devient contestable, et votre crédibilité en souffre au moment précis où vous avez besoin d'être pris au sérieux.</p>
</div>

<h2>Indemnité forfaitaire de recouvrement</h2>

<p>En plus des intérêts de retard, le créancier a droit à une <strong>indemnité forfaitaire de 40 EUR</strong> pour frais de recouvrement, due <strong>par facture impayée</strong>, automatiquement et sans justificatif de dépenses.</p>

<p>Si les frais de recouvrement réels dépassent 40 EUR, le créancier peut réclamer le montant effectif sur présentation de justificatifs.</p>

<p>Cette indemnité relève elle aussi du régime des transactions commerciales : elle ne se réclame pas à un client particulier.</p>

<h2>Bonnes pratiques sur vos factures</h2>

<p>Pour vous protéger en cas de litige, mentionnez sur chaque facture :</p>

<ul>
    <li>La <strong>date d'échéance</strong> précise (pas seulement "30 jours")</li>
    <li>Les <strong>modalités de paiement</strong> (virement, avec IBAN)</li>
    <li>La mention des <strong>intérêts de retard</strong> applicables</li>
    <li>La mention de l'<strong>indemnité forfaitaire de 40 EUR</strong> pour vos clients professionnels</li>
</ul>

<h2>Sources officielles</h2>

<ul>
    <li><a href="https://guichet.public.lu/fr/entreprises/gestion-juridique-comptabilite/facturation/encaissement/interets-retard.html" target="_blank" rel="noopener">Guichet.lu - Délais de paiement et intérêts de retard</a></li>
    <li><a href="https://mj.gouvernement.lu/fr/service-citoyens/taux-interet-legal.html" target="_blank" rel="noopener">Ministère de la Justice - Taux d'intérêt légal (mis à jour chaque semestre)</a></li>
    <li><a href="https://data.legilux.public.lu/filestore/eli/etat/leg/loi/2004/04/18/n8/jo/fr/html/eli-etat-leg-loi-2004-04-18-n8-jo-fr-html.html" target="_blank" rel="noopener">Legilux - Loi du 18 avril 2004 (texte d'origine)</a></li>
</ul>

<div class="mt-8 p-4 bg-slate-50 rounded-xl"><h3 class="text-base font-semibold text-slate-900 mb-3">Articles connexes</h3><ul class="space-y-1"><li><a href="/fr/blog/relancer-client-impaye-luxembourg" class="text-primary-500 hover:text-primary-600 text-sm">relancer un client →</a></li><li><a href="/fr/blog/mentions-obligatoires-facture-luxembourg" class="text-primary-500 hover:text-primary-600 text-sm">mentions obligatoires →</a></li></ul></div>

<div class="bg-primary-50 rounded-xl p-6 mt-8">
    <h3 class="text-lg font-semibold text-primary-900 mb-2">Gérez vos délais de paiement avec faktur.lu</h3>
    <p class="text-primary-800 mb-4">faktur.lu calcule automatiquement les dates d'échéance, détecte les retards et envoie des relances. Gardez le contrôle de votre trésorerie.</p>
    <a href="/register" class="inline-block bg-primary-500 text-white font-semibold px-6 py-3 rounded-xl hover:bg-primary-600">Essayer gratuitement 14 jours</a>
</div>

<p class="text-sm text-slate-500 mt-6"><em>Article mis à jour le 30 juillet 2026.</em></p>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">À vérifier chaque semestre</p>
    <p>Le taux applicable aux transactions commerciales change deux fois par an. Cette page est mise à jour régulièrement, mais avant de chiffrer une réclamation, vérifiez le taux du semestre en cours auprès du <a href="https://mj.gouvernement.lu/fr/service-citoyens/taux-interet-legal.html" target="_blank" rel="noopener">Ministère de la Justice</a>, et pour votre situation personnelle, consultez votre fiduciaire.</p>
</div>
HTML;
    }

    private function de(): string
    {
        return <<<'HTML'
<p class="lead">In Luxemburg sind die Zahlungsfristen gesetzlich geregelt. Ob Sie einem Unternehmen, einer Verwaltung oder einer Privatperson eine Rechnung stellen — hier sind die Regeln, die Ihre Liquidität schützen.</p>

<h2>Gesetzliche Zahlungsfristen</h2>

<h3>B2B-Transaktionen (zwischen Unternehmen)</h3>

<p>Das <strong>geänderte Gesetz vom 18. April 2004</strong> über Zahlungsfristen und Verzugszinsen legt Folgendes fest:</p>

<ul>
    <li><strong>Standardfrist</strong>: 30 Tage ab Eingang der Rechnung</li>
    <li><strong>Vertragliche Höchstfrist</strong>: 60 Tage</li>
    <li>Die Parteien können eine Frist von <strong>über 60 Tagen</strong> nur vereinbaren, wenn dies keinen <strong>offensichtlichen Missbrauch</strong> gegenüber dem Gläubiger darstellt</li>
</ul>

<p>Dieses Gesetz wurde durch das <strong>Gesetz vom 29. März 2013</strong> geändert, das die Richtlinie 2011/7/EU umsetzt. Erst diese Revision hat den Aufschlag auf 8 Punkte angehoben und die Pauschale von 40 EUR eingeführt: Artikel, die nur den Text von 2004 zitieren, beschreiben eine überholte Regelung.</p>

<h3>B2G-Transaktionen (mit dem öffentlichen Sektor)</h3>

<p>Für Behörden gelten strengere Fristen:</p>

<ul>
    <li><strong>Höchstfrist</strong>: 30 Tage ab Eingang der Rechnung</li>
    <li>Diese Frist kann auf <strong>höchstens 60 Tage</strong> verlängert werden, wenn dies ausdrücklich vertraglich vorgesehen und durch die Natur des Vertrags objektiv gerechtfertigt ist</li>
    <li>Das Abnahme- oder Überprüfungsverfahren darf <strong>30 Tage</strong> nicht überschreiten</li>
</ul>

<h2>Ab wann läuft die Frist?</h2>

<p>Die Zahlungsfrist beginnt zu laufen ab:</p>

<ul>
    <li>Dem <strong>Eingangsdatum der Rechnung</strong> beim Schuldner</li>
    <li>Oder dem <strong>Empfangsdatum der Waren/Dienstleistungen</strong>, wenn das Rechnungsdatum ungewiss ist oder die Rechnung vorher versandt wurde</li>
    <li>Oder dem <strong>Datum der Abnahme oder Überprüfung</strong>, wenn ein solches Verfahren vertraglich oder gesetzlich vorgesehen ist</li>
</ul>

<p><strong>Tipp:</strong> Geben Sie das <strong>Fälligkeitsdatum</strong> stets deutlich auf Ihrer Rechnung an. Mit faktur.lu wird es automatisch berechnet (standardmäßig 30 Tage, anpassbar).</p>

<h2>Verzugszinsen</h2>

<p>Bei Verzug sind Zinsen <strong>automatisch</strong> geschuldet, ohne dass eine Mahnung erforderlich wäre. Der Satz ist jedoch <strong>nicht derselbe, je nachdem wer Ihr Kunde ist</strong> — das ist die häufigste Verwechslung.</p>

<h3>Ihr Kunde ist ein Unternehmer</h3>

<ul>
    <li><strong>Formel</strong>: der zu Beginn jedes Halbjahres im Mémorial B veröffentlichte EZB-Referenzsatz, <strong>zuzüglich 8 Prozentpunkte</strong></li>
    <li><strong>1. Halbjahr 2026</strong>: <strong>10,15 %</strong> (2,15 % + 8)</li>
    <li>Der Satz wird <strong>halbjährlich angepasst</strong>: prüfen Sie den geltenden Satz, bevor Sie eine Forderung beziffern</li>
    <li>Die Zinsen laufen ab dem Tag nach der Fälligkeit</li>
</ul>

<h3>Ihr Kunde ist eine Privatperson</h3>

<p>Die Regelung für Handelsgeschäfte gilt nicht. Maßgeblich ist der <strong>gesetzliche Zinssatz</strong>, der jährlich festgelegt wird: <strong>3,75 % für 2026</strong>.</p>

<div class="bg-blue-50 border-l-4 border-blue-500 p-4 my-6">
    <p class="font-semibold">Der Unterschied ist erheblich</p>
    <p>10,15 % gegenüber 3,75 %: Wer einem Verbraucher den Handelssatz berechnet, fordert fast das Dreifache des Geschuldeten. Die Forderung wird angreifbar, und Ihre Glaubwürdigkeit leidet genau dann, wenn Sie ernst genommen werden müssen.</p>
</div>

<h2>Pauschale Beitreibungsentschädigung</h2>

<p>Zusätzlich zu den Verzugszinsen hat der Gläubiger Anspruch auf eine <strong>Pauschale von 40 EUR</strong> für Beitreibungskosten, geschuldet <strong>je unbezahlter Rechnung</strong>, automatisch und ohne Kostennachweis.</p>

<p>Übersteigen die tatsächlichen Beitreibungskosten 40 EUR, kann der Gläubiger den effektiven Betrag gegen Vorlage von Belegen verlangen.</p>

<p>Auch diese Pauschale gehört zur Regelung für Handelsgeschäfte: Sie ist gegenüber einem privaten Kunden nicht zu fordern.</p>

<h2>Bewährte Praxis auf Ihren Rechnungen</h2>

<p>Um sich im Streitfall abzusichern, geben Sie auf jeder Rechnung an:</p>

<ul>
    <li>Das genaue <strong>Fälligkeitsdatum</strong> (nicht nur „30 Tage")</li>
    <li>Die <strong>Zahlungsmodalitäten</strong> (Überweisung, mit IBAN)</li>
    <li>Den Hinweis auf die geltenden <strong>Verzugszinsen</strong></li>
    <li>Den Hinweis auf die <strong>Pauschale von 40 EUR</strong> bei gewerblichen Kunden</li>
</ul>

<h2>Offizielle Quellen</h2>

<ul>
    <li><a href="https://guichet.public.lu/fr/entreprises/gestion-juridique-comptabilite/facturation/encaissement/interets-retard.html" target="_blank" rel="noopener">Guichet.lu – Zahlungsfristen und Verzugszinsen</a></li>
    <li><a href="https://mj.gouvernement.lu/fr/service-citoyens/taux-interet-legal.html" target="_blank" rel="noopener">Justizministerium – Gesetzlicher Zinssatz (halbjährlich aktualisiert)</a></li>
    <li><a href="https://data.legilux.public.lu/filestore/eli/etat/leg/loi/2004/04/18/n8/jo/fr/html/eli-etat-leg-loi-2004-04-18-n8-jo-fr-html.html" target="_blank" rel="noopener">Legilux – Gesetz vom 18. April 2004 (Ursprungstext)</a></li>
</ul>

<div class="mt-8 p-4 bg-slate-50 rounded-xl"><h3 class="text-base font-semibold text-slate-900 mb-3">Verwandte Artikel</h3><ul class="space-y-1"><li><a href="/de/blog/relancer-client-impaye-luxembourg" class="text-primary-500 hover:text-primary-600 text-sm">Einen Kunden mahnen →</a></li><li><a href="/de/blog/mentions-obligatoires-facture-luxembourg" class="text-primary-500 hover:text-primary-600 text-sm">Pflichtangaben →</a></li></ul></div>

<div class="bg-primary-50 rounded-xl p-6 mt-8">
    <h3 class="text-lg font-semibold text-primary-900 mb-2">Verwalten Sie Ihre Zahlungsfristen mit faktur.lu</h3>
    <p class="text-primary-800 mb-4">faktur.lu berechnet Fälligkeitsdaten automatisch, erkennt Verzögerungen und versendet Mahnungen. Behalten Sie Ihre Liquidität im Griff.</p>
    <a href="/register" class="inline-block bg-primary-500 text-white font-semibold px-6 py-3 rounded-xl hover:bg-primary-600">14 Tage kostenlos testen</a>
</div>

<p class="text-sm text-slate-500 mt-6"><em>Artikel aktualisiert am 30. Juli 2026.</em></p>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">Halbjährlich zu prüfen</p>
    <p>Der für Handelsgeschäfte geltende Satz ändert sich zweimal jährlich. Diese Seite wird regelmäßig aktualisiert — prüfen Sie aber vor der Bezifferung einer Forderung den Satz des laufenden Halbjahres beim <a href="https://mj.gouvernement.lu/fr/service-citoyens/taux-interet-legal.html" target="_blank" rel="noopener">Justizministerium</a>, und wenden Sie sich für Ihre persönliche Situation an Ihren Treuhänder.</p>
</div>
HTML;
    }

    private function en(): string
    {
        return <<<'HTML'
<p class="lead">In Luxembourg, payment terms are governed by law. Whether you invoice a business, a public authority or a private individual, here are the rules that protect your cash flow.</p>

<h2>Statutory payment terms</h2>

<h3>B2B transactions (between businesses)</h3>

<p>The <strong>amended law of 18 April 2004</strong> on payment terms and late payment interest sets out the following rules:</p>

<ul>
    <li><strong>Default term</strong>: 30 days from receipt of the invoice</li>
    <li><strong>Maximum contractual term</strong>: 60 days</li>
    <li>The parties may agree on a term <strong>longer than 60 days</strong> only where this does not constitute a <strong>manifest abuse</strong> towards the creditor</li>
</ul>

<p>That law was <strong>amended by the law of 29 March 2013</strong>, which transposes Directive 2011/7/EU. It is this revision that raised the margin to 8 points and created the €40 fixed compensation: articles still citing only the 2004 text describe a superseded regime.</p>

<h3>B2G transactions (with the public sector)</h3>

<p>Terms are stricter for public authorities:</p>

<ul>
    <li><strong>Maximum term</strong>: 30 days from receipt of the invoice</li>
    <li>This may be <strong>extended to 60 days maximum</strong> where expressly agreed in the contract and objectively justified by its particular nature</li>
    <li>The acceptance or verification procedure may not exceed <strong>30 days</strong></li>
</ul>

<h2>When does the clock start?</h2>

<p>The payment period starts running from:</p>

<ul>
    <li>The <strong>date the debtor receives the invoice</strong></li>
    <li>Or the <strong>date the goods/services are received</strong>, where the invoice date is uncertain or the invoice was sent beforehand</li>
    <li>Or the <strong>date of acceptance or verification</strong>, where such a procedure is provided for by contract or by law</li>
</ul>

<p><strong>Tip:</strong> always state the <strong>due date</strong> clearly on your invoice. With faktur.lu it is calculated automatically (30 days by default, customisable).</p>

<h2>Late payment interest</h2>

<p>In the event of late payment, interest is due <strong>automatically</strong>, with no need to send a formal notice. The rate, however, is <strong>not the same depending on who your client is</strong> — this is the most common confusion.</p>

<h3>Your client is a business</h3>

<ul>
    <li><strong>Formula</strong>: the ECB reference rate published in the Mémorial B at the start of each half-year, <strong>plus 8 percentage points</strong></li>
    <li><strong>First half of 2026</strong>: <strong>10.15%</strong> (2.15% + 8)</li>
    <li>The rate is <strong>revised every six months</strong>: check the one in force before quantifying a claim</li>
    <li>Interest runs from the day after the due date</li>
</ul>

<h3>Your client is a private individual</h3>

<p>The commercial transactions regime does not apply. The <strong>statutory interest rate</strong> governs instead, set annually: <strong>3.75% for 2026</strong>.</p>

<div class="bg-blue-50 border-l-4 border-blue-500 p-4 my-6">
    <p class="font-semibold">The gap matters</p>
    <p>10.15% against 3.75%: charging a consumer the commercial rate means claiming nearly three times what is owed. The claim becomes contestable, and your credibility suffers at exactly the moment you need to be taken seriously.</p>
</div>

<h2>Fixed recovery compensation</h2>

<p>On top of late payment interest, the creditor is entitled to <strong>fixed compensation of €40</strong> for recovery costs, due <strong>per unpaid invoice</strong>, automatically and without any proof of expenditure.</p>

<p>Where actual recovery costs exceed €40, the creditor may claim the effective amount on production of supporting documents.</p>

<p>This compensation also belongs to the commercial transactions regime: it cannot be claimed from a private client.</p>

<h2>Best practice on your invoices</h2>

<p>To protect yourself in the event of a dispute, state on every invoice:</p>

<ul>
    <li>The precise <strong>due date</strong> (not merely "30 days")</li>
    <li>The <strong>payment details</strong> (bank transfer, with IBAN)</li>
    <li>A mention of the applicable <strong>late payment interest</strong></li>
    <li>A mention of the <strong>€40 fixed compensation</strong> for your business clients</li>
</ul>

<h2>Official sources</h2>

<ul>
    <li><a href="https://guichet.public.lu/fr/entreprises/gestion-juridique-comptabilite/facturation/encaissement/interets-retard.html" target="_blank" rel="noopener">Guichet.lu - Payment terms and late payment interest</a></li>
    <li><a href="https://mj.gouvernement.lu/fr/service-citoyens/taux-interet-legal.html" target="_blank" rel="noopener">Ministry of Justice - Statutory interest rate (updated each half-year)</a></li>
    <li><a href="https://data.legilux.public.lu/filestore/eli/etat/leg/loi/2004/04/18/n8/jo/fr/html/eli-etat-leg-loi-2004-04-18-n8-jo-fr-html.html" target="_blank" rel="noopener">Legilux - Law of 18 April 2004 (original text)</a></li>
</ul>

<div class="mt-8 p-4 bg-slate-50 rounded-xl"><h3 class="text-base font-semibold text-slate-900 mb-3">Related articles</h3><ul class="space-y-1"><li><a href="/en/blog/relancer-client-impaye-luxembourg" class="text-primary-500 hover:text-primary-600 text-sm">Chasing an unpaid client →</a></li><li><a href="/en/blog/mentions-obligatoires-facture-luxembourg" class="text-primary-500 hover:text-primary-600 text-sm">Mandatory invoice details →</a></li></ul></div>

<div class="bg-primary-50 rounded-xl p-6 mt-8">
    <h3 class="text-lg font-semibold text-primary-900 mb-2">Manage your payment terms with faktur.lu</h3>
    <p class="text-primary-800 mb-4">faktur.lu calculates due dates automatically, detects late payments and sends reminders. Stay in control of your cash flow.</p>
    <a href="/register" class="inline-block bg-primary-500 text-white font-semibold px-6 py-3 rounded-xl hover:bg-primary-600">Try free for 14 days</a>
</div>

<p class="text-sm text-slate-500 mt-6"><em>Article updated on 30 July 2026.</em></p>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">To check every six months</p>
    <p>The rate applying to commercial transactions changes twice a year. This page is updated regularly, but before quantifying a claim, check the current half-year's rate with the <a href="https://mj.gouvernement.lu/fr/service-citoyens/taux-interet-legal.html" target="_blank" rel="noopener">Ministry of Justice</a>, and for your own situation consult your accountant.</p>
</div>
HTML;
    }

    private function lb(): string
    {
        return <<<'HTML'
<p class="lead">Zu Lëtzebuerg sinn d'Bezuelungsfriste gesetzlech gereegelt. Egal ob Dir enger Entreprise, enger Verwaltung oder enger Privatpersoun eng Rechnung stellt — hei sinn d'Reegelen, déi Är Tresorerie schützen.</p>

<h2>Gesetzlech Bezuelungsfristen</h2>

<h3>B2B-Transaktiounen (tëscht Entreprisen)</h3>

<p>D'<strong>geännert Gesetz vum 18. Abrëll 2004</strong> iwwer d'Bezuelungsfristen an d'Verzuchszënse leet Folgendes fest:</p>

<ul>
    <li><strong>Standardfrist</strong>: 30 Deeg vum Empfang vun der Rechnung un</li>
    <li><strong>Maximal vertraglech Frist</strong>: 60 Deeg</li>
    <li>D'Parteie kënnen eng Frist vun <strong>iwwer 60 Deeg</strong> nëmme vereinbaren, wann dat kee <strong>offensichtleche Mëssbrauch</strong> géintiwwer dem Gleewege duerstellt</li>
</ul>

<p>Dëst Gesetz gouf duerch d'<strong>Gesetz vum 29. Mäerz 2013</strong> geännert, dat d'Direktiv 2011/7/EU ëmsetzt. Eréischt dës Revisioun huet den Zouschlag op 8 Punkte gehéicht an d'Pauschal vun 40 EUR agefouert: Artikelen, déi nëmmen den Text vun 2004 zitéieren, beschreiwen eng iwwerhuelt Reegelung.</p>

<h3>B2G-Transaktiounen (mam ëffentleche Secteur)</h3>

<p>Fir Verwaltunge gëlle méi strikt Fristen:</p>

<ul>
    <li><strong>Maximal Frist</strong>: 30 Deeg vum Empfang vun der Rechnung un</li>
    <li>Dës Frist kann op <strong>maximal 60 Deeg</strong> verlängert ginn, wann dat ausdrécklech am Kontrakt virgesinn an duerch d'Natur vun deem Kontrakt objektiv gerechtfäerdegt ass</li>
    <li>D'Ofnam- oder Iwwerpréifungsprozedur däerf <strong>30 Deeg</strong> net iwwerschreiden</li>
</ul>

<h2>Vu wéini leeft d'Frist?</h2>

<p>D'Bezuelungsfrist fänkt un ze lafen ab:</p>

<ul>
    <li>Dem <strong>Datum vum Empfang vun der Rechnung</strong> beim Schëllner</li>
    <li>Oder dem <strong>Datum vum Empfang vun de Wueren/Déngschtleeschtungen</strong>, wann d'Datum vun der Rechnung onsécher ass oder d'Rechnung virdru geschéckt gouf</li>
    <li>Oder dem <strong>Datum vun der Ofnam oder der Iwwerpréifung</strong>, wann esou eng Prozedur am Kontrakt oder am Gesetz virgesinn ass</li>
</ul>

<p><strong>Tipp:</strong> Gitt d'<strong>Fälegkeetsdatum</strong> ëmmer kloer op Ärer Rechnung un. Mat faktur.lu gëtt et automatesch berechent (standardméisseg 30 Deeg, upassbar).</p>

<h2>Verzuchszënsen</h2>

<p>Bei Verzuch si Zënsen <strong>automatesch</strong> geschëllt, ouni datt eng Mise en demeure néideg wier. De Taux ass awer <strong>net dee selwechte je nodeem wien Äre Client ass</strong> — dat ass déi heefegst Verwiesslung.</p>

<h3>Äre Client ass e Professionnel</h3>

<ul>
    <li><strong>Formel</strong>: den Referenztaux vun der BCE, deen am Ufank vun all Semester am Mémorial B publizéiert gëtt, <strong>plus 8 Prozentpunkten</strong></li>
    <li><strong>1. Semester 2026</strong>: <strong>10,15 %</strong> (2,15 % + 8)</li>
    <li>Den Taux gëtt <strong>all Semester ugepasst</strong>: iwwerpréift dee gëltegen, ier Dir eng Fuerderung bezifferet</li>
    <li>D'Zënse lafen ab dem Dag no der Fälegkeet</li>
</ul>

<h3>Äre Client ass eng Privatpersoun</h3>

<p>D'Reegelung fir Handelstransaktioune gëllt net. Et ass de <strong>gesetzlechen Zënssaz</strong>, dee spillt, all Joer festgeluecht: <strong>3,75 % fir 2026</strong>.</p>

<div class="bg-blue-50 border-l-4 border-blue-500 p-4 my-6">
    <p class="font-semibold">Den Ënnerscheed ass bedeitend</p>
    <p>10,15 % géint 3,75 %: Wien engem Konsument den Handelstaux berechent, freet bal dat Dräifacht vun deem, wat geschëllt ass. D'Fuerderung gëtt ufechtbar, an Är Glafwierdegkeet leit genee an deem Moment, wou Dir eescht geholl musst ginn.</p>
</div>

<h2>Pauschal Recouvrementsentschiedegung</h2>

<p>Zousätzlech zu de Verzuchszënsen huet de Gleewege Recht op eng <strong>Pauschal vun 40 EUR</strong> fir Recouvrementskäschten, geschëllt <strong>pro onbezuelter Rechnung</strong>, automatesch an ouni Käschtennoweis.</p>

<p>Wann déi tatsächlech Recouvrementskäschten 40 EUR iwwerschreiden, kann de Gleewege de effektive Betrag géint Virlag vu Beleeger verlaangen.</p>

<p>Och dës Pauschal gehéiert zur Reegelung fir Handelstransaktiounen: si gëtt net vun engem private Client gefuerdert.</p>

<h2>Bewäert Praxis op Äre Rechnungen</h2>

<p>Fir Iech am Sträitfall ofzesécheren, gitt op all Rechnung un:</p>

<ul>
    <li>Dat genaut <strong>Fälegkeetsdatum</strong> (net nëmmen „30 Deeg")</li>
    <li>D'<strong>Bezuelungsmodalitéiten</strong> (Iwwerweisung, mat IBAN)</li>
    <li>D'Mentioun vun de gëltege <strong>Verzuchszënsen</strong></li>
    <li>D'Mentioun vun der <strong>Pauschal vun 40 EUR</strong> fir Är professionell Clienten</li>
</ul>

<h2>Offiziell Quellen</h2>

<ul>
    <li><a href="https://guichet.public.lu/fr/entreprises/gestion-juridique-comptabilite/facturation/encaissement/interets-retard.html" target="_blank" rel="noopener">Guichet.lu – Bezuelungsfristen a Verzuchszënsen</a></li>
    <li><a href="https://mj.gouvernement.lu/fr/service-citoyens/taux-interet-legal.html" target="_blank" rel="noopener">Justizministère – Gesetzlechen Zënssaz (all Semester aktualiséiert)</a></li>
    <li><a href="https://data.legilux.public.lu/filestore/eli/etat/leg/loi/2004/04/18/n8/jo/fr/html/eli-etat-leg-loi-2004-04-18-n8-jo-fr-html.html" target="_blank" rel="noopener">Legilux – Gesetz vum 18. Abrëll 2004 (Originaltext)</a></li>
</ul>

<div class="mt-8 p-4 bg-slate-50 rounded-xl"><h3 class="text-base font-semibold text-slate-900 mb-3">Verbonnen Artikelen</h3><ul class="space-y-1"><li><a href="/lb/blog/relancer-client-impaye-luxembourg" class="text-primary-500 hover:text-primary-600 text-sm">E Client relancéieren →</a></li><li><a href="/lb/blog/mentions-obligatoires-facture-luxembourg" class="text-primary-500 hover:text-primary-600 text-sm">Obligatoresch Mentiounen →</a></li></ul></div>

<div class="bg-primary-50 rounded-xl p-6 mt-8">
    <h3 class="text-lg font-semibold text-primary-900 mb-2">Geréiert Är Bezuelungsfristen mat faktur.lu</h3>
    <p class="text-primary-800 mb-4">faktur.lu berechent d'Fälegkeetsdaten automatesch, erkennt Verspéidungen a schéckt Relancen. Behaalt d'Kontroll iwwer Är Tresorerie.</p>
    <a href="/register" class="inline-block bg-primary-500 text-white font-semibold px-6 py-3 rounded-xl hover:bg-primary-600">14 Deeg gratis testen</a>
</div>

<p class="text-sm text-slate-500 mt-6"><em>Artikel aktualiséiert den 30. Juli 2026.</em></p>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">All Semester ze préiwen</p>
    <p>Den Taux fir Handelstransaktiounen ännert zweemol am Joer. Dës Säit gëtt reegelméisseg aktualiséiert — mä ier Dir eng Fuerderung bezifferet, préift den Taux vum lafende Semester beim <a href="https://mj.gouvernement.lu/fr/service-citoyens/taux-interet-legal.html" target="_blank" rel="noopener">Justizministère</a>, a fir Är perséinlech Situatioun frot Är Fiduciaire.</p>
</div>
HTML;
    }

    private function pt(): string
    {
        return <<<'HTML'
<p class="lead">No Luxemburgo, os prazos de pagamento são regulados por lei. Quer fature a uma empresa, a uma administração ou a um particular, eis as regras que protegem a sua tesouraria.</p>

<h2>Prazos legais de pagamento</h2>

<h3>Transações B2B (entre empresas)</h3>

<p>A <strong>lei alterada de 18 de abril de 2004</strong> relativa aos prazos de pagamento e aos juros de mora fixa as seguintes regras:</p>

<ul>
    <li><strong>Prazo por defeito</strong>: 30 dias a contar da receção da fatura</li>
    <li><strong>Prazo máximo contratual</strong>: 60 dias</li>
    <li>As partes podem acordar um prazo <strong>superior a 60 dias</strong> apenas se tal não constituir um <strong>abuso manifesto</strong> em relação ao credor</li>
</ul>

<p>Esta lei foi <strong>alterada pela lei de 29 de março de 2013</strong>, que transpõe a diretiva 2011/7/UE. Foi essa revisão que elevou o agravamento para 8 pontos e criou a indemnização fixa de 40 EUR: os artigos que citam apenas o texto de 2004 descrevem um regime ultrapassado.</p>

<h3>Transações B2G (com o setor público)</h3>

<p>Os prazos são mais estritos para as administrações:</p>

<ul>
    <li><strong>Prazo máximo</strong>: 30 dias a contar da receção da fatura</li>
    <li>Este prazo pode ser <strong>alargado até 60 dias no máximo</strong> se estiver expressamente previsto no contrato e for objetivamente justificado pela natureza deste</li>
    <li>O procedimento de aceitação ou verificação não pode exceder <strong>30 dias</strong></li>
</ul>

<h2>A partir de quando corre o prazo?</h2>

<p>O prazo de pagamento começa a correr a partir de:</p>

<ul>
    <li>A <strong>data de receção da fatura</strong> pelo devedor</li>
    <li>Ou a <strong>data de receção dos bens/serviços</strong>, se a data da fatura for incerta ou se esta tiver sido enviada antes</li>
    <li>Ou a <strong>data de aceitação ou verificação</strong>, quando tal procedimento esteja previsto no contrato ou na lei</li>
</ul>

<p><strong>Conselho:</strong> indique sempre claramente a <strong>data de vencimento</strong> na sua fatura. Com o faktur.lu, é calculada automaticamente (30 dias por defeito, personalizável).</p>

<h2>Juros de mora</h2>

<p>Em caso de atraso, os juros são devidos <strong>automaticamente</strong>, sem que seja necessário enviar uma interpelação. A taxa não é, porém, <strong>a mesma consoante a qualidade do seu cliente</strong> — é a confusão mais frequente.</p>

<h3>O seu cliente é um profissional</h3>

<ul>
    <li><strong>Fórmula</strong>: taxa de referência do BCE publicada no Mémorial B no início de cada semestre, <strong>acrescida de 8 pontos percentuais</strong></li>
    <li><strong>1.º semestre de 2026</strong>: <strong>10,15 %</strong> (2,15 % + 8)</li>
    <li>A taxa é <strong>revista todos os semestres</strong>: verifique a que está em vigor antes de quantificar uma reclamação</li>
    <li>Os juros correm a partir do dia seguinte à data de vencimento</li>
</ul>

<h3>O seu cliente é um particular</h3>

<p>O regime das transações comerciais não se aplica. Vale a <strong>taxa de juro legal</strong>, fixada anualmente: <strong>3,75 % para 2026</strong>.</p>

<div class="bg-blue-50 border-l-4 border-blue-500 p-4 my-6">
    <p class="font-semibold">A diferença é importante</p>
    <p>10,15 % contra 3,75 %: reclamar a taxa comercial a um consumidor é pedir quase o triplo do devido. A reclamação torna-se contestável e a sua credibilidade sofre precisamente no momento em que precisa de ser levado a sério.</p>
</div>

<h2>Indemnização fixa de cobrança</h2>

<p>Para além dos juros de mora, o credor tem direito a uma <strong>indemnização fixa de 40 EUR</strong> a título de custos de cobrança, devida <strong>por fatura não paga</strong>, automaticamente e sem justificação de despesas.</p>

<p>Se os custos reais de cobrança excederem 40 EUR, o credor pode reclamar o montante efetivo mediante apresentação de comprovativos.</p>

<p>Esta indemnização pertence igualmente ao regime das transações comerciais: não se reclama a um cliente particular.</p>

<h2>Boas práticas nas suas faturas</h2>

<p>Para se proteger em caso de litígio, mencione em cada fatura:</p>

<ul>
    <li>A <strong>data de vencimento</strong> precisa (não apenas «30 dias»)</li>
    <li>As <strong>modalidades de pagamento</strong> (transferência, com IBAN)</li>
    <li>A menção dos <strong>juros de mora</strong> aplicáveis</li>
    <li>A menção da <strong>indemnização fixa de 40 EUR</strong> para os seus clientes profissionais</li>
</ul>

<h2>Fontes oficiais</h2>

<ul>
    <li><a href="https://guichet.public.lu/fr/entreprises/gestion-juridique-comptabilite/facturation/encaissement/interets-retard.html" target="_blank" rel="noopener">Guichet.lu – Prazos de pagamento e juros de mora</a></li>
    <li><a href="https://mj.gouvernement.lu/fr/service-citoyens/taux-interet-legal.html" target="_blank" rel="noopener">Ministério da Justiça – Taxa de juro legal (atualizada a cada semestre)</a></li>
    <li><a href="https://data.legilux.public.lu/filestore/eli/etat/leg/loi/2004/04/18/n8/jo/fr/html/eli-etat-leg-loi-2004-04-18-n8-jo-fr-html.html" target="_blank" rel="noopener">Legilux – Lei de 18 de abril de 2004 (texto original)</a></li>
</ul>

<div class="mt-8 p-4 bg-slate-50 rounded-xl"><h3 class="text-base font-semibold text-slate-900 mb-3">Artigos relacionados</h3><ul class="space-y-1"><li><a href="/pt/blog/relancer-client-impaye-luxembourg" class="text-primary-500 hover:text-primary-600 text-sm">Cobrar um cliente em atraso →</a></li><li><a href="/pt/blog/mentions-obligatoires-facture-luxembourg" class="text-primary-500 hover:text-primary-600 text-sm">Menções obrigatórias →</a></li></ul></div>

<div class="bg-primary-50 rounded-xl p-6 mt-8">
    <h3 class="text-lg font-semibold text-primary-900 mb-2">Faça a gestão dos seus prazos com o faktur.lu</h3>
    <p class="text-primary-800 mb-4">O faktur.lu calcula automaticamente as datas de vencimento, deteta os atrasos e envia lembretes. Mantenha o controlo da sua tesouraria.</p>
    <a href="/register" class="inline-block bg-primary-500 text-white font-semibold px-6 py-3 rounded-xl hover:bg-primary-600">Experimentar 14 dias grátis</a>
</div>

<p class="text-sm text-slate-500 mt-6"><em>Artigo atualizado a 30 de julho de 2026.</em></p>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">A verificar todos os semestres</p>
    <p>A taxa aplicável às transações comerciais muda duas vezes por ano. Esta página é atualizada regularmente, mas antes de quantificar uma reclamação, verifique a taxa do semestre em curso junto do <a href="https://mj.gouvernement.lu/fr/service-citoyens/taux-interet-legal.html" target="_blank" rel="noopener">Ministério da Justiça</a> e, para a sua situação pessoal, consulte o seu contabilista.</p>
</div>
HTML;
    }
};
