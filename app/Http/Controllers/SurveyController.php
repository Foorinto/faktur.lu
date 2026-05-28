<?php

namespace App\Http\Controllers;

use App\Mail\SatisfactionSurveyAdminNotification;
use App\Models\SatisfactionSurvey;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;
use Inertia\Response;

class SurveyController extends Controller
{
    /**
     * Display the public satisfaction survey form (tokenized, no auth).
     */
    public function show(string $locale, string $token): Response
    {
        $survey = SatisfactionSurvey::where('token', $token)->first();

        $state = match (true) {
            !$survey => 'invalid',
            $survey->isCompleted() => 'completed',
            $survey->isExpired() => 'expired',
            $survey->isValid() => 'form',
            default => 'invalid',
        };

        return Inertia::render('Survey/Show', [
            'state' => $state,
            'submitUrl' => $state === 'form'
                ? route('survey.submit', ['locale' => $locale, 'token' => $token])
                : null,
        ]);
    }

    /**
     * Handle the survey submission (NPS score + comment).
     */
    public function submit(Request $request, string $locale, string $token)
    {
        $validated = $request->validate([
            'nps_score' => 'required|integer|min:0|max:10',
            'comment' => 'nullable|string|max:2000',
        ]);

        $survey = SatisfactionSurvey::where('token', $token)->first();

        if (!$survey || !$survey->isValid()) {
            // Token invalid/expired/already used → show the appropriate state.
            return redirect()->route('survey.show', ['locale' => $locale, 'token' => $token]);
        }

        $survey->markCompleted($validated['nps_score'], $validated['comment'] ?? null);

        Mail::to(config('mail.from.address'))
            ->send(new SatisfactionSurveyAdminNotification($survey->fresh('user')));

        return back()->with('success', true);
    }
}
