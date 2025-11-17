<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Models\EventUser;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::factory()->create();

        User::factory()->pink()->create();

        Event::factory(5)->create();

        Event::factory(5)->allDay()->create();

        Event::factory(5)->pink()->create();

        Event::factory(5)->pink()->allDay()->create();

        EventUser::factory(10)->create();
    }
}
