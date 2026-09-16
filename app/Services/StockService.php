<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\StockMovement;

/**
 * Mouvements de stock automatiques (FEAT-116).
 *
 * Le stock suit l'émission des documents, jamais les brouillons : un brouillon
 * peut être supprimé. À l'émission d'une facture, chaque ligne rattachée à un
 * produit suivi sort du stock ; l'émission d'une note de crédit réintègre les
 * mêmes quantités. La règle est unifiée par le SIGNE de la quantité de ligne :
 * une facture porte des quantités positives (sortie = quantité négative), une
 * note de crédit des quantités négatives (entrée = quantité positive).
 */
class StockService
{
    /**
     * Applique les mouvements de stock d'un document qui vient d'être émis.
     *
     * Idempotent : si des mouvements existent déjà pour ce document, on ne fait
     * rien — finaliser deux fois ne doit pas décrémenter deux fois.
     */
    public function applyEmission(Invoice $invoice): void
    {
        $invoice->loadMissing('items.product');

        $isCreditNote = $invoice->type === Invoice::TYPE_CREDIT_NOTE;

        $alreadyApplied = StockMovement::withoutGlobalScope('user')
            ->where('source_type', Invoice::class)
            ->where('source_id', $invoice->id)
            ->exists();

        if ($alreadyApplied) {
            return;
        }

        foreach ($invoice->items as $item) {
            $product = $item->product;

            // Une ligne sans produit, ou dont le produit n'est pas suivi, ne
            // bouge aucun stock.
            if ($product === null || ! $product->track_stock || ! $product->canTrackStock()) {
                continue;
            }

            // Quantité signée du mouvement = l'inverse de la quantité de ligne.
            // Facture (qté +) -> sortie (-). Note de crédit (qté -) -> entrée (+).
            $quantity = bcmul((string) $item->quantity, '-1', 4);

            if ((float) $quantity === 0.0) {
                continue;
            }

            StockMovement::create([
                'user_id' => $invoice->user_id,
                'product_id' => $product->id,
                'quantity' => $quantity,
                'type' => $isCreditNote ? StockMovement::TYPE_ENTREE : StockMovement::TYPE_SORTIE,
                'source_type' => Invoice::class,
                'source_id' => $invoice->id,
                'date' => $invoice->issued_at?->toDateString() ?? now()->toDateString(),
                // La sortie comme la réintégration se valorisent au coût moyen
                // pondéré courant, calculé : on ne fige pas de coût ici.
                'unit_cost' => null,
                'note' => $invoice->number,
            ]);
        }
    }

    /**
     * Synchronise les entrées de stock générées par une dépense (FEAT-116).
     *
     * Chaque ligne de dépense qui désigne un produit suivi et une quantité fait
     * entrer ce produit en stock, au prix réellement payé (HT de la ligne /
     * quantité). Une dépense n'étant pas immuable, on efface puis on recrée les
     * mouvements issus de cette dépense à chaque enregistrement : l'état du
     * stock reflète toujours la dépense telle qu'elle est aujourd'hui.
     */
    public function syncFromExpense(Expense $expense): void
    {
        $expense->loadMissing('lines.product');

        // On repart de zéro pour cette dépense : simple et sans état résiduel.
        StockMovement::withoutGlobalScope('user')
            ->where('source_type', Expense::class)
            ->where('source_id', $expense->id)
            ->delete();

        foreach ($expense->lines as $line) {
            $product = $line->product;
            $quantity = (float) ($line->stock_quantity ?? 0);

            // Une ligne sans produit suivi, ou sans quantité, n'alimente rien.
            if ($product === null || ! $product->track_stock || $quantity <= 0) {
                continue;
            }

            // Coût unitaire = ce que la ligne a réellement coûté, par unité.
            $unitCost = round((float) $line->amount_ht / $quantity, 4);

            StockMovement::create([
                'user_id' => $expense->user_id,
                'product_id' => $product->id,
                'quantity' => $quantity,
                'type' => StockMovement::TYPE_ENTREE,
                'source_type' => Expense::class,
                'source_id' => $expense->id,
                'date' => $expense->date?->toDateString() ?? now()->toDateString(),
                'unit_cost' => $unitCost,
                'note' => $expense->provider_name,
            ]);
        }
    }

    /**
     * Enregistre une entrée manuelle de stock (réception, achat direct).
     */
    public function recordEntry(Product $product, float $quantity, ?float $unitCost = null, ?string $date = null, ?string $note = null): StockMovement
    {
        return StockMovement::create([
            'user_id' => $product->user_id,
            'product_id' => $product->id,
            'quantity' => abs($quantity),
            'type' => StockMovement::TYPE_ENTREE,
            'date' => $date ?? now()->toDateString(),
            'unit_cost' => $unitCost,
            'note' => $note,
        ]);
    }

    /**
     * Saisie d'inventaire : je déclare la quantité réelle comptée, et l'écart
     * avec le stock courant est enregistré comme un mouvement d'ajustement daté
     * et motivé. Rien n'est réécrit — l'écart est une ligne de plus.
     */
    public function recordInventory(Product $product, float $countedQuantity, ?string $date = null, ?string $note = null): ?StockMovement
    {
        $current = $product->currentStock($date);
        $delta = round($countedQuantity - $current, 4);

        if ($delta === 0.0) {
            return null;
        }

        return StockMovement::create([
            'user_id' => $product->user_id,
            'product_id' => $product->id,
            'quantity' => $delta,
            'type' => StockMovement::TYPE_AJUSTEMENT,
            'date' => $date ?? now()->toDateString(),
            'unit_cost' => null,
            'note' => $note,
        ]);
    }
}
