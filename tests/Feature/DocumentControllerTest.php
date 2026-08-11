<?php

use App\Models\Document;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('DocumentController index', function () {
    it('renders documents for authenticated users', function () {
        $user = User::factory()->create();
        Document::factory()->count(2)->create();

        $this->actingAs($user)
            ->get(route('document.index'))
            ->assertOk()
            ->assertInertia(
                fn ($page) => $page->component('documents/DocumentIndex')
                    ->has('documents', 2)
            );
    });

    it('requires authentication', function () {
        $this->get(route('document.index'))
            ->assertRedirect(route('login'));
    });
});

describe('DocumentController store', function () {
    it('renders the create page', function () {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('document.create'))
            ->assertOk()
            ->assertInertia(
                fn ($page) => $page->component('documents/DocumentCreate')
            );
    });

    it('stores a document for the current user', function () {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('document.store'), [
                'title' => 'Vozovy park',
                'body' => "| Auto | SPZ |\n| --- | --- |\n| Mazda | 1A2 3456 |",
            ])
            ->assertRedirect(route('document.show', Document::query()->latest('id')->firstOrFail()));

        $this->assertDatabaseHas('documents', [
            'author_id' => $user->id,
            'title' => 'Vozovy park',
            'body' => "| Auto | SPZ |\n| --- | --- |\n| Mazda | 1A2 3456 |",
        ]);
    });

    it('stores an empty body as an empty string', function () {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('document.store'), [
                'title' => 'Kontakty',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('documents', [
            'title' => 'Kontakty',
            'body' => '',
        ]);
    });

    it('validates the title', function () {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('document.store'), [
                'body' => 'Bez nazvu',
            ])
            ->assertSessionHasErrors('title');
    });

    it('limits body length', function () {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('document.store'), [
                'title' => 'Large document',
                'body' => str_repeat('a', 100001),
            ])
            ->assertSessionHasErrors('body');

        $this->assertDatabaseMissing('documents', ['title' => 'Large document']);
    });
});

describe('DocumentController authentication', function () {
    it('redirects guests from the create page', function () {
        $this->get(route('document.create'))
            ->assertRedirect(route('login'));
    });

    it('redirects guests from storing a document', function () {
        $this->post(route('document.store'), [
            'title' => 'Vozovy park',
            'body' => 'Servisni poznamky',
        ])->assertRedirect(route('login'));
    });

    it('redirects guests from viewing a document', function () {
        $document = Document::factory()->create();

        $this->get(route('document.show', $document))
            ->assertRedirect(route('login'));
    });

    it('redirects guests from the edit page', function () {
        $document = Document::factory()->create();

        $this->get(route('document.edit', $document))
            ->assertRedirect(route('login'));
    });

    it('redirects guests from updating a document', function () {
        $document = Document::factory()->create();

        $this->put(route('document.update', $document), [
            'title' => 'Vozovy park',
            'body' => 'Servisni poznamky',
        ])->assertRedirect(route('login'));
    });

    it('redirects guests from deleting a document', function () {
        $document = Document::factory()->create();

        $this->delete(route('document.destroy', $document))
            ->assertRedirect(route('login'));
    });
});

describe('DocumentController shared access', function () {
    it('renders the edit page for a document created by another user', function () {
        $user = User::factory()->create();
        $document = Document::factory()->create();

        $this->actingAs($user)
            ->get(route('document.edit', $document))
            ->assertOk()
            ->assertInertia(
                fn ($page) => $page->component('documents/DocumentEdit')
                    ->where('document.id', $document->id)
            );
    });

    it('shows a document created by another user', function () {
        $user = User::factory()->create();
        $document = Document::factory()->create();

        $this->actingAs($user)
            ->get(route('document.show', $document))
            ->assertOk()
            ->assertInertia(
                fn ($page) => $page->component('documents/DocumentShow')
                    ->where('document.id', $document->id)
            );
    });

    it('updates a document created by another user', function () {
        $user = User::factory()->create();
        $document = Document::factory()->create([
            'title' => 'Old title',
        ]);

        $this->actingAs($user)
            ->put(route('document.update', $document), [
                'title' => 'New title',
                'body' => 'Updated body',
            ])
            ->assertRedirect(route('document.show', $document))
            ->assertSessionHas('success', 'Dokument upraven');

        $this->assertDatabaseHas('documents', [
            'id' => $document->id,
            'title' => 'New title',
            'body' => 'Updated body',
        ]);
    });

    it('deletes a document created by another user', function () {
        $user = User::factory()->create();
        $document = Document::factory()->create();

        $this->actingAs($user)
            ->delete(route('document.destroy', $document))
            ->assertRedirect(route('document.index'));

        $this->assertDatabaseMissing('documents', [
            'id' => $document->id,
        ]);
    });
});
