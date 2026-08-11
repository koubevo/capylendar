<?php

use App\Models\Event;
use App\ValueObjects\EventCountdown;
use Carbon\CarbonImmutable;

beforeEach(function () {
    config()->set('app.timezone', 'Europe/Prague');
    date_default_timezone_set('Europe/Prague');
});

it('counts calendar days instead of elapsed twenty four hour periods', function (string $now, string $start, int $days, string $label) {
    $event = Event::factory()->make([
        'countdown_enabled' => true,
        'start_at' => CarbonImmutable::parse($start, 'Europe/Prague'),
        'is_all_day' => false,
    ]);

    $countdown = EventCountdown::forEvent(
        $event,
        CarbonImmutable::parse($now, 'Europe/Prague'),
    );

    expect($countdown)
        ->not->toBeNull()
        ->days->toBe($days)
        ->label->toBe($label);
})->with([
    'tomorrow with less than twenty four hours left' => [
        '2026-08-11 23:30',
        '2026-08-12 22:00',
        1,
        "z\u{00ED}tra",
    ],
    'same day stays at day precision' => [
        '2026-08-11 09:55',
        '2026-08-11 10:00',
        0,
        'dnes',
    ],
    'across daylight saving time' => [
        '2026-03-28 23:30',
        '2026-03-30 08:00',
        2,
        "za 2 dn\u{00ED}",
    ],
    'across the end of year' => [
        '2026-12-31 23:30',
        '2027-01-01 08:00',
        1,
        "z\u{00ED}tra",
    ],
]);

it('keeps an all-day countdown active for the whole event day', function () {
    $event = Event::factory()->make([
        'countdown_enabled' => true,
        'start_at' => CarbonImmutable::parse('2026-08-11 00:00:00', 'Europe/Prague'),
        'is_all_day' => true,
    ]);

    $countdown = EventCountdown::forEvent(
        $event,
        CarbonImmutable::parse('2026-08-11 18:00', 'Europe/Prague'),
    );

    expect($countdown)->not->toBeNull()->label->toBe('dnes');
});

it('keeps a timed countdown active for the whole event day', function () {
    $event = Event::factory()->make([
        'countdown_enabled' => true,
        'start_at' => CarbonImmutable::parse('2026-08-11 10:00:00', 'Europe/Prague'),
        'is_all_day' => false,
    ]);

    $countdown = EventCountdown::forEvent(
        $event,
        CarbonImmutable::parse('2026-08-11 18:00', 'Europe/Prague'),
    );

    expect($countdown)->not->toBeNull()->label->toBe('dnes');
});

it('hides a countdown after its calendar day', function () {
    $event = Event::factory()->make([
        'countdown_enabled' => true,
        'start_at' => CarbonImmutable::parse('2026-08-11 10:00:00', 'Europe/Prague'),
        'is_all_day' => false,
    ]);

    expect(EventCountdown::forEvent(
        $event,
        CarbonImmutable::parse('2026-08-12 00:00', 'Europe/Prague'),
    ))->toBeNull();
});

it('does not present a disabled countdown', function () {
    $event = Event::factory()->make([
        'countdown_enabled' => false,
        'start_at' => now()->addDay(),
    ]);

    expect(EventCountdown::forEvent($event))->toBeNull();
});
