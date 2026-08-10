<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Plafonne les transmissions Peppol du plan Pro.
 *
 * `max_peppol_per_month` valait `null`, c'est-à-dire **illimité**, sur un plan
 * à 15 €/mois. Or chaque document transmis se paie au point d'accès. Au tarif
 * public le plus élevé que nous paierions (Recommand, 0,30 € le document sur
 * le palier gratuit), un utilisateur dépassant 50 documents coûte plus cher
 * qu'il ne rapporte. Un client qui réussit devenait une perte.
 *
 * 15 € ÷ 0,30 € = 50. C'est le plafond exact au-delà duquel la marge devient
 * négative dans le pire cas. Aux paliers suivants (0,20 € puis 0,10 €), le même
 * plafond laisse 33 % puis 67 % de marge à saturation, et l'utilisateur moyen
 * reste très en dessous.
 *
 * Ne concerne que la **transmission** sur le réseau. L'export manuel du fichier
 * XML reste illimité : il ne nous coûte rien.
 *
 * ⚠️ `PlanService::canExportPeppol()` ne compte aujourd'hui que les
 * `PeppolTransmission`, donc les envois. Quand la réception arrivera
 * (FEAT-097), elle consommera le même quota chez le fournisseur et devra donc
 * entrer dans ce décompte, sans quoi le plafond protégera une moitié du flux
 * seulement.
 */
return new class extends Migration
{
    private const PLAFOND = 50;

    public function up(): void
    {
        $this->appliquer(self::PLAFOND);
    }

    public function down(): void
    {
        $this->appliquer(null);
    }

    private function appliquer(?int $plafond): void
    {
        // `name` et non `slug` : la table `plans` n'a pas de colonne `slug`,
        // et une clause sur une colonne inexistante passe sans erreur en
        // renvoyant zéro ligne. La migration se serait donc tue.
        $plan = DB::table('plans')->where('name', 'pro')->first();

        if (! $plan) {
            echo '  → plan « pro » introuvable, plafond non appliqué'.PHP_EOL;

            return;
        }

        $limites = json_decode($plan->limits ?? '{}', true) ?: [];
        $limites['max_peppol_per_month'] = $plafond;

        DB::table('plans')->where('id', $plan->id)->update([
            'limits' => json_encode($limites),
            'updated_at' => now(),
        ]);

        echo '  → plan pro : max_peppol_per_month = '.($plafond ?? 'null').PHP_EOL;
    }
};
