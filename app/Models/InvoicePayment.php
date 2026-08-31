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
        'recorded_before_issue',
        'reference',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'paid_at' => 'date:Y-m-d',
            'recorded_before_issue' => 'boolean',
        ];
    }

    /**
     * Un encaissement sait, dès sa création, s'il était connu avant l'émission.
     *
     * La réponse est inscrite plutôt que déduite d'une comparaison
     * d'horodatages : ceux-ci sont stockés à la seconde, et un règlement saisi
     * juste après la finalisation aurait été indiscernable d'un acompte saisi
     * juste avant. C'est elle qui décide de la présence sur le document.
     */
    protected static function booted(): void
    {
        static::creating(function (self $paiement) {
            // Une valeur explicitement fournie est respectée — les tests s'en
            // servent pour poser un acompte sans rejouer tout le cycle du
            // brouillon. `exists` vaut false à la création : c'est la présence
            // de l'attribut qu'il faut regarder, pas sa valeur.
            if (array_key_exists('recorded_before_issue', $paiement->getAttributes())) {
                return;
            }

            $paiement->recorded_before_issue = ! ($paiement->invoice?->isFinalized() ?? false);
        });
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
