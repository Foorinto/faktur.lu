<?php

namespace App\Http\Controllers;

use App\Services\GlobalSearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SearchController extends Controller
{
    public function __construct(
        private readonly GlobalSearchService $searchService,
    ) {}

    /**
     * JSON API for the command palette modal.
     */
    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'q' => 'required|string|min:2|max:100',
        ]);

        $results = $this->searchService->preview(
            query: $request->input('q'),
            limit: 5,
        );

        return response()->json($results);
    }

    /**
     * Full results page (Inertia).
     */
    public function results(Request $request): Response
    {
        $request->validate([
            'q' => 'required|string|min:2|max:100',
            'category' => 'nullable|string|in:clients,invoices,quotes,projects,tasks,expenses,time_entries,employees',
        ]);

        $query = $request->input('q');
        $category = $request->input('category');

        $results = $this->searchService->paginated(
            query: $query,
            category: $category,
        );

        return Inertia::render('Search/Results', [
            'results' => $results['items'],
            'filters' => [
                'q' => $query,
                'category' => $category,
            ],
            'categoryCounts' => $this->searchService->getCategoryCounts($query),
            'totalResults' => $results['total'],
        ]);
    }
}
