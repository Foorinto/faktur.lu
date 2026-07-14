<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;

/**
 * "Alternative à X" comparison pages (SEO, high buyer intent).
 *
 * Content lives in the Vue component + lang files (alternatives.common.* and
 * alternatives.{id}.*), mirroring FeaturePageController. The competitor column
 * uses cautious, defensible wording (international generalist tools) — never an
 * unverifiable claim about a named product.
 */
class AlternativeController extends Controller
{
    /**
     * Whitelisted competitors, keyed by URL slug.
     */
    protected array $competitors = [
        'sage' => ['id' => 'sage', 'name' => 'Sage'],
        'quickbooks' => ['id' => 'quickbooks', 'name' => 'QuickBooks'],
        'zervant' => ['id' => 'zervant', 'name' => 'Zervant'],
        'zoho' => ['id' => 'zoho', 'name' => 'Zoho Invoice'],
        'cegid' => ['id' => 'cegid', 'name' => 'Cegid'],
    ];

    /**
     * Slugs of every alternative page — single source of truth shared with the
     * sitemap so it can never drift from the actual pages.
     *
     * @return array<int, string>
     */
    public static function competitorSlugs(): array
    {
        return array_keys((new self())->competitors);
    }

    public function show(string $locale, string $competitor): Response
    {
        if (! isset($this->competitors[$competitor])) {
            abort(404);
        }

        return Inertia::render('Alternatives/Show', [
            'competitor' => $this->competitors[$competitor],
            'others' => collect($this->competitors)
                ->reject(fn ($data, $slug) => $slug === $competitor)
                ->map(fn ($data, $slug) => ['slug' => $slug, ...$data])
                ->values()
                ->all(),
        ]);
    }
}
