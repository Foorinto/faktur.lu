<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * FUSION des deux articles « taux de TVA » du blog. Troisième et dernier
 * doublon repéré.
 *
 * SURVIVANT : tva-luxembourg-taux-calcul-obligations
 *   17 liens internes depuis 4 articles, publié le 4 avril 2026.
 * RETIRÉ    : tva-luxembourg-2026-quatre-taux-17-14-8-3-expliques
 *   2 liens internes depuis 2 articles.
 *
 * Les deux articles étaient COMPLÉMENTAIRES plutôt que redondants : le
 * survivant couvre les taux, le calcul, les obligations déclaratives,
 * l'intracommunautaire et la déduction ; le retiré apportait les cas
 * pratiques (restauration, hôtellerie, e-books, rénovation), les
 * conséquences d'une erreur de taux et une FAQ. La fusion reprend donc
 * la structure du survivant et y greffe ce que l'autre avait de propre.
 *
 * DEUX CORRECTIONS DE COHÉRENCE avec des articles déjà déployés :
 *
 * 1. OBLIGATIONS DÉCLARATIVES. Le tableau présentait les trois
 *    périodicités comme exclusives — même erreur que celle corrigée dans
 *    l'article freelance : la déclaration annuelle s'AJOUTE aux
 *    déclarations périodiques, elle ne les remplace pas. Et l'échéance
 *    annoncée pour la déclaration annuelle était le 1er mars ; guichet.lu
 *    indique le 1er mai pour le régime normal. Le 1er mars concerne la
 *    déclaration annuelle SIMPLIFIÉE des assujettis en franchise
 *    réalisant des prestations intracommunautaires — un régime différent,
 *    traité dans l'article consacré à la franchise.
 *
 * 2. SEUIL B2C. « Au-delà de 10 000 € de ventes annuelles vers d'autres
 *    pays UE » ne précisait pas que ce seuil est COMMUN aux ventes à
 *    distance de biens et aux services électroniques, télécoms et
 *    audiovisuels, tous États membres confondus — correction déjà
 *    apportée à l'article TVA intracommunautaire.
 *
 * OMISSION MAJEURE COMBLÉE — L'AUTORISATION PRÉALABLE POUR LA RÉNOVATION.
 * Le doublon décrivait correctement le taux super-réduit de 3 % pour les
 * travaux de rénovation d'un logement affecté à l'habitation principale,
 * mais omettait la condition qui décide de tout. L'article 65bis de la
 * loi TVA est explicite :
 *   « L'assujetti qui effectue des travaux de création et de rénovation
 *     visés à l'annexe B, point 22 [...] doit demander auprès de
 *     l'administration l'autorisation pour l'application du taux
 *     super-réduit à ces travaux. Cette demande doit être introduite
 *     [...] AVANT la réalisation des travaux. »
 * Faire les travaux puis demander le 3 % ne fonctionne pas. Sur un
 * chantier de rénovation, l'écart entre 3 % et 17 % se compte en
 * milliers d'euros. L'annexe B point 22 renvoie par ailleurs à un
 * règlement grand-ducal pour les limites et conditions — le plafond et
 * l'ancienneté du bâtiment n'étant donc pas dans la loi elle-même, le
 * texte le dit désormais plutôt que de les présenter comme légaux.
 *
 * SANCTIONS COMPLÉTÉES, comme dans les autres articles : ajout de
 * l'amende proportionnelle de 10 % à 50 % de la TVA en cause.
 *
 * FAITS VÉRIFIÉS et confirmés exacts : quatre taux 17 / 14 / 8 / 3 % ;
 * restauration à 3 % avec les boissons alcoolisées à 17 % sur la même
 * addition ; hébergement hôtelier à 3 % quel que soit le classement ;
 * amendes de 250 à 10 000 EUR (art. 77) et sanctions pénales (art. 80).
 *
 * Mécanique de fusion identique aux deux précédentes : doublon archivé,
 * cinq redirections 301 dans routes/web.php, liens internes réécrits.
 *
 * ⚠️ La version luxembourgeoise mérite une relecture par un locuteur natif.
 */
return new class extends Migration
{
    private const RETIRED = 'tva-luxembourg-2026-quatre-taux-17-14-8-3-expliques';
    private const CANONICAL = 'tva-luxembourg-taux-calcul-obligations';

    private const SLUG_MAP = [
        'fr' => ['tva-luxembourg-2026-quatre-taux-17-14-8-3-expliques', 'tva-luxembourg-taux-calcul-obligations'],
        'de' => ['luxemburgische-mwst-2026-vier-saetze-17-14-8-3-erklaert', 'mwst-luxemburg-saetze-berechnung-pflichten'],
        'en' => ['luxembourg-vat-2026-four-rates-17-14-8-3-explained', 'vat-luxembourg-rates-calculation-obligations'],
        'lb' => ['letzebuerger-tva-2026-veier-satz-17-14-8-3-erklaert', 'tva-letzebuerg-tariffer-berechnung-obligatiounen'],
        'pt' => ['iva-luxemburgo-2026-quatro-taxas-17-14-8-3-explicadas', 'iva-no-luxemburgo-taxas-calculo-e-obrigacoes-para-as-empresas'],
    ];

    /** [H2 obligations déclaratives, H3 B2C, H2 conclusion] par langue. */
    private const ANCHORS = [
        'fr' => ['Les obligations déclaratives', 'Ventes à des particuliers UE (B2C)', 'Conclusion'],
        'de' => ['Erklärungspflichten', 'Verkäufe an EU-Privatpersonen (B2C)', 'Fazit'],
        'en' => ['Declaration Obligations', 'Sales to EU Individuals (B2C)', 'Conclusion'],
        'lb' => ['Erklärungspflichten', 'Verkaf un EU-Privatpersounen (B2C)', 'Conclusioun'],
        'pt' => ['As obrigações declarativas', 'Vendas a particulares da UE (B2C)', 'Conclusão'],
    ];

    public function up(): void
    {
        foreach (self::ANCHORS as $locale => [$declH2, $b2cH3, $conclH2]) {
            $post = DB::table('blog_posts')
                ->where('translation_key', self::CANONICAL)
                ->where('locale', $locale)
                ->first(['id', 'content']);

            if (! $post) {
                continue;
            }

            $c = $post->content;

            // 1. Remplacer la section « obligations déclaratives » (de son H2 au H2 suivant).
            $c = preg_replace(
                '~<h2>' . preg_quote($declH2, '~') . '</h2>.*?(?=<h2>)~s',
                $this->declarations($locale),
                $c,
                1
            );

            // 2. Remplacer la sous-section B2C (de son H3 au H2 ou H3 suivant).
            $c = preg_replace(
                '~<h3>' . preg_quote($b2cH3, '~') . '</h3>.*?(?=<h[23]>)~s',
                $this->b2c($locale),
                $c,
                1
            );

            // 3. Insérer les nouvelles sections avant la conclusion.
            $anchor = '<h2>' . $conclH2 . '</h2>';
            if (str_contains($c, $anchor)) {
                $c = str_replace($anchor, $this->addition($locale) . $anchor, $c);
            }

            DB::table('blog_posts')->where('id', $post->id)->update([
                'content' => $c,
                'updated_at' => now(),
            ]);
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

    private function declarations(string $l): string
    {
        return match ($l) {
            'fr' => <<<'HTML'
<h2>Les obligations déclaratives</h2>

<p>La périodicité dépend de votre chiffre d'affaires hors taxes. <strong>C'est l'AED qui détermine votre régime</strong> — vous ne le choisissez pas.</p>

<ul>
    <li><strong>CA inférieur à 112 000 €</strong> : déclaration annuelle uniquement</li>
    <li><strong>CA entre 112 000 € et 620 000 €</strong> : déclarations trimestrielles <strong>et</strong> déclaration annuelle</li>
    <li><strong>CA supérieur à 620 000 €</strong> : déclarations mensuelles <strong>et</strong> déclaration annuelle</li>
</ul>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold text-amber-800">⚠️ La déclaration annuelle ne remplace pas les déclarations périodiques</p>
    <p class="text-amber-700">Si vous déposez des déclarations trimestrielles ou mensuelles, vous devez <strong>en plus</strong> déposer une déclaration annuelle récapitulative, avant le <strong>1<sup>er</sup> mai</strong> de l'année suivante. L'oublier déclenche des pénalités de dépôt tardif, alors même que vous avez payé toute votre TVA en cours d'année.</p>
</div>

<p>Les déclarations périodiques se déposent avant le 15 du mois suivant la période concernée, en ligne via <strong>eCDF</strong>. Le paiement accompagne la déclaration ; en cas de crédit de TVA, un remboursement peut être demandé.</p>

HTML,
            'de' => <<<'HTML'
<h2>Erklärungspflichten</h2>

<p>Die Periodizität richtet sich nach Ihrem Nettoumsatz. <strong>Die AED bestimmt Ihr Regime</strong> — Sie wählen es nicht selbst.</p>

<ul>
    <li><strong>Umsatz unter 112 000 €</strong>: nur Jahreserklärung</li>
    <li><strong>Umsatz zwischen 112 000 € und 620 000 €</strong>: Vierteljahreserklärungen <strong>und</strong> Jahreserklärung</li>
    <li><strong>Umsatz über 620 000 €</strong>: Monatserklärungen <strong>und</strong> Jahreserklärung</li>
</ul>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold text-amber-800">⚠️ Die Jahreserklärung ersetzt die periodischen Erklärungen nicht</p>
    <p class="text-amber-700">Wer vierteljährlich oder monatlich erklärt, muss <strong>zusätzlich</strong> eine zusammenfassende Jahreserklärung einreichen, und zwar vor dem <strong>1. Mai</strong> des Folgejahres. Sie zu vergessen löst Verspätungszuschläge aus, obwohl Sie Ihre MwSt. unterjährig vollständig gezahlt haben.</p>
</div>

<p>Die periodischen Erklärungen sind vor dem 15. des Folgemonats online über <strong>eCDF</strong> einzureichen. Die Zahlung begleitet die Erklärung; bei einem MwSt.-Guthaben kann eine Erstattung beantragt werden.</p>

HTML,
            'en' => <<<'HTML'
<h2>Declaration Obligations</h2>

<p>The filing frequency depends on your net turnover. <strong>The AED determines your regime</strong> — you do not choose it.</p>

<ul>
    <li><strong>Turnover below €112,000</strong>: annual return only</li>
    <li><strong>Turnover between €112,000 and €620,000</strong>: quarterly returns <strong>and</strong> an annual return</li>
    <li><strong>Turnover above €620,000</strong>: monthly returns <strong>and</strong> an annual return</li>
</ul>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold text-amber-800">⚠️ The annual return does not replace the periodic ones</p>
    <p class="text-amber-700">If you file quarterly or monthly returns, you must <strong>also</strong> file a recapitulative annual return, due before <strong>1 May</strong> of the following year. Forgetting it triggers late-filing penalties even though you paid all your VAT during the year.</p>
</div>

<p>Periodic returns are due before the 15th of the following month, filed online through <strong>eCDF</strong>. Payment accompanies the return; where you have a VAT credit, a refund may be requested.</p>

HTML,
            'lb' => <<<'HTML'
<h2>Erklärungspflichten</h2>

<p>D'Periodizitéit hänkt vun Ärem Ëmsaz ouni TVA of. <strong>Et ass d'AED, déi Äert Regime bestëmmt</strong> — Dir wielt et net.</p>

<ul>
    <li><strong>Ëmsaz ënner 112 000 €</strong>: nëmmen d'Jooresdeklaratioun</li>
    <li><strong>Ëmsaz tëscht 112 000 € an 620 000 €</strong>: Trimesterdeklaratiounen <strong>an</strong> d'Jooresdeklaratioun</li>
    <li><strong>Ëmsaz iwwer 620 000 €</strong>: Méintlech Deklaratiounen <strong>an</strong> d'Jooresdeklaratioun</li>
</ul>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold text-amber-800">⚠️ D'Jooresdeklaratioun ersetzt déi periodesch Deklaratiounen net</p>
    <p class="text-amber-700">Wann Dir Trimester- oder Méintdeklaratiounen ofgitt, musst Dir <strong>zousätzlech</strong> eng récapitulativ Jooresdeklaratioun ofginn, virum <strong>1. Mee</strong> vum nächste Joer. Se ze vergiessen léist Verspéidungsstrofen aus, obwuel Dir Är TVA am Laf vum Joer komplett bezuelt hutt.</p>
</div>

<p>Déi periodesch Deklaratioune ginn ier den 15. vum nächste Mount online iwwer <strong>eCDF</strong> ofginn. D'Bezuelung begleet d'Deklaratioun; bei engem TVA-Kredit kann eng Réckerstattung gefrot ginn.</p>

HTML,
            'pt' => <<<'HTML'
<h2>As obrigações declarativas</h2>

<p>A periodicidade depende do seu volume de negócios sem IVA. <strong>É a AED que determina o seu regime</strong> — não é o contribuinte que o escolhe.</p>

<ul>
    <li><strong>Volume de negócios inferior a 112 000 €</strong>: apenas declaração anual</li>
    <li><strong>Entre 112 000 € e 620 000 €</strong>: declarações trimestrais <strong>e</strong> declaração anual</li>
    <li><strong>Superior a 620 000 €</strong>: declarações mensais <strong>e</strong> declaração anual</li>
</ul>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold text-amber-800">⚠️ A declaração anual não substitui as declarações periódicas</p>
    <p class="text-amber-700">Se entrega declarações trimestrais ou mensais, deve <strong>além disso</strong> entregar uma declaração anual recapitulativa, até <strong>1 de maio</strong> do ano seguinte. Esquecê-la desencadeia penalizações por entrega tardia, mesmo tendo pago todo o seu IVA ao longo do ano.</p>
</div>

<p>As declarações periódicas entregam-se até ao dia 15 do mês seguinte, em linha através do <strong>eCDF</strong>. O pagamento acompanha a declaração; havendo crédito de IVA, pode pedir-se o reembolso.</p>

HTML,
            default => '',
        };
    }

    private function b2c(string $l): string
    {
        return match ($l) {
            'fr' => <<<'HTML'
<h3>Ventes à des particuliers UE (B2C)</h3>

<p>Un <strong>seuil unique de 10 000 € par an</strong> gouverne ces ventes. En dessous, vous appliquez la TVA luxembourgeoise ; au-delà, la TVA du pays du client, déclarée via le guichet unique <strong>OSS</strong> ou par une immatriculation dans chaque pays.</p>

<p><strong>Attention :</strong> ce seuil est <strong>commun</strong> aux ventes à distance de biens <em>et</em> aux services électroniques, de télécommunications et audiovisuels — tous pays de l'UE confondus, hors Luxembourg. C'est donc le <strong>cumul</strong> de vos ventes B2C européennes qu'il faut suivre, et non chaque catégorie séparément. L'opération qui fait franchir le seuil est déjà taxable dans le pays du client.</p>

HTML,
            'de' => <<<'HTML'
<h3>Verkäufe an EU-Privatpersonen (B2C)</h3>

<p>Eine <strong>einheitliche Schwelle von 10 000 € pro Jahr</strong> regelt diese Verkäufe. Darunter berechnen Sie die luxemburgische MwSt.; darüber die MwSt. des Kundenlandes, gemeldet über den einheitlichen Schalter <strong>OSS</strong> oder durch Registrierung in jedem Land.</p>

<p><strong>Achtung:</strong> Diese Schwelle gilt <strong>gemeinsam</strong> für Fernverkäufe von Waren <em>und</em> für elektronische, Telekommunikations- und Rundfunkdienstleistungen — über alle EU-Länder hinweg, ohne Luxemburg. Zu verfolgen ist also die <strong>Summe</strong> Ihrer europäischen B2C-Umsätze, nicht jede Kategorie einzeln. Der Umsatz, der die Schwelle überschreitet, ist bereits im Land des Kunden steuerpflichtig.</p>

HTML,
            'en' => <<<'HTML'
<h3>Sales to EU Individuals (B2C)</h3>

<p>A <strong>single threshold of €10,000 per year</strong> governs these sales. Below it you charge Luxembourg VAT; above it, the VAT of the client's country, declared through the <strong>OSS</strong> one-stop shop or by registering in each country.</p>

<p><strong>Careful:</strong> this threshold is <strong>shared</strong> between distance sales of goods <em>and</em> electronic, telecommunications and broadcasting services — across all EU countries combined, excluding Luxembourg. What you must track is therefore the <strong>combined</strong> total of your European B2C sales, not each category separately. The transaction that crosses the threshold is already taxable in the client's country.</p>

HTML,
            'lb' => <<<'HTML'
<h3>Verkaf un EU-Privatpersounen (B2C)</h3>

<p>Eng <strong>eenzeg Schwell vun 10 000 € pro Joer</strong> reegelt dës Verkeef. Dorënner applizéiert Dir d'Lëtzebuerger TVA; doriwwer d'TVA vum Land vum Client, deklaréiert iwwer de <strong>OSS</strong>-Guichet oder duerch eng Aschreiwung an all Land.</p>

<p><strong>Opgepasst:</strong> dës Schwell ass <strong>gemeinsam</strong> fir Fernverkeef vu Wueren <em>an</em> elektronesch, Telekom- an audiovisuell Déngschtleeschtungen — iwwer all EU-Länner, ouni Lëtzebuerg. Ze verfollegen ass also d'<strong>Zomm</strong> vun Äre europäesche B2C-Verkeef, net all Kategorie eenzel. D'Operatioun, déi d'Schwell iwwerschreit, ass scho am Land vum Client steierflichteg.</p>

HTML,
            'pt' => <<<'HTML'
<h3>Vendas a particulares da UE (B2C)</h3>

<p>Um <strong>limiar único de 10 000 € por ano</strong> rege estas vendas. Abaixo, aplica o IVA luxemburguês; acima, o IVA do país do cliente, declarado através do balcão único <strong>OSS</strong> ou por registo em cada país.</p>

<p><strong>Atenção:</strong> este limiar é <strong>comum</strong> às vendas à distância de bens <em>e</em> aos serviços eletrónicos, de telecomunicações e audiovisuais — todos os países da UE em conjunto, excluindo o Luxemburgo. O que deve acompanhar é, portanto, o <strong>total</strong> das suas vendas B2C europeias, e não cada categoria isoladamente. A operação que ultrapassa o limiar já é tributável no país do cliente.</p>

HTML,
            default => '',
        };
    }

    private function addition(string $l): string
    {
        return match ($l) {
            'fr' => <<<'HTML'
<h2>Cas particuliers fréquents</h2>

<h3>Restauration : 3 % et 17 % sur la même addition</h3>
<p>Les repas servis sur place sont à <strong>3 %</strong>, mais les <strong>boissons alcoolisées</strong> restent à <strong>17 %</strong>. Les deux taux coexistent donc sur une même note, et la ventilation doit apparaître.</p>

<h3>Hôtellerie : 3 % quel que soit le classement</h3>
<p>Contrairement à d'autres pays, le Luxembourg applique uniformément <strong>3 %</strong> à toutes les nuitées, de l'auberge au palace. Les services annexes (spa, restaurant interne) suivent leur propre régime.</p>

<h3>Livres numériques : 3 %</h3>
<p>Les e-books bénéficient du même taux que les livres papier, soit <strong>3 %</strong>. En revanche, les abonnements de streaming vidéo ou musical restent à <strong>17 %</strong> : ce sont des services numériques, pas des livres.</p>

<h3>Travaux de rénovation : la condition que tout le monde oublie</h3>
<p>Les travaux de rénovation d'un logement affecté à l'habitation principale peuvent bénéficier du taux super-réduit de <strong>3 %</strong>, sous des limites et conditions fixées par règlement grand-ducal (ancienneté du bâtiment, plafond de faveur fiscale par logement).</p>

<div class="bg-red-50 border-l-4 border-red-500 p-4 my-6">
    <p class="font-semibold text-red-800">⚠️ L'autorisation doit être demandée AVANT les travaux</p>
    <p class="text-red-700">L'<strong>article 65bis de la loi TVA</strong> est explicite : l'assujetti qui effectue ces travaux « doit demander auprès de l'administration l'autorisation pour l'application du taux super-réduit », et cette demande « doit être introduite […] <strong>avant la réalisation des travaux</strong> ». Faire le chantier puis réclamer le 3 % ne fonctionne pas. Sur une rénovation, l'écart entre 3 % et 17 % se compte en milliers d'euros.</p>
</div>

<h2>Se tromper de taux : les conséquences</h2>

<ul>
    <li><strong>Taux trop bas</strong> : redressement de l'AED, intérêts de retard, et amende administrative de <strong>250 € à 10 000 € par infraction</strong> (art. 77 LIVA)</li>
    <li><strong>Manquement ayant privé le Trésor de recettes</strong> : amende de <strong>10 % à 50 % de la TVA en cause</strong> — proportionnelle, donc sans plafond</li>
    <li><strong>Taux trop élevé</strong> : le client peut réclamer le remboursement, et vous devez régulariser auprès de l'AED</li>
    <li><strong>Refus de déduction côté client</strong> : si le taux est manifestement erroné, votre client peut se voir refuser la récupération</li>
    <li><strong>Fraude caractérisée</strong> : amende pénale de 25 000 € à dix fois le montant de TVA, et emprisonnement d'un mois à cinq ans (art. 80 LIVA)</li>
</ul>

<h2>Questions fréquentes</h2>

<h3>Le taux normal est-il bien à 17 % en 2026 ?</h3>
<p>Oui. Le taux avait été temporairement abaissé à 16 % en 2023 ; il est revenu à <strong>17 %</strong> au 1<sup>er</sup> janvier 2024 et n'a pas changé depuis.</p>

<h3>Quel taux appliquer si ma facture est à cheval sur un changement de taux ?</h3>
<p>C'est la date du <strong>fait générateur</strong> qui commande — c'est-à-dire la date de livraison ou d'exécution de la prestation, pas la date d'émission de la facture ni celle du paiement.</p>

<h3>Comment justifier un taux particulier en cas de contrôle ?</h3>
<p>Conservez ce qui rattache l'opération à la catégorie : nature exacte du bien ou du service, et, pour les travaux de rénovation, <strong>l'autorisation obtenue avant le chantier</strong>. En son absence, le 3 % est refusé même si les travaux y étaient éligibles sur le fond.</p>

<h3>Mon activité est-elle exonérée ?</h3>
<p>Les exonérations sont limitativement énumérées par la loi et ne se présument pas. En cas de doute, faites trancher par votre fiduciaire avant d'émettre la première facture — une exonération appliquée à tort se rattrape mal.</p>

<h2>Sources officielles</h2>

<ul>
    <li><a href="https://pfi.public.lu/dam-assets/pdf/legislation/tva/loi/loi-tva-2023-01-01.pdf" target="_blank" rel="noopener">Loi TVA coordonnée - taux, annexes A et B, articles 65bis, 77, 80</a></li>
    <li><a href="https://guichet.public.lu/fr/entreprises/fiscalite/impots-benefices/tva/declarations/declaration-tva.html" target="_blank" rel="noopener">Guichet.lu - Déclarations de TVA et périodicités</a></li>
    <li><a href="https://pfi.public.lu/" target="_blank" rel="noopener">AED - Portail de la fiscalité indirecte</a></li>
</ul>

HTML,
            'de' => <<<'HTML'
<h2>Häufige Sonderfälle</h2>

<h3>Gastronomie: 3 % und 17 % auf derselben Rechnung</h3>
<p>Vor Ort servierte Speisen unterliegen <strong>3 %</strong>, <strong>alkoholische Getränke</strong> jedoch <strong>17 %</strong>. Beide Sätze stehen also auf derselben Rechnung, und die Aufteilung muss ersichtlich sein.</p>

<h3>Hotellerie: 3 % unabhängig von der Kategorie</h3>
<p>Anders als in anderen Ländern wendet Luxemburg einheitlich <strong>3 %</strong> auf alle Übernachtungen an, vom Gasthof bis zum Luxushotel. Nebenleistungen (Spa, hauseigenes Restaurant) folgen ihrer eigenen Regelung.</p>

<h3>E-Books: 3 %</h3>
<p>Digitale Bücher genießen denselben Satz wie gedruckte, also <strong>3 %</strong>. Streaming-Abonnements für Video oder Musik bleiben dagegen bei <strong>17 %</strong>: das sind digitale Dienstleistungen, keine Bücher.</p>

<h3>Renovierungsarbeiten: die Bedingung, die alle vergessen</h3>
<p>Renovierungsarbeiten an einer als Hauptwohnsitz genutzten Wohnung können den stark ermäßigten Satz von <strong>3 %</strong> genießen, innerhalb der durch großherzogliche Verordnung festgelegten Grenzen und Bedingungen (Alter des Gebäudes, steuerlicher Höchstbetrag je Wohnung).</p>

<div class="bg-red-50 border-l-4 border-red-500 p-4 my-6">
    <p class="font-semibold text-red-800">⚠️ Die Genehmigung ist VOR den Arbeiten zu beantragen</p>
    <p class="text-red-700"><strong>Artikel 65bis des MwSt.-Gesetzes</strong> ist eindeutig: Wer solche Arbeiten ausführt, „muss bei der Verwaltung die Genehmigung für die Anwendung des stark ermäßigten Satzes beantragen", und dieser Antrag „ist […] <strong>vor der Ausführung der Arbeiten</strong> zu stellen". Erst bauen und dann die 3 % verlangen funktioniert nicht. Bei einer Renovierung geht der Unterschied zwischen 3 % und 17 % in die Tausende.</p>
</div>

<h2>Den falschen Satz anwenden: die Folgen</h2>

<ul>
    <li><strong>Zu niedriger Satz</strong>: Nachforderung der AED, Verzugszinsen und Verwaltungsbußgeld von <strong>250 € bis 10 000 € je Verstoß</strong> (Art. 77 LIVA)</li>
    <li><strong>Verstoß, der dem Fiskus Einnahmen entzogen hat</strong>: Geldbuße von <strong>10 % bis 50 % der betroffenen MwSt.</strong> — anteilig, also ohne Obergrenze</li>
    <li><strong>Zu hoher Satz</strong>: Der Kunde kann Erstattung verlangen, und Sie müssen gegenüber der AED berichtigen</li>
    <li><strong>Ablehnung des Vorsteuerabzugs beim Kunden</strong>: Ist der Satz offensichtlich falsch, kann dem Kunden der Abzug versagt werden</li>
    <li><strong>Vorsätzlicher Betrug</strong>: Geldstrafe von 25 000 € bis zum Zehnfachen der MwSt. und Freiheitsstrafe von einem Monat bis fünf Jahren (Art. 80 LIVA)</li>
</ul>

<h2>Häufige Fragen</h2>

<h3>Liegt der Normalsatz 2026 wirklich bei 17 %?</h3>
<p>Ja. Der Satz war 2023 vorübergehend auf 16 % gesenkt worden; zum 1. Januar 2024 kehrte er auf <strong>17 %</strong> zurück und ist seither unverändert.</p>

<h3>Welcher Satz gilt, wenn meine Rechnung einen Satzwechsel überspannt?</h3>
<p>Maßgeblich ist das Datum des <strong>Steuertatbestands</strong> — also der Lieferung oder Leistungserbringung, nicht das Ausstellungs- oder Zahlungsdatum.</p>

<h3>Wie belege ich einen Sondersatz bei einer Prüfung?</h3>
<p>Bewahren Sie auf, was den Umsatz der Kategorie zuordnet: die genaue Art des Gegenstands oder der Leistung und, bei Renovierungen, <strong>die vor Baubeginn erteilte Genehmigung</strong>. Ohne sie werden die 3 % verweigert, selbst wenn die Arbeiten inhaltlich begünstigt wären.</p>

<h3>Ist meine Tätigkeit befreit?</h3>
<p>Die Befreiungen sind im Gesetz abschließend aufgezählt und werden nicht vermutet. Klären Sie es im Zweifel mit Ihrem Treuhänder, bevor Sie die erste Rechnung stellen — eine zu Unrecht angewandte Befreiung lässt sich schlecht nachträglich heilen.</p>

<h2>Offizielle Quellen</h2>

<ul>
    <li><a href="https://pfi.public.lu/dam-assets/pdf/legislation/tva/loi/loi-tva-2023-01-01.pdf" target="_blank" rel="noopener">Koordiniertes MwSt.-Gesetz – Sätze, Anhänge A und B, Artikel 65bis, 77, 80</a></li>
    <li><a href="https://guichet.public.lu/fr/entreprises/fiscalite/impots-benefices/tva/declarations/declaration-tva.html" target="_blank" rel="noopener">Guichet.lu – MwSt.-Erklärungen und Periodizität</a></li>
    <li><a href="https://pfi.public.lu/" target="_blank" rel="noopener">AED – Portal der indirekten Steuern</a></li>
</ul>

HTML,
            'en' => <<<'HTML'
<h2>Common special cases</h2>

<h3>Restaurants: 3% and 17% on the same bill</h3>
<p>Meals served on the premises are taxed at <strong>3%</strong>, but <strong>alcoholic drinks</strong> remain at <strong>17%</strong>. Both rates therefore appear on the same bill, and the split must be shown.</p>

<h3>Hotels: 3% whatever the rating</h3>
<p>Unlike other countries, Luxembourg applies a uniform <strong>3%</strong> to every night's stay, from guesthouse to luxury hotel. Ancillary services (spa, in-house restaurant) follow their own regime.</p>

<h3>E-books: 3%</h3>
<p>Digital books enjoy the same rate as printed ones, namely <strong>3%</strong>. Video or music streaming subscriptions, by contrast, stay at <strong>17%</strong>: those are digital services, not books.</p>

<h3>Renovation work: the condition everyone forgets</h3>
<p>Renovation of a dwelling used as a main residence can qualify for the super-reduced rate of <strong>3%</strong>, within limits and conditions set by grand-ducal regulation (age of the building, tax-relief ceiling per dwelling).</p>

<div class="bg-red-50 border-l-4 border-red-500 p-4 my-6">
    <p class="font-semibold text-red-800">⚠️ Authorisation must be requested BEFORE the works</p>
    <p class="text-red-700"><strong>Article 65bis of the VAT law</strong> is explicit: a taxable person carrying out such work "must request authorisation from the administration for the application of the super-reduced rate", and that request "must be submitted […] <strong>before the works are carried out</strong>". Doing the job first and claiming 3% afterwards does not work. On a renovation, the gap between 3% and 17% runs into thousands of euros.</p>
</div>

<h2>Getting the rate wrong: the consequences</h2>

<ul>
    <li><strong>Rate too low</strong>: reassessment by the AED, late-payment interest, and an administrative fine of <strong>€250 to €10,000 per infringement</strong> (art. 77 LIVA)</li>
    <li><strong>Breach that deprived the Treasury of revenue</strong>: fine of <strong>10% to 50% of the VAT at stake</strong> — proportional, hence uncapped</li>
    <li><strong>Rate too high</strong>: the customer can claim a refund, and you must correct it with the AED</li>
    <li><strong>Deduction refused on the customer's side</strong>: if the rate is manifestly wrong, your customer may be denied recovery</li>
    <li><strong>Deliberate fraud</strong>: criminal fine of €25,000 to ten times the VAT amount, and imprisonment from one month to five years (art. 80 LIVA)</li>
</ul>

<h2>Frequently asked questions</h2>

<h3>Is the standard rate really 17% in 2026?</h3>
<p>Yes. The rate was temporarily lowered to 16% in 2023; it returned to <strong>17%</strong> on 1 January 2024 and has not changed since.</p>

<h3>Which rate applies if my invoice straddles a rate change?</h3>
<p>The date of the <strong>chargeable event</strong> governs — that is, the date of delivery or performance, not the invoice date nor the payment date.</p>

<h3>How do I justify a special rate during an audit?</h3>
<p>Keep whatever ties the transaction to the category: the exact nature of the goods or service and, for renovation work, <strong>the authorisation obtained before the job started</strong>. Without it, the 3% is refused even if the work would otherwise have qualified.</p>

<h3>Is my activity exempt?</h3>
<p>Exemptions are exhaustively listed in the law and are never presumed. When in doubt, settle it with your accountant before issuing the first invoice — an exemption wrongly applied is hard to undo.</p>

<h2>Official sources</h2>

<ul>
    <li><a href="https://pfi.public.lu/dam-assets/pdf/legislation/tva/loi/loi-tva-2023-01-01.pdf" target="_blank" rel="noopener">Coordinated VAT law - rates, annexes A and B, articles 65bis, 77, 80</a></li>
    <li><a href="https://guichet.public.lu/fr/entreprises/fiscalite/impots-benefices/tva/declarations/declaration-tva.html" target="_blank" rel="noopener">Guichet.lu - VAT returns and filing frequencies</a></li>
    <li><a href="https://pfi.public.lu/" target="_blank" rel="noopener">AED - Indirect taxation portal</a></li>
</ul>

HTML,
            'lb' => <<<'HTML'
<h2>Heefeg Spezialfäll</h2>

<h3>Restauratioun: 3 % an 17 % op der selwechter Rechnung</h3>
<p>Iessen, dat op der Plaz servéiert gëtt, ass bei <strong>3 %</strong>, mä <strong>alkoholesch Gedrénks</strong> bleiwen bei <strong>17 %</strong>. Béid Tariffer stinn also op der selwechter Rechnung, an d'Opdeelung muss ersiichtlech sinn.</p>

<h3>Hotellerie: 3 % onofhängeg vun der Klassifikatioun</h3>
<p>Am Géigesaz zu anere Länner applizéiert Lëtzebuerg eenheetlech <strong>3 %</strong> op all Iwwernuechtung, vun der Auberge bis zum Luxushotel. Niewenleeschtungen (Spa, haus-eegent Restaurant) follegen hirem eegene Regime.</p>

<h3>E-Bicher: 3 %</h3>
<p>Digital Bicher profitéiere vum selwechten Taux wéi gedréckte, also <strong>3 %</strong>. Streaming-Abonnementer fir Video oder Musek bleiwen dogéint bei <strong>17 %</strong>: dat sinn digital Déngschtleeschtungen, keng Bicher.</p>

<h3>Renovatiounsaarbechten: d'Bedingung, déi jiddereen vergësst</h3>
<p>Renovatiounsaarbechten un enger Wunneng, déi als Haaptwunnsëtz genotzt gëtt, kënne vum superreduzéierten Taux vun <strong>3 %</strong> profitéieren, an de Grenzen a Bedingungen, déi duerch groussherzoglecht Reglement festgeluecht sinn (Alter vum Gebai, steierleche Plafong pro Wunneng).</p>

<div class="bg-red-50 border-l-4 border-red-500 p-4 my-6">
    <p class="font-semibold text-red-800">⚠️ D'Autorisatioun muss VIRUN den Aarbechte gefrot ginn</p>
    <p class="text-red-700">Den <strong>Artikel 65bis vum TVA-Gesetz</strong> ass kloer: den Assujetti, deen esou Aarbechte mécht, „muss bei der Administratioun d'Autorisatioun fir d'Applikatioun vum superreduzéierten Taux ufroen", an dës Ufro „muss […] <strong>virun der Realisatioun vun den Aarbechten</strong> agereecht ginn". Eréischt bauen an dann d'3 % verlaangen funktionéiert net. Bei enger Renovatioun geet den Ënnerscheed tëscht 3 % an 17 % an d'Dausenden.</p>
</div>

<h2>Sech am Taux ieren: d'Konsequenzen</h2>

<ul>
    <li><strong>Ze nidderegen Taux</strong>: Nofuerderung vun der AED, Verzuchszënsen, an administrativ Geldstrof vun <strong>250 € bis 10 000 € pro Infraktioun</strong> (Art. 77 LIVA)</li>
    <li><strong>Manktem, dat de Staat ëm Recetten bruecht huet</strong>: Geldstrof vun <strong>10 % bis 50 % vun der betraffener TVA</strong> — proportional, also ouni Plafong</li>
    <li><strong>Ze héijen Taux</strong>: de Client kann d'Réckerstattung verlaangen, an Dir musst bei der AED regulariséieren</li>
    <li><strong>Refus vum Ofzuch op der Client-Säit</strong>: ass den Taux offensichtlech falsch, kann dem Client d'Récupératioun verweigert ginn</li>
    <li><strong>Virsätzleche Bedruch</strong>: Geldstrof vun 25 000 € bis zum Zéngfache vum TVA-Betrag, a Prisong vun engem Mount bis fënnef Joer (Art. 80 LIVA)</li>
</ul>

<h2>Heefeg Froen</h2>

<h3>Ass den Normaltaux 2026 wierklech bei 17 %?</h3>
<p>Jo. Den Taux war 2023 provisoresch op 16 % erofgesat ginn; den 1. Januar 2024 ass en op <strong>17 %</strong> zréckkomm an ass zanterhier onverännert.</p>

<h3>Wéi ee Taux gëllt, wann meng Rechnung iwwer e Tauxwiessel geet?</h3>
<p>Entscheedend ass d'Datum vum <strong>Steiertatbestand</strong> — also vun der Liwwerung oder vun der Leeschtung, net d'Ausstellungs- oder Bezuelungsdatum.</p>

<h3>Wéi beleeën ech e Spezialtaux bei enger Kontroll?</h3>
<p>Haalt op, wat d'Operatioun der Kategorie zouuerdnet: déi genee Aart vum Gutt oder vun der Leeschtung an, bei Renovatiounen, <strong>d'Autorisatioun, déi virum Chantier kritt gouf</strong>. Ouni si ginn d'3 % refuséiert, och wann d'Aarbechten inhaltlech beguinstegt gewiescht wieren.</p>

<h3>Ass meng Aktivitéit befreit?</h3>
<p>D'Befreiunge sinn am Gesetz ofschléissend opgezielt a gi net vermutt. Am Zweiwel klärt et mat Ärer Fiduciaire, ier Dir déi éischt Rechnung ausstellt — eng zu Onrecht applizéiert Befreiung léist sech schlecht nohuelen.</p>

<h2>Offiziell Quellen</h2>

<ul>
    <li><a href="https://pfi.public.lu/dam-assets/pdf/legislation/tva/loi/loi-tva-2023-01-01.pdf" target="_blank" rel="noopener">Koordinéiert TVA-Gesetz – Tariffer, Annexen A a B, Artikelen 65bis, 77, 80</a></li>
    <li><a href="https://guichet.public.lu/fr/entreprises/fiscalite/impots-benefices/tva/declarations/declaration-tva.html" target="_blank" rel="noopener">Guichet.lu – TVA-Deklaratiounen a Periodizitéiten</a></li>
    <li><a href="https://pfi.public.lu/" target="_blank" rel="noopener">AED – Portal vun der indirekter Fiskalitéit</a></li>
</ul>

HTML,
            'pt' => <<<'HTML'
<h2>Casos particulares frequentes</h2>

<h3>Restauração: 3 % e 17 % na mesma conta</h3>
<p>As refeições servidas no local são tributadas a <strong>3 %</strong>, mas as <strong>bebidas alcoólicas</strong> mantêm-se a <strong>17 %</strong>. As duas taxas coexistem, portanto, na mesma conta, e a repartição deve ficar visível.</p>

<h3>Hotelaria: 3 % qualquer que seja a classificação</h3>
<p>Ao contrário de outros países, o Luxemburgo aplica uniformemente <strong>3 %</strong> a todas as dormidas, da pensão ao hotel de luxo. Os serviços acessórios (spa, restaurante interno) seguem o seu próprio regime.</p>

<h3>Livros digitais: 3 %</h3>
<p>Os e-books beneficiam da mesma taxa que os livros em papel, ou seja <strong>3 %</strong>. Em contrapartida, as assinaturas de streaming de vídeo ou música mantêm-se a <strong>17 %</strong>: são serviços digitais, não livros.</p>

<h3>Obras de renovação: a condição que toda a gente esquece</h3>
<p>As obras de renovação de uma habitação afeta a residência principal podem beneficiar da taxa super-reduzida de <strong>3 %</strong>, dentro dos limites e condições fixados por regulamento grão-ducal (idade do edifício, teto de benefício fiscal por habitação).</p>

<div class="bg-red-50 border-l-4 border-red-500 p-4 my-6">
    <p class="font-semibold text-red-800">⚠️ A autorização deve ser pedida ANTES das obras</p>
    <p class="text-red-700">O <strong>artigo 65bis da lei do IVA</strong> é explícito: quem executa estas obras «deve pedir à administração a autorização para a aplicação da taxa super-reduzida», e esse pedido «deve ser apresentado […] <strong>antes da realização das obras</strong>». Fazer a obra e depois reclamar os 3 % não funciona. Numa renovação, a diferença entre 3 % e 17 % conta-se em milhares de euros.</p>
</div>

<h2>Enganar-se na taxa: as consequências</h2>

<ul>
    <li><strong>Taxa demasiado baixa</strong>: correção pela AED, juros de mora e coima administrativa de <strong>250 € a 10 000 € por infração</strong> (art. 77 LIVA)</li>
    <li><strong>Incumprimento que privou o Estado de receita</strong>: coima de <strong>10 % a 50 % do IVA em causa</strong> — proporcional, logo sem limite máximo</li>
    <li><strong>Taxa demasiado alta</strong>: o cliente pode pedir o reembolso, e tem de regularizar junto da AED</li>
    <li><strong>Recusa de dedução do lado do cliente</strong>: se a taxa estiver manifestamente errada, o cliente pode ver recusada a recuperação</li>
    <li><strong>Fraude deliberada</strong>: multa penal de 25 000 € a dez vezes o montante do IVA, e prisão de um mês a cinco anos (art. 80 LIVA)</li>
</ul>

<h2>Perguntas frequentes</h2>

<h3>A taxa normal está mesmo em 17 % em 2026?</h3>
<p>Sim. A taxa tinha sido temporariamente reduzida para 16 % em 2023; voltou a <strong>17 %</strong> a 1 de janeiro de 2024 e não mudou desde então.</p>

<h3>Que taxa aplicar se a minha fatura atravessa uma mudança de taxa?</h3>
<p>Manda a data do <strong>facto gerador</strong> — ou seja, a data da entrega ou da execução da prestação, não a data de emissão da fatura nem a do pagamento.</p>

<h3>Como justificar uma taxa especial numa inspeção?</h3>
<p>Conserve o que liga a operação à categoria: a natureza exata do bem ou do serviço e, no caso das obras de renovação, <strong>a autorização obtida antes do início da obra</strong>. Sem ela, os 3 % são recusados mesmo que as obras fossem elegíveis quanto ao fundo.</p>

<h3>A minha atividade está isenta?</h3>
<p>As isenções são taxativamente enumeradas na lei e não se presumem. Na dúvida, esclareça com o seu contabilista antes de emitir a primeira fatura — uma isenção aplicada indevidamente dificilmente se corrige depois.</p>

<h2>Fontes oficiais</h2>

<ul>
    <li><a href="https://pfi.public.lu/dam-assets/pdf/legislation/tva/loi/loi-tva-2023-01-01.pdf" target="_blank" rel="noopener">Lei do IVA coordenada - taxas, anexos A e B, artigos 65bis, 77, 80</a></li>
    <li><a href="https://guichet.public.lu/fr/entreprises/fiscalite/impots-benefices/tva/declarations/declaration-tva.html" target="_blank" rel="noopener">Guichet.lu - Declarações de IVA e periodicidades</a></li>
    <li><a href="https://pfi.public.lu/" target="_blank" rel="noopener">AED - Portal da fiscalidade indireta</a></li>
</ul>

HTML,
            default => '',
        };
    }
};
