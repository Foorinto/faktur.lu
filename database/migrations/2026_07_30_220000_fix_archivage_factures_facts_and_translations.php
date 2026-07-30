<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Article « archivage-factures-luxembourg-duree-legale-format » —
 * vérification factuelle et traductions intégrales.
 *
 * Source de référence : texte coordonné de la loi TVA du 12 février 1979
 * publié par l'AED, article 65, lu verbatim.
 *
 * 1. POINT DE DÉPART DU DÉLAI ERRONÉ. L'article affirmait que « le délai
 *    court à partir de la fin de l'exercice fiscal », avec l'exemple
 *    d'une facture émise le 15 mars 2026 à conserver « jusqu'au
 *    31 décembre 2036 ». L'article 65, paragraphe 4, point 1° dit :
 *      « Ces factures et copies de factures doivent être stockées pendant
 *        une période de dix ans à partir de leur DATE D'ÉMISSION. »
 *    Le point de départ de la fin d'exercice vaut pour les LIVRES
 *    (« à partir de leur clôture »), pas pour les factures. L'article
 *    appliquait aux factures la règle des livres. La version publiée
 *    était plus longue que l'obligation réelle, donc sans danger de
 *    non-conformité — mais elle décrivait faussement la règle, et la
 *    distinction entre les deux régimes est précisément ce qu'un lecteur
 *    vient chercher.
 *
 * 2. CHAPITRE ENTIER MANQUANT — LE LIEU DE STOCKAGE. L'article 65,
 *    paragraphes 6 et 7, encadre où les archives peuvent résider. Rien
 *    n'en figurait, alors que la question se pose à quiconque utilise un
 *    service en ligne :
 *      - principe : l'assujetti détermine librement le lieu, à condition
 *        de tout mettre à disposition de l'administration « sans retard
 *        indu, à toute réquisition de sa part » ;
 *      - interdiction de stocker dans un pays sans instrument
 *        d'assistance mutuelle comparable, ou sans droit d'accès
 *        électronique ;
 *      - un assujetti établi au Luxembourg doit y stocker ses factures
 *        « lorsque le stockage n'est pas effectué par une voie
 *        électronique garantissant un accès complet et en ligne » —
 *        autrement dit, les archives papier restent au Luxembourg ;
 *      - obligation de DÉCLARER le lieu de stockage à l'administration
 *        lorsqu'il est hors du territoire, dans la déclaration annuelle
 *        prévue à l'article 64, paragraphe 7 ;
 *      - stockage électronique dans un autre État membre : obligation
 *        d'assurer aux agents un droit d'accès par voie électronique, de
 *        téléchargement et d'utilisation.
 *
 * 3. NOS PROPRES CLAIMS PRODUIT ÉTAIENT SURVENDUS.
 *    « Chaque facture finalisée est automatiquement archivée en PDF/A » :
 *    faux sur deux points vérifiés dans le code. L'archivage n'est pas
 *    automatique — c'est une action explicite (route POST
 *    invoices/{invoice}/archive, plus un traitement par lot), réservée
 *    au plan disposant de la fonctionnalité pdf_archive. Et la
 *    conversion PDF/A dépend de la présence de Ghostscript sur le
 *    serveur : à défaut, PdfArchiveService journalise « Ghostscript not
 *    available » et conserve un PDF standard. Description corrigée.
 *    Ce qui est exact et confirmé : PDF/A-1b par défaut, PDF/A-3b
 *    disponible, empreinte SHA-256, rétention de dix ans.
 *
 * 4. NUANCE PSDC. L'article écrivait que la loi du 25 juillet 2015
 *    « impose » le recours à un PSDC certifié. Elle le conditionne : le
 *    recours à un prestataire certifié est ce qui confère à la copie la
 *    valeur probante de l'original. Numériser sans PSDC reste possible,
 *    mais la copie ne bénéficie pas de cette présomption.
 *
 * 5. LIEN NON VÉRIFIABLE remplacé, comme dans l'article « mentions
 *    obligatoires » : l'ELI Legilux donné pour le Code de commerce
 *    renvoie vers une application JavaScript dont le contenu n'a pas pu
 *    être contrôlé.
 *
 * PARITÉ : luxembourgeois et allemand faisaient 3 251 et 3 681 caractères
 * contre 5 076, avec 2 liens contre 6.
 *
 * ⚠️ La version luxembourgeoise mérite une relecture par un locuteur natif.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['fr' => $this->fr(), 'de' => $this->de(), 'en' => $this->en(), 'lb' => $this->lb(), 'pt' => $this->pt()] as $locale => $content) {
            DB::table('blog_posts')
                ->where('translation_key', 'archivage-factures-luxembourg-duree-legale-format')
                ->where('locale', $locale)
                ->update(['content' => $content, 'updated_at' => now()]);
        }
    }

    public function down(): void
    {
        // Contenu rédactionnel : la version antérieure faisait courir le
        // délai des factures depuis la fin de l'exercice.
    }

    private function fr(): string
    {
        return <<<'HTML'
<p class="lead">L'archivage des factures est une obligation légale au Luxembourg : <strong>dix ans</strong>. Mais la question du <em>point de départ</em> de ce délai, et celle du <em>lieu</em> où les archives peuvent résider, sont plus précises qu'on ne le croit. Voici ce que dit exactement l'article 65 de la loi TVA.</p>

<h2>Durée légale de conservation</h2>

<h3>Factures : dix ans à partir de la date d'émission</h3>

<p>L'<strong>article 65, paragraphe 4</strong> de la <a href="https://pfi.public.lu/dam-assets/pdf/legislation/tva/loi/loi-tva-2023-01-01.pdf" target="_blank" rel="noopener">loi TVA</a> est explicite :</p>

<blockquote class="border-l-4 border-slate-300 pl-4 italic text-slate-600 my-6">
    « Ces factures et copies de factures doivent être stockées pendant une période de dix ans <strong>à partir de leur date d'émission</strong>. »
</blockquote>

<p>Une facture émise le <strong>15 mars 2026</strong> doit donc être conservée jusqu'au <strong>15 mars 2036</strong> — et non jusqu'à la fin de l'année 2036. La règle vaut pour les factures que vous émettez comme pour celles que vous recevez.</p>

<h3>Livres et autres documents</h3>

<p>Pour les autres pièces, le point de départ change :</p>

<ul>
    <li><strong>Livres comptables</strong> : dix ans à partir de leur <strong>clôture</strong></li>
    <li><strong>Autres documents</strong> : dix ans à partir de leur <strong>date</strong></li>
    <li><strong>Registres des interfaces électroniques</strong> (places de marché, plateformes) : dix ans à compter du <strong>31 décembre de l'année de l'opération</strong></li>
</ul>

<p>S'y ajoute l'obligation générale du <strong>Code de commerce</strong> (article 16), qui impose également dix ans pour les livres et la correspondance commerciale.</p>

<div class="bg-blue-50 border-l-4 border-blue-500 p-4 my-6">
    <p class="font-semibold">Une confusion fréquente</p>
    <p>Beaucoup de guides font courir le délai des factures depuis la fin de l'exercice. C'est la règle des <strong>livres</strong>, pas celle des factures. En pratique, aligner toutes vos purges sur la fin d'exercice vous fait conserver un peu plus longtemps que nécessaire : c'est prudent, mais ce n'est pas la règle.</p>
</div>

<h2>Où pouvez-vous stocker vos archives ?</h2>

<p>C'est la question que pose tout usage d'un service en ligne, et l'article 65 y répond au paragraphe 6.</p>

<h3>Le principe</h3>

<p>Vous <strong>déterminez librement le lieu de stockage</strong>, à une condition : mettre à disposition de l'administration, « <strong>sans retard indu, à toute réquisition de sa part</strong> », toutes les factures, informations, livres et documents concernés.</p>

<h3>Les limites</h3>

<ul>
    <li><strong>Pays sans assistance mutuelle</strong> : il est interdit de stocker dans un pays ou territoire avec lequel il n'existe aucun instrument d'assistance mutuelle de portée comparable, ni droit d'accès électronique</li>
    <li><strong>Archives papier</strong> : un assujetti établi au Luxembourg doit y stocker ses factures <strong>lorsque le stockage n'est pas électronique</strong> avec accès complet et en ligne. Concrètement, vos classeurs restent au Luxembourg</li>
    <li><strong>Déclaration obligatoire</strong> : si le lieu de stockage est <strong>hors du territoire luxembourgeois</strong>, vous devez le déclarer à l'administration — dans la <strong>déclaration annuelle</strong> prévue à l'article 64, paragraphe 7</li>
    <li><strong>Stockage dans un autre État membre</strong> : vous devez garantir aux agents de l'administration un droit d'<strong>accès par voie électronique, de téléchargement et d'utilisation</strong> de ces factures</li>
</ul>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">Point à vérifier si vous utilisez un service en ligne</p>
    <p>Demandez à votre prestataire <strong>où sont physiquement hébergées les données</strong>. Si les serveurs sont hors du Luxembourg, l'obligation de déclaration dans votre déclaration annuelle s'applique — même si le service est européen. C'est une formalité simple, mais elle est facile à oublier.</p>
</div>

<h2>Formats d'archivage acceptés</h2>

<p>L'article 65, paragraphe 5, pose l'exigence de fond : l'<strong>authenticité de l'origine</strong>, l'<strong>intégrité du contenu</strong> et la <strong>lisibilité</strong> doivent être assurées pendant toute la période de stockage.</p>

<h3>Archivage papier</h3>
<p>Les originaux papier doivent être conservés dans un endroit sec et accessible, sans altération, et — comme vu plus haut — sur le territoire luxembourgeois.</p>

<h3>Archivage numérique</h3>
<p>Le stockage électronique est valable « à condition que les données garantissant l'authenticité de l'origine et l'intégrité du contenu soient également stockées sous forme électronique ». En pratique :</p>

<ul>
    <li>Le format doit garantir l'<strong>intégrité</strong> du document</li>
    <li>Le document doit rester <strong>lisible</strong> pendant toute la durée de conservation</li>
    <li>Le format <strong>PDF/A</strong> (ISO 19005) est recommandé pour la lisibilité à long terme</li>
    <li>Une <strong>empreinte numérique</strong> (hash) permet de démontrer que le document n'a pas été modifié</li>
</ul>

<div class="bg-blue-50 border-l-4 border-blue-500 p-4 my-6">
    <p class="font-semibold">Numérisation et valeur probante</p>
    <p>Pour qu'une copie électronique ait la même <strong>valeur probante</strong> que l'original papier, la <a href="https://legilux.public.lu/eli/etat/leg/loi/2015/07/25/n1/jo" target="_blank" rel="noopener">loi du 25 juillet 2015</a> fait reposer cette présomption sur le recours à un <a href="https://ilnas.gouvernement.lu/" target="_blank" rel="noopener">PSDC</a> (Prestataire de Services de Dématérialisation ou de Conservation) certifié. Numériser sans PSDC reste possible, mais la copie ne bénéficie pas de cette présomption : en cas de contestation, c'est à vous de démontrer sa fidélité.</p>
</div>

<h2>Pourquoi le format PDF/A ?</h2>

<p>Le <strong>PDF/A</strong> est une norme ISO conçue pour l'archivage à long terme. Contrairement à un PDF standard :</p>

<ul>
    <li>Il <strong>embarque toutes les polices</strong> utilisées (pas de dépendance externe)</li>
    <li>Il <strong>interdit le JavaScript</strong> et les éléments multimédia</li>
    <li>Il vise à garantir que le document restera <strong>lisible dans dix, vingt ou cinquante ans</strong></li>
    <li>Il est <strong>largement reconnu</strong> par les administrations européennes</li>
</ul>

<h2>Archiver ses factures avec faktur.lu</h2>

<p>faktur.lu propose un archivage dédié, disponible sur les plans qui incluent la fonctionnalité :</p>

<ol>
    <li>Vous archivez une facture <strong>finalisée</strong> — à l'unité ou par lot</li>
    <li>Le document est converti en <strong>PDF/A-1b</strong> par défaut ; le <strong>PDF/A-3b</strong> est disponible si vous devez embarquer des pièces jointes</li>
    <li>Une <strong>empreinte SHA-256</strong> est calculée et enregistrée, ce qui permet de vérifier ensuite l'intégrité de l'archive</li>
    <li>Une <strong>échéance à dix ans</strong> est enregistrée avec l'archive</li>
    <li>Vous pouvez télécharger vos archives à tout moment</li>
</ol>

<p class="text-sm text-slate-500"><em>Précision technique : la conversion en PDF/A s'appuie sur Ghostscript côté serveur. Si l'outil n'est pas disponible, le document est conservé en PDF standard avec son empreinte — le format effectivement obtenu est enregistré avec l'archive, vous savez donc toujours ce que vous avez.</em></p>

<h2>Risques en cas de non-conformité</h2>

<p>En cas de contrôle fiscal, l'absence de factures ou un archivage non conforme peut entraîner :</p>

<ul>
    <li>Le <strong>rejet des déductions de TVA</strong> sur les factures manquantes</li>
    <li>Des <strong>amendes administratives</strong> (250 € à 10 000 € par infraction, art. 77 LIVA), et jusqu'à <strong>25 000 € par jour de retard</strong> après avertissement en cas de refus de communiquer les pièces lors d'un contrôle</li>
    <li>Une <strong>taxation d'office</strong> par l'administration</li>
</ul>

<h2>Sources officielles</h2>

<ul>
    <li><a href="https://pfi.public.lu/dam-assets/pdf/legislation/tva/loi/loi-tva-2023-01-01.pdf" target="_blank" rel="noopener">Loi TVA coordonnée - article 65 (conservation et stockage)</a></li>
    <li><a href="https://guichet.public.lu/fr/entreprises/gestion-juridique-comptabilite/comptable/enregistrement/obligations-comptables.html" target="_blank" rel="noopener">Guichet.lu - Obligations comptables des entreprises</a></li>
    <li><a href="https://legilux.public.lu/eli/etat/leg/loi/2015/07/25/n1/jo" target="_blank" rel="noopener">Loi du 25 juillet 2015 relative à l'archivage électronique</a></li>
    <li><a href="https://ilnas.gouvernement.lu/" target="_blank" rel="noopener">ILNAS - Prestataires PSDC certifiés</a></li>
</ul>

<div class="mt-8 p-4 bg-slate-50 rounded-xl"><h3 class="text-base font-semibold text-slate-900 mb-3">Articles connexes</h3><ul class="space-y-1"><li><a href="/fr/blog/faia-luxembourg-fichier-audit-informatise-guide" class="text-primary-500 hover:text-primary-600 text-sm">fichier FAIA →</a></li><li><a href="/fr/blog/controle-fiscal-luxembourg-comment-preparer" class="text-primary-500 hover:text-primary-600 text-sm">contrôle fiscal →</a></li></ul></div>

<div class="bg-primary-50 rounded-xl p-6 mt-8">
    <h3 class="text-lg font-semibold text-primary-900 mb-2">Archivage PDF/A avec faktur.lu</h3>
    <p class="text-primary-800 mb-4">Archivez vos factures finalisées en PDF/A avec empreinte SHA-256 et échéance à dix ans, à l'unité ou par lot.</p>
    <a href="/register" class="inline-block bg-primary-500 text-white font-semibold px-6 py-3 rounded-xl hover:bg-primary-600">Essayer gratuitement 14 jours</a>
</div>

<p class="text-sm text-slate-500 mt-6"><em>Article mis à jour le 30 juillet 2026.</em></p>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">À vérifier chaque année</p>
    <p>Les règles de conservation peuvent évoluer. Cette page est mise à jour régulièrement, mais pour votre situation personnelle, consultez votre fiduciaire ou directement l'<a href="https://pfi.public.lu/" target="_blank" rel="noopener">AED</a>.</p>
</div>
HTML;
    }

    private function de(): string
    {
        return <<<'HTML'
<p class="lead">Die Archivierung von Rechnungen ist in Luxemburg gesetzlich vorgeschrieben: <strong>zehn Jahre</strong>. Doch der <em>Beginn</em> dieser Frist und der <em>Ort</em>, an dem die Archive liegen dürfen, sind genauer geregelt, als man denkt. Hier steht, was Artikel 65 des MwSt.-Gesetzes tatsächlich sagt.</p>

<h2>Gesetzliche Aufbewahrungsdauer</h2>

<h3>Rechnungen: zehn Jahre ab dem Ausstellungsdatum</h3>

<p><strong>Artikel 65 Absatz 4</strong> des <a href="https://pfi.public.lu/dam-assets/pdf/legislation/tva/loi/loi-tva-2023-01-01.pdf" target="_blank" rel="noopener">MwSt.-Gesetzes</a> ist eindeutig:</p>

<blockquote class="border-l-4 border-slate-300 pl-4 italic text-slate-600 my-6">
    „Diese Rechnungen und Rechnungskopien sind für einen Zeitraum von zehn Jahren <strong>ab ihrem Ausstellungsdatum</strong> aufzubewahren."
</blockquote>

<p>Eine am <strong>15. März 2026</strong> ausgestellte Rechnung ist somit bis zum <strong>15. März 2036</strong> aufzubewahren — und nicht bis Ende 2036. Die Regel gilt für ausgestellte wie für erhaltene Rechnungen.</p>

<h3>Bücher und sonstige Unterlagen</h3>

<p>Für die übrigen Unterlagen ändert sich der Fristbeginn:</p>

<ul>
    <li><strong>Handelsbücher</strong>: zehn Jahre ab ihrem <strong>Abschluss</strong></li>
    <li><strong>Sonstige Unterlagen</strong>: zehn Jahre ab ihrem <strong>Datum</strong></li>
    <li><strong>Register elektronischer Schnittstellen</strong> (Marktplätze, Plattformen): zehn Jahre ab dem <strong>31. Dezember des Jahres des Umsatzes</strong></li>
</ul>

<p>Hinzu kommt die allgemeine Pflicht des <strong>Handelsgesetzbuchs</strong> (Artikel 16), das ebenfalls zehn Jahre für Bücher und Geschäftskorrespondenz vorsieht.</p>

<div class="bg-blue-50 border-l-4 border-blue-500 p-4 my-6">
    <p class="font-semibold">Eine häufige Verwechslung</p>
    <p>Viele Ratgeber lassen die Rechnungsfrist ab dem Ende des Geschäftsjahres laufen. Das ist die Regel für <strong>Bücher</strong>, nicht für Rechnungen. In der Praxis führt eine Ausrichtung aller Löschungen auf das Geschäftsjahresende dazu, dass Sie etwas länger aufbewahren als nötig: vorsichtig, aber nicht die Regel.</p>
</div>

<h2>Wo dürfen Sie Ihre Archive speichern?</h2>

<p>Das ist die Frage, die sich bei jedem Onlinedienst stellt, und Artikel 65 beantwortet sie in Absatz 6.</p>

<h3>Der Grundsatz</h3>

<p>Sie <strong>bestimmen den Speicherort frei</strong>, unter einer Bedingung: der Verwaltung „<strong>ohne unangemessene Verzögerung, auf jedes Verlangen hin</strong>" sämtliche Rechnungen, Informationen, Bücher und Unterlagen zur Verfügung zu stellen.</p>

<h3>Die Grenzen</h3>

<ul>
    <li><strong>Länder ohne Amtshilfe</strong>: Eine Speicherung in einem Land oder Gebiet, mit dem kein Amtshilfeinstrument vergleichbarer Reichweite und kein elektronisches Zugriffsrecht besteht, ist untersagt</li>
    <li><strong>Papierarchive</strong>: Ein in Luxemburg ansässiger Steuerpflichtiger muss seine Rechnungen dort speichern, <strong>wenn die Speicherung nicht elektronisch</strong> mit vollständigem Onlinezugriff erfolgt. Konkret: Ihre Ordner bleiben in Luxemburg</li>
    <li><strong>Meldepflicht</strong>: Liegt der Speicherort <strong>außerhalb des luxemburgischen Hoheitsgebiets</strong>, müssen Sie ihn der Verwaltung melden — in der <strong>Jahreserklärung</strong> nach Artikel 64 Absatz 7</li>
    <li><strong>Speicherung in einem anderen Mitgliedstaat</strong>: Sie müssen den Bediensteten der Verwaltung ein Recht auf <strong>elektronischen Zugriff, Herunterladen und Nutzung</strong> dieser Rechnungen gewährleisten</li>
</ul>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">Zu prüfen, wenn Sie einen Onlinedienst nutzen</p>
    <p>Fragen Sie Ihren Anbieter, <strong>wo die Daten physisch gehostet werden</strong>. Liegen die Server außerhalb Luxemburgs, greift die Meldepflicht in Ihrer Jahreserklärung — auch bei einem europäischen Dienst. Eine einfache Formalität, aber leicht zu vergessen.</p>
</div>

<h2>Zulässige Archivierungsformate</h2>

<p>Artikel 65 Absatz 5 stellt die inhaltliche Anforderung: <strong>Echtheit der Herkunft</strong>, <strong>Unversehrtheit des Inhalts</strong> und <strong>Lesbarkeit</strong> müssen während des gesamten Aufbewahrungszeitraums gewährleistet sein.</p>

<h3>Papierarchivierung</h3>
<p>Papieroriginale sind trocken und zugänglich aufzubewahren, unverändert und — wie oben gesehen — auf luxemburgischem Hoheitsgebiet.</p>

<h3>Digitale Archivierung</h3>
<p>Die elektronische Speicherung ist gültig, „sofern die Daten, die die Echtheit der Herkunft und die Unversehrtheit des Inhalts gewährleisten, ebenfalls elektronisch gespeichert werden". In der Praxis:</p>

<ul>
    <li>Das Format muss die <strong>Unversehrtheit</strong> des Dokuments sichern</li>
    <li>Das Dokument muss während der gesamten Dauer <strong>lesbar</strong> bleiben</li>
    <li>Das Format <strong>PDF/A</strong> (ISO 19005) wird für die Langzeitlesbarkeit empfohlen</li>
    <li>Ein <strong>digitaler Fingerabdruck</strong> (Hash) belegt, dass das Dokument nicht verändert wurde</li>
</ul>

<div class="bg-blue-50 border-l-4 border-blue-500 p-4 my-6">
    <p class="font-semibold">Digitalisierung und Beweiskraft</p>
    <p>Damit eine elektronische Kopie dieselbe <strong>Beweiskraft</strong> wie das Papieroriginal hat, knüpft das <a href="https://legilux.public.lu/eli/etat/leg/loi/2015/07/25/n1/jo" target="_blank" rel="noopener">Gesetz vom 25. Juli 2015</a> diese Vermutung an die Einschaltung eines zertifizierten <a href="https://ilnas.gouvernement.lu/" target="_blank" rel="noopener">PSDC</a> (Dienstleister für Dematerialisierung oder Aufbewahrung). Ohne PSDC zu digitalisieren bleibt möglich, doch die Kopie genießt diese Vermutung nicht: im Streitfall müssen Sie ihre Übereinstimmung selbst nachweisen.</p>
</div>

<h2>Warum das Format PDF/A?</h2>

<p><strong>PDF/A</strong> ist eine für die Langzeitarchivierung entwickelte ISO-Norm. Anders als ein Standard-PDF:</p>

<ul>
    <li>Es <strong>bettet alle verwendeten Schriften ein</strong> (keine externe Abhängigkeit)</li>
    <li>Es <strong>verbietet JavaScript</strong> und Multimedia-Elemente</li>
    <li>Es soll sicherstellen, dass das Dokument in <strong>zehn, zwanzig oder fünfzig Jahren lesbar</strong> bleibt</li>
    <li>Es ist bei europäischen Verwaltungen <strong>weithin anerkannt</strong></li>
</ul>

<h2>Rechnungen mit faktur.lu archivieren</h2>

<p>faktur.lu bietet eine eigene Archivierung, verfügbar in den Tarifen, die diese Funktion enthalten:</p>

<ol>
    <li>Sie archivieren eine <strong>finalisierte</strong> Rechnung — einzeln oder im Stapel</li>
    <li>Das Dokument wird standardmäßig nach <strong>PDF/A-1b</strong> konvertiert; <strong>PDF/A-3b</strong> steht zur Verfügung, wenn Anhänge eingebettet werden müssen</li>
    <li>Ein <strong>SHA-256-Fingerabdruck</strong> wird berechnet und gespeichert, sodass sich die Unversehrtheit des Archivs später prüfen lässt</li>
    <li>Eine <strong>Zehnjahresfrist</strong> wird mit dem Archiv hinterlegt</li>
    <li>Sie können Ihre Archive jederzeit herunterladen</li>
</ol>

<p class="text-sm text-slate-500"><em>Technischer Hinweis: Die PDF/A-Konvertierung stützt sich serverseitig auf Ghostscript. Ist das Werkzeug nicht verfügbar, wird das Dokument als Standard-PDF mit seinem Fingerabdruck aufbewahrt — das tatsächlich erzielte Format wird mit dem Archiv gespeichert, Sie wissen also immer, was Sie haben.</em></p>

<h2>Risiken bei Nichteinhaltung</h2>

<p>Bei einer Steuerprüfung kann das Fehlen von Rechnungen oder eine nicht konforme Archivierung Folgendes nach sich ziehen:</p>

<ul>
    <li>Die <strong>Verwerfung des Vorsteuerabzugs</strong> für die fehlenden Rechnungen</li>
    <li><strong>Verwaltungsbußgelder</strong> (250 € bis 10 000 € je Verstoß, Art. 77 LIVA) und bis zu <strong>25 000 € pro Verzugstag</strong> nach Verwarnung, wenn die Vorlage der Unterlagen verweigert wird</li>
    <li>Eine <strong>Schätzung von Amts wegen</strong> durch die Verwaltung</li>
</ul>

<h2>Offizielle Quellen</h2>

<ul>
    <li><a href="https://pfi.public.lu/dam-assets/pdf/legislation/tva/loi/loi-tva-2023-01-01.pdf" target="_blank" rel="noopener">Koordiniertes MwSt.-Gesetz – Artikel 65 (Aufbewahrung und Speicherung)</a></li>
    <li><a href="https://guichet.public.lu/fr/entreprises/gestion-juridique-comptabilite/comptable/enregistrement/obligations-comptables.html" target="_blank" rel="noopener">Guichet.lu – Buchführungspflichten der Unternehmen</a></li>
    <li><a href="https://legilux.public.lu/eli/etat/leg/loi/2015/07/25/n1/jo" target="_blank" rel="noopener">Gesetz vom 25. Juli 2015 über die elektronische Archivierung</a></li>
    <li><a href="https://ilnas.gouvernement.lu/" target="_blank" rel="noopener">ILNAS – Zertifizierte PSDC-Dienstleister</a></li>
</ul>

<div class="mt-8 p-4 bg-slate-50 rounded-xl"><h3 class="text-base font-semibold text-slate-900 mb-3">Verwandte Artikel</h3><ul class="space-y-1"><li><a href="/de/blog/faia-luxembourg-fichier-audit-informatise-guide" class="text-primary-500 hover:text-primary-600 text-sm">FAIA-Datei →</a></li><li><a href="/de/blog/controle-fiscal-luxembourg-comment-preparer" class="text-primary-500 hover:text-primary-600 text-sm">Steuerprüfung →</a></li></ul></div>

<div class="bg-primary-50 rounded-xl p-6 mt-8">
    <h3 class="text-lg font-semibold text-primary-900 mb-2">PDF/A-Archivierung mit faktur.lu</h3>
    <p class="text-primary-800 mb-4">Archivieren Sie Ihre finalisierten Rechnungen als PDF/A mit SHA-256-Fingerabdruck und Zehnjahresfrist, einzeln oder im Stapel.</p>
    <a href="/register" class="inline-block bg-primary-500 text-white font-semibold px-6 py-3 rounded-xl hover:bg-primary-600">14 Tage kostenlos testen</a>
</div>

<p class="text-sm text-slate-500 mt-6"><em>Artikel aktualisiert am 30. Juli 2026.</em></p>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">Jährlich zu prüfen</p>
    <p>Die Aufbewahrungsregeln können sich ändern. Diese Seite wird regelmäßig aktualisiert – für Ihre persönliche Situation wenden Sie sich an Ihren Treuhänder oder direkt an die <a href="https://pfi.public.lu/" target="_blank" rel="noopener">AED</a>.</p>
</div>
HTML;
    }

    private function en(): string
    {
        return <<<'HTML'
<p class="lead">Archiving invoices is a legal obligation in Luxembourg: <strong>ten years</strong>. But when that period <em>starts</em>, and <em>where</em> the archives may sit, are more precisely regulated than most people assume. Here is what article 65 of the VAT law actually says.</p>

<h2>Statutory retention period</h2>

<h3>Invoices: ten years from the date of issue</h3>

<p><strong>Article 65, paragraph 4</strong> of the <a href="https://pfi.public.lu/dam-assets/pdf/legislation/tva/loi/loi-tva-2023-01-01.pdf" target="_blank" rel="noopener">VAT law</a> is explicit:</p>

<blockquote class="border-l-4 border-slate-300 pl-4 italic text-slate-600 my-6">
    "Those invoices and copies of invoices must be stored for a period of ten years <strong>from their date of issue</strong>."
</blockquote>

<p>An invoice issued on <strong>15 March 2026</strong> must therefore be kept until <strong>15 March 2036</strong> — not until the end of 2036. The rule applies to invoices you issue as well as those you receive.</p>

<h3>Books and other documents</h3>

<p>For other records, the starting point changes:</p>

<ul>
    <li><strong>Accounting books</strong>: ten years from their <strong>closing</strong></li>
    <li><strong>Other documents</strong>: ten years from their <strong>date</strong></li>
    <li><strong>Electronic interface registers</strong> (marketplaces, platforms): ten years from <strong>31 December of the year of the transaction</strong></li>
</ul>

<p>On top of this sits the general obligation of the <strong>Commercial Code</strong> (article 16), which also requires ten years for books and commercial correspondence.</p>

<div class="bg-blue-50 border-l-4 border-blue-500 p-4 my-6">
    <p class="font-semibold">A common confusion</p>
    <p>Many guides run the invoice period from the end of the financial year. That is the rule for <strong>books</strong>, not for invoices. In practice, aligning all your purges on the year-end means keeping things slightly longer than required: prudent, but not the rule.</p>
</div>

<h2>Where may you store your archives?</h2>

<p>This is the question any online service raises, and article 65 answers it in paragraph 6.</p>

<h3>The principle</h3>

<p>You <strong>freely determine the place of storage</strong>, on one condition: making available to the administration, "<strong>without undue delay, on any request from it</strong>", all the invoices, information, books and documents concerned.</p>

<h3>The limits</h3>

<ul>
    <li><strong>Countries without mutual assistance</strong>: storage is prohibited in any country or territory with which there is no mutual assistance instrument of comparable scope, nor any electronic right of access</li>
    <li><strong>Paper archives</strong>: a taxable person established in Luxembourg must store invoices there <strong>where storage is not electronic</strong> with full online access. In practice, your binders stay in Luxembourg</li>
    <li><strong>Mandatory declaration</strong>: if the place of storage is <strong>outside Luxembourg territory</strong>, you must declare it to the administration — in the <strong>annual return</strong> provided for by article 64, paragraph 7</li>
    <li><strong>Storage in another Member State</strong>: you must guarantee officials a right of <strong>electronic access, download and use</strong> of those invoices</li>
</ul>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">Worth checking if you use an online service</p>
    <p>Ask your provider <strong>where the data is physically hosted</strong>. If the servers sit outside Luxembourg, the declaration obligation in your annual return applies — even for a European service. A simple formality, but an easy one to forget.</p>
</div>

<h2>Accepted archiving formats</h2>

<p>Article 65, paragraph 5 sets the substantive requirement: <strong>authenticity of origin</strong>, <strong>integrity of content</strong> and <strong>legibility</strong> must be ensured throughout the storage period.</p>

<h3>Paper archiving</h3>
<p>Paper originals must be kept in a dry, accessible place, unaltered and — as seen above — on Luxembourg territory.</p>

<h3>Digital archiving</h3>
<p>Electronic storage is valid "provided that the data guaranteeing the authenticity of origin and the integrity of content are also stored electronically". In practice:</p>

<ul>
    <li>The format must guarantee the document's <strong>integrity</strong></li>
    <li>The document must remain <strong>legible</strong> throughout the retention period</li>
    <li>The <strong>PDF/A</strong> format (ISO 19005) is recommended for long-term legibility</li>
    <li>A <strong>digital fingerprint</strong> (hash) demonstrates the document has not been altered</li>
</ul>

<div class="bg-blue-50 border-l-4 border-blue-500 p-4 my-6">
    <p class="font-semibold">Digitisation and probative value</p>
    <p>For an electronic copy to carry the same <strong>probative value</strong> as the paper original, the <a href="https://legilux.public.lu/eli/etat/leg/loi/2015/07/25/n1/jo" target="_blank" rel="noopener">law of 25 July 2015</a> rests that presumption on using a certified <a href="https://ilnas.gouvernement.lu/" target="_blank" rel="noopener">PSDC</a> (dematerialisation or conservation service provider). Digitising without a PSDC remains possible, but the copy does not enjoy that presumption: if challenged, it is for you to prove its fidelity.</p>
</div>

<h2>Why the PDF/A format?</h2>

<p><strong>PDF/A</strong> is an ISO standard designed for long-term archiving. Unlike a standard PDF:</p>

<ul>
    <li>It <strong>embeds every font</strong> used (no external dependency)</li>
    <li>It <strong>forbids JavaScript</strong> and multimedia elements</li>
    <li>It aims to guarantee the document stays <strong>legible in ten, twenty or fifty years</strong></li>
    <li>It is <strong>widely recognised</strong> by European administrations</li>
</ul>

<h2>Archiving invoices with faktur.lu</h2>

<p>faktur.lu offers dedicated archiving, available on the plans that include the feature:</p>

<ol>
    <li>You archive a <strong>finalised</strong> invoice — individually or in batches</li>
    <li>The document is converted to <strong>PDF/A-1b</strong> by default; <strong>PDF/A-3b</strong> is available if you need to embed attachments</li>
    <li>A <strong>SHA-256 fingerprint</strong> is computed and stored, so the archive's integrity can be verified later</li>
    <li>A <strong>ten-year expiry</strong> is recorded with the archive</li>
    <li>You can download your archives at any time</li>
</ol>

<p class="text-sm text-slate-500"><em>Technical note: PDF/A conversion relies on Ghostscript server-side. If the tool is unavailable, the document is kept as a standard PDF with its fingerprint — the format actually obtained is recorded with the archive, so you always know what you have.</em></p>

<h2>Risks of non-compliance</h2>

<p>During a tax audit, missing invoices or non-compliant archiving can lead to:</p>

<ul>
    <li><strong>Refusal of VAT deductions</strong> on the missing invoices</li>
    <li><strong>Administrative fines</strong> (€250 to €10,000 per infringement, art. 77 LIVA), and up to <strong>€25,000 per day of delay</strong> after a warning where records are not produced during an audit</li>
    <li><strong>Assessment on the administration's own estimate</strong></li>
</ul>

<h2>Official sources</h2>

<ul>
    <li><a href="https://pfi.public.lu/dam-assets/pdf/legislation/tva/loi/loi-tva-2023-01-01.pdf" target="_blank" rel="noopener">Coordinated VAT law - article 65 (retention and storage)</a></li>
    <li><a href="https://guichet.public.lu/fr/entreprises/gestion-juridique-comptabilite/comptable/enregistrement/obligations-comptables.html" target="_blank" rel="noopener">Guichet.lu - Accounting obligations of businesses</a></li>
    <li><a href="https://legilux.public.lu/eli/etat/leg/loi/2015/07/25/n1/jo" target="_blank" rel="noopener">Law of 25 July 2015 on electronic archiving</a></li>
    <li><a href="https://ilnas.gouvernement.lu/" target="_blank" rel="noopener">ILNAS - Certified PSDC providers</a></li>
</ul>

<div class="mt-8 p-4 bg-slate-50 rounded-xl"><h3 class="text-base font-semibold text-slate-900 mb-3">Related articles</h3><ul class="space-y-1"><li><a href="/en/blog/faia-luxembourg-fichier-audit-informatise-guide" class="text-primary-500 hover:text-primary-600 text-sm">FAIA file →</a></li><li><a href="/en/blog/controle-fiscal-luxembourg-comment-preparer" class="text-primary-500 hover:text-primary-600 text-sm">Tax audits →</a></li></ul></div>

<div class="bg-primary-50 rounded-xl p-6 mt-8">
    <h3 class="text-lg font-semibold text-primary-900 mb-2">PDF/A archiving with faktur.lu</h3>
    <p class="text-primary-800 mb-4">Archive your finalised invoices as PDF/A with a SHA-256 fingerprint and a ten-year expiry, individually or in batches.</p>
    <a href="/register" class="inline-block bg-primary-500 text-white font-semibold px-6 py-3 rounded-xl hover:bg-primary-600">Try free for 14 days</a>
</div>

<p class="text-sm text-slate-500 mt-6"><em>Article updated on 30 July 2026.</em></p>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">To check every year</p>
    <p>Retention rules may change. This page is updated regularly, but for your own situation consult your accountant or the <a href="https://pfi.public.lu/" target="_blank" rel="noopener">AED</a> directly.</p>
</div>
HTML;
    }

    private function lb(): string
    {
        return <<<'HTML'
<p class="lead">D'Archivéierung vu Rechnungen ass zu Lëtzebuerg eng gesetzlech Flicht: <strong>zéng Joer</strong>. Mä d'Fro vum <em>Ufank</em> vun dëser Frist, an déi vum <em>Plaz</em>, wou d'Archive leie kënnen, sinn méi genee gereegelt wéi ee mengt. Hei steet, wat den Artikel 65 vum TVA-Gesetz tatsächlech seet.</p>

<h2>Gesetzlech Opbewahrungsdauer</h2>

<h3>Rechnungen: zéng Joer vum Ausstellungsdatum un</h3>

<p>Den <strong>Artikel 65, Paragraf 4</strong> vum <a href="https://pfi.public.lu/dam-assets/pdf/legislation/tva/loi/loi-tva-2023-01-01.pdf" target="_blank" rel="noopener">TVA-Gesetz</a> ass eendeiteg:</p>

<blockquote class="border-l-4 border-slate-300 pl-4 italic text-slate-600 my-6">
    „Dës Rechnungen a Rechnungskopie musse fir eng Period vun zéng Joer <strong>vun hirem Ausstellungsdatum un</strong> gespäichert ginn."
</blockquote>

<p>Eng den <strong>15. Mäerz 2026</strong> ausgestallt Rechnung muss also bis den <strong>15. Mäerz 2036</strong> opbewahrt ginn — an net bis Enn 2036. D'Reegel gëllt fir ausgestallt wéi fir erhalen Rechnungen.</p>

<h3>Bicher an aner Dokumenter</h3>

<p>Fir déi aner Stécker ännert sech den Ufank vun der Frist:</p>

<ul>
    <li><strong>Comptabilitéitsbicher</strong>: zéng Joer vun hirem <strong>Ofschloss</strong> un</li>
    <li><strong>Aner Dokumenter</strong>: zéng Joer vun hirem <strong>Datum</strong> un</li>
    <li><strong>Registere vun elektroneschen Interfaces</strong> (Marchéplazen, Plattformen): zéng Joer vum <strong>31. Dezember vum Joer vun der Operatioun</strong> un</li>
</ul>

<p>Dobäi kënnt déi allgemeng Flicht vum <strong>Handelsgesetzbuch</strong> (Artikel 16), dat och zéng Joer fir Bicher a Geschäftskorrespondenz virgesäit.</p>

<div class="bg-blue-50 border-l-4 border-blue-500 p-4 my-6">
    <p class="font-semibold">Eng heefeg Verwiesslung</p>
    <p>Vill Guiden loossen d'Frist fir Rechnungen vum Enn vum Exercice un lafen. Dat ass d'Reegel fir <strong>Bicher</strong>, net fir Rechnungen. An der Praxis féiert eng Ausriichtung vun alle Läschungen um Exercice-Enn dozou, datt Dir e bësse méi laang opbewahrt wéi néideg: virsiichteg, mä net d'Reegel.</p>
</div>

<h2>Wou däerft Dir Är Archive späicheren?</h2>

<p>Dat ass d'Fro, déi sech bei all Onlinedéngscht stellt, an den Artikel 65 äntwert doropper am Paragraf 6.</p>

<h3>De Prinzip</h3>

<p>Dir <strong>bestëmmt d'Späicherplaz fräi</strong>, ënner enger Bedingung: der Administratioun „<strong>ouni ongerechtfäerdegt Verspéidung, op all Ufuerderung vun hirer Säit</strong>" all d'Rechnungen, Informatiounen, Bicher an Dokumenter zur Verfügung ze stellen.</p>

<h3>D'Grenzen</h3>

<ul>
    <li><strong>Länner ouni géigesäiteg Hëllef</strong>: et ass verbueden, an engem Land oder Territoire ze späicheren, mat deem et keen Instrument vu géigesäiteger Hëllef vu vergläichbarer Reechwäit gëtt, an och kee elektronescht Zougrëffsrecht</li>
    <li><strong>Pabeierarchiven</strong>: en zu Lëtzebuerg etabléierten Assujetti muss seng Rechnungen do späicheren, <strong>wann d'Späicherung net elektronesch</strong> mat komplettem Online-Zougrëff geschitt. Konkret: Är Classeure bleiwen zu Lëtzebuerg</li>
    <li><strong>Obligatoresch Deklaratioun</strong>: läit d'Späicherplaz <strong>ausserhalb vum Lëtzebuerger Territoire</strong>, musst Dir se der Administratioun deklaréieren — an der <strong>Jooresdeklaratioun</strong> no Artikel 64, Paragraf 7</li>
    <li><strong>Späicherung an engem anere Member-Staat</strong>: Dir musst den Agente vun der Administratioun e Recht op <strong>elektroneschen Zougrëff, Eroflueden a Notzung</strong> vun dëse Rechnunge garantéieren</li>
</ul>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">Ze préiwen, wann Dir en Onlinedéngscht notzt</p>
    <p>Frot Äre Prestataire, <strong>wou d'Donnéeën physesch gehost ginn</strong>. Leien d'Serveren ausserhalb vu Lëtzebuerg, gëllt d'Deklaratiounsflicht an Ärer Jooresdeklaratioun — och bei engem europäeschen Déngscht. Eng einfach Formalitéit, mä eng, déi ee liicht vergësst.</p>
</div>

<h2>Akzeptéiert Archivéierungsformater</h2>

<p>Den Artikel 65, Paragraf 5, stellt d'inhaltlech Ufuerderung: d'<strong>Autentizitéit vun der Hierkonft</strong>, d'<strong>Integritéit vum Inhalt</strong> an d'<strong>Liesbarkeet</strong> mussen déi ganz Späicherperiod laang garantéiert sinn.</p>

<h3>Pabeierarchivéierung</h3>
<p>Pabeieroriginaler musse dréchen an accessibel opbewahrt ginn, ouni Verännerung an — wéi hei uewen gesinn — um Lëtzebuerger Territoire.</p>

<h3>Digital Archivéierung</h3>
<p>D'elektronesch Späicherung ass gëlteg, „ënner der Bedingung, datt d'Donnéeën, déi d'Autentizitéit vun der Hierkonft an d'Integritéit vum Inhalt garantéieren, och elektronesch gespäichert ginn". An der Praxis:</p>

<ul>
    <li>D'Format muss d'<strong>Integritéit</strong> vum Dokument garantéieren</li>
    <li>D'Dokument muss déi ganz Dauer laang <strong>liesbar</strong> bleiwen</li>
    <li>D'Format <strong>PDF/A</strong> (ISO 19005) gëtt fir d'Laangzäitliesbarkeet recommandéiert</li>
    <li>En <strong>digitalen Ofdrock</strong> (Hash) beweist, datt d'Dokument net verännert gouf</li>
</ul>

<div class="bg-blue-50 border-l-4 border-blue-500 p-4 my-6">
    <p class="font-semibold">Digitaliséierung a Beweiskraaft</p>
    <p>Fir datt eng elektronesch Kopie déi selwecht <strong>Beweiskraaft</strong> wéi den Originalpabeier huet, knäppt d'<a href="https://legilux.public.lu/eli/etat/leg/loi/2015/07/25/n1/jo" target="_blank" rel="noopener">Gesetz vum 25. Juli 2015</a> dës Vermutung un de Recours op e certifiéierte <a href="https://ilnas.gouvernement.lu/" target="_blank" rel="noopener">PSDC</a> (Prestataire vun Dematerialiséierungs- oder Konservatiounsdéngschter). Ouni PSDC ze digitaliséiere bleift méiglech, mä d'Kopie profitéiert net vun dëser Vermutung: bei enger Kontestatioun musst Dir hir Treiheet selwer beweisen.</p>
</div>

<h2>Firwat d'Format PDF/A?</h2>

<p><strong>PDF/A</strong> ass eng ISO-Norm, déi fir d'Laangzäitarchivéierung entworf gouf. Am Géigesaz zu engem Standard-PDF:</p>

<ul>
    <li>Et <strong>embarquéiert all benotzte Schrëften</strong> (keng extern Ofhängegkeet)</li>
    <li>Et <strong>verbitt JavaScript</strong> a Multimedia-Elementer</li>
    <li>Et zielt drop of, ze garantéieren, datt d'Dokument an <strong>zéng, zwanzeg oder fofzeg Joer liesbar</strong> bleift</li>
    <li>Et ass bei europäesche Verwaltunge <strong>wäit unerkannt</strong></li>
</ul>

<h2>Rechnunge mat faktur.lu archivéieren</h2>

<p>faktur.lu bitt eng eege Archivéierung un, disponibel an de Pläng, déi d'Funktionalitéit enthalen:</p>

<ol>
    <li>Dir archivéiert eng <strong>finaliséiert</strong> Rechnung — eenzel oder am Lot</li>
    <li>D'Dokument gëtt standardméisseg an <strong>PDF/A-1b</strong> konvertéiert; <strong>PDF/A-3b</strong> ass disponibel, wann Dir Uschlëss musst embarquéieren</li>
    <li>En <strong>SHA-256-Ofdrock</strong> gëtt berechent a gespäichert, sou datt d'Integritéit vum Archiv duerno kann iwwerpréift ginn</li>
    <li>Eng <strong>Zéngjoresfrist</strong> gëtt mam Archiv hannerluecht</li>
    <li>Dir kënnt Är Archiven zu all Moment eroflueden</li>
</ol>

<p class="text-sm text-slate-500"><em>Technesch Präzisioun: d'PDF/A-Konversioun stëtzt sech serversäiteg op Ghostscript. Ass d'Tool net disponibel, gëtt d'Dokument als Standard-PDF mat sengem Ofdrock opbewahrt — dat tatsächlech erreechte Format gëtt mam Archiv gespäichert, Dir wësst also ëmmer, wat Dir hutt.</em></p>

<h2>Risike bei Netkonformitéit</h2>

<p>Bei enger Steierkontroll kann d'Feele vu Rechnungen oder eng net konform Archivéierung Folgendes no sech zéien:</p>

<ul>
    <li>De <strong>Refus vum TVA-Ofzuch</strong> op de feelende Rechnungen</li>
    <li><strong>Administrativ Geldstrofen</strong> (250 € bis 10 000 € pro Infraktioun, Art. 77 LIVA), a bis zu <strong>25 000 € pro Dag Verspéidung</strong> no engem Avertissement, wann d'Stécker bei enger Kontroll net virgeluecht ginn</li>
    <li>Eng <strong>Taxatioun vun Amts wéinst</strong> duerch d'Administratioun</li>
</ul>

<h2>Offiziell Quellen</h2>

<ul>
    <li><a href="https://pfi.public.lu/dam-assets/pdf/legislation/tva/loi/loi-tva-2023-01-01.pdf" target="_blank" rel="noopener">Koordinéiert TVA-Gesetz – Artikel 65 (Opbewahrung a Späicherung)</a></li>
    <li><a href="https://guichet.public.lu/fr/entreprises/gestion-juridique-comptabilite/comptable/enregistrement/obligations-comptables.html" target="_blank" rel="noopener">Guichet.lu – Comptabel Flichte vun den Entreprisen</a></li>
    <li><a href="https://legilux.public.lu/eli/etat/leg/loi/2015/07/25/n1/jo" target="_blank" rel="noopener">Gesetz vum 25. Juli 2015 iwwer déi elektronesch Archivéierung</a></li>
    <li><a href="https://ilnas.gouvernement.lu/" target="_blank" rel="noopener">ILNAS – Certifiéiert PSDC-Prestataire</a></li>
</ul>

<div class="mt-8 p-4 bg-slate-50 rounded-xl"><h3 class="text-base font-semibold text-slate-900 mb-3">Verbonnen Artikelen</h3><ul class="space-y-1"><li><a href="/lb/blog/faia-luxembourg-fichier-audit-informatise-guide" class="text-primary-500 hover:text-primary-600 text-sm">FAIA-Fichier →</a></li><li><a href="/lb/blog/controle-fiscal-luxembourg-comment-preparer" class="text-primary-500 hover:text-primary-600 text-sm">Steierkontroll →</a></li></ul></div>

<div class="bg-primary-50 rounded-xl p-6 mt-8">
    <h3 class="text-lg font-semibold text-primary-900 mb-2">PDF/A-Archivéierung mat faktur.lu</h3>
    <p class="text-primary-800 mb-4">Archivéiert Är finaliséiert Rechnungen an PDF/A mat SHA-256-Ofdrock an Zéngjoresfrist, eenzel oder am Lot.</p>
    <a href="/register" class="inline-block bg-primary-500 text-white font-semibold px-6 py-3 rounded-xl hover:bg-primary-600">14 Deeg gratis testen</a>
</div>

<p class="text-sm text-slate-500 mt-6"><em>Artikel aktualiséiert den 30. Juli 2026.</em></p>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">All Joer ze préiwen</p>
    <p>D'Opbewahrungsreegele kënnen änneren. Dës Säit gëtt reegelméisseg aktualiséiert – fir Är perséinlech Situatioun frot Är Fiduciaire oder direkt d'<a href="https://pfi.public.lu/" target="_blank" rel="noopener">AED</a>.</p>
</div>
HTML;
    }

    private function pt(): string
    {
        return <<<'HTML'
<p class="lead">O arquivo das faturas é uma obrigação legal no Luxemburgo: <strong>dez anos</strong>. Mas a questão do <em>início</em> desse prazo, e a do <em>local</em> onde os arquivos podem residir, são mais precisas do que se julga. Eis o que diz exatamente o artigo 65 da lei do IVA.</p>

<h2>Prazo legal de conservação</h2>

<h3>Faturas: dez anos a contar da data de emissão</h3>

<p>O <strong>artigo 65.º, parágrafo 4</strong> da <a href="https://pfi.public.lu/dam-assets/pdf/legislation/tva/loi/loi-tva-2023-01-01.pdf" target="_blank" rel="noopener">lei do IVA</a> é explícito:</p>

<blockquote class="border-l-4 border-slate-300 pl-4 italic text-slate-600 my-6">
    «Essas faturas e cópias de faturas devem ser conservadas durante um período de dez anos <strong>a contar da sua data de emissão</strong>.»
</blockquote>

<p>Uma fatura emitida a <strong>15 de março de 2026</strong> deve, portanto, ser conservada até <strong>15 de março de 2036</strong> — e não até ao final de 2036. A regra vale tanto para as faturas que emite como para as que recebe.</p>

<h3>Livros e outros documentos</h3>

<p>Para os restantes documentos, o ponto de partida muda:</p>

<ul>
    <li><strong>Livros contabilísticos</strong>: dez anos a contar do seu <strong>encerramento</strong></li>
    <li><strong>Outros documentos</strong>: dez anos a contar da sua <strong>data</strong></li>
    <li><strong>Registos das interfaces eletrónicas</strong> (marketplaces, plataformas): dez anos a contar de <strong>31 de dezembro do ano da operação</strong></li>
</ul>

<p>Acresce a obrigação geral do <strong>Código Comercial</strong> (artigo 16), que impõe igualmente dez anos para os livros e a correspondência comercial.</p>

<div class="bg-blue-50 border-l-4 border-blue-500 p-4 my-6">
    <p class="font-semibold">Uma confusão frequente</p>
    <p>Muitos guias fazem correr o prazo das faturas a partir do fim do exercício. É a regra dos <strong>livros</strong>, não a das faturas. Na prática, alinhar todas as suas eliminações pelo fim do exercício leva-o a conservar um pouco mais do que o necessário: é prudente, mas não é a regra.</p>
</div>

<h2>Onde pode armazenar os seus arquivos?</h2>

<p>É a questão que qualquer serviço em linha levanta, e o artigo 65.º responde-lhe no parágrafo 6.</p>

<h3>O princípio</h3>

<p>Determina <strong>livremente o local de armazenamento</strong>, com uma condição: colocar à disposição da administração, «<strong>sem demora indevida, a qualquer pedido seu</strong>», todas as faturas, informações, livros e documentos em causa.</p>

<h3>Os limites</h3>

<ul>
    <li><strong>Países sem assistência mútua</strong>: é proibido armazenar num país ou território com o qual não exista qualquer instrumento de assistência mútua de alcance comparável, nem direito de acesso eletrónico</li>
    <li><strong>Arquivos em papel</strong>: um sujeito passivo estabelecido no Luxemburgo deve aí armazenar as suas faturas <strong>quando o armazenamento não é eletrónico</strong> com acesso completo e em linha. Concretamente, os seus dossiês ficam no Luxemburgo</li>
    <li><strong>Declaração obrigatória</strong>: se o local de armazenamento estiver <strong>fora do território luxemburguês</strong>, deve declará-lo à administração — na <strong>declaração anual</strong> prevista no artigo 64.º, parágrafo 7</li>
    <li><strong>Armazenamento noutro Estado-Membro</strong>: deve garantir aos agentes da administração um direito de <strong>acesso por via eletrónica, descarregamento e utilização</strong> dessas faturas</li>
</ul>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">A verificar se utiliza um serviço em linha</p>
    <p>Pergunte ao seu prestador <strong>onde estão fisicamente alojados os dados</strong>. Se os servidores estiverem fora do Luxemburgo, aplica-se a obrigação de declaração na sua declaração anual — mesmo tratando-se de um serviço europeu. É uma formalidade simples, mas fácil de esquecer.</p>
</div>

<h2>Formatos de arquivo aceites</h2>

<p>O artigo 65.º, parágrafo 5, estabelece a exigência de fundo: a <strong>autenticidade da origem</strong>, a <strong>integridade do conteúdo</strong> e a <strong>legibilidade</strong> devem ser asseguradas durante todo o período de conservação.</p>

<h3>Arquivo em papel</h3>
<p>Os originais em papel devem ser conservados num local seco e acessível, sem alteração e — como visto acima — em território luxemburguês.</p>

<h3>Arquivo digital</h3>
<p>O armazenamento eletrónico é válido «desde que os dados que garantem a autenticidade da origem e a integridade do conteúdo sejam igualmente armazenados sob forma eletrónica». Na prática:</p>

<ul>
    <li>O formato deve garantir a <strong>integridade</strong> do documento</li>
    <li>O documento deve manter-se <strong>legível</strong> durante todo o prazo de conservação</li>
    <li>O formato <strong>PDF/A</strong> (ISO 19005) é recomendado para a legibilidade a longo prazo</li>
    <li>Uma <strong>impressão digital</strong> (hash) permite demonstrar que o documento não foi alterado</li>
</ul>

<div class="bg-blue-50 border-l-4 border-blue-500 p-4 my-6">
    <p class="font-semibold">Digitalização e valor probatório</p>
    <p>Para que uma cópia eletrónica tenha o mesmo <strong>valor probatório</strong> que o original em papel, a <a href="https://legilux.public.lu/eli/etat/leg/loi/2015/07/25/n1/jo" target="_blank" rel="noopener">lei de 25 de julho de 2015</a> faz assentar essa presunção no recurso a um <a href="https://ilnas.gouvernement.lu/" target="_blank" rel="noopener">PSDC</a> (prestador de serviços de desmaterialização ou de conservação) certificado. Digitalizar sem PSDC continua possível, mas a cópia não beneficia dessa presunção: em caso de contestação, compete-lhe demonstrar a sua fidelidade.</p>
</div>

<h2>Porquê o formato PDF/A?</h2>

<p>O <strong>PDF/A</strong> é uma norma ISO concebida para o arquivo a longo prazo. Ao contrário de um PDF normal:</p>

<ul>
    <li><strong>Incorpora todos os tipos de letra</strong> utilizados (sem dependência externa)</li>
    <li><strong>Proíbe o JavaScript</strong> e os elementos multimédia</li>
    <li>Visa garantir que o documento permanecerá <strong>legível dentro de dez, vinte ou cinquenta anos</strong></li>
    <li>É <strong>amplamente reconhecido</strong> pelas administrações europeias</li>
</ul>

<h2>Arquivar as faturas com o faktur.lu</h2>

<p>O faktur.lu propõe um arquivo dedicado, disponível nos planos que incluem a funcionalidade:</p>

<ol>
    <li>Arquiva uma fatura <strong>finalizada</strong> — individualmente ou em lote</li>
    <li>O documento é convertido em <strong>PDF/A-1b</strong> por defeito; o <strong>PDF/A-3b</strong> está disponível se precisar de incorporar anexos</li>
    <li>É calculada e registada uma <strong>impressão SHA-256</strong>, o que permite verificar depois a integridade do arquivo</li>
    <li>É registado com o arquivo um <strong>prazo de dez anos</strong></li>
    <li>Pode descarregar os seus arquivos a qualquer momento</li>
</ol>

<p class="text-sm text-slate-500"><em>Precisão técnica: a conversão em PDF/A apoia-se no Ghostscript do lado do servidor. Se a ferramenta não estiver disponível, o documento é conservado em PDF normal com a sua impressão — o formato efetivamente obtido é registado com o arquivo, pelo que sabe sempre o que tem.</em></p>

<h2>Riscos em caso de não conformidade</h2>

<p>Numa inspeção fiscal, a ausência de faturas ou um arquivo não conforme pode acarretar:</p>

<ul>
    <li>A <strong>rejeição das deduções de IVA</strong> sobre as faturas em falta</li>
    <li><strong>Coimas administrativas</strong> (250 € a 10 000 € por infração, art. 77 LIVA) e até <strong>25 000 € por dia de atraso</strong> após advertência, em caso de recusa de apresentação dos documentos durante uma inspeção</li>
    <li>Uma <strong>tributação oficiosa</strong> pela administração</li>
</ul>

<h2>Fontes oficiais</h2>

<ul>
    <li><a href="https://pfi.public.lu/dam-assets/pdf/legislation/tva/loi/loi-tva-2023-01-01.pdf" target="_blank" rel="noopener">Lei do IVA coordenada - artigo 65.º (conservação e armazenamento)</a></li>
    <li><a href="https://guichet.public.lu/fr/entreprises/gestion-juridique-comptabilite/comptable/enregistrement/obligations-comptables.html" target="_blank" rel="noopener">Guichet.lu - Obrigações contabilísticas das empresas</a></li>
    <li><a href="https://legilux.public.lu/eli/etat/leg/loi/2015/07/25/n1/jo" target="_blank" rel="noopener">Lei de 25 de julho de 2015 relativa ao arquivo eletrónico</a></li>
    <li><a href="https://ilnas.gouvernement.lu/" target="_blank" rel="noopener">ILNAS - Prestadores PSDC certificados</a></li>
</ul>

<div class="mt-8 p-4 bg-slate-50 rounded-xl"><h3 class="text-base font-semibold text-slate-900 mb-3">Artigos relacionados</h3><ul class="space-y-1"><li><a href="/pt/blog/faia-luxembourg-fichier-audit-informatise-guide" class="text-primary-500 hover:text-primary-600 text-sm">ficheiro FAIA →</a></li><li><a href="/pt/blog/controle-fiscal-luxembourg-comment-preparer" class="text-primary-500 hover:text-primary-600 text-sm">inspeção fiscal →</a></li></ul></div>

<div class="bg-primary-50 rounded-xl p-6 mt-8">
    <h3 class="text-lg font-semibold text-primary-900 mb-2">Arquivo PDF/A com o faktur.lu</h3>
    <p class="text-primary-800 mb-4">Arquive as suas faturas finalizadas em PDF/A com impressão SHA-256 e prazo de dez anos, individualmente ou em lote.</p>
    <a href="/register" class="inline-block bg-primary-500 text-white font-semibold px-6 py-3 rounded-xl hover:bg-primary-600">Experimentar 14 dias grátis</a>
</div>

<p class="text-sm text-slate-500 mt-6"><em>Artigo atualizado a 30 de julho de 2026.</em></p>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">A verificar todos os anos</p>
    <p>As regras de conservação podem evoluir. Esta página é atualizada regularmente, mas para a sua situação pessoal, consulte o seu contabilista ou diretamente a <a href="https://pfi.public.lu/" target="_blank" rel="noopener">AED</a>.</p>
</div>
HTML;
    }
};
