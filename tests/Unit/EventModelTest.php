<?php

use App\Enums\Capybara;
use App\Models\Event;
use App\Models\Tag;
use App\Models\User;
use Carbon\Carbon;

describe('Event Model', function () {
    it('has correct casts', function () {
        $event = Event::factory()->create([
            'start_at' => now(),
            'end_at' => now()->addHour(),
            'capybara' => 'pink',
            'meta' => ['key' => 'value'],
        ]);

        expect($event->start_at)->toBeInstanceOf(Carbon::class);
        expect($event->end_at)->toBeInstanceOf(Carbon::class);
        expect($event->capybara)->toBeInstanceOf(Capybara::class);
        expect($event->meta)->toBeArray();
    });

    it('sets updated_at to null on creation', function () {
        $event = Event::factory()->create();

        expect($event->updated_at)->toBeNull();
    });

    it('updates updated_at on update', function () {
        $event = Event::factory()->create();

        // Wait a second to ensure timestamp difference if it was not null
        $event->update(['title' => 'New Title']);

        expect($event->updated_at)->not->toBeNull();
        expect($event->updated_at)->toBeInstanceOf(Carbon::class);
    });

    it('determines is_private correctly based on subscribers count', function () {
        $user = User::factory()->create();
        $event = Event::factory()->create(['author_id' => $user->id]);

        // Initially 0 subscribers (factory doesn't attach unless specified or logic in service)
        // But let's attach just author
        $event->subscribers()->attach($user);

        expect($event->is_private)->toBeTrue();

        // Attach another user
        $otherUser = User::factory()->create();
        $event->subscribers()->attach($otherUser);

        // Refresh model to clear cache/loaded relations
        $event->refresh();

        expect($event->is_private)->toBeFalse();
    });

    it('calculates created_at_human', function () {
        $event = Event::factory()->create();

        expect($event->created_at_human)->toBeString();
        // Just verify it returns something meaningful from diffForHumans
        expect($event->created_at_human)->not->toBeEmpty();
    });

    it('belongs to an author', function () {
        $user = User::factory()->create();
        $event = Event::factory()->create(['author_id' => $user->id]);

        expect($event->author)->toBeInstanceOf(User::class);
        expect($event->author->id)->toBe($user->id);
    });

    it('has many subscribers', function () {
        $event = Event::factory()->create();
        $users = User::factory()->count(2)->create();
        $event->subscribers()->attach($users);

        expect($event->subscribers)->toHaveCount(2);
        expect($event->subscribers->first())->toBeInstanceOf(User::class);
    });

    it('has many tags', function () {
        $event = Event::factory()->create();
        $tags = Tag::factory()->count(2)->create();
        $event->tags()->attach($tags);

        expect($event->tags)->toHaveCount(2);
        expect($event->tags->first())->toBeInstanceOf(Tag::class);
    });
});
