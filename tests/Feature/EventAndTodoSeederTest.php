<?php

use App\Models\Event;
use App\Models\Todo;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\EventAndTodoSeeder;

afterEach(function () {
    Carbon::setTestNow();
});

it('seeds fifty events and todos for the next five months', function () {
    Carbon::setTestNow('2026-07-27 10:00:00');
    User::factory()->count(2)->create();

    $this->seed(EventAndTodoSeeder::class);

    $today = today();
    $fiveMonthsFromToday = today()->addMonths(5)->endOfDay();

    expect(Event::query()->count())->toBe(50)
        ->and(Todo::query()->count())->toBe(50)
        ->and(Event::query()->whereBetween('start_at', [$today, $fiveMonthsFromToday])->count())->toBe(50)
        ->and(Todo::query()->whereBetween('deadline', [$today, $fiveMonthsFromToday])->count())->toBe(50)
        ->and(Event::query()->whereNotNull('end_at')->whereColumn('end_at', '<=', 'start_at')->doesntExist())->toBeTrue()
        ->and(Event::query()->whereDoesntHave('subscribers')->doesntExist())->toBeTrue()
        ->and(Todo::query()->whereDoesntHave('subscribers')->doesntExist())->toBeTrue();
});
