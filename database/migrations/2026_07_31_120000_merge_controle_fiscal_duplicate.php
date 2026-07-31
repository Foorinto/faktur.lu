<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * FUSION des deux articles « contrôle fiscal » du blog.
 *
 * SURVIVANT (URL) : controle-fiscal-luxembourg-comment-preparer
 *   Choisi par Alexandre : le plus ancien (19 février 2026), titre
 *   intemporel, et 11 liens internes depuis 3 articles.
 * RETIRÉ (URL)    : controle-fiscal-aed-luxembourg-2026-preparation
 *
 * PARTICULARITÉ : contrairement à la fusion FAIA, c'est ici l'article
 * RETIRÉ qui portait le meilleur contenu — 11 845 caractères, 11 H2,
 * 8 H3 et 10 liens en français, contre 7 010 caractères, 7 H2, AUCUN H3
 * et 5 liens pour le survivant, dont les traductions allemande et
 * luxembourgeoise étaient tombées à 3 100 caractères. Une fusion bien
 * faite conserve l'URL qui a l'ancienneté et le contenu qui est le
 * meilleur : le contenu du retiré est donc promu dans l'URL du
 * survivant, après vérification et correction.
 *
 * ERREUR MAJEURE CORRIGÉE — LA PRESCRIPTION.
 * L'article affirmait, deux fois (corps et FAQ) : « la prescription est
 * de 5 ans en règle générale, portée à 10 ans en cas de non-déclaration
 * ou de déclaration inexacte (article 81 LIVA) ».
 *
 * L'article 81 de la loi TVA dit exactement l'inverse :
 *   « L'action du Trésor en paiement de l'impôt et des amendes se
 *     prescrit par cinq ans à partir du 31 décembre de l'année dans
 *     laquelle la somme à percevoir est devenue exigible. »
 * Aucune extension à dix ans n'y figure, et le mot « dix ans »
 * n'apparaît dans toute la loi TVA que pour la CONSERVATION des
 * documents, jamais pour la prescription.
 *
 * L'origine de l'erreur est identifiée : la prescription décennale en
 * cas de non-déclaration ou de déclaration inexacte existe bel et bien,
 * mais pour les IMPÔTS DIRECTS (§144 AO et art. 10 de la loi du
 * 27 novembre 1933, modifiée en 1999), administrés par l'ACD — pas pour
 * la TVA, administrée par l'AED. L'article transposait la règle d'une
 * administration à l'autre, dans une page consacrée précisément aux
 * contrôles de l'AED.
 *
 * Conséquence concrète pour le lecteur : croire que l'AED peut remonter
 * dix ans en TVA conduit à ne pas contester un redressement portant sur
 * des années prescrites. C'est une erreur qui coûte de l'argent.
 *
 * Le texte distingue désormais explicitement les deux régimes, et
 * sépare la prescription (5 ans) de l'obligation de conservation
 * (10 ans), que l'article présentait comme une conséquence de la
 * première alors qu'elles sont indépendantes.
 *
 * AUTRES CORRECTIONS :
 * - Périmètre FAIA : l'encadré listait trois exclusions ; ajout de la
 *   quatrième (volume d'environ 500 transactions) et du caractère
 *   cumulatif, pour aligner sur les deux articles FAIA corrigés.
 * - « FAIA format 2.01 publié en 2020 » : juillet 2020 est la date de
 *   dernière mise à jour de la page AED, pas celle du schéma.
 * - Sanctions : ajout de l'amende proportionnelle de 10 % à 50 % de la
 *   TVA en cause et de celle pouvant atteindre 25 000 EUR par jour de
 *   retard en cas de refus de communiquer les pièces — c'est exactement
 *   la situation d'un contrôle, donc la sanction la plus pertinente.
 * - Voies de recours : ajout de la règle du silence à six mois (la
 *   réclamation est réputée rejetée et le recours devient ouvert) et du
 *   délai de trois mois, sous peine de forclusion, pour signifier
 *   l'assignation après la décision directoriale.
 * - Lien Legilux vers le Code de commerce remplacé par la page
 *   « obligations comptables » de guichet.lu, vérifiable.
 *
 * FAITS VÉRIFIÉS et confirmés exacts : réclamation écrite motivée dans
 * les trois mois, saisine du directeur, recours par assignation devant
 * le tribunal d'arrondissement de Luxembourg siégeant en matière civile
 * (juridiction judiciaire et non administrative) ; amendes de 250 à
 * 10 000 EUR (art. 77) ; sanctions pénales de 25 000 EUR à dix fois la
 * TVA et un mois à cinq ans (art. 80) ; conservation dix ans.
 *
 * ⚠️ La version luxembourgeoise mérite une relecture par un locuteur natif.
 */
return new class extends Migration
{
    private const RETIRED = 'controle-fiscal-aed-luxembourg-2026-preparation';
    private const CANONICAL = 'controle-fiscal-luxembourg-comment-preparer';

    private const SLUG_MAP = [
        'fr' => ['controle-fiscal-aed-luxembourg-2026-preparation', 'controle-fiscal-luxembourg-comment-preparer'],
        'de' => ['aed-steuerpruefung-luxemburg-2026-vorbereitung', 'steuerpruefung-luxemburg-vorbereiten'],
        'en' => ['aed-tax-audit-luxembourg-2026-preparation-guide', 'tax-audit-luxembourg-how-to-prepare'],
        'lb' => ['aed-steierkontroll-letzebuerg-2026-virbereedung', 'steierprefung-letzebuerg-virbereden'],
        'pt' => ['auditoria-fiscal-aed-luxemburgo-2026-preparacao', 'controlo-fiscal-no-luxemburgo-como-se-preparar'],
    ];

    public function up(): void
    {
        foreach (['fr' => $this->fr(), 'de' => $this->de(), 'en' => $this->en(), 'lb' => $this->lb(), 'pt' => $this->pt()] as $locale => $content) {
            DB::table('blog_posts')
                ->where('translation_key', self::CANONICAL)
                ->where('locale', $locale)
                ->update(['content' => $content, 'updated_at' => now()]);
        }

        DB::table('blog_posts')
            ->where('translation_key', self::RETIRED)
            ->update(['status' => 'archived', 'updated_at' => now()]);

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
        DB::table('blog_posts')
            ->where('translation_key', self::RETIRED)
            ->update(['status' => 'published', 'updated_at' => now()]);
    }

    private function fr(): string
    {
        return <<<'HTML'
<p class="lead">Recevoir une notification de contrôle de l'<strong>Administration de l'enregistrement, des domaines et de la TVA (AED)</strong> inquiète tous les indépendants et dirigeants de PME au Luxembourg. Pourtant, avec une bonne préparation, un contrôle se passe en quelques heures. Voici comment vous y préparer — et une règle de prescription que presque tout le monde se trompe.</p>

<h2>Qui est concerné par un contrôle AED ?</h2>

<p>Toute entreprise immatriculée à la TVA au Luxembourg peut faire l'objet d'un contrôle. En pratique, l'administration cible :</p>

<ul>
    <li><strong>Les entreprises en croissance rapide</strong> dont le chiffre d'affaires fluctue de manière inhabituelle</li>
    <li><strong>Les secteurs à risque</strong> identifiés par l'AED (commerce, restauration, BTP, conseil)</li>
    <li><strong>Les déclarations TVA atypiques</strong> (crédit de TVA récurrent, opérations intra-UE importantes)</li>
    <li><strong>Les contrôles aléatoires</strong> selon des critères statistiques</li>
</ul>

<h2>Jusqu'où l'AED peut-elle remonter ?</h2>

<p>C'est le point le plus mal compris du sujet, et l'erreur se retrouve partout — y compris, jusqu'à récemment, sur cette page. L'<strong>article 81 de la loi TVA</strong> est pourtant sans ambiguïté :</p>

<blockquote class="border-l-4 border-slate-300 pl-4 italic text-slate-600 my-6">
    <p>« L'action du Trésor en paiement de l'impôt et des amendes se prescrit par <strong>cinq ans</strong> à partir du 31 décembre de l'année dans laquelle la somme à percevoir est devenue exigible. »</p>
</blockquote>

<p>En matière de <strong>TVA</strong>, la prescription est donc de <strong>cinq ans</strong>. Le mot « dix ans » n'apparaît dans toute la loi TVA que pour la <em>conservation</em> des documents — jamais pour la prescription.</p>

<div class="bg-red-50 border-l-4 border-red-500 p-4 my-6">
    <p class="font-semibold text-red-800">⚠️ Ne confondez pas TVA et impôts directs</p>
    <p class="text-red-700">La prescription portée à <strong>dix ans</strong> en cas de non-déclaration ou de déclaration inexacte existe bel et bien — mais pour les <strong>impôts directs</strong> (§144 AO et art. 10 de la loi du 27 novembre 1933), administrés par l'ACD. Elle ne s'applique <strong>pas</strong> à la TVA, qui relève de l'AED. Croire l'inverse peut vous conduire à accepter un redressement portant sur des années déjà prescrites.</p>
</div>

<p>Deux nuances tout de même. La prescription peut être <strong>interrompue</strong> (art. 2244 et suivants du Code civil, ou renonciation de l'assujetti) : un nouveau délai court alors, acquis à la fin de la quatrième année suivant le dernier acte interruptif. Et la prescription court à partir du <strong>31 décembre</strong> de l'année d'exigibilité, pas à la date de l'opération.</p>

<p>Enfin, prescription et archivage sont deux choses distinctes : vous devez conserver vos factures <strong>dix ans</strong> (art. 65 LIVA et art. 16 du Code de commerce) indépendamment du délai de prescription de cinq ans.</p>

<h2>Les 3 types de contrôle AED</h2>

<h3>1. Contrôle sur pièces</h3>

<p>L'AED vous demande d'envoyer des documents par courrier ou voie électronique. Pas de visite physique. C'est le type le plus fréquent et le moins lourd.</p>

<h3>2. Contrôle sur place</h3>

<p>Un agent se déplace dans vos locaux pour vérifier vos livres comptables, factures et justificatifs de dépenses. Annoncé par courrier — la loi TVA ne fixe pas de délai de préavis, l'AED convient du rendez-vous au cas par cas.</p>

<h3>3. Contrôle inopiné</h3>

<p>Rare, réservé aux soupçons de fraude grave. L'agent se présente sans préavis. Vous pouvez demander la présence de votre fiduciaire pendant le contrôle.</p>

<h2>Les documents que l'AED peut demander</h2>

<ul>
    <li><strong>Toutes vos factures émises et reçues</strong> sur la période contrôlée, avec les mentions obligatoires de l'art. 63 LIVA</li>
    <li><strong>Le fichier FAIA</strong> au format 2.01 — pour les assujettis tenus de le produire, voir ci-dessous</li>
    <li><strong>Le livre des recettes et dépenses</strong> ou la comptabilité complète selon votre régime</li>
    <li><strong>Les déclarations de TVA</strong> déposées, périodiques et annuelles</li>
    <li><strong>Les relevés bancaires</strong> professionnels de la période</li>
    <li><strong>Les justificatifs des dépenses déduites</strong> (notes de frais, factures fournisseurs)</li>
    <li><strong>Les contrats</strong> clients et fournisseurs significatifs</li>
    <li><strong>La preuve de l'autoliquidation</strong> pour le B2B intra-UE : numéros de TVA validés via VIES (art. 17 LIVA, art. 196 de la directive)</li>
</ul>

<h2>FAIA : le point critique — mais pas pour tout le monde</h2>

<p>Le <strong>FAIA</strong> est un fichier XML structuré, dérivé du standard SAF-T, qui regroupe l'en-tête de l'entreprise, les factures de vente de la période, les écritures comptables associées et les totaux de contrôle. Le schéma en vigueur est la version <strong>2.01</strong>, dont la dernière mise à jour publiée par l'AED date de juillet 2020.</p>

<div class="bg-blue-50 border-l-4 border-blue-500 p-4 my-6">
    <p class="font-semibold">Quatre conditions cumulatives</p>
    <p>Le FAIA ne concerne pas tous les assujettis. Selon la FAQ de l'AED, l'obligation suppose de réunir <strong>en même temps</strong> : être soumis au plan comptable normalisé (PCN), ne pas bénéficier d'un régime simplifié, dépasser 112 000 € de chiffre d'affaires, et dépasser environ <strong>500 transactions comptables</strong> par an. Une transaction est une chaîne entière de comptabilisation, pas une facture. Si une seule condition manque, vous n'êtes pas visé — voir notre <a href="/fr/blog/faia-luxembourg-fichier-audit-informatise-guide">guide complet du FAIA</a>.</p>
</div>

<p>Si vous y êtes soumis et que vous facturez sur tableur, il faudra reconstituer le fichier à la main : plusieurs jours de travail, et un risque d'erreur élevé.</p>

<h2>Comment se déroule un contrôle sur place</h2>

<ol>
    <li><strong>Notification écrite</strong> de l'AED, en général par courrier recommandé</li>
    <li><strong>Arrivée du ou des agents</strong> munis d'une commission de contrôle</li>
    <li><strong>Demande des documents</strong> selon une liste pré-établie</li>
    <li><strong>Vérification des écritures</strong> et croisement avec les déclarations TVA</li>
    <li><strong>Entretien</strong> sur les opérations non standard ou les écarts détectés</li>
    <li><strong>Notification des conclusions</strong> par courrier</li>
</ol>

<h2>Les 5 erreurs qui coûtent cher</h2>

<ol>
    <li><strong>Numérotation non séquentielle des factures</strong> (art. 63 LIVA, point 3°) : trous ou doublons valent présomption d'omission de chiffre d'affaires</li>
    <li><strong>Mentions obligatoires manquantes</strong> sur les factures (art. 63 LIVA)</li>
    <li><strong>Autoliquidation intra-UE non documentée</strong> : sans validation VIES du numéro client, l'AED peut requalifier l'opération en imposable au Luxembourg</li>
    <li><strong>Justificatifs de dépenses manquants</strong> ou illisibles</li>
    <li><strong>Incohérences entre déclarations TVA et factures</strong> : un écart même minime déclenche une vérification approfondie</li>
</ol>

<h2>Quelles sanctions en cas d'erreur ?</h2>

<ul>
    <li><strong>Manquements formels</strong> (mention manquante, FAIA non fourni, déclaration tardive) : amende administrative de <strong>250 € à 10 000 € par infraction</strong> (art. 77 LIVA)</li>
    <li><strong>Manquement ayant privé le Trésor de recettes</strong> : amende de <strong>10 % à 50 % de la TVA en cause</strong> — proportionnelle, donc sans plafond, et bien plus lourde que la précédente sur un litige significatif</li>
    <li><strong>Refus de communiquer factures et pièces comptables</strong> lors du contrôle : jusqu'à <strong>25 000 € par jour de retard</strong>, après avertissement</li>
    <li><strong>Fraude fiscale aggravée ou escroquerie fiscale</strong> : amende pénale de 25 000 € à dix fois le montant de TVA, emprisonnement d'un mois à cinq ans, privation des droits civiques de 5 à 10 ans (art. 80 LIVA)</li>
</ul>

<h2>Préparer son contrôle en 5 étapes</h2>

<ol>
    <li><strong>Préparer le FAIA</strong> sur la période demandée, si vous y êtes soumis</li>
    <li><strong>Exporter toutes les factures émises</strong>, idéalement en PDF/A</li>
    <li><strong>Vérifier la cohérence</strong> avec les déclarations de TVA déposées</li>
    <li><strong>Constituer un dossier par mois</strong> : factures, relevés bancaires, déclaration correspondante</li>
    <li><strong>Prévenir votre fiduciaire</strong> et lui demander d'être présente pendant le contrôle</li>
</ol>

<h2>Contester les conclusions</h2>

<p>Si vous n'êtes pas d'accord, la procédure est encadrée :</p>

<ol>
    <li><strong>Réclamation écrite et motivée</strong> auprès de l'administration, dans un délai de <strong>trois mois</strong> à compter de la notification</li>
    <li>En cas de rejet, <strong>le directeur de l'administration est saisi</strong> et sa décision se substitue à la précédente</li>
    <li><strong>Recours par assignation</strong> devant le <strong>tribunal d'arrondissement de Luxembourg, siégeant en matière civile</strong> — la TVA relève de la juridiction judiciaire, et non administrative comme les impôts directs. L'exploit doit être signifié dans les <strong>trois mois</strong> de la décision directoriale, <strong>sous peine de forclusion</strong></li>
</ol>

<div class="bg-blue-50 border-l-4 border-blue-500 p-4 my-6">
    <p class="font-semibold">Le silence de l'administration ne vous bloque pas</p>
    <p>Si aucune décision n'intervient dans les <strong>six mois</strong> suivant votre réclamation, vous pouvez la considérer comme rejetée et introduire directement un recours. Dans ce cas, le délai de trois mois ne court pas — vous n'êtes donc pas forclos par l'attente.</p>
</div>

<h2>Comment faktur.lu vous prépare</h2>

<ul>
    <li>Numérotation séquentielle automatique conforme à l'<strong>article 63 LIVA</strong></li>
    <li>Mentions obligatoires générées sur chaque facture</li>
    <li>Validation VIES pour les opérations B2B intra-UE</li>
    <li>Export <strong>FAIA 2.01</strong> sur n'importe quelle période</li>
    <li>Archivage <strong>PDF/A</strong> avec empreinte d'intégrité</li>
    <li>Portail fiduciaire en lecture seule, sans allers-retours par e-mail</li>
</ul>

<h2>Questions fréquentes</h2>

<h3>Combien de temps dure un contrôle ?</h3>
<p>Un contrôle sur pièces : deux à quatre semaines, le temps d'envoyer les documents. Un contrôle sur place : généralement un à trois jours en entreprise, puis plusieurs semaines d'analyse côté AED.</p>

<h3>Puis-je refuser un contrôle ?</h3>
<p>Non. Le refus est sanctionné et présume la mauvaise foi. Vous pouvez en revanche demander un report pour motif légitime et exiger la présence de votre fiduciaire.</p>

<h3>Dois-je tout de même archiver dix ans si la prescription est de cinq ans ?</h3>
<p>Oui. Ce sont deux obligations indépendantes : la conservation de dix ans découle de l'art. 65 LIVA et de l'art. 16 du Code de commerce, pas du délai de prescription.</p>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">À vérifier chaque année</p>
    <p>Procédures, sanctions et délais peuvent évoluer. Cette page est mise à jour régulièrement, mais pour votre situation personnelle, consultez votre fiduciaire ou directement l'<a href="https://pfi.public.lu/" target="_blank" rel="noopener">AED</a>.</p>
</div>

<h2>Sources officielles</h2>

<ul>
    <li><a href="https://pfi.public.lu/dam-assets/pdf/legislation/tva/loi/loi-tva-2023-01-01.pdf" target="_blank" rel="noopener">Loi TVA coordonnée - articles 63, 77, 80, 81</a></li>
    <li><a href="https://logistics.public.lu/fr/formalities-procedures/taxes/VAT-sanctions-remedies.html" target="_blank" rel="noopener">Sanctions TVA et voies de recours</a></li>
    <li><a href="https://pfi.public.lu/dam-assets/backup/FAIA/FAIA/FAIA-FAQ.pdf" target="_blank" rel="noopener">AED - FAQ FAIA (champ d'application)</a></li>
    <li><a href="https://guichet.public.lu/fr/entreprises/gestion-juridique-comptabilite/comptable/enregistrement/obligations-comptables.html" target="_blank" rel="noopener">Guichet.lu - Obligations comptables</a></li>
</ul>

<p class="text-sm text-slate-500 mt-6"><em>Article mis à jour le 31 juillet 2026.</em></p>

<h2>Pour aller plus loin</h2>
<ul>
    <li><a href="/fr/blog/faia-luxembourg-fichier-audit-informatise-guide">FAIA Luxembourg : tout savoir sur le fichier d'audit informatisé</a></li>
    <li><a href="/fr/blog/mentions-obligatoires-facture-luxembourg">Mentions obligatoires sur une facture au Luxembourg</a></li>
    <li><a href="/fr/blog/archivage-factures-luxembourg-duree-legale-format">Archivage des factures : durée légale et format</a></li>
</ul>

<div class="mt-8 p-6 bg-primary-50 rounded-2xl border border-primary-200">
    <h3 class="text-lg font-bold text-primary-900 mb-2">Préparez votre prochain contrôle dès aujourd'hui</h3>
    <p class="text-primary-800 mb-4">Numérotation automatique, FAIA 2.01 en un clic, mentions conformes, archivage PDF/A. Le contrôle devient une formalité.</p>
    <a href="/fr/register" class="inline-block bg-primary-500 hover:bg-primary-600 text-white font-semibold px-6 py-3 rounded-xl">Démarrer gratuitement</a>
</div>
HTML;
    }

    private function de(): string
    {
        return <<<'HTML'
<p class="lead">Eine Prüfungsankündigung der <strong>Registrierungs-, Domänen- und MwSt.-Verwaltung (AED)</strong> beunruhigt jeden Selbstständigen und KMU-Leiter in Luxemburg. Mit guter Vorbereitung ist eine Prüfung jedoch in wenigen Stunden erledigt. Hier erfahren Sie, wie Sie sich vorbereiten — und eine Verjährungsregel, die fast alle falsch wiedergeben.</p>

<h2>Wer ist von einer AED-Prüfung betroffen?</h2>

<p>Jedes in Luxemburg MwSt.-registrierte Unternehmen kann geprüft werden. In der Praxis zielt die Verwaltung auf:</p>

<ul>
    <li><strong>Schnell wachsende Unternehmen</strong> mit ungewöhnlich schwankendem Umsatz</li>
    <li><strong>Risikobranchen</strong>, die die AED identifiziert (Handel, Gastronomie, Bau, Beratung)</li>
    <li><strong>Auffällige MwSt.-Erklärungen</strong> (wiederkehrendes MwSt.-Guthaben, bedeutende innergemeinschaftliche Umsätze)</li>
    <li><strong>Stichprobenprüfungen</strong> nach statistischen Kriterien</li>
</ul>

<h2>Wie weit zurück darf die AED gehen?</h2>

<p>Das ist der am häufigsten missverstandene Punkt, und der Fehler findet sich überall — bis vor Kurzem auch auf dieser Seite. <strong>Artikel 81 des MwSt.-Gesetzes</strong> ist jedoch eindeutig:</p>

<blockquote class="border-l-4 border-slate-300 pl-4 italic text-slate-600 my-6">
    <p>„Die Klage des Fiskus auf Zahlung der Steuer und der Geldbußen verjährt in <strong>fünf Jahren</strong> ab dem 31. Dezember des Jahres, in dem der einzuziehende Betrag fällig geworden ist."</p>
</blockquote>

<p>Bei der <strong>MwSt.</strong> beträgt die Verjährung also <strong>fünf Jahre</strong>. Der Ausdruck „zehn Jahre" kommt im gesamten MwSt.-Gesetz nur für die <em>Aufbewahrung</em> von Unterlagen vor — nie für die Verjährung.</p>

<div class="bg-red-50 border-l-4 border-red-500 p-4 my-6">
    <p class="font-semibold text-red-800">⚠️ Verwechseln Sie MwSt. nicht mit direkten Steuern</p>
    <p class="text-red-700">Die auf <strong>zehn Jahre</strong> verlängerte Verjährung bei Nichterklärung oder unrichtiger Erklärung existiert tatsächlich — aber für die <strong>direkten Steuern</strong> (§144 AO und Art. 10 des Gesetzes vom 27. November 1933), verwaltet von der ACD. Sie gilt <strong>nicht</strong> für die MwSt., die der AED untersteht. Das Gegenteil zu glauben kann dazu führen, dass Sie eine Nachforderung für bereits verjährte Jahre akzeptieren.</p>
</div>

<p>Zwei Einschränkungen gleichwohl. Die Verjährung kann <strong>unterbrochen</strong> werden (Art. 2244 ff. Zivilgesetzbuch oder Verzicht des Steuerpflichtigen): Dann läuft eine neue Frist, die am Ende des vierten Jahres nach der letzten Unterbrechungshandlung eintritt. Und die Frist läuft ab dem <strong>31. Dezember</strong> des Fälligkeitsjahres, nicht ab dem Datum des Umsatzes.</p>

<p>Schließlich sind Verjährung und Archivierung zweierlei: Sie müssen Ihre Rechnungen <strong>zehn Jahre</strong> aufbewahren (Art. 65 LIVA und Art. 16 Handelsgesetzbuch), unabhängig von der fünfjährigen Verjährung.</p>

<h2>Die 3 Arten der AED-Prüfung</h2>

<h3>1. Prüfung nach Aktenlage</h3>

<p>Die AED fordert Unterlagen per Post oder elektronisch an. Kein Besuch vor Ort. Die häufigste und leichteste Form.</p>

<h3>2. Prüfung vor Ort</h3>

<p>Ein Bediensteter kommt in Ihre Räume, um Bücher, Rechnungen und Ausgabenbelege zu prüfen. Angekündigt per Brief — das MwSt.-Gesetz sieht keine Ankündigungsfrist vor, die AED vereinbart den Termin im Einzelfall.</p>

<h3>3. Unangekündigte Prüfung</h3>

<p>Selten, schwerem Betrugsverdacht vorbehalten. Der Bedienstete erscheint ohne Vorankündigung. Sie können die Anwesenheit Ihres Treuhänders verlangen.</p>

<h2>Unterlagen, die die AED anfordern kann</h2>

<ul>
    <li><strong>Alle ausgestellten und erhaltenen Rechnungen</strong> des geprüften Zeitraums, mit den Pflichtangaben nach Art. 63 LIVA</li>
    <li><strong>Die FAIA-Datei</strong> im Format 2.01 — für die dazu verpflichteten Steuerpflichtigen, siehe unten</li>
    <li><strong>Das Einnahmen-Ausgaben-Buch</strong> oder die vollständige Buchhaltung je nach Regime</li>
    <li><strong>Die eingereichten MwSt.-Erklärungen</strong>, periodische und jährliche</li>
    <li><strong>Die geschäftlichen Kontoauszüge</strong> des Zeitraums</li>
    <li><strong>Die Belege der abgezogenen Ausgaben</strong> (Spesen, Lieferantenrechnungen)</li>
    <li><strong>Wesentliche Verträge</strong> mit Kunden und Lieferanten</li>
    <li><strong>Den Nachweis des Reverse Charge</strong> beim innergemeinschaftlichen B2B: über VIES geprüfte MwSt.-Nummern (Art. 17 LIVA, Art. 196 der Richtlinie)</li>
</ul>

<h2>FAIA: der kritische Punkt — aber nicht für jeden</h2>

<p>Die <strong>FAIA</strong> ist eine strukturierte XML-Datei, abgeleitet vom SAF-T-Standard, die den Unternehmenskopf, die Ausgangsrechnungen des Zeitraums, die zugehörigen Buchungen und die Kontrollsummen bündelt. Das geltende Schema ist die Version <strong>2.01</strong>, deren letzte von der AED veröffentlichte Aktualisierung aus dem Juli 2020 stammt.</p>

<div class="bg-blue-50 border-l-4 border-blue-500 p-4 my-6">
    <p class="font-semibold">Vier kumulative Bedingungen</p>
    <p>Die FAIA betrifft nicht alle Steuerpflichtigen. Nach der FAQ der AED setzt die Pflicht voraus, <strong>gleichzeitig</strong>: dem normierten Kontenplan (PCN) zu unterliegen, keine vereinfachte Regelung in Anspruch zu nehmen, über 112 000 € Umsatz zu erzielen und etwa <strong>500 Buchungstransaktionen</strong> jährlich zu überschreiten. Eine Transaktion ist eine vollständige Buchungskette, keine Rechnung. Fehlt eine einzige Bedingung, sind Sie nicht betroffen — siehe unseren <a href="/de/blog/faia-luxembourg-fichier-audit-informatise-guide">vollständigen FAIA-Leitfaden</a>.</p>
</div>

<p>Sind Sie verpflichtet und fakturieren mit einer Tabellenkalkulation, müssen Sie die Datei von Hand rekonstruieren: mehrere Arbeitstage und ein hohes Fehlerrisiko.</p>

<h2>Wie eine Prüfung vor Ort abläuft</h2>

<ol>
    <li><strong>Schriftliche Ankündigung</strong> der AED, meist per Einschreiben</li>
    <li><strong>Eintreffen der Bediensteten</strong> mit Prüfungsauftrag</li>
    <li><strong>Anforderung der Unterlagen</strong> nach vorbereiteter Liste</li>
    <li><strong>Prüfung der Buchungen</strong> und Abgleich mit den MwSt.-Erklärungen</li>
    <li><strong>Gespräch</strong> über ungewöhnliche Vorgänge oder festgestellte Abweichungen</li>
    <li><strong>Mitteilung der Feststellungen</strong> per Brief</li>
</ol>

<h2>Die 5 teuersten Fehler</h2>

<ol>
    <li><strong>Nicht fortlaufende Rechnungsnummerierung</strong> (Art. 63 LIVA, Ziffer 3°): Lücken oder Dubletten begründen die Vermutung nicht erklärter Umsätze</li>
    <li><strong>Fehlende Pflichtangaben</strong> auf den Rechnungen (Art. 63 LIVA)</li>
    <li><strong>Nicht dokumentierter innergemeinschaftlicher Reverse Charge</strong>: ohne VIES-Prüfung der Kundennummer kann die AED den Umsatz als in Luxemburg steuerpflichtig umqualifizieren</li>
    <li><strong>Fehlende oder unleserliche Ausgabenbelege</strong></li>
    <li><strong>Unstimmigkeiten zwischen MwSt.-Erklärungen und Rechnungen</strong>: schon eine kleine Abweichung löst eine vertiefte Prüfung aus</li>
</ol>

<h2>Welche Sanktionen bei Fehlern?</h2>

<ul>
    <li><strong>Formale Verstöße</strong> (fehlende Angabe, FAIA nicht geliefert, verspätete Erklärung): Verwaltungsbußgeld von <strong>250 € bis 10 000 € je Verstoß</strong> (Art. 77 LIVA)</li>
    <li><strong>Verstoß, der dem Fiskus Einnahmen entzogen hat</strong>: Geldbuße von <strong>10 % bis 50 % der betroffenen MwSt.</strong> — anteilig, also ohne Obergrenze, und bei einem größeren Streit weit schwerer als die vorige</li>
    <li><strong>Weigerung, Rechnungen und Buchhaltungsunterlagen vorzulegen</strong>: bis zu <strong>25 000 € pro Verzugstag</strong>, nach Verwarnung</li>
    <li><strong>Schwere Steuerhinterziehung oder Steuerbetrug</strong>: Geldstrafe von 25 000 € bis zum Zehnfachen der MwSt., Freiheitsstrafe von einem Monat bis fünf Jahren, Aberkennung der bürgerlichen Rechte für 5 bis 10 Jahre (Art. 80 LIVA)</li>
</ul>

<h2>Die Prüfung in 5 Schritten vorbereiten</h2>

<ol>
    <li><strong>Die FAIA vorbereiten</strong> für den verlangten Zeitraum, sofern Sie verpflichtet sind</li>
    <li><strong>Alle ausgestellten Rechnungen exportieren</strong>, idealerweise als PDF/A</li>
    <li><strong>Die Stimmigkeit prüfen</strong> mit den eingereichten MwSt.-Erklärungen</li>
    <li><strong>Pro Monat eine Akte bilden</strong>: Rechnungen, Kontoauszüge, zugehörige Erklärung</li>
    <li><strong>Ihren Treuhänder informieren</strong> und um Anwesenheit während der Prüfung bitten</li>
</ol>

<h2>Die Feststellungen anfechten</h2>

<p>Sind Sie nicht einverstanden, ist das Verfahren geregelt:</p>

<ol>
    <li><strong>Schriftlicher, begründeter Einspruch</strong> bei der Verwaltung, innerhalb von <strong>drei Monaten</strong> ab der Mitteilung</li>
    <li>Bei Zurückweisung wird <strong>der Direktor der Verwaltung befasst</strong>, und seine Entscheidung tritt an die Stelle der vorherigen</li>
    <li><strong>Klage</strong> vor dem <strong>Bezirksgericht Luxemburg in Zivilsachen</strong> — die MwSt. gehört zur ordentlichen Gerichtsbarkeit, nicht zur Verwaltungsgerichtsbarkeit wie die direkten Steuern. Die Klageschrift ist binnen <strong>drei Monaten</strong> nach der Direktorialentscheidung zuzustellen, <strong>bei sonstigem Ausschluss</strong></li>
</ol>

<div class="bg-blue-50 border-l-4 border-blue-500 p-4 my-6">
    <p class="font-semibold">Das Schweigen der Verwaltung blockiert Sie nicht</p>
    <p>Ergeht binnen <strong>sechs Monaten</strong> nach Ihrem Einspruch keine Entscheidung, dürfen Sie ihn als zurückgewiesen betrachten und unmittelbar Klage erheben. In diesem Fall läuft die Dreimonatsfrist nicht — das Warten führt also nicht zum Ausschluss.</p>
</div>

<h2>Wie faktur.lu Sie vorbereitet</h2>

<ul>
    <li>Automatische fortlaufende Nummerierung nach <strong>Artikel 63 LIVA</strong></li>
    <li>Pflichtangaben auf jeder Rechnung erzeugt</li>
    <li>VIES-Prüfung für innergemeinschaftliche B2B-Umsätze</li>
    <li><strong>FAIA-2.01</strong>-Export für jeden Zeitraum</li>
    <li><strong>PDF/A</strong>-Archivierung mit Integritätsnachweis</li>
    <li>Treuhänderportal mit Lesezugriff, ohne E-Mail-Pingpong</li>
</ul>

<h2>Häufige Fragen</h2>

<h3>Wie lange dauert eine Prüfung?</h3>
<p>Nach Aktenlage: zwei bis vier Wochen, je nachdem, wie schnell Sie die Unterlagen senden. Vor Ort: meist ein bis drei Tage im Unternehmen, danach mehrere Wochen Auswertung bei der AED.</p>

<h3>Kann ich eine Prüfung verweigern?</h3>
<p>Nein. Die Weigerung wird sanktioniert und begründet die Vermutung der Bösgläubigkeit. Sie können jedoch aus berechtigtem Grund eine Verschiebung beantragen und die Anwesenheit Ihres Treuhänders verlangen.</p>

<h3>Muss ich trotzdem zehn Jahre archivieren, wenn die Verjährung fünf Jahre beträgt?</h3>
<p>Ja. Es sind zwei voneinander unabhängige Pflichten: Die zehnjährige Aufbewahrung folgt aus Art. 65 LIVA und Art. 16 Handelsgesetzbuch, nicht aus der Verjährungsfrist.</p>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">Jährlich zu prüfen</p>
    <p>Verfahren, Sanktionen und Fristen können sich ändern. Diese Seite wird regelmäßig aktualisiert — für Ihre persönliche Situation wenden Sie sich an Ihren Treuhänder oder direkt an die <a href="https://pfi.public.lu/" target="_blank" rel="noopener">AED</a>.</p>
</div>

<h2>Offizielle Quellen</h2>

<ul>
    <li><a href="https://pfi.public.lu/dam-assets/pdf/legislation/tva/loi/loi-tva-2023-01-01.pdf" target="_blank" rel="noopener">Koordiniertes MwSt.-Gesetz – Artikel 63, 77, 80, 81</a></li>
    <li><a href="https://logistics.public.lu/fr/formalities-procedures/taxes/VAT-sanctions-remedies.html" target="_blank" rel="noopener">MwSt.-Sanktionen und Rechtsbehelfe</a></li>
    <li><a href="https://pfi.public.lu/dam-assets/backup/FAIA/FAIA/FAIA-FAQ.pdf" target="_blank" rel="noopener">AED – FAIA-FAQ (Anwendungsbereich)</a></li>
    <li><a href="https://guichet.public.lu/fr/entreprises/gestion-juridique-comptabilite/comptable/enregistrement/obligations-comptables.html" target="_blank" rel="noopener">Guichet.lu – Buchführungspflichten</a></li>
</ul>

<p class="text-sm text-slate-500 mt-6"><em>Artikel aktualisiert am 31. Juli 2026.</em></p>

<h2>Weiterführend</h2>
<ul>
    <li><a href="/de/blog/faia-luxembourg-fichier-audit-informatise-guide">FAIA Luxemburg: alles zur informatisierten Audit-Datei</a></li>
    <li><a href="/de/blog/mentions-obligatoires-facture-luxembourg">Pflichtangaben auf einer luxemburgischen Rechnung</a></li>
    <li><a href="/de/blog/archivage-factures-luxembourg-duree-legale-format">Archivierung von Rechnungen: Dauer und Format</a></li>
</ul>

<div class="mt-8 p-6 bg-primary-50 rounded-2xl border border-primary-200">
    <h3 class="text-lg font-bold text-primary-900 mb-2">Bereiten Sie Ihre nächste Prüfung schon heute vor</h3>
    <p class="text-primary-800 mb-4">Automatische Nummerierung, FAIA 2.01 mit einem Klick, konforme Angaben, PDF/A-Archivierung. Die Prüfung wird zur Formsache.</p>
    <a href="/de/register" class="inline-block bg-primary-500 hover:bg-primary-600 text-white font-semibold px-6 py-3 rounded-xl">Kostenlos starten</a>
</div>
HTML;
    }

    private function en(): string
    {
        return <<<'HTML'
<p class="lead">Receiving an audit notice from the <strong>Registration Duties, Estates and VAT Authority (AED)</strong> unsettles every freelancer and SME owner in Luxembourg. Yet with proper preparation an audit takes a few hours. Here is how to get ready — and one limitation rule almost everyone gets wrong.</p>

<h2>Who gets audited by the AED?</h2>

<p>Any VAT-registered business in Luxembourg can be audited. In practice the administration targets:</p>

<ul>
    <li><strong>Fast-growing businesses</strong> whose turnover fluctuates unusually</li>
    <li><strong>Risk sectors</strong> identified by the AED (retail, hospitality, construction, consulting)</li>
    <li><strong>Atypical VAT returns</strong> (recurring VAT credit, significant intra-EU transactions)</li>
    <li><strong>Random audits</strong> based on statistical criteria</li>
</ul>

<h2>How far back can the AED go?</h2>

<p>This is the most misunderstood point of the subject, and the error is everywhere — including, until recently, on this page. <strong>Article 81 of the VAT law</strong> is unambiguous:</p>

<blockquote class="border-l-4 border-slate-300 pl-4 italic text-slate-600 my-6">
    <p>"The Treasury's action for payment of the tax and of fines is time-barred after <strong>five years</strong> from 31 December of the year in which the amount to be collected became due."</p>
</blockquote>

<p>For <strong>VAT</strong>, the limitation period is therefore <strong>five years</strong>. The phrase "ten years" appears in the entire VAT law only for the <em>retention</em> of documents — never for limitation.</p>

<div class="bg-red-50 border-l-4 border-red-500 p-4 my-6">
    <p class="font-semibold text-red-800">⚠️ Do not confuse VAT with direct taxes</p>
    <p class="text-red-700">The <strong>ten-year</strong> limitation for non-declaration or inaccurate declaration does exist — but for <strong>direct taxes</strong> (§144 AO and art. 10 of the law of 27 November 1933), administered by the ACD. It does <strong>not</strong> apply to VAT, which falls under the AED. Believing otherwise can lead you to accept a reassessment covering years that are already time-barred.</p>
</div>

<p>Two caveats nonetheless. The limitation can be <strong>interrupted</strong> (art. 2244 ff. of the Civil Code, or a waiver by the taxable person): a fresh period then runs, completed at the end of the fourth year following the last interrupting act. And it runs from <strong>31 December</strong> of the year the amount fell due, not from the date of the transaction.</p>

<p>Finally, limitation and archiving are two different things: you must keep your invoices for <strong>ten years</strong> (art. 65 LIVA and art. 16 of the Commercial Code), regardless of the five-year limitation.</p>

<h2>The 3 types of AED audit</h2>

<h3>1. Desk audit</h3>

<p>The AED asks you to send documents by post or electronically. No site visit. The most common and lightest form.</p>

<h3>2. On-site audit</h3>

<p>An officer visits your premises to check your books, invoices and expense receipts. Announced by letter — the VAT law sets no notice period, the AED arranges the appointment case by case.</p>

<h3>3. Unannounced audit</h3>

<p>Rare, reserved for suspicion of serious fraud. The officer arrives without notice. You may ask for your accountant to be present.</p>

<h2>Documents the AED may request</h2>

<ul>
    <li><strong>Every invoice issued and received</strong> in the audited period, with the mandatory details of art. 63 LIVA</li>
    <li><strong>The FAIA file</strong> in format 2.01 — for taxable persons required to produce it, see below</li>
    <li><strong>The receipts and expenses book</strong> or full accounts depending on your regime</li>
    <li><strong>The VAT returns</strong> filed, periodic and annual</li>
    <li><strong>Business bank statements</strong> for the period</li>
    <li><strong>Supporting documents for deducted expenses</strong> (expense claims, supplier invoices)</li>
    <li><strong>Significant contracts</strong> with customers and suppliers</li>
    <li><strong>Evidence of reverse charge</strong> for intra-EU B2B: VAT numbers validated through VIES (art. 17 LIVA, art. 196 of the Directive)</li>
</ul>

<h2>FAIA: the critical point — but not for everyone</h2>

<p>The <strong>FAIA</strong> is a structured XML file, derived from the SAF-T standard, gathering the company header, the period's sales invoices, the related accounting entries and the control totals. The current schema is version <strong>2.01</strong>, whose last update published by the AED dates from July 2020.</p>

<div class="bg-blue-50 border-l-4 border-blue-500 p-4 my-6">
    <p class="font-semibold">Four cumulative conditions</p>
    <p>The FAIA does not concern every taxable person. According to the AED's FAQ, the obligation requires meeting <strong>at the same time</strong>: being subject to the standard chart of accounts (PCN), not benefiting from a simplified regime, exceeding €112,000 in turnover, and exceeding roughly <strong>500 accounting transactions</strong> a year. A transaction is an entire posting chain, not an invoice. If a single condition is missing, you are not covered — see our <a href="/en/blog/faia-luxembourg-fichier-audit-informatise-guide">complete FAIA guide</a>.</p>
</div>

<p>If you are covered and invoice from a spreadsheet, you will have to rebuild the file by hand: several days of work and a high risk of error.</p>

<h2>How an on-site audit unfolds</h2>

<ol>
    <li><strong>Written notice</strong> from the AED, usually by registered post</li>
    <li><strong>Arrival of the officers</strong> with an audit warrant</li>
    <li><strong>Request for documents</strong> from a pre-established list</li>
    <li><strong>Verification of entries</strong> and cross-checking against the VAT returns</li>
    <li><strong>Interview</strong> on non-standard transactions or detected discrepancies</li>
    <li><strong>Notification of conclusions</strong> by letter</li>
</ol>

<h2>The 5 costly mistakes</h2>

<ol>
    <li><strong>Non-sequential invoice numbering</strong> (art. 63 LIVA, point 3°): gaps or duplicates raise a presumption of undeclared turnover</li>
    <li><strong>Missing mandatory details</strong> on invoices (art. 63 LIVA)</li>
    <li><strong>Undocumented intra-EU reverse charge</strong>: without VIES validation of the customer's number, the AED can reclassify the transaction as taxable in Luxembourg</li>
    <li><strong>Missing or illegible expense receipts</strong></li>
    <li><strong>Inconsistencies between VAT returns and invoices</strong>: even a small gap triggers a deeper review</li>
</ol>

<h2>What penalties apply?</h2>

<ul>
    <li><strong>Formal breaches</strong> (missing wording, FAIA not supplied, late return): administrative fine of <strong>€250 to €10,000 per infringement</strong> (art. 77 LIVA)</li>
    <li><strong>Breach that deprived the Treasury of revenue</strong>: fine of <strong>10% to 50% of the VAT at stake</strong> — proportional, hence uncapped, and far heavier than the previous one on a significant dispute</li>
    <li><strong>Refusal to produce invoices and accounting records</strong>: up to <strong>€25,000 per day of delay</strong>, after a warning</li>
    <li><strong>Aggravated tax fraud or tax swindling</strong>: criminal fine of €25,000 to ten times the VAT amount, imprisonment from one month to five years, loss of civic rights for 5 to 10 years (art. 80 LIVA)</li>
</ul>

<h2>Preparing your audit in 5 steps</h2>

<ol>
    <li><strong>Prepare the FAIA</strong> for the requested period, if you are covered</li>
    <li><strong>Export every invoice issued</strong>, ideally as PDF/A</li>
    <li><strong>Check consistency</strong> with the VAT returns you filed</li>
    <li><strong>Build a file per month</strong>: invoices, bank statements, matching return</li>
    <li><strong>Tell your accountant</strong> and ask them to attend the audit</li>
</ol>

<h2>Challenging the conclusions</h2>

<p>If you disagree, the procedure is defined:</p>

<ol>
    <li><strong>Written, reasoned claim</strong> to the administration, within <strong>three months</strong> of the notification</li>
    <li>If rejected, <strong>the director of the administration is seized</strong> and their decision replaces the earlier one</li>
    <li><strong>Court action</strong> before the <strong>Luxembourg district court sitting in civil matters</strong> — VAT falls under the ordinary courts, not the administrative courts as direct taxes do. The writ must be served within <strong>three months</strong> of the director's decision, <strong>on pain of foreclosure</strong></li>
</ol>

<div class="bg-blue-50 border-l-4 border-blue-500 p-4 my-6">
    <p class="font-semibold">The administration's silence does not block you</p>
    <p>If no decision comes within <strong>six months</strong> of your claim, you may treat it as rejected and go straight to court. In that case the three-month deadline does not run — waiting cannot foreclose you.</p>
</div>

<h2>How faktur.lu prepares you</h2>

<ul>
    <li>Automatic sequential numbering compliant with <strong>article 63 LIVA</strong></li>
    <li>Mandatory details generated on every invoice</li>
    <li>VIES validation for intra-EU B2B transactions</li>
    <li><strong>FAIA 2.01</strong> export for any period</li>
    <li><strong>PDF/A</strong> archiving with an integrity fingerprint</li>
    <li>Read-only accountant portal, no email ping-pong</li>
</ul>

<h2>Frequently asked questions</h2>

<h3>How long does an audit last?</h3>
<p>A desk audit: two to four weeks, depending on how fast you send the documents. An on-site audit: usually one to three days on the premises, then several weeks of analysis at the AED.</p>

<h3>Can I refuse an audit?</h3>
<p>No. Refusal is penalised and raises a presumption of bad faith. You may, however, request a postponement on legitimate grounds and insist on your accountant being present.</p>

<h3>Must I still archive for ten years if the limitation is five?</h3>
<p>Yes. These are two independent obligations: the ten-year retention comes from art. 65 LIVA and art. 16 of the Commercial Code, not from the limitation period.</p>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">To check every year</p>
    <p>Procedures, penalties and deadlines may change. This page is updated regularly, but for your own situation consult your accountant or the <a href="https://pfi.public.lu/" target="_blank" rel="noopener">AED</a> directly.</p>
</div>

<h2>Official sources</h2>

<ul>
    <li><a href="https://pfi.public.lu/dam-assets/pdf/legislation/tva/loi/loi-tva-2023-01-01.pdf" target="_blank" rel="noopener">Coordinated VAT law - articles 63, 77, 80, 81</a></li>
    <li><a href="https://logistics.public.lu/fr/formalities-procedures/taxes/VAT-sanctions-remedies.html" target="_blank" rel="noopener">VAT sanctions and remedies</a></li>
    <li><a href="https://pfi.public.lu/dam-assets/backup/FAIA/FAIA/FAIA-FAQ.pdf" target="_blank" rel="noopener">AED - FAIA FAQ (scope)</a></li>
    <li><a href="https://guichet.public.lu/fr/entreprises/gestion-juridique-comptabilite/comptable/enregistrement/obligations-comptables.html" target="_blank" rel="noopener">Guichet.lu - Accounting obligations</a></li>
</ul>

<p class="text-sm text-slate-500 mt-6"><em>Article updated on 31 July 2026.</em></p>

<h2>Going further</h2>
<ul>
    <li><a href="/en/blog/faia-luxembourg-fichier-audit-informatise-guide">FAIA Luxembourg: everything about the computerised audit file</a></li>
    <li><a href="/en/blog/mentions-obligatoires-facture-luxembourg">Mandatory details on a Luxembourg invoice</a></li>
    <li><a href="/en/blog/archivage-factures-luxembourg-duree-legale-format">Archiving invoices: legal duration and format</a></li>
</ul>

<div class="mt-8 p-6 bg-primary-50 rounded-2xl border border-primary-200">
    <h3 class="text-lg font-bold text-primary-900 mb-2">Prepare your next audit today</h3>
    <p class="text-primary-800 mb-4">Automatic numbering, FAIA 2.01 in one click, compliant wording, PDF/A archiving. The audit becomes a formality.</p>
    <a href="/en/register" class="inline-block bg-primary-500 hover:bg-primary-600 text-white font-semibold px-6 py-3 rounded-xl">Start for free</a>
</div>
HTML;
    }

    private function lb(): string
    {
        return <<<'HTML'
<p class="lead">Eng Kontrollnotifikatioun vun der <strong>Administration de l'enregistrement, des domaines et de la TVA (AED)</strong> beonrouegt all Onofhängegen a KMU-Chef zu Lëtzebuerg. Mat enger gudder Virbereedung ass eng Kontroll awer a puer Stonnen erleedegt. Hei ass, wéi Dir Iech virbereet — an eng Verjärungsreegel, déi bal jiddereen falsch widderhëlt.</p>

<h2>Wien ass vun enger AED-Kontroll betraff?</h2>

<p>All Entreprise, déi zu Lëtzebuerg TVA-registréiert ass, kann kontrolléiert ginn. An der Praxis zielt d'Administratioun op:</p>

<ul>
    <li><strong>Séier wuessend Entreprisen</strong>, deenen hiren Ëmsaz ongewéinlech schwankt</li>
    <li><strong>Risikosecteuren</strong>, déi d'AED identifizéiert (Handel, Restauratioun, Bau, Beroodung)</li>
    <li><strong>Atypesch TVA-Deklaratiounen</strong> (widderkéierend TVA-Kredit, bedeitend innergemeinschaftlech Operatiounen)</li>
    <li><strong>Zoufälleg Kontrollen</strong> no statistesche Critèren</li>
</ul>

<h2>Wéi wäit zréck däerf d'AED goen?</h2>

<p>Dat ass de meescht mëssverstanene Punkt vum Sujet, an de Feeler fënnt een iwwerall — bis viru Kuerzem och op dëser Säit. Den <strong>Artikel 81 vum TVA-Gesetz</strong> ass awer eendeiteg:</p>

<blockquote class="border-l-4 border-slate-300 pl-4 italic text-slate-600 my-6">
    <p>„D'Aktioun vum Trésor op Bezuelung vun der Steier a vun de Geldstrofe verjäert no <strong>fënnef Joer</strong> vum 31. Dezember vum Joer un, an deem de Betrag, deen anzezéien ass, fälleg gouf."</p>
</blockquote>

<p>Bei der <strong>TVA</strong> ass d'Verjärung also <strong>fënnef Joer</strong>. D'Wuert „zéng Joer" kënnt am ganzen TVA-Gesetz nëmme fir d'<em>Opbewahrung</em> vun Dokumenter vir — ni fir d'Verjärung.</p>

<div class="bg-red-50 border-l-4 border-red-500 p-4 my-6">
    <p class="font-semibold text-red-800">⚠️ Verwiesselt TVA net mat direkte Steieren</p>
    <p class="text-red-700">D'Verjärung, déi op <strong>zéng Joer</strong> verlängert gëtt bei Netdeklaratioun oder ongenauer Deklaratioun, existéiert tatsächlech — mä fir déi <strong>direkt Steieren</strong> (§144 AO an Art. 10 vum Gesetz vum 27. November 1933), déi vun der ACD verwalt ginn. Si gëllt <strong>net</strong> fir d'TVA, déi ënner d'AED fält. D'Géigendeel ze gleewe kann dozou féieren, datt Dir eng Nofuerderung fir schonn verjäerte Joren akzeptéiert.</p>
</div>

<p>Zwou Nuancen awer. D'Verjärung kann <strong>ënnerbrach</strong> ginn (Art. 2244 ff. vum Zivilgesetzbuch, oder e Verzicht vum Assujetti): dann leeft eng nei Frist, déi um Enn vum véierte Joer no der leschter Ënnerbriechungshandlung erreecht ass. An d'Frist leeft vum <strong>31. Dezember</strong> vum Joer vun der Fällegkeet un, net vum Datum vun der Operatioun.</p>

<p>Schlussendlech sinn d'Verjärung an d'Archivéierung zwou verschidde Saachen: Dir musst Är Rechnungen <strong>zéng Joer</strong> opbewahren (Art. 65 LIVA an Art. 16 vum Handelsgesetzbuch), onofhängeg vun der Verjärung vu fënnef Joer.</p>

<h2>Déi 3 Zorte vun AED-Kontroll</h2>

<h3>1. Kontroll op Stécker</h3>

<p>D'AED freet Iech, Dokumenter per Post oder elektronesch ze schécken. Kee Besuch op der Plaz. Déi heefegst an déi mannsten opwänneg Form.</p>

<h3>2. Kontroll op der Plaz</h3>

<p>En Agent kënnt an Är Raim, fir Är Bicher, Rechnungen an Ausgabebeleeger ze préiwen. Per Bréif ugekënnegt — d'TVA-Gesetz gesäit keng Virwarnfrist vir, d'AED vereinbart den Rendez-vous vun Fall zu Fall.</p>

<h3>3. Onugekënnegt Kontroll</h3>

<p>Rar, engem schwéiere Bedrugsverdacht virbehalen. Den Agent kënnt ouni Virwarnung. Dir kënnt d'Presenz vun Ärer Fiduciaire verlaangen.</p>

<h2>Dokumenter, déi d'AED ka verlaangen</h2>

<ul>
    <li><strong>All Är ausgestallt an erhalen Rechnungen</strong> vun der kontrolléierter Period, mat de obligatoresche Mentioune vum Art. 63 LIVA</li>
    <li><strong>De FAIA-Fichier</strong> am Format 2.01 — fir déi Assujettien, déi dozou verflicht sinn, kuckt hei ënnen</li>
    <li><strong>D'Recetten- an Ausgabebuch</strong> oder déi komplett Comptabilitéit no Ärem Regime</li>
    <li><strong>Déi ofgi TVA-Deklaratiounen</strong>, periodesch a jäerlech</li>
    <li><strong>D'berufflech Kontosauszich</strong> vun der Period</li>
    <li><strong>D'Beleeger vun den ofgezunnen Ausgaben</strong> (Spesen, Fournisseursrechnungen)</li>
    <li><strong>Déi wesentlech Kontrakter</strong> mat Clienten a Fournisseuren</li>
    <li><strong>De Beweis vun der Autoliquidatioun</strong> beim innergemeinschaftleche B2B: TVA-Nummeren, iwwer VIES validéiert (Art. 17 LIVA, Art. 196 vun der Direktiv)</li>
</ul>

<h2>FAIA: de kriteschen Punkt — mä net fir jiddereen</h2>

<p>De <strong>FAIA</strong> ass e strukturéierten XML-Fichier, ofgeleet vum SAF-T-Standard, deen de Kapp vun der Entreprise, d'Verkafsrechnunge vun der Period, déi zougehéiereg Écritureën an d'Kontrolltotaler sammelt. D'Schema a Kraaft ass d'Versioun <strong>2.01</strong>, deenen hir lescht vun der AED publizéiert Aktualiséierung aus dem Juli 2020 stamt.</p>

<div class="bg-blue-50 border-l-4 border-blue-500 p-4 my-6">
    <p class="font-semibold">Véier kumulativ Bedingungen</p>
    <p>De FAIA betrëfft net all Assujetti. No der FAQ vun der AED setzt d'Flicht viraus, <strong>gläichzäiteg</strong>: dem normaliséierte Comptesplang (PCN) ze ënnerleien, kee vereinfachte Regime a Usproch ze huelen, iwwer 112 000 € Ëmsaz ze realiséieren, an ongeféier <strong>500 comptabel Transaktiounen</strong> pro Joer ze iwwerschreiden. Eng Transaktioun ass eng ganz Comptabiliséierungskette, keng Rechnung. Feelt eng eenzeg Bedingung, sidd Dir net betraff — kuckt eise <a href="/lb/blog/faia-luxembourg-fichier-audit-informatise-guide">komplette FAIA-Guide</a>.</p>
</div>

<p>Wann Dir dozou verflicht sidd an op engem Tabelleblat fakturéiert, musst Dir de Fichier mat der Hand rekonstruéieren: e puer Aarbechtsdeeg an en héije Feelerrisiko.</p>

<h2>Wéi eng Kontroll op der Plaz oflaf</h2>

<ol>
    <li><strong>Schrëftlech Notifikatioun</strong> vun der AED, allgemeng per Aschreiwen</li>
    <li><strong>Ukunft vun den Agenten</strong> mat engem Kontrollopdrag</li>
    <li><strong>Ufro vun den Dokumenter</strong> no enger virbereeter Lëscht</li>
    <li><strong>Iwwerpréiwung vun den Écritureën</strong> an Ofgläich mat den TVA-Deklaratiounen</li>
    <li><strong>Gespréich</strong> iwwer net-standard Operatiounen oder festgestallt Ofwäichungen</li>
    <li><strong>Notifikatioun vun de Schlussfolgerungen</strong> per Bréif</li>
</ol>

<h2>Déi 5 Feeler, déi deier kommen</h2>

<ol>
    <li><strong>Net-sequenziell Rechnungsnummeréierung</strong> (Art. 63 LIVA, Punkt 3°): Lacken oder Duebele gëllen als Vermutung vun net deklaréiertem Ëmsaz</li>
    <li><strong>Feelend obligatoresch Mentiounen</strong> op de Rechnungen (Art. 63 LIVA)</li>
    <li><strong>Net dokumentéiert innergemeinschaftlech Autoliquidatioun</strong>: ouni VIES-Validatioun vun der Clientsnummer kann d'AED d'Operatioun als zu Lëtzebuerg steierflichteg requalifizéieren</li>
    <li><strong>Feelend oder onliesbar Ausgabebeleeger</strong></li>
    <li><strong>Inkohärenzen tëscht TVA-Deklaratiounen a Rechnungen</strong>: schonn eng kleng Ofwäichung léist eng vertéift Iwwerpréiwung aus</li>
</ol>

<h2>Wéi eng Sanktioune bei Feeler?</h2>

<ul>
    <li><strong>Formell Manktemer</strong> (feelend Mentioun, FAIA net geliwwert, verspéidet Deklaratioun): administrativ Geldstrof vun <strong>250 € bis 10 000 € pro Infraktioun</strong> (Art. 77 LIVA)</li>
    <li><strong>Manktem, dat de Staat ëm Recetten bruecht huet</strong>: Geldstrof vun <strong>10 % bis 50 % vun der betraffener TVA</strong> — proportional, also ouni Plafong, a bei engem gréissere Sträit vill méi schwéier wéi déi virescht</li>
    <li><strong>Refus, Rechnungen a comptabel Stécker ze weisen</strong>: bis zu <strong>25 000 € pro Dag Verspéidung</strong>, no engem Avertissement</li>
    <li><strong>Schwéier Steierhannerzéiung oder Steierbedruch</strong>: Geldstrof vun 25 000 € bis zum Zéngfache vum TVA-Betrag, Prisong vun engem Mount bis fënnef Joer, Verloscht vun de biergerleche Rechter fir 5 bis 10 Joer (Art. 80 LIVA)</li>
</ul>

<h2>Seng Kontroll a 5 Schrëtt virbereeden</h2>

<ol>
    <li><strong>De FAIA virbereeden</strong> fir déi gefroten Period, wann Dir dozou verflicht sidd</li>
    <li><strong>All ausgestallt Rechnungen exportéieren</strong>, idealerweis als PDF/A</li>
    <li><strong>D'Kohärenz préiwen</strong> mat den ofgin TVA-Deklaratiounen</li>
    <li><strong>Pro Mount en Dossier bilden</strong>: Rechnungen, Kontosauszich, zougehéiereg Deklaratioun</li>
    <li><strong>Är Fiduciaire informéieren</strong> a si froen, bei der Kontroll present ze sinn</li>
</ol>

<h2>D'Schlussfolgerungen ufechten</h2>

<p>Wann Dir net d'accord sidd, ass d'Prozedur gereegelt:</p>

<ol>
    <li><strong>Schrëftlech, begrënnt Reklamatioun</strong> bei der Administratioun, bannent <strong>dräi Méint</strong> no der Notifikatioun</li>
    <li>Bei Ofleenung gëtt <strong>den Direkter vun der Administratioun befaasst</strong>, a seng Entscheedung trëtt un d'Plaz vun der virechter</li>
    <li><strong>Recours duerch Assignatioun</strong> virum <strong>Bezierksgeriicht Lëtzebuerg a Zivilsaachen</strong> — d'TVA fält ënner d'ordentlech Geriichtsbarkeet, net ënner d'Verwaltungsgeriichtsbarkeet wéi déi direkt Steieren. D'Assignatioun muss bannent <strong>dräi Méint</strong> no der Direktorialentscheedung zougestallt ginn, <strong>ënner Strof vun der Forclusioun</strong></li>
</ol>

<div class="bg-blue-50 border-l-4 border-blue-500 p-4 my-6">
    <p class="font-semibold">D'Stëllschweige vun der Administratioun blockéiert Iech net</p>
    <p>Kënnt bannent <strong>sechs Méint</strong> no Ärer Reklamatioun keng Entscheedung, kënnt Dir se als ofgeleent betruechten an direkt e Recours areechen. An deem Fall leeft d'Dräiméintfrist net — d'Waarde bréngt Iech also net an d'Forclusioun.</p>
</div>

<h2>Wéi faktur.lu Iech virbereet</h2>

<ul>
    <li>Automatesch fortlafend Nummeréierung konform mam <strong>Artikel 63 LIVA</strong></li>
    <li>Obligatoresch Mentiounen op all Rechnung generéiert</li>
    <li>VIES-Validatioun fir innergemeinschaftlech B2B-Operatiounen</li>
    <li><strong>FAIA-2.01</strong>-Export fir all Period</li>
    <li><strong>PDF/A</strong>-Archivéierung mat Integritéitsofdrock</li>
    <li>Fiduciaire-Portal a Lieszougrëff, ouni E-Mail-Pingpong</li>
</ul>

<h2>Heefeg Froen</h2>

<h3>Wéi laang dauert eng Kontroll?</h3>
<p>Eng Kontroll op Stécker: zwou bis véier Wochen, jee nodeem wéi séier Dir d'Dokumenter schéckt. Eng Kontroll op der Plaz: allgemeng een bis dräi Deeg an der Entreprise, duerno e puer Wochen Analys bei der AED.</p>

<h3>Kann ech eng Kontroll refuséieren?</h3>
<p>Neen. De Refus gëtt sanktionéiert an ënnerstellt béise Glawen. Dir kënnt awer aus engem legitime Grond eng Verschiebung froen an d'Presenz vun Ärer Fiduciaire verlaangen.</p>

<h3>Muss ech trotzdem zéng Joer archivéieren, wann d'Verjärung fënnef Joer ass?</h3>
<p>Jo. Dat sinn zwou onofhängeg Flichten: d'Opbewahrung vun zéng Joer kënnt aus dem Art. 65 LIVA an dem Art. 16 vum Handelsgesetzbuch, net aus der Verjärungsfrist.</p>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">All Joer ze préiwen</p>
    <p>Prozeduren, Sanktiounen a Fristen kënnen änneren. Dës Säit gëtt reegelméisseg aktualiséiert — fir Är perséinlech Situatioun frot Är Fiduciaire oder direkt d'<a href="https://pfi.public.lu/" target="_blank" rel="noopener">AED</a>.</p>
</div>

<h2>Offiziell Quellen</h2>

<ul>
    <li><a href="https://pfi.public.lu/dam-assets/pdf/legislation/tva/loi/loi-tva-2023-01-01.pdf" target="_blank" rel="noopener">Koordinéiert TVA-Gesetz – Artikelen 63, 77, 80, 81</a></li>
    <li><a href="https://logistics.public.lu/fr/formalities-procedures/taxes/VAT-sanctions-remedies.html" target="_blank" rel="noopener">TVA-Sanktiounen a Rechtsmëttelen</a></li>
    <li><a href="https://pfi.public.lu/dam-assets/backup/FAIA/FAIA/FAIA-FAQ.pdf" target="_blank" rel="noopener">AED – FAIA-FAQ (Applikatiounsberäich)</a></li>
    <li><a href="https://guichet.public.lu/fr/entreprises/gestion-juridique-comptabilite/comptable/enregistrement/obligations-comptables.html" target="_blank" rel="noopener">Guichet.lu – Comptabel Flichten</a></li>
</ul>

<p class="text-sm text-slate-500 mt-6"><em>Artikel aktualiséiert den 31. Juli 2026.</em></p>

<h2>Fir méi wäit ze goen</h2>
<ul>
    <li><a href="/lb/blog/faia-luxembourg-fichier-audit-informatise-guide">FAIA Lëtzebuerg: alles iwwer den informatiséierten Auditfichier</a></li>
    <li><a href="/lb/blog/mentions-obligatoires-facture-luxembourg">Obligatoresch Mentiounen op enger Lëtzebuerger Rechnung</a></li>
    <li><a href="/lb/blog/archivage-factures-luxembourg-duree-legale-format">Archivéierung vu Rechnungen: gesetzlech Dauer a Format</a></li>
</ul>

<div class="mt-8 p-6 bg-primary-50 rounded-2xl border border-primary-200">
    <h3 class="text-lg font-bold text-primary-900 mb-2">Bereet Är nächst Kontroll haut vir</h3>
    <p class="text-primary-800 mb-4">Automatesch Nummeréierung, FAIA 2.01 mat engem Klick, konform Mentiounen, PDF/A-Archivéierung. D'Kontroll gëtt eng Formalitéit.</p>
    <a href="/lb/register" class="inline-block bg-primary-500 hover:bg-primary-600 text-white font-semibold px-6 py-3 rounded-xl">Gratis ufänken</a>
</div>
HTML;
    }

    private function pt(): string
    {
        return <<<'HTML'
<p class="lead">Receber uma notificação de inspeção da <strong>Administração do registo, dos domínios e do IVA (AED)</strong> inquieta qualquer independente ou dirigente de PME no Luxemburgo. No entanto, com boa preparação, uma inspeção resolve-se em poucas horas. Eis como preparar-se — e uma regra de prescrição que quase toda a gente repete errada.</p>

<h2>Quem é abrangido por uma inspeção da AED?</h2>

<p>Qualquer empresa registada para efeitos de IVA no Luxemburgo pode ser inspecionada. Na prática, a administração visa:</p>

<ul>
    <li><strong>Empresas em crescimento rápido</strong> cujo volume de negócios flutua de forma invulgar</li>
    <li><strong>Setores de risco</strong> identificados pela AED (comércio, restauração, construção, consultoria)</li>
    <li><strong>Declarações de IVA atípicas</strong> (crédito de IVA recorrente, operações intra-UE significativas)</li>
    <li><strong>Inspeções aleatórias</strong> segundo critérios estatísticos</li>
</ul>

<h2>Até que ponto pode a AED recuar?</h2>

<p>É o ponto mais mal compreendido do tema, e o erro está em toda a parte — incluindo, até há pouco, nesta página. O <strong>artigo 81 da lei do IVA</strong> é, porém, inequívoco:</p>

<blockquote class="border-l-4 border-slate-300 pl-4 italic text-slate-600 my-6">
    <p>«A ação do Tesouro para pagamento do imposto e das coimas prescreve em <strong>cinco anos</strong> a contar de 31 de dezembro do ano em que a quantia a cobrar se tornou exigível.»</p>
</blockquote>

<p>Em matéria de <strong>IVA</strong>, a prescrição é portanto de <strong>cinco anos</strong>. A expressão «dez anos» surge em toda a lei do IVA apenas para a <em>conservação</em> de documentos — nunca para a prescrição.</p>

<div class="bg-red-50 border-l-4 border-red-500 p-4 my-6">
    <p class="font-semibold text-red-800">⚠️ Não confunda IVA com impostos diretos</p>
    <p class="text-red-700">A prescrição alargada para <strong>dez anos</strong> em caso de não declaração ou declaração inexata existe de facto — mas para os <strong>impostos diretos</strong> (§144 AO e art. 10 da lei de 27 de novembro de 1933), administrados pela ACD. <strong>Não</strong> se aplica ao IVA, que compete à AED. Acreditar no contrário pode levá-lo a aceitar uma correção relativa a anos já prescritos.</p>
</div>

<p>Duas nuances, ainda assim. A prescrição pode ser <strong>interrompida</strong> (art. 2244.º e seguintes do Código Civil, ou renúncia do sujeito passivo): corre então um novo prazo, completado no fim do quarto ano seguinte ao último ato interruptivo. E corre a partir de <strong>31 de dezembro</strong> do ano de exigibilidade, não da data da operação.</p>

<p>Por fim, prescrição e arquivo são coisas distintas: deve conservar as suas faturas <strong>dez anos</strong> (art. 65 LIVA e art. 16 do Código Comercial), independentemente do prazo de prescrição de cinco anos.</p>

<h2>Os 3 tipos de inspeção da AED</h2>

<h3>1. Inspeção documental</h3>

<p>A AED pede-lhe que envie documentos por correio ou por via eletrónica. Sem visita presencial. É o tipo mais frequente e menos pesado.</p>

<h3>2. Inspeção no local</h3>

<p>Um agente desloca-se às suas instalações para verificar livros, faturas e comprovativos de despesas. Anunciada por carta — a lei do IVA não fixa prazo de pré-aviso, a AED marca o encontro caso a caso.</p>

<h3>3. Inspeção inopinada</h3>

<p>Rara, reservada a suspeitas de fraude grave. O agente apresenta-se sem aviso. Pode pedir a presença do seu contabilista.</p>

<h2>Documentos que a AED pode pedir</h2>

<ul>
    <li><strong>Todas as faturas emitidas e recebidas</strong> no período inspecionado, com as menções obrigatórias do art. 63 LIVA</li>
    <li><strong>O ficheiro FAIA</strong> no formato 2.01 — para os sujeitos passivos obrigados a produzi-lo, ver abaixo</li>
    <li><strong>O livro de receitas e despesas</strong> ou a contabilidade completa consoante o regime</li>
    <li><strong>As declarações de IVA</strong> entregues, periódicas e anuais</li>
    <li><strong>Os extratos bancários</strong> profissionais do período</li>
    <li><strong>Os comprovativos das despesas deduzidas</strong> (notas de despesa, faturas de fornecedores)</li>
    <li><strong>Os contratos</strong> significativos com clientes e fornecedores</li>
    <li><strong>A prova da autoliquidação</strong> no B2B intra-UE: números de IVA validados no VIES (art. 17 LIVA, art. 196.º da diretiva)</li>
</ul>

<h2>FAIA: o ponto crítico — mas não para todos</h2>

<p>O <strong>FAIA</strong> é um ficheiro XML estruturado, derivado do padrão SAF-T, que reúne o cabeçalho da empresa, as faturas de venda do período, os lançamentos associados e os totais de controlo. O esquema em vigor é a versão <strong>2.01</strong>, cuja última atualização publicada pela AED data de julho de 2020.</p>

<div class="bg-blue-50 border-l-4 border-blue-500 p-4 my-6">
    <p class="font-semibold">Quatro condições cumulativas</p>
    <p>O FAIA não diz respeito a todos os sujeitos passivos. Segundo as FAQ da AED, a obrigação pressupõe reunir <strong>ao mesmo tempo</strong>: estar sujeito ao plano de contas normalizado (PCN), não beneficiar de um regime simplificado, ultrapassar 112 000 € de volume de negócios e ultrapassar cerca de <strong>500 transações contabilísticas</strong> por ano. Uma transação é uma cadeia inteira de lançamentos, não uma fatura. Se faltar uma só condição, não está abrangido — ver o nosso <a href="/pt/blog/faia-luxembourg-fichier-audit-informatise-guide">guia completo do FAIA</a>.</p>
</div>

<p>Se estiver abrangido e faturar numa folha de cálculo, terá de reconstituir o ficheiro à mão: vários dias de trabalho e um risco elevado de erro.</p>

<h2>Como decorre uma inspeção no local</h2>

<ol>
    <li><strong>Notificação escrita</strong> da AED, em geral por carta registada</li>
    <li><strong>Chegada dos agentes</strong> munidos de uma credencial de inspeção</li>
    <li><strong>Pedido dos documentos</strong> segundo uma lista pré-estabelecida</li>
    <li><strong>Verificação dos lançamentos</strong> e cruzamento com as declarações de IVA</li>
    <li><strong>Entrevista</strong> sobre operações fora do padrão ou desvios detetados</li>
    <li><strong>Notificação das conclusões</strong> por carta</li>
</ol>

<h2>Os 5 erros que saem caro</h2>

<ol>
    <li><strong>Numeração não sequencial das faturas</strong> (art. 63 LIVA, ponto 3°): falhas ou duplicados valem presunção de omissão de volume de negócios</li>
    <li><strong>Menções obrigatórias em falta</strong> nas faturas (art. 63 LIVA)</li>
    <li><strong>Autoliquidação intra-UE não documentada</strong>: sem validação VIES do número do cliente, a AED pode requalificar a operação como tributável no Luxemburgo</li>
    <li><strong>Comprovativos de despesas em falta</strong> ou ilegíveis</li>
    <li><strong>Incoerências entre declarações de IVA e faturas</strong>: mesmo um desvio mínimo desencadeia uma verificação aprofundada</li>
</ol>

<h2>Que sanções em caso de erro?</h2>

<ul>
    <li><strong>Incumprimentos formais</strong> (menção em falta, FAIA não fornecido, declaração tardia): coima administrativa de <strong>250 € a 10 000 € por infração</strong> (art. 77 LIVA)</li>
    <li><strong>Incumprimento que privou o Estado de receita</strong>: coima de <strong>10 % a 50 % do IVA em causa</strong> — proporcional, logo sem limite máximo, e bem mais pesada do que a anterior num litígio significativo</li>
    <li><strong>Recusa de apresentar faturas e documentos contabilísticos</strong>: até <strong>25 000 € por dia de atraso</strong>, após advertência</li>
    <li><strong>Fraude fiscal agravada ou burla fiscal</strong>: multa penal de 25 000 € a dez vezes o montante do IVA, prisão de um mês a cinco anos, privação dos direitos cívicos de 5 a 10 anos (art. 80 LIVA)</li>
</ul>

<h2>Preparar a inspeção em 5 etapas</h2>

<ol>
    <li><strong>Preparar o FAIA</strong> para o período pedido, se estiver abrangido</li>
    <li><strong>Exportar todas as faturas emitidas</strong>, idealmente em PDF/A</li>
    <li><strong>Verificar a coerência</strong> com as declarações de IVA entregues</li>
    <li><strong>Constituir um dossiê por mês</strong>: faturas, extratos bancários, declaração correspondente</li>
    <li><strong>Avisar o seu contabilista</strong> e pedir-lhe que esteja presente na inspeção</li>
</ol>

<h2>Contestar as conclusões</h2>

<p>Se não concordar, o procedimento está definido:</p>

<ol>
    <li><strong>Reclamação escrita e fundamentada</strong> junto da administração, no prazo de <strong>três meses</strong> a contar da notificação</li>
    <li>Em caso de indeferimento, <strong>o diretor da administração é chamado a decidir</strong> e a sua decisão substitui a anterior</li>
    <li><strong>Recurso por citação</strong> perante o <strong>tribunal de comarca do Luxemburgo, em matéria cível</strong> — o IVA compete à jurisdição judicial, e não à administrativa como os impostos diretos. A citação deve ser notificada no prazo de <strong>três meses</strong> a contar da decisão do diretor, <strong>sob pena de caducidade</strong></li>
</ol>

<div class="bg-blue-50 border-l-4 border-blue-500 p-4 my-6">
    <p class="font-semibold">O silêncio da administração não o bloqueia</p>
    <p>Se não houver decisão nos <strong>seis meses</strong> seguintes à sua reclamação, pode considerá-la indeferida e interpor recurso diretamente. Nesse caso, o prazo de três meses não corre — a espera não o faz caducar.</p>
</div>

<h2>Como o faktur.lu o prepara</h2>

<ul>
    <li>Numeração sequencial automática conforme ao <strong>artigo 63 LIVA</strong></li>
    <li>Menções obrigatórias geradas em cada fatura</li>
    <li>Validação VIES para as operações B2B intra-UE</li>
    <li>Exportação <strong>FAIA 2.01</strong> em qualquer período</li>
    <li>Arquivo <strong>PDF/A</strong> com impressão de integridade</li>
    <li>Portal do contabilista em modo de leitura, sem trocas de e-mail</li>
</ul>

<h2>Perguntas frequentes</h2>

<h3>Quanto tempo dura uma inspeção?</h3>
<p>Uma inspeção documental: duas a quatro semanas, consoante a rapidez com que enviar os documentos. Uma inspeção no local: geralmente um a três dias na empresa, depois várias semanas de análise na AED.</p>

<h3>Posso recusar uma inspeção?</h3>
<p>Não. A recusa é sancionada e presume má-fé. Pode, contudo, pedir adiamento por motivo legítimo e exigir a presença do seu contabilista.</p>

<h3>Tenho mesmo de arquivar dez anos se a prescrição é de cinco?</h3>
<p>Sim. São duas obrigações independentes: a conservação de dez anos decorre do art. 65 LIVA e do art. 16 do Código Comercial, não do prazo de prescrição.</p>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">A verificar todos os anos</p>
    <p>Procedimentos, sanções e prazos podem evoluir. Esta página é atualizada regularmente, mas para a sua situação pessoal, consulte o seu contabilista ou diretamente a <a href="https://pfi.public.lu/" target="_blank" rel="noopener">AED</a>.</p>
</div>

<h2>Fontes oficiais</h2>

<ul>
    <li><a href="https://pfi.public.lu/dam-assets/pdf/legislation/tva/loi/loi-tva-2023-01-01.pdf" target="_blank" rel="noopener">Lei do IVA coordenada - artigos 63, 77, 80, 81</a></li>
    <li><a href="https://logistics.public.lu/fr/formalities-procedures/taxes/VAT-sanctions-remedies.html" target="_blank" rel="noopener">Sanções de IVA e vias de recurso</a></li>
    <li><a href="https://pfi.public.lu/dam-assets/backup/FAIA/FAIA/FAIA-FAQ.pdf" target="_blank" rel="noopener">AED - FAQ FAIA (âmbito de aplicação)</a></li>
    <li><a href="https://guichet.public.lu/fr/entreprises/gestion-juridique-comptabilite/comptable/enregistrement/obligations-comptables.html" target="_blank" rel="noopener">Guichet.lu - Obrigações contabilísticas</a></li>
</ul>

<p class="text-sm text-slate-500 mt-6"><em>Artigo atualizado a 31 de julho de 2026.</em></p>

<h2>Para ir mais longe</h2>
<ul>
    <li><a href="/pt/blog/faia-luxembourg-fichier-audit-informatise-guide">FAIA Luxemburgo: tudo sobre o ficheiro de auditoria informatizado</a></li>
    <li><a href="/pt/blog/mentions-obligatoires-facture-luxembourg">Menções obrigatórias numa fatura luxemburguesa</a></li>
    <li><a href="/pt/blog/archivage-factures-luxembourg-duree-legale-format">Arquivo das faturas: prazo legal e formato</a></li>
</ul>

<div class="mt-8 p-6 bg-primary-50 rounded-2xl border border-primary-200">
    <h3 class="text-lg font-bold text-primary-900 mb-2">Prepare já a sua próxima inspeção</h3>
    <p class="text-primary-800 mb-4">Numeração automática, FAIA 2.01 num clique, menções conformes, arquivo PDF/A. A inspeção torna-se uma formalidade.</p>
    <a href="/pt/register" class="inline-block bg-primary-500 hover:bg-primary-600 text-white font-semibold px-6 py-3 rounded-xl">Começar gratuitamente</a>
</div>
HTML;
    }
};
