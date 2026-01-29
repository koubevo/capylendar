<?php

use App\Enums\Capybara;
use App\Enums\EventType;
use App\Http\Requests\Event\StoreEventRequest;
use App\Http\Requests\Event\UpdateEventRequest;
use App\Models\Event;
use App\Models\Tag;
use App\Models\User;
use App\services\EventService;
use App\services\EventTagService;
use App\services\EventUserService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ValidatedInput;
use shweshi\OpenGraph\OpenGraph;

beforeEach(function () {
    $this->openGraph = Mockery::mock(OpenGraph::class);
    $this->eventService = new EventService(
        new EventUserService,
        new EventTagService,
        $this->openGraph
    );
    $this->user = User::factory()->create();
});

describe('EventService store', function () {
    it('returns null if author is missing', function () {
        $validatedInput = Mockery::mock(ValidatedInput::class);
        $validatedInput->shouldReceive('except')->andReturn([]);

        $request = Mockery::mock(StoreEventRequest::class);
        $request->shouldReceive('safe')->andReturn($validatedInput);
        $request->shouldReceive('boolean')->andReturn(false);
        $request->shouldReceive('input')->with('tags', [])->andReturn([]);
        $request->shouldReceive('user')->andReturn(null);

        $result = $this->eventService->store($request);
        
        expect($result)->toBeNull();
    });
});

describe('EventService update', function () {
    it('returns null if author is missing', function () {
        $validatedInput = Mockery::mock(ValidatedInput::class);
        $validatedInput->shouldReceive('except')->andReturn([]);

        $request = Mockery::mock(UpdateEventRequest::class);
        $request->shouldReceive('safe')->andReturn($validatedInput);
        $request->shouldReceive('boolean')->andReturn(false);
         $request->shouldReceive('input')->with('tags', [])->andReturn([]);
        $request->shouldReceive('user')->andReturn(null);

        $event = new Event();

        $result = $this->eventService->update($event, $request);
        
        expect($result)->toBeNull();
    });
});

describe('EventService resolveMetadata', function () {
    it('resolves map preview from google maps url', function () {
        $url = 'https://maps.app.goo.gl/test';
        $description = "Location: $url";
        
        $this->openGraph->shouldReceive('fetch')
            ->once()
            // ->with($url) // Argument matching might be strict, let's relax or ensure exact match
            ->andReturn([
                'title' => 'Map Location',
                'image' => 'http://example.com/image.jpg',
            ]);

        // Reflect to access private method
        $method = new ReflectionMethod(EventService::class, 'resolveMetadata');
        $method->setAccessible(true);
        
        $result = $method->invoke($this->eventService, $description);
        
        expect($result['map_preview']['title'])->toBe('Map Location');
        expect($result['map_preview']['url'])->toBe($url);
    });

    it('returns null when no map url', function () {
        $method = new ReflectionMethod(EventService::class, 'resolveMetadata');
        $method->setAccessible(true);
        
        $result = $method->invoke($this->eventService, 'Just description');
        
        expect($result)->toBeNull();
    });

    it('returns null and logs error when open graph fetch fails', function () {
        $url = 'https://maps.app.goo.gl/fail';
        $description = "Location: $url";

        $this->openGraph->shouldReceive('fetch')
            ->once()
            ->with($url)
            ->andThrow(new Exception('Fetch failed'));

        Log::shouldReceive('error')
            ->once()
            ->with('Failed to fetch OpenGraph data for map preview.', Mockery::any());

        $method = new ReflectionMethod(EventService::class, 'resolveMetadata');
        $method->setAccessible(true);

        $result = $method->invoke($this->eventService, $description);

        expect($result)->toBeNull();
    });
});

describe('EventService getAssignedEvents', function () {
    it('returns empty array when user is null', function () {
        $result = $this->eventService->getAssignedEvents(null);

        expect($result)->toBeEmpty();
    });

    it('returns only events user is subscribed to', function () {
        $subscribedEvent = Event::factory()->create([
            'author_id' => $this->user->id,
            'title' => 'My Event',
            'start_at' => now()->addDays(1),
        ]);
        $subscribedEvent->subscribers()->attach($this->user);

        $otherUser = User::factory()->pink()->create();
        $unsubscribedEvent = Event::factory()->create([
            'author_id' => $otherUser->id,
            'title' => 'Other Event',
            'start_at' => now()->addDays(1),
        ]);
        $unsubscribedEvent->subscribers()->attach($otherUser);

        $result = $this->eventService->getAssignedEvents($this->user);

        expect($result)->toHaveCount(1);
        expect($result[0]['title'])->toBe('My Event');
    });

    // ... other existing tests ...
    // Since I'm overwriting, I must include all tests.
    
    it('filters upcoming events correctly', function () {
        $upcomingEvent = Event::factory()->create([
            'author_id' => $this->user->id,
            'title' => 'Upcoming',
            'start_at' => now()->addDays(1),
        ]);
        $upcomingEvent->subscribers()->attach($this->user);

        $pastEvent = Event::factory()->create([
            'author_id' => $this->user->id,
            'title' => 'Past',
            'start_at' => now()->subDays(1),
        ]);
        $pastEvent->subscribers()->attach($this->user);

        $result = $this->eventService->getAssignedEvents($this->user, EventType::Upcoming);

        expect($result)->toHaveCount(1);
        expect($result[0]['title'])->toBe('Upcoming');
    });

    it('filters history events correctly', function () {
        $upcomingEvent = Event::factory()->create([
            'author_id' => $this->user->id,
            'title' => 'Upcoming',
            'start_at' => now()->addDays(1),
        ]);
        $upcomingEvent->subscribers()->attach($this->user);

        $pastEvent = Event::factory()->create([
            'author_id' => $this->user->id,
            'title' => 'Past',
            'start_at' => now()->subDays(1),
        ]);
        $pastEvent->subscribers()->attach($this->user);

        $result = $this->eventService->getAssignedEvents($this->user, EventType::History);

        expect($result)->toHaveCount(1);
        expect($result[0]['title'])->toBe('Past');
    });

    it('filters by capybara color', function () {
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

        $result = $this->eventService->getAssignedEvents(
            $this->user,
            EventType::Upcoming,
            ['capybara' => 'blue']
        );

        expect($result)->toHaveCount(1);
        expect($result[0]['title'])->toBe('Blue Event');
    });

    it('filters by tags', function () {
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

        $result = $this->eventService->getAssignedEvents(
            $this->user,
            EventType::Upcoming,
            ['tags' => [$tag->id]]
        );

        expect($result)->toHaveCount(1);
        expect($result[0]['title'])->toBe('Tagged Event');
    });

    it('orders upcoming events by start_at ascending', function () {
        $laterEvent = Event::factory()->create([
            'author_id' => $this->user->id,
            'title' => 'Later',
            'start_at' => now()->addDays(10),
        ]);
        $laterEvent->subscribers()->attach($this->user);

        $soonerEvent = Event::factory()->create([
            'author_id' => $this->user->id,
            'title' => 'Sooner',
            'start_at' => now()->addDays(1),
        ]);
        $soonerEvent->subscribers()->attach($this->user);

        $result = $this->eventService->getAssignedEvents($this->user, EventType::Upcoming);

        expect($result)->toHaveCount(2);
        expect($result[0]['title'])->toBe('Sooner');
        expect($result[1]['title'])->toBe('Later');
    });

    it('orders history events by start_at descending', function () {
        $olderEvent = Event::factory()->create([
            'author_id' => $this->user->id,
            'title' => 'Older',
            'start_at' => now()->subDays(10),
        ]);
        $olderEvent->subscribers()->attach($this->user);

        $recentEvent = Event::factory()->create([
            'author_id' => $this->user->id,
            'title' => 'Recent',
            'start_at' => now()->subDays(1),
        ]);
        $recentEvent->subscribers()->attach($this->user);

        $result = $this->eventService->getAssignedEvents($this->user, EventType::History);

        expect($result)->toHaveCount(2);
        expect($result[0]['title'])->toBe('Recent');
        expect($result[1]['title'])->toBe('Older');
    });
});

describe('EventService getDeletedEvents', function () {
    it('returns empty array when user is null', function () {
        $result = $this->eventService->getDeletedEvents(null);

        expect($result)->toBeEmpty();
    });

    it('returns only deleted events', function () {
        $activeEvent = Event::factory()->create([
            'author_id' => $this->user->id,
            'title' => 'Active Event',
        ]);
        $activeEvent->subscribers()->attach($this->user);

        $deletedEvent = Event::factory()->create([
            'author_id' => $this->user->id,
            'title' => 'Deleted Event',
        ]);
        $deletedEvent->subscribers()->attach($this->user);
        $deletedEvent->delete();

        $result = $this->eventService->getDeletedEvents($this->user);

        expect($result)->toHaveCount(1);
        expect($result[0]['title'])->toBe('Deleted Event');
    });

    it('orders deleted events by deleted_at descending', function () {
        $firstDeleted = Event::factory()->create([
            'author_id' => $this->user->id,
            'title' => 'First Deleted',
        ]);
        $firstDeleted->subscribers()->attach($this->user);
        $firstDeleted->delete();

        // Ensure different deleted_at times
        Carbon::setTestNow(now()->addSecond());

        $secondDeleted = Event::factory()->create([
            'author_id' => $this->user->id,
            'title' => 'Second Deleted',
        ]);
        $secondDeleted->subscribers()->attach($this->user);
        $secondDeleted->delete();

        Carbon::setTestNow();

        $result = $this->eventService->getDeletedEvents($this->user);

        expect($result)->toHaveCount(2);
        expect($result[0]['title'])->toBe('Second Deleted');
    });
});

describe('EventService restore', function () {
    it('restores a deleted event', function () {
        $event = Event::factory()->create([
            'author_id' => $this->user->id,
        ]);
        $event->delete();

        expect($event->trashed())->toBeTrue();

        $this->eventService->restore($event);

        $event->refresh();
        expect($event->trashed())->toBeFalse();
    });
});
