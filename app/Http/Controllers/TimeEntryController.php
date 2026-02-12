<?php

namespace App\Http\Controllers;

use App\Actions\ConvertTimeToInvoiceAction;
use App\Http\Requests\Api\V1\StoreTimeEntryRequest;
use App\Http\Requests\Api\V1\UpdateTimeEntryRequest;
use App\Models\Client;
use App\Models\Project;
use App\Models\TimeEntry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TimeEntryController extends Controller
{
    /**
     * Display a listing of time entries.
     */
    public function index(Request $request): Response
    {
        $query = TimeEntry::query()
            ->with(['client', 'project:id,title,color', 'task:id,title'])
            ->stopped();

        // Filter by client
        if ($request->filled('client_id')) {
            $query->forClient($request->input('client_id'));
        }

        // Filter by billed status
        if ($request->filled('billed')) {
            $request->boolean('billed')
                ? $query->billed()
                : $query->unbilled();
        }

        // Filter by period
        if ($request->filled('period')) {
            match ($request->input('period')) {
                'today' => $query->whereDate('started_at', today()),
                'week' => $query->thisWeek(),
                'month' => $query->thisMonth(),
                default => null,
            };
        }

        // Filter by date range
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->between($request->input('start_date'), $request->input('end_date'));
        }

        $entries = $query
            ->orderByDesc('started_at')
            ->paginate(20)
            ->withQueryString();

        // Transform entries to include computed attributes
        $entries->getCollection()->transform(function ($entry) {
            return array_merge($entry->toArray(), [
                'duration_formatted' => $entry->duration_formatted,
                'duration_hours' => $entry->duration_hours,
                'amount' => $entry->amount,
            ]);
        });

        // Get running timer
        $runningTimer = TimeEntry::running()->with(['client', 'project:id,title,color', 'task:id,title'])->first();
        if ($runningTimer) {
            $runningTimer = array_merge($runningTimer->toArray(), [
                'duration_formatted' => $runningTimer->duration_formatted,
                'current_duration_seconds' => $runningTimer->getCurrentDurationSeconds(),
            ]);
        }

        // Get summary
        $summaryQuery = TimeEntry::query()->stopped();
        if ($request->filled('client_id')) {
            $summaryQuery->forClient($request->input('client_id'));
        }
        if ($request->filled('period')) {
            match ($request->input('period')) {
                'today' => $summaryQuery->whereDate('started_at', today()),
                'week' => $summaryQuery->thisWeek(),
                'month' => $summaryQuery->thisMonth(),
                default => null,
            };
        }

        $totalSeconds = $summaryQuery->sum('duration_seconds');
        $unbilledSeconds = (clone $summaryQuery)->unbilled()->sum('duration_seconds');

        // Get draft invoices grouped by client for the add-to-invoice feature
        $draftInvoices = \App\Models\Invoice::query()
            ->draft()
            ->with('client:id,name')
            ->orderBy('created_at', 'desc')
            ->get(['id', 'client_id', 'title', 'number', 'total_ht', 'created_at'])
            ->map(fn ($invoice) => [
                'id' => $invoice->id,
                'client_id' => $invoice->client_id,
                'client_name' => $invoice->client->name ?? 'Client inconnu',
                'title' => $invoice->title,
                'display_number' => $invoice->display_number,
                'total_ht' => $invoice->total_ht,
                'created_at' => $invoice->created_at->format('d/m/Y'),
            ]);

        // Get active projects grouped by client
        $projects = Project::query()
            ->active()
            ->with(['tasks' => function ($query) {
                $query->where('is_completed', false)->orderBy('sort_order');
            }])
            ->orderBy('title')
            ->get(['id', 'client_id', 'title', 'color']);

        return Inertia::render('TimeTracking/Index', [
            'entries' => $entries,
            'runningTimer' => $runningTimer,
            'summary' => [
                'total_seconds' => $totalSeconds,
                'total_formatted' => TimeEntry::formatSeconds($totalSeconds),
                'unbilled_seconds' => $unbilledSeconds,
                'unbilled_formatted' => TimeEntry::formatSeconds($unbilledSeconds),
            ],
            'filters' => [
                'client_id' => $request->input('client_id'),
                'billed' => $request->input('billed'),
                'period' => $request->input('period'),
            ],
            'clients' => Client::orderBy('name')->get(['id', 'name', 'default_hourly_rate']),
            'projects' => $projects,
            'periods' => [
                ['value' => 'today', 'label' => 'Aujourd\'hui'],
                ['value' => 'week', 'label' => 'Cette semaine'],
                ['value' => 'month', 'label' => 'Ce mois'],
            ],
            'draftInvoices' => $draftInvoices,
            'defaultHourlyRate' => \App\Models\BusinessSettings::getInstance()?->default_hourly_rate,
            'isVatExempt' => \App\Models\BusinessSettings::getInstance()?->isVatExempt() ?? true,
        ]);
    }

    /**
     * Store a newly created time entry.
     */
    public function store(StoreTimeEntryRequest $request): RedirectResponse
    {
        $data = $request->validated();

        // If duration is provided as string (HH:MM), convert to seconds
        if (isset($data['duration']) && is_string($data['duration'])) {
            $data['duration_seconds'] = TimeEntry::parseToSeconds($data['duration']);
            unset($data['duration']);
        }

        // Set started_at and stopped_at for manual entries
        if (isset($data['duration_seconds']) && !isset($data['started_at'])) {
            $data['started_at'] = isset($data['date'])
                ? now()->parse($data['date'])->setTime(9, 0)
                : now()->subSeconds($data['duration_seconds']);
            $data['stopped_at'] = $data['started_at']->copy()->addSeconds($data['duration_seconds']);
        }

        TimeEntry::create($data);

        return back()->with('success', 'Entrée de temps enregistrée.');
    }

    /**
     * Update the specified time entry.
     */
    public function update(UpdateTimeEntryRequest $request, TimeEntry $timeEntry): RedirectResponse
    {
        if ($timeEntry->is_billed) {
            return back()->with('error', 'Impossible de modifier une entrée facturée.');
        }

        $data = $request->validated();

        // If duration is provided as string (HH:MM), convert to seconds
        if (isset($data['duration']) && is_string($data['duration'])) {
            $data['duration_seconds'] = TimeEntry::parseToSeconds($data['duration']);
            unset($data['duration']);

            // Recalculate stopped_at
            if ($timeEntry->started_at) {
                $data['stopped_at'] = $timeEntry->started_at->copy()->addSeconds($data['duration_seconds']);
            }
        }

        $timeEntry->update($data);

        return back()->with('success', 'Entrée de temps mise à jour.');
    }

    /**
     * Remove the specified time entry.
     */
    public function destroy(TimeEntry $timeEntry): RedirectResponse
    {
        if ($timeEntry->is_billed) {
            return back()->with('error', 'Impossible de supprimer une entrée facturée.');
        }

        $timeEntry->delete();

        return back()->with('success', 'Entrée de temps supprimée.');
    }

    /**
     * Start a new timer.
     */
    public function start(Request $request): RedirectResponse
    {
        // Stop any running timer first
        $runningTimer = TimeEntry::running()->first();
        if ($runningTimer) {
            $runningTimer->stop();
        }

        $request->validate([
            'client_id' => 'required|exists:clients,id',
            'project_id' => 'nullable|exists:projects,id',
            'task_id' => 'nullable|exists:tasks,id',
            'project_name' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:1000',
        ]);

        $client = Client::findOrFail($request->input('client_id'));

        // If a project is selected, use its title as project_name for backward compatibility
        $projectName = $request->input('project_name');
        if ($request->filled('project_id')) {
            $project = Project::find($request->input('project_id'));
            if ($project && !$projectName) {
                $projectName = $project->title;
            }
        }

        TimeEntry::create([
            'client_id' => $client->id,
            'project_id' => $request->input('project_id'),
            'task_id' => $request->input('task_id'),
            'project_name' => $projectName,
            'description' => $request->input('description'),
            'hourly_rate' => $client->default_hourly_rate,
            'started_at' => now(),
        ]);

        return back()->with('success', 'Timer démarré.');
    }

    /**
     * Stop a running timer.
     */
    public function stop(TimeEntry $timeEntry): RedirectResponse
    {
        if (!$timeEntry->isRunning()) {
            return back()->with('error', 'Ce timer n\'est pas en cours.');
        }

        $timeEntry->stop();

        return back()->with('success', 'Timer arrêté. Durée: ' . $timeEntry->duration_formatted);
    }

    /**
     * Get the currently running timer.
     */
    public function running(): Response
    {
        $runningTimer = TimeEntry::running()->with('client')->first();

        if ($runningTimer) {
            $runningTimer = array_merge($runningTimer->toArray(), [
                'duration_formatted' => $runningTimer->duration_formatted,
                'current_duration_seconds' => $runningTimer->getCurrentDurationSeconds(),
            ]);
        }

        return Inertia::render('TimeTracking/Running', [
            'timer' => $runningTimer,
        ]);
    }

    /**
     * Convert time entries to invoice.
     */
    public function toInvoice(Request $request, ConvertTimeToInvoiceAction $action): RedirectResponse
    {
        $request->validate([
            'time_entry_ids' => 'required|array|min:1',
            'time_entry_ids.*' => 'exists:time_entries,id',
            'hourly_rate' => 'required|numeric|min:0',
            'vat_rate' => 'required|numeric|in:0,3,8,14,17',
            'group_by_project' => 'boolean',
        ]);

        try {
            $invoice = $action->execute(
                $request->input('time_entry_ids'),
                (float) $request->input('hourly_rate'),
                (int) $request->input('vat_rate'),
                $request->boolean('group_by_project', true)
            );

            return redirect()
                ->route('invoices.edit', $invoice)
                ->with('success', 'Facture brouillon créée à partir du temps.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Add a time entry to an existing invoice.
     */
    public function addToInvoice(Request $request, TimeEntry $timeEntry): RedirectResponse
    {
        if ($timeEntry->is_billed) {
            return back()->with('error', 'Cette entrée est déjà facturée.');
        }

        $request->validate([
            'invoice_id' => 'required|exists:invoices,id',
            'hourly_rate' => 'required|numeric|min:0',
            'vat_rate' => 'required|numeric|in:0,3,8,14,17',
        ]);

        $invoice = \App\Models\Invoice::findOrFail($request->input('invoice_id'));

        // Verify invoice is draft
        if (!$invoice->isDraft()) {
            return back()->with('error', 'Impossible d\'ajouter à une facture finalisée.');
        }

        // Get the hourly rate from request
        $hourlyRate = (float) $request->input('hourly_rate');

        // Calculate hours
        $hours = round($timeEntry->duration_seconds / 3600, 2);

        // Build title and description
        $title = $timeEntry->project_name ?: "Prestation du " . $timeEntry->started_at->format('d/m/Y');
        $description = $timeEntry->description;

        // If we have a project name, add the date to description
        if ($timeEntry->project_name && $timeEntry->started_at) {
            $dateInfo = "(" . $timeEntry->started_at->format('d/m/Y') . ")";
            $description = $description ? "{$dateInfo}\n{$description}" : $dateInfo;
        }

        // Get the next sort order
        $maxSortOrder = $invoice->items()->max('sort_order') ?? 0;

        \App\Models\InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'title' => $title,
            'description' => $description ?: null,
            'quantity' => $hours,
            'unit' => 'hour',
            'unit_price' => $hourlyRate,
            'vat_rate' => $request->input('vat_rate'),
            'sort_order' => $maxSortOrder + 1,
        ]);

        // Mark time entry as billed
        $timeEntry->markAsBilled($invoice);

        return back()->with('success', 'Entrée ajoutée à la facture.');
    }

    /**
     * Get summary for time entries.
     */
    public function summary(Request $request): Response
    {
        $clientId = $request->input('client_id');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        $summary = TimeEntry::getSummary($clientId, $startDate, $endDate);

        // Get breakdown by client
        $byClient = TimeEntry::query()
            ->stopped()
            ->when($startDate && $endDate, fn ($q) => $q->between($startDate, $endDate))
            ->selectRaw('client_id, SUM(duration_seconds) as total_seconds, SUM(CASE WHEN is_billed = 0 THEN duration_seconds ELSE 0 END) as unbilled_seconds')
            ->groupBy('client_id')
            ->with('client:id,name')
            ->get()
            ->map(fn ($item) => [
                'client_id' => $item->client_id,
                'client_name' => $item->client->name ?? 'Unknown',
                'total_seconds' => $item->total_seconds,
                'total_formatted' => TimeEntry::formatSeconds($item->total_seconds),
                'unbilled_seconds' => $item->unbilled_seconds,
                'unbilled_formatted' => TimeEntry::formatSeconds($item->unbilled_seconds),
            ]);

        return Inertia::render('TimeTracking/Summary', [
            'summary' => $summary,
            'byClient' => $byClient,
            'filters' => [
                'client_id' => $clientId,
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
            'clients' => Client::orderBy('name')->get(['id', 'name']),
        ]);
    }
}
