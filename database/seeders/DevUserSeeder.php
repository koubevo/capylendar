<?php

namespace Database\Seeders;

use App\Enums\Capybara;
use App\Models\User;
use Illuminate\Database\Seeder;

class DevUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::query()->firstOrNew([
            'email' => 'vojta@example.com',
        ]);

        $user->forceFill([
            'name' => 'Vojta',
            'password' => 'password',
            'email_verified_at' => now(),
            'capybara' => Capybara::Blue,
            'notifications_enabled' => false,
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ])->save();
    }
}
