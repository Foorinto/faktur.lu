<?php

namespace App\Http\Controllers\Import;

use App\Http\Controllers\Controller;
use App\Models\Import\ImportSession;
use App\Services\Import\ClientImportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class ClientImportController extends Controller
{
    public function __construct(protected ClientImportService $service)
    {
    }

    /**
     * Affiche le wizard d'import.
     */
    public function index(Request $request)
    {
        $session = null;
        if ($request->has('session')) {
            $session = ImportSession::where('user_id', $request->user()->id)
                ->find($request->input('session'));
        }

        return Inertia::render('Clients/Import/Index', [
            'availableFields' => ClientImportService::AVAILABLE_FIELDS,
            'session' => $session,
        ]);
    }

    /**
     * Étape 1 : upload du fichier.
     */
    public function upload(Request $request)
    {
        $request->validate([
            'file' => [
                'required',
                'file',
                'max:10240',
                function ($attribute, $value, $fail) {
                    $extension = strtolower($value->getClientOriginalExtension());
                    if (!in_array($extension, ['xlsx', 'xls', 'csv', 'ods', 'txt'])) {
                        $fail('Le fichier doit être au format Excel, CSV ou ODS.');
                    }
                },
            ],
        ]);

        $file = $request->file('file');
        $extension = strtolower($file->getClientOriginalExtension());
        $filename = uniqid('import_') . '.' . $extension;
        $path = $file->storeAs('imports/' . $request->user()->id, $filename);

        $parsed = $this->service->parseFile(Storage::path($path));
        $autoMapping = $this->service->autoDetectMapping($parsed['headers']);

        $session = ImportSession::create([
            'user_id' => $request->user()->id,
            'type' => 'clients',
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

    /**
     * Étape 2 : sauvegarde du mapping.
     */
    public function saveMapping(Request $request, ImportSession $importSession)
    {
        $this->authorize('update', $importSession);

        $request->validate([
            'mapping' => 'required|array',
        ]);

        $importSession->update([
            'mapping' => $request->input('mapping'),
            'status' => 'preview',
        ]);

        // Validation et détection des doublons
        $result = $this->service->validateAndPreview($importSession);

        return response()->json([
            'session' => $importSession->fresh(),
            'preview' => $result,
        ]);
    }

    /**
     * Étape 3 : lancement de l'import.
     */
    public function process(Request $request, ImportSession $importSession)
    {
        $this->authorize('update', $importSession);

        $request->validate([
            'duplicate_strategy' => 'required|in:skip,update,create',
            'default_status' => 'nullable|in:prospect,contacted,discussing,active,inactive,lost',
        ]);

        $importSession->update([
            'duplicate_strategy' => $request->input('duplicate_strategy'),
        ]);

        $this->service->import($importSession, $request->input('default_status'));

        return response()->json([
            'session' => $importSession->fresh(),
        ]);
    }

    /**
     * Statut d'une session d'import.
     */
    public function status(ImportSession $importSession)
    {
        $this->authorize('view', $importSession);

        return response()->json(['session' => $importSession]);
    }

    /**
     * Suppression d'une session.
     */
    public function destroy(ImportSession $importSession)
    {
        $this->authorize('delete', $importSession);

        if ($importSession->storage_path) {
            Storage::delete($importSession->storage_path);
        }

        $importSession->delete();

        return response()->json(['success' => true]);
    }
}
