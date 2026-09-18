<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Le jour du mois voulu par l'utilisateur, mémorisé (FEAT-117).
 *
 * Sans lui, une échéance calée sur le 31 se replie sur le 28 au premier
 * février et n'en repart jamais : le loyer demandé « le 31 » devient un loyer
 * « le 28 », et la facture récurrente avec lui.
 *
 * L'ancre garde l'intention. L'échéance est ensuite ramenée au dernier jour
 * disponible quand le mois est trop court : 31 janvier, 28 février, 31 mars.
 *
 * Reprise : le jour de l'échéance en cours fait l'ancre. C'est exactement
 * l'intention de l'utilisateur telle qu'elle est lisible aujourd'hui, et cela
 * ne change le comportement d'aucune récurrence calée avant le 29.
 */
return new class extends Migration
{
    /** @var array<int, array{0: string, 1: string}> */
    private array $tables = [
        ['recurring_expenses', 'next_expense_date'],
        ['recurring_invoices', 'next_invoice_date'],
    ];

    public function up(): void
    {
        foreach ($this->tables as [$table, $colonne]) {
            Schema::table($table, function (Blueprint $blueprint) use ($colonne) {
                $blueprint->unsignedTinyInteger('anchor_day')->nullable()->after($colonne);
            });
        }

        foreach ($this->tables as [$table, $colonne]) {
            DB::table($table)
                ->whereNull('anchor_day')
                ->orderBy('id')
                ->chunkById(500, function ($lignes) use ($table, $colonne) {
                    foreach ($lignes as $ligne) {
                        $date = $ligne->{$colonne} ?? null;

                        if ($date === null) {
                            continue;
                        }

                        DB::table($table)
                            ->where('id', $ligne->id)
                            ->update(['anchor_day' => (int) Carbon::parse($date)->day]);
                    }
                });
        }
    }

    public function down(): void
    {
        foreach ($this->tables as [$table, $colonne]) {
            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->dropColumn('anchor_day');
            });
        }
    }
};
