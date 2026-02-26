<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Tag;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class TagController extends Controller
{
    public function index(): JsonResponse
    {
        $tags = Tag::query()->orderBy('name')->get();

        return response()->json($tags);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:50'],
            'color' => ['required', 'string', 'max:7', 'regex:/^#[0-9a-fA-F]{6}$/'],
        ]);

        $tag = Tag::create($validated);

        return response()->json($tag, 201);
    }

    public function update(Request $request, Tag $tag): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:50'],
            'color' => ['required', 'string', 'max:7', 'regex:/^#[0-9a-fA-F]{6}$/'],
        ]);

        $tag->update($validated);

        return response()->json($tag);
    }

    public function destroy(Tag $tag): JsonResponse
    {
        $tag->clients()->detach();
        $tag->delete();

        return response()->json(null, 204);
    }

    public function attach(Client $client, Tag $tag): RedirectResponse
    {
        $client->tags()->syncWithoutDetaching([$tag->id]);

        return back()->with('success', 'Tag ajouté.');
    }

    public function detach(Client $client, Tag $tag): RedirectResponse
    {
        $client->tags()->detach($tag->id);

        return back()->with('success', 'Tag retiré.');
    }
}
