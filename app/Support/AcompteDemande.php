<?php

namespace App\Support;

/**
 * Acompte demandé sur un document : un pourcentage ou une somme.
 *
 * ⚠️ Une DEMANDE, jamais un encaissement, et jamais une remise. Rien ici ne
 * touche aux totaux du document : l'acompte ne réduit ni la base taxable, ni
 * la TVA, ni le montant à payer. Il annonce ce qui est attendu à la commande.
 * Le versement réel s'enregistre dans les encaissements de la facture, et
 * c'est lui seul qui compte en comptabilité.
 *
 * Le trait est partagé par le devis et la facture : la conversion reporte
 * l'acompte, et les deux documents doivent le calculer de la même façon.
 */
trait AcompteDemande
{
    public const DEPOSIT_PERCENT = 'percent';
    public const DEPOSIT_AMOUNT = 'amount';

    /**
     * Montant attendu, en TTC, ou null si aucun acompte n'est demandé.
     *
     * Le pourcentage s'applique au TTC : c'est la somme que le client sort de
     * sa poche, et c'est celle qu'il comprend. Un acompte annoncé sur le HT
     * laisserait la TVA en suspens sans que personne ne sache quoi en faire.
     */
    public function depositAmount(): ?float
    {
        $valeur = (float) ($this->deposit_value ?? 0);

        if ($valeur <= 0) {
            return null;
        }

        $total = (float) ($this->total_ttc ?? 0);

        $montant = $this->deposit_type === self::DEPOSIT_PERCENT
            ? $total * $valeur / 100
            : $valeur;

        // Un acompte ne dépasse pas ce qu'il y a à payer : au-delà, ce n'est
        // plus un acompte, et le document deviendrait incompréhensible.
        return round(min($montant, $total), 2);
    }

    /**
     * Reste à régler après l'acompte annoncé.
     */
    public function depositBalance(): ?float
    {
        $acompte = $this->depositAmount();

        return $acompte === null
            ? null
            : round((float) ($this->total_ttc ?? 0) - $acompte, 2);
    }

    public function hasDeposit(): bool
    {
        return $this->depositAmount() !== null;
    }
}
