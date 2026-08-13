<?php

namespace App\Services;

use App\Models\BusinessSettings;
use App\Models\HR\Employee;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

/**
 * Vigie de la clé d'application.
 *
 * `APP_KEY` déchiffre les IBAN, la configuration de messagerie et les données
 * personnelles des salariés. Sa perte est irréversible : le chiffrement est
 * symétrique, il n'existe aucune procédure de secours.
 *
 * Le danger n'est pas qu'elle change — c'est que rien ne le signale. Les
 * lectures échouent une à une, souvent dans des écrans peu visités, pendant que
 * les écritures suivantes se font sous la nouvelle clé. Au bout de quelques
 * jours, deux jeux de données coexistent qu'aucune clé unique ne peut plus
 * lire, et remettre l'ancienne ne suffit plus.
 *
 * D'où deux contrôles complémentaires :
 *
 *  - l'EMPREINTE dit que la clé a changé, immédiatement, même si aucune donnée
 *    chiffrée n'existe encore ;
 *  - le DÉCHIFFREMENT D'ÉPREUVE dit si le changement a fait des dégâts. Une
 *    empreinte différente sur une base sans données chiffrées est bénigne ;
 *    la même empreinte différente sur des IBAN illisibles ne l'est pas.
 */
class EncryptionKeyGuard
{
    public const ETAT_CONFORME = 'conforme';
    public const ETAT_PREMIER_ENREGISTREMENT = 'premier_enregistrement';
    public const ETAT_CHANGEE_SANS_DEGAT = 'changee_sans_degat';
    public const ETAT_DONNEES_ILLISIBLES = 'donnees_illisibles';

    /**
     * @return array{etat: string, message: string, echantillons: int, echecs: int}
     */
    public function verifier(): array
    {
        $empreinte = $this->empreinteCourante();
        $connue = DB::table('encryption_fingerprints')->orderByDesc('id')->first();

        [$echantillons, $echecs] = $this->eprouverLeDechiffrement();

        if (! $connue) {
            $this->enregistrer($empreinte, 'premier enregistrement');

            return [
                'etat' => self::ETAT_PREMIER_ENREGISTREMENT,
                'message' => "Empreinte de clé enregistrée pour la première fois. Les contrôles suivants s'y compareront.",
                'echantillons' => $echantillons,
                'echecs' => $echecs,
            ];
        }

        if (hash_equals($connue->fingerprint, $empreinte) && $echecs === 0) {
            return [
                'etat' => self::ETAT_CONFORME,
                'message' => $echantillons > 0
                    ? "Clé inchangée, {$echantillons} valeur(s) chiffrée(s) relue(s) sans erreur."
                    : 'Clé inchangée. Aucune donnée chiffrée à éprouver.',
                'echantillons' => $echantillons,
                'echecs' => $echecs,
            ];
        }

        // Des valeurs illisibles priment sur l'empreinte : c'est le dégât
        // constaté, pas seulement le soupçon.
        if ($echecs > 0) {
            return [
                'etat' => self::ETAT_DONNEES_ILLISIBLES,
                'message' => "{$echecs} valeur(s) chiffrée(s) sur {$echantillons} ne se déchiffrent plus. "
                    ."La clé d'application ne correspond plus aux données. NE RIEN ÉCRIRE avant d'avoir "
                    ."rétabli l'APP_KEY d'origine : chaque enregistrement ajoute des données que "
                    ."l'ancienne clé ne saura pas lire non plus.",
                'echantillons' => $echantillons,
                'echecs' => $echecs,
            ];
        }

        return [
            'etat' => self::ETAT_CHANGEE_SANS_DEGAT,
            'message' => "L'APP_KEY a changé depuis le dernier contrôle, mais aucune donnée chiffrée "
                ."n'est illisible — soit il n'y en a pas encore, soit le changement était voulu. "
                ."Confirmer avec « encryption:check --accepter » si la rotation est intentionnelle.",
            'echantillons' => $echantillons,
            'echecs' => $echecs,
        ];
    }

    /** Acte une rotation volontaire de la clé. */
    public function accepterLaCleCourante(string $note = 'rotation acceptée'): void
    {
        $this->enregistrer($this->empreinteCourante(), $note);
    }

    /**
     * Éprouve la clé sur des valeurs réellement en base.
     *
     * Un échantillon suffit par colonne : si la clé est mauvaise, elle l'est
     * pour toutes. On lit par le constructeur de requêtes, sans Eloquent, pour
     * que le cast ne lève pas avant d'avoir pu compter.
     *
     * @return array{0: int, 1: int} nombre de valeurs éprouvées, nombre d'échecs
     */
    private function eprouverLeDechiffrement(): array
    {
        $cibles = [
            [(new BusinessSettings)->getTable(), 'iban'],
            [(new Employee)->getTable(), 'bank_iban'],
            [(new Employee)->getTable(), 'nationality'],
            ['email_settings', 'provider_config'],
        ];

        $eprouvees = 0;
        $echecs = 0;

        foreach ($cibles as [$table, $colonne]) {
            $valeur = $this->premiereValeurNonVide($table, $colonne);

            if ($valeur === null) {
                continue;
            }

            $eprouvees++;

            try {
                Crypt::decryptString($valeur);
            } catch (\Throwable) {
                $echecs++;
            }
        }

        return [$eprouvees, $echecs];
    }

    private function premiereValeurNonVide(string $table, string $colonne): ?string
    {
        try {
            $valeur = DB::table($table)
                ->whereNotNull($colonne)
                ->where($colonne, '!=', '')
                ->value($colonne);
        } catch (\Throwable) {
            // Table ou colonne absente : rien à éprouver, ce n'est pas un échec.
            return null;
        }

        return is_string($valeur) ? $valeur : null;
    }

    /**
     * La clé n'est jamais stockée, seulement son condensé. La table ne doit
     * aider personne à déchiffrer quoi que ce soit.
     */
    private function empreinteCourante(): string
    {
        return hash('sha256', (string) config('app.key'));
    }

    private function enregistrer(string $empreinte, string $note): void
    {
        DB::table('encryption_fingerprints')->insert([
            'fingerprint' => $empreinte,
            'recorded_at' => now(),
            'note' => $note,
        ]);
    }
}
