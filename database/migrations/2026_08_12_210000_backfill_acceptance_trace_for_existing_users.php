<?php

use App\Support\DpaDocument;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Version du DPA en vigueur avant l'introduction de la case dédiée.
     *
     * C'est celle que l'en-tête du document affichait, et donc celle que les
     * comptes antérieurs ont acceptée. L'inscrire telle quelle est plus exact
     * que de leur attribuer la version courante, qu'ils n'ont jamais lue.
     */
    private const VERSION_HISTORIQUE = '1.0';

    /**
     * Reprise de la trace d'acceptation pour les comptes existants.
     *
     * La date retenue est celle de la création du compte, et elle n'est pas
     * une reconstitution de confort : la case des conditions générales était
     * déjà obligatoire à l'inscription (`required|accepted`), et l'article
     * 10.5 de ces conditions dispose qu'« en acceptant les CGU, l'Utilisateur
     * accepte également le DPA dans sa version en vigueur ». L'acceptation a
     * donc bien eu lieu à cette date — seule sa trace manquait.
     *
     * Deux limites sont assumées plutôt que comblées :
     *
     *  - `acceptance_ip` reste vide. L'adresse n'a jamais été relevée, et en
     *    inventer une retirerait toute valeur à celles qui sont vraies ;
     *  - `dpa_acceptance_method` distingue les deux voies. Confondre une case
     *    cochée en connaissance de cause avec une acceptation par renvoi
     *    affaiblirait les deux : questionnée en audit, une trace qui ne sait
     *    pas dire comment elle a été constituée ne prouve rien.
     */
    public function up(): void
    {
        // Garde de rejouabilité : la reprise des données est vérifiée par un
        // test qui rejoue `up()` sur une base déjà migrée.
        if (! Schema::hasColumn('users', 'dpa_acceptance_method')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('dpa_acceptance_method', 20)->nullable()->after('dpa_version');
            });
        }

        // Les comptes créés depuis l'introduction de la case portent déjà leur
        // trace : on ne touche qu'aux lignes restées vides.
        DB::table('users')
            ->whereNull('dpa_accepted_at')
            ->update([
                'terms_accepted_at' => DB::raw('created_at'),
                'dpa_accepted_at' => DB::raw('created_at'),
                'dpa_version' => self::VERSION_HISTORIQUE,
                'dpa_acceptance_method' => 'terms',
            ]);

        // Les inscriptions passées par la case dédiée sont marquées comme
        // telles, y compris celles enregistrées entre les deux migrations.
        DB::table('users')
            ->whereNotNull('dpa_accepted_at')
            ->whereNull('dpa_acceptance_method')
            ->update(['dpa_acceptance_method' => 'explicit']);
    }

    public function down(): void
    {
        // On ne remet pas les dates à zéro : elles décrivent un fait, la
        // colonne seule est réversible.
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('dpa_acceptance_method');
        });
    }
};
