<?php

use App\Enums\Capybara;
use App\Models\Event;
use App\Models\Tag;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->user = User::factory()->create();
});

describe('DashboardController', function () {
    it('shows upcoming and history events', function () {
        // Create upcoming event
        $upcomingEvent = Event::factory()->create([
            'author_id' => $this->user->id,
            'title' => 'Upcoming Event',
            'start_at' => now()->addDays(5),
        ]);
        $upcomingEvent->subscribers()->attach($this->user);

        // Create history event
        $historyEvent = Event::factory()->create([
            'author_id' => $this->user->id,
            'title' => 'Past Event',
            'start_at' => now()->subDays(5),
        ]);
        $historyEvent->subscribers()->attach($this->user);

        $this->actingAs($this->user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard')
                ->has('upcomingEvents', 1)
                ->has('historyEvents', 1)
                ->has('capybaraOptions')
                ->has('availableTags')
            );
    });

    it('returns capybara options', function () {
        $this->actingAs($this->user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('capybaraOptions', 3)
            );
    });

    it('returns available tags', function () {
        Tag::factory()->count(2)->create();

        $this->actingAs($this->user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('availableTags', 2)
            );
    });
});

describe('DashboardController filters', function () {
    it('filters events by search query in title', function () {
        $matchingEvent = Event::factory()->create([
            'author_id' => $this->user->id,
            'title' => 'Meeting with Team',
            'start_at' => now()->addDays(1),
        ]);
        $matchingEvent->subscribers()->attach($this->user);

        $nonMatchingEvent = Event::factory()->create([
            'author_id' => $this->user->id,
            'title' => 'Lunch',
            'start_at' => now()->addDays(2),
        ]);
        $nonMatchingEvent->subscribers()->attach($this->user);

        $this->actingAs($this->user)
            ->get(route('dashboard', ['search' => 'Meeting']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('upcomingEvents', 1)
                ->where('upcomingEvents.0.title', 'Meeting with Team')
            );
    });

    it('filters events by capybara color', function () {
        $blueEvent = Event::factory()->create([
            'author_id' => $this->user->id,
            'title' => 'Blue Event',
            'capybara' => Capybara::Blue,
            'start_at' => now()->addDays(1),
        ]);
        $blueEvent->subscribers()->attach($this->user);

        $pinkEvent = Event::factory()->pink()->create([
            'author_id' => $this->user->id,
            'title' => 'Pink Event',
            'start_at' => now()->addDays(1),
        ]);
        $pinkEvent->subscribers()->attach($this->user);

        $this->actingAs($this->user)
            ->get(route('dashboard', ['capybara' => 'blue']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('upcomingEvents', 1)
                ->where('upcomingEvents.0.title', 'Blue Event')
            );
    });

    it('filters events by tags', function () {
        $tag = Tag::factory()->create();

        $taggedEvent = Event::factory()->create([
            'author_id' => $this->user->id,
            'title' => 'Tagged Event',
            'start_at' => now()->addDays(1),
        ]);
        $taggedEvent->subscribers()->attach($this->user);
        $taggedEvent->tags()->attach($tag);

        $untaggedEvent = Event::factory()->create([
            'author_id' => $this->user->id,
            'title' => 'Untagged Event',
            'start_at' => now()->addDays(2),
        ]);
        $untaggedEvent->subscribers()->attach($this->user);

        $this->actingAs($this->user)
            ->get(route('dashboard', ['tags' => [$tag->id]]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('upcomingEvents', 1)
                ->where('upcomingEvents.0.title', 'Tagged Event')
            );
    });

    it('returns filters in response', function () {
        $this->actingAs($this->user)
            ->get(route('dashboard', ['search' => 'test', 'capybara' => 'blue']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('eventFilters.search', 'test')
                ->where('eventFilters.capybara', 'blue')
            );
    });

    it('shows only events user is subscribed to', function () {
        $otherUser = User::factory()->pink()->create();

        $myEvent = Event::factory()->create([
            'author_id' => $this->user->id,
            'title' => 'My Event',
            'start_at' => now()->addDays(1),
        ]);
        $myEvent->subscribers()->attach($this->user);

        $otherEvent = Event::factory()->create([
            'author_id' => $otherUser->id,
            'title' => 'Other Event',
            'start_at' => now()->addDays(1),
        ]);
        $otherEvent->subscribers()->attach($otherUser);

        $this->actingAs($this->user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('upcomingEvents', 1)
                ->where('upcomingEvents.0.title', 'My Event')
            );
    });
});

describe('DashboardController sorting', function () {
    it('orders upcoming events by start_at ascending', function () {
        $laterEvent = Event::factory()->create([
            'author_id' => $this->user->id,
            'title' => 'Later Event',
            'start_at' => now()->addDays(10),
        ]);
        $laterEvent->subscribers()->attach($this->user);

        $soonerEvent = Event::factory()->create([
            'author_id' => $this->user->id,
            'title' => 'Sooner Event',
            'start_at' => now()->addDays(1),
        ]);
        $soonerEvent->subscribers()->attach($this->user);

        $this->actingAs($this->user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('upcomingEvents', 2)
                ->where('upcomingEvents.0.title', 'Sooner Event')
                ->where('upcomingEvents.1.title', 'Later Event')
            );
    });

    it('orders history events by start_at descending', function () {
        $olderEvent = Event::factory()->create([
            'author_id' => $this->user->id,
            'title' => 'Older Event',
            'start_at' => now()->subDays(10),
        ]);
        $olderEvent->subscribers()->attach($this->user);

        $recentEvent = Event::factory()->create([
            'author_id' => $this->user->id,
            'title' => 'Recent Event',
            'start_at' => now()->subDays(1),
        ]);
        $recentEvent->subscribers()->attach($this->user);

        $this->actingAs($this->user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('historyEvents', 2)
                ->where('historyEvents.0.title', 'Recent Event')
                ->where('historyEvents.1.title', 'Older Event')
            );
    });
});
