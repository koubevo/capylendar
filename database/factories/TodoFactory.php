<?php

namespace Database\Factories;

use App\Enums\Capybara;
use App\Enums\Priority;
use App\Models\Todo;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Todo>
 */
class TodoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => $this->faker->word(),
            'author_id' => User::exists() ? User::inRandomOrder()->first()->id : User::factory(),
            'capybara' => Capybara::Blue,
            'deadline' => $this->faker->dateTimeBetween('now', '+1 year'),
            'description' => $this->faker->text(),
            'priority' => Priority::Medium,
        ];
    }

    public function finished(): static
    {
        return $this->state(fn (array $attributes) => [
            'finished_at' => now(),
        ]);
    }

    public function highPriority(): static
    {
        return $this->state(fn (array $attributes) => [
            'priority' => Priority::High,
        ]);
    }

    public function pink(): static
    {
        return $this->state(fn (array $attributes) => [
            'capybara' => Capybara::Pink,
        ]);
    }
}
