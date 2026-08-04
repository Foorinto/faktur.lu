<?php

namespace App\Services\Import;

use App\Models\BusinessSettings;
use App\Models\Import\ImportSession;
use App\Models\InvoiceItem;
use App\Models\Product;
use App\Models\User;
use App\Rules\SalesVatRateAllowed;
use App\Services\PlanService;
use Illuminate\Support\Facades\Storage;

/**
 * Import du catalogue d'articles depuis un tableur.
 *
 * Le socle commun (lecture du fichier, choix de la feuille, détection des
 * colonnes) vient de SpreadsheetImportService : l'utilisateur dépose son
 * fichier tel quel, sans reformatage. Ne reste ici que ce qui est propre aux
 * articles — et l'essentiel s'y joue dans les normalisations : un catalogue
 * exporté d'un autre outil arrive avec des prix « 1 234,56 € », des taux
 * « 17 % » et des unités écrites en toutes lettres.
 */
class ProductImportService extends SpreadsheetImportService
{
    public function __construct(private readonly PlanService $planService) {}

    /**
     * Champs disponibles pour le mapping.
     */
    public const AVAILABLE_FIELDS = [
        'designation' => ['label' => 'Désignation', 'required' => true],
        'reference' => ['label' => 'Référence', 'required' => false],
        'type' => ['label' => 'Famille (produit / prestation)', 'required' => false],
        'description' => ['label' => 'Description', 'required' => false],
        'unit_price_ht' => ['label' => 'Prix HT', 'required' => true],
        'vat_rate' => ['label' => 'Taux de TVA', 'required' => false],
        'unit' => ['label' => 'Unité', 'required' => false],
    ];

    /**
     * Mots-clés pour la détection automatique du mapping.
     *
     * L'ordre compte : la première correspondance de score égal l'emporte.
     * « Art » (allemand pour « type ») est volontairement absent — il serait
     * reconnu dans « Article » et détournerait la colonne de désignation.
     */
    protected array $fieldKeywords = [
        'designation' => ['designation', 'désignation', 'libellé', 'libelle', 'article', 'produit', 'prestation', 'intitulé', 'name', 'nom', 'bezeichnung', 'artikel'],
        'reference' => ['reference', 'référence', 'ref', 'sku', 'code', 'referenz'],
        'type' => ['type', 'famille', 'categorie', 'catégorie', 'nature'],
        'description' => ['description', 'détail', 'detail', 'beschreibung'],
        'unit_price_ht' => ['prix', 'prix ht', 'prix_ht', 'prix unitaire', 'tarif', 'price', 'unit price', 'preis', 'montant'],
        'vat_rate' => ['tva', 'taux', 'taux tva', 'vat', 'vat rate', 'mwst', 'mehrwertsteuer'],
        'unit' => ['unite', 'unité', 'unit', 'einheit'],
    ];

    /** Libellés d'unité acceptés à l'import, vers la clé interne. */
    private const UNIT_ALIASES = [
        'heure' => InvoiceItem::UNIT_HOUR, 'heures' => InvoiceItem::UNIT_HOUR, 'h' => InvoiceItem::UNIT_HOUR,
        'hour' => InvoiceItem::UNIT_HOUR, 'hours' => InvoiceItem::UNIT_HOUR, 'stunde' => InvoiceItem::UNIT_HOUR,
        'jour' => InvoiceItem::UNIT_DAY, 'jours' => InvoiceItem::UNIT_DAY, 'j' => InvoiceItem::UNIT_DAY,
        'day' => InvoiceItem::UNIT_DAY, 'days' => InvoiceItem::UNIT_DAY, 'tag' => InvoiceItem::UNIT_DAY,
        'piece' => InvoiceItem::UNIT_PIECE, 'pièce' => InvoiceItem::UNIT_PIECE, 'pieces' => InvoiceItem::UNIT_PIECE,
        'unite' => InvoiceItem::UNIT_PIECE, 'unité' => InvoiceItem::UNIT_PIECE, 'u' => InvoiceItem::UNIT_PIECE,
        'stück' => InvoiceItem::UNIT_PIECE, 'stuck' => InvoiceItem::UNIT_PIECE,
        'paquet' => InvoiceItem::UNIT_PACKAGE, 'package' => InvoiceItem::UNIT_PACKAGE, 'lot' => InvoiceItem::UNIT_PACKAGE,
        'mois' => InvoiceItem::UNIT_MONTH, 'month' => InvoiceItem::UNIT_MONTH, 'monat' => InvoiceItem::UNIT_MONTH,
        'mot' => InvoiceItem::UNIT_WORD, 'mots' => InvoiceItem::UNIT_WORD, 'word' => InvoiceItem::UNIT_WORD,
        'page' => InvoiceItem::UNIT_PAGE, 'pages' => InvoiceItem::UNIT_PAGE, 'seite' => InvoiceItem::UNIT_PAGE,
    ];

    /** Libellés de famille acceptés à l'import. */
    private const TYPE_ALIASES = [
        'produit' => Product::TYPE_PRODUCT, 'produits' => Product::TYPE_PRODUCT, 'product' => Product::TYPE_PRODUCT,
        'bien' => Product::TYPE_PRODUCT, 'marchandise' => Product::TYPE_PRODUCT, 'ware' => Product::TYPE_PRODUCT,
        'produkt' => Product::TYPE_PRODUCT, 'p' => Product::TYPE_PRODUCT,
        'prestation' => Product::TYPE_SERVICE, 'prestations' => Product::TYPE_SERVICE, 'presta' => Product::TYPE_SERVICE,
        'service' => Product::TYPE_SERVICE, 'services' => Product::TYPE_SERVICE, 'dienstleistung' => Product::TYPE_SERVICE,
        's' => Product::TYPE_SERVICE,
    ];

    /**
     * Places encore disponibles dans le quota d'articles, ou null si illimité.
     *
     * Même précaution que pour les clients : l'import ne doit pas être une
     * porte dérobée qui contourne le plafond du plan.
     */
    private function remainingProductSlots(ImportSession $session): ?int
    {
        $owner = User::find($session->user_id);

        if (! $owner) {
            return null;
        }

        $limit = $this->planService->getUserPlan($owner)->getLimit('max_products');

        if ($limit === null) {
            return null;
        }

        return max(0, $limit - Product::withoutUserScope()->where('user_id', $session->user_id)->count());
    }

    /**
     * Valide les données et détecte les doublons.
     */
    public function validateAndPreview(ImportSession $session): array
    {
        $rows = $this->readRows(Storage::path($session->storage_path));
        $headers = array_shift($rows);
        $mapping = $session->mapping;

        $validRows = [];
        $duplicateRows = [];
        $errorRows = [];

        [$existingReferences, $existingDesignations] = $this->existingKeys($session->user_id);

        foreach ($rows as $rowIndex => $row) {
            $data = $this->mapRowToProduct($row, $headers, $mapping);
            $error = $this->validateRow($data);

            if ($error !== null) {
                $errorRows[] = ['row' => $rowIndex + 2, 'data' => $data, 'error' => $error];

                continue;
            }

            $isDuplicate = (! empty($data['reference']) && in_array(mb_strtolower($data['reference']), $existingReferences, true))
                || in_array(mb_strtolower($data['designation']), $existingDesignations, true);

            if ($isDuplicate) {
                $duplicateRows[] = ['row' => $rowIndex + 2, 'data' => $data];
            } else {
                $validRows[] = ['row' => $rowIndex + 2, 'data' => $data];
            }
        }

        $session->update([
            'total_rows' => count($rows),
            'valid_rows' => count($validRows),
            'duplicate_rows' => count($duplicateRows),
            'error_rows' => count($errorRows),
            'status' => 'preview',
        ]);

        return ['valid' => $validRows, 'duplicates' => $duplicateRows, 'errors' => $errorRows];
    }

    /**
     * Importe les articles en base.
     */
    public function import(ImportSession $session): void
    {
        $session->update(['status' => 'processing', 'started_at' => now()]);

        try {
            $rows = $this->readRows(Storage::path($session->storage_path));
            $headers = array_shift($rows);
            $mapping = $session->mapping;
            $strategy = $session->duplicate_strategy;

            $imported = 0;
            $updated = 0;
            $skipped = 0;
            $errors = [];
            $quotaBlocked = 0;

            // Calculé une fois : l'import est le seul écrivain pendant sa
            // propre exécution, recompter à chaque ligne serait sans effet.
            $remainingSlots = $this->remainingProductSlots($session);

            $existing = Product::withoutUserScope()
                ->where('user_id', $session->user_id)
                ->get()
                ->keyBy(fn ($p) => mb_strtolower($p->reference ?: $p->designation));

            foreach ($rows as $rowIndex => $row) {
                try {
                    $data = $this->mapRowToProduct($row, $headers, $mapping);

                    if ($this->validateRow($data) !== null) {
                        $skipped++;

                        continue;
                    }

                    $key = mb_strtolower(! empty($data['reference']) ? $data['reference'] : $data['designation']);
                    $match = $existing->get($key);

                    if ($match) {
                        if ($strategy === 'skip') {
                            $skipped++;

                            continue;
                        }

                        if ($strategy === 'update') {
                            $match->update($data);
                            $updated++;

                            continue;
                        }
                    }

                    // Le plafond ne concerne que les créations : mettre à jour
                    // un article existant n'agrandit pas le catalogue.
                    if ($remainingSlots !== null && $remainingSlots <= 0) {
                        $quotaBlocked++;
                        $skipped++;

                        continue;
                    }

                    Product::create(array_merge($data, ['user_id' => $session->user_id]));
                    $imported++;

                    if ($remainingSlots !== null) {
                        $remainingSlots--;
                    }
                } catch (\Exception $e) {
                    $errors[] = ['row' => $rowIndex + 2, 'message' => $e->getMessage()];
                    $skipped++;
                }
            }

            // Un import qui s'arrête au plafond sans le dire ressemble à un bug.
            if ($quotaBlocked > 0) {
                $errors[] = [
                    'row' => null,
                    'message' => __('app.import_products_quota_reached', ['count' => $quotaBlocked]),
                ];
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
            $session->update(['status' => 'failed', 'errors' => ['message' => $e->getMessage()]]);
            throw $e;
        }
    }

    /**
     * @return array{0: array<int, string>, 1: array<int, string>}
     */
    private function existingKeys(int $userId): array
    {
        $products = Product::withoutUserScope()->where('user_id', $userId)->get(['reference', 'designation']);

        return [
            $products->whereNotNull('reference')->pluck('reference')->map(fn ($r) => mb_strtolower($r))->all(),
            $products->pluck('designation')->map(fn ($d) => mb_strtolower($d))->all(),
        ];
    }

    /**
     * Renvoie le motif de rejet d'une ligne, ou null si elle est acceptable.
     */
    private function validateRow(array $data): ?string
    {
        if (empty($data['designation'])) {
            return __('app.import_products_error_designation');
        }

        if (! isset($data['unit_price_ht'])) {
            return __('app.import_products_error_price');
        }

        // Le régime de franchise interdit tout taux non nul : on rejette la
        // ligne plutôt que de la corriger en silence, sans quoi l'utilisateur
        // croirait avoir importé des taux qu'il n'a pas.
        $rejected = false;
        (new SalesVatRateAllowed)->validate('vat_rate', $data['vat_rate'] ?? 0, function () use (&$rejected) {
            $rejected = true;
        });

        return $rejected ? __('app.import_products_error_vat_franchise') : null;
    }

    /**
     * Mappe une ligne vers un tableau d'article, avec les normalisations.
     */
    protected function mapRowToProduct(array $row, array $headers, array $mapping): array
    {
        $data = $this->mapRow($row, $headers, $mapping);

        if (isset($data['unit_price_ht'])) {
            $price = $this->parseDecimal($data['unit_price_ht']);
            if ($price === null) {
                unset($data['unit_price_ht']);
            } else {
                $data['unit_price_ht'] = $price;
            }
        }

        if (isset($data['vat_rate'])) {
            $data['vat_rate'] = $this->parseDecimal(str_replace('%', '', $data['vat_rate'])) ?? $this->defaultVatRate();
        } else {
            $data['vat_rate'] = $this->defaultVatRate();
        }

        $data['unit'] = isset($data['unit'])
            ? (self::UNIT_ALIASES[mb_strtolower(trim($data['unit']))] ?? InvoiceItem::UNIT_PIECE)
            : InvoiceItem::UNIT_PIECE;

        $data['type'] = isset($data['type'])
            ? (self::TYPE_ALIASES[mb_strtolower(trim($data['type']))] ?? null)
            : null;

        return $data;
    }

    /**
     * Lit un nombre écrit à l'européenne comme à l'anglo-saxonne.
     *
     * « 1 234,56 », « 1.234,56 », « 1,234.56 » et « 1234.56 » désignent tous le
     * même montant. Le séparateur décimal est celui qui apparaît en dernier ;
     * l'autre n'est qu'un séparateur de milliers.
     */
    private function parseDecimal(string $value): ?float
    {
        $value = preg_replace('/[^\d,.\-]/u', '', $value);

        if ($value === '' || $value === null) {
            return null;
        }

        $lastComma = strrpos($value, ',');
        $lastDot = strrpos($value, '.');

        if ($lastComma !== false && $lastDot !== false) {
            $decimal = $lastComma > $lastDot ? ',' : '.';
            $thousands = $decimal === ',' ? '.' : ',';
            $value = str_replace($thousands, '', $value);
            $value = str_replace($decimal, '.', $value);
        } elseif ($lastComma !== false) {
            $value = str_replace(',', '.', $value);
        }

        return is_numeric($value) ? (float) $value : null;
    }

    private function defaultVatRate(): float
    {
        $settings = BusinessSettings::getInstance();

        if ($settings?->isVatExempt() ?? false) {
            return 0.0;
        }

        return (float) (config('countries.LU.default_vat_rate') ?? 17);
    }
}
