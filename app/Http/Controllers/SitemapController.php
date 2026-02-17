<?php

namespace App\Http\Controllers;

use App\Models\BlogPost;
use App\Models\BlogCategory;
use App\Models\BlogTag;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

class SitemapController extends Controller
{
    /**
     * Supported locales for the sitemap.
     */
    protected array $locales = ['fr', 'de', 'en', 'lb'];

    /**
     * Localized route slugs for SEO-friendly URLs.
     */
    protected array $localizedRoutes;

    public function __construct()
    {
        $this->localizedRoutes = config('localized_routes', []);
    }

    /**
     * Generate the main sitemap index.
     */
    public function index(): Response
    {
        $content = Cache::remember('sitemap-index', 3600, function () {
            return $this->generateSitemapIndex();
        });

        return response($content, 200, [
            'Content-Type' => 'application/xml',
        ]);
    }

    /**
     * Generate the pages sitemap.
     */
    public function pages(): Response
    {
        $content = Cache::remember('sitemap-pages', 3600, function () {
            return $this->generatePagesSitemap();
        });

        return response($content, 200, [
            'Content-Type' => 'application/xml',
        ]);
    }

    /**
     * Generate the blog sitemap.
     */
    public function blog(): Response
    {
        $content = Cache::remember('sitemap-blog', 3600, function () {
            return $this->generateBlogSitemap();
        });

        return response($content, 200, [
            'Content-Type' => 'application/xml',
        ]);
    }

    /**
     * Generate the sitemap index XML.
     */
    protected function generateSitemapIndex(): string
    {
        $baseUrl = config('app.url');

        $xml = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml .= '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

        // Pages sitemap
        $xml .= '<sitemap>';
        $xml .= '<loc>' . $baseUrl . '/sitemap-pages.xml</loc>';
        $xml .= '<lastmod>' . now()->toAtomString() . '</lastmod>';
        $xml .= '</sitemap>';

        // Blog sitemap
        $xml .= '<sitemap>';
        $xml .= '<loc>' . $baseUrl . '/sitemap-blog.xml</loc>';
        $xml .= '<lastmod>' . now()->toAtomString() . '</lastmod>';
        $xml .= '</sitemap>';

        $xml .= '</sitemapindex>';

        return $xml;
    }

    /**
     * Generate the pages sitemap XML.
     */
    protected function generatePagesSitemap(): string
    {
        $baseUrl = config('app.url');

        $xml = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" ';
        $xml .= 'xmlns:xhtml="http://www.w3.org/1999/xhtml">';

        // Static pages with their route names (for localized slugs)
        $pages = [
            ['route' => null, 'path' => '/', 'priority' => '1.0', 'changefreq' => 'weekly'],
            ['route' => 'pricing', 'priority' => '0.9', 'changefreq' => 'monthly'],
            ['route' => 'faia-validator', 'priority' => '0.8', 'changefreq' => 'monthly'],
            ['route' => 'blog.index', 'priority' => '0.8', 'changefreq' => 'daily'],
            ['route' => 'legal.mentions', 'priority' => '0.3', 'changefreq' => 'yearly'],
            ['route' => 'legal.privacy', 'priority' => '0.3', 'changefreq' => 'yearly'],
            ['route' => 'legal.terms', 'priority' => '0.3', 'changefreq' => 'yearly'],
            ['route' => 'legal.cookies', 'priority' => '0.3', 'changefreq' => 'yearly'],
        ];

        foreach ($pages as $page) {
            $xml .= $this->generateLocalizedUrlEntry($baseUrl, $page['route'] ?? null, $page['path'] ?? null, $page['priority'], $page['changefreq']);
        }

        $xml .= '</urlset>';

        return $xml;
    }

    /**
     * Generate the blog sitemap XML.
     */
    protected function generateBlogSitemap(): string
    {
        $baseUrl = config('app.url');

        $xml = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" ';
        $xml .= 'xmlns:xhtml="http://www.w3.org/1999/xhtml">';

        // Blog posts
        $posts = BlogPost::published()
            ->select(['slug', 'locale', 'updated_at'])
            ->orderByDesc('published_at')
            ->get();

        foreach ($posts as $post) {
            $xml .= '<url>';
            $xml .= '<loc>' . $baseUrl . '/' . $post->locale . '/blog/' . $post->slug . '</loc>';
            $xml .= '<lastmod>' . $post->updated_at->toAtomString() . '</lastmod>';
            $xml .= '<changefreq>monthly</changefreq>';
            $xml .= '<priority>0.7</priority>';

            // Hreflang for the post's locale
            $xml .= '<xhtml:link rel="alternate" hreflang="' . $post->locale . '" ';
            $xml .= 'href="' . $baseUrl . '/' . $post->locale . '/blog/' . $post->slug . '" />';

            $xml .= '</url>';
        }

        // Blog categories (for each locale with localized paths)
        $categories = BlogCategory::all();
        foreach ($categories as $category) {
            foreach ($this->locales as $locale) {
                $catSlug = $this->getLocalizedSlug('blog.category', $locale);
                $xml .= '<url>';
                $xml .= '<loc>' . $baseUrl . '/' . $locale . '/' . $catSlug . '/' . $category->slug . '</loc>';
                $xml .= '<changefreq>weekly</changefreq>';
                $xml .= '<priority>0.6</priority>';

                // Hreflang alternatives with localized paths
                foreach ($this->locales as $altLocale) {
                    $altCatSlug = $this->getLocalizedSlug('blog.category', $altLocale);
                    $xml .= '<xhtml:link rel="alternate" hreflang="' . $altLocale . '" ';
                    $xml .= 'href="' . $baseUrl . '/' . $altLocale . '/' . $altCatSlug . '/' . $category->slug . '" />';
                }
                $frCatSlug = $this->getLocalizedSlug('blog.category', 'fr');
                $xml .= '<xhtml:link rel="alternate" hreflang="x-default" ';
                $xml .= 'href="' . $baseUrl . '/fr/' . $frCatSlug . '/' . $category->slug . '" />';

                $xml .= '</url>';
            }
        }

        // Blog tags (for each locale with localized paths)
        $tags = BlogTag::all();
        foreach ($tags as $tag) {
            foreach ($this->locales as $locale) {
                $tagSlug = $this->getLocalizedSlug('blog.tag', $locale);
                $xml .= '<url>';
                $xml .= '<loc>' . $baseUrl . '/' . $locale . '/' . $tagSlug . '/' . $tag->slug . '</loc>';
                $xml .= '<changefreq>weekly</changefreq>';
                $xml .= '<priority>0.5</priority>';

                // Hreflang alternatives with localized paths
                foreach ($this->locales as $altLocale) {
                    $altTagSlug = $this->getLocalizedSlug('blog.tag', $altLocale);
                    $xml .= '<xhtml:link rel="alternate" hreflang="' . $altLocale . '" ';
                    $xml .= 'href="' . $baseUrl . '/' . $altLocale . '/' . $altTagSlug . '/' . $tag->slug . '" />';
                }
                $frTagSlug = $this->getLocalizedSlug('blog.tag', 'fr');
                $xml .= '<xhtml:link rel="alternate" hreflang="x-default" ';
                $xml .= 'href="' . $baseUrl . '/fr/' . $frTagSlug . '/' . $tag->slug . '" />';

                $xml .= '</url>';
            }
        }

        $xml .= '</urlset>';

        return $xml;
    }

    /**
     * Get the localized slug for a route and locale.
     */
    protected function getLocalizedSlug(?string $routeName, string $locale): string
    {
        if (!$routeName) {
            return '';
        }

        return $this->localizedRoutes[$routeName][$locale] ?? '';
    }

    /**
     * Generate a URL entry with localized slugs and hreflang alternatives.
     */
    protected function generateLocalizedUrlEntry(string $baseUrl, ?string $routeName, ?string $staticPath, string $priority, string $changefreq): string
    {
        $xml = '';

        foreach ($this->locales as $locale) {
            // Get localized path
            if ($staticPath !== null) {
                $path = $staticPath;
            } else {
                $slug = $this->getLocalizedSlug($routeName, $locale);
                $path = $slug ? '/' . $slug : '';
            }

            $xml .= '<url>';
            $xml .= '<loc>' . $baseUrl . '/' . $locale . $path . '</loc>';
            $xml .= '<changefreq>' . $changefreq . '</changefreq>';
            $xml .= '<priority>' . $priority . '</priority>';

            // Hreflang alternatives for all locales with localized slugs
            foreach ($this->locales as $altLocale) {
                if ($staticPath !== null) {
                    $altPath = $staticPath;
                } else {
                    $altSlug = $this->getLocalizedSlug($routeName, $altLocale);
                    $altPath = $altSlug ? '/' . $altSlug : '';
                }
                $xml .= '<xhtml:link rel="alternate" hreflang="' . $altLocale . '" ';
                $xml .= 'href="' . $baseUrl . '/' . $altLocale . $altPath . '" />';
            }

            // x-default points to French version
            if ($staticPath !== null) {
                $defaultPath = $staticPath;
            } else {
                $defaultSlug = $this->getLocalizedSlug($routeName, 'fr');
                $defaultPath = $defaultSlug ? '/' . $defaultSlug : '';
            }
            $xml .= '<xhtml:link rel="alternate" hreflang="x-default" ';
            $xml .= 'href="' . $baseUrl . '/fr' . $defaultPath . '" />';

            $xml .= '</url>';
        }

        return $xml;
    }

    /**
     * Clear sitemap cache (call after content changes).
     */
    public static function clearCache(): void
    {
        Cache::forget('sitemap-index');
        Cache::forget('sitemap-pages');
        Cache::forget('sitemap-blog');
    }
}
