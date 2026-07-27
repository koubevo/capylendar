<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\WatchDevice;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<WatchDevice>
 */
class WatchDeviceFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => 'Pixel Watch',
            'token_hash' => hash('sha256', 'capy_watch_'.Str::random(64)),
        ];
    }
}
