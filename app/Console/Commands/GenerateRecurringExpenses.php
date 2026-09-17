<?php

namespace App\Console\Commands;

use App\Models\Expense;
use App\Models\RecurringExpense;
use App\Services\PlanService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Fait naître les dépenses des charges fixes arrivées à échéance (FEAT-117).
 *
 * Le pendant de `recurring:generate` côté achats. Une charge suspendue ou
 * terminée ne produit rien : c'est le scope `due()` du rythme partagé qui le
 * garantit, au même endroit pour les factures et pour les charges.
 */
class GenerateRecurringExpenses extends Command
{
    protected $signature = 'recurring-expenses:generate';

    protected $description = 'Génère les dépenses des charges fixes récurrentes dues';

    /**
     * Les quotas du plan s'appliquent ici comme partout ailleurs.
     *
     * Quand le plafond est atteint, la charge est **reportée sans que son
     * échéance avance** : elle reste due et repartira dès le lendemain ou dès
     * la montée de plan. Avancer la date perdrait la dépense pour de bon, et
     * une charge manquante fausse la comptabilité autant qu'une charge en trop.
     */
    public function handle(PlanService $plans): int
    {
        $charges = RecurringExpense::with('user')->due()->get();

        $generees = 0;
        $reportees = 0;
        $erreurs = 0;

        foreach ($charges as $charge) {
            try {
                $proprietaire = $charge->user;

                if ($proprietaire && ! $plans->canCreateExpense($proprietaire)) {
                    $reportees++;
                    $this->warn("Charge fixe #{$charge->id} reportée : quota de dépenses atteint (compte #{$proprietaire->id}).");
                    Log::warning("Recurring expense #{$charge->id} deferred: expense quota reached for user #{$proprietaire->id}");

                    continue;
                }

                $depense = Expense::create($charge->toExpenseAttributes());

                // Sans sa ligne de ventilation, la dépense n'apparaîtrait pas
                // dans le récapitulatif fiscal par catégorie, qui interroge les
                // lignes : le loyer serait facturé nulle part.
                $depense->ensureDerivedLine();

                $charge->update(['last_expense_id' => $depense->id]);
                $charge->advanceToNextDate();

                $generees++;
                $this->info("Dépense créée pour {$charge->displayName()} (charge fixe #{$charge->id}).");
                Log::info("Recurring expense #{$charge->id}: expense #{$depense->id} created");
            } catch (\Exception $e) {
                $erreurs++;
                $this->error("Erreur charge fixe #{$charge->id} : {$e->getMessage()}");
                Log::error("Recurring expense #{$charge->id} failed: {$e->getMessage()}");
            }
        }

        $this->info("Terminé : {$generees} dépenses générées, {$reportees} reportées (quota), {$erreurs} erreurs.");

        return 0;
    }
}
