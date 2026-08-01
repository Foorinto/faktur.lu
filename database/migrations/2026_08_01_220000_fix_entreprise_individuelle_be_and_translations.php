<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Guide « Créer une entreprise individuelle en Belgique ».
 *
 * Vérifications faites le 2026-08-01 contre les sources officielles belges.
 * Tout est exact, au centime près, sauf un point — mais celui-là est un
 * contresens complet.
 *
 * CONFIRMÉ par inasti.be : 20,50 % jusqu'à 75 024,54 EUR, 14,16 % entre
 * 75 024,54 et 110 562,42 EUR, cotisation minimale calculée sur un revenu
 * plancher de 17 374,08 EUR. CONFIRMÉ par finances.belgium.be : seuil de
 * franchise à 25 000 EUR, articles 56bis à 56undecies du Code de la TVA.
 * CONFIRMÉ par economie.fgov.be : huit guichets d'entreprises agréés.
 *
 * ERREUR CORRIGÉE. L'article affirmait : « Tolérance 10 % supprimée depuis
 * 2025 - tout dépassement entraîne désormais la sortie de la franchise ».
 * C'est faux sur les deux points. Le SPF Finances décrit trois situations :
 *   - CA <= 25 000 EUR : franchise ;
 *   - dépassement de 10 % au maximum (27 500 EUR) : la franchise est
 *     conservée jusqu'au 31 décembre, le régime normal s'applique au
 *     1er janvier suivant, et le retour à la franchise n'est possible qu'au
 *     1er janvier de l'année d'après ;
 *   - dépassement de plus de 10 % : régime normal immédiatement, dès
 *     l'opération qui a fait dépasser le seuil, et l'année suivante aussi.
 * L'article privait donc le lecteur d'une marge qui existe, tout en taisant
 * la conséquence différée qui, elle, le concerne vraiment.
 *
 * NUANCE AJOUTÉE. L'article annonce la suppression des connaissances de
 * gestion de base dans les trois régions. La page fédérale du SPF Économie
 * indique pourtant encore qu'il faut prouver ses capacités entrepreneuriales
 * en Wallonie et à Bruxelles. Les pages fédérales sont souvent en retard sur
 * les réformes régionales, mais je ne peux pas trancher : la divergence est
 * signalée et le lecteur renvoyé au guichet d'entreprises, ce que la page
 * officielle recommande elle-même.
 *
 * DE, EN, LB, PT : 8 123 à 8 769 caractères contre 10 570, quatre à cinq
 * liens contre neuf.
 */
return new class extends Migration
{
    private const KEY = 'creer-entreprise-individuelle-belgique-guide-2025';

    private const FR_FIXES = [
        [
            '<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">'."\n"
                .'    <p class="font-semibold">⚠ Tolérance 10 % supprimée depuis 2025</p>'."\n"
                .'    <p>La tolérance qui permettait de dépasser ponctuellement le seuil de 25 000 € jusqu\'à 10 % (27 500 €) sans perte immédiate de la franchise a été <strong>supprimée depuis le 1<sup>er</sup> janvier 2025</strong>. Tout dépassement entraîne désormais la sortie de la franchise.</p>'."\n"
                .'</div>',
            '<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">'."\n"
                .'    <p class="font-semibold">Ce que déclenche un dépassement</p>'."\n"
                .'    <p>Le seuil de 25 000 € admet une marge de 10 %, mais les conséquences diffèrent selon l\'ampleur du dépassement :</p>'."\n"
                .'    <ul class="mt-2">'."\n"
                .'        <li><strong>Dépassement de 10 % au maximum</strong> (jusqu\'à 27 500 €) : vous gardez la franchise jusqu\'au 31 décembre, mais passez au régime normal le 1<sup>er</sup> janvier suivant. Le retour à la franchise n\'est possible qu\'au 1<sup>er</sup> janvier de l\'année d\'après.</li>'."\n"
                .'        <li><strong>Dépassement de plus de 10 %</strong> : le régime normal s\'applique <strong>immédiatement</strong>, dès l\'opération qui a fait franchir le seuil - et l\'année suivante également.</li>'."\n"
                .'    </ul>'."\n"
                .'</div>',
        ],
        [
            'La franchise TVA est possible si le CA reste sous 25 000 €/an (tolérance 10 % supprimée depuis 2025).',
            'La franchise TVA est possible si le CA reste sous 25 000 €/an, avec une marge de 10 % qui décale la sortie du régime au 1<sup>er</sup> janvier suivant.',
        ],
        [
            '        <li><strong>Wallonie :</strong> depuis le 1<sup>er</sup> octobre 2025</li>'."\n"
                .'    </ul>'."\n"
                .'</div>',
            '        <li><strong>Wallonie :</strong> depuis le 1<sup>er</sup> octobre 2025</li>'."\n"
                .'    </ul>'."\n"
                .'    <p class="mt-2 text-sm">La page fédérale du SPF Économie mentionne toutefois encore une obligation de prouver ses capacités entrepreneuriales en Wallonie et à Bruxelles. Les pages fédérales suivent parfois les réformes régionales avec retard : faites confirmer votre cas par un guichet d\'entreprises.</p>'."\n"
                .'</div>',
        ],
    ];

    public function up(): void
    {
        $this->rewriteFrench(self::FR_FIXES);

        foreach ($this->translations() as $locale => $content) {
            DB::table('blog_posts')
                ->where('translation_key', self::KEY)
                ->where('locale', $locale)
                ->update(['content' => $content, 'updated_at' => now()]);
        }
    }

    public function down(): void
    {
        $this->rewriteFrench(array_map('array_reverse', self::FR_FIXES));
    }

    /** @param  array<int, array{0:string,1:string}>  $fixes */
    private function rewriteFrench(array $fixes): void
    {
        $post = DB::table('blog_posts')
            ->where('translation_key', self::KEY)
            ->where('locale', 'fr')
            ->first(['id', 'content']);

        if (! $post) {
            return;
        }

        $content = $post->content;

        foreach ($fixes as [$before, $after]) {
            $content = str_replace($before, $after, $content);
        }

        DB::table('blog_posts')->where('id', $post->id)->update([
            'content' => $content,
            'updated_at' => now(),
        ]);
    }

    /** @param  array<int, array{0:string,1:string}>  $rows */
    private function table(string $h1, string $h2, array $rows): string
    {
        $body = '';

        foreach ($rows as [$left, $right]) {
            $body .= '        <tr><td class="p-2 border-b">'.$left.'</td><td class="p-2 border-b">'.$right."</td></tr>\n";
        }

        return "<table class=\"w-full my-4\">\n    <thead>\n        <tr>\n"
            ."            <th class=\"text-left p-2 bg-slate-100\">{$h1}</th>\n"
            ."            <th class=\"text-left p-2 bg-slate-100\">{$h2}</th>\n"
            ."        </tr>\n    </thead>\n    <tbody>\n{$body}    </tbody>\n</table>";
    }

    /** @return array<string, string> */
    private function translations(): array
    {
        $de = implode("\n\n", [
            '<p class="lead">Belgien bietet Selbststaendigen einen guenstigen Rahmen; seit dem Wegfall der Grundkenntnisse der Betriebsfuehrung sind die Schritte einfacher geworden. Dieser Leitfaden begleitet Sie 2026 bei der Gruendung Ihres Einzelunternehmens, mit den aktuellen Schwellen und INASTI-Beitraegen.</p>',

            '<h2>Rechtsform: Unternehmen als natuerliche Person</h2>',
            '<p>Das Unternehmen als natuerliche Person (Selbststaendiger) ist die einfachste Form, um allein eine wirtschaftliche Taetigkeit in Belgien auszuueben.</p>',
            '<h3>Wesentliche Merkmale</h3>',
            $this->table('Aspekt', 'Detail', [
                ['Gruendungsurkunde', 'Keine erforderlich'],
                ['Mindestkapital', 'Keines erforderlich'],
                ['Haftung', '<strong>Unbeschraenkt</strong> – Privat- und Betriebsvermoegen sind nicht getrennt'],
                ['Buchfuehrung', 'Vereinfacht bei einem Umsatz &lt; 500 000 EUR'],
            ]),

            '<h2>Voraussetzungen</h2>',
            '<h3>Allgemeine Bedingungen</h3>',
            "<ul>\n    <li>Mindestens <strong>18 Jahre</strong> alt sein</li>\n    <li>Im Besitz der buergerlichen und politischen Rechte sein</li>\n    <li>Rechtlich geschaeftsfaehig sein</li>\n</ul>",

            '<h3>Grundkenntnisse der Betriebsfuehrung: ABGESCHAFFT</h3>',
            '<div class="bg-green-50 border-l-4 border-green-500 p-4 my-6">'."\n"
                .'    <p class="font-semibold">Gute Nachricht!</p>'."\n"
                .'    <p>Die Grundkenntnisse der Betriebsfuehrung wurden in allen Regionen abgeschafft:</p>'."\n"
                ."    <ul class=\"mt-2\">\n"
                ."        <li><strong>Flandern:</strong> seit 2018</li>\n"
                ."        <li><strong>Bruessel:</strong> seit dem 15. Januar 2024</li>\n"
                ."        <li><strong>Wallonie:</strong> seit dem 1. Oktober 2025</li>\n"
                ."    </ul>\n"
                .'    <p class="mt-2 text-sm">Die foederale Seite des FOeD Wirtschaft nennt allerdings weiterhin eine Pflicht, unternehmerische Faehigkeiten in der Wallonie und in Bruessel nachzuweisen. Foederale Seiten folgen regionalen Reformen mitunter verspaetet: lassen Sie Ihren Fall von einem Unternehmensschalter bestaetigen.</p>'."\n"
                .'</div>',

            '<h3>Berufszugang</h3>',
            '<p>Bestimmte reglementierte Berufe verlangen weiterhin <strong>besondere Fachkenntnisse</strong>: Friseur, Baecker, Konditor, Kfz-Werkstatt, Dachdecker, Heizungsbauer, Gastronom und andere.</p>',

            '<h2>Gruendungsschritte</h2>',
            '<h3>Schritt 1: Ein Geschaeftskonto eroeffnen</h3>',
            '<p>Verpflichtend, um geschaeftliche und private Vorgaenge zu trennen.</p>',
            '<h3>Schritt 2: Eintragung in die Zentrale Datenbank der Unternehmen (ZDU)</h3>',
            "<ul>\n    <li>Ueber einen <strong>zugelassenen Unternehmensschalter</strong></li>\n    <li>Erhalt der <strong>Unternehmensnummer</strong> (eindeutige Kennung)</li>\n    <li>Pruefung der Fachkenntnisse, falls erforderlich</li>\n</ul>",
            '<h3>Schritt 3: Die MwSt-Nummer aktivieren</h3>',
            "<ul>\n    <li>Bei der Generalverwaltung Steuerwesen (FOeD Finanzen)</li>\n    <li>Kann ueber den Unternehmensschalter erledigt werden</li>\n    <li>Moeglichkeit, die MwSt-Befreiung zu beantragen (Umsatz &lt; 25 000 EUR, siehe unten)</li>\n</ul>",
            '<h3>Schritt 4: Einer Sozialversicherungskasse beitreten</h3>',
            '<p><strong>Verpflichtend VOR Beginn der Taetigkeit</strong>. Der Beitritt ist bis zu sechs Monate im Voraus moeglich.</p>',
            '<h3>Schritt 5: Einer Krankenkasse beitreten</h3>',
            '<p>Verpflichtend, um Kranken- und Invaliditaetsschutz zu geniessen.</p>',
            '<h3>Schritt 6: Die noetigen Versicherungen abschliessen</h3>',
            '<p>Berufshaftpflicht und weitere je nach Taetigkeit.</p>',

            '<h2>Die acht zugelassenen Unternehmensschalter</h2>',
            "<ol>\n    <li>Liantis (der groesste)</li>\n    <li>Acerta</li>\n    <li>Partena Professional</li>\n    <li>UCM</li>\n    <li>Xerius</li>\n    <li>Securex</li>\n    <li>Eunomia</li>\n    <li>Formalis</li>\n</ol>",

            '<h2>Gruendungskosten</h2>',
            $this->table('Posten', 'Betrag 2026', [
                ['ZDU-Eintragung ueber einen Schalter (einheitlich reguliert)', '~111,50 EUR (MwSt-frei)'],
                ['Sonstiges (Krankenkasse, Haftpflicht, Bank …)', 'Unterschiedlich'],
                ['<strong>Geschaetztes Gesamtbudget</strong>', '<strong>200 – 500 EUR</strong>'],
            ]),

            '<h2>Uebliche Fristen</h2>',
            $this->table('Schritt', 'Frist', [
                ['ZDU-Eintragung ueber einen Schalter', 'Sofort bis einige Tage'],
                ['MwSt-Aktivierung', 'Einige Tage'],
                ['Beitritt zur Sozialkasse', 'Sofort'],
                ['<strong>Gesamter Vorgang</strong>', '<strong>1 bis 2 Wochen</strong>'],
            ]),

            '<h2>Pflichten nach der Gruendung</h2>',
            '<h3>MwSt</h3>',
            '<h4>Normalregelung</h4>',
            "<ul>\n    <li>Regelmaessige MwSt-Erklaerung (monatlich oder vierteljaehrlich)</li>\n    <li>Fakturierung mit MwSt</li>\n    <li>Jaehrliche Kundenliste vor dem 31. Maerz</li>\n</ul>",
            '<h4>Befreiungsregelung (Umsatz ≤ 25 000 EUR)</h4>',
            "<ul>\n    <li>Keine regelmaessige Erklaerung</li>\n    <li>Keine MwSt in Rechnung zu stellen oder abzufuehren</li>\n    <li>Mitteilung des Jahresumsatzes vor dem 31. Maerz</li>\n</ul>",

            '<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">'."\n"
                .'    <p class="font-semibold">Was eine Ueberschreitung ausloest</p>'."\n"
                .'    <p>Die Schwelle von 25 000 EUR laesst einen Spielraum von 10 % zu, die Folgen unterscheiden sich jedoch nach dem Ausmass:</p>'."\n"
                ."    <ul class=\"mt-2\">\n"
                ."        <li><strong>Ueberschreitung um hoechstens 10 %</strong> (bis 27 500 EUR): Sie behalten die Befreiung bis zum 31. Dezember, wechseln aber am 1. Januar darauf in die Normalregelung. Eine Rueckkehr zur Befreiung ist erst am 1. Januar des Folgejahres moeglich.</li>\n"
                ."        <li><strong>Ueberschreitung um mehr als 10 %</strong>: die Normalregelung gilt <strong>sofort</strong>, ab dem Umsatz, der die Schwelle ueberschritten hat – und im Folgejahr ebenfalls.</li>\n"
                ."    </ul>\n"
                .'</div>',

            '<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">'."\n"
                .'    <p class="font-semibold">Pflichtangabe bei Befreiung</p>'."\n"
                .'    <p>« Petite entreprise assujettie au régime de la franchise de taxe - TVA non applicable (Art. 56bis du Code TVA) »</p>'."\n"
                .'</div>',

            '<h3>Sozialbeitraege (INASTI) – 2026</h3>',
            $this->table('Stufe des jaehrlichen Nettoeinkommens', 'Satz 2026', [
                ['0 bis 75 024,54 EUR', '<strong>20,50 %</strong>'],
                ['75 024,54 EUR bis 110 562,42 EUR', '14,16 %'],
                ['Ueber 110 562,42 EUR', 'Befreit (Hoechstgrenze erreicht)'],
            ]),
            '<p><strong>Mindestbeitrag je Quartal 2026:</strong> rund <strong>890 EUR</strong> fuer einen hauptberuflich Selbststaendigen (Mindesteinkommen von 17 374,08 EUR jaehrlich). Den genauen Betrag nach Kasse finden Sie bei <a href="https://www.inasti.be/fr/faq/combien-de-cotisations-sociales-dois-je-payer" target="_blank" rel="noopener">INASTI – Sozialbeitraege</a>.</p>',
            '<p><strong>Ablauf:</strong></p>',
            "<ul>\n    <li><strong>Vierteljaehrliche</strong> Zahlung (31. Maerz / 30. Juni / 30. September / 31. Dezember)</li>\n    <li>Zunaechst <strong>vorlaeufige</strong> Beitraege (auf Basis der Einkuenfte aus N-3)</li>\n    <li>Ausgleich, sobald die endgueltigen Einkuenfte des Jahres N feststehen (in der Regel zwei Jahre spaeter)</li>\n</ul>",

            '<h3>Buchfuehrungspflichten</h3>',
            '<h4>Vereinfachte Buchfuehrung (Umsatz &lt; 500 000 EUR)</h4>',
            '<p>Drei Pflichtjournale:</p>',
            "<ol>\n    <li><strong>Einkaufsjournal:</strong> Aufstellung der Ausgaben</li>\n    <li><strong>Verkaufsjournal:</strong> chronologische Uebersicht der Rechnungen</li>\n    <li><strong>Finanzjournal:</strong> Kassenbuch und Bankbuch</li>\n</ol>",
            '<p><strong>Aufbewahrung der Unterlagen:</strong> zehn Jahre</p>',

            '<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">'."\n"
                .'    <p class="font-semibold">Jaehrlich zu pruefen</p>'."\n"
                .'    <p>Die INASTI-Schwellen, Mindestbeitraege und MwSt-Regeln aendern sich in Belgien jedes Jahr. Diese Seite wird regelmaessig aktualisiert; fuer Ihre persoenliche Situation wenden Sie sich an Ihre Sozialversicherungskasse oder an das <a href="https://www.inasti.be/" target="_blank" rel="noopener">INASTI</a>.</p>'."\n"
                .'</div>',

            '<h2>Offizielle Quellen</h2>',
            "<ul>\n"
                ."    <li><a href=\"https://economie.fgov.be/fr/themes/entreprises/creer-une-entreprise/demarches-pour-un-travailleur\" target=\"_blank\" rel=\"noopener\">FOeD Wirtschaft – Schritte fuer Selbststaendige</a></li>\n"
                ."    <li><a href=\"https://1819.brussels/\" target=\"_blank\" rel=\"noopener\">1819.brussels – Hub fuer Unternehmer</a></li>\n"
                ."    <li><a href=\"https://www.inasti.be/fr/faq/combien-de-cotisations-sociales-dois-je-payer\" target=\"_blank\" rel=\"noopener\">INASTI – Sozialbeitraege</a></li>\n"
                ."    <li><a href=\"https://finances.belgium.be/fr/entreprises/tva/assujettissement-tva/regime-franchise-taxe\" target=\"_blank\" rel=\"noopener\">FOeD Finanzen – MwSt-Befreiungsregelung</a></li>\n"
                .'</ul>',

            '<p class="text-sm text-slate-500 mt-6"><em>Artikel aktualisiert am 4. Juni 2026.</em></p>',

            '<div class="bg-green-50 border-l-4 border-green-500 p-4 my-6">'."\n"
                .'    <p class="font-semibold">Kurz gefasst</p>'."\n"
                .'    <p>Sich in Belgien selbststaendig zu machen kostet rund 200-500 EUR (davon etwa 111 EUR fuer die ZDU-Eintragung) und dauert 1 bis 2 Wochen. Die INASTI-Beitraege 2026 betragen 20,50 % bis 75 024 EUR, dann 14,16 % bis 110 562 EUR. Die MwSt-Befreiung ist moeglich, solange der Umsatz unter 25 000 EUR im Jahr bleibt, mit einem Spielraum von 10 %, der den Wechsel auf den 1. Januar darauf verschiebt.</p>'."\n"
                .'</div>',

            '<div class="mt-8 rounded-xl border border-slate-200 bg-slate-50 p-5"><h3 class="text-base font-semibold text-slate-900 mb-3">Verwandte Artikel</h3><ul class="space-y-1"><li><a href="/de/blog/einzelunternehmen-luxemburg-gruenden-leitfaden-2026" class="text-primary-500 hover:text-primary-600 text-sm">Einzelunternehmen in Luxemburg gruenden: Leitfaden 2026 →</a></li><li><a href="/de/blog/pflichtangaben-rechnung-luxemburg" class="text-primary-500 hover:text-primary-600 text-sm">Pflichtangaben auf einer Rechnung in Luxemburg →</a></li><li><a href="/de/blog/mwst-luxemburg-saetze-berechnung-pflichten" class="text-primary-500 hover:text-primary-600 text-sm">MwSt Luxemburg 2026: die vier Saetze erklaert →</a></li></ul></div>',
        ]);

        $en = implode("\n\n", [
            '<p class="lead">Belgium offers a favourable framework for the self-employed, with simpler formalities since basic management knowledge was abolished. This guide walks you through setting up as a sole trader in 2026, with current thresholds and INASTI contributions.</p>',

            '<h2>Legal form: sole trader (natural person)</h2>',
            '<p>Trading as a natural person is the simplest way to carry on an economic activity alone in Belgium.</p>',
            '<h3>Key characteristics</h3>',
            $this->table('Aspect', 'Detail', [
                ['Deed of incorporation', 'None required'],
                ['Minimum capital', 'None required'],
                ['Liability', '<strong>Unlimited</strong> — personal and business assets are not separated'],
                ['Bookkeeping', 'Simplified if turnover &lt; EUR 500,000'],
            ]),

            '<h2>Conditions and prerequisites</h2>',
            '<h3>General conditions</h3>',
            "<ul>\n    <li>Be at least <strong>18 years old</strong></li>\n    <li>Enjoy your civil and political rights</li>\n    <li>Have legal capacity</li>\n</ul>",

            '<h3>Basic management knowledge: ABOLISHED</h3>',
            '<div class="bg-green-50 border-l-4 border-green-500 p-4 my-6">'."\n"
                .'    <p class="font-semibold">Good news</p>'."\n"
                .'    <p>Basic management knowledge has been abolished in every region:</p>'."\n"
                ."    <ul class=\"mt-2\">\n"
                ."        <li><strong>Flanders:</strong> since 2018</li>\n"
                ."        <li><strong>Brussels:</strong> since 15 January 2024</li>\n"
                ."        <li><strong>Wallonia:</strong> since 1 October 2025</li>\n"
                ."    </ul>\n"
                .'    <p class="mt-2 text-sm">The federal FPS Economy page still mentions an obligation to prove entrepreneurial skills in Wallonia and Brussels. Federal pages sometimes lag behind regional reforms: have a business counter confirm your own case.</p>'."\n"
                .'</div>',

            '<h3>Access to the profession</h3>',
            '<p>Some regulated professions still require <strong>specific professional skills</strong>: hairdresser, baker, pastry chef, garage owner, roofer, heating engineer, restaurateur and others.</p>',

            '<h2>Set-up steps</h2>',
            '<h3>Step 1: Open a business bank account</h3>',
            '<p>Mandatory, to keep business and private transactions apart.</p>',
            '<h3>Step 2: Register with the Crossroads Bank for Enterprises (CBE)</h3>',
            "<ul>\n    <li>Through an <strong>accredited business counter</strong></li>\n    <li>You receive your <strong>enterprise number</strong> (unique identifier)</li>\n    <li>Professional skills are checked where required</li>\n</ul>",
            '<h3>Step 3: Activate the VAT number</h3>',
            "<ul>\n    <li>With the General Administration of Taxation (FPS Finance)</li>\n    <li>Can be done through the business counter</li>\n    <li>You may request the VAT exemption scheme (turnover &lt; EUR 25,000, see below)</li>\n</ul>",
            '<h3>Step 4: Join a social insurance fund</h3>',
            '<p><strong>Mandatory BEFORE starting the activity</strong>. You can join up to six months in advance.</p>',
            '<h3>Step 5: Join a health insurance fund</h3>',
            '<p>Mandatory to obtain sickness and invalidity cover.</p>',
            '<h3>Step 6: Take out the necessary insurance</h3>',
            '<p>Professional liability cover and others depending on the activity.</p>',

            '<h2>The eight accredited business counters</h2>',
            "<ol>\n    <li>Liantis (the largest)</li>\n    <li>Acerta</li>\n    <li>Partena Professional</li>\n    <li>UCM</li>\n    <li>Xerius</li>\n    <li>Securex</li>\n    <li>Eunomia</li>\n    <li>Formalis</li>\n</ol>",

            '<h2>Set-up costs</h2>',
            $this->table('Item', '2026 amount', [
                ['CBE registration via a counter (regulated flat fee)', '~EUR 111.50 (VAT exempt)'],
                ['Sundries (health fund, liability cover, bank …)', 'Varies'],
                ['<strong>Estimated total budget</strong>', '<strong>EUR 200 – 500</strong>'],
            ]),

            '<h2>Typical timescales</h2>',
            $this->table('Formality', 'Time', [
                ['CBE registration via a counter', 'Immediate to a few days'],
                ['VAT activation', 'A few days'],
                ['Joining a social insurance fund', 'Immediate'],
                ['<strong>Whole process</strong>', '<strong>1 to 2 weeks</strong>'],
            ]),

            '<h2>Obligations once you are set up</h2>',
            '<h3>VAT</h3>',
            '<h4>Normal regime</h4>',
            "<ul>\n    <li>Periodic VAT return (monthly or quarterly)</li>\n    <li>Invoicing with VAT</li>\n    <li>Annual client listing before 31 March</li>\n</ul>",
            '<h4>Exemption scheme (turnover ≤ EUR 25,000)</h4>',
            "<ul>\n    <li>No periodic return</li>\n    <li>No VAT to charge or remit</li>\n    <li>Report annual turnover before 31 March</li>\n</ul>",

            '<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">'."\n"
                .'    <p class="font-semibold">What exceeding the threshold triggers</p>'."\n"
                .'    <p>The EUR 25,000 threshold allows a 10% margin, but the consequences differ with the size of the overrun:</p>'."\n"
                ."    <ul class=\"mt-2\">\n"
                ."        <li><strong>Exceeded by up to 10%</strong> (up to EUR 27,500): you keep the exemption until 31 December, then move to the normal regime on 1 January. You can only return to the exemption on 1 January of the year after that.</li>\n"
                ."        <li><strong>Exceeded by more than 10%</strong>: the normal regime applies <strong>immediately</strong>, from the transaction that crossed the threshold — and the following year as well.</li>\n"
                ."    </ul>\n"
                .'</div>',

            '<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">'."\n"
                .'    <p class="font-semibold">Mandatory mention under the exemption</p>'."\n"
                .'    <p>« Petite entreprise assujettie au régime de la franchise de taxe - TVA non applicable (Art. 56bis du Code TVA) »</p>'."\n"
                .'</div>',

            '<h3>Social contributions (INASTI) — 2026</h3>',
            $this->table('Band of annual net income', '2026 rate', [
                ['0 to EUR 75,024.54', '<strong>20.50%</strong>'],
                ['EUR 75,024.54 to EUR 110,562.42', '14.16%'],
                ['Above EUR 110,562.42', 'Exempt (ceiling reached)'],
            ]),
            '<p><strong>Minimum quarterly contribution 2026:</strong> around <strong>EUR 890</strong> for someone self-employed as a main occupation (minimum income set at EUR 17,374.08 a year). See <a href="https://www.inasti.be/fr/faq/combien-de-cotisations-sociales-dois-je-payer" target="_blank" rel="noopener">INASTI – Social contributions</a> for the exact amount from your fund.</p>',
            '<p><strong>How it works:</strong></p>',
            "<ul>\n    <li><strong>Quarterly</strong> payment (31 March / 30 June / 30 September / 31 December)</li>\n    <li>Contributions are <strong>provisional</strong> at first (based on income from N-3)</li>\n    <li>Adjusted once the final income for year N is known (usually two years later)</li>\n</ul>",

            '<h3>Bookkeeping obligations</h3>',
            '<h4>Simplified bookkeeping (turnover &lt; EUR 500,000)</h4>',
            '<p>Three mandatory journals:</p>',
            "<ol>\n    <li><strong>Purchase journal:</strong> list of expenses</li>\n    <li><strong>Sales journal:</strong> chronological overview of invoices</li>\n    <li><strong>Cash journal:</strong> cash book and bank book</li>\n</ol>",
            '<p><strong>Document retention:</strong> ten years</p>',

            '<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">'."\n"
                .'    <p class="font-semibold">To check every year</p>'."\n"
                .'    <p>INASTI thresholds, minimum contributions and VAT rules change every year in Belgium. This page is updated regularly, but for your own situation consult your social insurance fund or <a href="https://www.inasti.be/" target="_blank" rel="noopener">INASTI</a>.</p>'."\n"
                .'</div>',

            '<h2>Official sources</h2>',
            "<ul>\n"
                ."    <li><a href=\"https://economie.fgov.be/fr/themes/entreprises/creer-une-entreprise/demarches-pour-un-travailleur\" target=\"_blank\" rel=\"noopener\">FPS Economy – Steps for the self-employed</a></li>\n"
                ."    <li><a href=\"https://1819.brussels/\" target=\"_blank\" rel=\"noopener\">1819.brussels – Hub for entrepreneurs</a></li>\n"
                ."    <li><a href=\"https://www.inasti.be/fr/faq/combien-de-cotisations-sociales-dois-je-payer\" target=\"_blank\" rel=\"noopener\">INASTI – Social contributions</a></li>\n"
                ."    <li><a href=\"https://finances.belgium.be/fr/entreprises/tva/assujettissement-tva/regime-franchise-taxe\" target=\"_blank\" rel=\"noopener\">FPS Finance – VAT exemption scheme</a></li>\n"
                .'</ul>',

            '<p class="text-sm text-slate-500 mt-6"><em>Article updated on 4 June 2026.</em></p>',

            '<div class="bg-green-50 border-l-4 border-green-500 p-4 my-6">'."\n"
                .'    <p class="font-semibold">In short</p>'."\n"
                .'    <p>Becoming self-employed in Belgium costs around EUR 200-500 (of which about EUR 111 for CBE registration) and takes 1 to 2 weeks. INASTI contributions for 2026 are 20.50% up to EUR 75,024, then 14.16% up to EUR 110,562. The VAT exemption is available while turnover stays under EUR 25,000 a year, with a 10% margin that pushes the switch to 1 January of the following year.</p>'."\n"
                .'</div>',

            '<div class="mt-8 rounded-xl border border-slate-200 bg-slate-50 p-5"><h3 class="text-base font-semibold text-slate-900 mb-3">Related articles</h3><ul class="space-y-1"><li><a href="/en/blog/sole-proprietorship-luxembourg-guide-2026" class="text-primary-500 hover:text-primary-600 text-sm">Setting up a sole proprietorship in Luxembourg: complete guide 2026 →</a></li><li><a href="/en/blog/mandatory-information-invoice-luxembourg" class="text-primary-500 hover:text-primary-600 text-sm">Mandatory information on a Luxembourg invoice →</a></li><li><a href="/en/blog/vat-luxembourg-rates-calculation-obligations" class="text-primary-500 hover:text-primary-600 text-sm">Luxembourg VAT 2026: the four rates explained →</a></li></ul></div>',
        ]);

        $lb = implode("\n\n", [
            '<p class="lead">D\'Belsch bitt den Onofhängegen e gënschtege Kader, mat vereinfachte Schrëtt zanter der Ofschafung vun de Grondkenntnisser vun der Betribsféierung. Dëse Guide begleet Iech 2026 bei der Grënnung vun Ärer Entreprise als natierlech Persoun, mat den aktuelle Schwellen an INASTI-Bäiträg.</p>',

            '<h2>Rechtsform: Entreprise als natierlech Persoun</h2>',
            '<p>D\'Entreprise als natierlech Persoun (Onofhängegen) ass déi einfachst Form fir eleng eng wirtschaftlech Aktivitéit an der Belsch auszeüben.</p>',
            '<h3>Haaptcharakteristiken</h3>',
            $this->table('Aspekt', 'Detail', [
                ['Grënnungsakt', 'Keen néideg'],
                ['Mindestkapital', 'Keent néideg'],
                ['Verantwortung', '<strong>Onbegrenzt</strong> – perséinlecht a beruflecht Verméige sinn net getrennt'],
                ['Comptabilitéit', 'Vereinfacht wann den Ëmsaz &lt; 500 000 € ass'],
            ]),

            '<h2>Konditiounen a Viraussetzungen</h2>',
            '<h3>Allgemeng Konditiounen</h3>',
            "<ul>\n    <li>Op mannst <strong>18 Joer</strong> al sinn</li>\n    <li>Seng biergerlech a politesch Rechter hunn</li>\n    <li>Rechtlech handlungsfäeg sinn</li>\n</ul>",

            '<h3>Grondkenntnisser vun der Betribsféierung: OFGESCHAFFT</h3>',
            '<div class="bg-green-50 border-l-4 border-green-500 p-4 my-6">'."\n"
                .'    <p class="font-semibold">Gutt Noriicht!</p>'."\n"
                .'    <p>D\'Grondkenntnisser vun der Betribsféierung goufen an alle Regiounen ofgeschaaft:</p>'."\n"
                ."    <ul class=\"mt-2\">\n"
                ."        <li><strong>Flandern:</strong> zanter 2018</li>\n"
                ."        <li><strong>Bréissel:</strong> zanter dem 15. Januar 2024</li>\n"
                ."        <li><strong>Wallonie:</strong> zanter dem 1. Oktober 2025</li>\n"
                ."    </ul>\n"
                .'    <p class="mt-2 text-sm">Déi federal Säit vum SPF Economie ernimmt awer nach ëmmer eng Flicht, seng entrepreneuriell Fäegkeeten an der Wallonie an zu Bréissel ze beweisen. Federal Säiten hänken heiansdo hannert regionale Reformen hier: loosst Äre Fall vun engem Entreprisë-Schalter bestätegen.</p>'."\n"
                .'</div>',

            '<h3>Zougang zum Beruff</h3>',
            '<p>Gewësse reglementéiert Beruffer verlaangen ëmmer nach <strong>spezifesch beruflech Kompetenzen</strong>: Coiffeur, Bäcker, Patissier, Garagist, Dachdecker, Heizungsinstallateur, Restaurateur asw.</p>',

            '<h2>Grënnungsschrëtt</h2>',
            '<h3>Schrëtt 1: E professionnelle Bankkont opmaachen</h3>',
            '<p>Obligatoresch, fir déi beruflech an déi privat Operatiounen ze trennen.</p>',
            '<h3>Schrëtt 2: Sech an der Banque-Carrefour des Entreprises (BCE) aschreiwen</h3>',
            "<ul>\n    <li>Iwwer en <strong>agreéierten Entreprisë-Schalter</strong></li>\n    <li>Dir kritt Är <strong>Entreprisënummer</strong> (eendeiteg Identifikatioun)</li>\n    <li>Kontroll vun de beruflechen Kompetenzen, wann néideg</li>\n</ul>",
            '<h3>Schrëtt 3: D\'TVA-Nummer aktivéieren</h3>',
            "<ul>\n    <li>Bei der Administration générale de la Fiscalité (SPF Finances)</li>\n    <li>Kann iwwer den Entreprisë-Schalter gemaach ginn</li>\n    <li>Méiglechkeet, d'TVA-Franchise ze froen (Ëmsaz &lt; 25 000 €, kuckt méi ënnen)</li>\n</ul>",
            '<h3>Schrëtt 4: Sech bei enger Sozialversécherungskeess uschléissen</h3>',
            '<p><strong>Obligatoresch VIRUM Ufank vun der Aktivitéit</strong>. Den Uschloss ass bis zu sechs Méint am Viraus méiglech.</p>',
            '<h3>Schrëtt 5: Sech bei enger Mutualitéit uschléissen</h3>',
            '<p>Obligatoresch fir vun der Krankheets- an Invaliditéitsversécherung ze profitéieren.</p>',
            '<h3>Schrëtt 6: Déi néideg Versécherungen ofschléissen</h3>',
            '<p>Beruflech Haftpflichtversécherung an anerer no Aktivitéit.</p>',

            '<h2>Déi aacht agreéiert Entreprisë-Schalteren</h2>',
            "<ol>\n    <li>Liantis (dee gréissten)</li>\n    <li>Acerta</li>\n    <li>Partena Professional</li>\n    <li>UCM</li>\n    <li>Xerius</li>\n    <li>Securex</li>\n    <li>Eunomia</li>\n    <li>Formalis</li>\n</ol>",

            '<h2>Grënnungskäschten</h2>',
            $this->table('Posten', 'Montant 2026', [
                ['BCE-Aschreiwung iwwer e Schalter (eenheetlech reglementéiert Tarif)', '~111,50 € (TVA-fräi)'],
                ['Verschidde Käschten (Mutualitéit, RC-Versécherung, Bank …)', 'Variabel'],
                ['<strong>Geschate Gesamtbudget</strong>', '<strong>200 – 500 €</strong>'],
            ]),

            '<h2>Duerchschnëttlech Delaien</h2>',
            $this->table('Schrëtt', 'Delai', [
                ['BCE-Aschreiwung iwwer e Schalter', 'Direkt bis e puer Deeg'],
                ['TVA-Aktivéierung', 'E puer Deeg'],
                ['Uschloss un d\'Sozialkeess', 'Direkt'],
                ['<strong>Kompletten Prozess</strong>', '<strong>1 bis 2 Wochen</strong>'],
            ]),

            '<h2>Obligatiounen no der Grënnung</h2>',
            '<h3>TVA</h3>',
            '<h4>Normalregime</h4>',
            "<ul>\n    <li>Periodesch TVA-Deklaratioun (méintlech oder trimestriell)</li>\n    <li>Fakturatioun mat TVA</li>\n    <li>Jäerlech Clientelëscht virum 31. Mäerz</li>\n</ul>",
            '<h4>Franchise-Regime (Ëmsaz ≤ 25 000 €)</h4>',
            "<ul>\n    <li>Keng periodesch Deklaratioun</li>\n    <li>Keng TVA ze fakturéieren nach ofzeféieren</li>\n    <li>Matdeele vum jäerlechen Ëmsaz virum 31. Mäerz</li>\n</ul>",

            '<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">'."\n"
                .'    <p class="font-semibold">Wat eng Iwwerschreidung ausléist</p>'."\n"
                .'    <p>D\'Schwell vun 25 000 € léisst e Spillraum vun 10 % zou, mä d\'Konsequenzen ënnerscheede sech no der Gréisst:</p>'."\n"
                ."    <ul class=\"mt-2\">\n"
                ."        <li><strong>Iwwerschreidung vun héchstens 10 %</strong> (bis 27 500 €): Dir behaalt d'Franchise bis den 31. Dezember, wiesselt awer den 1. Januar duerno an d'Normalregime. E Retour zur Franchise ass eréischt den 1. Januar vum Joer duerno méiglech.</li>\n"
                ."        <li><strong>Iwwerschreidung vu méi wéi 10 %</strong>: d'Normalregime gëllt <strong>direkt</strong>, vun der Operatioun un, déi d'Schwell iwwerschratt huet – an d'Joer duerno och.</li>\n"
                ."    </ul>\n"
                .'</div>',

            '<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">'."\n"
                .'    <p class="font-semibold">Pflichtmentioun an der Franchise</p>'."\n"
                .'    <p>« Petite entreprise assujettie au régime de la franchise de taxe - TVA non applicable (Art. 56bis du Code TVA) »</p>'."\n"
                .'</div>',

            '<h3>Sozialbäiträg (INASTI) – 2026</h3>',
            $this->table('Tranche vum jäerlechen Nettoakommes', 'Saz 2026', [
                ['0 bis 75 024,54 €', '<strong>20,50 %</strong>'],
                ['75 024,54 € bis 110 562,42 €', '14,16 %'],
                ['Iwwer 110 562,42 €', 'Befreit (Plafong erreecht)'],
            ]),
            '<p><strong>Minimal Trimesterbäitrag 2026:</strong> ronn <strong>890 €</strong> fir en Onofhängegen am Haaptberuff (Mindestakommes vun 17 374,08 € am Joer). De genaue Montant no Keess fannt Dir bei <a href="https://www.inasti.be/fr/faq/combien-de-cotisations-sociales-dois-je-payer" target="_blank" rel="noopener">INASTI – Sozialbäiträg</a>.</p>',
            '<p><strong>Funktionéiere:</strong></p>',
            "<ul>\n    <li><strong>Trimestriell</strong> Bezuelung (31. Mäerz / 30. Juni / 30. September / 31. Dezember)</li>\n    <li>Bäiträg éischt <strong>provisoresch</strong> (op Basis vun den Akommes vun N-3)</li>\n    <li>Regulariséierung, soubal déi definitiv Akommes vum Joer N bekannt sinn (allgemeng zwee Joer méi spéit)</li>\n</ul>",

            '<h3>Comptabel Obligatiounen</h3>',
            '<h4>Vereinfacht Comptabilitéit (Ëmsaz &lt; 500 000 €)</h4>',
            '<p>Dräi obligatoresch Journalen:</p>',
            "<ol>\n    <li><strong>Akafsjournal:</strong> Lëscht vun den Ausgaben</li>\n    <li><strong>Verkafsjournal:</strong> chronologeschen Iwwerbléck vun de Rechnungen</li>\n    <li><strong>Trésorerie-Journal:</strong> Keessebuch a Bankbuch</li>\n</ol>",
            '<p><strong>Opbewahrung vun den Dokumenter:</strong> 10 Joer</p>',

            '<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">'."\n"
                .'    <p class="font-semibold">All Joer ze kontrolléieren</p>'."\n"
                .'    <p>D\'INASTI-Schwellen, d\'Mindestbäiträg an d\'TVA-Reegelen änneren sech an der Belsch all Joer. Dës Säit gëtt reegelméisseg aktualiséiert, mä fir Är perséinlech Situatioun frot Är Sozialversécherungskeess oder d\'<a href="https://www.inasti.be/" target="_blank" rel="noopener">INASTI</a>.</p>'."\n"
                .'</div>',

            '<h2>Offiziell Quellen</h2>',
            "<ul>\n"
                ."    <li><a href=\"https://economie.fgov.be/fr/themes/entreprises/creer-une-entreprise/demarches-pour-un-travailleur\" target=\"_blank\" rel=\"noopener\">SPF Economie – Schrëtt fir en Onofhängegen</a></li>\n"
                ."    <li><a href=\"https://1819.brussels/\" target=\"_blank\" rel=\"noopener\">1819.brussels – Hub fir Entrepreneuren</a></li>\n"
                ."    <li><a href=\"https://www.inasti.be/fr/faq/combien-de-cotisations-sociales-dois-je-payer\" target=\"_blank\" rel=\"noopener\">INASTI – Sozialbäiträg</a></li>\n"
                ."    <li><a href=\"https://finances.belgium.be/fr/entreprises/tva/assujettissement-tva/regime-franchise-taxe\" target=\"_blank\" rel=\"noopener\">SPF Finances – TVA-Franchise-Regime</a></li>\n"
                .'</ul>',

            '<p class="text-sm text-slate-500 mt-6"><em>Artikel den 4. Juni 2026 aktualiséiert.</em></p>',

            '<div class="bg-green-50 border-l-4 border-green-500 p-4 my-6">'."\n"
                .'    <p class="font-semibold">Kuerz zesummegefaasst</p>'."\n"
                .'    <p>Onofhängegen an der Belsch ze ginn kascht ronn 200-500 € (dovunner ronn 111 € fir d\'BCE-Aschreiwung) an dauert 1 bis 2 Wochen. D\'INASTI-Bäiträg 2026 sinn 20,50 % bis 75 024 €, duerno 14,16 % bis 110 562 €. D\'TVA-Franchise ass méiglech, soulaang den Ëmsaz ënner 25 000 € am Joer bleift, mat engem Spillraum vun 10 %, deen de Wiessel op den 1. Januar duerno verréckelt.</p>'."\n"
                .'</div>',

            '<div class="mt-8 rounded-xl border border-slate-200 bg-slate-50 p-5"><h3 class="text-base font-semibold text-slate-900 mb-3">Verbonnen Artikelen</h3><ul class="space-y-1"><li><a href="/lb/blog/eenzelentreprise-letzebuerg-grenden-guide-2026" class="text-primary-500 hover:text-primary-600 text-sm">Eng Eenzelentreprise zu Lëtzebuerg grënnen: Guide 2026 →</a></li><li><a href="/lb/blog/pflichtinformatiounen-rechnung-letzebuerg" class="text-primary-500 hover:text-primary-600 text-sm">Pflichtmentiounen op enger Rechnung zu Lëtzebuerg →</a></li><li><a href="/lb/blog/tva-letzebuerg-tariffer-berechnung-obligatiounen" class="text-primary-500 hover:text-primary-600 text-sm">TVA Lëtzebuerg 2026: déi 4 Sätz erkläert →</a></li></ul></div>',
        ]);

        $pt = implode("\n\n", [
            '<p class="lead">A Bélgica oferece um quadro favorável aos independentes, com formalidades simplificadas desde a supressão dos conhecimentos de gestão de base. Este guia acompanha-o na criação da sua empresa em nome individual em 2026, com os limiares e as contribuições INASTI atualizados.</p>',

            '<h2>Forma jurídica: empresa em nome individual</h2>',
            '<p>A empresa em nome individual (independente) é a forma mais simples de exercer sozinho uma atividade económica na Bélgica.</p>',
            '<h3>Características principais</h3>',
            $this->table('Aspeto', 'Detalhe', [
                ['Ato constitutivo', 'Nenhum exigido'],
                ['Capital mínimo', 'Nenhum exigido'],
                ['Responsabilidade', '<strong>Ilimitada</strong> — património pessoal e profissional não separados'],
                ['Contabilidade', 'Simplificada se o volume de negócios for &lt; 500 000 €'],
            ]),

            '<h2>Condições e requisitos</h2>',
            '<h3>Condições gerais</h3>',
            "<ul>\n    <li>Ter no mínimo <strong>18 anos</strong></li>\n    <li>Gozar dos direitos civis e políticos</li>\n    <li>Ter capacidade legal</li>\n</ul>",

            '<h3>Conhecimentos de gestão de base: SUPRIMIDOS</h3>',
            '<div class="bg-green-50 border-l-4 border-green-500 p-4 my-6">'."\n"
                .'    <p class="font-semibold">Boa notícia!</p>'."\n"
                .'    <p>Os conhecimentos de gestão de base foram suprimidos em todas as regiões:</p>'."\n"
                ."    <ul class=\"mt-2\">\n"
                ."        <li><strong>Flandres:</strong> desde 2018</li>\n"
                ."        <li><strong>Bruxelas:</strong> desde 15 de janeiro de 2024</li>\n"
                ."        <li><strong>Valónia:</strong> desde 1 de outubro de 2025</li>\n"
                ."    </ul>\n"
                .'    <p class="mt-2 text-sm">A página federal do SPF Économie menciona, contudo, ainda a obrigação de provar as capacidades empresariais na Valónia e em Bruxelas. As páginas federais acompanham por vezes com atraso as reformas regionais: confirme o seu caso junto de um balcão de empresas.</p>'."\n"
                .'</div>',

            '<h3>Acesso à profissão</h3>',
            '<p>Certas profissões regulamentadas continuam a exigir <strong>competências profissionais específicas</strong>: cabeleireiro, padeiro, pasteleiro, mecânico, telhador, técnico de aquecimento, restaurador, entre outros.</p>',

            '<h2>Etapas de criação</h2>',
            '<h3>Etapa 1: Abrir uma conta bancária profissional</h3>',
            '<p>Obrigatória para separar as operações profissionais das privadas.</p>',
            '<h3>Etapa 2: Inscrever-se no Banco-Encruzilhada das Empresas (BCE)</h3>',
            "<ul>\n    <li>Através de um <strong>balcão de empresas acreditado</strong></li>\n    <li>Obtenção do <strong>número de empresa</strong> (identificador único)</li>\n    <li>Verificação das competências profissionais, se necessário</li>\n</ul>",
            '<h3>Etapa 3: Ativar o número de IVA</h3>',
            "<ul>\n    <li>Junto da Administração Geral da Fiscalidade (SPF Finances)</li>\n    <li>Pode ser feito através do balcão de empresas</li>\n    <li>Possibilidade de pedir o regime de isenção de IVA (volume de negócios &lt; 25 000 €, ver adiante)</li>\n</ul>",
            '<h3>Etapa 4: Filiar-se numa caixa de seguros sociais</h3>',
            '<p><strong>Obrigatório ANTES do início da atividade</strong>. A filiação é possível até seis meses antes.</p>',
            '<h3>Etapa 5: Filiar-se numa mutualidade</h3>',
            '<p>Obrigatório para beneficiar do seguro de doença e invalidez.</p>',
            '<h3>Etapa 6: Subscrever os seguros necessários</h3>',
            '<p>Seguro de responsabilidade civil profissional e outros consoante a atividade.</p>',

            '<h2>Os oito balcões de empresas acreditados</h2>',
            "<ol>\n    <li>Liantis (o maior)</li>\n    <li>Acerta</li>\n    <li>Partena Professional</li>\n    <li>UCM</li>\n    <li>Xerius</li>\n    <li>Securex</li>\n    <li>Eunomia</li>\n    <li>Formalis</li>\n</ol>",

            '<h2>Custos de criação</h2>',
            $this->table('Rubrica', 'Montante 2026', [
                ['Inscrição no BCE via balcão (tarifa regulada idêntica)', '~111,50 € (isento de IVA)'],
                ['Despesas diversas (mutualidade, seguro RC, banco…)', 'Variável'],
                ['<strong>Orçamento total estimado</strong>', '<strong>200 – 500 €</strong>'],
            ]),

            '<h2>Prazos médios</h2>',
            $this->table('Diligência', 'Prazo', [
                ['Inscrição no BCE via balcão', 'Imediato a alguns dias'],
                ['Ativação do IVA', 'Alguns dias'],
                ['Filiação na caixa social', 'Imediata'],
                ['<strong>Processo completo</strong>', '<strong>1 a 2 semanas</strong>'],
            ]),

            '<h2>Obrigações após a criação</h2>',
            '<h3>IVA</h3>',
            '<h4>Regime normal</h4>',
            "<ul>\n    <li>Declaração periódica de IVA (mensal ou trimestral)</li>\n    <li>Faturação com IVA</li>\n    <li>Listagem anual de clientes antes de 31 de março</li>\n</ul>",
            '<h4>Regime de isenção (volume de negócios ≤ 25 000 €)</h4>',
            "<ul>\n    <li>Sem declaração periódica</li>\n    <li>Sem IVA a faturar nem a entregar</li>\n    <li>Comunicação do volume de negócios anual antes de 31 de março</li>\n</ul>",

            '<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">'."\n"
                .'    <p class="font-semibold">O que desencadeia uma ultrapassagem</p>'."\n"
                .'    <p>O limiar de 25 000 € admite uma margem de 10 %, mas as consequências diferem consoante a dimensão da ultrapassagem:</p>'."\n"
                ."    <ul class=\"mt-2\">\n"
                ."        <li><strong>Ultrapassagem até 10 %</strong> (até 27 500 €): mantém a isenção até 31 de dezembro, passando ao regime normal a 1 de janeiro seguinte. O regresso à isenção só é possível a 1 de janeiro do ano seguinte a esse.</li>\n"
                ."        <li><strong>Ultrapassagem superior a 10 %</strong>: o regime normal aplica-se <strong>imediatamente</strong>, a partir da operação que fez ultrapassar o limiar — e também no ano seguinte.</li>\n"
                ."    </ul>\n"
                .'</div>',

            '<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">'."\n"
                .'    <p class="font-semibold">Menção obrigatória em isenção</p>'."\n"
                .'    <p>« Petite entreprise assujettie au régime de la franchise de taxe - TVA non applicable (Art. 56bis du Code TVA) »</p>'."\n"
                .'</div>',

            '<h3>Contribuições sociais (INASTI) — 2026</h3>',
            $this->table('Escalão de rendimento anual líquido', 'Taxa 2026', [
                ['0 a 75 024,54 €', '<strong>20,50 %</strong>'],
                ['75 024,54 € a 110 562,42 €', '14,16 %'],
                ['Acima de 110 562,42 €', 'Isento (teto atingido)'],
            ]),
            '<p><strong>Contribuição trimestral mínima 2026:</strong> cerca de <strong>890 €</strong> para um independente a título principal (rendimento mínimo fixado em 17 374,08 € anuais). Consulte <a href="https://www.inasti.be/fr/faq/combien-de-cotisations-sociales-dois-je-payer" target="_blank" rel="noopener">INASTI – Contribuições sociais</a> para o montante exato da sua caixa.</p>',
            '<p><strong>Funcionamento:</strong></p>',
            "<ul>\n    <li>Pagamento <strong>trimestral</strong> (31 de março / 30 de junho / 30 de setembro / 31 de dezembro)</li>\n    <li>Contribuições inicialmente <strong>provisórias</strong> (com base nos rendimentos de N-3)</li>\n    <li>Regularização quando os rendimentos definitivos de N forem conhecidos (geralmente dois anos depois)</li>\n</ul>",

            '<h3>Obrigações contabilísticas</h3>',
            '<h4>Contabilidade simplificada (volume de negócios &lt; 500 000 €)</h4>',
            '<p>Três diários obrigatórios:</p>',
            "<ol>\n    <li><strong>Diário de compras:</strong> lista das despesas</li>\n    <li><strong>Diário de vendas:</strong> panorâmica cronológica das faturas</li>\n    <li><strong>Diário de tesouraria:</strong> livro de caixa e livro de banco</li>\n</ol>",
            '<p><strong>Conservação dos documentos:</strong> 10 anos</p>',

            '<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">'."\n"
                .'    <p class="font-semibold">A verificar todos os anos</p>'."\n"
                .'    <p>Os limiares INASTI, as contribuições mínimas e as regras de IVA mudam todos os anos na Bélgica. Esta página é atualizada regularmente, mas para a sua situação pessoal consulte a sua caixa de seguros sociais ou o <a href="https://www.inasti.be/" target="_blank" rel="noopener">INASTI</a>.</p>'."\n"
                .'</div>',

            '<h2>Fontes oficiais</h2>',
            "<ul>\n"
                ."    <li><a href=\"https://economie.fgov.be/fr/themes/entreprises/creer-une-entreprise/demarches-pour-un-travailleur\" target=\"_blank\" rel=\"noopener\">SPF Économie – Diligências para um trabalhador independente</a></li>\n"
                ."    <li><a href=\"https://1819.brussels/\" target=\"_blank\" rel=\"noopener\">1819.brussels – Hub para empreendedores</a></li>\n"
                ."    <li><a href=\"https://www.inasti.be/fr/faq/combien-de-cotisations-sociales-dois-je-payer\" target=\"_blank\" rel=\"noopener\">INASTI – Contribuições sociais</a></li>\n"
                ."    <li><a href=\"https://finances.belgium.be/fr/entreprises/tva/assujettissement-tva/regime-franchise-taxe\" target=\"_blank\" rel=\"noopener\">SPF Finances – Regime de isenção de IVA</a></li>\n"
                .'</ul>',

            '<p class="text-sm text-slate-500 mt-6"><em>Artigo atualizado a 4 de junho de 2026.</em></p>',

            '<div class="bg-green-50 border-l-4 border-green-500 p-4 my-6">'."\n"
                .'    <p class="font-semibold">Em resumo</p>'."\n"
                .'    <p>Tornar-se independente na Bélgica custa cerca de 200-500 € (dos quais cerca de 111 € para a inscrição no BCE) e demora 1 a 2 semanas. As contribuições INASTI 2026 são de 20,50 % até 75 024 €, depois 14,16 % até 110 562 €. A isenção de IVA é possível enquanto o volume de negócios se mantiver abaixo de 25 000 €/ano, com uma margem de 10 % que adia a saída para 1 de janeiro seguinte.</p>'."\n"
                .'</div>',

            '<div class="mt-8 rounded-xl border border-slate-200 bg-slate-50 p-5"><h3 class="text-base font-semibold text-slate-900 mb-3">Artigos relacionados</h3><ul class="space-y-1"><li><a href="/pt/blog/criar-uma-empresa-individual-no-luxemburgo-guia-completo-2026" class="text-primary-500 hover:text-primary-600 text-sm">Criar uma empresa individual no Luxemburgo: guia completo 2026 →</a></li><li><a href="/pt/blog/mencoes-obrigatorias-numa-fatura-no-luxemburgo-checklist-completa" class="text-primary-500 hover:text-primary-600 text-sm">Menções obrigatórias numa fatura no Luxemburgo →</a></li><li><a href="/pt/blog/iva-no-luxemburgo-taxas-calculo-e-obrigacoes-para-as-empresas" class="text-primary-500 hover:text-primary-600 text-sm">IVA no Luxemburgo 2026: as 4 taxas explicadas →</a></li></ul></div>',
        ]);

        return ['de' => $de, 'en' => $en, 'lb' => $lb, 'pt' => $pt];
    }
};
