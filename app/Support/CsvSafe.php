<?php

namespace App\Support;

/**
 * Neutralise l'injection de formules dans les fichiers CSV/XLSX (CWE-1236).
 *
 * Un tableur (Excel, LibreOffice) interprète comme formule toute cellule
 * commençant par =, +, -, @, une tabulation ou un retour chariot. Une valeur
 * contrôlée par l'utilisateur (nom de client, référence, libellé) du type
 * « =HYPERLINK(...) » ou « =cmd|'/c calc'!A1 » s'exécute alors sur le poste de
 * celui qui ouvre l'export — ici, le comptable.
 *
 * On préfixe une apostrophe : le tableur affiche la valeur littéralement sans
 * l'évaluer, et l'apostrophe elle-même reste invisible à l'ouverture.
 */
class CsvSafe
{
    public static function field(?string $value): string
    {
        $value = (string) $value;

        if ($value === '') {
            return $value;
        }

        if (in_array($value[0], ['=', '+', '-', '@', "\t", "\r"], true)) {
            return "'".$value;
        }

        return $value;
    }
}
