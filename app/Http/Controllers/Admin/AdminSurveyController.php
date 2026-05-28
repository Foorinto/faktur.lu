<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SatisfactionSurvey;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminSurveyController extends Controller
{
    public function index(Request $request)
    {
        $responses = SatisfactionSurvey::completed()
            ->with('user:id,name,email')
            ->orderByDesc('completed_at')
            ->paginate(50)
            ->withQueryString();

        return Inertia::render('Admin/Surveys/Index', [
            'responses' => $responses,
            'stats' => $this->stats(),
        ]);
    }

    /**
     * Aggregate stats: NPS score, response rate, distribution.
     */
    protected function stats(): array
    {
        $completed = SatisfactionSurvey::completed()->count();
        $sent = SatisfactionSurvey::whereNotNull('sent_at')->count();

        $promoters = SatisfactionSurvey::completed()
            ->where('nps_score', '>=', SatisfactionSurvey::PROMOTER_MIN)->count();
        $detractors = SatisfactionSurvey::completed()
            ->where('nps_score', '<=', SatisfactionSurvey::DETRACTOR_MAX)->count();

        $nps = $completed > 0
            ? round((($promoters - $detractors) / $completed) * 100)
            : null;

        return [
            'sent' => $sent,
            'completed' => $completed,
            'response_rate' => $sent > 0 ? round(($completed / $sent) * 100, 1) : 0,
            'nps' => $nps, // -100..+100
            'promoters' => $promoters,
            'passives' => max(0, $completed - $promoters - $detractors),
            'detractors' => $detractors,
        ];
    }

    public function export(): StreamedResponse
    {
        $filename = 'satisfaction-surveys-' . now()->format('Y-m-d-His') . '.csv';

        return response()->streamDownload(function () {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF"); // BOM UTF-8 (Excel)
            fputcsv($handle, ['user_name', 'user_email', 'nps_score', 'comment', 'completed_at']);

            SatisfactionSurvey::completed()
                ->with('user:id,name,email')
                ->orderByDesc('completed_at')
                ->chunk(500, function ($surveys) use ($handle) {
                    foreach ($surveys as $s) {
                        fputcsv($handle, [
                            $s->user?->name,
                            $s->user?->email,
                            $s->nps_score,
                            $s->comment,
                            $s->completed_at?->toIso8601String(),
                        ]);
                    }
                });

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
