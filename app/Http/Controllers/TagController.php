<?php

namespace App\Http\Controllers;

use App\Http\Requests\Settings\StoreTagRequest;
use App\Models\Tag;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class TagController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('settings/Tags', [
            'tags' => Tag::all(),
        ]);
    }

    public function store(StoreTagRequest $request): RedirectResponse
    {
        Tag::create($request->validated());

        return back();
    }

    public function destroy(Tag $tag): RedirectResponse
    {
        $tag->delete();

        return to_route('tags.index')->with('success', 'Štítek byl úspěšně smazán');
    }
}
