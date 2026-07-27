<?php

use App\Models\Event;
use App\Models\User;
use App\Models\WatchDevice;
use Illuminate\Support\Str;

describe('watch event API', function () {
    it('requires a valid watch token', function () {
        $this->getJson(route('watch.events.index'))->assertUnauthorized();
    });

    it('lists upcoming events assigned to the paired user', function () {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $plainTextToken = 'capy_watch_'.Str::random(64);

        WatchDevice::factory()->for($user)->create([
            'token_hash' => hash('sha256', $plainTextToken),
        ]);

        $assignedEvent = Event::factory()->for($user, 'author')->create([
            'title' => 'Večeře',
            'start_at' => now()->addHour(),
            'end_at' => null,
            'is_all_day' => false,
        ]);
        $ongoingEvent = Event::factory()->for($user, 'author')->create([
            'title' => 'Overnight event',
            'start_at' => now()->subDay(),
            'end_at' => now()->addHour(),
            'is_all_day' => false,
        ]);
        $ongoingEvent->subscribers()->attach($user);

        $assignedEvent->subscribers()->attach($user);

        $pastEvent = Event::factory()->for($user, 'author')->create([
            'end_at' => now()->subHours(20),
            'start_at' => now()->subDay(),
        ]);
        $pastEvent->subscribers()->attach($user);

        $otherEvent = Event::factory()->for($otherUser, 'author')->create([
            'start_at' => now()->addDay(),
        ]);
        $otherEvent->subscribers()->attach($otherUser);

        $this->withToken($plainTextToken)
            ->getJson(route('watch.events.index'))
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.id', $ongoingEvent->id)
            ->assertJsonPath('data.1.id', $assignedEvent->id)
            ->assertJsonPath('data.1.title', 'Večeře')
            ->assertJsonPath('data.1.date.start_time', $assignedEvent->start_at->format('H:i'))
            ->assertJsonPath('data.1.date.end_time', '')
            ->assertJsonMissing(['id' => $pastEvent->id])
            ->assertJsonMissing(['id' => $otherEvent->id]);
    });

    it('limits the response to twenty events', function () {
        $user = User::factory()->create();
        $plainTextToken = 'capy_watch_'.Str::random(64);

        WatchDevice::factory()->for($user)->create([
            'token_hash' => hash('sha256', $plainTextToken),
        ]);

        Event::factory()
            ->count(21)
            ->for($user, 'author')
            ->sequence(fn ($sequence): array => [
                'start_at' => now()->addDays($sequence->index + 1),
            ])
            ->create()
            ->each(fn (Event $event) => $event->subscribers()->attach($user));

        $this->withToken($plainTextToken)
            ->getJson(route('watch.events.index'))
            ->assertOk()
            ->assertJsonCount(20, 'data');
    });
});
