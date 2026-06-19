<?php

namespace App\Http\Controllers;

use Inertia\Inertia;

class FeaturePageController extends Controller
{
    /**
     * Available feature pages with their slugs and metadata.
     */
    protected array $features = [
        'facturation' => [
            'id' => 'invoicing',
            'icon' => 'document',
            'color' => '#9b5de5',
        ],
        'devis' => [
            'id' => 'quotes',
            'icon' => 'clipboard',
            'color' => '#f15bb5',
        ],
        'clients' => [
            'id' => 'clients',
            'icon' => 'users',
            'color' => '#00bbf9',
        ],
        'depenses' => [
            'id' => 'expenses',
            'icon' => 'credit-card',
            'color' => '#fee440',
        ],
        'faia' => [
            'id' => 'faia',
            'icon' => 'shield',
            'color' => '#00f5d4',
        ],
        'peppol' => [
            'id' => 'peppol',
            'icon' => 'globe',
            'color' => '#00bbf9',
        ],
        'gestion-projets' => [
            'id' => 'projects',
            'icon' => 'folder',
            'color' => '#f15bb5',
        ],
        'suivi-temps' => [
            'id' => 'time-tracking',
            'icon' => 'clock',
            'color' => '#fee440',
        ],
        'module-rh' => [
            'id' => 'hr',
            'icon' => 'identification',
            'color' => '#9b5de5',
        ],
        'crm' => [
            'id' => 'crm',
            'icon' => 'chat',
            'color' => '#00f5d4',
        ],
        'exports-comptables' => [
            'id' => 'accounting-exports',
            'icon' => 'calculator',
            'color' => '#f97316',
        ],
        'factur-x' => [
            'id' => 'facturx',
            'icon' => 'document-duplicate',
            'color' => '#6366f1',
        ],
        'numerotation-personnalisable' => [
            'id' => 'custom-numbering',
            'icon' => 'pencil-square',
            'color' => '#ec4899',
        ],
    ];

    /**
     * Canonical (French) slugs of every feature page. Single source of truth
     * shared with SitemapController so the sitemap can never drift from the
     * actual pages (a missing slug = a linked, indexable page absent du sitemap).
     */
    public static function featureSlugs(): array
    {
        return array_keys((new self())->features);
    }

    /**
     * Map localized slugs to canonical (French) slugs.
     */
    protected function resolveSlug(string $slug): ?string
    {
        // Direct match (French slugs)
        if (isset($this->features[$slug])) {
            return $slug;
        }

        // Localized slug mappings to canonical French slug
        $slugMap = [
            // German
            'rechnungsstellung' => 'facturation',
            'angebote' => 'devis',
            'kunden' => 'clients',
            'ausgaben' => 'depenses',
            'projektverwaltung' => 'gestion-projets',
            'zeiterfassung' => 'suivi-temps',
            'hr-modul' => 'module-rh',
            'buchhaltungsexporte' => 'exports-comptables',
            // English
            'invoicing' => 'facturation',
            'quotes' => 'devis',
            'clients' => 'clients',
            'expenses' => 'depenses',
            'project-management' => 'gestion-projets',
            'time-tracking' => 'suivi-temps',
            'hr-module' => 'module-rh',
            'accounting-exports' => 'exports-comptables',
            'custom-numbering' => 'numerotation-personnalisable',
            'benutzerdefinierte-nummerierung' => 'numerotation-personnalisable',
            'personaliseier-nummeréierung' => 'numerotation-personnalisable',
            'numeracao-personalizada' => 'numerotation-personnalisable',
            // Luxembourgish
            'fakturatioun' => 'facturation',
            'devis-lb' => 'devis',
            'clienten' => 'clients',
            'ausgaben-lb' => 'depenses',
            'projetverwaltung' => 'gestion-projets',
            'zäiterfassung' => 'suivi-temps',
            'rh-modul' => 'module-rh',
            'buchhaltungsexporter' => 'exports-comptables',
        ];

        return $slugMap[$slug] ?? null;
    }

    /**
     * Features overview page.
     */
    public function index()
    {
        $features = collect($this->features)->map(fn ($data, $slug) => [
            'slug' => $slug,
            ...$data,
        ])->values()->all();

        return Inertia::render('Features/Index', [
            'features' => $features,
        ]);
    }

    /**
     * Individual feature detail page.
     */
    public function show(string $locale, string $slug)
    {
        $canonicalSlug = $this->resolveSlug($slug);

        if (!$canonicalSlug || !isset($this->features[$canonicalSlug])) {
            abort(404);
        }

        $feature = $this->features[$canonicalSlug];

        // Get all other features for the "related features" section
        $otherFeatures = collect($this->features)
            ->filter(fn ($data, $s) => $s !== $canonicalSlug)
            ->map(fn ($data, $s) => ['slug' => $s, ...$data])
            ->values()
            ->all();

        return Inertia::render('Features/Show', [
            'feature' => [
                'slug' => $canonicalSlug,
                ...$feature,
            ],
            'otherFeatures' => $otherFeatures,
        ]);
    }
}
