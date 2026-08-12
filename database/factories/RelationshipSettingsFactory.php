<?php

namespace Database\Factories;

use App\Models\RelationshipSettings;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<RelationshipSettings> */
class RelationshipSettingsFactory extends Factory
{
    public function definition(): array
    {
        return [
            'id' => RelationshipSettings::SINGLETON_ID,
            'started_on' => fake()->dateTimeBetween('-5 years', '-1 day'),
            'name' => fake()->optional()->words(2, true),
            'notifications_enabled' => true,
            'created_by' => User::factory(),
            'updated_by' => User::factory(),
        ];
    }
}
