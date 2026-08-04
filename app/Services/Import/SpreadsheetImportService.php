<?php

namespace App\Services\Import;

use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Socle commun aux imports de tableur (clients, articles, …).
 *
 * Regroupe ce qui ne dépend pas de l'entité importée : lecture du fichier,
 * choix de la feuille, nettoyage des lignes de titre, et détection automatique
 * des colonnes. C'est cette partie qui fait qu'un import ne réclame aucun
 * formatage préalable — l'utilisateur dépose son fichier tel quel et corrige à
 * l'écran ce que la détection n'a pas deviné.
 *
 * Chaque sous-classe apporte :
 * - la constante `AVAILABLE_FIELDS` (champs proposés à l'écran de correspondance),
 * - la propriété `$fieldKeywords` (synonymes FR/EN/DE par champ),
 * - ses méthodes métier de validation et d'import.
 */
abstract class SpreadsheetImportService
{
    /**
     * Synonymes reconnus par champ, pour la détection automatique.
     *
     * @var array<string, array<int, string>>
     */
    protected array $fieldKeywords = [];

    /**
     * Parse un fichier Excel/CSV et retourne les headers et un aperçu.
     */
    public function parseFile(string $path): array
    {
        $rows = $this->readRows($path);

        if (empty($rows)) {
            return ['headers' => [], 'preview_data' => [], 'total_rows' => 0];
        }

        $headers = array_filter(array_shift($rows), fn ($h) => ! empty($h));
        $previewRows = array_slice($rows, 0, 5);

        return [
            'headers' => array_values($headers),
            'preview_data' => $previewRows,
            'total_rows' => count($rows),
        ];
    }

    /**
     * Lit toutes les lignes d'un fichier Excel/CSV.
     * Sélectionne automatiquement la feuille avec le plus de données utiles
     * et skip les lignes vides en début.
     */
    protected function readRows(string $path): array
    {
        $reader = IOFactory::createReaderForFile($path);
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($path);

        // Trouver la feuille avec le plus de données structurées
        $bestSheet = null;
        $bestScore = 0;

        foreach ($spreadsheet->getAllSheets() as $sheet) {
            $rows = $sheet->toArray(null, true, true, false);
            $score = $this->scoreSheet($rows);
            if ($score > $bestScore) {
                $bestScore = $score;
                $bestSheet = $sheet;
            }
        }

        if (! $bestSheet) {
            return [];
        }

        $rows = $bestSheet->toArray(null, true, true, false);

        return $this->trimEmptyRows($rows);
    }

    /**
     * Calcule un score pour une feuille basé sur le nombre de cellules non vides.
     */
    protected function scoreSheet(array $rows): int
    {
        $score = 0;
        foreach ($rows as $row) {
            foreach ($row as $cell) {
                if ($cell !== null && $cell !== '') {
                    $score++;
                }
            }
        }

        return $score;
    }

    /**
     * Skip les lignes vides en début de feuille jusqu'à trouver une vraie ligne de header.
     */
    protected function trimEmptyRows(array $rows): array
    {
        $startIndex = 0;
        foreach ($rows as $i => $row) {
            $nonEmpty = array_filter($row, fn ($v) => $v !== null && $v !== '');
            if (count($nonEmpty) >= 2) {
                $startIndex = $i;
                break;
            }
        }

        $rows = array_slice($rows, $startIndex);

        // Trim aussi les colonnes vides à droite (basé sur le header)
        if (! empty($rows)) {
            $header = $rows[0];
            $lastCol = 0;
            foreach ($header as $i => $cell) {
                if ($cell !== null && $cell !== '') {
                    $lastCol = $i;
                }
            }
            $rows = array_map(fn ($r) => array_slice($r, 0, $lastCol + 1), $rows);
        }

        return $rows;
    }

    /**
     * Détecte automatiquement le mapping en fonction des en-têtes.
     */
    public function autoDetectMapping(array $headers): array
    {
        $mapping = [];

        foreach ($headers as $index => $header) {
            $normalized = $this->normalize($header);
            $bestMatch = null;
            $bestScore = 0;

            foreach ($this->fieldKeywords as $field => $keywords) {
                foreach ($keywords as $keyword) {
                    $score = $this->similarity($normalized, $this->normalize($keyword));
                    if ($score > $bestScore && $score > 0.7) {
                        $bestScore = $score;
                        $bestMatch = $field;
                    }
                }
            }

            $mapping[$header] = $bestMatch ?? 'ignore';
        }

        return $mapping;
    }

    /**
     * Applique le mapping à une ligne : en-tête du fichier => champ de l'entité.
     *
     * Les valeurs vides sont écartées plutôt que stockées en chaîne vide, pour
     * que les valeurs par défaut du modèle s'appliquent.
     */
    protected function mapRow(array $row, array $headers, array $mapping): array
    {
        $mapped = [];

        foreach ($headers as $index => $header) {
            $field = $mapping[$header] ?? 'ignore';
            if ($field === 'ignore') {
                continue;
            }

            $value = $row[$index] ?? null;
            if ($value === null || $value === '') {
                continue;
            }

            $mapped[$field] = trim((string) $value);
        }

        return $mapped;
    }

    /**
     * Normalise une chaîne pour comparaison.
     */
    protected function normalize(string $str): string
    {
        $str = mb_strtolower($str);
        $str = preg_replace('/[^a-z0-9]+/', '_', $str);

        return trim($str, '_');
    }

    /**
     * Calcule la similarité entre deux chaînes (0-1).
     */
    protected function similarity(string $a, string $b): float
    {
        if ($a === $b) {
            return 1.0;
        }

        if (str_contains($a, $b) || str_contains($b, $a)) {
            return 0.9;
        }

        similar_text($a, $b, $percent);

        return $percent / 100;
    }
}
