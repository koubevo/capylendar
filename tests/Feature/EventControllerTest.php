<?php

use App\Models\Event;
use App\Models\Tag;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->event = Event::factory()->create(['author_id' => $this->user->id]);
    $this->event->subscribers()->attach($this->user);
});

describe('EventController create', function () {
    it('renders the event create page', function () {
        $this->actingAs($this->user)
            ->get(route('event.create'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('events/EventCreate')
                ->has('capybaraOptions')
                ->has('availableTags')
            );
    });

    it('renders event create with duplicate event data', function () {
        $this->actingAs($this->user)
            ->get(route('event.create', ['duplicate_event_id' => $this->event->id]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('events/EventCreate')
                ->has('event')
                ->where('event.title', $this->event->title)
            );
    });

    it('returns 404 when duplicating non-existent event', function () {
        $this->actingAs($this->user)
            ->get(route('event.create', ['duplicate_event_id' => 99999]))
            ->assertNotFound();
    });

    it('requires authentication to access create page', function () {
        $this->get(route('event.create'))
            ->assertRedirect(route('login'));
    });
});

describe('EventController store', function () {
    it('stores a new event successfully', function () {
        $tomorrow = now()->addDay()->format('Y-m-d');

        $this->actingAs($this->user)
            ->post(route('event.store'), [
                'title' => 'New Event',
                'date' => $tomorrow,
                'start_at' => '10:00',
                'is_all_day' => false,
                'capybara' => 'blue',
                'is_private' => false,
            ])
            ->assertRedirect('dashboard?scrollToDate='.$tomorrow)
            ->assertSessionHas('success');

        $this->assertDatabaseHas('events', [
            'title' => 'New Event',
            'capybara' => 'blue',
        ]);
    });

    it('stores an all-day event', function () {
        $tomorrow = now()->addDay()->format('Y-m-d');

        $this->actingAs($this->user)
            ->post(route('event.store'), [
                'title' => 'All Day Event',
                'date' => $tomorrow,
                'start_at' => '',
                'is_all_day' => true,
                'capybara' => 'pink',
                'is_private' => false,
            ])
            ->assertRedirect('dashboard?scrollToDate='.$tomorrow);

        $this->assertDatabaseHas('events', [
            'title' => 'All Day Event',
            'is_all_day' => true,
        ]);
    });

    it('stores a private event for only the author', function () {
        $otherUser = User::factory()->pink()->create();
        $tomorrow = now()->addDay()->format('Y-m-d');

        $this->actingAs($this->user)
            ->post(route('event.store'), [
                'title' => 'Private Event',
                'date' => $tomorrow,
                'start_at' => '14:00',
                'is_all_day' => false,
                'capybara' => 'blue',
                'is_private' => true,
            ])
            ->assertRedirect('dashboard?scrollToDate='.$tomorrow);

        $event = Event::where('title', 'Private Event')->first();
        expect($event->subscribers)->toHaveCount(1);
        expect($event->subscribers->first()->id)->toBe($this->user->id);
    });

    it('stores an event with tags', function () {
        $tag = Tag::factory()->create();
        $tomorrow = now()->addDay()->format('Y-m-d');

        $this->actingAs($this->user)
            ->post(route('event.store'), [
                'title' => 'Tagged Event',
                'date' => $tomorrow,
                'start_at' => '10:00',
                'is_all_day' => false,
                'capybara' => 'blue',
                'is_private' => false,
                'tags' => [$tag->id],
            ])
            ->assertRedirect('dashboard?scrollToDate='.$tomorrow);

        $event = Event::where('title', 'Tagged Event')->first();
        expect($event->tags)->toHaveCount(1);
        expect($event->tags->first()->id)->toBe($tag->id);
    });

    it('requires authentication to store event', function () {
        $this->post(route('event.store'), [])
            ->assertRedirect(route('login'));
    });
});

describe('EventController store validation', function () {
    it('requires title', function () {
        $this->actingAs($this->user)
            ->post(route('event.store'), [
                'date' => now()->addDay()->format('Y-m-d'),
                'start_at' => '10:00',
                'is_all_day' => false,
                'capybara' => 'blue',
            ])
            ->assertSessionHasErrors('title');
    });

    it('requires date', function () {
        $this->actingAs($this->user)
            ->post(route('event.store'), [
                'title' => 'Event',
                'start_at' => '10:00',
                'is_all_day' => false,
                'capybara' => 'blue',
            ])
            ->assertSessionHasErrors('start_at');
    });

    it('requires valid capybara value', function () {
        $this->actingAs($this->user)
            ->post(route('event.store'), [
                'title' => 'Event',
                'date' => now()->addDay()->format('Y-m-d'),
                'start_at' => '10:00',
                'is_all_day' => false,
                'capybara' => 'invalid',
            ])
            ->assertSessionHasErrors('capybara');
    });

    it('validates title max length', function () {
        $this->actingAs($this->user)
            ->post(route('event.store'), [
                'title' => str_repeat('a', 256),
                'date' => now()->addDay()->format('Y-m-d'),
                'start_at' => '10:00',
                'is_all_day' => false,
                'capybara' => 'blue',
            ])
            ->assertSessionHasErrors('title');
    });

    it('validates end_at is after start_at', function () {
        $this->actingAs($this->user)
            ->post(route('event.store'), [
                'title' => 'Event',
                'date' => now()->addDay()->format('Y-m-d'),
                'start_at' => '14:00',
                'end_at' => '10:00',
                'is_all_day' => false,
                'capybara' => 'blue',
            ])
            ->assertSessionHasErrors('end_at');
    });

    it('validates tags exist in database', function () {
        $this->actingAs($this->user)
            ->post(route('event.store'), [
                'title' => 'Event',
                'date' => now()->addDay()->format('Y-m-d'),
                'start_at' => '10:00',
                'is_all_day' => false,
                'capybara' => 'blue',
                'tags' => [99999],
            ])
            ->assertSessionHasErrors('tags.0');
    });
});

describe('EventController show', function () {
    it('shows event detail page for subscriber', function () {
        $this->actingAs($this->user)
            ->get(route('event.show', $this->event))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('events/EventShow')
                ->has('event')
                ->where('event.title', $this->event->title)
            );
    });
});

describe('EventController edit', function () {
    it('renders edit form for subscriber', function () {
        $this->actingAs($this->user)
            ->get(route('event.edit', $this->event))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('events/EventEdit')
                ->has('event')
                ->has('capybaraOptions')
                ->has('availableTags')
            );
    });
});

describe('EventController update', function () {
    it('updates an event successfully', function () {
        $this->actingAs($this->user)
            ->put(route('event.update', $this->event), [
                'title' => 'Updated Title',
                'date' => now()->addDay()->format('Y-m-d'),
                'start_at' => '15:00',
                'is_all_day' => false,
                'capybara' => 'yellow',
            ])
            ->assertRedirect(route('event.show', $this->event))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('events', [
            'id' => $this->event->id,
            'title' => 'Updated Title',
            'capybara' => 'yellow',
        ]);
    });

    it('updates event tags', function () {
        $tag = Tag::factory()->create();

        $this->actingAs($this->user)
            ->put(route('event.update', $this->event), [
                'title' => 'Event with Tags',
                'date' => now()->addDay()->format('Y-m-d'),
                'start_at' => '10:00',
                'is_all_day' => false,
                'capybara' => 'blue',
                'tags' => [$tag->id],
            ])
            ->assertRedirect();

        $this->event->refresh();
        expect($this->event->tags)->toHaveCount(1);
    });

    it('removes tags when updating with empty tags', function () {
        $tag = Tag::factory()->create();
        $this->event->tags()->attach($tag);

        $this->actingAs($this->user)
            ->put(route('event.update', $this->event), [
                'title' => 'No Tags Event',
                'date' => now()->addDay()->format('Y-m-d'),
                'start_at' => '10:00',
                'is_all_day' => false,
                'capybara' => 'blue',
                'tags' => [],
            ])
            ->assertRedirect();

        $this->event->refresh();
        expect($this->event->tags)->toHaveCount(0);
    });
});

describe('EventController destroy', function () {
    it('soft deletes an event', function () {
        $eventId = $this->event->id;

        $this->actingAs($this->user)
            ->delete(route('event.destroy', $this->event))
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('success');

        $this->assertSoftDeleted('events', ['id' => $eventId]);
    });
});

describe('EventController deletedIndex', function () {
    it('shows deleted events index page', function () {
        $this->event->delete();

        $this->actingAs($this->user)
            ->get(route('event.deletedIndex'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('events/EventDeletedIndex')
                ->has('deletedEvents', 1)
            );
    });

    it('shows empty page when no deleted events', function () {
        $this->actingAs($this->user)
            ->get(route('event.deletedIndex'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('events/EventDeletedIndex')
                ->has('deletedEvents', 0)
            );
    });

    it('requires authentication', function () {
        $this->get(route('event.deletedIndex'))
            ->assertRedirect(route('login'));
    });
});

describe('EventController restore', function () {
    it('restores a deleted event', function () {
        $this->event->delete();

        $this->actingAs($this->user)
            ->post(route('event.restore', $this->event))
            ->assertRedirect(route('event.deletedIndex'))
            ->assertSessionHas('success');

        expect($this->event->fresh()->trashed())->toBeFalse();
    });
});
