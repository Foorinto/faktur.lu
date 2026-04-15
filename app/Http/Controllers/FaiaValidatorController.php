<?php

namespace App\Http\Controllers;

use App\Services\FaiaValidatorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;

class FaiaValidatorController extends Controller
{
    public function __construct(
        protected FaiaValidatorService $validator
    ) {}

    /**
     * Display the FAIA validator page.
     */
    public function index()
    {
        return Inertia::render('FaiaValidator', [
            'canLogin' => true,
            'canRegister' => true,
            'validationsCount' => Cache::get('faia_validations_count', 0),
        ]);
    }

    /**
     * Validate an uploaded FAIA file.
     */
    public function validate(Request $request)
    {
        $request->validate([
            'file' => [
                'required',
                'file',
                'max:51200', // 50 MB
                'mimetypes:application/xml,text/xml,text/plain',
            ],
        ], [
            'file.required' => 'Veuillez sélectionner un fichier XML.',
            'file.max' => 'Le fichier ne doit pas dépasser 50 Mo.',
            'file.mimetypes' => 'Le fichier doit être au format XML.',
        ]);

        $file = $request->file('file');

        // Additional security: check file extension
        $extension = strtolower($file->getClientOriginalExtension());
        if (!in_array($extension, ['xml'])) {
            return response()->json([
                'status' => 'error',
                'message' => 'Seuls les fichiers .xml sont acceptés.',
            ], 422);
        }

        // Read file content
        $content = file_get_contents($file->getRealPath());

        // Security: Check for XXE patterns
        if ($this->containsXxePatterns($content)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Le fichier contient des patterns non autorisés.',
            ], 422);
        }

        // Increment validations counter
        Cache::increment('faia_validations_count');

        // Validate the FAIA file
        $result = $this->validator->validate($content);

        // Add file info
        $result['fileInfo'] = [
            'name' => $file->getClientOriginalName(),
            'size' => $file->getSize(),
            'sizeFormatted' => $this->formatFileSize($file->getSize()),
        ];

        return response()->json($result);
    }

    /**
     * Check for XXE (XML External Entity) attack patterns.
     */
    protected function containsXxePatterns(string $content): bool
    {
        $patterns = [
            '/<!ENTITY/i',
            '/<!DOCTYPE[^>]*\[/i',
            '/SYSTEM\s+["\'][^"\']*["\']/i',
            '/PUBLIC\s+["\'][^"\']*["\']/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $content)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Format file size in human readable format.
     */
    protected function formatFileSize(int $bytes): string
    {
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' Mo';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' Ko';
        }
        return $bytes . ' octets';
    }
}
