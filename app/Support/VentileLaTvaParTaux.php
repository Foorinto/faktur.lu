<?php

namespace App\Support;

use App\Models\Invoice;
use Illuminate\Support\Collection;

/**
 * Ventilation de la TVA par taux, sur un ensemble de factures.
 *
 * ⚠️ Additionner `invoice_items.total_ht` groupé par taux donne un résultat
 * FAUX dès qu'une remise globale existe : la remise porte sur le document, pas
 * sur les lignes, qui gardent leur montant d'origine. Le tableau affichait
 * alors une base par taux supérieure à son propre total.
 *
 * Constaté chez un client le 2026-08-31 : 4 407,50 € de base à 17 % pour un
 * total de 3 888,78 €, soit 518,72 € de remises invisibles. C'est le chiffre
 * qu'on recopie dans une déclaration de TVA.
 *
 * On s'appuie donc sur `Invoice::vat_breakdown`, qui passe par
 * `DocumentTotalsCalculator` — le calcul même qui a produit les totaux de la
 * facture. L'égalité entre les lignes et le total est alors vraie par
 * construction, et non par coïncidence.
 *
 * Réécrire la répartition ici aurait fait une TROISIÈME implémentation de la
 * même règle. Les deux premières finissent déjà par diverger.
 */
trait VentileLaTvaParTaux
{
    /**
     * @param  Collection<int, Invoice>  $invoices
     * @return array<int, array{rate: float, base: float, amount: float}>
     */
    protected function ventilationTvaParTaux(Collection $invoices): array
    {
        $parTaux = [];

        foreach ($invoices as $invoice) {
            foreach ($invoice->vat_breakdown as $tranche) {
                $taux = (float) $tranche['rate'];
                $cle = (string) $taux;

                $parTaux[$cle] ??= ['rate' => $taux, 'base' => 0.0, 'amount' => 0.0];
                $parTaux[$cle]['base'] += (float) $tranche['base'];
                $parTaux[$cle]['amount'] += (float) $tranche['amount'];
            }
        }

        // Arrondi à la fin seulement : arrondir chaque facture puis sommer
        // ferait dériver le total de quelques centimes sur un exercice entier.
        $lignes = array_map(fn ($ligne) => [
            'rate' => $ligne['rate'],
            'base' => round($ligne['base'], 2),
            'amount' => round($ligne['amount'], 2),
        ], array_values($parTaux));

        usort($lignes, fn ($a, $b) => $b['rate'] <=> $a['rate']);

        return $lignes;
    }
}
