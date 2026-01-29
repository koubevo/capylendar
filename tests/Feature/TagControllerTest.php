<?php

use App\Models\Tag;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->user = User::factory()->create();
});

describe('TagController index', function () {
    it('renders the tags index page', function () {
        Tag::factory()->count(3)->create();

        $this->actingAs($this->user)
            ->get(route('tags.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('settings/Tags')
                ->has('tags', 3)
            );
    });

    it('orders tags by created_at desc', function () {
        // Create tags with explicit timestamps to ensure ordering
        $oldTag = Tag::factory()->create([
            'label' => 'OldTag',
            'created_at' => now()->subHour(),
        ]);
        $newTag = Tag::factory()->create([
            'label' => 'NewTag',
            'created_at' => now(),
        ]);

        $this->actingAs($this->user)
            ->get(route('tags.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('settings/Tags')
                ->where('tags.0.label', 'NewTag')
                ->where('tags.1.label', 'OldTag')
            );
    });

    it('requires authentication', function () {
        $this->get(route('tags.index'))
            ->assertRedirect(route('login'));
    });
});

describe('TagController store', function () {
    it('creates a new tag', function () {
        $this->actingAs($this->user)
            ->post(route('tags.store'), [
                'label' => 'New Tag',
                'color' => '#ff5733',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('tags', [
            'label' => 'New Tag',
            'color' => '#ff5733',
        ]);
    });

    it('redirects back after creating a tag', function () {
        $this->actingAs($this->user)
            ->from(route('tags.index'))
            ->post(route('tags.store'), [
                'label' => 'Another Tag',
                'color' => '#00ff00',
            ])
            ->assertRedirect(route('tags.index'));
    });

    it('requires authentication to store a tag', function () {
        $this->post(route('tags.store'), [
            'label' => 'Tag',
            'color' => '#000000',
        ])->assertRedirect(route('login'));
    });
});

describe('TagController store validation', function () {
    it('requires label', function () {
        $this->actingAs($this->user)
            ->post(route('tags.store'), [
                'color' => '#ff5733',
            ])
            ->assertSessionHasErrors('label');
    });

    it('requires color', function () {
        $this->actingAs($this->user)
            ->post(route('tags.store'), [
                'label' => 'Tag',
            ])
            ->assertSessionHasErrors('color');
    });

    it('validates label max length', function () {
        $this->actingAs($this->user)
            ->post(route('tags.store'), [
                'label' => str_repeat('a', 21),
                'color' => '#ff5733',
            ])
            ->assertSessionHasErrors('label');
    });

    it('validates label uniqueness', function () {
        Tag::factory()->create(['label' => 'ExistingTag']);

        $this->actingAs($this->user)
            ->post(route('tags.store'), [
                'label' => 'ExistingTag',
                'color' => '#ff5733',
            ])
            ->assertSessionHasErrors('label');
    });

    it('validates color is hex format', function () {
        $this->actingAs($this->user)
            ->post(route('tags.store'), [
                'label' => 'Tag',
                'color' => 'not-a-hex-color',
            ])
            ->assertSessionHasErrors('color');
    });

    it('accepts valid hex color formats', function () {
        $this->actingAs($this->user)
            ->post(route('tags.store'), [
                'label' => 'ShortHex',
                'color' => '#abc',
            ])
            ->assertSessionDoesntHaveErrors('color');
    });
});

describe('TagController destroy', function () {
    it('deletes a tag', function () {
        $tag = Tag::factory()->create();

        $this->actingAs($this->user)
            ->delete(route('tags.destroy', $tag))
            ->assertRedirect(route('tags.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('tags', ['id' => $tag->id]);
    });

    it('returns 404 for non-existent tag', function () {
        $this->actingAs($this->user)
            ->delete(route('tags.destroy', 99999))
            ->assertNotFound();
    });

    it('requires authentication to delete a tag', function () {
        $tag = Tag::factory()->create();

        $this->delete(route('tags.destroy', $tag))
            ->assertRedirect(route('login'));
    });
});
