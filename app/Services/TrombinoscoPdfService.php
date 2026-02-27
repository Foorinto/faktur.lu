<?php

namespace App\Services;

use App\Models\BusinessSettings;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\Response;

class TrombinoscoPdfService
{
    public function download(Collection $employees): Response
    {
        $pdf = $this->createPdf($employees);

        return $pdf->download('trombinoscope.pdf');
    }

    public function stream(Collection $employees): Response
    {
        $pdf = $this->createPdf($employees);

        return $pdf->stream('trombinoscope.pdf');
    }

    protected function createPdf(Collection $employees): \Barryvdh\DomPDF\PDF
    {
        $data = $this->prepareData($employees);

        return Pdf::loadView('pdf.trombinoscope', $data)
            ->setPaper('a4', 'portrait')
            ->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
                'defaultFont' => 'sans-serif',
            ]);
    }

    protected function prepareData(Collection $employees): array
    {
        $grouped = $employees->groupBy(fn ($e) => $e->department?->name ?? 'Sans département');

        $employees->each(function ($employee) {
            $employee->photo_data_uri = $this->getPhotoDataUri($employee->photo_path);
        });

        $settings = BusinessSettings::getInstance();

        return [
            'grouped' => $grouped,
            'generatedAt' => now()->format('d/m/Y'),
            'companyName' => $settings?->company_name ?? '',
        ];
    }

    protected function getPhotoDataUri(?string $photoPath): ?string
    {
        if (!$photoPath) {
            return null;
        }

        $fullPath = storage_path('app/public/' . $photoPath);

        if (!file_exists($fullPath)) {
            return null;
        }

        $content = file_get_contents($fullPath);
        if ($content === false) {
            return null;
        }

        $mimeType = mime_content_type($fullPath) ?: 'image/png';

        return 'data:' . $mimeType . ';base64,' . base64_encode($content);
    }
}
