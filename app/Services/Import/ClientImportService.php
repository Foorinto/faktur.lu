<?php

namespace App\Services\Import;

use App\Models\Client;
use App\Models\Import\ImportSession;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ClientImportService
{
    /**
     * Champs disponibles pour le mapping.
     */
    public const AVAILABLE_FIELDS = [
        'name' => ['label' => 'Nom de l\'entreprise', 'required' => true],
        'contact_name' => ['label' => 'Nom du contact', 'required' => false],
        'email' => ['label' => 'Email', 'required' => false],
        'phone' => ['label' => 'Téléphone', 'required' => false],
        'vat_number' => ['label' => 'Numéro de TVA', 'required' => false],
        'registration_number' => ['label' => 'Numéro RCS / SIRET', 'required' => false],
        'address' => ['label' => 'Adresse', 'required' => false],
        'postal_code' => ['label' => 'Code postal', 'required' => false],
        'city' => ['label' => 'Ville', 'required' => false],
        'country_code' => ['label' => 'Pays (code ISO ou nom)', 'required' => false],
        'type' => ['label' => 'Type (B2B/B2C)', 'required' => false],
        'status' => ['label' => 'Statut', 'required' => false],
        'source' => ['label' => 'Source', 'required' => false],
        'estimated_value' => ['label' => 'Valeur estimée', 'required' => false],
        'currency' => ['label' => 'Devise', 'required' => false],
        'notes' => ['label' => 'Notes', 'required' => false],
    ];

    /**
     * Mots-clés pour la détection automatique du mapping.
     */
    protected array $fieldKeywords = [
        'name' => ['name', 'nom', 'company', 'société', 'entreprise', 'client', 'organisation', 'organization', 'firma'],
        'contact_name' => ['contact', 'contact_name', 'first_name', 'prénom', 'last_name', 'nom_contact'],
        'email' => ['email', 'mail', 'e-mail', 'courriel'],
        'phone' => ['phone', 'tel', 'telephone', 'téléphone', 'mobile', 'gsm'],
        'vat_number' => ['vat', 'tva', 'vat_number', 'vat number', 'numero_tva', 'n° tva', 'btw', 'mwst'],
        'registration_number' => ['siret', 'siren', 'rcs', 'registration', 'matricule'],
        'address' => ['address', 'adresse', 'street', 'rue'],
        'postal_code' => ['postal', 'zip', 'code_postal', 'cp', 'plz'],
        'city' => ['city', 'ville', 'town', 'stadt', 'commune'],
        'country_code' => ['country', 'pays', 'land', 'country_code'],
        'type' => ['type', 'b2b', 'b2c'],
        'status' => ['status', 'statut'],
        'source' => ['source', 'origin', 'origine'],
        'estimated_value' => ['value', 'valeur', 'estimated', 'amount', 'montant'],
        'currency' => ['currency', 'devise', 'cur'],
        'notes' => ['notes', 'note', 'comment', 'commentaire', 'remarque', 'description'],
    ];

    /**
     * Parse un fichier Excel/CSV et retourne les headers et un aperçu.
     */
    public function parseFile(string $path): array
    {
        $rows = $this->readRows($path);

        if (empty($rows)) {
            return ['headers' => [], 'preview_data' => [], 'total_rows' => 0];
        }

        $headers = array_filter(array_shift($rows), fn($h) => !empty($h));
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

        if (!$bestSheet) {
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
            $nonEmpty = array_filter($row, fn($v) => $v !== null && $v !== '');
            if (count($nonEmpty) >= 2) {
                $startIndex = $i;
                break;
            }
        }

        $rows = array_slice($rows, $startIndex);

        // Trim aussi les colonnes vides à droite (basé sur le header)
        if (!empty($rows)) {
            $header = $rows[0];
            $lastCol = 0;
            foreach ($header as $i => $cell) {
                if ($cell !== null && $cell !== '') {
                    $lastCol = $i;
                }
            }
            $rows = array_map(fn($r) => array_slice($r, 0, $lastCol + 1), $rows);
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
     * Valide les données et détecte les doublons.
     */
    public function validateAndPreview(ImportSession $session): array
    {
        $path = Storage::path($session->storage_path);
        $rows = $this->readRows($path);
        $headers = array_shift($rows); // Remove header row
        $mapping = $session->mapping;

        $validRows = [];
        $duplicateRows = [];
        $errorRows = [];

        $existingEmails = Client::where('user_id', $session->user_id)
            ->whereNotNull('email')
            ->pluck('email')
            ->map(fn($e) => strtolower($e))
            ->toArray();

        $existingNames = Client::where('user_id', $session->user_id)
            ->pluck('name')
            ->map(fn($n) => strtolower($n))
            ->toArray();

        foreach ($rows as $rowIndex => $row) {
            $clientData = $this->mapRowToClient($row, $headers, $mapping);

            // Validation
            if (empty($clientData['name'])) {
                $errorRows[] = [
                    'row' => $rowIndex + 2,
                    'data' => $clientData,
                    'error' => 'Le nom est obligatoire',
                ];
                continue;
            }

            if (!empty($clientData['email']) && !filter_var($clientData['email'], FILTER_VALIDATE_EMAIL)) {
                $errorRows[] = [
                    'row' => $rowIndex + 2,
                    'data' => $clientData,
                    'error' => 'Email invalide',
                ];
                continue;
            }

            // Détection de doublon
            $isDuplicate = false;
            if (!empty($clientData['email']) && in_array(strtolower($clientData['email']), $existingEmails)) {
                $isDuplicate = true;
            } elseif (in_array(strtolower($clientData['name']), $existingNames)) {
                $isDuplicate = true;
            }

            if ($isDuplicate) {
                $duplicateRows[] = [
                    'row' => $rowIndex + 2,
                    'data' => $clientData,
                ];
            } else {
                $validRows[] = [
                    'row' => $rowIndex + 2,
                    'data' => $clientData,
                ];
            }
        }

        $session->update([
            'total_rows' => count($rows),
            'valid_rows' => count($validRows),
            'duplicate_rows' => count($duplicateRows),
            'error_rows' => count($errorRows),
            'status' => 'preview',
        ]);

        return [
            'valid' => $validRows,
            'duplicates' => $duplicateRows,
            'errors' => $errorRows,
        ];
    }

    /**
     * Importe les clients en base.
     */
    public function import(ImportSession $session, ?string $defaultStatus = null): void
    {
        $session->update([
            'status' => 'processing',
            'started_at' => now(),
        ]);

        try {
            $path = Storage::path($session->storage_path);
            $rows = $this->readRows($path);
            $headers = array_shift($rows);
            $mapping = $session->mapping;
            $strategy = $session->duplicate_strategy;
            $forcedStatus = $defaultStatus && in_array($defaultStatus, Client::STATUSES) ? $defaultStatus : null;

            $imported = 0;
            $updated = 0;
            $skipped = 0;
            $errors = [];

            $existingClients = Client::where('user_id', $session->user_id)
                ->get()
                ->keyBy(fn($c) => strtolower($c->email ?: $c->name));

            foreach ($rows as $rowIndex => $row) {
                try {
                    $clientData = $this->mapRowToClient($row, $headers, $mapping);

                    // Skip lignes invalides
                    if (empty($clientData['name'])) {
                        $skipped++;
                        continue;
                    }

                    if (!empty($clientData['email']) && !filter_var($clientData['email'], FILTER_VALIDATE_EMAIL)) {
                        unset($clientData['email']);
                    }

                    $key = strtolower(!empty($clientData['email']) ? $clientData['email'] : $clientData['name']);
                    $existing = $existingClients->get($key);

                    if ($existing) {
                        if ($strategy === 'skip') {
                            $skipped++;
                            continue;
                        } elseif ($strategy === 'update') {
                            $existing->update($clientData);
                            $updated++;
                            continue;
                        }
                    }

                    // Valeurs par défaut sécurisées
                    $type = strtolower($clientData['type'] ?? 'b2b');
                    if (!in_array($type, ['b2b', 'b2c'])) {
                        $type = 'b2b';
                    }

                    $status = $forcedStatus ?? ($clientData['status'] ?? 'prospect');
                    if (!in_array($status, Client::STATUSES)) {
                        $status = 'prospect';
                    }

                    Client::create(array_merge($clientData, [
                        'user_id' => $session->user_id,
                        'type' => $type,
                        'status' => $status,
                        'currency' => $clientData['currency'] ?? 'EUR',
                        'country_code' => $clientData['country_code'] ?? 'LU',
                    ]));
                    $imported++;
                } catch (\Exception $e) {
                    $errors[] = [
                        'row' => $rowIndex + 2,
                        'message' => $e->getMessage(),
                    ];
                    $skipped++;
                }
            }

            $session->update([
                'imported_count' => $imported,
                'updated_count' => $updated,
                'skipped_count' => $skipped,
                'errors' => $errors,
                'status' => 'completed',
                'completed_at' => now(),
            ]);
        } catch (\Exception $e) {
            $session->update([
                'status' => 'failed',
                'errors' => ['message' => $e->getMessage()],
            ]);
            throw $e;
        }
    }

    /**
     * Mappe une ligne de données vers un tableau client.
     */
    protected function mapRowToClient(array $row, array $headers, array $mapping): array
    {
        $client = [];

        foreach ($headers as $index => $header) {
            $field = $mapping[$header] ?? 'ignore';
            if ($field === 'ignore') {
                continue;
            }

            $value = $row[$index] ?? null;
            if ($value === null || $value === '') {
                continue;
            }

            $client[$field] = trim((string) $value);
        }

        // Normalisations
        if (isset($client['country_code'])) {
            $client['country_code'] = $this->normalizeCountry($client['country_code']);
        }

        if (isset($client['type'])) {
            $client['type'] = strtolower($client['type']) === 'b2c' ? 'b2c' : 'b2b';
        }

        return $client;
    }

    /**
     * Normalise un nom de pays vers son code ISO.
     */
    protected function normalizeCountry(string $country): string
    {
        $country = strtoupper(trim($country));

        if (strlen($country) === 2) {
            return $country;
        }

        $map = [
            'LUXEMBOURG' => 'LU',
            'FRANCE' => 'FR',
            'BELGIQUE' => 'BE', 'BELGIUM' => 'BE',
            'ALLEMAGNE' => 'DE', 'GERMANY' => 'DE', 'DEUTSCHLAND' => 'DE',
            'PAYS-BAS' => 'NL', 'NETHERLANDS' => 'NL',
            'ITALIE' => 'IT', 'ITALY' => 'IT',
            'ESPAGNE' => 'ES', 'SPAIN' => 'ES',
            'PORTUGAL' => 'PT',
            'ROYAUME-UNI' => 'GB', 'UNITED KINGDOM' => 'GB', 'UK' => 'GB',
            'SUISSE' => 'CH', 'SWITZERLAND' => 'CH',
        ];

        return $map[$country] ?? 'LU';
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
