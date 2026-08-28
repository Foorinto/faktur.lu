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
     * Ventilation d'une année, détaillée mois par mois.
     *
     * Le tableau de bord affiche déjà un chiffre d'affaires mensuel ; la
     * demande était de voir « le montant reçu en espèces, en virement etc. par
     * mois ». Tout est calculé en une requête et envoyé une fois : la page
     * bascule d'un mois à l'autre sans repasser par le serveur.
     *
     * @return array<int, array<int, array<string, mixed>>>  mois (1-12) => lignes
     */
    public function parMoisPourAnnee(int $userId, int $annee): array
    {
        $brut = $this->requete($userId)
            ->whereYear('paid_at', $annee)
            ->selectRaw(
                \App\Helpers\DatabaseHelper::month('paid_at')
                . ' as mois, method, SUM(amount) as total, COUNT(*) as nombre'
            )
            ->groupBy('mois', 'method')
            ->get();

        $parMois = [];

        foreach ($brut as $l) {
            $parMois[(int) $l->mois][] = $l;
        }

        $resultat = [];

        foreach ($parMois as $mois => $lignes) {
            $total = round(array_sum(array_map(fn ($l) => (float) $l->total, $lignes)), 2);

            usort($lignes, fn ($a, $b) => (float) $b->total <=> (float) $a->total);

            $resultat[$mois] = array_map(
                fn ($l) => $this->ligne($l->method, (float) $l->total, (int) $l->nombre, $total),
                $lignes
            );
        }

        return $resultat;
    }

    /**
     * Encaissements de l'utilisateur.
     *
     * `InvoicePayment` ne porte pas de portée globale : sans ce `whereHas`, la
     * ventilation additionnerait les encaissements de tout le monde.
     */
    private function requete(int $userId)
    {
        return InvoicePayment::query()
            ->whereHas('invoice', fn ($q) => $q->where('user_id', $userId));
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
