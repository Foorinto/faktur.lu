<?php

namespace App\Security;

use Illuminate\Support\Facades\DB;

/**
 * Rattrapage du journal d'audit : masquer ce qui y est entré en clair.
 *
 * Jusqu'au 2026-09-22, le journal recopiait les attributs chiffrés de la base
 * en clair, IBAN des réglages, IBAN et données personnelles des salariés.
 * Les nouvelles entrées sont masquées à la source (AuditLogger) ; celles qui
 * existent déjà sont reprises ici, une fois, par la migration du même jour.
 *
 * Idempotent : un IBAN déjà masqué le reste, un « *** » aussi. Rejouer ne
 * change rien, et c'est ce qui permet de tester la routine sur des lignes
 * posées à la main.
 */
class AuditLogMasker
{
    /** Les clés à masquer, quel que soit le modèle qui les a écrites. */
    public const KEYS = [
        'iban', 'bank_iban',
        'nationality', 'phone_perso', 'email_perso', 'address', 'city', 'postal_code',
    ];

    /**
     * @return int nombre de lignes réécrites
     */
    public function run(): int
    {
        $reecrites = 0;

        DB::table('audit_logs')
            ->select('id', 'old_values', 'new_values')
            ->where(function ($q) {
                foreach (self::KEYS as $key) {
                    $q->orWhere('old_values', 'like', '%"'.$key.'"%')
                        ->orWhere('new_values', 'like', '%"'.$key.'"%');
                }
            })
            ->orderBy('id')
            ->chunkById(200, function ($lignes) use (&$reecrites) {
                foreach ($lignes as $ligne) {
                    $old = $this->masquer($ligne->old_values);
                    $new = $this->masquer($ligne->new_values);

                    if ($old === $ligne->old_values && $new === $ligne->new_values) {
                        continue;
                    }

                    DB::table('audit_logs')->where('id', $ligne->id)->update([
                        'old_values' => $old,
                        'new_values' => $new,
                    ]);
                    $reecrites++;
                }
            });

        return $reecrites;
    }

    /**
     * Masque les clés sensibles d'une colonne JSON, ou la rend telle quelle si
     * elle ne contient rien à masquer.
     */
    private function masquer(?string $json): ?string
    {
        if ($json === null || $json === '') {
            return $json;
        }

        $valeurs = json_decode($json, true);

        if (! is_array($valeurs)) {
            return $json;
        }

        $touche = false;

        foreach (self::KEYS as $key) {
            if (! array_key_exists($key, $valeurs) || $valeurs[$key] === null || $valeurs[$key] === '') {
                continue;
            }

            $masque = Mask::value($key, (string) $valeurs[$key]);

            if ($masque !== $valeurs[$key]) {
                $valeurs[$key] = $masque;
                $touche = true;
            }
        }

        return $touche ? json_encode($valeurs, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : $json;
    }
}
