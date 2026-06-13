<?php

namespace Database\Factories;

use App\Models\Document;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Document>
 */
class DocumentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'author_id' => User::factory(),
            'title' => fake()->sentence(3),
            'body' => implode("\n\n", [
                '## '.fake()->words(2, true),
                fake()->paragraph(),
                '| Kdy | Co | Poznamka |',
                '| --- | --- | --- |',
                '| 2026-03-12 | '.fake()->words(3, true).' | '.fake()->words(4, true).' |',
            ]),
        ];
    }
}
