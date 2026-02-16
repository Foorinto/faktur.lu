<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\BlogTag;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class AdminBlogController extends Controller
{
    /**
     * Process tags: sync existing IDs and create new tags from names.
     */
    private function processTags(array $existingTagIds, array $newTagNames): array
    {
        $allTagIds = $existingTagIds;

        foreach ($newTagNames as $tagName) {
            $tagName = trim($tagName);
            if (empty($tagName)) {
                continue;
            }

            // Check if tag already exists (case-insensitive)
            $existingTag = BlogTag::whereRaw('LOWER(name) = ?', [strtolower($tagName)])->first();

            if ($existingTag) {
                if (!in_array($existingTag->id, $allTagIds)) {
                    $allTagIds[] = $existingTag->id;
                }
            } else {
                // Create new tag
                $newTag = BlogTag::create(['name' => $tagName]);
                $allTagIds[] = $newTag->id;
            }
        }

        return $allTagIds;
    }

    public function index(Request $request): Response
    {
        $query = BlogPost::with(['category', 'author']);

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('excerpt', 'like', "%{$search}%");
            });
        }

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        if ($categoryId = $request->get('category')) {
            $query->where('category_id', $categoryId);
        }

        $sortField = $request->get('sort', 'created_at');
        $sortDirection = $request->get('direction', 'desc');

        $query->orderBy($sortField, $sortDirection);

        $posts = $query->paginate(15)->withQueryString();

        return Inertia::render('Admin/Blog/Index', [
            'posts' => $posts,
            'categories' => BlogCategory::orderBy('sort_order')->get(),
            'filters' => [
                'search' => $request->get('search', ''),
                'status' => $request->get('status', ''),
                'category' => $request->get('category', ''),
            ],
            'stats' => [
                'total' => BlogPost::count(),
                'published' => BlogPost::where('status', 'published')->count(),
                'draft' => BlogPost::where('status', 'draft')->count(),
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Blog/Create', [
            'categories' => BlogCategory::orderBy('sort_order')->get(),
            'tags' => BlogTag::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:blog_posts,slug',
            'excerpt' => 'nullable|string|max:500',
            'content' => 'required|string',
            'category_id' => 'nullable|exists:blog_categories,id',
            'cover_image' => 'nullable|image|max:2048',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'status' => 'required|in:draft,published,archived',
            'published_at' => 'nullable|date',
            'tags' => 'nullable|array',
            'tags.*' => 'exists:blog_tags,id',
            'new_tags' => 'nullable|array',
            'new_tags.*' => 'string|max:50',
        ]);

        $slug = $validated['slug'] ?? Str::slug($validated['title']);
        $baseSlug = $slug;
        $counter = 1;
        while (BlogPost::where('slug', $slug)->exists()) {
            $slug = $baseSlug . '-' . $counter++;
        }

        $coverImage = null;
        if ($request->hasFile('cover_image')) {
            $coverImage = $request->file('cover_image')->store('blog/covers', 'public');
        }

        $post = BlogPost::create([
            'title' => $validated['title'],
            'slug' => $slug,
            'excerpt' => $validated['excerpt'],
            'content' => $validated['content'],
            'category_id' => $validated['category_id'],
            'cover_image' => $coverImage,
            'meta_title' => $validated['meta_title'],
            'meta_description' => $validated['meta_description'],
            'status' => $validated['status'],
            'published_at' => $validated['status'] === 'published'
                ? ($validated['published_at'] ?? now())
                : $validated['published_at'],
            'author_id' => null, // Admin posts don't have user author
        ]);

        $tagIds = $this->processTags(
            $validated['tags'] ?? [],
            $validated['new_tags'] ?? []
        );
        $post->tags()->sync($tagIds);

        return redirect()->route('admin.blog.index')
            ->with('success', 'Article créé avec succès.');
    }

    public function edit(BlogPost $post): Response
    {
        $post->load('tags');

        return Inertia::render('Admin/Blog/Edit', [
            'post' => [
                'id' => $post->id,
                'title' => $post->title,
                'slug' => $post->slug,
                'excerpt' => $post->excerpt,
                'content' => $post->content,
                'category_id' => $post->category_id,
                'cover_image' => $post->cover_image,
                'cover_image_url' => $post->cover_image_url,
                'meta_title' => $post->meta_title,
                'meta_description' => $post->meta_description,
                'status' => $post->status,
                'published_at' => $post->published_at?->format('Y-m-d\TH:i'),
                'tags' => $post->tags->pluck('id'),
            ],
            'categories' => BlogCategory::orderBy('sort_order')->get(),
            'tags' => BlogTag::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, BlogPost $post): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:blog_posts,slug,' . $post->id,
            'excerpt' => 'nullable|string|max:500',
            'content' => 'required|string',
            'category_id' => 'nullable|exists:blog_categories,id',
            'cover_image' => 'nullable|image|max:2048',
            'remove_cover' => 'nullable|boolean',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'status' => 'required|in:draft,published,archived',
            'published_at' => 'nullable|date',
            'tags' => 'nullable|array',
            'tags.*' => 'exists:blog_tags,id',
            'new_tags' => 'nullable|array',
            'new_tags.*' => 'string|max:50',
        ]);

        $coverImage = $post->cover_image;

        if ($request->boolean('remove_cover') && $coverImage) {
            Storage::disk('public')->delete($coverImage);
            $coverImage = null;
        }

        if ($request->hasFile('cover_image')) {
            if ($post->cover_image) {
                Storage::disk('public')->delete($post->cover_image);
            }
            $coverImage = $request->file('cover_image')->store('blog/covers', 'public');
        }

        $post->update([
            'title' => $validated['title'],
            'slug' => $validated['slug'] ?? $post->slug,
            'excerpt' => $validated['excerpt'],
            'content' => $validated['content'],
            'category_id' => $validated['category_id'],
            'cover_image' => $coverImage,
            'meta_title' => $validated['meta_title'],
            'meta_description' => $validated['meta_description'],
            'status' => $validated['status'],
            'published_at' => $validated['status'] === 'published' && !$post->published_at
                ? ($validated['published_at'] ?? now())
                : $validated['published_at'],
        ]);

        $tagIds = $this->processTags(
            $validated['tags'] ?? [],
            $validated['new_tags'] ?? []
        );
        $post->tags()->sync($tagIds);

        return redirect()->route('admin.blog.index')
            ->with('success', 'Article mis à jour avec succès.');
    }

    public function destroy(BlogPost $post): RedirectResponse
    {
        if ($post->cover_image) {
            Storage::disk('public')->delete($post->cover_image);
        }

        $post->tags()->detach();
        $post->delete();

        return redirect()->route('admin.blog.index')
            ->with('success', 'Article supprimé avec succès.');
    }

    public function duplicate(BlogPost $post): RedirectResponse
    {
        $newPost = $post->replicate();
        $newPost->title = $post->title . ' (copie)';
        $newPost->slug = Str::slug($newPost->title);
        $newPost->status = 'draft';
        $newPost->published_at = null;
        $newPost->views_count = 0;
        $newPost->save();

        $newPost->tags()->sync($post->tags->pluck('id'));

        return redirect()->route('admin.blog.edit', $newPost)
            ->with('success', 'Article dupliqué avec succès.');
    }
}
