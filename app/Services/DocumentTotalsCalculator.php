<?php

namespace App\Services;

/**
 * FEAT-094 — Calcule les totaux d'un document (facture/devis) en appliquant les
 * remises globales (sur le total) avec VENTILATION par taux de TVA.
 *
 * Modèle en deux étages :
 *   1. les lignes portent déjà leur remise éventuelle (item->total_ht est net de
 *      la remise par ligne) ;
 *   2. les remises GLOBALES s'appliquent au sous-total HT, sont réparties
 *      proportionnellement sur chaque taux de TVA présent, puis la TVA est
 *      recalculée sur la base nette de chaque taux.
 *
 * Convention retenue : les remises en % portent sur le sous-total (non
 * cumulatives entre elles), les remises en montant sont fixes, le total des
 * remises est plafonné au sous-total (jamais de total négatif). La réconciliation
 * est exacte (le reliquat d'arrondi est affecté au dernier taux).
 *
 * Source unique utilisée par les totaux stockés, le PDF et l'export e-facture.
 */
class DocumentTotalsCalculator
{
    /**
     * @param  iterable  $items      objets avec ->vat_rate et ->total_ht (net de remise ligne)
     * @param  iterable  $discounts  objets avec ->type ('percent'|'amount') et ->value
     * @return array{subtotal_ht:string,discount_total:string,total_ht:string,total_vat:string,total_ttc:string,rates:array<string,array{rate:string,gross_base:string,discount:string,net_base:string,vat:string}>}
     */
    public function compute(iterable $items, iterable $discounts = []): array
    {
        // 1. Base HT par taux de TVA (après remises de ligne)
        $baseByRate = [];
        $subtotal = '0';
        foreach ($items as $item) {
            $rate = $this->normalizeRate($item->vat_rate);
            $ht = (string) ($item->total_ht ?? '0');
            $baseByRate[$rate] = bcadd($baseByRate[$rate] ?? '0', $ht, 4);
            $subtotal = bcadd($subtotal, $ht, 4);
        }

        // 2. Total des remises globales (% sur le sous-total, montants fixes), plafonné
        $discountTotal = '0';
        foreach ($discounts as $d) {
            $value = (string) ($d->value ?? '0');
            if (bccomp($value, '0', 4) !== 1) {
                continue;
            }
            if (($d->type ?? 'percent') === 'amount') {
                $discountTotal = bcadd($discountTotal, $value, 4);
            } else {
                $pct = bccomp($value, '100', 4) === 1 ? '100' : $value;
                $discountTotal = bcadd($discountTotal, bcmul($subtotal, bcdiv($pct, '100', 6), 4), 4);
            }
        }
        // Les remises globales ne s'appliquent que sur un document positif ; sur un
        // avoir (montants négatifs) on ne plafonne pas et on ne remise pas.
        if (bccomp($subtotal, '0', 4) === 1) {
            if (bccomp($discountTotal, $subtotal, 4) === 1) {
                $discountTotal = $subtotal;
            }
        } else {
            $discountTotal = '0';
        }

        // 3. Ventilation proportionnelle par taux ; reliquat sur le dernier taux
        $rates = [];
        $allocated = '0';
        $rateKeys = array_keys($baseByRate);
        $lastKey = end($rateKeys);
        foreach ($baseByRate as $rate => $base) {
            if ((string) $rate === (string) $lastKey) {
                $discount = bcsub($discountTotal, $allocated, 4);
            } elseif (bccomp($subtotal, '0', 4) === 1) {
                $discount = bcmul($discountTotal, bcdiv($base, $subtotal, 8), 4);
                $allocated = bcadd($allocated, $discount, 4);
            } else {
                $discount = '0';
            }

            $netBase = bcsub($base, $discount, 4);
            // Garde anti-négatif uniquement sur document positif (préserve les avoirs négatifs)
            if (bccomp($subtotal, '0', 4) === 1 && bccomp($netBase, '0', 4) === -1) {
                $netBase = '0';
            }
            $vat = bcmul($netBase, bcdiv((string) $rate, '100', 6), 4);

            $rates[(string) $rate] = [
                'rate' => (string) $rate,
                'gross_base' => $base,
                'discount' => $discount,
                'net_base' => $netBase,
                'vat' => $vat,
            ];
        }

        // 4. Totaux
        $totalHt = bcsub($subtotal, $discountTotal, 4);
        $totalVat = '0';
        foreach ($rates as $r) {
            $totalVat = bcadd($totalVat, $r['vat'], 4);
        }
        $totalTtc = bcadd($totalHt, $totalVat, 4);

        return [
            'subtotal_ht' => $subtotal,
            'discount_total' => $discountTotal,
            'total_ht' => $totalHt,
            'total_vat' => $totalVat,
            'total_ttc' => $totalTtc,
            'rates' => $rates,
        ];
    }

    private function normalizeRate(mixed $rate): string
    {
        return number_format((float) $rate, 2, '.', '');
    }
}
