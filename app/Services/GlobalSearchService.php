<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Expense;
use App\Models\HR\Employee;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\Quote;
use App\Models\Task;
use App\Models\TimeEntry;
use Illuminate\Support\Collection;

class GlobalSearchService
{
    /**
     * Preview search for the command palette modal.
     * Returns max $limit results per category + total counts.
     */
    public function preview(string $query, int $limit = 5): array
    {
        $userId = auth()->id();
        $categories = [];

        foreach ($this->getSearchableEntities() as $key => $config) {
            $baseQuery = $config['query']($query, $userId);
            $count = $baseQuery->count();

            if ($count === 0) {
                continue;
            }

            $items = $config['query']($query, $userId)
                ->limit($limit)
                ->get()
                ->map(fn ($item) => $config['format']($item))
                ->toArray();

            $categories[] = [
                'key' => $key,
                'items' => $items,
                'count' => $count,
            ];
        }

        return [
            'categories' => $categories,
            'query' => $query,
        ];
    }

    /**
     * Full paginated search for the results page.
     */
    public function paginated(string $query, ?string $category, int $perPage = 15): array
    {
        $userId = auth()->id();
        $entities = $this->getSearchableEntities();

        if ($category && isset($entities[$category])) {
            $paginated = $entities[$category]['query']($query, $userId)->paginate($perPage);

            return [
                'category' => $category,
                'items' => $paginated->through(fn ($item) => $entities[$category]['format']($item)),
                'total' => $paginated->total(),
            ];
        }

        // All categories — collect all results merged, paginated manually
        $allResults = collect();

        foreach ($entities as $key => $config) {
            $config['query']($query, $userId)
                ->limit(100)
                ->get()
                ->each(function ($item) use ($config, $key, &$allResults) {
                    $formatted = $config['format']($item);
                    $formatted['category'] = $key;
                    $allResults->push($formatted);
                });
        }

        $page = request()->input('page', 1);
        $sliced = $allResults->slice(($page - 1) * $perPage, $perPage)->values();

        return [
            'category' => null,
            'items' => new \Illuminate\Pagination\LengthAwarePaginator(
                $sliced,
                $allResults->count(),
                $perPage,
                $page,
                ['path' => request()->url(), 'query' => request()->query()]
            ),
            'total' => $allResults->count(),
        ];
    }

    /**
     * Get category counts for the results page tabs.
     */
    public function getCategoryCounts(string $query): array
    {
        $userId = auth()->id();
        $counts = [];

        foreach ($this->getSearchableEntities() as $key => $config) {
            $counts[$key] = $config['query']($query, $userId)->count();
        }

        return $counts;
    }

    /**
     * Registry of all searchable entities.
     */
    private function getSearchableEntities(): array
    {
        return [
            'clients' => [
                'query' => fn (string $q, int $userId) => Client::search($q),
                'format' => fn (Client $client) => [
                    'id' => $client->id,
                    'type' => 'clients',
                    'title' => $client->name,
                    'subtitle' => collect([$client->email, $client->type ? strtoupper($client->type) : null])->filter()->join(' · '),
                    'url' => route('clients.show', $client),
                    'status' => $client->status,
                    'status_color' => match ($client->status) {
                        'active' => 'green',
                        'prospect' => 'blue',
                        'inactive' => 'gray',
                        default => 'gray',
                    },
                ],
            ],

            'invoices' => [
                'query' => fn (string $q, int $userId) => Invoice::search($q)->with('client:id,name'),
                'format' => fn (Invoice $inv) => [
                    'id' => $inv->id,
                    'type' => 'invoices',
                    'title' => $inv->number . ($inv->title ? " — {$inv->title}" : ''),
                    'subtitle' => collect([
                        $inv->client?->name,
                        number_format($inv->total_ttc ?? 0, 2, ',', ' ') . ' €',
                    ])->filter()->join(' · '),
                    'url' => route('invoices.show', $inv),
                    'status' => $inv->status,
                    'status_color' => match ($inv->status) {
                        'paid' => 'green',
                        'sent', 'finalized' => 'blue',
                        'draft' => 'gray',
                        'cancelled' => 'red',
                        default => 'gray',
                    },
                ],
            ],

            'quotes' => [
                'query' => fn (string $q, int $userId) => Quote::search($q)->with('client:id,name'),
                'format' => fn (Quote $quote) => [
                    'id' => $quote->id,
                    'type' => 'quotes',
                    'title' => $quote->reference,
                    'subtitle' => collect([
                        $quote->client?->name,
                        number_format($quote->total_ttc ?? 0, 2, ',', ' ') . ' €',
                    ])->filter()->join(' · '),
                    'url' => route('quotes.show', $quote),
                    'status' => $quote->status,
                    'status_color' => match ($quote->status) {
                        'accepted' => 'green',
                        'sent' => 'blue',
                        'draft' => 'gray',
                        'declined', 'expired' => 'red',
                        'converted' => 'purple',
                        default => 'gray',
                    },
                ],
            ],

            'projects' => [
                'query' => fn (string $q, int $userId) => Project::search($q)->with('client:id,name'),
                'format' => fn (Project $project) => [
                    'id' => $project->id,
                    'type' => 'projects',
                    'title' => $project->title,
                    'subtitle' => collect([$project->client?->name, $project->status])->filter()->join(' · '),
                    'url' => route('projects.show', $project),
                    'status' => $project->status,
                    'status_color' => match ($project->status) {
                        'in_progress' => 'blue',
                        'done' => 'green',
                        'backlog', 'next' => 'gray',
                        'waiting_for' => 'yellow',
                        default => 'gray',
                    },
                ],
            ],

            'tasks' => [
                'query' => fn (string $q, int $userId) => Task::search($q)
                    ->whereHas('project', fn ($pq) => $pq->where('user_id', $userId))
                    ->with('project:id,title'),
                'format' => fn (Task $task) => [
                    'id' => $task->id,
                    'type' => 'tasks',
                    'title' => $task->title,
                    'subtitle' => collect([$task->project?->title, $task->priority])->filter()->join(' · '),
                    'url' => route('projects.show', $task->project_id),
                    'status' => $task->status,
                    'status_color' => match ($task->status) {
                        'done' => 'green',
                        'in_progress' => 'blue',
                        'backlog', 'next' => 'gray',
                        default => 'gray',
                    },
                ],
            ],

            'expenses' => [
                'query' => fn (string $q, int $userId) => Expense::search($q),
                'format' => fn (Expense $exp) => [
                    'id' => $exp->id,
                    'type' => 'expenses',
                    'title' => $exp->provider_name ?: $exp->description,
                    'subtitle' => collect([
                        $exp->category,
                        number_format($exp->amount_ht ?? 0, 2, ',', ' ') . ' €',
                        $exp->date?->format('d/m/Y'),
                    ])->filter()->join(' · '),
                    'url' => route('expenses.show', $exp),
                ],
            ],

            'time_entries' => [
                'query' => fn (string $q, int $userId) => TimeEntry::search($q),
                'format' => fn (TimeEntry $te) => [
                    'id' => $te->id,
                    'type' => 'time_entries',
                    'title' => $te->description ?: $te->project_name,
                    'subtitle' => collect([
                        $te->project_name,
                        $te->duration_seconds ? gmdate('H:i', $te->duration_seconds) : null,
                        $te->started_at?->format('d/m/Y'),
                    ])->filter()->join(' · '),
                    'url' => route('time-entries.index'),
                ],
            ],

            'employees' => [
                'query' => fn (string $q, int $userId) => Employee::search($q)->with('department:id,name'),
                'format' => fn (Employee $emp) => [
                    'id' => $emp->id,
                    'type' => 'employees',
                    'title' => "{$emp->first_name} {$emp->last_name}",
                    'subtitle' => collect([$emp->job_title, $emp->department?->name])->filter()->join(' · '),
                    'url' => route('hr.employees.show', $emp),
                ],
            ],
        ];
    }
}
