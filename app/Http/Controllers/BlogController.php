<?php

namespace App\Http\Controllers;

use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\BlogTag;
use Illuminate\Support\Facades\App;
use Inertia\Inertia;
use Inertia\Response;

class BlogController extends Controller
{
    /**
     * Get the current locale from the route or app.
     */
    protected function getLocale(): string
    {
        return request()->route('locale') ?? App::getLocale();
    }

    public function index(string $locale): Response
    {
        $posts = BlogPost::published()
            ->forLocale($locale)
            ->with(['category', 'author'])
            ->orderByDesc('published_at')
            ->paginate(10);

        // If no posts in requested locale, try fallback to French
        if ($posts->isEmpty() && $locale !== 'fr') {
            $posts = BlogPost::published()
                ->forLocale('fr')
                ->with(['category', 'author'])
                ->orderByDesc('published_at')
                ->paginate(10);
        }

        $categories = BlogCategory::withCount(['posts' => function ($query) use ($locale) {
            $query->published()->forLocale($locale);
        }])
            ->orderBy('sort_order')
            ->get()
            ->filter(fn ($category) => $category->posts_count > 0)
            ->values();

        $recentPosts = BlogPost::published()
            ->forLocale($locale)
            ->select(['id', 'title', 'slug', 'published_at', 'locale'])
            ->orderByDesc('published_at')
            ->limit(5)
            ->get();

        return Inertia::render('Blog/Index', [
            'posts' => $posts,
            'categories' => $categories,
            'recentPosts' => $recentPosts,
        ]);
    }

    public function show(string $locale, BlogPost $post): Response
    {
        if (!$post->isPublished()) {
            abort(404);
        }

        $post->load(['category', 'author', 'tags']);
        $post->incrementViews();

        // Get related posts in the same locale first, then fallback
        $relatedPosts = BlogPost::published()
            ->forLocale($post->locale)
            ->where('id', '!=', $post->id)
            ->where(function ($query) use ($post) {
                $query->where('category_id', $post->category_id)
                    ->orWhereHas('tags', function ($q) use ($post) {
                        $q->whereIn('blog_tags.id', $post->tags->pluck('id'));
                    });
            })
            ->with('category')
            ->orderByDesc('published_at')
            ->limit(3)
            ->get();

        return Inertia::render('Blog/Show', [
            'post' => [
                'id' => $post->id,
                'title' => $post->title,
                'slug' => $post->slug,
                'locale' => $post->locale,
                'excerpt' => $post->excerpt,
                'content' => $post->content,
                'cover_image_url' => $post->cover_image_url,
                'meta_title' => $post->meta_title,
                'meta_description' => $post->meta_description,
                'published_at' => $post->published_at->toISOString(),
                'reading_time' => $post->reading_time,
                'views_count' => $post->views_count,
                'category' => $post->category ? [
                    'name' => $post->category->name,
                    'slug' => $post->category->slug,
                ] : null,
                'author' => $post->author ? [
                    'name' => $post->author->name,
                ] : null,
                'tags' => $post->tags->map(fn ($tag) => [
                    'name' => $tag->name,
                    'slug' => $tag->slug,
                ]),
            ],
            'relatedPosts' => $relatedPosts->map(fn ($p) => [
                'title' => $p->title,
                'slug' => $p->slug,
                'excerpt' => $p->excerpt,
                'cover_image_url' => $p->cover_image_url,
                'published_at' => $p->published_at->toISOString(),
                'category' => $p->category?->name,
            ]),
        ]);
    }

    public function category(string $locale, BlogCategory $category): Response
    {
        $posts = BlogPost::published()
            ->forLocale($locale)
            ->where('category_id', $category->id)
            ->with(['category', 'author'])
            ->orderByDesc('published_at')
            ->paginate(10);

        // Fallback to French if no posts in requested locale
        if ($posts->isEmpty() && $locale !== 'fr') {
            $posts = BlogPost::published()
                ->forLocale('fr')
                ->where('category_id', $category->id)
                ->with(['category', 'author'])
                ->orderByDesc('published_at')
                ->paginate(10);
        }

        $categories = BlogCategory::withCount(['posts' => function ($query) use ($locale) {
            $query->published()->forLocale($locale);
        }])
            ->orderBy('sort_order')
            ->get()
            ->filter(fn ($category) => $category->posts_count > 0)
            ->values();

        return Inertia::render('Blog/Category', [
            'category' => [
                'name' => $category->name,
                'slug' => $category->slug,
                'description' => $category->description,
            ],
            'posts' => $posts,
            'categories' => $categories,
        ]);
    }

    public function tag(string $locale, BlogTag $tag): Response
    {
        $posts = $tag->posts()
            ->published()
            ->forLocale($locale)
            ->with(['category', 'author'])
            ->orderByDesc('published_at')
            ->paginate(10);

        // Fallback to French if no posts in requested locale
        if ($posts->isEmpty() && $locale !== 'fr') {
            $posts = $tag->posts()
                ->published()
                ->forLocale('fr')
                ->with(['category', 'author'])
                ->orderByDesc('published_at')
                ->paginate(10);
        }

        return Inertia::render('Blog/Tag', [
            'tag' => [
                'name' => $tag->name,
                'slug' => $tag->slug,
            ],
            'posts' => $posts,
        ]);
    }
}
