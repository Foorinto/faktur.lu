<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Models\HR\OnboardingTemplate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OnboardingTemplateController extends Controller
{
    public function index(): Response
    {
        $templates = OnboardingTemplate::query()
            ->withCount('items')
            ->with(['items' => fn ($q) => $q->orderBy('position')])
            ->orderBy('name')
            ->get();

        return Inertia::render('HR/OnboardingTemplates/Index', [
            'templates' => $templates,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.title' => ['required', 'string', 'max:255'],
        ]);

        $template = OnboardingTemplate::create(['name' => $validated['name']]);

        foreach ($validated['items'] as $index => $item) {
            $template->items()->create([
                'title' => $item['title'],
                'position' => $index,
            ]);
        }

        return back()->with('success', __('app.hr.template_created'));
    }

    public function update(Request $request, OnboardingTemplate $onboardingTemplate): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.title' => ['required', 'string', 'max:255'],
        ]);

        $onboardingTemplate->update(['name' => $validated['name']]);

        $onboardingTemplate->items()->delete();

        foreach ($validated['items'] as $index => $item) {
            $onboardingTemplate->items()->create([
                'title' => $item['title'],
                'position' => $index,
            ]);
        }

        return back()->with('success', __('app.hr.template_updated'));
    }

    public function destroy(OnboardingTemplate $onboardingTemplate): RedirectResponse
    {
        $onboardingTemplate->delete();

        return back()->with('success', __('app.hr.template_deleted'));
    }
}
