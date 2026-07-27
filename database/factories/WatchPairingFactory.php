<?php

namespace Database\Factories;

use App\Models\WatchPairing;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<WatchPairing>
 */
class WatchPairingFactory extends Factory
{
    public function definition(): array
    {
        return [
            'device_name' => 'Pixel Watch',
            'device_code_hash' => hash('sha256', Str::random(64)),
            'user_code_hash' => hash('sha256', Str::random(16)),
            'expires_at' => now()->addMinutes(10),
        ];
    }
}
