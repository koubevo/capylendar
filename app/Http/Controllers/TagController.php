<?php

namespace App\Http\Controllers;

use App\Http\Requests\Settings\StoreTagRequest;
use App\Models\Tag;
use App\Services\TagService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class TagController extends Controller
{
    public function __construct(protected TagService $tagService) {}

    public function index(): Response
    {
        return Inertia::render('settings/Tags', [
            'tags' => $this->tagService->getAvailableTags(['by' => 'created_at', 'order' => 'desc']),
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
