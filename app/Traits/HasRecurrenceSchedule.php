<?php

namespace App\Traits;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

/**
 * Le rythme d'une récurrence : sa fréquence, son échéance, sa fin.
 *
 * Les factures récurrentes et les charges fixes obéissent exactement au même
 * mécanisme. Il vit ici pour qu'un décalage de date n'ait qu'un seul endroit où
 * être corrigé, et pour que le rythme d'une charge ne dérive jamais de celui
 * d'une facture.
 *
 * Le modèle qui l'emploie déclare la colonne qui porte son échéance et le
 * compteur qu'il incrémente ; tout le reste est commun.
 */
trait HasRecurrenceSchedule
{
    public const FREQUENCY_WEEKLY = 'weekly';

    public const FREQUENCY_MONTHLY = 'monthly';

    public const FREQUENCY_QUARTERLY = 'quarterly';

    public const FREQUENCY_YEARLY = 'yearly';

    public const FREQUENCIES = [
        self::FREQUENCY_WEEKLY,
        self::FREQUENCY_MONTHLY,
        self::FREQUENCY_QUARTERLY,
        self::FREQUENCY_YEARLY,
    ];

    /** Colonne portant la prochaine échéance (par exemple `next_invoice_date`). */
    abstract public static function nextDateColumn(): string;

    /** Colonne comptant ce qui a déjà été généré (par exemple `invoices_generated`). */
    abstract public static function generatedCountColumn(): string;

    /** Prochaine échéance, quel que soit le nom de sa colonne. */
    public function nextDate(): ?Carbon
    {
        return $this->{static::nextDateColumn()};
    }

    /**
     * L'échéance est-elle atteinte ?
     *
     * Une récurrence suspendue ou terminée n'est jamais due : c'est ce qui
     * garantit qu'une charge mise en pause ne génère rien et ne pèse sur
     * aucune prévision.
     */
    public function isDue(): bool
    {
        return (bool) $this->is_active
            && $this->nextDate()?->lte(now()->startOfDay())
            && ($this->ends_at === null || $this->ends_at->gte(now()->startOfDay()));
    }

    /**
     * L'échéance suivante, sans jamais déborder sur le mois d'après.
     *
     * Carbon, laissé à lui-même, transforme le 31 janvier + 1 mois en 3 mars :
     * février est purement sauté. Pour une facture récurrente, c'est un mois
     * non facturé ; pour un loyer, une charge disparue. Et le décalage est
     * définitif, l'échéance restant ensuite collée au 3.
     *
     * Les variantes « sans débordement » ramènent au dernier jour du mois visé
     * (28 février), ce qui ne saute jamais une échéance.
     */
    public function calculateNextDate(): Carbon
    {
        $depuis = $this->nextDate()->copy();

        return match ($this->frequency) {
            self::FREQUENCY_WEEKLY => $depuis->addWeek(),
            self::FREQUENCY_MONTHLY => $depuis->addMonthNoOverflow(),
            self::FREQUENCY_QUARTERLY => $depuis->addMonthsNoOverflow(3),
            self::FREQUENCY_YEARLY => $depuis->addYearNoOverflow(),
        };
    }

    /**
     * Avance l'échéance d'une période et compte ce qui vient d'être généré.
     *
     * L'échéance repart de la date prévue, jamais du jour où la génération a
     * eu lieu : un serveur en retard d'un jour ne décale pas le loyer de tous
     * les mois suivants.
     */
    public function advanceToNextDate(): void
    {
        $compteur = static::generatedCountColumn();

        $this->update([
            static::nextDateColumn() => $this->calculateNextDate(),
            $compteur => $this->{$compteur} + 1,
        ]);

        // Passée sa date de fin, la récurrence s'éteint d'elle-même : sans
        // cela elle resterait active en portant une échéance qu'elle ne
        // franchira jamais.
        if ($this->ends_at && $this->nextDate()->gt($this->ends_at)) {
            $this->update(['is_active' => false]);
        }
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeDue(Builder $query): Builder
    {
        return $query->active()
            ->where(static::nextDateColumn(), '<=', now()->startOfDay())
            ->where(function (Builder $q) {
                $q->whereNull('ends_at')
                    ->orWhere('ends_at', '>=', now()->startOfDay());
            });
    }
}
