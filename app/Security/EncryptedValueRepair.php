<?php

namespace App\Security;

use App\Casts\EncryptedIban;
use App\Casts\EncryptedText;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

/**
 * Remettre au propre les valeurs chiffrées que l'application ne sait pas lire.
 *
 * Les migrations de chiffrement (les IBAN le 2026-07-25, les données des
 * salariés le 2026-08-13) ont laissé telles quelles les valeurs vides. Or le
 * cast `encrypted` de Laravel lève une exception sur une chaîne vide, et la
 * page qui la lit répond une erreur 500. Constaté en production le 2026-09-22.
 *
 * Ici, colonne par colonne : une valeur vide est chiffrée vide (la colonne
 * `business_settings.iban` refuse le NULL, et le vide chiffré se relit comme
 * « pas de valeur »), une valeur en clair est chiffrée (un IBAN n'est reconnu
 * que s'il en a la forme), une valeur déjà chiffrée n'est pas touchée. Ce qui
 * n'est rien de tout ça est laissé tel quel et compté, pour qu'on le regarde
 * plutôt que de le perdre.
 *
 * Idempotent : rejouer ne change rien.
 */
class EncryptedValueRepair
{
    public const IBAN = 'iban';

    public const TEXTE = 'texte';

    public const TABLEAU = 'tableau';

    /** @var array<string, array<string, string>> table => [colonne => nature] */
    public const COLUMNS = [
        'business_settings' => ['iban' => self::IBAN],
        'employees' => [
            'bank_iban' => self::IBAN,
            'nationality' => self::TEXTE,
            'phone_perso' => self::TEXTE,
            'email_perso' => self::TEXTE,
            'address' => self::TEXTE,
            'city' => self::TEXTE,
            'postal_code' => self::TEXTE,
            'benefits' => self::TABLEAU,
            'emergency_contact' => self::TABLEAU,
        ],
    ];

    /**
     * @return array<string, array{vides: int, chiffres: int, inconnus: int}>
     */
    public function run(): array
    {
        $bilan = [];

        foreach (self::COLUMNS as $table => $colonnes) {
            $bilan[$table] = ['vides' => 0, 'chiffres' => 0, 'inconnus' => 0];

            DB::table($table)
                ->select(['id', ...array_keys($colonnes)])
                ->chunkById(200, function ($lignes) use ($table, $colonnes, &$bilan) {
                    foreach ($lignes as $ligne) {
                        $modifications = [];

                        foreach ($colonnes as $colonne => $nature) {
                            $valeur = $ligne->{$colonne};

                            if ($valeur === null) {
                                continue;
                            }

                            if (trim((string) $valeur) === '') {
                                $modifications[$colonne] = Crypt::encryptString('');
                                $bilan[$table]['vides']++;

                                continue;
                            }

                            if ($this->dejaChiffre((string) $valeur)) {
                                continue;
                            }

                            $clair = $this->enClair((string) $valeur, $nature);

                            if ($clair === null) {
                                $bilan[$table]['inconnus']++;

                                continue;
                            }

                            $modifications[$colonne] = Crypt::encryptString($clair);
                            $bilan[$table]['chiffres']++;
                        }

                        if ($modifications !== []) {
                            DB::table($table)->where('id', $ligne->id)->update($modifications);
                        }
                    }
                });
        }

        return $bilan;
    }

    /** La valeur en clair à chiffrer, ou null si on ne la reconnaît pas. */
    private function enClair(string $valeur, string $nature): ?string
    {
        return match ($nature) {
            self::IBAN => EncryptedIban::looksLikeIban($valeur) ? EncryptedIban::normalize($valeur) : null,
            self::TEXTE => EncryptedText::looksEncrypted($valeur) ? null : $valeur,
            self::TABLEAU => is_array(json_decode($valeur, true)) ? $valeur : null,
            default => null,
        };
    }

    private function dejaChiffre(string $valeur): bool
    {
        try {
            Crypt::decryptString($valeur);

            return true;
        } catch (DecryptException) {
            return false;
        }
    }
}
