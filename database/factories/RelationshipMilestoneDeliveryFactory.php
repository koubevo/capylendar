<?php

namespace Database\Factories;

use App\Models\RelationshipMilestoneDelivery;
use App\Models\RelationshipSettings;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<RelationshipMilestoneDelivery> */
class RelationshipMilestoneDeliveryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'relationship_settings_id' => RelationshipSettings::factory(),
            'user_id' => User::factory(),
            'milestone_key' => fake()->uuid(),
            'milestone_on' => fake()->date(),
            'delivered_at' => now(),
        ];
    }
}
