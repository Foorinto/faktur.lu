<?php

namespace App\Http\Controllers\Import;

use App\Http\Controllers\Controller;
use App\Models\Import\ImportSession;
use App\Services\Import\ProductImportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Assistant d'import du catalogue d'articles.
 *
 * Décalque de l'import de clients : dépôt du fichier, correspondance des
 * colonnes, aperçu, import. Le modèle téléchargeable n'est qu'un raccourci
 * pour ceux qui partent de zéro — la détection automatique accepte n'importe
 * quel tableur.
 */
class ProductImportController extends Controller
{
    public function __construct(protected ProductImportService $service) {}

    public function index(Request $request)
    {
        $session = null;
        if ($request->has('session')) {
            $session = ImportSession::where('user_id', $request->user()->id)
                ->find($request->input('session'));
        }

        return Inertia::render('Products/Import/Index', [
            'availableFields' => ProductImportService::AVAILABLE_FIELDS,
            'session' => $session,
        ]);
    }

    /**
     * Modèle CSV.
     *
     * ⚠️ BOM UTF-8 en tête : sans lui, Excel ouvre le fichier en ANSI et
     * massacre les accents. L'utilisateur croirait le modèle fautif.
     */
    public function template(): StreamedResponse
    {
        $rows = [
            ['designation', 'reference', 'type', 'description', 'unit_price_ht', 'vat_rate', 'unit'],
            ['Prestation de conseil', 'SRV-001', 'prestation', 'Accompagnement à la journée', '750,00', '17', 'jour'],
            ['Microphone USB', 'PRD-014', 'produit', 'Micro cardioïde', '129,90', '17', 'pièce'],
        ];

        return response()->streamDownload(function () use ($rows) {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF");

            foreach ($rows as $row) {
                fputcsv($handle, $row, ';');
            }

            fclose($handle);
        }, 'modele-catalogue-faktur.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function upload(Request $request)
    {
        $request->validate([
            'file' => [
                'required',
                'file',
                'max:10240',
                function ($attribute, $value, $fail) {
                    $extension = strtolower($value->getClientOriginalExtension());
                    if (! in_array($extension, ['xlsx', 'xls', 'csv', 'ods', 'txt'])) {
                        $fail(__('app.import_file_type_invalid'));
                    }
                },
            ],
        ]);

        $file = $request->file('file');
        $extension = strtolower($file->getClientOriginalExtension());
        $filename = uniqid('import_').'.'.$extension;
        $path = $file->storeAs('imports/'.$request->user()->id, $filename);

        $parsed = $this->service->parseFile(Storage::path($path));
        $autoMapping = $this->service->autoDetectMapping($parsed['headers']);

        $session = ImportSession::create([
            'user_id' => $request->user()->id,
            'type' => 'products',
            'filename' => $file->getClientOriginalName(),
            'storage_path' => $path,
            'headers' => $parsed['headers'],
            'preview_data' => $parsed['preview_data'],
            'mapping' => $autoMapping,
            'total_rows' => $parsed['total_rows'],
            'status' => 'mapping',
        ]);

        return response()->json(['session' => $session]);
    }

    public function saveMapping(Request $request, ImportSession $importSession)
    {
        $this->authorizeSession($request, $importSession);

        $request->validate(['mapping' => 'required|array']);

        $importSession->update([
            'mapping' => $request->input('mapping'),
            'status' => 'preview',
        ]);

        return response()->json([
            'session' => $importSession->fresh(),
            'preview' => $this->service->validateAndPreview($importSession),
        ]);
    }

    public function process(Request $request, ImportSession $importSession)
    {
        $this->authorizeSession($request, $importSession);

        $request->validate(['duplicate_strategy' => 'required|in:skip,update,create']);

        $importSession->update(['duplicate_strategy' => $request->input('duplicate_strategy')]);

        $this->service->import($importSession);

        return response()->json(['session' => $importSession->fresh()]);
    }

    public function status(Request $request, ImportSession $importSession)
    {
        $this->authorizeSession($request, $importSession);

        return response()->json(['session' => $importSession]);
    }

    public function destroy(Request $request, ImportSession $importSession)
    {
        $this->authorizeSession($request, $importSession);

        if ($importSession->storage_path) {
            Storage::delete($importSession->storage_path);
        }

        $importSession->delete();

        return response()->json(['success' => true]);
    }

    /**
     * Une session appartient à son créateur, et à lui seul : les identifiants
     * sont séquentiels, la vérification n'est pas facultative.
     */
    private function authorizeSession(Request $request, ImportSession $session): void
    {
        abort_unless(
            (int) $session->user_id === (int) $request->user()->id && $session->type === 'products',
            403
        );
    }
}
