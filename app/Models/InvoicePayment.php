<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Un encaissement sur une facture.
 *
 * Plusieurs par facture : « des fois un paiement en espèces pour une partie et
 * le reste par virement ». Le statut « payée » se dérive de leur somme, il ne
 * se saisit plus à la main.
 */
class InvoicePayment extends Model
{
    use HasFactory;

    /**
     * Moyens d'encaissement.
     *
     * Repris de `BusinessSettings::PAYMENT_METHODS`, qui décrit ce qu'on
     * PROPOSE au client sur la facture. Ici il s'agit de ce qu'on a REÇU : les
     * deux listes coïncident aujourd'hui, et un test les garde alignées.
     *
     * `wero` est ajouté à la demande du client, qui l'anticipe au Luxembourg.
     * Payconiq est en cours d'absorption par Wero au niveau européen : les deux
     * coexistent, parce qu'un encaissement passé en Payconiq doit rester
     * lisible dans les écritures.
     */
    public const METHODS = ['transfer', 'payconiq', 'wero', 'cash', 'card', 'check'];

    protected $fillable = [
        'invoice_id',
        'amount',
        'paid_at',
        'method',
        'label',
        'reference',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'paid_at' => 'date:Y-m-d',
        ];
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * Libellé traduit du moyen, ou une mention explicite quand il est inconnu.
     *
     * Les encaissements repris lors de la migration n'ont pas de moyen : ils
     * doivent le dire, pas se ranger silencieusement sous « virement ».
     */
    public function methodLabel(): string
    {
        return $this->method
            ? __("app.payment_methods.{$this->method}")
            : __('app.payment_methods.unknown');
    }
}
