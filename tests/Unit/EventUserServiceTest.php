<?php

use App\Models\Event;
use App\Models\User;
use App\services\EventUserService;

beforeEach(function () {
    $this->eventUserService = new EventUserService;
    $this->author = User::factory()->create();
    $this->event = Event::factory()->create(['author_id' => $this->author->id]);
});

describe('EventUserService assignSubscribers', function () {
    it('assigns only author as subscriber when event is private', function () {
        // Create other users
        User::factory()->count(3)->create();

        $this->eventUserService->assignSubscribers(
            $this->event,
            true, // isPrivate
            $this->author
        );

        $this->event->refresh();
        expect($this->event->subscribers)->toHaveCount(1);
        expect($this->event->subscribers->first()->id)->toBe($this->author->id);
    });

    it('assigns all users as subscribers when event is public', function () {
        // Create other users
        $otherUsers = User::factory()->count(3)->create();

        $this->eventUserService->assignSubscribers(
            $this->event,
            false, // isPublic
            $this->author
        );

        $this->event->refresh();
        // Author + 3 other users = 4
        expect($this->event->subscribers)->toHaveCount(4);
        foreach ($otherUsers as $user) {
            expect($this->event->subscribers->contains($user))->toBeTrue();
        }
        expect($this->event->subscribers->contains($this->author))->toBeTrue();
    });

    it('updates subscribers when switching from private to public', function () {
        // Start as private
        User::factory()->count(3)->create();
        $this->event->subscribers()->attach($this->author);

        // Switch to public
        $this->eventUserService->assignSubscribers(
            $this->event,
            false,
            $this->author
        );

        $this->event->refresh();
        expect($this->event->subscribers->count())->toBeGreaterThan(1);
    });

    it('updates subscribers when switching from public to private', function () {
        // Start as public
        User::factory()->count(3)->create();
        $this->event->subscribers()->attach(User::all());

        // Switch to private
        $this->eventUserService->assignSubscribers(
            $this->event,
            true,
            $this->author
        );

        $this->event->refresh();
        expect($this->event->subscribers)->toHaveCount(1);
        expect($this->event->subscribers->first()->id)->toBe($this->author->id);
    });
});
