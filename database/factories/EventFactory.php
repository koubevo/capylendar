<?php

namespace Database\Factories;

use App\Enums\Capybara;
use App\Models\Model;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Model>
 */
class EventFactory extends Factory
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
            'start_at' => $this->faker->dateTimeBetween('2025-01-01', '2025-12-31'),
            'end_at' => $this->faker->dateTimeBetween('2025-01-01', '2025-12-31'),
            'is_all_day' => false,
            'description' => $this->faker->text(),
            'icon' => $this->faker->word(),
        ];
    }

    public function allDay(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_all_day' => true,
            'end_at' => null,
        ]);
    }

    public function pink(): static
    {
        return $this->state(fn (array $attributes) => [
            'capybara' => Capybara::Pink,
        ]);
    }
}
