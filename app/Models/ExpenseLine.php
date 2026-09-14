<?php

namespace App\Models;

use App\Traits\BelongsToUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Une ligne de ventilation d'une dépense (FEAT-115).
 *
 * Porte la catégorie, la description et les montants d'une part de la dépense.
 * Le régime de TVA, la déductibilité et tout ce qui dépend du fournisseur
 * restent sur la dépense : ce sont des propriétés de l'opération, pas de la
 * nature de l'achat. La ligne, elle, ne connaît que sa base HT et son taux.
 *
 * Montant canonique : le HT. Le mode de saisie (HT vs TTC) vit sur la dépense
 * et c'est le formulaire qui convertit en HT avant d'écrire la ligne, pour que
 * le modèle de ligne reste simple et sans ambiguïté.
 */
class ExpenseLine extends Model
{
    use HasFactory, BelongsToUser;

    protected $fillable = [
        'user_id',
        'expense_id',
        'category',
        'description',
        'amount_ht',
        'vat_rate',
        'amount_vat',
        'amount_ttc',
        'sort_order',
    ];

    protected $casts = [
        'amount_ht' => 'decimal:4',
        'vat_rate' => 'decimal:2',
        'amount_vat' => 'decimal:4',
        'amount_ttc' => 'decimal:4',
        'sort_order' => 'integer',
    ];

    protected static function booted(): void
    {
        // La ligne calcule sa TVA et son TTC depuis le HT à chaque sauvegarde,
        // comme le fait l'InvoiceItem côté ventes. Cohérence garantie même si
        // l'appelant passe des montants incohérents.
        static::saving(function (ExpenseLine $line) {
            $line->calculateAmounts();
        });
    }

    public function expense(): BelongsTo
    {
        return $this->belongsTo(Expense::class);
    }

    /**
     * Libellé lisible de la catégorie de la ligne.
     *
     * Réutilise la table des catégories de la dépense : une ligne « Logiciels »
     * s'affiche « Logiciels » dans les exports, jamais sa clé technique.
     */
    public function getCategoryLabelAttribute(): string
    {
        return Expense::categoryMap(activeOnly: false)[$this->category]
            ?? Expense::builtInCategories()[$this->category]
            ?? $this->category;
    }

    /**
     * TVA et TTC dérivés du HT et du taux, en base 10 exacte.
     *
     * On reste en HT canonique : la conversion depuis un TTC saisi se fait dans
     * le formulaire, là où vit le mode de saisie de la dépense.
     */
    public function calculateAmounts(): void
    {
        $amountHt = (string) ($this->amount_ht ?? '0');
        $vatMultiplier = bcdiv((string) ($this->vat_rate ?? '0'), '100', 6);

        $this->amount_vat = bcmul($amountHt, $vatMultiplier, 4);
        $this->amount_ttc = bcadd($amountHt, $this->amount_vat, 4);
    }
}
