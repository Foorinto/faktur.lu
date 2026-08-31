<?php

namespace App\Services;

use App\Models\InvoicePayment;

/**
 * Répartition des encaissements par moyen de paiement (FEAT-114).
 *
 * ⚠️ Cette ventilation lit les ENCAISSEMENTS, jamais les factures. Une facture
 * réglée en deux fois, 300 € en espèces puis 700 € par virement, doit peser
 * dans les deux colonnes et à leurs dates respectives. Repartir du montant de
 * la facture et de sa date de règlement attribuerait tout au dernier moyen et
 * au dernier mois.
 *
 * Le calcul vivait dans RevenueBookController. Le tableau de bord en a besoin
 * aussi — un client payant est allé chercher l'information là avant de la
 * trouver ailleurs — d'où ce service partagé plutôt qu'une seconde copie.
 */
class VentilationEncaissements
{
    /**
     * Ventilation sur une période, tous moyens confondus.
     *
     * @return array{total: float, lignes: array<int, array<string, mixed>>}
     */
    public function surPeriode(int $userId, string $debut, string $fin): array
    {
        $lignes = $this->requete($userId)
            ->whereDate('paid_at', '>=', $debut)
            ->whereDate('paid_at', '<=', $fin)
            ->selectRaw('method, SUM(amount) as total, COUNT(*) as nombre')
            ->groupBy('method')
            ->orderByDesc('total')
            ->get();

        $total = round((float) $lignes->sum('total'), 2);

        return [
            'total' => $total,
            'lignes' => $lignes
                ->map(fn ($l) => $this->ligne($l->method, (float) $l->total, (int) $l->nombre, $total))
                ->values()
                ->all(),
        ];
    }

    /**
     * Encaissements de l'utilisateur.
     *
     * `InvoicePayment` ne porte pas de portée globale : sans ce `whereHas`, la
     * ventilation additionnerait les encaissements de tout le monde.
     *
     * Le `user_id` explicite fait double emploi avec la portée globale de
     * `Invoice` tant qu'on interroge pour l'utilisateur connecté — une mutation
     * l'a confirmé. Il reste parce qu'il rend le service indépendant de la
     * session : une tâche planifiée ou un export pour un autre compte
     * n'auraient pas cette portée.
     */
    private function requete(int $userId)
    {
        return InvoicePayment::query()
            ->whereHas('invoice', fn ($q) => $q
                ->where('user_id', $userId)
                // ⚠️ Les brouillons sont écartés. Depuis que l'acompte se
                // saisit avant l'émission, un encaissement peut être rattaché
                // à un document qui n'existe pas encore — et qui peut être
                // supprimé, emportant ses encaissements avec lui. Le chiffre
                // disparaîtrait alors du livre de recettes après y avoir figuré.
                //
                // L'acompte y entre dès que la facture est émise, à sa vraie
                // date de versement : on perd quelques jours de visibilité, on
                // gagne un livre de recettes qui ne se contredit pas. La liste
                // des factures, juste à côté, ne compte elle non plus que des
                // documents émis.
                ->whereNotNull('finalized_at')
            );
    }

    /**
     * @return array<string, mixed>
     */
    private function ligne(?string $method, float $total, int $nombre, float $totalPeriode): array
    {
        return [
            'method' => $method,
            // « Non renseigné » se dit. Les encaissements repris lors de la
            // migration n'ont pas de moyen, et les ranger sous « virement »
            // fabriquerait une donnée comptable dans des documents conservés
            // dix ans.
            'label' => $method
                ? __("app.payment_methods.{$method}")
                : __('app.payment_methods.unknown'),
            'total' => round($total, 2),
            'nombre' => $nombre,
            'part' => $totalPeriode > 0 ? round($total / $totalPeriode * 100, 1) : 0.0,
        ];
    }
}
