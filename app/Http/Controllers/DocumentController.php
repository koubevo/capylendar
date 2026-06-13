<?php

namespace App\Http\Controllers;

use App\Http\Requests\Document\StoreDocumentRequest;
use App\Http\Requests\Document\UpdateDocumentRequest;
use App\Http\Resources\DocumentResource;
use App\Models\Document;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class DocumentController extends Controller
{
    public function index(): Response
    {
        $documents = Document::query()
            ->with('author')
            ->latest('updated_at')
            ->latest('id')
            ->get();

        return Inertia::render('documents/DocumentIndex', [
            'documents' => DocumentResource::collection($documents)->resolve(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('documents/DocumentCreate');
    }

    public function store(StoreDocumentRequest $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $document = Document::create([
            ...$request->validated(),
            'body' => $request->string('body')->toString(),
            'author_id' => $user->id,
        ]);

        return to_route('document.show', $document)->with('success', 'Dokument ulozen');
    }

    public function show(Document $document): Response
    {
        $document->loadMissing('author');

        return Inertia::render('documents/DocumentShow', [
            'document' => DocumentResource::make($document)->resolve(),
        ]);
    }

    public function edit(Document $document): Response
    {
        $document->loadMissing('author');

        return Inertia::render('documents/DocumentEdit', [
            'document' => DocumentResource::make($document)->resolve(),
        ]);
    }

    public function update(Document $document, UpdateDocumentRequest $request): RedirectResponse
    {
        $document->update([
            ...$request->validated(),
            'body' => $request->string('body')->toString(),
        ]);

        return to_route('document.show', $document)->with('success', 'Dokument upraven');
    }

    public function destroy(Document $document): RedirectResponse
    {
        $document->delete();

        return to_route('document.index')->with('success', 'Dokument smazan');
    }
}
