<?php

namespace Database\Seeders;

use App\Enums\Capybara;
use App\Enums\Priority;
use App\Models\Event;
use App\Models\Todo;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;

class EventAndTodoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::query()->get();

        if ($users->isEmpty()) {
            $users = User::factory()->count(2)->create();
        }

        $today = CarbonImmutable::today();
        $fiveMonthsFromToday = $today->addMonths(5)->endOfDay();

        Event::factory()->count(50)
            ->state(function () use ($users, $today, $fiveMonthsFromToday): array {
                $author = $users->random();
                $startsAt = CarbonImmutable::instance(fake()->dateTimeBetween($today, $fiveMonthsFromToday));
                $isAllDay = fake()->boolean(20);

                return [
                    'author_id' => $author->id,
                    'capybara' => fake()->randomElement(Capybara::cases()),
                    'start_at' => $startsAt,
                    'end_at' => $isAllDay ? null : $startsAt->addMinutes(fake()->numberBetween(30, 360)),
                    'is_all_day' => $isAllDay,
                ];
            })
            ->create()
            ->each(function (Event $event): void {
                $event->subscribers()->attach($event->author_id);
            });

        Todo::factory()->count(50)
            ->state(function () use ($users, $today, $fiveMonthsFromToday): array {
                $author = $users->random();

                return [
                    'author_id' => $author->id,
                    'capybara' => fake()->randomElement(Capybara::cases()),
                    'deadline' => fake()->dateTimeBetween($today, $fiveMonthsFromToday),
                    'priority' => fake()->randomElement(Priority::cases()),
                ];
            })
            ->create()
            ->each(function (Todo $todo): void {
                $todo->subscribers()->attach($todo->author_id);
            });
    }
}
