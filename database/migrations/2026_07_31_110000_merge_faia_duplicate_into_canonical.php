<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * FUSION des deux articles FAIA du blog.
 *
 * Le blog hébergeait deux articles concurrents sur le FAIA, qui se
 * cannibalisaient en SEO et doublaient la charge de maintenance — avec le
 * risque de divergence déjà constaté : l'un affirmait que tous les
 * assujettis sont concernés, l'autre non.
 *
 * SURVIVANT : faia-luxembourg-fichier-audit-informatise-guide
 *   24 liens internes depuis 8 articles, et présent dans le top 10 Matomo.
 * RETIRÉ    : faia-luxembourg-guide-fichier-audit-informatise-2026
 *   5 liens internes depuis 1 seul article.
 *
 * Cette migration fait trois choses :
 *
 * 1. ENRICHIT LE SURVIVANT de ce que l'article retiré avait de meilleur,
 *    dans les cinq langues : la section « les 4 erreurs FAIA les plus
 *    fréquentes » et la FAQ en cinq questions. Ce n'est pas une
 *    concaténation : le reste du contenu retiré (définition du FAIA,
 *    périmètre, contenu du fichier, options de génération) faisait déjà
 *    doublon avec le survivant, corrigé le 2026-07-30.
 *
 * 2. ARCHIVE l'article retiré (status = 'archived' sur les 5 lignes). Le
 *    scope `published()` du modèle filtre sur 'published', donc l'article
 *    quitte l'index, le sitemap et la page publique — sans être supprimé,
 *    ce qui rend l'opération réversible.
 *
 * 3. RÉÉCRIT LES LIENS INTERNES qui pointaient vers l'ancien slug, dans
 *    les cinq langues, pour qu'ils visent directement le survivant plutôt
 *    que de transiter par une redirection.
 *
 * Les redirections 301 (une par langue) sont déclarées séparément dans
 * routes/web.php, avant le groupe localisé — sans quoi `blog.show`
 * capturerait la route en premier, comme le rappelle le commentaire déjà
 * présent dans ce fichier.
 *
 * Les slugs diffèrent par langue : ils sont donc traités explicitement,
 * et non par substitution du slug français.
 */
return new class extends Migration
{
    private const RETIRED = 'faia-luxembourg-guide-fichier-audit-informatise-2026';
    private const CANONICAL = 'faia-luxembourg-fichier-audit-informatise-guide';

    /** Ancien slug → nouveau slug, par langue. */
    private const SLUG_MAP = [
        'fr' => ['faia-luxembourg-guide-fichier-audit-informatise-2026', 'faia-luxembourg-fichier-audit-informatise-guide'],
        'de' => ['faia-luxemburg-vollstaendiger-leitfaden-pruefdatei-2026', 'faia-luxemburg-informatisierte-audit-datei-leitfaden'],
        'en' => ['faia-luxembourg-standard-audit-file-complete-guide-2026', 'faia-luxembourg-computerized-audit-file-guide'],
        'lb' => ['faia-letzebuerg-komplette-guide-auditdatei-2026', 'faia-letzebuerg-informatiseierte-audit-fichier-guide'],
        'pt' => ['faia-luxemburgo-guia-completo-ficheiro-auditoria-2026', 'faia-luxemburgo-tudo-sobre-o-ficheiro-de-auditoria-informatizado'],
    ];

    /** H2 avant lequel insérer les nouvelles sections, par langue. */
    private const ANCHOR = [
        'fr' => '<h2>Sources officielles</h2>',
        'de' => '<h2>Offizielle Quellen</h2>',
        'en' => '<h2>Official sources</h2>',
        'lb' => '<h2>Offiziell Quellen</h2>',
        'pt' => '<h2>Fontes oficiais</h2>',
    ];

    public function up(): void
    {
        // 1. Enrichir le survivant.
        foreach (self::ANCHOR as $locale => $anchor) {
            $post = DB::table('blog_posts')
                ->where('translation_key', self::CANONICAL)
                ->where('locale', $locale)
                ->first(['id', 'content']);

            if (! $post || ! str_contains($post->content, $anchor)) {
                continue; // ancre absente : on ne touche pas plutôt que d'insérer au mauvais endroit
            }

            $addition = $this->addition($locale);

            if (str_contains($post->content, $addition)) {
                continue; // déjà appliqué
            }

            DB::table('blog_posts')->where('id', $post->id)->update([
                'content' => str_replace($anchor, $addition . $anchor, $post->content),
                'updated_at' => now(),
            ]);
        }

        // 2. Archiver l'article retiré.
        DB::table('blog_posts')
            ->where('translation_key', self::RETIRED)
            ->update(['status' => 'archived', 'updated_at' => now()]);

        // 3. Réécrire les liens internes vers l'ancien slug.
        foreach (self::SLUG_MAP as $locale => [$old, $new]) {
            foreach (DB::table('blog_posts')->where('locale', $locale)->get(['id', 'content']) as $row) {
                if (! str_contains($row->content, '/blog/' . $old)) {
                    continue;
                }
                DB::table('blog_posts')->where('id', $row->id)->update([
                    'content' => str_replace('/blog/' . $old, '/blog/' . $new, $row->content),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        // Réversible sur le point qui compte : l'article retiré redevient
        // visible. L'enrichissement du survivant et la réécriture des liens
        // ne sont pas annulés — ils restent corrects dans les deux cas.
        DB::table('blog_posts')
            ->where('translation_key', self::RETIRED)
            ->update(['status' => 'published', 'updated_at' => now()]);
    }

    private function addition(string $locale): string
    {
        return match ($locale) {
            'fr' => <<<'HTML'
<h2>Les 4 erreurs FAIA les plus fréquentes</h2>

<ol>
    <li><strong>Numérotation des factures non conforme</strong> à l'article 63 LIVA point 3° (trous dans la séquence ou doublons). Le fichier peut être rejeté à la validation.</li>
    <li><strong>Champs obligatoires manquants</strong> : numéro de TVA du client, adresse complète, mention d'autoliquidation pour le B2B intra-UE.</li>
    <li><strong>Totaux incohérents</strong> entre les sections — par exemple, somme des factures différente du total déclaré.</li>
    <li><strong>Format de date incorrect</strong> : le FAIA exige la norme ISO 8601 (AAAA-MM-JJ), pas JJ/MM/AAAA.</li>
</ol>

<h2>Questions fréquentes</h2>

<h3>Faut-il envoyer un FAIA chaque année ?</h3>
<p>Non. Le FAIA est produit uniquement sur demande de l'AED lors d'un contrôle, et seulement par les entreprises qui y sont soumises. Il ne se dépose jamais avec la déclaration de TVA.</p>

<h3>Que se passe-t-il si je ne peux pas le fournir alors que j'y suis tenu ?</h3>
<p>Le contrôleur considère que votre comptabilité n'est pas tenue selon les standards. Vous vous exposez à une amende administrative de 250 € à 10 000 € par infraction (art. 77 LIVA), à une taxation d'office, et — en cas de refus de communiquer les pièces — à une amende pouvant atteindre 25 000 € par jour de retard après avertissement.</p>

<h3>Le FAIA est-il obligatoire en franchise de TVA ?</h3>
<p>En général non. La franchise (art. 57bis LIVA, chiffre d'affaires ≤ 50 000 €) place l'assujetti sous le seuil de 112 000 €, et le régime simplifié est de toute façon une cause d'exclusion à lui seul.</p>

<h3>Comment savoir si mon fichier est conforme avant un contrôle ?</h3>
<p>Validez-le contre le schéma XSD officiel. Le <a href="/fr/validateur-faia">validateur FAIA de faktur.lu</a> est gratuit et sans inscription : il vérifie la conformité au schéma 2.01, détecte les champs manquants, contrôle la cohérence des totaux et la séquentialité des numéros de facture, sans stocker vos données.</p>

<h3>Mon comptable peut-il générer le FAIA pour moi ?</h3>
<p>Oui. Votre fiduciaire peut le produire depuis son propre outil, ou récupérer le vôtre via un portail comptable en lecture seule.</p>


HTML,
            'de' => <<<'HTML'
<h2>Die 4 häufigsten FAIA-Fehler</h2>

<ol>
    <li><strong>Nicht konforme Rechnungsnummerierung</strong> nach Artikel 63 LIVA Ziffer 3° (Lücken in der Sequenz oder Dubletten). Die Datei kann bei der Validierung zurückgewiesen werden.</li>
    <li><strong>Fehlende Pflichtfelder</strong>: MwSt.-Nummer des Kunden, vollständige Anschrift, Reverse-Charge-Vermerk beim innergemeinschaftlichen B2B.</li>
    <li><strong>Unstimmige Summen</strong> zwischen den Abschnitten — etwa eine Rechnungssumme, die vom ausgewiesenen Gesamtbetrag abweicht.</li>
    <li><strong>Falsches Datumsformat</strong>: Die FAIA verlangt die Norm ISO 8601 (JJJJ-MM-TT), nicht TT/MM/JJJJ.</li>
</ol>

<h2>Häufige Fragen</h2>

<h3>Muss man jedes Jahr eine FAIA einsenden?</h3>
<p>Nein. Die FAIA wird ausschließlich auf Verlangen der AED bei einer Prüfung erzeugt, und nur von den betroffenen Unternehmen. Sie wird nie zusammen mit der MwSt.-Erklärung eingereicht.</p>

<h3>Was passiert, wenn ich sie nicht liefern kann, obwohl ich dazu verpflichtet bin?</h3>
<p>Der Prüfer geht davon aus, dass Ihre Buchhaltung nicht standardgemäß geführt wird. Sie riskieren ein Verwaltungsbußgeld von 250 € bis 10 000 € je Verstoß (Art. 77 LIVA), eine Schätzung von Amts wegen und — bei Weigerung, die Unterlagen vorzulegen — eine Geldbuße von bis zu 25 000 € pro Verzugstag nach Verwarnung.</p>

<h3>Ist die FAIA bei MwSt.-Freigrenze Pflicht?</h3>
<p>In der Regel nein. Die Freigrenze (Art. 57bis LIVA, Umsatz ≤ 50 000 €) hält den Steuerpflichtigen unter der Schwelle von 112 000 €, und die vereinfachte Regelung ist ohnehin für sich genommen ein Ausschlussgrund.</p>

<h3>Wie erfahre ich vor einer Prüfung, ob meine Datei konform ist?</h3>
<p>Validieren Sie sie gegen das offizielle XSD-Schema. Der <a href="/de/validateur-faia">FAIA-Validator von faktur.lu</a> ist kostenlos und ohne Anmeldung: Er prüft die Konformität mit dem Schema 2.01, erkennt fehlende Felder, kontrolliert die Stimmigkeit der Summen und die Fortlaufendheit der Rechnungsnummern, ohne Ihre Daten zu speichern.</p>

<h3>Kann mein Buchhalter die FAIA für mich erzeugen?</h3>
<p>Ja. Ihr Treuhänder kann sie aus seinem eigenen Werkzeug erzeugen oder Ihre über ein Buchhalterportal mit Lesezugriff abrufen.</p>


HTML,
            'en' => <<<'HTML'
<h2>The 4 most common FAIA mistakes</h2>

<ol>
    <li><strong>Invoice numbering not compliant</strong> with article 63 LIVA point 3° (gaps in the sequence or duplicates). The file can be rejected at validation.</li>
    <li><strong>Missing mandatory fields</strong>: customer VAT number, full address, reverse charge wording for intra-EU B2B.</li>
    <li><strong>Inconsistent totals</strong> between sections — for instance a sum of invoices that differs from the declared total.</li>
    <li><strong>Incorrect date format</strong>: the FAIA requires ISO 8601 (YYYY-MM-DD), not DD/MM/YYYY.</li>
</ol>

<h2>Frequently asked questions</h2>

<h3>Do you have to send a FAIA every year?</h3>
<p>No. The FAIA is produced solely at the AED's request during an audit, and only by the businesses covered. It is never filed alongside the VAT return.</p>

<h3>What happens if I cannot provide it when required?</h3>
<p>The auditor concludes that your accounts are not kept to standard. You risk an administrative fine of €250 to €10,000 per infringement (art. 77 LIVA), an assessment on the administration's own estimate, and — where you refuse to produce records — a fine of up to €25,000 per day of delay after a warning.</p>

<h3>Is the FAIA mandatory under the VAT exemption regime?</h3>
<p>Generally no. The exemption (art. 57bis LIVA, turnover ≤ €50,000) keeps the taxable person below the €112,000 threshold, and a simplified regime is in any case a ground for exclusion on its own.</p>

<h3>How do I know my file is compliant before an audit?</h3>
<p>Validate it against the official XSD schema. The <a href="/en/faia-validator">faktur.lu FAIA validator</a> is free and needs no sign-up: it checks compliance with the 2.01 schema, detects missing fields, verifies the consistency of totals and the sequentiality of invoice numbers, and stores none of your data.</p>

<h3>Can my accountant generate the FAIA for me?</h3>
<p>Yes. Your accountant can produce it from their own tool, or retrieve yours through a read-only accountant portal.</p>


HTML,
            'lb' => <<<'HTML'
<h2>Déi 4 heefegst FAIA-Feeler</h2>

<ol>
    <li><strong>Net konform Rechnungsnummeréierung</strong> no dem Artikel 63 LIVA Punkt 3° (Lacken an der Sequenz oder Duebelen). De Fichier kann bei der Validatioun zréckgewise ginn.</li>
    <li><strong>Feelend obligatoresch Felder</strong>: TVA-Nummer vum Client, vollstänneg Adress, Mentioun vun der Autoliquidatioun beim innergemeinschaftleche B2B.</li>
    <li><strong>Inkohärent Totaler</strong> tëscht de Sektiounen — zum Beispill eng Rechnungszomm, déi vum deklaréierten Total ofwäicht.</li>
    <li><strong>Falscht Datumsformat</strong>: de FAIA verlaangt d'Norm ISO 8601 (JJJJ-MM-DD), net DD/MM/JJJJ.</li>
</ol>

<h2>Heefeg Froen</h2>

<h3>Muss ee all Joer e FAIA schécken?</h3>
<p>Neen. De FAIA gëtt eleng op Ufro vun der AED bei enger Kontroll produzéiert, an nëmme vun de betraffenen Entreprisen. En gëtt ni mat der TVA-Deklaratioun ofginn.</p>

<h3>Wat geschitt, wann ech en net liwwere kann, obwuel ech dozou verflicht sinn?</h3>
<p>De Kontrolleur geet dovun aus, datt Är Comptabilitéit net no de Standarde gefouert gëtt. Dir riskéiert eng administrativ Geldstrof vun 250 € bis 10 000 € pro Infraktioun (Art. 77 LIVA), eng Taxatioun vun Amts wéinst, an — bei Refus, d'Stécker ze weisen — eng Geldstrof bis zu 25 000 € pro Dag Verspéidung no engem Avertissement.</p>

<h3>Ass de FAIA bei der TVA-Franchise obligatoresch?</h3>
<p>Allgemeng neen. D'Franchise (Art. 57bis LIVA, Ëmsaz ≤ 50 000 €) hält den Assujetti ënner dem Seuil vun 112 000 €, an de vereinfachte Regime ass sowisou fir sech eleng e Grond fir den Ausschloss.</p>

<h3>Wéi weess ech virun enger Kontroll, ob mäi Fichier konform ass?</h3>
<p>Validéiert en géint dat offiziellt XSD-Schema. De <a href="/lb/faia-validator">FAIA-Validator vu faktur.lu</a> ass gratis an ouni Aschreiwung: en iwwerpréift d'Konformitéit mam Schema 2.01, erkennt feelend Felder, kontrolléiert d'Kohärenz vun den Totaler an d'Sequenzialitéit vun de Rechnungsnummeren, ouni Är Daten ze späicheren.</p>

<h3>Kann mäi Comptabel de FAIA fir mech generéieren?</h3>
<p>Jo. Är Fiduciaire kann en aus hirem eegenen Tool produzéieren, oder Ären iwwer e Comptabelsportal mat Lieszougrëff ofruffen.</p>


HTML,
            'pt' => <<<'HTML'
<h2>Os 4 erros FAIA mais frequentes</h2>

<ol>
    <li><strong>Numeração das faturas não conforme</strong> ao artigo 63 LIVA ponto 3° (falhas na sequência ou duplicados). O ficheiro pode ser rejeitado na validação.</li>
    <li><strong>Campos obrigatórios em falta</strong>: número de IVA do cliente, endereço completo, menção de autoliquidação para o B2B intra-UE.</li>
    <li><strong>Totais incoerentes</strong> entre as secções — por exemplo, uma soma de faturas diferente do total declarado.</li>
    <li><strong>Formato de data incorreto</strong>: o FAIA exige a norma ISO 8601 (AAAA-MM-DD), não DD/MM/AAAA.</li>
</ol>

<h2>Perguntas frequentes</h2>

<h3>É preciso enviar um FAIA todos os anos?</h3>
<p>Não. O FAIA é produzido unicamente a pedido da AED durante uma inspeção, e apenas pelas empresas abrangidas. Nunca se entrega juntamente com a declaração de IVA.</p>

<h3>O que acontece se não o conseguir fornecer estando obrigado?</h3>
<p>O inspetor considera que a sua contabilidade não é mantida segundo os padrões. Arrisca uma coima administrativa de 250 € a 10 000 € por infração (art. 77 LIVA), uma tributação oficiosa e — em caso de recusa de apresentação dos documentos — uma coima que pode atingir 25 000 € por dia de atraso após advertência.</p>

<h3>O FAIA é obrigatório em regime de isenção de IVA?</h3>
<p>Em geral, não. A isenção (art. 57bis LIVA, volume de negócios ≤ 50 000 €) coloca o sujeito passivo abaixo do limiar de 112 000 €, e o regime simplificado é, de qualquer modo, por si só um motivo de exclusão.</p>

<h3>Como saber se o meu ficheiro está conforme antes de uma inspeção?</h3>
<p>Valide-o contra o esquema XSD oficial. O <a href="/pt/validador-faia">validador FAIA do faktur.lu</a> é gratuito e sem inscrição: verifica a conformidade com o esquema 2.01, deteta campos em falta, controla a coerência dos totais e a sequencialidade dos números de fatura, sem armazenar os seus dados.</p>

<h3>O meu contabilista pode gerar o FAIA por mim?</h3>
<p>Sim. O seu contabilista pode produzi-lo a partir da sua própria ferramenta, ou obter o seu através de um portal contabilístico em modo de leitura.</p>


HTML,
            default => '',
        };
    }
};
