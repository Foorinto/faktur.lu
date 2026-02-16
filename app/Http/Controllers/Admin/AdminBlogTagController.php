<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BlogTag;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class AdminBlogTagController extends Controller
{
    public function index(): Response
    {
        $tags = BlogTag::withCount('posts')
            ->orderBy('name')
            ->get();

        return Inertia::render('Admin/Blog/Tags', [
            'tags' => $tags,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:blog_tags,name',
        ]);

        BlogTag::create([
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']),
        ]);

        return back()->with('success', 'Tag créé avec succès.');
    }

    public function update(Request $request, BlogTag $tag): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:50|unique:blog_tags,name,' . $tag->id,
            'slug' => 'nullable|string|max:50|unique:blog_tags,slug,' . $tag->id,
        ]);

        $tag->update([
            'name' => $validated['name'],
            'slug' => $validated['slug'] ?? Str::slug($validated['name']),
        ]);

        return back()->with('success', 'Tag mis à jour avec succès.');
    }

    public function destroy(BlogTag $tag): RedirectResponse
    {
        $tag->posts()->detach();
        $tag->delete();

        return back()->with('success', 'Tag supprimé avec succès.');
    }
}
