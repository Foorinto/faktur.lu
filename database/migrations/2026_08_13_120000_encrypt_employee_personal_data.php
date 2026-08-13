<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Champs chiffrés au repos, et pourquoi ceux-là.
     *
     * Aucun n'est interrogé en SQL — ni `where`, ni `orderBy`, ni `like`,
     * vérifié avant écriture. C'est la condition : le chiffrement de Laravel
     * emploie un vecteur d'initialisation aléatoire, deux fois la même valeur
     * donne deux chiffrés différents, et toute recherche par égalité, tout
     * index et tout tri SQL deviennent impossibles.
     *
     * `salary_gross` et `birth_date` en sont écartés à dessein : le premier
     * casserait l'arithmétique, le second le cast de date dont dépend
     * l'affichage des anniversaires du tableau de bord RH.
     *
     * @var list<string>
     */
    private const CHAMPS_TEXTE = [
        'nationality',
        'phone_perso',
        'email_perso',
        'address',
        'city',
        'postal_code',
    ];

    /** Déjà en TEXT et castés en tableau : seul le contenu change. */
    private const CHAMPS_TABLEAU = [
        'emergency_contact',
        'benefits',
    ];

    /**
     * Chiffre les données personnelles des salariés déjà enregistrées.
     *
     * L'hébergement mutualisé ne garantit pas le chiffrement du disque. Les
     * coordonnées bancaires étaient jusqu'ici la seule contrepartie, alors que
     * la table porte de quoi reconstituer une identité complète — date de
     * naissance, nationalité, adresse et téléphone personnels, contact
     * d'urgence — sur des personnes qui ne sont pas clientes du service et
     * n'ont rien signé.
     *
     * DEUX PRÉCAUTIONS PORTENT TOUTE LA MIGRATION.
     *
     * 1. Les lignes existantes contiennent du texte EN CLAIR. Changer le cast
     *    sans les convertir ferait échouer chaque lecture sur une exception de
     *    déchiffrement : le module ne serait pas dégradé, il serait mort. La
     *    conversion se fait donc ici, ligne à ligne.
     *
     * 2. La lecture passe par le constructeur de requêtes, jamais par Eloquent.
     *    Le modèle porte désormais le cast : le charger tenterait de déchiffrer
     *    un texte clair, et lèverait avant même d'avoir commencé.
     */
    public function up(): void
    {
        $this->elargirLesColonnes();

        DB::table('employees')->orderBy('id')->chunkById(200, function ($lignes) {
            foreach ($lignes as $ligne) {
                $modifications = [];

                foreach ([...self::CHAMPS_TEXTE, ...self::CHAMPS_TABLEAU] as $champ) {
                    $valeur = $ligne->{$champ};

                    if ($valeur === null || $valeur === '' || $this->dejaChiffre($valeur)) {
                        continue;
                    }

                    $modifications[$champ] = Crypt::encryptString($valeur);
                }

                if ($modifications) {
                    DB::table('employees')->where('id', $ligne->id)->update($modifications);
                }
            }
        });
    }

    /**
     * Rétablit le texte clair, pour que la migration reste réversible.
     *
     * Une valeur qui ne se déchiffre pas est laissée intacte : mieux vaut une
     * ligne non convertie qu'une donnée écrasée.
     */
    public function down(): void
    {
        DB::table('employees')->orderBy('id')->chunkById(200, function ($lignes) {
            foreach ($lignes as $ligne) {
                $modifications = [];

                foreach ([...self::CHAMPS_TEXTE, ...self::CHAMPS_TABLEAU] as $champ) {
                    $valeur = $ligne->{$champ};

                    if ($valeur === null || $valeur === '' || ! $this->dejaChiffre($valeur)) {
                        continue;
                    }

                    $modifications[$champ] = Crypt::decryptString($valeur);
                }

                if ($modifications) {
                    DB::table('employees')->where('id', $ligne->id)->update($modifications);
                }
            }
        });

        Schema::table('employees', function (Blueprint $table) {
            foreach (self::CHAMPS_TEXTE as $champ) {
                $table->string($champ)->nullable()->change();
            }
        });
    }

    /**
     * Un chiffré fait deux cents caractères là où la colonne en acceptait
     * cinquante : sans cet élargissement, MySQL tronquerait silencieusement et
     * la valeur deviendrait indéchiffrable.
     */
    private function elargirLesColonnes(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            foreach (self::CHAMPS_TEXTE as $champ) {
                $table->text($champ)->nullable()->change();
            }
        });
    }

    /**
     * Garde d'idempotence : rejouer la migration ne doit pas chiffrer deux
     * fois. Le déchiffrement réussit si et seulement si la valeur l'est déjà.
     */
    private function dejaChiffre(mixed $valeur): bool
    {
        if (! is_string($valeur)) {
            return false;
        }

        try {
            Crypt::decryptString($valeur);

            return true;
        } catch (\Throwable) {
            return false;
        }
    }
};
