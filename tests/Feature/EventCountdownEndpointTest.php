<?php

use App\Models\Event;
use App\Models\User;

it('lists only active countdown events assigned to the authenticated user', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    $later = Event::factory()->for($user, 'author')->create([
        'title' => 'Later',
        'countdown_enabled' => true,
        'start_at' => now()->addDays(2),
    ]);
    $sooner = Event::factory()->for($user, 'author')->create([
        'title' => 'Sooner',
        'countdown_enabled' => true,
        'start_at' => now()->addDay(),
    ]);
    $earlierToday = Event::factory()->for($user, 'author')->create([
        'countdown_enabled' => true,
        'start_at' => now()->startOfDay(),
    ]);
    $yesterday = Event::factory()->for($user, 'author')->create([
        'countdown_enabled' => true,
        'start_at' => now()->subDay(),
    ]);
    $deleted = Event::factory()->for($user, 'author')->create([
        'countdown_enabled' => true,
        'start_at' => now()->addDays(3),
    ]);
    $unassigned = Event::factory()->for($otherUser, 'author')->create([
        'countdown_enabled' => true,
        'start_at' => now()->addHour(),
    ]);

    foreach ([$later, $sooner, $earlierToday, $yesterday, $deleted] as $event) {
        $event->subscribers()->attach($user);
    }
    $unassigned->subscribers()->attach($otherUser);
    $deleted->delete();

    $this->actingAs($user)
        ->getJson(route('event.countdowns'))
        ->assertOk()
        ->assertJsonCount(3, 'data')
        ->assertJsonPath('data.0.id', $earlierToday->id)
        ->assertJsonPath('data.1.id', $sooner->id)
        ->assertJsonPath('data.2.id', $later->id)
        ->assertJsonPath('data.0.countdown_enabled', true)
        ->assertJsonPath('data.0.countdown.label', 'dnes')
        ->assertJsonMissing(['id' => $yesterday->id])
        ->assertJsonMissing(['id' => $deleted->id])
        ->assertJsonMissing(['id' => $unassigned->id]);
});

it('requires authentication for the countdown endpoint', function () {
    $this->getJson(route('event.countdowns'))->assertUnauthorized();
});

it('stores and updates the countdown flag', function () {
    $user = User::factory()->create();
    $date = now()->addDay()->format('Y-m-d');

    $this->actingAs($user)->post(route('event.store'), [
        'title' => 'Countdown event',
        'date' => $date,
        'start_at' => '10:00',
        'is_all_day' => false,
        'is_private' => true,
        'countdown_enabled' => true,
        'capybara' => 'blue',
    ])->assertRedirect();

    $event = Event::query()->where('title', 'Countdown event')->firstOrFail();

    expect($event->countdown_enabled)->toBeTrue();

    $this->actingAs($user)->put(route('event.update', $event), [
        'title' => $event->title,
        'date' => $date,
        'start_at' => '10:00',
        'is_all_day' => false,
        'is_private' => true,
        'countdown_enabled' => false,
        'capybara' => 'blue',
    ])->assertRedirect();

    expect($event->fresh()->countdown_enabled)->toBeFalse();
});
