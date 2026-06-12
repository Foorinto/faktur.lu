<?php

namespace App\Services;

use App\Models\BusinessSettings;
use App\Models\HR\Employee;
use App\Models\HR\Evaluation;
use Barryvdh\DomPDF\Facade\Pdf;
use Symfony\Component\HttpFoundation\Response;

class EvaluationPdfService
{
    public function download(Evaluation $evaluation, Employee $employee): Response
    {
        $pdf = $this->createPdf($evaluation, $employee);
        $filename = 'evaluation-' . str($evaluation->title)->slug() . '-' . $evaluation->date->format('Y-m-d') . '.pdf';

        return $pdf->download($filename);
    }

    protected function createPdf(Evaluation $evaluation, Employee $employee): \Barryvdh\DomPDF\PDF
    {
        $settings = BusinessSettings::getInstance();

        return Pdf::loadView('pdf.evaluation', [
            'evaluation' => $evaluation,
            'employee' => $employee,
            'companyName' => $settings?->company_name ?? '',
            'generatedAt' => now()->format('d/m/Y'),
        ])
            ->setPaper('a4', 'portrait')
            ->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => false,
                'defaultFont' => 'sans-serif',
            ]);
    }
}
