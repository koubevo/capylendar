<?php

use App\Enums\Capybara;
use App\Enums\Priority;
use App\Models\Event;
use App\Models\Tag;
use App\Models\Todo;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->travelTo(now()->startOfMonth()->addDays(9)->setTime(8, 0));
    $this->user = User::factory()->create();
});

describe('DashboardController', function () {
    it('shows upcoming events on dashboard', function () {
        $upcomingEvent = Event::factory()->create([
            'author_id' => $this->user->id,
            'title' => 'Upcoming Event',
            'start_at' => now()->addDays(5),
        ]);
        $upcomingEvent->subscribers()->attach($this->user);

        // Past events should NOT appear in upcomingEvents on the dashboard
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
                ->has('dashboardMonths.data.0.events', 1)
                ->has('capybaraOptions')
                ->has('availableTags')
                ->where('dashboardMonths.data.0.events.0.title', 'Upcoming Event')
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
                ->has('dashboardMonths.data.0.events', 1)
                ->where('dashboardMonths.data.0.events.0.title', 'Meeting with Team')
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
                ->has('dashboardMonths.data.0.events', 1)
                ->where('dashboardMonths.data.0.events.0.title', 'Blue Event')
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
                ->has('dashboardMonths.data.0.events', 1)
                ->where('dashboardMonths.data.0.events.0.title', 'Tagged Event')
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
                ->has('dashboardMonths.data.0.events', 1)
                ->where('dashboardMonths.data.0.events.0.title', 'My Event')
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
                ->has('dashboardMonths.data.0.events', 2)
                ->where('dashboardMonths.data.0.events.0.title', 'Sooner Event')
                ->where('dashboardMonths.data.0.events.1.title', 'Later Event')
            );
    });

    it('orders all-day events before timed events on the same date', function () {
        $tomorrow = now()->addDay();

        $timedEvent = Event::factory()->create([
            'author_id' => $this->user->id,
            'title' => 'Timed Event',
            'start_at' => $tomorrow->copy()->setTime(14, 0),
            'is_all_day' => false,
        ]);
        $timedEvent->subscribers()->attach($this->user);

        $allDayEvent = Event::factory()->allDay()->create([
            'author_id' => $this->user->id,
            'title' => 'All Day Event',
            'start_at' => $tomorrow->copy()->setTime(14, 0),
        ]);
        $allDayEvent->subscribers()->attach($this->user);

        $this->actingAs($this->user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('dashboardMonths.data.0.events', 2)
                ->where('dashboardMonths.data.0.events.0.title', 'All Day Event')
                ->where('dashboardMonths.data.0.events.1.title', 'Timed Event')
            );
    });

    it('orders events alphabetically by title within the same type', function () {
        $tomorrow = now()->addDay();

        $eventB = Event::factory()->create([
            'author_id' => $this->user->id,
            'title' => 'Bravo Event',
            'start_at' => $tomorrow->copy()->setTime(10, 0),
            'is_all_day' => false,
        ]);
        $eventB->subscribers()->attach($this->user);

        $eventA = Event::factory()->create([
            'author_id' => $this->user->id,
            'title' => 'Alpha Event',
            'start_at' => $tomorrow->copy()->setTime(10, 0),
            'is_all_day' => false,
        ]);
        $eventA->subscribers()->attach($this->user);

        $this->actingAs($this->user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('dashboardMonths.data.0.events', 2)
                ->where('dashboardMonths.data.0.events.0.title', 'Alpha Event')
                ->where('dashboardMonths.data.0.events.1.title', 'Bravo Event')
            );
    });

    it('orders unfinished todos by priority then title', function () {
        $deadline = now()->addDays(3);

        $lowTodo = Todo::factory()->create([
            'author_id' => $this->user->id,
            'title' => 'Low Todo',
            'deadline' => $deadline,
            'priority' => Priority::Low,
        ]);
        $lowTodo->subscribers()->attach($this->user);

        $highTodoB = Todo::factory()->highPriority()->create([
            'author_id' => $this->user->id,
            'title' => 'Bravo High',
            'deadline' => $deadline,
        ]);
        $highTodoB->subscribers()->attach($this->user);

        $highTodoA = Todo::factory()->highPriority()->create([
            'author_id' => $this->user->id,
            'title' => 'Alpha High',
            'deadline' => $deadline,
        ]);
        $highTodoA->subscribers()->attach($this->user);

        $mediumTodo = Todo::factory()->create([
            'author_id' => $this->user->id,
            'title' => 'Medium Todo',
            'deadline' => $deadline,
            'priority' => Priority::Medium,
        ]);
        $mediumTodo->subscribers()->attach($this->user);

        $this->actingAs($this->user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('dashboardMonths.data.0.todos', 4)
                ->where('dashboardMonths.data.0.todos.0.title', 'Alpha High')
                ->where('dashboardMonths.data.0.todos.1.title', 'Bravo High')
                ->where('dashboardMonths.data.0.todos.2.title', 'Medium Todo')
                ->where('dashboardMonths.data.0.todos.3.title', 'Low Todo')
            );
    });

    it('loads one calendar month per dashboard page', function () {
        $currentEvent = Event::factory()->create([
            'author_id' => $this->user->id,
            'title' => 'Current month event',
            'start_at' => now()->setTime(10, 0),
        ]);
        $currentEvent->subscribers()->attach($this->user);

        $nextMonthDate = now()->addMonthNoOverflow()->startOfMonth()->addDay();
        $nextEvent = Event::factory()->create([
            'author_id' => $this->user->id,
            'title' => 'Next month event',
            'start_at' => $nextMonthDate->copy()->setTime(10, 0),
        ]);
        $nextEvent->subscribers()->attach($this->user);

        $overdueTodo = Todo::factory()->create([
            'author_id' => $this->user->id,
            'title' => 'Overdue todo',
            'deadline' => now()->subMonth(),
        ]);
        $overdueTodo->subscribers()->attach($this->user);

        $nextTodo = Todo::factory()->create([
            'author_id' => $this->user->id,
            'title' => 'Next month todo',
            'deadline' => $nextMonthDate,
        ]);
        $nextTodo->subscribers()->attach($this->user);

        $this->actingAs($this->user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('dashboardMonths.data', 1)
                ->where(
                    'dashboardMonths.data.0.key',
                    now()->format('Y-m'),
                )
                ->has('dashboardMonths.data.0.events', 1)
                ->where(
                    'dashboardMonths.data.0.events.0.title',
                    'Current month event',
                )
                ->has('dashboardMonths.data.0.todos', 1)
                ->where(
                    'dashboardMonths.data.0.todos.0.title',
                    'Overdue todo',
                )
            );

        $this->actingAs($this->user)
            ->get(route('dashboard', ['dashboard' => 2]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('dashboardMonths.data', 1)
                ->where(
                    'dashboardMonths.data.0.key',
                    $nextMonthDate->format('Y-m'),
                )
                ->has('dashboardMonths.data.0.events', 1)
                ->where(
                    'dashboardMonths.data.0.events.0.title',
                    'Next month event',
                )
                ->has('dashboardMonths.data.0.todos', 1)
                ->where(
                    'dashboardMonths.data.0.todos.0.title',
                    'Next month todo',
                )
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
            ->get(route('event.historyIndex'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('events/EventHistoryIndex')
                ->has('historyEvents.data', 2)
                ->where('historyEvents.data.0.title', 'Recent Event')
                ->where('historyEvents.data.1.title', 'Older Event')
            );
    });
    it('paginates history events beyond the first twenty records', function () {
        foreach (range(1, 21) as $daysAgo) {
            $event = Event::factory()->create([
                'author_id' => $this->user->id,
                'title' => "History {$daysAgo}",
                'start_at' => now()->subDays($daysAgo),
            ]);
            $event->subscribers()->attach($this->user);
        }

        $this->actingAs($this->user)
            ->get(route('event.historyIndex', ['page' => 2]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('historyEvents.data', 1)
                ->where('historyEvents.data.0.title', 'History 21')
                ->where('historyEvents.meta.current_page', 2)
                ->where('historyEvents.meta.last_page', 2)
            );
    });
});
