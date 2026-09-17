<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\BuildsExpenseFormOptions;
use App\Models\Expense;
use App\Models\RecurringExpense;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Charges fixes récurrentes (FEAT-117).
 *
 * L'écran ne manipule jamais de montants prévisionnels : il décrit la dépense
 * à faire naître et le rythme auquel elle naîtra. Les dépenses, elles, sont
 * créées par `recurring-expenses:generate`.
 */
class RecurringExpenseController extends Controller
{
    use BuildsExpenseFormOptions;

    public function index(): Response
    {
        $modeles = RecurringExpense::with('lastExpense:id,date')
            ->orderByDesc('is_active')
            ->orderBy('next_expense_date')
            ->get();

        $actives = $modeles->where('is_active', true);

        $charges = $modeles
            ->map(fn (RecurringExpense $c) => [
                'id' => $c->id,
                'label' => $c->displayName(),
                'provider_name' => $c->provider_name,
                'category_label' => Expense::categoryMap(activeOnly: false)[$c->category] ?? $c->category,
                'frequency' => $c->frequency,
                'amount' => (float) $c->amount,
                'amount_input_mode' => $c->amount_input_mode,
                // Les deux montants, pour qu'aucun écran n'oblige à convertir
                // de tête : le HT parle au comptable, le TTC au compte en banque.
                'amount_ht' => $c->montants()['ht'],
                'amount_ttc' => $c->montants()['ttc'],
                'next_expense_date' => $c->next_expense_date?->toDateString(),
                'ends_at' => $c->ends_at?->toDateString(),
                'is_active' => $c->is_active,
                'expenses_generated' => $c->expenses_generated,
                'last_generated_at' => $c->lastExpense?->date?->toDateString(),
            ]);

        return Inertia::render('RecurringExpenses/Index', [
            'charges' => $charges,
            'monthlyTotal' => round($actives->sum(fn (RecurringExpense $c) => $c->poidsMensuelTtc()), 2),
        ]);
    }

    public function create(Request $request): Response
    {
        // Depuis une dépense existante : le chemin naturel, quand on se rend
        // compte qu'on ressaisit son loyer pour la troisième fois.
        $modele = null;

        if ($request->filled('from_expense')) {
            $depense = Expense::find($request->integer('from_expense'));

            if ($depense !== null) {
                $modele = $this->pourFormulaire(RecurringExpense::fromExpense(
                    $depense,
                    RecurringExpense::FREQUENCY_MONTHLY,
                    Carbon::parse($depense->date)->addMonthNoOverflow()->toDateString(),
                ));
            }
        }

        return Inertia::render('RecurringExpenses/Create', array_merge($this->expenseFormOptions(), [
            'frequencies' => RecurringExpense::FREQUENCIES,
            'modele' => $modele,
            // Combien de fois cette charge a déjà été saisie à la main. Le
            // chiffre est indicatif : c'est le fournisseur et la catégorie
            // finalement enregistrés qui décideront du rattachement.
            'occurrencesPassees' => $modele !== null
                ? $this->occurrencesPassees($modele['provider_name'], $modele['category'])->count()
                : 0,
        ]));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->valider($request);

        $charge = RecurringExpense::create($data);

        // Une charge déclarée après coup a souvent déjà été saisie à la main
        // pendant des mois. Sans rattachement, ces dépenses resteraient dans
        // la moyenne des dépenses variables PENDANT que la charge est projetée
        // à sa date : le loyer pèserait deux fois jusqu'à ce que l'historique
        // sorte de la fenêtre de six mois.
        //
        // Le rattachement ne se fait jamais tout seul : l'utilisateur coche, et
        // le message dit combien de dépenses ont effectivement changé.
        $rattachees = 0;

        if ($request->boolean('attach_past')) {
            $rattachees = $this->occurrencesPassees($data['provider_name'], $data['category'])
                ->update(['recurring_expense_id' => $charge->id]);
        }

        return redirect()->route('recurring-expenses.index')
            ->with('success', $rattachees > 0
                ? __('app.recurring_expenses.flash_created_with_attached', ['count' => $rattachees])
                : __('app.recurring_expenses.flash_created'));
    }

    public function edit(RecurringExpense $recurringExpense): Response
    {
        return Inertia::render('RecurringExpenses/Edit', array_merge($this->expenseFormOptions(), [
            'charge' => $this->pourFormulaire($recurringExpense) + [
                'id' => $recurringExpense->id,
                'ends_at' => $recurringExpense->ends_at?->toDateString(),
                'is_active' => $recurringExpense->is_active,
                'expenses_generated' => $recurringExpense->expenses_generated,
            ],
            'frequencies' => RecurringExpense::FREQUENCIES,
        ]));
    }

    public function update(Request $request, RecurringExpense $recurringExpense): RedirectResponse
    {
        $data = $this->valider($request, creation: false);

        // Déplacer l'échéance déplace aussi le jour voulu : sans cela, l'ancre
        // d'origine ramènerait la prochaine dépense à l'ancien jour du mois.
        $data['anchor_day'] = Carbon::parse($data['next_expense_date'])->day;

        $recurringExpense->update($data);

        return redirect()->route('recurring-expenses.index')
            ->with('success', __('app.recurring_expenses.flash_updated'));
    }

    /**
     * Suspend ou reprend une charge.
     *
     * Une charge suspendue ne génère rien et ne pèse sur aucune prévision :
     * c'est la façon d'arrêter un abonnement résilié sans effacer l'historique
     * des dépenses qu'il a déjà produites.
     */
    public function toggle(RecurringExpense $recurringExpense): RedirectResponse
    {
        $recurringExpense->update(['is_active' => ! $recurringExpense->is_active]);

        return back()->with('success', $recurringExpense->is_active
            ? __('app.recurring_expenses.flash_resumed')
            : __('app.recurring_expenses.flash_suspended'));
    }

    /**
     * Supprime la charge. Les dépenses déjà générées restent : ce sont des
     * écritures comptables, pas des prévisions.
     */
    public function destroy(RecurringExpense $recurringExpense): RedirectResponse
    {
        $recurringExpense->delete();

        return redirect()->route('recurring-expenses.index')
            ->with('success', __('app.recurring_expenses.flash_deleted'));
    }

    /**
     * Les dépenses déjà saisies qui sont, selon toute vraisemblance, des
     * occurrences de cette charge.
     *
     * Même fournisseur, même catégorie, sur la fenêtre que regarde la
     * prévision. Celles qui appartiennent déjà à une autre charge ne sont
     * jamais reprises.
     *
     * @return Builder<Expense>
     */
    private function occurrencesPassees(?string $fournisseur, ?string $categorie): Builder
    {
        return Expense::query()
            ->whereNull('recurring_expense_id')
            ->where('provider_name', (string) $fournisseur)
            ->where('category', (string) $categorie)
            ->where('date', '>=', now()->subMonths(6)->startOfMonth()->toDateString());
    }

    /**
     * Les champs du formulaire, en valeurs simples.
     *
     * Les dates sont rendues en `Y-m-d` et les montants en nombres : un objet
     * Carbon arriverait au navigateur horodaté en UTC, et un champ date
     * l'afficherait vide ou décalé d'un jour.
     *
     * @return array<string, mixed>
     */
    private function pourFormulaire(RecurringExpense $charge): array
    {
        return [
            'label' => $charge->label,
            'frequency' => $charge->frequency,
            'next_expense_date' => $charge->next_expense_date?->toDateString(),
            'provider_name' => $charge->provider_name,
            'supplier_country' => $charge->supplier_country,
            'category' => $charge->category,
            'amount_input_mode' => $charge->amount_input_mode,
            'amount' => $charge->amount !== null ? (float) $charge->amount : null,
            'vat_rate' => $charge->vat_rate !== null ? (float) $charge->vat_rate : null,
            'vat_regime' => $charge->vat_regime,
            'reverse_charge_vat_rate' => $charge->reverse_charge_vat_rate !== null ? (float) $charge->reverse_charge_vat_rate : null,
            'is_deductible' => (bool) $charge->is_deductible,
            'payment_method' => $charge->payment_method,
            'description' => $charge->description,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function valider(Request $request, bool $creation = true): array
    {
        $data = $request->validate([
            'label' => ['nullable', 'string', 'max:255'],
            'frequency' => ['required', Rule::in(RecurringExpense::FREQUENCIES)],
            'next_expense_date' => ['required', 'date'],
            'ends_at' => ['nullable', 'date', 'after:next_expense_date'],
            'is_active' => ['boolean'],
            'provider_name' => ['required', 'string', 'max:255'],
            'supplier_country' => ['nullable', 'string', Rule::in(array_column(Expense::getSupplierCountries(), 'code'))],
            'category' => ['required', 'string', Rule::in(array_keys(Expense::categoryMap(activeOnly: false)))],
            'amount_input_mode' => ['required', Rule::in([Expense::INPUT_HT, Expense::INPUT_TTC])],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'vat_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'vat_regime' => ['nullable', 'string', Rule::in(array_keys(Expense::getVatRegimes()))],
            'reverse_charge_vat_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'is_deductible' => ['boolean'],
            'payment_method' => ['nullable', 'string', Rule::in(array_keys(Expense::getPaymentMethods()))],
            'description' => ['nullable', 'string', 'max:2000'],
            'attach_past' => ['boolean'],
        ]);

        // Un ordre donné au contrôleur, pas une colonne de la charge.
        unset($data['attach_past']);

        $data['is_active'] = $request->boolean('is_active', true);
        $data['is_deductible'] = $request->boolean('is_deductible', true);

        if ($creation) {
            $data['user_id'] = auth()->id();
        }

        return $data;
    }
}
