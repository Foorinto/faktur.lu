<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Models\HR\Employee;
use App\Models\HR\OnboardingTask;
use App\Models\HR\OnboardingTemplate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class OnboardingTaskController extends Controller
{
    public function store(Request $request, Employee $employee): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
        ]);

        $maxPosition = $employee->onboardingTasks()->max('position') ?? 0;

        $employee->onboardingTasks()->create([
            ...$validated,
            'position' => $maxPosition + 1,
        ]);

        return back()->with('success', __('app.hr.task_added'));
    }

    public function toggle(Request $request, Employee $employee, OnboardingTask $onboardingTask): RedirectResponse
    {
        $onboardingTask->update([
            'completed_at' => $onboardingTask->completed_at ? null : now(),
        ]);

        return back();
    }

    public function destroy(Employee $employee, OnboardingTask $onboardingTask): RedirectResponse
    {
        $onboardingTask->delete();

        return back()->with('success', __('app.hr.task_deleted'));
    }

    public function applyTemplate(Request $request, Employee $employee): RedirectResponse
    {
        $validated = $request->validate([
            'template_id' => ['required', 'exists:onboarding_templates,id'],
        ]);

        $template = OnboardingTemplate::with(['items' => fn ($q) => $q->orderBy('position')])->findOrFail($validated['template_id']);

        $maxPosition = $employee->onboardingTasks()->max('position') ?? 0;

        foreach ($template->items as $item) {
            $maxPosition++;
            $employee->onboardingTasks()->create([
                'title' => $item->title,
                'position' => $maxPosition,
            ]);
        }

        return back()->with('success', __('app.hr.template_applied'));
    }
}
