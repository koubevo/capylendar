<?php

namespace Database\Factories;

use App\Enums\ReactionType;
use App\Models\Event;
use App\Models\Reaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Reaction>
 */
class ReactionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'reactable_type' => (new Event)->getMorphClass(),
            'reactable_id' => Event::factory(),
            'key' => fake()->randomElement(ReactionType::cases()),
        ];
    }
}
