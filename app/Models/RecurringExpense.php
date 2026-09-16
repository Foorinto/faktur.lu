<?php

namespace App\Models;

use App\Traits\BelongsToUser;
use App\Traits\HasRecurrenceSchedule;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Une charge fixe qui se répète : loyer, abonnement, assurance (FEAT-117).
 *
 * Ce modèle ne porte aucun montant prévisionnel à additionner : il **génère**
 * de vraies dépenses, à leur échéance. C'est ce qui évite le double compte —
 * une charge annoncée et la dépense correspondante ne sont jamais deux
 * montants distincts, mais le même, compté une fois.
 *
 * Le montant reste indicatif : un loyer indexé ou une facture d'électricité
 * varient. La récurrence donne le rythme et le montant probable ; la dépense
 * générée reste modifiable.
 */
class RecurringExpense extends Model
{
    use BelongsToUser, HasFactory, HasRecurrenceSchedule;

    protected $fillable = [
        'user_id',
        'label',
        'frequency',
        'next_expense_date',
        'anchor_day',
        'ends_at',
        'is_active',
        'expenses_generated',
        'last_expense_id',
        'provider_name',
        'supplier_country',
        'category',
        'amount_input_mode',
        'amount',
        'vat_rate',
        'vat_regime',
        'reverse_charge_vat_rate',
        'is_deductible',
        'payment_method',
        'description',
    ];

    protected $casts = [
        'next_expense_date' => 'date:Y-m-d',
        'ends_at' => 'date:Y-m-d',
        'anchor_day' => 'integer',
        'is_active' => 'boolean',
        'is_deductible' => 'boolean',
        'amount' => 'decimal:4',
        'vat_rate' => 'decimal:2',
        'reverse_charge_vat_rate' => 'decimal:2',
    ];

    protected $attributes = [
        'supplier_country' => 'LU',
        'amount_input_mode' => Expense::INPUT_HT,
    ];

    public static function nextDateColumn(): string
    {
        return 'next_expense_date';
    }

    public static function generatedCountColumn(): string
    {
        return 'expenses_generated';
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function lastExpense(): BelongsTo
    {
        return $this->belongsTo(Expense::class, 'last_expense_id');
    }

    /** Les dépenses que cette charge a déjà fait naître. */
    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    /** Nom lisible de la charge, à défaut celui du fournisseur. */
    public function displayName(): string
    {
        return $this->label !== null && $this->label !== ''
            ? $this->label
            : $this->provider_name;
    }

    /**
     * Les attributs de la dépense que cette charge fait naître à son échéance.
     *
     * La dépense est datée de l'échéance prévue, jamais du jour de génération :
     * un serveur en retard d'un jour ne déplace pas le loyer dans le mois
     * suivant, et l'écriture reste à sa place dans le récapitulatif fiscal.
     */
    public function toExpenseAttributes(?string $date = null): array
    {
        $attributs = [
            'user_id' => $this->user_id,
            'recurring_expense_id' => $this->id,
            'date' => $date ?? $this->next_expense_date->toDateString(),
            'provider_name' => $this->provider_name,
            'supplier_country' => $this->supplier_country,
            'category' => $this->category,
            'description' => $this->description,
            'vat_rate' => $this->vat_rate,
            'vat_regime' => $this->vat_regime,
            'reverse_charge_vat_rate' => $this->reverse_charge_vat_rate,
            'is_deductible' => $this->is_deductible,
            'payment_method' => $this->payment_method,
            'amount_input_mode' => $this->amount_input_mode,
        ];

        // On repose le montant dans le champ qui correspond au mode de saisie,
        // et la dépense en déduit TVA et total : le calcul n'existe qu'à un
        // seul endroit.
        $attributs[$this->amount_input_mode === Expense::INPUT_TTC ? 'amount_ttc' : 'amount_ht'] = $this->amount;

        return $attributs;
    }

    /**
     * Construit une charge fixe à partir d'une dépense déjà saisie.
     *
     * C'est le chemin naturel : on découvre le besoin en ressaisissant son
     * loyer pour la troisième fois.
     */
    public static function fromExpense(Expense $expense, string $frequency, string $nextDate): self
    {
        return new self([
            'user_id' => $expense->user_id,
            'label' => $expense->description,
            'frequency' => $frequency,
            'next_expense_date' => $nextDate,
            'provider_name' => $expense->provider_name,
            'supplier_country' => $expense->supplier_country,
            'category' => $expense->category,
            'amount_input_mode' => $expense->amount_input_mode ?? Expense::INPUT_HT,
            'amount' => $expense->amount_input_mode === Expense::INPUT_TTC
                ? $expense->amount_ttc
                : $expense->amount_ht,
            'vat_rate' => $expense->vat_rate,
            'vat_regime' => $expense->vat_regime,
            'reverse_charge_vat_rate' => $expense->reverse_charge_vat_rate,
            'is_deductible' => $expense->is_deductible,
            'payment_method' => $expense->payment_method,
            'description' => $expense->description,
        ]);
    }
}
