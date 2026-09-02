<?php

namespace App\Services;

use App\Models\BankBalance;
use App\Models\Expense;
use App\Models\Invoice;
use Carbon\Carbon;

/**
 * La prévision de trésorerie.
 *
 * ⚠️ Ce service partait de zéro et ne regardait QUE les factures impayées.
 * Trois conséquences fausses, signalées par un client :
 *
 * 1. Encaisser faisait BAISSER la prévision. Une facture payée sort de
 *    `unpaid()`, donc son montant disparaissait de la courbe au moment précis
 *    où l'argent arrivait. L'argent réellement reçu n'était compté nulle part.
 * 2. Le solde du jour était négatif par construction : au jour 0, le service
 *    retirait déjà un trentième des dépenses du mois alors que la seule
 *    recette comptée ce jour-là était le retard de paiement.
 * 3. Le graphique ignorait ce qu'il y avait sur le compte, donc il ne
 *    prévoyait pas un solde mais une variation, sans le dire.
 *
 * Le calcul part désormais d'un relevé bancaire saisi à la main (voir
 * `BankBalance`), auquel s'ajoutent les mouvements RÉELS depuis ce relevé,
 * avant seulement de projeter l'avenir.
 */
class CashflowForecastService
{
    public function __construct(
        // ⚠️ Le même service que le livre de recettes et la ventilation du
        // tableau de bord. Réécrire ici une somme d'encaissements produirait
        // une seconde définition : celle-ci compterait les règlements des
        // brouillons, l'autre non, et les deux écrans se contrediraient sur le
        // même mois. Le service porte déjà les deux pièges, l'isolation par
        // utilisateur et l'exclusion des brouillons.
        protected VentilationEncaissements $ventilation,
    ) {}

    public function getForecast(int $days = 90): array
    {
        $releve = BankBalance::applicableAt(now()->toDateString())->first();
        $aDesFacturesEnAttente = Invoice::unpaid()->invoicesOnly()->exists();

        if (! $aDesFacturesEnAttente && ! $releve) {
            return $this->prévisionVide($days);
        }

        $depart = $this->soldeEstiméAujourdhui($releve);

        $attendu = $this->getUnpaidInvoicesByDay($days);
        $incomeByDay = $attendu['byDay'];
        $monthlyExpense = $this->getAverageMonthlyExpense();
        $dailyExpense = (float) bcdiv((string) $monthlyExpense, '30', 4);

        $timeline = $this->buildDailyTimeline($days, $incomeByDay, $dailyExpense, $depart['solde']);
        $summary = $this->getSummaryAtMilestones($timeline);

        $totalIncome = 0;
        foreach ($timeline as $day) {
            $totalIncome = (float) bcadd((string) $totalIncome, (string) $day['income'], 4);
        }

        // `$days` et non `$days + 1` : le jour 0 ne porte plus de dépense
        // projetée, ses dépenses sont déjà dans les mouvements réels.
        $totalExpense = (float) bcmul((string) $dailyExpense, (string) $days, 4);

        return [
            'has_data' => true,

            // Sépare « je n'ai pas de relevé » de « je n'ai pas de données ».
            // Sans relevé, la courbe reste une VARIATION partant de zéro, et
            // l'interface doit le dire au lieu d'afficher un faux solde.
            'has_balance' => $releve !== null,
            'opening_balance' => $releve ? [
                'id' => $releve->id,
                'amount' => round((float) $releve->amount, 2),
                'date' => $releve->balance_date->format('Y-m-d'),
                'label' => $releve->label,
            ] : null,
            'movements_since_balance' => $releve ? [
                'income' => round($depart['encaissements'], 2),
                'expense' => round($depart['depenses'], 2),
            ] : null,
            'current_balance' => $releve ? round($depart['solde'], 2) : null,

            // Ce que la courbe suppose rentrant dès demain alors que
            // l'échéance est déjà passée. Affiché tel quel : le calcul reste
            // optimiste, l'interface le dit plutôt que de le maquiller.
            'overdue_total' => round($attendu['overdue'], 2),

            'timeline' => $timeline,
            'summary' => $summary,
            'totals' => [
                'total_expected_income' => round($totalIncome, 2),
                'total_expected_expense' => round($totalExpense, 2),
                'monthly_expense_average' => round($monthlyExpense, 2),
            ],
            'period_days' => $days,
        ];
    }

    protected function prévisionVide(int $days): array
    {
        return [
            'has_data' => false,
            'has_balance' => false,
            'opening_balance' => null,
            'movements_since_balance' => null,
            'current_balance' => null,
            'overdue_total' => 0,
            'timeline' => [],
            'summary' => ['current' => 0, 'days_30' => null, 'days_60' => null, 'days_90' => null],
            'totals' => ['total_expected_income' => 0, 'total_expected_expense' => 0, 'monthly_expense_average' => 0],
            'period_days' => $days,
        ];
    }

    /**
     * Le solde d'aujourd'hui : le relevé, plus ce qui est réellement entré et
     * sorti depuis.
     *
     * ⚠️ La frontière est INCLUSIVE : les encaissements du jour du relevé
     * comptent.
     *
     * J'avais fait l'inverse, pour ne pas compter deux fois un encaissement
     * déjà présent sur le relevé bancaire. Le raisonnement était joli et le
     * résultat catastrophique : le relevé se saisit le jour même dans la quasi
     * totalité des cas, si bien qu'un encaissement du jour était écarté des
     * mouvements réels PENDANT que sa facture quittait la projection. Les
     * 3 000 € disparaissaient des deux côtés à la fois. C'est exactement le
     * bug que ce service était censé corriger, et un client l'a retrouvé en
     * quelques minutes.
     *
     * La règle qui tient est celle-ci : chaque euro doit être compté une fois
     * et une seule, soit comme déjà reçu, soit comme attendu. Ce qui sort de
     * la projection doit entrer dans le réel, au même instant. La frontière
     * est donc la même des deux côtés.
     *
     * Il reste un risque de double compte, si le relevé bancaire incluait déjà
     * un encaissement du jour. Il est très inférieur à l'autre : un solde
     * légèrement optimiste sur une journée se corrige au relevé suivant, de
     * l'argent qui s'évapore fait douter de tout l'écran.
     */
    protected function soldeEstiméAujourdhui(?BankBalance $releve): array
    {
        if (! $releve) {
            return ['solde' => 0.0, 'encaissements' => 0.0, 'depenses' => 0.0];
        }

        $depuis = $releve->balance_date->toDateString();
        $jusqua = now()->toDateString();

        $encaissements = (float) $this->ventilation
            ->surPeriode((int) auth()->id(), $depuis, $jusqua)['total'];

        $depenses = (float) Expense::dateBetween($depuis, $jusqua)->sum('amount_ttc');

        $solde = (float) bcsub(
            bcadd((string) $releve->amount, (string) $encaissements, 4),
            (string) $depenses,
            4
        );

        return ['solde' => $solde, 'encaissements' => $encaissements, 'depenses' => $depenses];
    }

    /**
     * Ce qui RESTE DÛ sur les factures non soldées, regroupé par échéance.
     *
     * ⚠️ Le reste dû, et non le total de la facture. La projection raisonnait
     * sur le STATUT : une facture « envoyée » pesait pour son montant entier,
     * même déjà réglée pour moitié. L'acompte comptait alors deux fois, une
     * fois comme encaissement réel et une fois comme recette attendue.
     *
     * C'est la même cause que la disparition des 3 000 € : dès que le statut
     * décide seul, il existe des états où l'argent est compté deux fois, et
     * d'autres où il n'est compté nulle part. Le reste dû, lui, ne ment
     * jamais — il vaut zéro quand tout est encaissé, et la projection s'éteint
     * d'elle-même.
     *
     * ⚠️ Les factures en retard, sans échéance, ou échéant aujourd'hui sont
     * reportées à DEMAIN. Le jour 0 doit rester le solde réel : y inscrire une
     * recette attendue afficherait comme disponible un argent qui n'est pas
     * arrivé.
     *
     * Ce report est OPTIMISTE et la méthode renvoie donc son montant à part,
     * pour que l'interface puisse le dire. Sur un compte réel, les 11 factures
     * impayées étaient toutes en retard : la courbe supposait 29 907 € rentrant
     * dans les vingt-quatre heures, ce qui expliquait à soi seul le bond entre
     * aujourd'hui et le mois suivant. Le chiffre n'est pas faux, mais le lire
     * sans cette hypothèse en tête, c'est le prendre pour une promesse.
     *
     * @return array{byDay: array<string, float>, overdue: float}
     */
    protected function getUnpaidInvoicesByDay(int $days): array
    {
        $today = now()->startOfDay();
        $demain = now()->addDay()->startOfDay();
        $endDate = now()->addDays($days)->endOfDay();

        $factures = Invoice::unpaid()
            ->invoicesOnly()
            ->where(function ($q) use ($today, $endDate) {
                $q->whereNull('due_at')
                    ->orWhere('due_at', '<', $today)
                    ->orWhereBetween('due_at', [$today, $endDate]);
            })
            ->withSum('payments as deja_encaisse', 'amount')
            ->get(['id', 'due_at', 'total_ttc']);

        $incomeByDay = [];
        $enRetard = 0.0;

        foreach ($factures as $facture) {
            $reste = (float) bcsub(
                (string) $facture->total_ttc,
                (string) ($facture->deja_encaisse ?? 0),
                4
            );

            // Une facture soldée, ou trop-perçue, ne projette plus rien.
            if ($reste <= 0) {
                continue;
            }

            $echeance = $facture->due_at ? Carbon::parse($facture->due_at) : null;

            $reporte = $echeance === null || $echeance->lessThanOrEqualTo($today);

            if ($reporte) {
                $enRetard = (float) bcadd((string) $enRetard, (string) $reste, 4);
            }

            $dateKey = $reporte ? $demain->format('Y-m-d') : $echeance->format('Y-m-d');

            $incomeByDay[$dateKey] = (float) bcadd(
                (string) ($incomeByDay[$dateKey] ?? 0),
                (string) $reste,
                4
            );
        }

        return ['byDay' => $incomeByDay, 'overdue' => $enRetard];
    }

    /**
     * Dépense mensuelle moyenne (TTC) sur les six derniers mois.
     */
    protected function getAverageMonthlyExpense(): float
    {
        $sixMonthsAgo = now()->subMonths(6)->startOfMonth();
        $lastMonthEnd = now()->subMonth()->endOfMonth();

        $totalExpenses = (float) Expense::dateBetween(
            $sixMonthsAgo->format('Y-m-d'),
            $lastMonthEnd->format('Y-m-d')
        )->sum('amount_ttc');

        if ($totalExpenses <= 0) {
            return 0;
        }

        return (float) bcdiv((string) $totalExpenses, '6', 4);
    }

    /**
     * La courbe, jour par jour.
     *
     * Le jour 0 vaut exactement le solde d'aujourd'hui : aucune recette, aucune
     * dépense projetée. La projection ne commence qu'au jour 1.
     */
    protected function buildDailyTimeline(int $days, array $incomeByDay, float $dailyExpense, float $soldeDeDepart): array
    {
        $timeline = [];
        $cumulativeIncome = 0;
        $cumulativeExpense = 0;

        for ($i = 0; $i <= $days; $i++) {
            $date = now()->addDays($i);
            $dateKey = $date->format('Y-m-d');

            $incomeToday = $i === 0 ? 0 : ($incomeByDay[$dateKey] ?? 0);
            $expenseToday = $i === 0 ? 0 : $dailyExpense;

            $cumulativeIncome = (float) bcadd((string) $cumulativeIncome, (string) $incomeToday, 4);
            $cumulativeExpense = (float) bcadd((string) $cumulativeExpense, (string) $expenseToday, 4);

            $netCash = (float) bcsub(
                bcadd((string) $soldeDeDepart, (string) $cumulativeIncome, 4),
                (string) $cumulativeExpense,
                4
            );

            $timeline[] = [
                'date' => $dateKey,
                'label' => $date->format('d/m'),
                'day_number' => $i,
                'income' => round($incomeToday, 2),
                'expense' => round($expenseToday, 2),
                'cumulative_income' => round($cumulativeIncome, 2),
                'cumulative_expense' => round($cumulativeExpense, 2),
                'net_cash' => round($netCash, 2),
            ];
        }

        return $timeline;
    }

    protected function getSummaryAtMilestones(array $timeline): array
    {
        $get = fn (int $day) => isset($timeline[$day]) ? $timeline[$day]['net_cash'] : null;

        return [
            'current' => $get(0) ?? 0,
            'days_30' => $get(30),
            'days_60' => $get(60),
            'days_90' => $get(90),
        ];
    }
}
