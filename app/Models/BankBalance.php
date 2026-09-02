<?php

namespace App\Models;

use App\Traits\BelongsToUser;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Un relevé de solde bancaire, saisi à la main.
 *
 * Point de départ de la prévision de trésorerie. Voir la migration pour le
 * pourquoi de l'historique.
 */
class BankBalance extends Model
{
    use BelongsToUser;

    protected $fillable = [
        'balance_date',
        'amount',
        'label',
    ];

    protected $casts = [
        // ⚠️ `date:Y-m-d` et non `date` : en production les dates reviennent en
        // UTC, et un relevé saisi le 1er ressortait daté du 31. Le piège est
        // invisible en SQLite, donc invisible en test.
        'balance_date' => 'date:Y-m-d',
        'amount' => 'decimal:2',
    ];

    /**
     * Le relevé qui fait foi à une date donnée : le plus récent qui ne soit
     * pas postérieur à cette date.
     *
     * Un relevé daté du futur existe légitimement — on saisit parfois par
     * anticipation — mais il ne doit pas servir de base à un calcul qui part
     * d'aujourd'hui.
     */
    public function scopeApplicableAt(Builder $query, string $date): Builder
    {
        return $query->whereDate('balance_date', '<=', $date)
            ->orderByDesc('balance_date')
            // Départage deux relevés du même jour : le dernier saisi gagne,
            // c'est une correction du précédent.
            ->orderByDesc('id');
    }
}
