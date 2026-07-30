<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Article « mentions-obligatoires-facture-luxembourg » —
 * vérification factuelle et traductions intégrales.
 *
 * L'article était le mieux sourcé de la série. Quatre corrections
 * néanmoins, dont deux sur le sens de l'obligation :
 *
 * 1. NUMÉRO RCS présenté comme une mention de l'article 63 LIVA, sous le
 *    chapeau « Selon l'article 63 LIVA, votre facture doit impérativement
 *    contenir ». La liste officielle de l'AED compte NEUF mentions et
 *    n'inclut pas le RCS : c'est une obligation de droit des sociétés,
 *    pas de droit de la TVA. La citation « Code de commerce art. 8 »
 *    n'a pas pu être vérifiée et est retirée. Le RCS reste dans la
 *    checklist, correctement rattaché.
 *
 * 2. DATE DE LIVRAISON annoncée comme « toujours indiquée, même si elle
 *    coïncide avec la date d'émission ». L'AED comme l'article 226 §7 de
 *    la directive ne l'exigent que si elle DIFFÈRE de la date d'émission.
 *    L'article inventait une obligation. Reclassé en recommandation, avec
 *    la règle légale énoncée telle qu'elle est.
 *
 * 3. SANCTIONS INCOMPLÈTES — et c'est l'omission qui compte le plus. Ne
 *    figurait que l'amende de 250 à 10 000 EUR. Manquaient :
 *      - l'amende de 10 % à 50 % DU MONTANT DE TVA EN CAUSE lorsque le
 *        manquement a entraîné un non-paiement ou un remboursement
 *        irrégulier — sans plafond, donc potentiellement très supérieure
 *        aux 10 000 EUR mis en avant ;
 *      - l'amende pouvant atteindre 25 000 EUR PAR JOUR de retard, après
 *        avertissement, en cas de non-communication des factures et
 *        pièces comptables lors d'un contrôle.
 *    L'article rassurait donc à tort en présentant un plafond de
 *    10 000 EUR comme le risque maximal.
 *
 * 4. COHÉRENCE avec les articles « délais de paiement » et « relancer un
 *    client » : le taux d'intérêt de retard passe de « ~10 % en 2026 » au
 *    taux officiel daté (10,15 %, 1er semestre 2026), et la loi de 2004
 *    est citée comme « modifiée ».
 *
 * FAITS VÉRIFIÉS et confirmés exacts :
 * - Amende administrative de 250 à 10 000 EUR par infraction (art. 77).
 * - Sanctions pénales : 25 000 EUR à 10 fois le montant de TVA,
 *   emprisonnement d'un mois à cinq ans (art. 80). Ajout de la privation
 *   des droits civiques de 5 à 10 ans, omise.
 * - Taux luxembourgeois 17 / 14 / 8 / 3 %.
 * - Mentions d'autoliquidation (art. 196), de livraison intracommunautaire
 *   (art. 138), d'exportation (art. 146) et de franchise (art. 57bis).
 * - Conservation 10 ans.
 *
 * LIEN CORRIGÉ : l'ELI Legilux donné pour le Code de commerce renvoie
 * vers une application JavaScript dont le contenu n'a pas pu être
 * vérifié. Remplacé par la page « obligations comptables » de guichet.lu,
 * dont le contenu est contrôlable.
 *
 * PARITÉ : allemand, anglais, luxembourgeois et portugais faisaient
 * 9 996 à 11 222 caractères contre 14 876, avec 1 à 2 liens contre 11.
 *
 * ⚠️ La version luxembourgeoise mérite une relecture par un locuteur natif.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['fr' => $this->fr(), 'de' => $this->de(), 'en' => $this->en(), 'lb' => $this->lb(), 'pt' => $this->pt()] as $locale => $content) {
            DB::table('blog_posts')
                ->where('translation_key', 'mentions-obligatoires-facture-luxembourg')
                ->where('locale', $locale)
                ->update(['content' => $content, 'updated_at' => now()]);
        }
    }

    public function down(): void
    {
        // Contenu rédactionnel : la version antérieure présentait le
        // plafond de 10 000 EUR comme le risque maximal.
    }

    /** Bloc « exemple de facture », identique dans toutes les langues hors libellés. */
    private function sample(array $t): string
    {
        return <<<HTML
<div class="bg-gray-50 border border-gray-200 rounded-lg p-6 my-6 text-sm">
    <div class="flex justify-between mb-6">
        <div>
            <p class="font-bold">{$t['company']}</p>
            <p>123 rue du Commerce</p>
            <p>L-1234 Luxembourg</p>
            <p>TVA: LU12345678</p>
            <p>RCS: B123456</p>
        </div>
        <div class="text-right">
            <p class="font-bold text-xl">{$t['invoice']}</p>
            <p>N° 2026-0042</p>
            <p>{$t['date']}: 15/02/2026</p>
        </div>
    </div>

    <div class="mb-6">
        <p class="font-semibold">{$t['billed']}</p>
        <p>{$t['client']}</p>
        <p>456 avenue des Affaires</p>
        <p>L-5678 Luxembourg</p>
        <p>TVA: LU87654321</p>
    </div>

    <table class="w-full mb-6">
        <thead class="border-b-2 border-gray-300">
            <tr>
                <th class="text-left py-2">{$t['desc']}</th>
                <th class="text-right py-2">{$t['qty']}</th>
                <th class="text-right py-2">{$t['unit']}</th>
                <th class="text-right py-2">TVA</th>
                <th class="text-right py-2">{$t['net']}</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="py-2">{$t['line']}</td>
                <td class="text-right">5h</td>
                <td class="text-right">150,00€</td>
                <td class="text-right">17%</td>
                <td class="text-right">750,00€</td>
            </tr>
        </tbody>
    </table>

    <div class="text-right">
        <p>{$t['totalNet']}: <strong>750,00€</strong></p>
        <p>TVA 17%: <strong>127,50€</strong></p>
        <p class="text-lg">{$t['totalGross']}: <strong>877,50€</strong></p>
    </div>
</div>
HTML;
    }

    private function fr(): string
    {
        $sample = $this->sample([
            'company' => 'Votre Société SARL', 'invoice' => 'FACTURE', 'date' => 'Date',
            'billed' => 'Facturé à:', 'client' => 'Client Entreprise SA', 'desc' => 'Description',
            'qty' => 'Qté', 'unit' => 'P.U. HT', 'net' => 'Total HT', 'line' => 'Prestation de conseil',
            'totalNet' => 'Total HT', 'totalGross' => 'Total TTC',
        ]);

        return <<<HTML
<div data-ai-summary class="bg-slate-50 border border-slate-200 rounded-xl p-5 my-4"><p class="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-2">En bref</p><ul class="list-disc pl-5 space-y-1 text-slate-700 text-sm"><li>Les mentions obligatoires relèvent de l'<strong>article 63 LIVA</strong> : identité et adresse des parties, numéros de TVA, date, <strong>numéro séquentiel unique</strong>, désignation, base HT, taux, montant de TVA, total TTC.</li><li>Une facture <strong>non conforme</strong> peut être rejetée par le client et entraîner une <strong>amende AED de 250 € à 10 000 €</strong> (art. 77 LIVA) — voire <strong>10 % à 50 % de la TVA en cause</strong> si le Trésor a été privé de recettes.</li><li>Mentions conditionnelles : <strong>autoliquidation</strong> (art. 196 directive), franchise, exonérations.</li><li><strong>Conservation 10 ans</strong> des factures et pièces comptables.</li></ul></div>
<p class="lead">Une facture non conforme peut être rejetée par votre client et vous exposer à des amendes AED. Voici la checklist complète des mentions obligatoires pour créer des factures luxembourgeoises irréprochables — basée sur l'<a href="https://legilux.public.lu/eli/etat/leg/loi/1979/02/12/n1/jo" target="_blank" rel="noopener">article 63 LIVA</a> et sur la liste publiée par l'AED.</p>

<h2>Checklist des mentions obligatoires</h2>

<p>L'<strong>article 63 LIVA</strong> (loi modifiée du 12 février 1979) et la liste de l'AED imposent les éléments suivants :</p>

<div class="bg-white border-2 border-purple-200 rounded-xl p-6 my-6">
    <h3 class="text-lg font-bold text-purple-800 mb-4">✅ Informations sur l'émetteur</h3>
    <ul class="space-y-2">
        <li>☐ <strong>Nom complet ou raison sociale</strong></li>
        <li>☐ <strong>Adresse complète</strong> du siège social</li>
        <li>☐ <strong>Numéro de TVA intracommunautaire</strong> (format LU + 8 chiffres)</li>
        <li>☐ <strong>Numéro RCS</strong> pour les commerçants et sociétés inscrits — obligation issue de la <em>législation sur le registre de commerce et des sociétés</em>, et non de la loi TVA</li>
    </ul>
</div>

<div class="bg-white border-2 border-purple-200 rounded-xl p-6 my-6">
    <h3 class="text-lg font-bold text-purple-800 mb-4">✅ Informations sur le client</h3>
    <ul class="space-y-2">
        <li>☐ <strong>Nom complet ou raison sociale</strong></li>
        <li>☐ <strong>Adresse complète</strong></li>
        <li>☐ <strong>Numéro de TVA</strong> (obligatoire pour B2B intracommunautaire - validé via <a href="https://ec.europa.eu/taxation_customs/vies/" target="_blank" rel="noopener">VIES</a>)</li>
    </ul>
</div>

<div class="bg-white border-2 border-purple-200 rounded-xl p-6 my-6">
    <h3 class="text-lg font-bold text-purple-800 mb-4">✅ Informations sur la facture</h3>
    <ul class="space-y-2">
        <li>☐ <strong>Numéro de facture unique et séquentiel</strong> (art. 63 LIVA, point 3°)</li>
        <li>☐ <strong>Date d'émission</strong> de la facture</li>
        <li>☐ <strong>Date de livraison ou de prestation</strong> — légalement exigée <em>lorsqu'elle diffère</em> de la date d'émission (art. 226 §7 de la directive). L'indiquer systématiquement reste la bonne pratique : cela lève toute ambiguïté sur le fait générateur en cas de contrôle</li>
    </ul>
</div>

<div class="bg-white border-2 border-purple-200 rounded-xl p-6 my-6">
    <h3 class="text-lg font-bold text-purple-800 mb-4">✅ Détail des biens ou services</h3>
    <ul class="space-y-2">
        <li>☐ <strong>Description claire</strong> et détaillée</li>
        <li>☐ <strong>Quantité</strong> livrée ou prestée</li>
        <li>☐ <strong>Prix unitaire HT</strong></li>
        <li>☐ <strong>Réductions ou rabais</strong> éventuels</li>
    </ul>
</div>

<div class="bg-white border-2 border-purple-200 rounded-xl p-6 my-6">
    <h3 class="text-lg font-bold text-purple-800 mb-4">✅ Informations TVA</h3>
    <ul class="space-y-2">
        <li>☐ <strong>Taux de TVA</strong> applicable par ligne (17 %, 14 %, 8 %, 3 % ou 0 %)</li>
        <li>☐ <strong>Montant de TVA</strong> par taux</li>
        <li>☐ <strong>Base imposable</strong> (montant HT) par taux de TVA</li>
        <li>☐ <strong>Mention d'exonération ou d'autoliquidation</strong> si applicable (voir tableau ci-dessous)</li>
    </ul>
</div>

<div class="bg-white border-2 border-purple-200 rounded-xl p-6 my-6">
    <h3 class="text-lg font-bold text-purple-800 mb-4">✅ Totaux</h3>
    <ul class="space-y-2">
        <li>☐ <strong>Total HT</strong> (hors taxes)</li>
        <li>☐ <strong>Total TVA</strong></li>
        <li>☐ <strong>Total TTC</strong> (toutes taxes comprises)</li>
    </ul>
</div>

<h2>Mentions conditionnelles selon les cas</h2>

<p>Selon la nature de l'opération, une mention spécifique doit figurer sur la facture. Voici les formulations codifiées :</p>

<table class="w-full border-collapse border border-gray-300 my-6">
    <thead class="bg-gray-100">
        <tr>
            <th class="border border-gray-300 px-4 py-2 text-left">Situation</th>
            <th class="border border-gray-300 px-4 py-2 text-left">Mention à apposer</th>
            <th class="border border-gray-300 px-4 py-2 text-left">Base légale</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td class="border border-gray-300 px-4 py-2">Service B2B intra-UE (preneur dans un autre État membre)</td>
            <td class="border border-gray-300 px-4 py-2 font-mono text-sm">« Autoliquidation - Article 196 de la directive 2006/112/CE »</td>
            <td class="border border-gray-300 px-4 py-2 text-sm">Art. 17 LIVA (lieu) + art. 196 directive (redevable) + art. 226 §11bis (mention)</td>
        </tr>
        <tr>
            <td class="border border-gray-300 px-4 py-2">Livraison intra-UE de biens B2B</td>
            <td class="border border-gray-300 px-4 py-2 font-mono text-sm">« Exonération de TVA - Article 138 de la directive 2006/112/CE »</td>
            <td class="border border-gray-300 px-4 py-2 text-sm">Art. 43 §1 d) LIVA</td>
        </tr>
        <tr>
            <td class="border border-gray-300 px-4 py-2">Exportation hors UE</td>
            <td class="border border-gray-300 px-4 py-2 font-mono text-sm">« Exonération de TVA - Article 146 de la directive 2006/112/CE »</td>
            <td class="border border-gray-300 px-4 py-2 text-sm">Art. 43 §1 a) LIVA</td>
        </tr>
        <tr>
            <td class="border border-gray-300 px-4 py-2">Franchise de TVA (petites entreprises)</td>
            <td class="border border-gray-300 px-4 py-2 font-mono text-sm">« TVA non applicable - Article 57bis de la loi modifiée du 12 février 1979 »</td>
            <td class="border border-gray-300 px-4 py-2 text-sm">Art. 57bis LIVA (seuil 50 000 €)</td>
        </tr>
    </tbody>
</table>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">À ne pas confondre</p>
    <p>Pour l'autoliquidation B2B intra-UE, beaucoup de modèles francophones recopient la référence à « l'article 44 de la directive 2006/112/CE ». L'article 44 définit le <strong>lieu d'imposition</strong> (chez le preneur), mais la mention obligatoire codifiée renvoie à l'<strong>article 196</strong> (qui désigne le preneur comme redevable). Préférez la mention article 196.</p>
</div>

<h3>Cas particuliers de facturation</h3>

<p><strong>Facture d'acompte</strong> :</p>

<div class="bg-blue-50 border-l-4 border-blue-500 p-4 my-4">
    <p class="font-mono text-sm">« Acompte sur commande n° [référence] du [date] »</p>
</div>

<p><strong>Facture d'avoir (note de crédit)</strong> :</p>

<div class="bg-blue-50 border-l-4 border-blue-500 p-4 my-4">
    <p class="font-mono text-sm">« Avoir sur facture n° [numéro] du [date] »</p>
</div>

<h2>La numérotation des factures (art. 63 LIVA, point 3°)</h2>

<p>La numérotation doit respecter ces règles :</p>

<ul>
    <li><strong>Unique</strong> : chaque facture a un numéro distinct</li>
    <li><strong>Chronologique</strong> : les numéros se suivent dans l'ordre d'émission</li>
    <li><strong>Sans rupture</strong> : pas de trou dans la séquence</li>
    <li><strong>Non réutilisable</strong> : un numéro ne peut être attribué qu'une fois</li>
</ul>

<h3>Exemples de formats acceptés</h3>

<table class="w-full border-collapse border border-gray-300 my-6">
    <thead class="bg-gray-100">
        <tr>
            <th class="border border-gray-300 px-4 py-2 text-left">Format</th>
            <th class="border border-gray-300 px-4 py-2 text-left">Exemple</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td class="border border-gray-300 px-4 py-2">Année + numéro</td>
            <td class="border border-gray-300 px-4 py-2 font-mono">2026-0001, 2026-0002</td>
        </tr>
        <tr>
            <td class="border border-gray-300 px-4 py-2">Préfixe + numéro</td>
            <td class="border border-gray-300 px-4 py-2 font-mono">FAC-001, FAC-002</td>
        </tr>
        <tr>
            <td class="border border-gray-300 px-4 py-2">Numéro simple</td>
            <td class="border border-gray-300 px-4 py-2 font-mono">00001, 00002</td>
        </tr>
    </tbody>
</table>

<h2>Conditions de paiement</h2>

<p>Bien que non exigées par l'article 63 LIVA, ces indications sont <strong>fortement recommandées</strong> :</p>

<ul>
    <li><strong>Délai de paiement</strong> (par défaut : 30 jours après réception, loi modifiée du 18 avril 2004)</li>
    <li><strong>Date d'échéance</strong></li>
    <li><strong>Coordonnées bancaires</strong> (IBAN, BIC)</li>
    <li><strong>Intérêts de retard</strong> applicables entre professionnels — taux de référence de la BCE + 8 points, soit <strong>10,15 % au 1<sup>er</sup> semestre 2026</strong>, révisé chaque semestre — et <strong>indemnité forfaitaire de 40 €</strong></li>
</ul>

<h2>Conservation des factures</h2>

<p>Vous devez conserver vos factures (émises et reçues) pendant <strong>10 ans</strong> à compter de la clôture de l'exercice — article 16 du Code de commerce et article 65 LIVA. Format papier ou électronique (PDF/A avec garanties d'intégrité). Voir les <a href="https://guichet.public.lu/fr/entreprises/gestion-juridique-comptabilite/comptable/enregistrement/obligations-comptables.html" target="_blank" rel="noopener">obligations comptables sur Guichet.lu</a>.</p>

<h2>Conséquences d'une facture non conforme</h2>

<div class="bg-red-50 border-l-4 border-red-500 p-4 my-6">
    <p class="font-semibold text-red-800">⚠️ Risques encourus</p>
    <ul class="text-red-700 mt-2">
        <li>Rejet de la déduction de TVA par votre client</li>
        <li><strong>Amende administrative de 250 € à 10 000 € par infraction</strong> (art. 77 LIVA)</li>
        <li>Si le manquement a entraîné un non-paiement de TVA ou un remboursement irrégulier : <strong>amende de 10 % à 50 % du montant de TVA en cause</strong> — proportionnelle, donc sans plafond</li>
        <li>En cas de refus de communiquer factures et pièces comptables lors d'un contrôle : <strong>jusqu'à 25 000 € par jour de retard</strong>, après avertissement</li>
        <li>En cas de fraude fiscale aggravée ou d'escroquerie fiscale : amende pénale de 25 000 € à 10 fois le montant de TVA, emprisonnement d'un mois à cinq ans et privation des droits civiques de 5 à 10 ans (art. 80 LIVA)</li>
    </ul>
</div>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">Le plafond de 10 000 € n'est pas le risque maximal</p>
    <p>C'est l'amende proportionnelle qui fait mal. Sur un litige portant sur 80 000 € de TVA, une sanction de 10 à 50 % représente 8 000 à 40 000 € — indépendamment du nombre d'infractions formelles relevées.</p>
</div>

<h2>Exemple de facture conforme</h2>

<p>Voici les éléments essentiels d'une facture conforme :</p>

{$sample}

<h2>Simplifiez-vous la vie avec faktur.lu</h2>

<p>Créer des factures conformes manuellement peut être source d'erreurs. <strong>faktur.lu</strong> automatise la conformité :</p>

<ul>
    <li>✅ Toutes les mentions obligatoires pré-remplies</li>
    <li>✅ Numérotation automatique et séquentielle (art. 63 LIVA)</li>
    <li>✅ Calcul automatique de la TVA selon le cas</li>
    <li>✅ Mentions légales adaptées (autoliquidation, export, franchise 57bis…)</li>
    <li>✅ Export FAIA intégré pour les contrôles fiscaux AED</li>
</ul>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">À vérifier chaque année</p>
    <p>Les mentions obligatoires, références d'articles et seuils peuvent évoluer. Cette page est mise à jour régulièrement, mais pour votre situation personnelle, consultez votre fiduciaire ou directement l'<a href="https://pfi.public.lu/" target="_blank" rel="noopener">AED</a>.</p>
</div>

<h2>Sources officielles</h2>

<ul>
    <li><a href="https://legilux.public.lu/eli/etat/leg/loi/1979/02/12/n1/jo" target="_blank" rel="noopener">Loi modifiée du 12 février 1979 (LIVA) - articles 17, 43, 57bis, 63, 65, 77, 80</a></li>
    <li><a href="https://pfi.public.lu/fr/professionnel/tva/en-cours-activite-economique/que-doivent-contenir-factures.html" target="_blank" rel="noopener">AED - Mentions obligatoires sur les factures</a></li>
    <li><a href="https://logistics.public.lu/fr/formalities-procedures/taxes/VAT-sanctions-remedies.html" target="_blank" rel="noopener">Sanctions TVA et voies de recours</a></li>
    <li><a href="https://eur-lex.europa.eu/legal-content/FR/TXT/?uri=CELEX:32006L0112" target="_blank" rel="noopener">Directive 2006/112/CE - articles 138, 146, 196, 226</a></li>
</ul>

<p class="text-sm text-slate-500 mt-6"><em>Article mis à jour le 30 juillet 2026.</em></p>

<div class="mt-8 p-4 bg-slate-50 rounded-xl"><h3 class="text-base font-semibold text-slate-900 mb-3">Articles connexes</h3><ul class="space-y-1"><li><a href="/fr/blog/note-de-credit-luxembourg-comment-etablir" class="text-primary-500 hover:text-primary-600 text-sm">Note de crédit Luxembourg →</a></li><li><a href="/fr/blog/article-63-liva-numerotation-sequentielle-factures-luxembourg-obligatoire" class="text-primary-500 hover:text-primary-600 text-sm">Numérotation séquentielle (article 63 LIVA) →</a></li><li><a href="/fr/blog/franchise-tva-luxembourg-seuil-obligations-regime-normal" class="text-primary-500 hover:text-primary-600 text-sm">Franchise TVA Luxembourg (seuil 50 000 €) →</a></li></ul></div>
HTML;
    }

    private function de(): string
    {
        $sample = $this->sample([
            'company' => 'Ihre Gesellschaft SARL', 'invoice' => 'RECHNUNG', 'date' => 'Datum',
            'billed' => 'Rechnung an:', 'client' => 'Kunde Unternehmen SA', 'desc' => 'Beschreibung',
            'qty' => 'Menge', 'unit' => 'EP netto', 'net' => 'Netto', 'line' => 'Beratungsleistung',
            'totalNet' => 'Nettobetrag', 'totalGross' => 'Bruttobetrag',
        ]);

        return <<<HTML
<div data-ai-summary class="bg-slate-50 border border-slate-200 rounded-xl p-5 my-4"><p class="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-2">Kurz gefasst</p><ul class="list-disc pl-5 space-y-1 text-slate-700 text-sm"><li>Die Pflichtangaben ergeben sich aus <strong>Artikel 63 LIVA</strong>: Identität und Anschrift der Parteien, MwSt.-Nummern, Datum, <strong>eindeutige fortlaufende Nummer</strong>, Bezeichnung, Nettobasis, Satz, MwSt.-Betrag, Bruttobetrag.</li><li>Eine <strong>nicht konforme</strong> Rechnung kann vom Kunden zurückgewiesen werden und ein <strong>AED-Bußgeld von 250 € bis 10 000 €</strong> nach sich ziehen (Art. 77 LIVA) — oder <strong>10 % bis 50 % der betroffenen MwSt.</strong>, wenn dem Fiskus Einnahmen entgangen sind.</li><li>Bedingte Angaben: <strong>Reverse Charge</strong> (Art. 196 Richtlinie), Freigrenze, Befreiungen.</li><li><strong>Aufbewahrung 10 Jahre</strong> für Rechnungen und Buchhaltungsunterlagen.</li></ul></div>
<p class="lead">Eine nicht konforme Rechnung kann von Ihrem Kunden zurückgewiesen werden und Sie AED-Bußgeldern aussetzen. Hier ist die vollständige Checkliste der Pflichtangaben für einwandfreie luxemburgische Rechnungen — auf Grundlage von <a href="https://legilux.public.lu/eli/etat/leg/loi/1979/02/12/n1/jo" target="_blank" rel="noopener">Artikel 63 LIVA</a> und der von der AED veröffentlichten Liste.</p>

<h2>Checkliste der Pflichtangaben</h2>

<p><strong>Artikel 63 LIVA</strong> (geändertes Gesetz vom 12. Februar 1979) und die Liste der AED verlangen Folgendes:</p>

<div class="bg-white border-2 border-purple-200 rounded-xl p-6 my-6">
    <h3 class="text-lg font-bold text-purple-800 mb-4">✅ Angaben zum Aussteller</h3>
    <ul class="space-y-2">
        <li>☐ <strong>Vollständiger Name oder Firmenbezeichnung</strong></li>
        <li>☐ <strong>Vollständige Anschrift</strong> des Sitzes</li>
        <li>☐ <strong>Innergemeinschaftliche MwSt.-Nummer</strong> (Format LU + 8 Ziffern)</li>
        <li>☐ <strong>RCS-Nummer</strong> für eingetragene Kaufleute und Gesellschaften — eine Pflicht aus dem <em>Recht des Handels- und Gesellschaftsregisters</em>, nicht aus dem MwSt.-Gesetz</li>
    </ul>
</div>

<div class="bg-white border-2 border-purple-200 rounded-xl p-6 my-6">
    <h3 class="text-lg font-bold text-purple-800 mb-4">✅ Angaben zum Kunden</h3>
    <ul class="space-y-2">
        <li>☐ <strong>Vollständiger Name oder Firmenbezeichnung</strong></li>
        <li>☐ <strong>Vollständige Anschrift</strong></li>
        <li>☐ <strong>MwSt.-Nummer</strong> (verpflichtend bei innergemeinschaftlichem B2B - über <a href="https://ec.europa.eu/taxation_customs/vies/" target="_blank" rel="noopener">VIES</a> geprüft)</li>
    </ul>
</div>

<div class="bg-white border-2 border-purple-200 rounded-xl p-6 my-6">
    <h3 class="text-lg font-bold text-purple-800 mb-4">✅ Angaben zur Rechnung</h3>
    <ul class="space-y-2">
        <li>☐ <strong>Eindeutige, fortlaufende Rechnungsnummer</strong> (Art. 63 LIVA, Ziffer 3°)</li>
        <li>☐ <strong>Ausstellungsdatum</strong> der Rechnung</li>
        <li>☐ <strong>Liefer- oder Leistungsdatum</strong> — gesetzlich gefordert, <em>wenn es abweicht</em> vom Ausstellungsdatum (Art. 226 §7 der Richtlinie). Es systematisch anzugeben bleibt gute Praxis: es beseitigt bei einer Prüfung jede Unklarheit über den Steuertatbestand</li>
    </ul>
</div>

<div class="bg-white border-2 border-purple-200 rounded-xl p-6 my-6">
    <h3 class="text-lg font-bold text-purple-800 mb-4">✅ Einzelheiten zu Waren und Leistungen</h3>
    <ul class="space-y-2">
        <li>☐ <strong>Klare, ausführliche Beschreibung</strong></li>
        <li>☐ <strong>Gelieferte oder erbrachte Menge</strong></li>
        <li>☐ <strong>Nettoeinzelpreis</strong></li>
        <li>☐ <strong>Etwaige Nachlässe oder Rabatte</strong></li>
    </ul>
</div>

<div class="bg-white border-2 border-purple-200 rounded-xl p-6 my-6">
    <h3 class="text-lg font-bold text-purple-800 mb-4">✅ MwSt.-Angaben</h3>
    <ul class="space-y-2">
        <li>☐ <strong>Anwendbarer MwSt.-Satz</strong> je Position (17 %, 14 %, 8 %, 3 % oder 0 %)</li>
        <li>☐ <strong>MwSt.-Betrag</strong> je Satz</li>
        <li>☐ <strong>Bemessungsgrundlage</strong> (Nettobetrag) je MwSt.-Satz</li>
        <li>☐ <strong>Befreiungs- oder Reverse-Charge-Vermerk</strong>, falls einschlägig (siehe Tabelle unten)</li>
    </ul>
</div>

<div class="bg-white border-2 border-purple-200 rounded-xl p-6 my-6">
    <h3 class="text-lg font-bold text-purple-800 mb-4">✅ Summen</h3>
    <ul class="space-y-2">
        <li>☐ <strong>Nettosumme</strong></li>
        <li>☐ <strong>MwSt.-Summe</strong></li>
        <li>☐ <strong>Bruttosumme</strong></li>
    </ul>
</div>

<h2>Bedingte Angaben je nach Fall</h2>

<p>Je nach Art des Umsatzes muss ein bestimmter Vermerk auf der Rechnung stehen. Hier die kodifizierten Formulierungen:</p>

<table class="w-full border-collapse border border-gray-300 my-6">
    <thead class="bg-gray-100">
        <tr>
            <th class="border border-gray-300 px-4 py-2 text-left">Situation</th>
            <th class="border border-gray-300 px-4 py-2 text-left">Anzubringender Vermerk</th>
            <th class="border border-gray-300 px-4 py-2 text-left">Rechtsgrundlage</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td class="border border-gray-300 px-4 py-2">B2B-Dienstleistung innergemeinschaftlich (Empfänger in einem anderen Mitgliedstaat)</td>
            <td class="border border-gray-300 px-4 py-2 font-mono text-sm">„Umkehrung der Steuerschuldnerschaft – Artikel 196 der Richtlinie 2006/112/EG"</td>
            <td class="border border-gray-300 px-4 py-2 text-sm">Art. 17 LIVA (Ort) + Art. 196 Richtlinie (Steuerschuldner) + Art. 226 §11bis (Vermerk)</td>
        </tr>
        <tr>
            <td class="border border-gray-300 px-4 py-2">Innergemeinschaftliche B2B-Warenlieferung</td>
            <td class="border border-gray-300 px-4 py-2 font-mono text-sm">„MwSt.-Befreiung – Artikel 138 der Richtlinie 2006/112/EG"</td>
            <td class="border border-gray-300 px-4 py-2 text-sm">Art. 43 §1 d) LIVA</td>
        </tr>
        <tr>
            <td class="border border-gray-300 px-4 py-2">Ausfuhr außerhalb der EU</td>
            <td class="border border-gray-300 px-4 py-2 font-mono text-sm">„MwSt.-Befreiung – Artikel 146 der Richtlinie 2006/112/EG"</td>
            <td class="border border-gray-300 px-4 py-2 text-sm">Art. 43 §1 a) LIVA</td>
        </tr>
        <tr>
            <td class="border border-gray-300 px-4 py-2">MwSt.-Freigrenze (Kleinunternehmen)</td>
            <td class="border border-gray-300 px-4 py-2 font-mono text-sm">„MwSt. nicht anwendbar – Artikel 57bis des geänderten Gesetzes vom 12. Februar 1979"</td>
            <td class="border border-gray-300 px-4 py-2 text-sm">Art. 57bis LIVA (Schwelle 50 000 €)</td>
        </tr>
    </tbody>
</table>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">Nicht zu verwechseln</p>
    <p>Für den innergemeinschaftlichen B2B-Reverse-Charge übernehmen viele Vorlagen den Verweis auf „Artikel 44 der Richtlinie 2006/112/EG". Artikel 44 bestimmt den <strong>Ort der Besteuerung</strong> (beim Empfänger), der kodifizierte Pflichtvermerk verweist jedoch auf <strong>Artikel 196</strong> (der den Empfänger als Steuerschuldner benennt). Bevorzugen Sie den Vermerk zu Artikel 196.</p>
</div>

<h3>Sonderfälle der Fakturierung</h3>

<p><strong>Anzahlungsrechnung</strong>:</p>

<div class="bg-blue-50 border-l-4 border-blue-500 p-4 my-4">
    <p class="font-mono text-sm">„Anzahlung auf Bestellung Nr. [Referenz] vom [Datum]"</p>
</div>

<p><strong>Gutschrift (Stornorechnung)</strong>:</p>

<div class="bg-blue-50 border-l-4 border-blue-500 p-4 my-4">
    <p class="font-mono text-sm">„Gutschrift zu Rechnung Nr. [Nummer] vom [Datum]"</p>
</div>

<h2>Die Nummerierung der Rechnungen (Art. 63 LIVA, Ziffer 3°)</h2>

<p>Die Nummerierung muss diese Regeln einhalten:</p>

<ul>
    <li><strong>Eindeutig</strong>: jede Rechnung hat eine eigene Nummer</li>
    <li><strong>Chronologisch</strong>: die Nummern folgen der Reihenfolge der Ausstellung</li>
    <li><strong>Lückenlos</strong>: keine Lücke in der Sequenz</li>
    <li><strong>Nicht wiederverwendbar</strong>: eine Nummer darf nur einmal vergeben werden</li>
</ul>

<h3>Beispiele zulässiger Formate</h3>

<table class="w-full border-collapse border border-gray-300 my-6">
    <thead class="bg-gray-100">
        <tr>
            <th class="border border-gray-300 px-4 py-2 text-left">Format</th>
            <th class="border border-gray-300 px-4 py-2 text-left">Beispiel</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td class="border border-gray-300 px-4 py-2">Jahr + Nummer</td>
            <td class="border border-gray-300 px-4 py-2 font-mono">2026-0001, 2026-0002</td>
        </tr>
        <tr>
            <td class="border border-gray-300 px-4 py-2">Präfix + Nummer</td>
            <td class="border border-gray-300 px-4 py-2 font-mono">FAC-001, FAC-002</td>
        </tr>
        <tr>
            <td class="border border-gray-300 px-4 py-2">Einfache Nummer</td>
            <td class="border border-gray-300 px-4 py-2 font-mono">00001, 00002</td>
        </tr>
    </tbody>
</table>

<h2>Zahlungsbedingungen</h2>

<p>Auch wenn Artikel 63 LIVA sie nicht verlangt, sind diese Angaben <strong>dringend zu empfehlen</strong>:</p>

<ul>
    <li><strong>Zahlungsfrist</strong> (standardmäßig: 30 Tage nach Eingang, geändertes Gesetz vom 18. April 2004)</li>
    <li><strong>Fälligkeitsdatum</strong></li>
    <li><strong>Bankverbindung</strong> (IBAN, BIC)</li>
    <li><strong>Verzugszinsen</strong> zwischen Unternehmern — EZB-Referenzsatz + 8 Punkte, also <strong>10,15 % im 1. Halbjahr 2026</strong>, halbjährlich angepasst — und <strong>Pauschale von 40 €</strong></li>
</ul>

<h2>Aufbewahrung der Rechnungen</h2>

<p>Sie müssen Ihre Rechnungen (ausgestellte und erhaltene) <strong>10 Jahre</strong> ab Abschluss des Geschäftsjahres aufbewahren — Artikel 16 des Handelsgesetzbuchs und Artikel 65 LIVA. In Papierform oder elektronisch (PDF/A mit Integritätsgarantien). Siehe die <a href="https://guichet.public.lu/fr/entreprises/gestion-juridique-comptabilite/comptable/enregistrement/obligations-comptables.html" target="_blank" rel="noopener">Buchführungspflichten auf Guichet.lu</a>.</p>

<h2>Folgen einer nicht konformen Rechnung</h2>

<div class="bg-red-50 border-l-4 border-red-500 p-4 my-6">
    <p class="font-semibold text-red-800">⚠️ Bestehende Risiken</p>
    <ul class="text-red-700 mt-2">
        <li>Ablehnung des Vorsteuerabzugs durch Ihren Kunden</li>
        <li><strong>Verwaltungsbußgeld von 250 € bis 10 000 € je Verstoß</strong> (Art. 77 LIVA)</li>
        <li>Hat der Verstoß zu einer Nichtzahlung von MwSt. oder einer unrechtmäßigen Erstattung geführt: <strong>Geldbuße von 10 % bis 50 % der betroffenen MwSt.</strong> — anteilig, also ohne Obergrenze</li>
        <li>Bei Weigerung, Rechnungen und Buchhaltungsunterlagen bei einer Prüfung vorzulegen: <strong>bis zu 25 000 € pro Verzugstag</strong>, nach Verwarnung</li>
        <li>Bei schwerer Steuerhinterziehung oder Steuerbetrug: Geldstrafe von 25 000 € bis zum Zehnfachen der MwSt., Freiheitsstrafe von einem Monat bis fünf Jahren und Aberkennung der bürgerlichen Rechte für 5 bis 10 Jahre (Art. 80 LIVA)</li>
    </ul>
</div>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">Die Obergrenze von 10 000 € ist nicht das größte Risiko</p>
    <p>Wehtun tut die anteilige Geldbuße. Bei einem Streit über 80 000 € MwSt. bedeuten 10 bis 50 % zwischen 8 000 und 40 000 € — unabhängig davon, wie viele formale Verstöße festgestellt werden.</p>
</div>

<h2>Beispiel einer konformen Rechnung</h2>

<p>Hier die wesentlichen Bestandteile einer konformen Rechnung:</p>

{$sample}

<h2>Machen Sie es sich einfach mit faktur.lu</h2>

<p>Konforme Rechnungen von Hand zu erstellen ist fehleranfällig. <strong>faktur.lu</strong> automatisiert die Konformität:</p>

<ul>
    <li>✅ Alle Pflichtangaben vorausgefüllt</li>
    <li>✅ Automatische, fortlaufende Nummerierung (Art. 63 LIVA)</li>
    <li>✅ Automatische MwSt.-Berechnung je nach Fall</li>
    <li>✅ Passende Rechtsvermerke (Reverse Charge, Ausfuhr, Freigrenze 57bis…)</li>
    <li>✅ Integrierter FAIA-Export für Steuerprüfungen der AED</li>
</ul>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">Jährlich zu prüfen</p>
    <p>Pflichtangaben, Artikelverweise und Schwellen können sich ändern. Diese Seite wird regelmäßig aktualisiert – für Ihre persönliche Situation wenden Sie sich an Ihren Treuhänder oder direkt an die <a href="https://pfi.public.lu/" target="_blank" rel="noopener">AED</a>.</p>
</div>

<h2>Offizielle Quellen</h2>

<ul>
    <li><a href="https://legilux.public.lu/eli/etat/leg/loi/1979/02/12/n1/jo" target="_blank" rel="noopener">Geändertes Gesetz vom 12. Februar 1979 (LIVA) – Artikel 17, 43, 57bis, 63, 65, 77, 80</a></li>
    <li><a href="https://pfi.public.lu/fr/professionnel/tva/en-cours-activite-economique/que-doivent-contenir-factures.html" target="_blank" rel="noopener">AED – Pflichtangaben auf Rechnungen</a></li>
    <li><a href="https://logistics.public.lu/fr/formalities-procedures/taxes/VAT-sanctions-remedies.html" target="_blank" rel="noopener">MwSt.-Sanktionen und Rechtsbehelfe</a></li>
    <li><a href="https://eur-lex.europa.eu/legal-content/DE/TXT/?uri=CELEX:32006L0112" target="_blank" rel="noopener">Richtlinie 2006/112/EG – Artikel 138, 146, 196, 226</a></li>
</ul>

<p class="text-sm text-slate-500 mt-6"><em>Artikel aktualisiert am 30. Juli 2026.</em></p>

<div class="mt-8 p-4 bg-slate-50 rounded-xl"><h3 class="text-base font-semibold text-slate-900 mb-3">Verwandte Artikel</h3><ul class="space-y-1"><li><a href="/de/blog/note-de-credit-luxembourg-comment-etablir" class="text-primary-500 hover:text-primary-600 text-sm">Gutschrift Luxemburg →</a></li><li><a href="/de/blog/article-63-liva-numerotation-sequentielle-factures-luxembourg-obligatoire" class="text-primary-500 hover:text-primary-600 text-sm">Fortlaufende Nummerierung (Artikel 63 LIVA) →</a></li><li><a href="/de/blog/franchise-tva-luxembourg-seuil-obligations-regime-normal" class="text-primary-500 hover:text-primary-600 text-sm">MwSt.-Freigrenze Luxemburg (50 000 €) →</a></li></ul></div>
HTML;
    }

    private function en(): string
    {
        $sample = $this->sample([
            'company' => 'Your Company SARL', 'invoice' => 'INVOICE', 'date' => 'Date',
            'billed' => 'Billed to:', 'client' => 'Client Company SA', 'desc' => 'Description',
            'qty' => 'Qty', 'unit' => 'Unit price', 'net' => 'Net total', 'line' => 'Consulting services',
            'totalNet' => 'Net total', 'totalGross' => 'Gross total',
        ]);

        return <<<HTML
<div data-ai-summary class="bg-slate-50 border border-slate-200 rounded-xl p-5 my-4"><p class="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-2">In brief</p><ul class="list-disc pl-5 space-y-1 text-slate-700 text-sm"><li>Mandatory details come from <strong>article 63 LIVA</strong>: identity and address of the parties, VAT numbers, date, <strong>unique sequential number</strong>, description, net base, rate, VAT amount, gross total.</li><li>A <strong>non-compliant</strong> invoice can be rejected by the client and lead to an <strong>AED fine of €250 to €10,000</strong> (art. 77 LIVA) — or <strong>10% to 50% of the VAT at stake</strong> where the Treasury lost revenue.</li><li>Conditional wording: <strong>reverse charge</strong> (art. 196 Directive), exemption regime, exemptions.</li><li><strong>10-year retention</strong> of invoices and accounting records.</li></ul></div>
<p class="lead">A non-compliant invoice can be rejected by your client and expose you to AED fines. Here is the complete checklist of mandatory details for flawless Luxembourg invoices — based on <a href="https://legilux.public.lu/eli/etat/leg/loi/1979/02/12/n1/jo" target="_blank" rel="noopener">article 63 LIVA</a> and the list published by the AED.</p>

<h2>Checklist of mandatory details</h2>

<p><strong>Article 63 LIVA</strong> (amended law of 12 February 1979) and the AED's list require the following:</p>

<div class="bg-white border-2 border-purple-200 rounded-xl p-6 my-6">
    <h3 class="text-lg font-bold text-purple-800 mb-4">✅ Issuer details</h3>
    <ul class="space-y-2">
        <li>☐ <strong>Full name or business name</strong></li>
        <li>☐ <strong>Full address</strong> of the registered office</li>
        <li>☐ <strong>Intra-community VAT number</strong> (format LU + 8 digits)</li>
        <li>☐ <strong>RCS number</strong> for registered traders and companies — an obligation arising from <em>trade and companies register legislation</em>, not from VAT law</li>
    </ul>
</div>

<div class="bg-white border-2 border-purple-200 rounded-xl p-6 my-6">
    <h3 class="text-lg font-bold text-purple-800 mb-4">✅ Client details</h3>
    <ul class="space-y-2">
        <li>☐ <strong>Full name or business name</strong></li>
        <li>☐ <strong>Full address</strong></li>
        <li>☐ <strong>VAT number</strong> (mandatory for intra-community B2B - validated through <a href="https://ec.europa.eu/taxation_customs/vies/" target="_blank" rel="noopener">VIES</a>)</li>
    </ul>
</div>

<div class="bg-white border-2 border-purple-200 rounded-xl p-6 my-6">
    <h3 class="text-lg font-bold text-purple-800 mb-4">✅ Invoice details</h3>
    <ul class="space-y-2">
        <li>☐ <strong>Unique, sequential invoice number</strong> (art. 63 LIVA, point 3°)</li>
        <li>☐ <strong>Date of issue</strong> of the invoice</li>
        <li>☐ <strong>Date of delivery or supply</strong> — legally required <em>where it differs</em> from the date of issue (art. 226 §7 of the Directive). Stating it systematically remains good practice: it removes any ambiguity about the chargeable event during an audit</li>
    </ul>
</div>

<div class="bg-white border-2 border-purple-200 rounded-xl p-6 my-6">
    <h3 class="text-lg font-bold text-purple-800 mb-4">✅ Details of goods or services</h3>
    <ul class="space-y-2">
        <li>☐ <strong>Clear, detailed description</strong></li>
        <li>☐ <strong>Quantity</strong> delivered or supplied</li>
        <li>☐ <strong>Unit price excluding VAT</strong></li>
        <li>☐ <strong>Any discounts or rebates</strong></li>
    </ul>
</div>

<div class="bg-white border-2 border-purple-200 rounded-xl p-6 my-6">
    <h3 class="text-lg font-bold text-purple-800 mb-4">✅ VAT details</h3>
    <ul class="space-y-2">
        <li>☐ <strong>Applicable VAT rate</strong> per line (17%, 14%, 8%, 3% or 0%)</li>
        <li>☐ <strong>VAT amount</strong> per rate</li>
        <li>☐ <strong>Taxable base</strong> (net amount) per VAT rate</li>
        <li>☐ <strong>Exemption or reverse charge wording</strong> where applicable (see table below)</li>
    </ul>
</div>

<div class="bg-white border-2 border-purple-200 rounded-xl p-6 my-6">
    <h3 class="text-lg font-bold text-purple-800 mb-4">✅ Totals</h3>
    <ul class="space-y-2">
        <li>☐ <strong>Net total</strong></li>
        <li>☐ <strong>Total VAT</strong></li>
        <li>☐ <strong>Gross total</strong></li>
    </ul>
</div>

<h2>Conditional wording by situation</h2>

<p>Depending on the nature of the transaction, specific wording must appear on the invoice. Here are the codified formulations:</p>

<table class="w-full border-collapse border border-gray-300 my-6">
    <thead class="bg-gray-100">
        <tr>
            <th class="border border-gray-300 px-4 py-2 text-left">Situation</th>
            <th class="border border-gray-300 px-4 py-2 text-left">Wording to use</th>
            <th class="border border-gray-300 px-4 py-2 text-left">Legal basis</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td class="border border-gray-300 px-4 py-2">Intra-EU B2B service (customer in another Member State)</td>
            <td class="border border-gray-300 px-4 py-2 font-mono text-sm">"Reverse charge - Article 196 of Directive 2006/112/EC"</td>
            <td class="border border-gray-300 px-4 py-2 text-sm">Art. 17 LIVA (place) + art. 196 Directive (liability) + art. 226 §11bis (wording)</td>
        </tr>
        <tr>
            <td class="border border-gray-300 px-4 py-2">Intra-EU B2B supply of goods</td>
            <td class="border border-gray-300 px-4 py-2 font-mono text-sm">"VAT exemption - Article 138 of Directive 2006/112/EC"</td>
            <td class="border border-gray-300 px-4 py-2 text-sm">Art. 43 §1 d) LIVA</td>
        </tr>
        <tr>
            <td class="border border-gray-300 px-4 py-2">Export outside the EU</td>
            <td class="border border-gray-300 px-4 py-2 font-mono text-sm">"VAT exemption - Article 146 of Directive 2006/112/EC"</td>
            <td class="border border-gray-300 px-4 py-2 text-sm">Art. 43 §1 a) LIVA</td>
        </tr>
        <tr>
            <td class="border border-gray-300 px-4 py-2">VAT exemption regime (small businesses)</td>
            <td class="border border-gray-300 px-4 py-2 font-mono text-sm">"VAT not applicable - Article 57bis of the amended law of 12 February 1979"</td>
            <td class="border border-gray-300 px-4 py-2 text-sm">Art. 57bis LIVA (€50,000 threshold)</td>
        </tr>
    </tbody>
</table>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">Not to be confused</p>
    <p>For intra-EU B2B reverse charge, many templates copy the reference to "article 44 of Directive 2006/112/EC". Article 44 defines the <strong>place of supply</strong> (the customer's), but the codified mandatory wording refers to <strong>article 196</strong> (which designates the customer as liable). Prefer the article 196 wording.</p>
</div>

<h3>Special invoicing cases</h3>

<p><strong>Deposit invoice</strong>:</p>

<div class="bg-blue-50 border-l-4 border-blue-500 p-4 my-4">
    <p class="font-mono text-sm">"Deposit on order no. [reference] dated [date]"</p>
</div>

<p><strong>Credit note</strong>:</p>

<div class="bg-blue-50 border-l-4 border-blue-500 p-4 my-4">
    <p class="font-mono text-sm">"Credit note on invoice no. [number] dated [date]"</p>
</div>

<h2>Invoice numbering (art. 63 LIVA, point 3°)</h2>

<p>Numbering must follow these rules:</p>

<ul>
    <li><strong>Unique</strong>: each invoice has a distinct number</li>
    <li><strong>Chronological</strong>: numbers follow the order of issue</li>
    <li><strong>Unbroken</strong>: no gap in the sequence</li>
    <li><strong>Non-reusable</strong>: a number may be assigned only once</li>
</ul>

<h3>Examples of accepted formats</h3>

<table class="w-full border-collapse border border-gray-300 my-6">
    <thead class="bg-gray-100">
        <tr>
            <th class="border border-gray-300 px-4 py-2 text-left">Format</th>
            <th class="border border-gray-300 px-4 py-2 text-left">Example</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td class="border border-gray-300 px-4 py-2">Year + number</td>
            <td class="border border-gray-300 px-4 py-2 font-mono">2026-0001, 2026-0002</td>
        </tr>
        <tr>
            <td class="border border-gray-300 px-4 py-2">Prefix + number</td>
            <td class="border border-gray-300 px-4 py-2 font-mono">FAC-001, FAC-002</td>
        </tr>
        <tr>
            <td class="border border-gray-300 px-4 py-2">Simple number</td>
            <td class="border border-gray-300 px-4 py-2 font-mono">00001, 00002</td>
        </tr>
    </tbody>
</table>

<h2>Payment terms</h2>

<p>Although article 63 LIVA does not require them, these details are <strong>strongly recommended</strong>:</p>

<ul>
    <li><strong>Payment period</strong> (default: 30 days after receipt, amended law of 18 April 2004)</li>
    <li><strong>Due date</strong></li>
    <li><strong>Bank details</strong> (IBAN, BIC)</li>
    <li><strong>Late payment interest</strong> applicable between businesses — ECB reference rate + 8 points, i.e. <strong>10.15% in the first half of 2026</strong>, revised every six months — and the <strong>€40 fixed compensation</strong></li>
</ul>

<h2>Retaining invoices</h2>

<p>You must keep your invoices (issued and received) for <strong>10 years</strong> from the close of the financial year — article 16 of the Commercial Code and article 65 LIVA. Paper or electronic format (PDF/A with integrity guarantees). See the <a href="https://guichet.public.lu/fr/entreprises/gestion-juridique-comptabilite/comptable/enregistrement/obligations-comptables.html" target="_blank" rel="noopener">accounting obligations on Guichet.lu</a>.</p>

<h2>Consequences of a non-compliant invoice</h2>

<div class="bg-red-50 border-l-4 border-red-500 p-4 my-6">
    <p class="font-semibold text-red-800">⚠️ Risks incurred</p>
    <ul class="text-red-700 mt-2">
        <li>Your client's VAT deduction refused</li>
        <li><strong>Administrative fine of €250 to €10,000 per infringement</strong> (art. 77 LIVA)</li>
        <li>Where the breach led to unpaid VAT or an irregular refund: <strong>a fine of 10% to 50% of the VAT at stake</strong> — proportional, hence uncapped</li>
        <li>Where invoices and accounting records are not produced during an audit: <strong>up to €25,000 per day of delay</strong>, after a warning</li>
        <li>In cases of aggravated tax fraud or tax swindling: criminal fine of €25,000 to ten times the VAT amount, imprisonment from one month to five years, and loss of civic rights for 5 to 10 years (art. 80 LIVA)</li>
    </ul>
</div>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">The €10,000 cap is not the worst case</p>
    <p>It is the proportional fine that hurts. On a dispute involving €80,000 of VAT, a 10–50% penalty means €8,000 to €40,000 — regardless of how many formal breaches are recorded.</p>
</div>

<h2>Example of a compliant invoice</h2>

<p>Here are the essential elements of a compliant invoice:</p>

{$sample}

<h2>Make life easier with faktur.lu</h2>

<p>Creating compliant invoices manually invites mistakes. <strong>faktur.lu</strong> automates compliance:</p>

<ul>
    <li>✅ All mandatory details pre-filled</li>
    <li>✅ Automatic sequential numbering (art. 63 LIVA)</li>
    <li>✅ Automatic VAT calculation for each case</li>
    <li>✅ Appropriate legal wording (reverse charge, export, 57bis exemption…)</li>
    <li>✅ Built-in FAIA export for AED tax audits</li>
</ul>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">To check every year</p>
    <p>Mandatory details, article references and thresholds may change. This page is updated regularly, but for your own situation consult your accountant or the <a href="https://pfi.public.lu/" target="_blank" rel="noopener">AED</a> directly.</p>
</div>

<h2>Official sources</h2>

<ul>
    <li><a href="https://legilux.public.lu/eli/etat/leg/loi/1979/02/12/n1/jo" target="_blank" rel="noopener">Amended law of 12 February 1979 (LIVA) - articles 17, 43, 57bis, 63, 65, 77, 80</a></li>
    <li><a href="https://pfi.public.lu/fr/professionnel/tva/en-cours-activite-economique/que-doivent-contenir-factures.html" target="_blank" rel="noopener">AED - Mandatory invoice details</a></li>
    <li><a href="https://logistics.public.lu/fr/formalities-procedures/taxes/VAT-sanctions-remedies.html" target="_blank" rel="noopener">VAT sanctions and remedies</a></li>
    <li><a href="https://eur-lex.europa.eu/legal-content/EN/TXT/?uri=CELEX:32006L0112" target="_blank" rel="noopener">Directive 2006/112/EC - articles 138, 146, 196, 226</a></li>
</ul>

<p class="text-sm text-slate-500 mt-6"><em>Article updated on 30 July 2026.</em></p>

<div class="mt-8 p-4 bg-slate-50 rounded-xl"><h3 class="text-base font-semibold text-slate-900 mb-3">Related articles</h3><ul class="space-y-1"><li><a href="/en/blog/note-de-credit-luxembourg-comment-etablir" class="text-primary-500 hover:text-primary-600 text-sm">Credit notes in Luxembourg →</a></li><li><a href="/en/blog/article-63-liva-numerotation-sequentielle-factures-luxembourg-obligatoire" class="text-primary-500 hover:text-primary-600 text-sm">Sequential numbering (article 63 LIVA) →</a></li><li><a href="/en/blog/franchise-tva-luxembourg-seuil-obligations-regime-normal" class="text-primary-500 hover:text-primary-600 text-sm">Luxembourg VAT exemption (€50,000 threshold) →</a></li></ul></div>
HTML;
    }

    private function lb(): string
    {
        $sample = $this->sample([
            'company' => 'Är Gesellschaft SARL', 'invoice' => 'RECHNUNG', 'date' => 'Datum',
            'billed' => 'Rechnung un:', 'client' => 'Client Entreprise SA', 'desc' => 'Beschreiwung',
            'qty' => 'Quantitéit', 'unit' => 'Eenheetspräis', 'net' => 'Total ouni TVA', 'line' => 'Beroodungsleeschtung',
            'totalNet' => 'Total ouni TVA', 'totalGross' => 'Total mat TVA',
        ]);

        return <<<HTML
<div data-ai-summary class="bg-slate-50 border border-slate-200 rounded-xl p-5 my-4"><p class="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-2">A kuerz</p><ul class="list-disc pl-5 space-y-1 text-slate-700 text-sm"><li>D'obligatoresch Mentiounen ergi sech aus dem <strong>Artikel 63 LIVA</strong>: Identitéit an Adress vun de Parteien, TVA-Nummeren, Datum, <strong>eenzeg fortlafend Nummer</strong>, Bezeechnung, Basis ouni TVA, Taux, TVA-Betrag, Gesamtbetrag.</li><li>Eng <strong>net konform</strong> Rechnung kann vum Client zréckgewise ginn an eng <strong>AED-Geldstrof vun 250 € bis 10 000 €</strong> no sech zéien (Art. 77 LIVA) — oder <strong>10 % bis 50 % vun der betraffener TVA</strong>, wann de Staat Recetten verluer huet.</li><li>Bedingt Mentiounen: <strong>Autoliquidatioun</strong> (Art. 196 Direktiv), Franchise, Befreiungen.</li><li><strong>Opbewahrung 10 Joer</strong> vun de Rechnungen a comptabele Stécker.</li></ul></div>
<p class="lead">Eng net konform Rechnung kann vun Ärem Client zréckgewise ginn an Iech AED-Geldstrofen aussetzen. Hei ass déi komplett Checklëscht vun den obligatoresche Mentiounen fir onbeanstandbar Lëtzebuerger Rechnungen — op Basis vum <a href="https://legilux.public.lu/eli/etat/leg/loi/1979/02/12/n1/jo" target="_blank" rel="noopener">Artikel 63 LIVA</a> an der Lëscht, déi d'AED publizéiert huet.</p>

<h2>Checklëscht vun den obligatoresche Mentiounen</h2>

<p>Den <strong>Artikel 63 LIVA</strong> (geännert Gesetz vum 12. Februar 1979) an d'Lëscht vun der AED verlaange Folgendes:</p>

<div class="bg-white border-2 border-purple-200 rounded-xl p-6 my-6">
    <h3 class="text-lg font-bold text-purple-800 mb-4">✅ Informatiounen iwwer den Emetteur</h3>
    <ul class="space-y-2">
        <li>☐ <strong>Vollstännege Numm oder Firmennumm</strong></li>
        <li>☐ <strong>Vollstänneg Adress</strong> vum Sëtz</li>
        <li>☐ <strong>Innergemeinschaftlech TVA-Nummer</strong> (Format LU + 8 Zifferen)</li>
        <li>☐ <strong>RCS-Nummer</strong> fir agedroen Händler a Gesellschaften — eng Flicht aus der <em>Legislatioun iwwer d'Handels- a Gesellschaftsregëster</em>, net aus dem TVA-Gesetz</li>
    </ul>
</div>

<div class="bg-white border-2 border-purple-200 rounded-xl p-6 my-6">
    <h3 class="text-lg font-bold text-purple-800 mb-4">✅ Informatiounen iwwer de Client</h3>
    <ul class="space-y-2">
        <li>☐ <strong>Vollstännege Numm oder Firmennumm</strong></li>
        <li>☐ <strong>Vollstänneg Adress</strong></li>
        <li>☐ <strong>TVA-Nummer</strong> (obligatoresch fir innergemeinschaftlecht B2B - iwwer <a href="https://ec.europa.eu/taxation_customs/vies/" target="_blank" rel="noopener">VIES</a> validéiert)</li>
    </ul>
</div>

<div class="bg-white border-2 border-purple-200 rounded-xl p-6 my-6">
    <h3 class="text-lg font-bold text-purple-800 mb-4">✅ Informatiounen iwwer d'Rechnung</h3>
    <ul class="space-y-2">
        <li>☐ <strong>Eenzeg a fortlafend Rechnungsnummer</strong> (Art. 63 LIVA, Punkt 3°)</li>
        <li>☐ <strong>Ausstellungsdatum</strong> vun der Rechnung</li>
        <li>☐ <strong>Liwwer- oder Leeschtungsdatum</strong> — gesetzlech verlaangt, <em>wann et ofwäicht</em> vum Ausstellungsdatum (Art. 226 §7 vun der Direktiv). Et systematesch unzeginn bleift gutt Praxis: et hieft bei enger Kontroll all Ondäitlechkeet iwwer de Steiertatbestand op</li>
    </ul>
</div>

<div class="bg-white border-2 border-purple-200 rounded-xl p-6 my-6">
    <h3 class="text-lg font-bold text-purple-800 mb-4">✅ Detail vun de Wueren oder Déngschter</h3>
    <ul class="space-y-2">
        <li>☐ <strong>Kloer an detailléiert Beschreiwung</strong></li>
        <li>☐ <strong>Geliwwert oder geleescht Quantitéit</strong></li>
        <li>☐ <strong>Eenheetspräis ouni TVA</strong></li>
        <li>☐ <strong>Eventuell Reduktiounen oder Rabatter</strong></li>
    </ul>
</div>

<div class="bg-white border-2 border-purple-200 rounded-xl p-6 my-6">
    <h3 class="text-lg font-bold text-purple-800 mb-4">✅ TVA-Informatiounen</h3>
    <ul class="space-y-2">
        <li>☐ <strong>Applikabelen TVA-Taux</strong> pro Linn (17 %, 14 %, 8 %, 3 % oder 0 %)</li>
        <li>☐ <strong>TVA-Betrag</strong> pro Taux</li>
        <li>☐ <strong>Steierbasis</strong> (Betrag ouni TVA) pro TVA-Taux</li>
        <li>☐ <strong>Mentioun vun der Befreiung oder Autoliquidatioun</strong>, wa relevant (kuckt d'Tabell hei ënnen)</li>
    </ul>
</div>

<div class="bg-white border-2 border-purple-200 rounded-xl p-6 my-6">
    <h3 class="text-lg font-bold text-purple-800 mb-4">✅ Totaler</h3>
    <ul class="space-y-2">
        <li>☐ <strong>Total ouni TVA</strong></li>
        <li>☐ <strong>Total TVA</strong></li>
        <li>☐ <strong>Total mat TVA</strong></li>
    </ul>
</div>

<h2>Bedingt Mentiounen no de Fäll</h2>

<p>No der Aart vun der Operatioun muss eng bestëmmte Mentioun op der Rechnung stoen. Hei sinn déi kodifizéiert Formuléierungen:</p>

<table class="w-full border-collapse border border-gray-300 my-6">
    <thead class="bg-gray-100">
        <tr>
            <th class="border border-gray-300 px-4 py-2 text-left">Situatioun</th>
            <th class="border border-gray-300 px-4 py-2 text-left">Mentioun</th>
            <th class="border border-gray-300 px-4 py-2 text-left">Gesetzlech Basis</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td class="border border-gray-300 px-4 py-2">B2B-Déngscht innergemeinschaftlech (Client an engem anere Member-Staat)</td>
            <td class="border border-gray-300 px-4 py-2 font-mono text-sm">„Autoliquidatioun – Artikel 196 vun der Direktiv 2006/112/EG"</td>
            <td class="border border-gray-300 px-4 py-2 text-sm">Art. 17 LIVA (Ort) + Art. 196 Direktiv (Schëllner) + Art. 226 §11bis (Mentioun)</td>
        </tr>
        <tr>
            <td class="border border-gray-300 px-4 py-2">Innergemeinschaftlech B2B-Wuerelieferung</td>
            <td class="border border-gray-300 px-4 py-2 font-mono text-sm">„TVA-Befreiung – Artikel 138 vun der Direktiv 2006/112/EG"</td>
            <td class="border border-gray-300 px-4 py-2 text-sm">Art. 43 §1 d) LIVA</td>
        </tr>
        <tr>
            <td class="border border-gray-300 px-4 py-2">Export ausserhalb vun der EU</td>
            <td class="border border-gray-300 px-4 py-2 font-mono text-sm">„TVA-Befreiung – Artikel 146 vun der Direktiv 2006/112/EG"</td>
            <td class="border border-gray-300 px-4 py-2 text-sm">Art. 43 §1 a) LIVA</td>
        </tr>
        <tr>
            <td class="border border-gray-300 px-4 py-2">TVA-Franchise (kleng Entreprisen)</td>
            <td class="border border-gray-300 px-4 py-2 font-mono text-sm">„TVA net applicabel – Artikel 57bis vum geännerte Gesetz vum 12. Februar 1979"</td>
            <td class="border border-gray-300 px-4 py-2 text-sm">Art. 57bis LIVA (Seuil 50 000 €)</td>
        </tr>
    </tbody>
</table>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">Net ze verwiesselen</p>
    <p>Fir d'innergemeinschaftlech B2B-Autoliquidatioun iwwerhuele vill Virlagen d'Referenz op den „Artikel 44 vun der Direktiv 2006/112/EG". Den Artikel 44 definéiert den <strong>Ort vun der Besteierung</strong> (beim Client), mä déi kodifizéiert obligatoresch Mentioun verweist op den <strong>Artikel 196</strong> (deen de Client als Schëllner bezeechent). Bevirzegt d'Mentioun Artikel 196.</p>
</div>

<h3>Besonnesch Fäll vun der Fakturatioun</h3>

<p><strong>Akontsrechnung</strong>:</p>

<div class="bg-blue-50 border-l-4 border-blue-500 p-4 my-4">
    <p class="font-mono text-sm">„Akont op d'Bestellung Nr. [Referenz] vum [Datum]"</p>
</div>

<p><strong>Avoir (Kreditnot)</strong>:</p>

<div class="bg-blue-50 border-l-4 border-blue-500 p-4 my-4">
    <p class="font-mono text-sm">„Avoir op d'Rechnung Nr. [Nummer] vum [Datum]"</p>
</div>

<h2>D'Nummeréierung vun de Rechnungen (Art. 63 LIVA, Punkt 3°)</h2>

<p>D'Nummeréierung muss dës Reegele respektéieren:</p>

<ul>
    <li><strong>Eenzeg</strong>: all Rechnung huet eng eege Nummer</li>
    <li><strong>Chronologesch</strong>: d'Nummere follegen der Uerdnung vun der Ausstellung</li>
    <li><strong>Ouni Ënnerbriechung</strong>: keng Lack an der Sequenz</li>
    <li><strong>Net erëmbenotzbar</strong>: eng Nummer däerf nëmmen eemol vergi ginn</li>
</ul>

<h3>Beispiller vun akzeptéierte Formater</h3>

<table class="w-full border-collapse border border-gray-300 my-6">
    <thead class="bg-gray-100">
        <tr>
            <th class="border border-gray-300 px-4 py-2 text-left">Format</th>
            <th class="border border-gray-300 px-4 py-2 text-left">Beispill</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td class="border border-gray-300 px-4 py-2">Joer + Nummer</td>
            <td class="border border-gray-300 px-4 py-2 font-mono">2026-0001, 2026-0002</td>
        </tr>
        <tr>
            <td class="border border-gray-300 px-4 py-2">Präfix + Nummer</td>
            <td class="border border-gray-300 px-4 py-2 font-mono">FAC-001, FAC-002</td>
        </tr>
        <tr>
            <td class="border border-gray-300 px-4 py-2">Einfach Nummer</td>
            <td class="border border-gray-300 px-4 py-2 font-mono">00001, 00002</td>
        </tr>
    </tbody>
</table>

<h2>Bezuelungsbedingungen</h2>

<p>Och wann den Artikel 63 LIVA se net verlaangt, sinn dës Uginne <strong>staark ze recommandéieren</strong>:</p>

<ul>
    <li><strong>Bezuelungsfrist</strong> (standardméisseg: 30 Deeg nom Empfang, geännert Gesetz vum 18. Abrëll 2004)</li>
    <li><strong>Fälegkeetsdatum</strong></li>
    <li><strong>Bankkoordinaten</strong> (IBAN, BIC)</li>
    <li><strong>Verzuchszënsen</strong> tëscht Professioneller — BCE-Referenztaux + 8 Punkten, also <strong>10,15 % am 1. Semester 2026</strong>, all Semester ugepasst — an d'<strong>Pauschal vun 40 €</strong></li>
</ul>

<h2>Opbewahrung vun de Rechnungen</h2>

<p>Dir musst Är Rechnungen (ausgestallt an erhalen) <strong>10 Joer</strong> laang vum Ofschloss vum Exercice un opbewahren — Artikel 16 vum Handelsgesetzbuch an Artikel 65 LIVA. A Pabeierform oder elektronesch (PDF/A mat Integritéitsgarantien). Kuckt d'<a href="https://guichet.public.lu/fr/entreprises/gestion-juridique-comptabilite/comptable/enregistrement/obligations-comptables.html" target="_blank" rel="noopener">comptabel Flichten op Guichet.lu</a>.</p>

<h2>Konsequenze vun enger net konformer Rechnung</h2>

<div class="bg-red-50 border-l-4 border-red-500 p-4 my-6">
    <p class="font-semibold text-red-800">⚠️ Risiken</p>
    <ul class="text-red-700 mt-2">
        <li>Refus vum TVA-Ofzuch duerch Äre Client</li>
        <li><strong>Administrativ Geldstrof vun 250 € bis 10 000 € pro Infraktioun</strong> (Art. 77 LIVA)</li>
        <li>Wann de Manktem zu enger Netbezuelung vun TVA oder zu enger onreegelméisseger Réckerstattung gefouert huet: <strong>Geldstrof vun 10 % bis 50 % vun der betraffener TVA</strong> — proportional, also ouni Plafong</li>
        <li>Bei Refus, Rechnungen a comptabel Stécker bei enger Kontroll ze weisen: <strong>bis zu 25 000 € pro Dag Verspéidung</strong>, no engem Avertissement</li>
        <li>Bei schwéierer Steierhannerzéiung oder Steierbedruch: Geldstrof vun 25 000 € bis zum Zéngfache vum TVA-Betrag, Prisong vun engem Mount bis fënnef Joer a Verloscht vun de biergerleche Rechter fir 5 bis 10 Joer (Art. 80 LIVA)</li>
    </ul>
</div>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">De Plafong vun 10 000 € ass net dee gréisste Risiko</p>
    <p>Wat weh deet, ass déi proportional Geldstrof. Bei engem Sträit iwwer 80 000 € TVA bedeit eng Sanktioun vun 10 bis 50 % tëscht 8 000 an 40 000 € — onofhängeg dovunner, wéi vill formell Infraktioune festgestallt ginn.</p>
</div>

<h2>Beispill vun enger konformer Rechnung</h2>

<p>Hei sinn déi wesentlech Elementer vun enger konformer Rechnung:</p>

{$sample}

<h2>Maacht et Iech einfach mat faktur.lu</h2>

<p>Konform Rechnungen mat der Hand ze erstellen ass feeleranfälleg. <strong>faktur.lu</strong> automatiséiert d'Konformitéit:</p>

<ul>
    <li>✅ All obligatoresch Mentiounen virausgefëllt</li>
    <li>✅ Automatesch a fortlafend Nummeréierung (Art. 63 LIVA)</li>
    <li>✅ Automatesch TVA-Berechnung no dem Fall</li>
    <li>✅ Ugepasst gesetzlech Mentiounen (Autoliquidatioun, Export, Franchise 57bis…)</li>
    <li>✅ Integréierten FAIA-Export fir d'Steierkontrolle vun der AED</li>
</ul>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">All Joer ze préiwen</p>
    <p>D'obligatoresch Mentiounen, Artikelreferenzen a Seuiler kënnen änneren. Dës Säit gëtt reegelméisseg aktualiséiert – fir Är perséinlech Situatioun frot Är Fiduciaire oder direkt d'<a href="https://pfi.public.lu/" target="_blank" rel="noopener">AED</a>.</p>
</div>

<h2>Offiziell Quellen</h2>

<ul>
    <li><a href="https://legilux.public.lu/eli/etat/leg/loi/1979/02/12/n1/jo" target="_blank" rel="noopener">Geännert Gesetz vum 12. Februar 1979 (LIVA) – Artikelen 17, 43, 57bis, 63, 65, 77, 80</a></li>
    <li><a href="https://pfi.public.lu/fr/professionnel/tva/en-cours-activite-economique/que-doivent-contenir-factures.html" target="_blank" rel="noopener">AED – Obligatoresch Mentiounen op de Rechnungen</a></li>
    <li><a href="https://logistics.public.lu/fr/formalities-procedures/taxes/VAT-sanctions-remedies.html" target="_blank" rel="noopener">TVA-Sanktiounen a Rechtsmëttelen</a></li>
    <li><a href="https://eur-lex.europa.eu/legal-content/FR/TXT/?uri=CELEX:32006L0112" target="_blank" rel="noopener">Direktiv 2006/112/EG – Artikelen 138, 146, 196, 226</a></li>
</ul>

<p class="text-sm text-slate-500 mt-6"><em>Artikel aktualiséiert den 30. Juli 2026.</em></p>

<div class="mt-8 p-4 bg-slate-50 rounded-xl"><h3 class="text-base font-semibold text-slate-900 mb-3">Verbonnen Artikelen</h3><ul class="space-y-1"><li><a href="/lb/blog/note-de-credit-luxembourg-comment-etablir" class="text-primary-500 hover:text-primary-600 text-sm">Kreditnot Lëtzebuerg →</a></li><li><a href="/lb/blog/article-63-liva-numerotation-sequentielle-factures-luxembourg-obligatoire" class="text-primary-500 hover:text-primary-600 text-sm">Fortlafend Nummeréierung (Artikel 63 LIVA) →</a></li><li><a href="/lb/blog/franchise-tva-luxembourg-seuil-obligations-regime-normal" class="text-primary-500 hover:text-primary-600 text-sm">TVA-Franchise Lëtzebuerg (Seuil 50 000 €) →</a></li></ul></div>
HTML;
    }

    private function pt(): string
    {
        $sample = $this->sample([
            'company' => 'A Sua Sociedade SARL', 'invoice' => 'FATURA', 'date' => 'Data',
            'billed' => 'Faturado a:', 'client' => 'Cliente Empresa SA', 'desc' => 'Descrição',
            'qty' => 'Qtd', 'unit' => 'P.U. s/IVA', 'net' => 'Total s/IVA', 'line' => 'Prestação de consultoria',
            'totalNet' => 'Total s/IVA', 'totalGross' => 'Total c/IVA',
        ]);

        return <<<HTML
<div data-ai-summary class="bg-slate-50 border border-slate-200 rounded-xl p-5 my-4"><p class="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-2">Em resumo</p><ul class="list-disc pl-5 space-y-1 text-slate-700 text-sm"><li>As menções obrigatórias decorrem do <strong>artigo 63 LIVA</strong>: identidade e endereço das partes, números de IVA, data, <strong>número sequencial único</strong>, designação, base sem IVA, taxa, montante de IVA, total com IVA.</li><li>Uma fatura <strong>não conforme</strong> pode ser rejeitada pelo cliente e originar uma <strong>coima da AED de 250 € a 10 000 €</strong> (art. 77 LIVA) — ou <strong>10 % a 50 % do IVA em causa</strong> se o Estado tiver perdido receita.</li><li>Menções condicionais: <strong>autoliquidação</strong> (art. 196 diretiva), isenção, exonerações.</li><li><strong>Conservação 10 anos</strong> das faturas e documentos contabilísticos.</li></ul></div>
<p class="lead">Uma fatura não conforme pode ser rejeitada pelo seu cliente e expô-lo a coimas da AED. Eis a checklist completa das menções obrigatórias para criar faturas luxemburguesas irrepreensíveis — com base no <a href="https://legilux.public.lu/eli/etat/leg/loi/1979/02/12/n1/jo" target="_blank" rel="noopener">artigo 63 LIVA</a> e na lista publicada pela AED.</p>

<h2>Checklist das menções obrigatórias</h2>

<p>O <strong>artigo 63 LIVA</strong> (lei alterada de 12 de fevereiro de 1979) e a lista da AED impõem os seguintes elementos:</p>

<div class="bg-white border-2 border-purple-200 rounded-xl p-6 my-6">
    <h3 class="text-lg font-bold text-purple-800 mb-4">✅ Informações sobre o emitente</h3>
    <ul class="space-y-2">
        <li>☐ <strong>Nome completo ou denominação social</strong></li>
        <li>☐ <strong>Endereço completo</strong> da sede</li>
        <li>☐ <strong>Número de IVA intracomunitário</strong> (formato LU + 8 dígitos)</li>
        <li>☐ <strong>Número RCS</strong> para comerciantes e sociedades inscritos — obrigação decorrente da <em>legislação sobre o registo comercial e das sociedades</em>, e não da lei do IVA</li>
    </ul>
</div>

<div class="bg-white border-2 border-purple-200 rounded-xl p-6 my-6">
    <h3 class="text-lg font-bold text-purple-800 mb-4">✅ Informações sobre o cliente</h3>
    <ul class="space-y-2">
        <li>☐ <strong>Nome completo ou denominação social</strong></li>
        <li>☐ <strong>Endereço completo</strong></li>
        <li>☐ <strong>Número de IVA</strong> (obrigatório para B2B intracomunitário - validado via <a href="https://ec.europa.eu/taxation_customs/vies/" target="_blank" rel="noopener">VIES</a>)</li>
    </ul>
</div>

<div class="bg-white border-2 border-purple-200 rounded-xl p-6 my-6">
    <h3 class="text-lg font-bold text-purple-800 mb-4">✅ Informações sobre a fatura</h3>
    <ul class="space-y-2">
        <li>☐ <strong>Número de fatura único e sequencial</strong> (art. 63 LIVA, ponto 3°)</li>
        <li>☐ <strong>Data de emissão</strong> da fatura</li>
        <li>☐ <strong>Data de entrega ou prestação</strong> — legalmente exigida <em>quando difere</em> da data de emissão (art. 226 §7 da diretiva). Indicá-la sistematicamente continua a ser boa prática: elimina qualquer ambiguidade sobre o facto gerador numa inspeção</li>
    </ul>
</div>

<div class="bg-white border-2 border-purple-200 rounded-xl p-6 my-6">
    <h3 class="text-lg font-bold text-purple-800 mb-4">✅ Detalhe dos bens ou serviços</h3>
    <ul class="space-y-2">
        <li>☐ <strong>Descrição clara</strong> e detalhada</li>
        <li>☐ <strong>Quantidade</strong> entregue ou prestada</li>
        <li>☐ <strong>Preço unitário sem IVA</strong></li>
        <li>☐ <strong>Eventuais descontos ou abatimentos</strong></li>
    </ul>
</div>

<div class="bg-white border-2 border-purple-200 rounded-xl p-6 my-6">
    <h3 class="text-lg font-bold text-purple-800 mb-4">✅ Informações de IVA</h3>
    <ul class="space-y-2">
        <li>☐ <strong>Taxa de IVA</strong> aplicável por linha (17 %, 14 %, 8 %, 3 % ou 0 %)</li>
        <li>☐ <strong>Montante de IVA</strong> por taxa</li>
        <li>☐ <strong>Base tributável</strong> (montante sem IVA) por taxa de IVA</li>
        <li>☐ <strong>Menção de isenção ou autoliquidação</strong> se aplicável (ver tabela abaixo)</li>
    </ul>
</div>

<div class="bg-white border-2 border-purple-200 rounded-xl p-6 my-6">
    <h3 class="text-lg font-bold text-purple-800 mb-4">✅ Totais</h3>
    <ul class="space-y-2">
        <li>☐ <strong>Total sem IVA</strong></li>
        <li>☐ <strong>Total de IVA</strong></li>
        <li>☐ <strong>Total com IVA</strong></li>
    </ul>
</div>

<h2>Menções condicionais consoante os casos</h2>

<p>Consoante a natureza da operação, deve constar da fatura uma menção específica. Eis as formulações codificadas:</p>

<table class="w-full border-collapse border border-gray-300 my-6">
    <thead class="bg-gray-100">
        <tr>
            <th class="border border-gray-300 px-4 py-2 text-left">Situação</th>
            <th class="border border-gray-300 px-4 py-2 text-left">Menção a apor</th>
            <th class="border border-gray-300 px-4 py-2 text-left">Base legal</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td class="border border-gray-300 px-4 py-2">Serviço B2B intra-UE (adquirente noutro Estado-Membro)</td>
            <td class="border border-gray-300 px-4 py-2 font-mono text-sm">«Autoliquidação - Artigo 196.º da Diretiva 2006/112/CE»</td>
            <td class="border border-gray-300 px-4 py-2 text-sm">Art. 17 LIVA (lugar) + art. 196.º diretiva (devedor) + art. 226.º §11bis (menção)</td>
        </tr>
        <tr>
            <td class="border border-gray-300 px-4 py-2">Entrega intra-UE de bens B2B</td>
            <td class="border border-gray-300 px-4 py-2 font-mono text-sm">«Isenção de IVA - Artigo 138.º da Diretiva 2006/112/CE»</td>
            <td class="border border-gray-300 px-4 py-2 text-sm">Art. 43 §1 d) LIVA</td>
        </tr>
        <tr>
            <td class="border border-gray-300 px-4 py-2">Exportação fora da UE</td>
            <td class="border border-gray-300 px-4 py-2 font-mono text-sm">«Isenção de IVA - Artigo 146.º da Diretiva 2006/112/CE»</td>
            <td class="border border-gray-300 px-4 py-2 text-sm">Art. 43 §1 a) LIVA</td>
        </tr>
        <tr>
            <td class="border border-gray-300 px-4 py-2">Isenção de IVA (pequenas empresas)</td>
            <td class="border border-gray-300 px-4 py-2 font-mono text-sm">«IVA não aplicável - Artigo 57bis da lei alterada de 12 de fevereiro de 1979»</td>
            <td class="border border-gray-300 px-4 py-2 text-sm">Art. 57bis LIVA (limiar 50 000 €)</td>
        </tr>
    </tbody>
</table>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">A não confundir</p>
    <p>Para a autoliquidação B2B intra-UE, muitos modelos copiam a referência ao «artigo 44.º da Diretiva 2006/112/CE». O artigo 44.º define o <strong>lugar de tributação</strong> (no adquirente), mas a menção obrigatória codificada remete para o <strong>artigo 196.º</strong> (que designa o adquirente como devedor). Prefira a menção do artigo 196.º.</p>
</div>

<h3>Casos particulares de faturação</h3>

<p><strong>Fatura de adiantamento</strong>:</p>

<div class="bg-blue-50 border-l-4 border-blue-500 p-4 my-4">
    <p class="font-mono text-sm">«Adiantamento sobre a encomenda n.º [referência] de [data]»</p>
</div>

<p><strong>Nota de crédito</strong>:</p>

<div class="bg-blue-50 border-l-4 border-blue-500 p-4 my-4">
    <p class="font-mono text-sm">«Nota de crédito sobre a fatura n.º [número] de [data]»</p>
</div>

<h2>A numeração das faturas (art. 63 LIVA, ponto 3°)</h2>

<p>A numeração deve respeitar estas regras:</p>

<ul>
    <li><strong>Única</strong>: cada fatura tem um número distinto</li>
    <li><strong>Cronológica</strong>: os números seguem a ordem de emissão</li>
    <li><strong>Sem falhas</strong>: nenhuma lacuna na sequência</li>
    <li><strong>Não reutilizável</strong>: um número só pode ser atribuído uma vez</li>
</ul>

<h3>Exemplos de formatos aceites</h3>

<table class="w-full border-collapse border border-gray-300 my-6">
    <thead class="bg-gray-100">
        <tr>
            <th class="border border-gray-300 px-4 py-2 text-left">Formato</th>
            <th class="border border-gray-300 px-4 py-2 text-left">Exemplo</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td class="border border-gray-300 px-4 py-2">Ano + número</td>
            <td class="border border-gray-300 px-4 py-2 font-mono">2026-0001, 2026-0002</td>
        </tr>
        <tr>
            <td class="border border-gray-300 px-4 py-2">Prefixo + número</td>
            <td class="border border-gray-300 px-4 py-2 font-mono">FAC-001, FAC-002</td>
        </tr>
        <tr>
            <td class="border border-gray-300 px-4 py-2">Número simples</td>
            <td class="border border-gray-300 px-4 py-2 font-mono">00001, 00002</td>
        </tr>
    </tbody>
</table>

<h2>Condições de pagamento</h2>

<p>Embora o artigo 63 LIVA não as exija, estas indicações são <strong>vivamente recomendadas</strong>:</p>

<ul>
    <li><strong>Prazo de pagamento</strong> (por defeito: 30 dias após receção, lei alterada de 18 de abril de 2004)</li>
    <li><strong>Data de vencimento</strong></li>
    <li><strong>Dados bancários</strong> (IBAN, BIC)</li>
    <li><strong>Juros de mora</strong> aplicáveis entre profissionais — taxa de referência do BCE + 8 pontos, ou seja <strong>10,15 % no 1.º semestre de 2026</strong>, revista todos os semestres — e <strong>indemnização fixa de 40 €</strong></li>
</ul>

<h2>Conservação das faturas</h2>

<p>Deve conservar as suas faturas (emitidas e recebidas) durante <strong>10 anos</strong> a contar do encerramento do exercício — artigo 16 do Código Comercial e artigo 65 LIVA. Em papel ou em formato eletrónico (PDF/A com garantias de integridade). Ver as <a href="https://guichet.public.lu/fr/entreprises/gestion-juridique-comptabilite/comptable/enregistrement/obligations-comptables.html" target="_blank" rel="noopener">obrigações contabilísticas no Guichet.lu</a>.</p>

<h2>Consequências de uma fatura não conforme</h2>

<div class="bg-red-50 border-l-4 border-red-500 p-4 my-6">
    <p class="font-semibold text-red-800">⚠️ Riscos incorridos</p>
    <ul class="text-red-700 mt-2">
        <li>Rejeição da dedução do IVA pelo seu cliente</li>
        <li><strong>Coima administrativa de 250 € a 10 000 € por infração</strong> (art. 77 LIVA)</li>
        <li>Se o incumprimento tiver conduzido a um não pagamento de IVA ou a um reembolso indevido: <strong>coima de 10 % a 50 % do montante de IVA em causa</strong> — proporcional, logo sem limite máximo</li>
        <li>Em caso de recusa de comunicar faturas e documentos contabilísticos durante uma inspeção: <strong>até 25 000 € por dia de atraso</strong>, após advertência</li>
        <li>Em caso de fraude fiscal agravada ou burla fiscal: multa penal de 25 000 € a 10 vezes o montante do IVA, prisão de um mês a cinco anos e privação dos direitos cívicos de 5 a 10 anos (art. 80 LIVA)</li>
    </ul>
</div>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">O limite de 10 000 € não é o risco máximo</p>
    <p>É a coima proporcional que dói. Num litígio sobre 80 000 € de IVA, uma sanção de 10 a 50 % representa 8 000 a 40 000 € — independentemente do número de infrações formais registadas.</p>
</div>

<h2>Exemplo de fatura conforme</h2>

<p>Eis os elementos essenciais de uma fatura conforme:</p>

{$sample}

<h2>Simplifique a vida com o faktur.lu</h2>

<p>Criar faturas conformes manualmente é fonte de erros. O <strong>faktur.lu</strong> automatiza a conformidade:</p>

<ul>
    <li>✅ Todas as menções obrigatórias pré-preenchidas</li>
    <li>✅ Numeração automática e sequencial (art. 63 LIVA)</li>
    <li>✅ Cálculo automático do IVA consoante o caso</li>
    <li>✅ Menções legais adequadas (autoliquidação, exportação, isenção 57bis…)</li>
    <li>✅ Exportação FAIA integrada para as inspeções fiscais da AED</li>
</ul>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">A verificar todos os anos</p>
    <p>As menções obrigatórias, referências de artigos e limiares podem evoluir. Esta página é atualizada regularmente, mas para a sua situação pessoal, consulte o seu contabilista ou diretamente a <a href="https://pfi.public.lu/" target="_blank" rel="noopener">AED</a>.</p>
</div>

<h2>Fontes oficiais</h2>

<ul>
    <li><a href="https://legilux.public.lu/eli/etat/leg/loi/1979/02/12/n1/jo" target="_blank" rel="noopener">Lei alterada de 12 de fevereiro de 1979 (LIVA) - artigos 17, 43, 57bis, 63, 65, 77, 80</a></li>
    <li><a href="https://pfi.public.lu/fr/professionnel/tva/en-cours-activite-economique/que-doivent-contenir-factures.html" target="_blank" rel="noopener">AED - Menções obrigatórias nas faturas</a></li>
    <li><a href="https://logistics.public.lu/fr/formalities-procedures/taxes/VAT-sanctions-remedies.html" target="_blank" rel="noopener">Sanções de IVA e vias de recurso</a></li>
    <li><a href="https://eur-lex.europa.eu/legal-content/PT/TXT/?uri=CELEX:32006L0112" target="_blank" rel="noopener">Diretiva 2006/112/CE - artigos 138.º, 146.º, 196.º, 226.º</a></li>
</ul>

<p class="text-sm text-slate-500 mt-6"><em>Artigo atualizado a 30 de julho de 2026.</em></p>

<div class="mt-8 p-4 bg-slate-50 rounded-xl"><h3 class="text-base font-semibold text-slate-900 mb-3">Artigos relacionados</h3><ul class="space-y-1"><li><a href="/pt/blog/note-de-credit-luxembourg-comment-etablir" class="text-primary-500 hover:text-primary-600 text-sm">Nota de crédito Luxemburgo →</a></li><li><a href="/pt/blog/article-63-liva-numerotation-sequentielle-factures-luxembourg-obligatoire" class="text-primary-500 hover:text-primary-600 text-sm">Numeração sequencial (artigo 63 LIVA) →</a></li><li><a href="/pt/blog/franchise-tva-luxembourg-seuil-obligations-regime-normal" class="text-primary-500 hover:text-primary-600 text-sm">Isenção de IVA no Luxemburgo (limiar 50 000 €) →</a></li></ul></div>
HTML;
    }
};
