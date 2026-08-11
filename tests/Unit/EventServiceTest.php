<?php

use App\Enums\Capybara;
use App\Enums\EventType;
use App\Http\Requests\Event\StoreEventRequest;
use App\Http\Requests\Event\UpdateEventRequest;
use App\Models\Event;
use App\Models\Tag;
use App\Models\User;
use App\Services\EventService;
use App\Services\EventTagService;
use App\Services\EventUserService;
use Carbon\Carbon;
use GuzzleHttp\Promise\Create;
use GuzzleHttp\Psr7\PumpStream;
use GuzzleHttp\Psr7\Response as PsrResponse;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ValidatedInput;

beforeEach(function () {
    $this->eventService = new EventService(
        new EventUserService,
        new EventTagService,
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

        $event = new Event;

        $result = $this->eventService->update($event, $request);

        expect($result)->toBeNull();
    });
});

describe('EventService resolveMetadata', function () {
    it('resolves map preview from google maps url', function () {
        $url = 'https://maps.app.goo.gl/test';

        Http::fake([
            $url => Http::response(
                '<meta property="og:title" content="Map Location"><meta property="og:image" content="https://example.com/image.jpg">',
                200,
                ['Content-Type' => 'text/html; charset=UTF-8'],
            ),
        ]);

        $method = new ReflectionMethod(EventService::class, 'resolveMetadata');
        $method->setAccessible(true);

        $result = $method->invoke($this->eventService, "Location: $url");

        expect($result['map_preview']['title'])->toBe('Map Location');
        expect($result['map_preview']['image'])->toBe('https://example.com/image.jpg');
        expect($result['map_preview']['url'])->toBe($url);
    });

    it('preserves utf-8 map preview titles without a charset declaration', function () {
        $url = 'https://maps.app.goo.gl/utf8';
        $title = "\u{010C}esk\u{00FD} Krumlov \u{2013} n\u{00E1}m\u{011B}st\u{00ED}";

        Http::fake([
            $url => Http::response(
                '<meta property="og:title" content="'.$title.'"><meta property="og:image" content="https://example.com/image.jpg">',
                200,
                ['Content-Type' => 'text/html'],
            ),
        ]);

        $method = new ReflectionMethod(EventService::class, 'resolveMetadata');
        $method->setAccessible(true);

        $result = $method->invoke($this->eventService, "Location: $url");

        expect($result['map_preview']['title'])->toBe($title);
    });

    it('rejects insecure preview images', function () {
        $url = 'https://maps.app.goo.gl/insecure-image';

        Http::fake([
            $url => Http::response(
                '<meta property="og:title" content="Map Location"><meta property="og:image" content="http://example.com/image.jpg">',
                200,
                ['Content-Type' => 'text/html; charset=UTF-8'],
            ),
        ]);

        $method = new ReflectionMethod(EventService::class, 'resolveMetadata');
        $method->setAccessible(true);

        expect($method->invoke($this->eventService, "Location: $url"))->toBeNull();
    });

    it('does not make a request when no map url is present', function () {
        Http::preventStrayRequests();

        $method = new ReflectionMethod(EventService::class, 'resolveMetadata');
        $method->setAccessible(true);

        $result = $method->invoke($this->eventService, 'Just description');

        expect($result)->toBeNull();
        Http::assertNothingSent();
    });

    it('rejects insecure map urls', function () {
        Http::preventStrayRequests();

        $method = new ReflectionMethod(EventService::class, 'resolveMetadata');
        $method->setAccessible(true);

        $result = $method->invoke(
            $this->eventService,
            'Location: http://maps.app.goo.gl/insecure',
        );

        expect($result)->toBeNull();
        Http::assertNothingSent();
    });

    it('rejects redirects to untrusted hosts', function () {
        $url = 'https://maps.app.goo.gl/private';

        Http::fake([
            $url => Http::response('', 302, [
                'Location' => 'http://127.0.0.1/private',
            ]),
        ]);

        $method = new ReflectionMethod(EventService::class, 'resolveMetadata');
        $method->setAccessible(true);

        $result = $method->invoke($this->eventService, "Location: $url");

        expect($result)->toBeNull();
        Http::assertSentCount(1);
    });

    it('rejects oversized map responses', function () {
        $url = 'https://maps.app.goo.gl/large';
        $remainingBytes = 1048577;
        $body = new PumpStream(function (int $requestedBytes) use (&$remainingBytes): string|false {
            if ($remainingBytes === 0) {
                return false;
            }

            $chunkSize = min($requestedBytes, $remainingBytes);
            $remainingBytes -= $chunkSize;

            return str_repeat('a', $chunkSize);
        });

        Http::fake(fn () => Create::promiseFor(new PsrResponse(
            200,
            ['Content-Type' => 'text/html'],
            $body,
        )));

        $method = new ReflectionMethod(EventService::class, 'resolveMetadata');
        $method->setAccessible(true);

        $result = $method->invoke($this->eventService, "Location: $url");

        expect($result)->toBeNull();
    });

    it('rejects response bodies after their absolute deadline', function () {
        $response = new Response(new PsrResponse(
            200,
            ['Content-Type' => 'text/html'],
            'response body',
        ));

        $method = new ReflectionMethod(EventService::class, 'readLimitedResponseBody');
        $method->setAccessible(true);

        expect($method->invoke($this->eventService, $response, hrtime(true) - 1))
            ->toBeNull();
    });

    it('returns null and logs error when the map request fails', function () {
        $url = 'https://maps.app.goo.gl/fail';

        Http::fake(fn () => throw new RuntimeException('Fetch failed'));

        Log::shouldReceive('error')
            ->once()
            ->with('Failed to fetch OpenGraph data for map preview.', Mockery::any());

        $method = new ReflectionMethod(EventService::class, 'resolveMetadata');
        $method->setAccessible(true);

        $result = $method->invoke($this->eventService, "Location: $url");

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
