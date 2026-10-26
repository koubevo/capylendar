<?php

use App\Enums\Capybara;
use App\Enums\Priority;
use App\Models\Event;
use App\Models\Todo;
use App\Models\User;
use Illuminate\Support\Facades\Http;

const MAP_PREVIEW_URL = 'https://maps.app.goo.gl/place';

function fakeMapPreview(?Closure $beforeResponse = null): void
{
    Http::fake(function () use ($beforeResponse) {
        $beforeResponse?->__invoke();

        return Http::response(
            '<meta property="og:title" content="Kavárna"><meta property="og:image" content="https://example.com/map.jpg">',
            200,
            ['Content-Type' => 'text/html'],
        );
    });
}

function mapPreviewEventPayload(array $attributes = []): array
{
    return [
        'title' => 'Kafe',
        'date' => now()->addDay()->format('Y-m-d'),
        'start_at' => '10:00',
        'is_all_day' => false,
        'capybara' => Capybara::Blue->value,
        'description' => 'Sraz tady '.MAP_PREVIEW_URL,
        ...$attributes,
    ];
}

beforeEach(function () {
    $this->user = User::factory()->create();
});

it('stores the map preview of a new event after the response without marking it as edited', function () {
    fakeMapPreview();

    $this->actingAs($this->user)->post(route('event.store'), mapPreviewEventPayload())->assertRedirect();

    $event = Event::query()->where('title', 'Kafe')->firstOrFail();

    expect($event->meta['map_preview'])->toMatchArray([
        'title' => 'Kavárna',
        'image' => 'https://example.com/map.jpg',
        'url' => MAP_PREVIEW_URL,
    ])->and($event->updated_at)->toBeNull();
});

it('stores the map preview of a new todo after the response', function () {
    fakeMapPreview();

    $this->actingAs($this->user)->post(route('todo.store'), [
        'title' => 'Koupit kafe',
        'capybara' => Capybara::Blue->value,
        'deadline' => now()->addDay()->format('Y-m-d'),
        'priority' => Priority::Medium->value,
        'description' => 'Tady '.MAP_PREVIEW_URL,
    ])->assertRedirect();

    expect(Todo::query()->where('title', 'Koupit kafe')->firstOrFail()->meta['map_preview']['title'])
        ->toBe('Kavárna');
});

it('discards a map preview when the description changes before the lookup finishes', function () {
    fakeMapPreview(fn () => Event::query()->update(['description' => 'Jiný popis']));

    $this->actingAs($this->user)->post(route('event.store'), mapPreviewEventPayload())->assertRedirect();

    expect(Event::query()->where('title', 'Kafe')->firstOrFail()->meta)->toBeNull();
});

it('clears a stale map preview when the description changes', function () {
    Http::preventStrayRequests();
    $event = Event::factory()->create([
        'author_id' => $this->user->id,
        'description' => 'Sraz '.MAP_PREVIEW_URL,
        'meta' => ['map_preview' => ['title' => 'Starý', 'image' => 'https://example.com/old.jpg', 'url' => MAP_PREVIEW_URL]],
    ]);
    $event->subscribers()->attach($this->user);

    $this->actingAs($this->user)
        ->put(route('event.update', $event), mapPreviewEventPayload(['description' => 'Bez mapy']))
        ->assertRedirect();

    expect($event->refresh()->meta)->toBeNull();
});

it('keeps an existing map preview without a lookup when the description is unchanged', function () {
    Http::preventStrayRequests();
    $meta = ['map_preview' => ['title' => 'Starý', 'image' => 'https://example.com/old.jpg', 'url' => MAP_PREVIEW_URL]];
    $event = Event::factory()->create([
        'author_id' => $this->user->id,
        'description' => 'Sraz tady '.MAP_PREVIEW_URL,
        'meta' => $meta,
    ]);
    $event->subscribers()->attach($this->user);

    $this->actingAs($this->user)
        ->put(route('event.update', $event), mapPreviewEventPayload(['title' => 'Nový název']))
        ->assertRedirect();

    expect($event->refresh()->meta)->toBe($meta);
    Http::assertNothingSent();
});

it('retries a missing map preview when an event is saved again', function () {
    fakeMapPreview();
    $event = Event::factory()->create([
        'author_id' => $this->user->id,
        'description' => 'Sraz tady '.MAP_PREVIEW_URL,
        'meta' => null,
    ]);
    $event->subscribers()->attach($this->user);

    $this->actingAs($this->user)
        ->put(route('event.update', $event), mapPreviewEventPayload())
        ->assertRedirect();

    expect($event->refresh()->meta['map_preview']['title'])->toBe('Kavárna');
});

it('keeps an existing map preview when an update omits the description', function () {
    Http::preventStrayRequests();
    $meta = ['map_preview' => ['title' => 'Starý', 'image' => 'https://example.com/old.jpg', 'url' => MAP_PREVIEW_URL]];
    $todo = Todo::factory()->create([
        'author_id' => $this->user->id,
        'description' => 'Tady '.MAP_PREVIEW_URL,
        'meta' => $meta,
    ]);
    $todo->subscribers()->attach($this->user);

    $this->actingAs($this->user)->put(route('todo.update', $todo), [
        'title' => 'Nový název',
        'capybara' => Capybara::Blue->value,
        'deadline' => now()->addDay()->format('Y-m-d'),
        'priority' => Priority::Medium->value,
    ])->assertRedirect();

    expect($todo->refresh()->meta)->toBe($meta);
});
