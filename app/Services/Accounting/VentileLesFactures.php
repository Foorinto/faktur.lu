<?php

namespace App\Services\Accounting;

use App\Models\AccountingSetting;
use App\Models\Invoice;

/**
 * Ventilation d'une facture par compte de vente et par taux de TVA.
 *
 * Une facture donne une ligne, sauf lorsque ses articles se répartissent sur
 * plusieurs comptes ou plusieurs taux — elle en donne alors une par
 * combinaison, dont la somme fait le total du document.
 *
 * Extrait du formateur CSV pour être partagé avec le classeur XLSX : les deux
 * produisent les mêmes écritures dans deux emballages différents, et deux
 * implémentations auraient fini par diverger sur un arrondi.
 */
trait VentileLesFactures
{
    protected function ventiler(Invoice $invoice, AccountingSetting $settings): array
    {
        if (! $invoice->relationLoaded('items')) {
            $invoice->load('items');
        }

        $tranches = $invoice->vat_breakdown;

        // Aucune ligne d'article : un montant saisi directement, ou une reprise.
        // On conserve le document tel quel plutôt que de le reconstruire.
        if (empty($tranches) || $invoice->items->isEmpty()) {
            return [[
                'ht' => round((float) $invoice->total_ht, 2),
                'tva' => round((float) $invoice->total_vat, 2),
                'ttc' => round((float) $invoice->total_ttc, 2),
                'taux' => $this->getMainVatRate($invoice),
                'compte' => $settings->sales_account,
            ]];
        }

        $parts = [];

        foreach ($tranches as $tranche) {
            $taux = (float) $tranche['rate'];
            $htNet = round((float) $tranche['base'], 2);
            $tvaNet = round((float) $tranche['amount'], 2);

            // Les articles de ce taux, regroupés par compte. Le brut sert de clé
            // de répartition ; la remise est déjà déduite du net de la tranche.
            $brut = $invoice->items
                ->filter(fn ($item) => abs((float) $item->vat_rate - $taux) < 0.001)
                ->groupBy(fn ($item) => $item->pcn_account ?: $settings->sales_account)
                ->map(fn ($lignes) => round((float) $lignes->sum('total_ht'), 2))
                ->filter(fn ($montant) => abs($montant) > 0.001);

            if ($brut->count() <= 1) {
                $parts[] = [
                    'ht' => $htNet,
                    'tva' => $tvaNet,
                    'ttc' => round($htNet + $tvaNet, 2),
                    'taux' => $taux,
                    'compte' => (string) ($brut->keys()->first() ?? $settings->sales_account),
                ];

                continue;
            }

            $sommeBrute = round($brut->sum(), 2);
            $premiere = count($parts);
            $cumulHt = 0.0;
            $cumulTva = 0.0;

            foreach ($brut as $compte => $montant) {
                $quotient = $montant / $sommeBrute;
                $ht = round($htNet * $quotient, 2);
                $tva = round($tvaNet * $quotient, 2);

                $parts[] = [
                    'ht' => $ht,
                    'tva' => $tva,
                    'ttc' => round($ht + $tva, 2),
                    'taux' => $taux,
                    'compte' => (string) $compte,
                ];

                $cumulHt = round($cumulHt + $ht, 2);
                $cumulTva = round($cumulTva + $tva, 2);
            }

            // Le reliquat d'arrondi va au plus gros compte de la tranche : la
            // somme des lignes doit faire le total du document au centime près,
            // sinon le fichier se contredit là où il prétendait s'expliquer.
            // Repéré par son compte, non par sa valeur : sur un avoir les
            // montants sont négatifs, et chercher le maximum en valeur absolue
            // dans la liste des montants ne l'y trouverait pas.
            $compteMajoritaire = $brut->sortByDesc(fn ($m) => abs($m))->keys()->first();
            $rangPrincipal = $premiere + $brut->keys()->values()->search($compteMajoritaire);

            $parts[$rangPrincipal]['ht'] = round($parts[$rangPrincipal]['ht'] + ($htNet - $cumulHt), 2);
            $parts[$rangPrincipal]['tva'] = round($parts[$rangPrincipal]['tva'] + ($tvaNet - $cumulTva), 2);
            $parts[$rangPrincipal]['ttc'] = round($parts[$rangPrincipal]['ht'] + $parts[$rangPrincipal]['tva'], 2);
        }

        return $parts;
    }

    /**
     * Get the main VAT rate from an invoice (highest HT amount).
     */
    protected function getMainVatRate(Invoice $invoice): float
    {
        if (!$invoice->relationLoaded('items')) {
            $invoice->load('items');
        }

        if ($invoice->items->isEmpty()) {
            return 0;
        }

        return $invoice->items
            ->groupBy('vat_rate')
            ->map(fn ($items) => $items->sum('total_ht'))
            ->sortDesc()
            ->keys()
            ->first() ?? 0;
    }
}
