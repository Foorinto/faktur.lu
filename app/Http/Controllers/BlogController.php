<?php

namespace App\Http\Controllers;

use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\BlogTag;
use Inertia\Inertia;
use Inertia\Response;

class BlogController extends Controller
{
    public function index(): Response
    {
        $posts = BlogPost::published()
            ->with(['category', 'author'])
            ->orderByDesc('published_at')
            ->paginate(10);

        $categories = BlogCategory::withCount(['posts' => function ($query) {
            $query->published();
        }])
            ->having('posts_count', '>', 0)
            ->orderBy('sort_order')
            ->get();

        $recentPosts = BlogPost::published()
            ->select(['id', 'title', 'slug', 'published_at'])
            ->orderByDesc('published_at')
            ->limit(5)
            ->get();

        return Inertia::render('Blog/Index', [
            'posts' => $posts,
            'categories' => $categories,
            'recentPosts' => $recentPosts,
        ]);
    }

    public function show(BlogPost $post): Response
    {
        if (!$post->isPublished()) {
            abort(404);
        }

        $post->load(['category', 'author', 'tags']);
        $post->incrementViews();

        $relatedPosts = BlogPost::published()
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

    public function category(BlogCategory $category): Response
    {
        $posts = BlogPost::published()
            ->where('category_id', $category->id)
            ->with(['category', 'author'])
            ->orderByDesc('published_at')
            ->paginate(10);

        $categories = BlogCategory::withCount(['posts' => function ($query) {
            $query->published();
        }])
            ->having('posts_count', '>', 0)
            ->orderBy('sort_order')
            ->get();

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

    public function tag(BlogTag $tag): Response
    {
        $posts = $tag->posts()
            ->published()
            ->with(['category', 'author'])
            ->orderByDesc('published_at')
            ->paginate(10);

        return Inertia::render('Blog/Tag', [
            'tag' => [
                'name' => $tag->name,
                'slug' => $tag->slug,
            ],
            'posts' => $posts,
        ]);
    }
}
