<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Article « freelance-luxembourg-facturer-conformite » —
 * vérification factuelle et traductions intégrales.
 *
 * ERREUR CORRIGÉE — déclarations de TVA présentées comme exclusives :
 *
 *   CA < 112 000 EUR    : déclaration annuelle
 *   CA 112 000-620 000  : déclaration trimestrielle
 *   CA > 620 000 EUR    : déclaration mensuelle
 *
 * Or la déclaration annuelle est due EN PLUS des déclarations
 * périodiques (guichet.lu : « Déclaration TVA mensuelle ET annuelle »).
 * Un freelance à 200 000 EUR de CA lisant cette liste déposait ses quatre
 * déclarations trimestrielles en pensant être en règle, et omettait la
 * déclaration annuelle récapitulative — omission qui déclenche des
 * pénalités de dépôt tardif.
 *
 * Ajouté également : c'est l'AED qui détermine le régime applicable, le
 * contribuable ne le choisit pas.
 *
 * FAITS VÉRIFIÉS et confirmés exacts (guichet.lu) :
 * - Seuils 112 000 / 620 000 EUR corrects.
 * - Échéance du 1er mai pour la déclaration annuelle : confirmée par
 *   guichet.lu, contrairement à plusieurs agrégateurs qui annoncent le
 *   1er mars. La source officielle prime.
 * - Délai d'émission au 15 du mois suivant : s'applique bien à toutes les
 *   opérations entre professionnels, et non aux seules opérations
 *   intracommunautaires.
 * - Franchise à 50 000 EUR (art. 57bis LIVA depuis 2025), taux normal
 *   17 %, autoliquidation art. 196 directive, conservation 10 ans.
 * - La distinction art. 44 / art. 196 était déjà correctement traitée.
 *
 * PRÉCISION AJOUTÉE : la franchise prive du droit à déduction de la TVA
 * en amont — contrepartie décisive que l'article passait sous silence.
 *
 * PARITÉ : allemand, anglais, luxembourgeois et portugais faisaient
 * 7 894 à 8 501 caractères contre 11 363, avec 1 seul lien contre 11.
 * Le bloc « sources officielles » et les articles connexes manquaient
 * entièrement.
 *
 * ⚠️ La version luxembourgeoise mérite une relecture par un locuteur natif.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['fr' => $this->fr(), 'de' => $this->de(), 'en' => $this->en(), 'lb' => $this->lb(), 'pt' => $this->pt()] as $locale => $content) {
            DB::table('blog_posts')
                ->where('translation_key', 'freelance-luxembourg-facturer-conformite')
                ->where('locale', $locale)
                ->update(['content' => $content, 'updated_at' => now()]);
        }
    }

    public function down(): void
    {
        // Contenu rédactionnel : la version antérieure laissait croire que
        // la déclaration annuelle remplaçait les déclarations périodiques.
    }

    private function fr(): string
    {
        return <<<'HTML'
<p class="lead">Vous lancez votre activité de freelance au Luxembourg ? La facturation est un aspect crucial de votre activité. Ce guide vous explique comment créer des factures conformes en 2026 et gérer vos obligations fiscales (mises à jour suite au relèvement du seuil de franchise TVA à 50 000 €).</p>

<h2>Le statut de freelance au Luxembourg</h2>

<p>Au Luxembourg, le freelance (ou indépendant) exerce généralement sous l'un de ces statuts :</p>

<ul>
    <li><strong>Entreprise individuelle</strong> : le statut le plus courant pour démarrer</li>
    <li><strong>Société unipersonnelle (SARL-S)</strong> : une société à responsabilité limitée simplifiée</li>
    <li><strong>Profession libérale</strong> : pour certaines activités réglementées</li>
</ul>

<p>Quel que soit votre statut, vous devez respecter les mêmes règles de facturation.</p>

<h2>S'immatriculer à la TVA</h2>

<p>Avant de commencer à facturer (au-delà du seuil de franchise, voir ci-dessous), vous devez obtenir un <strong>numéro de TVA intracommunautaire</strong> auprès de l'<a href="https://pfi.public.lu/" target="_blank" rel="noopener">AED (Administration de l'Enregistrement, des Domaines et de la TVA)</a>.</p>

<h3>Procédure d'immatriculation</h3>

<ol>
    <li>Obtenir une <strong>autorisation d'établissement</strong> auprès du Ministère de l'Économie</li>
    <li>S'inscrire au <strong>Registre de Commerce (RCS)</strong> si applicable</li>
    <li>Demander l'<strong>immatriculation TVA</strong> via <a href="https://www.myguichet.lu/" target="_blank" rel="noopener">MyGuichet.lu</a></li>
    <li>Recevoir votre numéro au format <strong>LU + 8 chiffres</strong></li>
</ol>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold text-amber-800">⚠️ Attention</p>
    <p class="text-amber-700">Sous le seuil de franchise (CA ≤ 50 000 € HT, art. 57bis LIVA depuis 2025), l'immatriculation TVA n'est pas obligatoire. Vous facturez alors sans TVA avec la mention « TVA non applicable - Article 57bis ». <strong>Contrepartie :</strong> vous ne récupérez pas la TVA sur vos achats professionnels (matériel, logiciels, sous-traitance). Si vous investissez, la franchise peut vous coûter plus qu'elle ne vous rapporte.</p>
</div>

<h2>Les mentions obligatoires sur vos factures</h2>

<p>Selon l'<strong>article 63 LIVA</strong>, en tant que freelance, vos factures doivent contenir :</p>

<h3>Vos informations</h3>

<ul>
    <li><strong>Nom complet</strong> ou raison sociale</li>
    <li><strong>Adresse professionnelle</strong> au Luxembourg</li>
    <li><strong>Numéro de TVA</strong> (LU12345678)</li>
    <li>Éventuellement votre numéro RCS (obligatoire pour les commerçants/sociétés inscrits)</li>
</ul>

<h3>Informations du client</h3>

<ul>
    <li>Nom ou raison sociale</li>
    <li>Adresse complète</li>
    <li>Numéro de TVA (obligatoire pour les clients professionnels intra-UE)</li>
</ul>

<h3>Détails de la prestation</h3>

<ul>
    <li><strong>Numéro de facture</strong> unique et séquentiel (article 63 LIVA, point 3°)</li>
    <li><strong>Date d'émission</strong></li>
    <li><strong>Date de prestation</strong> (fait générateur de TVA)</li>
    <li><strong>Description détaillée</strong> des services rendus</li>
    <li><strong>Nombre d'heures ou de jours</strong> (recommandé)</li>
    <li><strong>Tarif unitaire HT</strong></li>
    <li><strong>Montant HT, TVA et TTC</strong></li>
    <li><strong>Taux de TVA applicable</strong></li>
</ul>

<h2>Quel taux de TVA appliquer ?</h2>

<p>En tant que freelance, vous appliquez généralement le <strong>taux normal de 17 %</strong> pour la plupart des prestations de services.</p>

<h3>Cas particuliers</h3>

<table class="w-full border-collapse border border-gray-300 my-6">
    <thead class="bg-gray-100">
        <tr>
            <th class="border border-gray-300 px-4 py-2 text-left">Situation</th>
            <th class="border border-gray-300 px-4 py-2 text-left">TVA applicable</th>
        </tr>
    </thead>
    <tbody>
        <tr><td class="border border-gray-300 px-4 py-2">Client professionnel au Luxembourg</td><td class="border border-gray-300 px-4 py-2">TVA 17 %</td></tr>
        <tr><td class="border border-gray-300 px-4 py-2">Client professionnel dans l'UE (B2B intra-UE)</td><td class="border border-gray-300 px-4 py-2">0 % (autoliquidation, art. 17 LIVA + art. 196 directive)</td></tr>
        <tr><td class="border border-gray-300 px-4 py-2">Client hors UE</td><td class="border border-gray-300 px-4 py-2">0 % (hors champ TVA luxembourgeoise pour la plupart des prestations intellectuelles)</td></tr>
        <tr><td class="border border-gray-300 px-4 py-2">Client particulier au Luxembourg</td><td class="border border-gray-300 px-4 py-2">TVA 17 %</td></tr>
    </tbody>
</table>

<h2>Facturer un client à l'étranger</h2>

<h3>Client professionnel dans l'UE</h3>

<p>Si votre client est une entreprise dans un autre pays de l'UE :</p>

<ol>
    <li><strong>Vérifiez son numéro de TVA</strong> sur le système <a href="https://ec.europa.eu/taxation_customs/vies/" target="_blank" rel="noopener">VIES</a></li>
    <li><strong>N'appliquez pas de TVA</strong> sur votre facture (lieu d'imposition chez le preneur, art. 17 LIVA)</li>
    <li><strong>Ajoutez la mention</strong> : <em>« Autoliquidation - Article 196 de la directive 2006/112/CE »</em></li>
    <li><strong>Indiquez le numéro de TVA</strong> du client sur la facture</li>
</ol>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">⚠ Mention obligatoire - base légale correcte</p>
    <p>La mention canonique pour l'autoliquidation B2B intra-UE est <strong>« Autoliquidation - Article 196 de la directive 2006/112/CE »</strong>. L'article 196 désigne le preneur comme redevable (art. 226 §11bis directive). À ne pas confondre avec l'article 44 directive (lieu d'imposition) ni avec l'article 44 de la loi LU 1979 (exonérations sectorielles, sans rapport).</p>
</div>

<h3>Client hors UE</h3>

<p>Pour les clients situés hors de l'Union Européenne, les prestations intellectuelles courantes du freelance (conseil, informatique, publicité, traduction) sont hors champ de la TVA luxembourgeoise. Mentionnez « TVA non applicable - service localisé hors UE ».</p>

<h2>La numérotation de vos factures (article 63 LIVA, point 3°)</h2>

<p>Vos factures doivent suivre une <strong>numérotation chronologique et continue</strong> :</p>

<ul>
    <li>Pas de trou dans la séquence</li>
    <li>Format libre mais cohérent (ex : 2026-001, 2026-002…)</li>
    <li>Reset annuel autorisé</li>
</ul>

<div class="bg-purple-50 border-l-4 border-purple-500 p-4 my-6">
    <p class="font-semibold text-purple-800">💡 Conseil</p>
    <p class="text-purple-700">Utilisez un logiciel de facturation comme faktur.lu pour générer automatiquement des numéros conformes et éviter les erreurs.</p>
</div>

<h2>Gérer vos déclarations de TVA</h2>

<p>Une fois immatriculé, vous déposez des déclarations dont la périodicité dépend de votre chiffre d'affaires HT. <strong>C'est l'AED qui détermine votre régime</strong> — vous ne le choisissez pas.</p>

<ul>
    <li><strong>CA &lt; 112 000 €/an</strong> : déclaration annuelle uniquement</li>
    <li><strong>CA entre 112 000 € et 620 000 €/an</strong> : déclarations trimestrielles <strong>et</strong> déclaration annuelle</li>
    <li><strong>CA &gt; 620 000 €/an</strong> : déclarations mensuelles <strong>et</strong> déclaration annuelle</li>
</ul>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold text-amber-800">⚠️ La déclaration annuelle ne remplace pas les déclarations périodiques</p>
    <p class="text-amber-700">C'est l'erreur la plus coûteuse de cette page. Si vous déposez des déclarations trimestrielles ou mensuelles, vous devez <strong>en plus</strong> déposer une <strong>déclaration annuelle récapitulative</strong>, à déposer avant le <strong>1<sup>er</sup> mai de l'année suivante</strong>. L'oublier déclenche des pénalités de dépôt tardif, alors même que vous avez payé toute votre TVA en cours d'année.</p>
</div>

<p>Les déclarations périodiques se déposent avant le 15 du mois suivant la période concernée. Tout se fait en ligne via <strong>eCDF (eTVA)</strong>.</p>

<h2>Le fichier FAIA pour les freelances</h2>

<p>Si vous utilisez un logiciel de facturation, vous devez être capable de produire un <strong>fichier FAIA</strong> sur demande de l'AED - sous conditions. La FAQ AED exclut les assujettis avec CA ≤ 112 000 € HT, donc les très petits freelances en franchise ne sont généralement pas tenus de produire le FAIA. À confirmer avec votre fiduciaire selon votre régime.</p>

<div class="bg-green-50 border-l-4 border-green-500 p-4 my-6">
    <p class="font-semibold text-green-800">✅ faktur.lu génère votre fichier FAIA</p>
    <p class="text-green-700">Notre logiciel produit automatiquement un fichier FAIA 2.01 conforme, prêt pour tout contrôle fiscal AED.</p>
</div>

<h2>Conseils pour les freelances débutants</h2>

<ol>
    <li><strong>Utilisez un logiciel adapté</strong> dès le départ pour éviter les erreurs</li>
    <li><strong>Conservez toutes vos factures</strong> (émises et reçues) pendant 10 ans (art. 16 Code de commerce + art. 65 LIVA)</li>
    <li><strong>Séparez vos comptes</strong> personnels et professionnels</li>
    <li><strong>Facturez rapidement</strong> (au plus tard le 15 du mois suivant la prestation, art. 63 LIVA)</li>
    <li><strong>Vérifiez les numéros de TVA</strong> de vos clients UE avant de facturer (VIES)</li>
    <li><strong>Notez vos deux échéances</strong> : les déclarations périodiques et la déclaration annuelle</li>
    <li><strong>Consultez un comptable</strong> pour les questions complexes</li>
</ol>

<h2>Erreurs courantes à éviter</h2>

<ul>
    <li>❌ Facturer sans numéro de TVA (si vous y êtes assujetti)</li>
    <li>❌ Oublier des mentions obligatoires (art. 63 LIVA)</li>
    <li>❌ Appliquer un mauvais taux de TVA</li>
    <li>❌ Numérotation non séquentielle</li>
    <li>❌ Facturer après le 15 du mois suivant la prestation</li>
    <li>❌ Ne pas vérifier les numéros de TVA UE (VIES)</li>
    <li>❌ <strong>Croire que les déclarations trimestrielles dispensent de la déclaration annuelle</strong></li>
    <li>❌ Confondre « article 44 directive » (lieu d'imposition) et « article 196 directive » (mention autoliquidation)</li>
</ul>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">À vérifier chaque année</p>
    <p>Les seuils, taux et procédures TVA peuvent évoluer. Cette page est mise à jour régulièrement, mais pour votre situation personnelle, consultez votre fiduciaire ou l'<a href="https://pfi.public.lu/" target="_blank" rel="noopener">AED</a>.</p>
</div>

<h2>Sources officielles</h2>

<ul>
    <li><a href="https://legilux.public.lu/eli/etat/leg/loi/1979/02/12/n1/jo" target="_blank" rel="noopener">Loi modifiée du 12 février 1979 (LIVA) - articles 17, 57bis, 63, 65</a></li>
    <li><a href="https://guichet.public.lu/fr/entreprises/fiscalite/impots-benefices/tva/declarations/declaration-tva.html" target="_blank" rel="noopener">Guichet.lu - Déclarations de TVA et périodicités</a></li>
    <li><a href="https://pfi.public.lu/fr/professionnel/tva/sme.html" target="_blank" rel="noopener">AED - Régime de franchise (art. 57bis)</a></li>
    <li><a href="https://pfi.public.lu/fr/professionnel/tva/en-cours-activite-economique/que-doivent-contenir-factures.html" target="_blank" rel="noopener">AED - Mentions obligatoires sur les factures</a></li>
    <li><a href="https://ec.europa.eu/taxation_customs/vies/" target="_blank" rel="noopener">VIES - Validation TVA intracommunautaire</a></li>
</ul>

<p class="text-sm text-slate-500 mt-6"><em>Article mis à jour le 30 juillet 2026.</em></p>

<h2>Conclusion</h2>

<p>La facturation en tant que freelance au Luxembourg n'est pas compliquée si vous suivez les règles. L'utilisation d'un logiciel de facturation adapté comme faktur.lu vous permet de créer des factures conformes en quelques clics, avec toutes les mentions obligatoires et les bons taux de TVA appliqués automatiquement. Concentrez-vous sur votre métier, nous nous occupons de votre conformité !</p>

<div class="mt-8 p-4 bg-slate-50 rounded-xl"><h3 class="text-base font-semibold text-slate-900 mb-3">Articles connexes</h3><ul class="space-y-1"><li><a href="/fr/blog/5-erreurs-frequentes-facture-freelance-luxembourg" class="text-primary-500 hover:text-primary-600 text-sm">5 erreurs fréquentes sur une facture freelance →</a></li><li><a href="/fr/blog/mentions-obligatoires-facture-luxembourg" class="text-primary-500 hover:text-primary-600 text-sm">Mentions obligatoires sur une facture LU →</a></li><li><a href="/fr/blog/franchise-tva-luxembourg-seuil-obligations-regime-normal" class="text-primary-500 hover:text-primary-600 text-sm">Franchise TVA Luxembourg (seuil 50 000 €) →</a></li></ul></div>
HTML;
    }

    private function de(): string
    {
        return <<<'HTML'
<p class="lead">Sie starten als Freiberufler in Luxemburg? Die Rechnungsstellung ist ein entscheidender Teil Ihrer Tätigkeit. Dieser Leitfaden zeigt Ihnen, wie Sie 2026 konforme Rechnungen erstellen und Ihre steuerlichen Pflichten erfüllen (aktualisiert nach der Anhebung der MwSt.-Freigrenze auf 50 000 €).</p>

<h2>Der Freiberufler-Status in Luxemburg</h2>

<p>In Luxemburg übt der Freiberufler (oder Selbstständige) seine Tätigkeit in der Regel unter einem dieser Status aus:</p>

<ul>
    <li><strong>Einzelunternehmen</strong>: der gängigste Status für den Start</li>
    <li><strong>Einpersonengesellschaft (SARL-S)</strong>: eine vereinfachte Gesellschaft mit beschränkter Haftung</li>
    <li><strong>Freier Beruf</strong>: für bestimmte reglementierte Tätigkeiten</li>
</ul>

<p>Unabhängig vom Status gelten für Sie dieselben Regeln der Rechnungsstellung.</p>

<h2>Sich für die MwSt. registrieren</h2>

<p>Bevor Sie mit dem Fakturieren beginnen (oberhalb der Freigrenze, siehe unten), müssen Sie eine <strong>innergemeinschaftliche MwSt.-Nummer</strong> bei der <a href="https://pfi.public.lu/" target="_blank" rel="noopener">AED (Registrierungs-, Domänen- und MwSt.-Verwaltung)</a> beantragen.</p>

<h3>Registrierungsverfahren</h3>

<ol>
    <li>Eine <strong>Niederlassungsgenehmigung</strong> beim Wirtschaftsministerium einholen</li>
    <li>Sich gegebenenfalls im <strong>Handelsregister (RCS)</strong> eintragen</li>
    <li>Die <strong>MwSt.-Registrierung</strong> über <a href="https://www.myguichet.lu/" target="_blank" rel="noopener">MyGuichet.lu</a> beantragen</li>
    <li>Ihre Nummer im Format <strong>LU + 8 Ziffern</strong> erhalten</li>
</ol>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold text-amber-800">⚠️ Achtung</p>
    <p class="text-amber-700">Unterhalb der Freigrenze (Umsatz ≤ 50 000 € netto, Art. 57bis LIVA seit 2025) ist die MwSt.-Registrierung nicht verpflichtend. Sie fakturieren dann ohne MwSt. mit dem Vermerk „MwSt. nicht anwendbar – Artikel 57bis". <strong>Kehrseite:</strong> Sie können die MwSt. auf Ihre betrieblichen Einkäufe (Ausrüstung, Software, Unteraufträge) nicht abziehen. Wer investiert, für den kann die Freigrenze teurer sein als sie einbringt.</p>
</div>

<h2>Pflichtangaben auf Ihren Rechnungen</h2>

<p>Nach <strong>Artikel 63 LIVA</strong> müssen Ihre Rechnungen als Freiberufler enthalten:</p>

<h3>Ihre Angaben</h3>

<ul>
    <li><strong>Vollständiger Name</strong> oder Firmenbezeichnung</li>
    <li><strong>Geschäftsadresse</strong> in Luxemburg</li>
    <li><strong>MwSt.-Nummer</strong> (LU12345678)</li>
    <li>Gegebenenfalls Ihre RCS-Nummer (verpflichtend für eingetragene Kaufleute/Gesellschaften)</li>
</ul>

<h3>Angaben zum Kunden</h3>

<ul>
    <li>Name oder Firmenbezeichnung</li>
    <li>Vollständige Anschrift</li>
    <li>MwSt.-Nummer (verpflichtend bei gewerblichen Kunden innerhalb der EU)</li>
</ul>

<h3>Einzelheiten der Leistung</h3>

<ul>
    <li><strong>Eindeutige, fortlaufende Rechnungsnummer</strong> (Artikel 63 LIVA, Ziffer 3°)</li>
    <li><strong>Ausstellungsdatum</strong></li>
    <li><strong>Leistungsdatum</strong> (Steuertatbestand)</li>
    <li><strong>Ausführliche Beschreibung</strong> der erbrachten Leistungen</li>
    <li><strong>Anzahl der Stunden oder Tage</strong> (empfohlen)</li>
    <li><strong>Einzelpreis netto</strong></li>
    <li><strong>Nettobetrag, MwSt. und Bruttobetrag</strong></li>
    <li><strong>Anwendbarer MwSt.-Satz</strong></li>
</ul>

<h2>Welchen MwSt.-Satz anwenden?</h2>

<p>Als Freiberufler wenden Sie für die meisten Dienstleistungen in der Regel den <strong>Normalsatz von 17 %</strong> an.</p>

<h3>Sonderfälle</h3>

<table class="w-full border-collapse border border-gray-300 my-6">
    <thead class="bg-gray-100">
        <tr>
            <th class="border border-gray-300 px-4 py-2 text-left">Situation</th>
            <th class="border border-gray-300 px-4 py-2 text-left">Anwendbare MwSt.</th>
        </tr>
    </thead>
    <tbody>
        <tr><td class="border border-gray-300 px-4 py-2">Gewerblicher Kunde in Luxemburg</td><td class="border border-gray-300 px-4 py-2">MwSt. 17 %</td></tr>
        <tr><td class="border border-gray-300 px-4 py-2">Gewerblicher Kunde in der EU (B2B innergemeinschaftlich)</td><td class="border border-gray-300 px-4 py-2">0 % (Reverse Charge, Art. 17 LIVA + Art. 196 Richtlinie)</td></tr>
        <tr><td class="border border-gray-300 px-4 py-2">Kunde außerhalb der EU</td><td class="border border-gray-300 px-4 py-2">0 % (bei den meisten geistigen Leistungen außerhalb des luxemburgischen MwSt.-Bereichs)</td></tr>
        <tr><td class="border border-gray-300 px-4 py-2">Privatkunde in Luxemburg</td><td class="border border-gray-300 px-4 py-2">MwSt. 17 %</td></tr>
    </tbody>
</table>

<h2>Einem Kunden im Ausland fakturieren</h2>

<h3>Gewerblicher Kunde in der EU</h3>

<p>Ist Ihr Kunde ein Unternehmen in einem anderen EU-Land:</p>

<ol>
    <li><strong>Prüfen Sie seine MwSt.-Nummer</strong> im <a href="https://ec.europa.eu/taxation_customs/vies/" target="_blank" rel="noopener">VIES</a>-System</li>
    <li><strong>Berechnen Sie keine MwSt.</strong> auf Ihrer Rechnung (Ort der Besteuerung beim Leistungsempfänger, Art. 17 LIVA)</li>
    <li><strong>Fügen Sie den Vermerk hinzu</strong>: <em>„Umkehrung der Steuerschuldnerschaft – Artikel 196 der Richtlinie 2006/112/EG"</em></li>
    <li><strong>Geben Sie die MwSt.-Nummer</strong> des Kunden auf der Rechnung an</li>
</ol>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">⚠ Pflichtangabe – korrekte Rechtsgrundlage</p>
    <p>Der kanonische Vermerk für den innergemeinschaftlichen B2B-Reverse-Charge lautet <strong>„Umkehrung der Steuerschuldnerschaft – Artikel 196 der Richtlinie 2006/112/EG"</strong>. Artikel 196 benennt den Leistungsempfänger als Steuerschuldner (Art. 226 §11bis der Richtlinie). Nicht zu verwechseln mit Artikel 44 der Richtlinie (Ort der Besteuerung) noch mit Artikel 44 des luxemburgischen Gesetzes von 1979 (sektorielle Befreiungen, ohne Zusammenhang).</p>
</div>

<h3>Kunde außerhalb der EU</h3>

<p>Bei Kunden außerhalb der Europäischen Union fallen die gängigen geistigen Leistungen eines Freiberuflers (Beratung, IT, Werbung, Übersetzung) nicht in den luxemburgischen MwSt.-Bereich. Vermerken Sie „MwSt. nicht anwendbar – Leistungsort außerhalb der EU".</p>

<h2>Die Nummerierung Ihrer Rechnungen (Artikel 63 LIVA, Ziffer 3°)</h2>

<p>Ihre Rechnungen müssen einer <strong>chronologischen und lückenlosen Nummerierung</strong> folgen:</p>

<ul>
    <li>Keine Lücke in der Reihenfolge</li>
    <li>Freies, aber einheitliches Format (z. B. 2026-001, 2026-002…)</li>
    <li>Jährlicher Neustart zulässig</li>
</ul>

<div class="bg-purple-50 border-l-4 border-purple-500 p-4 my-6">
    <p class="font-semibold text-purple-800">💡 Tipp</p>
    <p class="text-purple-700">Nutzen Sie eine Rechnungssoftware wie faktur.lu, um automatisch konforme Nummern zu erzeugen und Fehler zu vermeiden.</p>
</div>

<h2>Ihre MwSt.-Erklärungen verwalten</h2>

<p>Nach der Registrierung reichen Sie Erklärungen ein, deren Rhythmus sich nach Ihrem Nettoumsatz richtet. <strong>Die AED bestimmt Ihr Regime</strong> – Sie wählen es nicht selbst.</p>

<ul>
    <li><strong>Umsatz &lt; 112 000 €/Jahr</strong>: nur Jahreserklärung</li>
    <li><strong>Umsatz zwischen 112 000 € und 620 000 €/Jahr</strong>: Vierteljahreserklärungen <strong>und</strong> Jahreserklärung</li>
    <li><strong>Umsatz &gt; 620 000 €/Jahr</strong>: Monatserklärungen <strong>und</strong> Jahreserklärung</li>
</ul>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold text-amber-800">⚠️ Die Jahreserklärung ersetzt die periodischen Erklärungen nicht</p>
    <p class="text-amber-700">Das ist der teuerste Irrtum auf dieser Seite. Wer vierteljährlich oder monatlich erklärt, muss <strong>zusätzlich</strong> eine <strong>zusammenfassende Jahreserklärung</strong> einreichen, und zwar vor dem <strong>1. Mai des Folgejahres</strong>. Sie zu vergessen löst Verspätungszuschläge aus, obwohl Sie Ihre MwSt. unterjährig vollständig gezahlt haben.</p>
</div>

<p>Die periodischen Erklärungen sind vor dem 15. des auf den Zeitraum folgenden Monats einzureichen. Alles läuft online über <strong>eCDF (eTVA)</strong>.</p>

<h2>Die FAIA-Datei für Freiberufler</h2>

<p>Wenn Sie eine Rechnungssoftware nutzen, müssen Sie auf Anfrage der AED eine <strong>FAIA-Datei</strong> erzeugen können – unter Bedingungen. Die AED-FAQ nimmt Steuerpflichtige mit einem Nettoumsatz ≤ 112 000 € aus, sodass sehr kleine Freiberufler in der Freigrenzenregelung in der Regel keine FAIA-Datei vorlegen müssen. Mit Ihrem Treuhänder je nach Regime zu bestätigen.</p>

<div class="bg-green-50 border-l-4 border-green-500 p-4 my-6">
    <p class="font-semibold text-green-800">✅ faktur.lu erzeugt Ihre FAIA-Datei</p>
    <p class="text-green-700">Unsere Software erstellt automatisch eine konforme FAIA-2.01-Datei, bereit für jede Steuerprüfung der AED.</p>
</div>

<h2>Tipps für Freiberufler am Anfang</h2>

<ol>
    <li><strong>Nutzen Sie von Anfang an eine geeignete Software</strong>, um Fehler zu vermeiden</li>
    <li><strong>Bewahren Sie alle Rechnungen</strong> (ausgestellte und erhaltene) 10 Jahre auf (Art. 16 Handelsgesetzbuch + Art. 65 LIVA)</li>
    <li><strong>Trennen Sie</strong> private und geschäftliche Konten</li>
    <li><strong>Fakturieren Sie zügig</strong> (spätestens am 15. des auf die Leistung folgenden Monats, Art. 63 LIVA)</li>
    <li><strong>Prüfen Sie die MwSt.-Nummern</strong> Ihrer EU-Kunden vor der Fakturierung (VIES)</li>
    <li><strong>Notieren Sie beide Fristen</strong>: die periodischen Erklärungen und die Jahreserklärung</li>
    <li><strong>Ziehen Sie einen Buchhalter hinzu</strong> bei komplexen Fragen</li>
</ol>

<h2>Häufige Fehler, die es zu vermeiden gilt</h2>

<ul>
    <li>❌ Ohne MwSt.-Nummer fakturieren (sofern Sie steuerpflichtig sind)</li>
    <li>❌ Pflichtangaben vergessen (Art. 63 LIVA)</li>
    <li>❌ Einen falschen MwSt.-Satz anwenden</li>
    <li>❌ Nicht fortlaufende Nummerierung</li>
    <li>❌ Nach dem 15. des auf die Leistung folgenden Monats fakturieren</li>
    <li>❌ Die MwSt.-Nummern von EU-Kunden nicht prüfen (VIES)</li>
    <li>❌ <strong>Glauben, Vierteljahreserklärungen ersparten die Jahreserklärung</strong></li>
    <li>❌ „Artikel 44 der Richtlinie" (Ort der Besteuerung) mit „Artikel 196 der Richtlinie" (Reverse-Charge-Vermerk) verwechseln</li>
</ul>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">Jährlich zu prüfen</p>
    <p>Schwellen, Sätze und MwSt.-Verfahren können sich ändern. Diese Seite wird regelmäßig aktualisiert – für Ihre persönliche Situation wenden Sie sich an Ihren Treuhänder oder die <a href="https://pfi.public.lu/" target="_blank" rel="noopener">AED</a>.</p>
</div>

<h2>Offizielle Quellen</h2>

<ul>
    <li><a href="https://legilux.public.lu/eli/etat/leg/loi/1979/02/12/n1/jo" target="_blank" rel="noopener">Geändertes Gesetz vom 12. Februar 1979 (LIVA) – Artikel 17, 57bis, 63, 65</a></li>
    <li><a href="https://guichet.public.lu/fr/entreprises/fiscalite/impots-benefices/tva/declarations/declaration-tva.html" target="_blank" rel="noopener">Guichet.lu – MwSt.-Erklärungen und Periodizität</a></li>
    <li><a href="https://pfi.public.lu/fr/professionnel/tva/sme.html" target="_blank" rel="noopener">AED – Freigrenzenregelung (Art. 57bis)</a></li>
    <li><a href="https://pfi.public.lu/fr/professionnel/tva/en-cours-activite-economique/que-doivent-contenir-factures.html" target="_blank" rel="noopener">AED – Pflichtangaben auf Rechnungen</a></li>
    <li><a href="https://ec.europa.eu/taxation_customs/vies/" target="_blank" rel="noopener">VIES – Prüfung innergemeinschaftlicher MwSt.-Nummern</a></li>
</ul>

<p class="text-sm text-slate-500 mt-6"><em>Artikel aktualisiert am 30. Juli 2026.</em></p>

<h2>Fazit</h2>

<p>Die Rechnungsstellung als Freiberufler in Luxemburg ist nicht kompliziert, wenn Sie die Regeln kennen. Mit einer geeigneten Rechnungssoftware wie faktur.lu erstellen Sie konforme Rechnungen in wenigen Klicks, mit allen Pflichtangaben und automatisch korrekten MwSt.-Sätzen. Konzentrieren Sie sich auf Ihr Handwerk – um die Konformität kümmern wir uns!</p>

<div class="mt-8 p-4 bg-slate-50 rounded-xl"><h3 class="text-base font-semibold text-slate-900 mb-3">Verwandte Artikel</h3><ul class="space-y-1"><li><a href="/de/blog/5-erreurs-frequentes-facture-freelance-luxembourg" class="text-primary-500 hover:text-primary-600 text-sm">5 häufige Fehler auf einer Freiberufler-Rechnung →</a></li><li><a href="/de/blog/mentions-obligatoires-facture-luxembourg" class="text-primary-500 hover:text-primary-600 text-sm">Pflichtangaben auf einer luxemburgischen Rechnung →</a></li><li><a href="/de/blog/franchise-tva-luxembourg-seuil-obligations-regime-normal" class="text-primary-500 hover:text-primary-600 text-sm">MwSt.-Freigrenze Luxemburg (50 000 €) →</a></li></ul></div>
HTML;
    }

    private function en(): string
    {
        return <<<'HTML'
<p class="lead">Starting out as a freelancer in Luxembourg? Invoicing is a crucial part of your business. This guide explains how to create compliant invoices in 2026 and manage your tax obligations (updated following the increase of the VAT exemption threshold to €50,000).</p>

<h2>Freelance status in Luxembourg</h2>

<p>In Luxembourg, a freelancer (or self-employed person) generally operates under one of these statuses:</p>

<ul>
    <li><strong>Sole proprietorship</strong>: the most common status to start with</li>
    <li><strong>Single-member company (SARL-S)</strong>: a simplified limited liability company</li>
    <li><strong>Liberal profession</strong>: for certain regulated activities</li>
</ul>

<p>Whatever your status, the same invoicing rules apply.</p>

<h2>Registering for VAT</h2>

<p>Before you start invoicing (above the exemption threshold, see below), you must obtain an <strong>intra-community VAT number</strong> from the <a href="https://pfi.public.lu/" target="_blank" rel="noopener">AED (Registration Duties, Estates and VAT Authority)</a>.</p>

<h3>Registration procedure</h3>

<ol>
    <li>Obtain a <strong>business permit</strong> from the Ministry of the Economy</li>
    <li>Register with the <strong>Trade and Companies Register (RCS)</strong> where applicable</li>
    <li>Apply for <strong>VAT registration</strong> via <a href="https://www.myguichet.lu/" target="_blank" rel="noopener">MyGuichet.lu</a></li>
    <li>Receive your number in the format <strong>LU + 8 digits</strong></li>
</ol>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold text-amber-800">⚠️ Note</p>
    <p class="text-amber-700">Below the exemption threshold (turnover ≤ €50,000 excl. VAT, art. 57bis LIVA since 2025), VAT registration is not mandatory. You then invoice without VAT, stating "VAT not applicable - Article 57bis". <strong>The trade-off:</strong> you cannot reclaim VAT on your business purchases (equipment, software, subcontracting). If you invest, the exemption may cost you more than it saves.</p>
</div>

<h2>Mandatory details on your invoices</h2>

<p>Under <strong>article 63 LIVA</strong>, as a freelancer your invoices must contain:</p>

<h3>Your details</h3>

<ul>
    <li><strong>Full name</strong> or business name</li>
    <li><strong>Business address</strong> in Luxembourg</li>
    <li><strong>VAT number</strong> (LU12345678)</li>
    <li>Your RCS number where applicable (mandatory for registered traders/companies)</li>
</ul>

<h3>Client details</h3>

<ul>
    <li>Name or business name</li>
    <li>Full address</li>
    <li>VAT number (mandatory for intra-EU business clients)</li>
</ul>

<h3>Details of the service</h3>

<ul>
    <li><strong>Unique, sequential invoice number</strong> (article 63 LIVA, point 3°)</li>
    <li><strong>Date of issue</strong></li>
    <li><strong>Date of supply</strong> (chargeable event for VAT)</li>
    <li><strong>Detailed description</strong> of the services rendered</li>
    <li><strong>Number of hours or days</strong> (recommended)</li>
    <li><strong>Unit price excl. VAT</strong></li>
    <li><strong>Net amount, VAT and gross amount</strong></li>
    <li><strong>Applicable VAT rate</strong></li>
</ul>

<h2>Which VAT rate applies?</h2>

<p>As a freelancer you generally apply the <strong>standard rate of 17%</strong> to most services.</p>

<h3>Special cases</h3>

<table class="w-full border-collapse border border-gray-300 my-6">
    <thead class="bg-gray-100">
        <tr>
            <th class="border border-gray-300 px-4 py-2 text-left">Situation</th>
            <th class="border border-gray-300 px-4 py-2 text-left">Applicable VAT</th>
        </tr>
    </thead>
    <tbody>
        <tr><td class="border border-gray-300 px-4 py-2">Business client in Luxembourg</td><td class="border border-gray-300 px-4 py-2">VAT 17%</td></tr>
        <tr><td class="border border-gray-300 px-4 py-2">Business client in the EU (intra-EU B2B)</td><td class="border border-gray-300 px-4 py-2">0% (reverse charge, art. 17 LIVA + art. 196 Directive)</td></tr>
        <tr><td class="border border-gray-300 px-4 py-2">Client outside the EU</td><td class="border border-gray-300 px-4 py-2">0% (outside the scope of Luxembourg VAT for most intellectual services)</td></tr>
        <tr><td class="border border-gray-300 px-4 py-2">Private client in Luxembourg</td><td class="border border-gray-300 px-4 py-2">VAT 17%</td></tr>
    </tbody>
</table>

<h2>Invoicing a client abroad</h2>

<h3>Business client in the EU</h3>

<p>If your client is a business in another EU country:</p>

<ol>
    <li><strong>Check their VAT number</strong> on the <a href="https://ec.europa.eu/taxation_customs/vies/" target="_blank" rel="noopener">VIES</a> system</li>
    <li><strong>Do not charge VAT</strong> on your invoice (place of supply is the customer's, art. 17 LIVA)</li>
    <li><strong>Add the wording</strong>: <em>"Reverse charge - Article 196 of Directive 2006/112/EC"</em></li>
    <li><strong>State the client's VAT number</strong> on the invoice</li>
</ol>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">⚠ Mandatory wording - correct legal basis</p>
    <p>The canonical wording for intra-EU B2B reverse charge is <strong>"Reverse charge - Article 196 of Directive 2006/112/EC"</strong>. Article 196 designates the customer as liable (art. 226 §11bis of the Directive). Not to be confused with Article 44 of the Directive (place of supply) nor with Article 44 of the Luxembourg 1979 law (sectoral exemptions, unrelated).</p>
</div>

<h3>Client outside the EU</h3>

<p>For clients located outside the European Union, the intellectual services a freelancer typically provides (consulting, IT, advertising, translation) fall outside the scope of Luxembourg VAT. State "VAT not applicable - service supplied outside the EU".</p>

<h2>Numbering your invoices (article 63 LIVA, point 3°)</h2>

<p>Your invoices must follow <strong>chronological, unbroken numbering</strong>:</p>

<ul>
    <li>No gaps in the sequence</li>
    <li>Free but consistent format (e.g. 2026-001, 2026-002…)</li>
    <li>Annual reset permitted</li>
</ul>

<div class="bg-purple-50 border-l-4 border-purple-500 p-4 my-6">
    <p class="font-semibold text-purple-800">💡 Tip</p>
    <p class="text-purple-700">Use invoicing software such as faktur.lu to generate compliant numbers automatically and avoid mistakes.</p>
</div>

<h2>Managing your VAT returns</h2>

<p>Once registered, you file returns whose frequency depends on your net turnover. <strong>The AED determines your regime</strong> — you do not choose it.</p>

<ul>
    <li><strong>Turnover &lt; €112,000/year</strong>: annual return only</li>
    <li><strong>Turnover between €112,000 and €620,000/year</strong>: quarterly returns <strong>and</strong> an annual return</li>
    <li><strong>Turnover &gt; €620,000/year</strong>: monthly returns <strong>and</strong> an annual return</li>
</ul>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold text-amber-800">⚠️ The annual return does not replace the periodic ones</p>
    <p class="text-amber-700">This is the costliest misunderstanding on this page. If you file quarterly or monthly returns, you must <strong>also</strong> file a <strong>recapitulative annual return</strong>, due before <strong>1 May of the following year</strong>. Forgetting it triggers late-filing penalties even though you paid all your VAT during the year.</p>
</div>

<p>Periodic returns are due before the 15th of the month following the period concerned. Everything is filed online through <strong>eCDF (eTVA)</strong>.</p>

<h2>The FAIA file for freelancers</h2>

<p>If you use invoicing software, you must be able to produce a <strong>FAIA file</strong> on request from the AED - subject to conditions. The AED FAQ excludes taxable persons with turnover ≤ €112,000 excl. VAT, so very small freelancers under the exemption regime are generally not required to produce a FAIA file. Confirm with your accountant based on your regime.</p>

<div class="bg-green-50 border-l-4 border-green-500 p-4 my-6">
    <p class="font-semibold text-green-800">✅ faktur.lu generates your FAIA file</p>
    <p class="text-green-700">Our software automatically produces a compliant FAIA 2.01 file, ready for any AED tax audit.</p>
</div>

<h2>Tips for freelancers starting out</h2>

<ol>
    <li><strong>Use suitable software</strong> from day one to avoid mistakes</li>
    <li><strong>Keep every invoice</strong> (issued and received) for 10 years (art. 16 Commercial Code + art. 65 LIVA)</li>
    <li><strong>Separate</strong> your personal and business accounts</li>
    <li><strong>Invoice promptly</strong> (by the 15th of the month following the supply, art. 63 LIVA)</li>
    <li><strong>Check the VAT numbers</strong> of your EU clients before invoicing (VIES)</li>
    <li><strong>Diarise both deadlines</strong>: the periodic returns and the annual return</li>
    <li><strong>Consult an accountant</strong> for complex questions</li>
</ol>

<h2>Common mistakes to avoid</h2>

<ul>
    <li>❌ Invoicing without a VAT number (where you are liable for VAT)</li>
    <li>❌ Omitting mandatory details (art. 63 LIVA)</li>
    <li>❌ Applying the wrong VAT rate</li>
    <li>❌ Non-sequential numbering</li>
    <li>❌ Invoicing after the 15th of the month following the supply</li>
    <li>❌ Failing to check EU VAT numbers (VIES)</li>
    <li>❌ <strong>Believing that quarterly returns remove the need for the annual return</strong></li>
    <li>❌ Confusing "Article 44 of the Directive" (place of supply) with "Article 196 of the Directive" (reverse charge wording)</li>
</ul>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">To check every year</p>
    <p>VAT thresholds, rates and procedures may change. This page is updated regularly, but for your own situation consult your accountant or the <a href="https://pfi.public.lu/" target="_blank" rel="noopener">AED</a>.</p>
</div>

<h2>Official sources</h2>

<ul>
    <li><a href="https://legilux.public.lu/eli/etat/leg/loi/1979/02/12/n1/jo" target="_blank" rel="noopener">Amended law of 12 February 1979 (LIVA) - articles 17, 57bis, 63, 65</a></li>
    <li><a href="https://guichet.public.lu/fr/entreprises/fiscalite/impots-benefices/tva/declarations/declaration-tva.html" target="_blank" rel="noopener">Guichet.lu - VAT returns and filing frequencies</a></li>
    <li><a href="https://pfi.public.lu/fr/professionnel/tva/sme.html" target="_blank" rel="noopener">AED - Exemption regime (art. 57bis)</a></li>
    <li><a href="https://pfi.public.lu/fr/professionnel/tva/en-cours-activite-economique/que-doivent-contenir-factures.html" target="_blank" rel="noopener">AED - Mandatory invoice details</a></li>
    <li><a href="https://ec.europa.eu/taxation_customs/vies/" target="_blank" rel="noopener">VIES - Intra-community VAT validation</a></li>
</ul>

<p class="text-sm text-slate-500 mt-6"><em>Article updated on 30 July 2026.</em></p>

<h2>Conclusion</h2>

<p>Invoicing as a freelancer in Luxembourg is not complicated once you know the rules. Using suitable invoicing software such as faktur.lu lets you create compliant invoices in a few clicks, with every mandatory detail and the right VAT rates applied automatically. Focus on your craft — we will handle your compliance!</p>

<div class="mt-8 p-4 bg-slate-50 rounded-xl"><h3 class="text-base font-semibold text-slate-900 mb-3">Related articles</h3><ul class="space-y-1"><li><a href="/en/blog/5-erreurs-frequentes-facture-freelance-luxembourg" class="text-primary-500 hover:text-primary-600 text-sm">5 common mistakes on a freelance invoice →</a></li><li><a href="/en/blog/mentions-obligatoires-facture-luxembourg" class="text-primary-500 hover:text-primary-600 text-sm">Mandatory details on a Luxembourg invoice →</a></li><li><a href="/en/blog/franchise-tva-luxembourg-seuil-obligations-regime-normal" class="text-primary-500 hover:text-primary-600 text-sm">Luxembourg VAT exemption (€50,000 threshold) →</a></li></ul></div>
HTML;
    }

    private function lb(): string
    {
        return <<<'HTML'
<p class="lead">Fänkt Dir als Freelancer zu Lëtzebuerg un? D'Fakturatioun ass e wesentlechen Deel vun Ärer Aktivitéit. Dëse Guide erkläert Iech, wéi Dir 2026 konform Rechnunge erstellt an Är steierlech Flichte verwalt (aktualiséiert no der Erhéijung vum TVA-Franchisesseuil op 50 000 €).</p>

<h2>De Freelancer-Statut zu Lëtzebuerg</h2>

<p>Zu Lëtzebuerg schafft de Freelancer (oder Onofhängegen) allgemeng ënner engem vun dëse Statuten:</p>

<ul>
    <li><strong>Eenzelentreprise</strong>: de gängegste Statut fir unzefänken</li>
    <li><strong>Eepersounegesellschaft (SARL-S)</strong>: eng vereinfacht Gesellschaft mat limitéierter Haftung</li>
    <li><strong>Fräie Beruff</strong>: fir gewësse reglementéiert Aktivitéiten</li>
</ul>

<p>Egal wéi Äre Statut ass, et gëllen déi selwecht Reegele fir d'Fakturatioun.</p>

<h2>Sech fir d'TVA aschreiwen</h2>

<p>Ier Dir ufänkt ze fakturéieren (iwwer de Franchisesseuil, kuckt hei ënnen), musst Dir eng <strong>innergemeinschaftlech TVA-Nummer</strong> bei der <a href="https://pfi.public.lu/" target="_blank" rel="noopener">AED (Administration de l'Enregistrement, des Domaines et de la TVA)</a> ufroen.</p>

<h3>Aschreiwungsprozedur</h3>

<ol>
    <li>Eng <strong>Etablissementsautorisatioun</strong> beim Wirtschaftsministère kréien</li>
    <li>Sech am <strong>Handelsregëster (RCS)</strong> aschreiwen, wa relevant</li>
    <li>D'<strong>TVA-Aschreiwung</strong> iwwer <a href="https://www.myguichet.lu/" target="_blank" rel="noopener">MyGuichet.lu</a> ufroen</li>
    <li>Är Nummer am Format <strong>LU + 8 Zifferen</strong> kréien</li>
</ol>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold text-amber-800">⚠️ Opgepasst</p>
    <p class="text-amber-700">Ënner dem Franchisesseuil (Ëmsaz ≤ 50 000 € ouni TVA, Art. 57bis LIVA zanter 2025) ass d'TVA-Aschreiwung net obligatoresch. Dir fakturéiert dann ouni TVA mat der Mentioun „TVA net applicabel – Artikel 57bis". <strong>D'Kéiersäit:</strong> Dir kritt d'TVA op Är berufflech Akeef (Material, Software, Ënneropträg) net zréck. Wien investéiert, fir deen ka d'Franchise méi deier ginn wéi se abréngt.</p>
</div>

<h2>D'obligatoresch Mentiounen op Äre Rechnungen</h2>

<p>No <strong>Artikel 63 LIVA</strong> mussen Är Rechnungen als Freelancer enthalen:</p>

<h3>Är Informatiounen</h3>

<ul>
    <li><strong>Vollstännege Numm</strong> oder Firmennumm</li>
    <li><strong>Beruffsadress</strong> zu Lëtzebuerg</li>
    <li><strong>TVA-Nummer</strong> (LU12345678)</li>
    <li>Eventuell Är RCS-Nummer (obligatoresch fir agedroe Händler/Gesellschaften)</li>
</ul>

<h3>Informatioune vum Client</h3>

<ul>
    <li>Numm oder Firmennumm</li>
    <li>Vollstänneg Adress</li>
    <li>TVA-Nummer (obligatoresch fir professionell Clienten an der EU)</li>
</ul>

<h3>Detailer vun der Leeschtung</h3>

<ul>
    <li><strong>Eenzeg a fortlafend Rechnungsnummer</strong> (Artikel 63 LIVA, Punkt 3°)</li>
    <li><strong>Ausstellungsdatum</strong></li>
    <li><strong>Leeschtungsdatum</strong> (Steiertatbestand)</li>
    <li><strong>Detailléiert Beschreiwung</strong> vun de geleeschten Déngschter</li>
    <li><strong>Unzuel vu Stonnen oder Deeg</strong> (recommandéiert)</li>
    <li><strong>Eenheetspräis ouni TVA</strong></li>
    <li><strong>Betrag ouni TVA, TVA a Gesamtbetrag</strong></li>
    <li><strong>Applikabelen TVA-Taux</strong></li>
</ul>

<h2>Wéi enged TVA-Taux applizéieren?</h2>

<p>Als Freelancer applizéiert Dir allgemeng den <strong>Normaltaux vun 17 %</strong> fir déi meeschten Déngschtleeschtungen.</p>

<h3>Besonnesch Fäll</h3>

<table class="w-full border-collapse border border-gray-300 my-6">
    <thead class="bg-gray-100">
        <tr>
            <th class="border border-gray-300 px-4 py-2 text-left">Situatioun</th>
            <th class="border border-gray-300 px-4 py-2 text-left">Applikabel TVA</th>
        </tr>
    </thead>
    <tbody>
        <tr><td class="border border-gray-300 px-4 py-2">Professionelle Client zu Lëtzebuerg</td><td class="border border-gray-300 px-4 py-2">TVA 17 %</td></tr>
        <tr><td class="border border-gray-300 px-4 py-2">Professionelle Client an der EU (B2B innergemeinschaftlech)</td><td class="border border-gray-300 px-4 py-2">0 % (Autoliquidatioun, Art. 17 LIVA + Art. 196 Direktiv)</td></tr>
        <tr><td class="border border-gray-300 px-4 py-2">Client ausserhalb vun der EU</td><td class="border border-gray-300 px-4 py-2">0 % (fir déi meescht intellektuell Leeschtungen ausserhalb vum Lëtzebuerger TVA-Beräich)</td></tr>
        <tr><td class="border border-gray-300 px-4 py-2">Privatclient zu Lëtzebuerg</td><td class="border border-gray-300 px-4 py-2">TVA 17 %</td></tr>
    </tbody>
</table>

<h2>Engem Client am Ausland fakturéieren</h2>

<h3>Professionelle Client an der EU</h3>

<p>Wann Äre Client eng Entreprise an engem anere EU-Land ass:</p>

<ol>
    <li><strong>Iwwerpréift seng TVA-Nummer</strong> am <a href="https://ec.europa.eu/taxation_customs/vies/" target="_blank" rel="noopener">VIES</a>-System</li>
    <li><strong>Applizéiert keng TVA</strong> op Ärer Rechnung (Ort vun der Besteierung beim Client, Art. 17 LIVA)</li>
    <li><strong>Setzt d'Mentioun derbäi</strong>: <em>„Autoliquidatioun – Artikel 196 vun der Direktiv 2006/112/EG"</em></li>
    <li><strong>Gitt d'TVA-Nummer</strong> vum Client op der Rechnung un</li>
</ol>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">⚠ Obligatoresch Mentioun – korrekt gesetzlech Basis</p>
    <p>Déi kanonesch Mentioun fir d'innergemeinschaftlech B2B-Autoliquidatioun ass <strong>„Autoliquidatioun – Artikel 196 vun der Direktiv 2006/112/EG"</strong>. Den Artikel 196 bezeechent de Client als Schëllner (Art. 226 §11bis Direktiv). Net ze verwiesselen mam Artikel 44 vun der Direktiv (Ort vun der Besteierung) nach mam Artikel 44 vum Lëtzebuerger Gesetz vun 1979 (sektoriell Befreiungen, ouni Zesummenhang).</p>
</div>

<h3>Client ausserhalb vun der EU</h3>

<p>Fir Clienten ausserhalb vun der Europäescher Unioun falen déi gängeg intellektuell Leeschtunge vun engem Freelancer (Beroodung, Informatik, Reklamm, Iwwersetzung) net an de Lëtzebuerger TVA-Beräich. Vermierkt „TVA net applicabel – Déngscht ausserhalb vun der EU lokaliséiert".</p>

<h2>D'Nummeréierung vun Äre Rechnungen (Artikel 63 LIVA, Punkt 3°)</h2>

<p>Är Rechnunge mussen enger <strong>chronologescher a kontinuéierlecher Nummeréierung</strong> follegen:</p>

<ul>
    <li>Keng Lack an der Sequenz</li>
    <li>Fräit awer kohärent Format (z. B. 2026-001, 2026-002…)</li>
    <li>Jäerlechen Neistart erlaabt</li>
</ul>

<div class="bg-purple-50 border-l-4 border-purple-500 p-4 my-6">
    <p class="font-semibold text-purple-800">💡 Tipp</p>
    <p class="text-purple-700">Notzt eng Fakturatiounssoftware wéi faktur.lu, fir automatesch konform Nummeren ze generéieren a Feeler ze vermeiden.</p>
</div>

<h2>Är TVA-Deklaratioune verwalten</h2>

<p>Eemol ageschriwwen, gitt Dir Deklaratiounen of, deenen hir Periodizitéit vun Ärem Ëmsaz ouni TVA ofhänkt. <strong>Et ass d'AED, déi Äert Regime bestëmmt</strong> — Dir wielt et net.</p>

<ul>
    <li><strong>Ëmsaz &lt; 112 000 €/Joer</strong>: nëmmen d'Jooresdeklaratioun</li>
    <li><strong>Ëmsaz tëscht 112 000 € an 620 000 €/Joer</strong>: Trimesterdeklaratiounen <strong>an</strong> d'Jooresdeklaratioun</li>
    <li><strong>Ëmsaz &gt; 620 000 €/Joer</strong>: Méintlech Deklaratiounen <strong>an</strong> d'Jooresdeklaratioun</li>
</ul>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold text-amber-800">⚠️ D'Jooresdeklaratioun ersetzt déi periodesch Deklaratiounen net</p>
    <p class="text-amber-700">Dat ass de deiersten Ierrtum op dëser Säit. Wann Dir Trimester- oder Méintdeklaratiounen ofgitt, musst Dir <strong>zousätzlech</strong> eng <strong>récapitulativ Jooresdeklaratioun</strong> ofginn, virum <strong>1. Mee vum nächste Joer</strong>. Se ze vergiessen léist Verspéidungsstrofen aus, obwuel Dir Är TVA am Laf vum Joer komplett bezuelt hutt.</p>
</div>

<p>Déi periodesch Deklaratioune ginn ier den 15. vum Mount no der betreffender Period ofginn. Alles leeft online iwwer <strong>eCDF (eTVA)</strong>.</p>

<h2>D'FAIA-Datei fir Freelancer</h2>

<p>Wann Dir eng Fakturatiounssoftware notzt, musst Dir op Ufro vun der AED eng <strong>FAIA-Datei</strong> produzéiere kënnen – ënner Bedingungen. D'AED-FAQ schléisst Assujettien mat engem Ëmsaz ≤ 112 000 € ouni TVA aus, sou datt ganz kleng Freelancer an der Franchise allgemeng keng FAIA-Datei musse virleeën. Mat Ärer Fiduciaire no Ärem Regime ze bestätegen.</p>

<div class="bg-green-50 border-l-4 border-green-500 p-4 my-6">
    <p class="font-semibold text-green-800">✅ faktur.lu generéiert Är FAIA-Datei</p>
    <p class="text-green-700">Eis Software produzéiert automatesch eng konform FAIA-2.01-Datei, prett fir all Steierkontroll vun der AED.</p>
</div>

<h2>Rotschléi fir Freelancer um Ufank</h2>

<ol>
    <li><strong>Notzt vun Ufank un eng ugepasste Software</strong>, fir Feeler ze vermeiden</li>
    <li><strong>Haalt all Är Rechnungen op</strong> (ausgestallt an erhalen) fir 10 Joer (Art. 16 Handelsgesetzbuch + Art. 65 LIVA)</li>
    <li><strong>Trennt</strong> Är privat a berufflech Konten</li>
    <li><strong>Fakturéiert séier</strong> (spéitstens den 15. vum Mount no der Leeschtung, Art. 63 LIVA)</li>
    <li><strong>Iwwerpréift d'TVA-Nummeren</strong> vun Äre EU-Clienten ier Dir fakturéiert (VIES)</li>
    <li><strong>Notéiert Är zwee Termine</strong>: déi periodesch Deklaratiounen an d'Jooresdeklaratioun</li>
    <li><strong>Frot e Comptabel</strong> bei komplexe Froen</li>
</ol>

<h2>Heefeg Feeler ze vermeiden</h2>

<ul>
    <li>❌ Ouni TVA-Nummer fakturéieren (wann Dir assujetti sidd)</li>
    <li>❌ Obligatoresch Mentioune vergiessen (Art. 63 LIVA)</li>
    <li>❌ E falschen TVA-Taux applizéieren</li>
    <li>❌ Net-sequenziell Nummeréierung</li>
    <li>❌ No dem 15. vum Mount no der Leeschtung fakturéieren</li>
    <li>❌ D'EU-TVA-Nummeren net iwwerpréiwen (VIES)</li>
    <li>❌ <strong>Mengen, d'Trimesterdeklaratioune géifen d'Jooresdeklaratioun erspueren</strong></li>
    <li>❌ „Artikel 44 Direktiv" (Ort vun der Besteierung) mam „Artikel 196 Direktiv" (Mentioun Autoliquidatioun) verwiesselen</li>
</ul>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">All Joer ze préiwen</p>
    <p>D'Seuilen, Tariffer an TVA-Prozedure kënnen änneren. Dës Säit gëtt reegelméisseg aktualiséiert – fir Är perséinlech Situatioun frot Är Fiduciaire oder d'<a href="https://pfi.public.lu/" target="_blank" rel="noopener">AED</a>.</p>
</div>

<h2>Offiziell Quellen</h2>

<ul>
    <li><a href="https://legilux.public.lu/eli/etat/leg/loi/1979/02/12/n1/jo" target="_blank" rel="noopener">Geännert Gesetz vum 12. Februar 1979 (LIVA) – Artikelen 17, 57bis, 63, 65</a></li>
    <li><a href="https://guichet.public.lu/fr/entreprises/fiscalite/impots-benefices/tva/declarations/declaration-tva.html" target="_blank" rel="noopener">Guichet.lu – TVA-Deklaratiounen a Periodizitéiten</a></li>
    <li><a href="https://pfi.public.lu/fr/professionnel/tva/sme.html" target="_blank" rel="noopener">AED – Franchisereegelung (Art. 57bis)</a></li>
    <li><a href="https://pfi.public.lu/fr/professionnel/tva/en-cours-activite-economique/que-doivent-contenir-factures.html" target="_blank" rel="noopener">AED – Obligatoresch Mentiounen op de Rechnungen</a></li>
    <li><a href="https://ec.europa.eu/taxation_customs/vies/" target="_blank" rel="noopener">VIES – Validatioun vun innergemeinschaftlechen TVA-Nummeren</a></li>
</ul>

<p class="text-sm text-slate-500 mt-6"><em>Artikel aktualiséiert den 30. Juli 2026.</em></p>

<h2>Conclusioun</h2>

<p>D'Fakturatioun als Freelancer zu Lëtzebuerg ass net komplizéiert, wann Dir d'Reegele befollegt. Mat enger ugepasster Fakturatiounssoftware wéi faktur.lu erstellt Dir konform Rechnungen a wéinege Klicken, mat alle obligatoresche Mentiounen an den automatesch richtegen TVA-Tariffer. Konzentréiert Iech op Äre Beruff – ëm Är Konformitéit këmmere mir eis!</p>

<div class="mt-8 p-4 bg-slate-50 rounded-xl"><h3 class="text-base font-semibold text-slate-900 mb-3">Verbonnen Artikelen</h3><ul class="space-y-1"><li><a href="/lb/blog/5-erreurs-frequentes-facture-freelance-luxembourg" class="text-primary-500 hover:text-primary-600 text-sm">5 heefeg Feeler op enger Freelancer-Rechnung →</a></li><li><a href="/lb/blog/mentions-obligatoires-facture-luxembourg" class="text-primary-500 hover:text-primary-600 text-sm">Obligatoresch Mentiounen op enger Lëtzebuerger Rechnung →</a></li><li><a href="/lb/blog/franchise-tva-luxembourg-seuil-obligations-regime-normal" class="text-primary-500 hover:text-primary-600 text-sm">TVA-Franchise Lëtzebuerg (Seuil 50 000 €) →</a></li></ul></div>
HTML;
    }

    private function pt(): string
    {
        return <<<'HTML'
<p class="lead">Está a lançar a sua atividade como freelancer no Luxemburgo? A faturação é um aspeto crucial da sua atividade. Este guia explica-lhe como criar faturas conformes em 2026 e gerir as suas obrigações fiscais (atualizado na sequência do aumento do limiar de isenção de IVA para 50 000 €).</p>

<h2>O estatuto de freelancer no Luxemburgo</h2>

<p>No Luxemburgo, o freelancer (ou trabalhador independente) exerce geralmente sob um destes estatutos:</p>

<ul>
    <li><strong>Empresa em nome individual</strong>: o estatuto mais comum para começar</li>
    <li><strong>Sociedade unipessoal (SARL-S)</strong>: uma sociedade por quotas simplificada</li>
    <li><strong>Profissão liberal</strong>: para certas atividades regulamentadas</li>
</ul>

<p>Seja qual for o seu estatuto, aplicam-se as mesmas regras de faturação.</p>

<h2>Registar-se no IVA</h2>

<p>Antes de começar a faturar (acima do limiar de isenção, ver abaixo), deve obter um <strong>número de IVA intracomunitário</strong> junto da <a href="https://pfi.public.lu/" target="_blank" rel="noopener">AED (Administração do registo, dos domínios e do IVA)</a>.</p>

<h3>Procedimento de registo</h3>

<ol>
    <li>Obter uma <strong>autorização de estabelecimento</strong> junto do Ministério da Economia</li>
    <li>Inscrever-se no <strong>Registo Comercial (RCS)</strong> se aplicável</li>
    <li>Pedir o <strong>registo de IVA</strong> através do <a href="https://www.myguichet.lu/" target="_blank" rel="noopener">MyGuichet.lu</a></li>
    <li>Receber o seu número no formato <strong>LU + 8 dígitos</strong></li>
</ol>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold text-amber-800">⚠️ Atenção</p>
    <p class="text-amber-700">Abaixo do limiar de isenção (volume de negócios ≤ 50 000 € sem IVA, art. 57bis LIVA desde 2025), o registo de IVA não é obrigatório. Fatura então sem IVA com a menção «IVA não aplicável - Artigo 57bis». <strong>Contrapartida:</strong> não recupera o IVA das suas compras profissionais (equipamento, software, subcontratação). Se investe, a isenção pode custar-lhe mais do que lhe rende.</p>
</div>

<h2>As menções obrigatórias nas suas faturas</h2>

<p>Segundo o <strong>artigo 63 LIVA</strong>, enquanto freelancer, as suas faturas devem conter:</p>

<h3>As suas informações</h3>

<ul>
    <li><strong>Nome completo</strong> ou denominação social</li>
    <li><strong>Endereço profissional</strong> no Luxemburgo</li>
    <li><strong>Número de IVA</strong> (LU12345678)</li>
    <li>Eventualmente o seu número RCS (obrigatório para comerciantes/sociedades inscritos)</li>
</ul>

<h3>Informações do cliente</h3>

<ul>
    <li>Nome ou denominação social</li>
    <li>Endereço completo</li>
    <li>Número de IVA (obrigatório para clientes profissionais intra-UE)</li>
</ul>

<h3>Detalhes da prestação</h3>

<ul>
    <li><strong>Número de fatura</strong> único e sequencial (artigo 63 LIVA, ponto 3°)</li>
    <li><strong>Data de emissão</strong></li>
    <li><strong>Data da prestação</strong> (facto gerador do IVA)</li>
    <li><strong>Descrição detalhada</strong> dos serviços prestados</li>
    <li><strong>Número de horas ou dias</strong> (recomendado)</li>
    <li><strong>Preço unitário sem IVA</strong></li>
    <li><strong>Montante sem IVA, IVA e total</strong></li>
    <li><strong>Taxa de IVA aplicável</strong></li>
</ul>

<h2>Que taxa de IVA aplicar?</h2>

<p>Enquanto freelancer, aplica geralmente a <strong>taxa normal de 17 %</strong> à maioria das prestações de serviços.</p>

<h3>Casos particulares</h3>

<table class="w-full border-collapse border border-gray-300 my-6">
    <thead class="bg-gray-100">
        <tr>
            <th class="border border-gray-300 px-4 py-2 text-left">Situação</th>
            <th class="border border-gray-300 px-4 py-2 text-left">IVA aplicável</th>
        </tr>
    </thead>
    <tbody>
        <tr><td class="border border-gray-300 px-4 py-2">Cliente profissional no Luxemburgo</td><td class="border border-gray-300 px-4 py-2">IVA 17 %</td></tr>
        <tr><td class="border border-gray-300 px-4 py-2">Cliente profissional na UE (B2B intra-UE)</td><td class="border border-gray-300 px-4 py-2">0 % (autoliquidação, art. 17 LIVA + art. 196 diretiva)</td></tr>
        <tr><td class="border border-gray-300 px-4 py-2">Cliente fora da UE</td><td class="border border-gray-300 px-4 py-2">0 % (fora do âmbito do IVA luxemburguês para a maioria das prestações intelectuais)</td></tr>
        <tr><td class="border border-gray-300 px-4 py-2">Cliente particular no Luxemburgo</td><td class="border border-gray-300 px-4 py-2">IVA 17 %</td></tr>
    </tbody>
</table>

<h2>Faturar a um cliente no estrangeiro</h2>

<h3>Cliente profissional na UE</h3>

<p>Se o seu cliente for uma empresa noutro país da UE:</p>

<ol>
    <li><strong>Verifique o número de IVA</strong> no sistema <a href="https://ec.europa.eu/taxation_customs/vies/" target="_blank" rel="noopener">VIES</a></li>
    <li><strong>Não aplique IVA</strong> na sua fatura (lugar de tributação no adquirente, art. 17 LIVA)</li>
    <li><strong>Acrescente a menção</strong>: <em>«Autoliquidação - Artigo 196.º da Diretiva 2006/112/CE»</em></li>
    <li><strong>Indique o número de IVA</strong> do cliente na fatura</li>
</ol>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">⚠ Menção obrigatória - base legal correta</p>
    <p>A menção canónica para a autoliquidação B2B intra-UE é <strong>«Autoliquidação - Artigo 196.º da Diretiva 2006/112/CE»</strong>. O artigo 196.º designa o adquirente como devedor (art. 226.º §11bis da diretiva). Não confundir com o artigo 44.º da diretiva (lugar de tributação) nem com o artigo 44.º da lei luxemburguesa de 1979 (isenções setoriais, sem relação).</p>
</div>

<h3>Cliente fora da UE</h3>

<p>Para clientes situados fora da União Europeia, as prestações intelectuais correntes do freelancer (consultoria, informática, publicidade, tradução) ficam fora do âmbito do IVA luxemburguês. Mencione «IVA não aplicável - serviço localizado fora da UE».</p>

<h2>A numeração das suas faturas (artigo 63 LIVA, ponto 3°)</h2>

<p>As suas faturas devem seguir uma <strong>numeração cronológica e contínua</strong>:</p>

<ul>
    <li>Sem falhas na sequência</li>
    <li>Formato livre mas coerente (ex.: 2026-001, 2026-002…)</li>
    <li>Reinício anual autorizado</li>
</ul>

<div class="bg-purple-50 border-l-4 border-purple-500 p-4 my-6">
    <p class="font-semibold text-purple-800">💡 Conselho</p>
    <p class="text-purple-700">Utilize um software de faturação como o faktur.lu para gerar automaticamente números conformes e evitar erros.</p>
</div>

<h2>Gerir as suas declarações de IVA</h2>

<p>Uma vez registado, entrega declarações cuja periodicidade depende do seu volume de negócios sem IVA. <strong>É a AED que determina o seu regime</strong> — não é o contribuinte que o escolhe.</p>

<ul>
    <li><strong>Volume de negócios &lt; 112 000 €/ano</strong>: apenas declaração anual</li>
    <li><strong>Entre 112 000 € e 620 000 €/ano</strong>: declarações trimestrais <strong>e</strong> declaração anual</li>
    <li><strong>Superior a 620 000 €/ano</strong>: declarações mensais <strong>e</strong> declaração anual</li>
</ul>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold text-amber-800">⚠️ A declaração anual não substitui as declarações periódicas</p>
    <p class="text-amber-700">É o equívoco mais dispendioso desta página. Se entrega declarações trimestrais ou mensais, deve <strong>além disso</strong> entregar uma <strong>declaração anual recapitulativa</strong>, até <strong>1 de maio do ano seguinte</strong>. Esquecê-la desencadeia penalizações por entrega tardia, mesmo tendo pago todo o seu IVA ao longo do ano.</p>
</div>

<p>As declarações periódicas entregam-se até ao dia 15 do mês seguinte ao período em causa. Tudo se faz em linha através do <strong>eCDF (eTVA)</strong>.</p>

<h2>O ficheiro FAIA para os freelancers</h2>

<p>Se utiliza um software de faturação, deve ser capaz de produzir um <strong>ficheiro FAIA</strong> a pedido da AED - sob condições. As FAQ da AED excluem os sujeitos passivos com volume de negócios ≤ 112 000 € sem IVA, pelo que os freelancers muito pequenos em regime de isenção não são geralmente obrigados a produzir o FAIA. A confirmar com o seu contabilista consoante o regime.</p>

<div class="bg-green-50 border-l-4 border-green-500 p-4 my-6">
    <p class="font-semibold text-green-800">✅ O faktur.lu gera o seu ficheiro FAIA</p>
    <p class="text-green-700">O nosso software produz automaticamente um ficheiro FAIA 2.01 conforme, pronto para qualquer inspeção fiscal da AED.</p>
</div>

<h2>Conselhos para freelancers iniciantes</h2>

<ol>
    <li><strong>Utilize um software adequado</strong> desde o início para evitar erros</li>
    <li><strong>Conserve todas as faturas</strong> (emitidas e recebidas) durante 10 anos (art. 16 Código Comercial + art. 65 LIVA)</li>
    <li><strong>Separe</strong> as contas pessoais e profissionais</li>
    <li><strong>Fature rapidamente</strong> (até ao dia 15 do mês seguinte à prestação, art. 63 LIVA)</li>
    <li><strong>Verifique os números de IVA</strong> dos seus clientes da UE antes de faturar (VIES)</li>
    <li><strong>Anote os seus dois prazos</strong>: as declarações periódicas e a declaração anual</li>
    <li><strong>Consulte um contabilista</strong> para questões complexas</li>
</ol>

<h2>Erros correntes a evitar</h2>

<ul>
    <li>❌ Faturar sem número de IVA (se lhe for exigível)</li>
    <li>❌ Esquecer menções obrigatórias (art. 63 LIVA)</li>
    <li>❌ Aplicar uma taxa de IVA errada</li>
    <li>❌ Numeração não sequencial</li>
    <li>❌ Faturar depois do dia 15 do mês seguinte à prestação</li>
    <li>❌ Não verificar os números de IVA da UE (VIES)</li>
    <li>❌ <strong>Julgar que as declarações trimestrais dispensam a declaração anual</strong></li>
    <li>❌ Confundir «artigo 44.º da diretiva» (lugar de tributação) e «artigo 196.º da diretiva» (menção de autoliquidação)</li>
</ul>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">A verificar todos os anos</p>
    <p>Os limiares, taxas e procedimentos de IVA podem evoluir. Esta página é atualizada regularmente, mas para a sua situação pessoal, consulte o seu contabilista ou a <a href="https://pfi.public.lu/" target="_blank" rel="noopener">AED</a>.</p>
</div>

<h2>Fontes oficiais</h2>

<ul>
    <li><a href="https://legilux.public.lu/eli/etat/leg/loi/1979/02/12/n1/jo" target="_blank" rel="noopener">Lei alterada de 12 de fevereiro de 1979 (LIVA) - artigos 17, 57bis, 63, 65</a></li>
    <li><a href="https://guichet.public.lu/fr/entreprises/fiscalite/impots-benefices/tva/declarations/declaration-tva.html" target="_blank" rel="noopener">Guichet.lu - Declarações de IVA e periodicidades</a></li>
    <li><a href="https://pfi.public.lu/fr/professionnel/tva/sme.html" target="_blank" rel="noopener">AED - Regime de isenção (art. 57bis)</a></li>
    <li><a href="https://pfi.public.lu/fr/professionnel/tva/en-cours-activite-economique/que-doivent-contenir-factures.html" target="_blank" rel="noopener">AED - Menções obrigatórias nas faturas</a></li>
    <li><a href="https://ec.europa.eu/taxation_customs/vies/" target="_blank" rel="noopener">VIES - Validação de IVA intracomunitário</a></li>
</ul>

<p class="text-sm text-slate-500 mt-6"><em>Artigo atualizado a 30 de julho de 2026.</em></p>

<h2>Conclusão</h2>

<p>Faturar como freelancer no Luxemburgo não é complicado se seguir as regras. Utilizar um software de faturação adequado como o faktur.lu permite-lhe criar faturas conformes em poucos cliques, com todas as menções obrigatórias e as taxas de IVA corretas aplicadas automaticamente. Concentre-se no seu ofício, nós tratamos da sua conformidade!</p>

<div class="mt-8 p-4 bg-slate-50 rounded-xl"><h3 class="text-base font-semibold text-slate-900 mb-3">Artigos relacionados</h3><ul class="space-y-1"><li><a href="/pt/blog/5-erreurs-frequentes-facture-freelance-luxembourg" class="text-primary-500 hover:text-primary-600 text-sm">5 erros frequentes numa fatura de freelancer →</a></li><li><a href="/pt/blog/mentions-obligatoires-facture-luxembourg" class="text-primary-500 hover:text-primary-600 text-sm">Menções obrigatórias numa fatura luxemburguesa →</a></li><li><a href="/pt/blog/franchise-tva-luxembourg-seuil-obligations-regime-normal" class="text-primary-500 hover:text-primary-600 text-sm">Isenção de IVA no Luxemburgo (limiar 50 000 €) →</a></li></ul></div>
HTML;
    }
};
