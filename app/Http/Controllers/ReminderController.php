<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Reminder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ReminderController extends Controller
{
    public function index(): Response
    {
        $overdue = Reminder::query()
            ->overdue()
            ->with('client:id,name')
            ->get();

        $upcoming = Reminder::query()
            ->upcoming()
            ->with('client:id,name')
            ->limit(20)
            ->get();

        $completed = Reminder::query()
            ->completed()
            ->with('client:id,name')
            ->limit(10)
            ->get();

        return Inertia::render('Reminders/Index', [
            'overdue' => $overdue,
            'upcoming' => $upcoming,
            'completed' => $completed,
        ]);
    }

    public function store(Request $request, Client $client): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'remind_at' => ['required', 'date', 'after:now'],
        ]);

        $client->reminders()->create($validated);

        return back()->with('success', 'Rappel créé.');
    }

    public function update(Request $request, Reminder $reminder): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'remind_at' => ['required', 'date'],
        ]);

        $reminder->update($validated);

        return back()->with('success', 'Rappel mis à jour.');
    }

    public function complete(Reminder $reminder): RedirectResponse
    {
        $reminder->update(['is_completed' => true]);

        return back()->with('success', 'Rappel terminé.');
    }

    public function destroy(Reminder $reminder): RedirectResponse
    {
        $reminder->delete();

        return back()->with('success', 'Rappel supprimé.');
    }
}
