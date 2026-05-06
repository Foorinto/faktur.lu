<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NewsletterSubscriber;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminNewsletterController extends Controller
{
    public function index(Request $request)
    {
        $query = NewsletterSubscriber::query();

        if ($search = $request->get('search')) {
            $query->where('email', 'like', "%{$search}%");
        }

        if ($source = $request->get('source')) {
            if ($source === 'tools') {
                $query->where('source', 'like', 'templates_%');
            } elseif ($source === 'footer') {
                $query->where('source', 'footer');
            } else {
                $query->where('source', $source);
            }
        }

        if ($locale = $request->get('locale')) {
            $query->where('locale', $locale);
        }

        $sortField = $request->get('sort', 'created_at');
        $sortDirection = $request->get('direction', 'desc');
        $allowedSorts = ['email', 'locale', 'source', 'created_at', 'confirmed_at'];

        if (in_array($sortField, $allowedSorts)) {
            $query->orderBy($sortField, $sortDirection);
        }

        $subscribers = $query->paginate(50)->withQueryString();

        $stats = [
            'total' => NewsletterSubscriber::count(),
            'tools' => NewsletterSubscriber::where('source', 'like', 'templates_%')->count(),
            'footer' => NewsletterSubscriber::where('source', 'footer')->count(),
            'confirmed' => NewsletterSubscriber::whereNotNull('confirmed_at')->count(),
            'unsubscribed' => NewsletterSubscriber::whereNotNull('unsubscribed_at')->count(),
            'by_template' => NewsletterSubscriber::where('source', 'like', 'templates_%')
                ->selectRaw('source, COUNT(*) as count')
                ->groupBy('source')
                ->pluck('count', 'source'),
        ];

        return Inertia::render('Admin/Newsletter/Index', [
            'subscribers' => $subscribers,
            'stats' => $stats,
            'filters' => [
                'search' => $request->get('search', ''),
                'source' => $request->get('source', ''),
                'locale' => $request->get('locale', ''),
                'sort' => $sortField,
                'direction' => $sortDirection,
            ],
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $query = NewsletterSubscriber::query();

        if ($source = $request->get('source')) {
            if ($source === 'tools') {
                $query->where('source', 'like', 'templates_%');
            } elseif ($source === 'footer') {
                $query->where('source', 'footer');
            } else {
                $query->where('source', $source);
            }
        }

        if ($locale = $request->get('locale')) {
            $query->where('locale', $locale);
        }

        $filename = 'newsletter-subscribers-' . now()->format('Y-m-d-His') . '.csv';

        return response()->streamDownload(function () use ($query) {
            $handle = fopen('php://output', 'w');
            // BOM UTF-8 pour Excel
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, ['email', 'locale', 'source', 'confirmed_at', 'unsubscribed_at', 'created_at']);

            $query->orderBy('created_at', 'desc')
                ->chunk(500, function ($subscribers) use ($handle) {
                    foreach ($subscribers as $sub) {
                        fputcsv($handle, [
                            $sub->email,
                            $sub->locale,
                            $sub->source,
                            $sub->confirmed_at?->toIso8601String(),
                            $sub->unsubscribed_at?->toIso8601String(),
                            $sub->created_at?->toIso8601String(),
                        ]);
                    }
                });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function destroy(NewsletterSubscriber $subscriber)
    {
        $subscriber->delete();

        return back()->with('success', 'Abonné supprimé.');
    }
}
