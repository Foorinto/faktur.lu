<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BlogCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class AdminBlogCategoryController extends Controller
{
    public function index(): Response
    {
        $categories = BlogCategory::withCount('posts')
            ->orderBy('sort_order')
            ->get();

        return Inertia::render('Admin/Blog/Categories', [
            'categories' => $categories,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:blog_categories,slug',
            'description' => 'nullable|string|max:500',
            'sort_order' => 'nullable|integer',
        ]);

        $slug = $validated['slug'] ?? Str::slug($validated['name']);

        BlogCategory::create([
            'name' => $validated['name'],
            'slug' => $slug,
            'description' => $validated['description'],
            'sort_order' => $validated['sort_order'] ?? 0,
        ]);

        return back()->with('success', 'Catégorie créée avec succès.');
    }

    public function update(Request $request, BlogCategory $category): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:blog_categories,slug,' . $category->id,
            'description' => 'nullable|string|max:500',
            'sort_order' => 'nullable|integer',
        ]);

        $category->update([
            'name' => $validated['name'],
            'slug' => $validated['slug'] ?? $category->slug,
            'description' => $validated['description'],
            'sort_order' => $validated['sort_order'] ?? $category->sort_order,
        ]);

        return back()->with('success', 'Catégorie mise à jour avec succès.');
    }

    public function destroy(BlogCategory $category): RedirectResponse
    {
        if ($category->posts()->count() > 0) {
            return back()->with('error', 'Impossible de supprimer une catégorie contenant des articles.');
        }

        $category->delete();

        return back()->with('success', 'Catégorie supprimée avec succès.');
    }

    public function reorder(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'categories' => 'required|array',
            'categories.*.id' => 'required|exists:blog_categories,id',
            'categories.*.sort_order' => 'required|integer',
        ]);

        foreach ($validated['categories'] as $item) {
            BlogCategory::where('id', $item['id'])->update(['sort_order' => $item['sort_order']]);
        }

        return back()->with('success', 'Ordre des catégories mis à jour.');
    }
}
