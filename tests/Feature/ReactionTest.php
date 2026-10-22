<?php

use App\Enums\ReactionType;
use App\Models\Event;
use App\Models\Reaction;
use App\Models\Todo;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;

function reactionTypes(): array
{
    return ['event', 'todo'];
}

function reactionEndpointCases(): array
{
    $cases = [];

    foreach (reactionTypes() as $type) {
        foreach (['put', 'delete'] as $method) {
            $cases["{$type} {$method}"] = [$type, $method];
        }
    }

    return $cases;
}

dataset('reactionable types', reactionTypes());
dataset('reaction endpoints', reactionEndpointCases());

function reactionItem(string $type, User $author, array $attributes = []): Event|Todo
{
    $tomorrow = now()->addDay()->setTime(10, 0);

    $defaults = match ($type) {
        'event' => [
            'author_id' => $author->id,
            'start_at' => $tomorrow,
            'end_at' => $tomorrow->copy()->addHour(),
            'is_all_day' => false,
        ],
        'todo' => [
            'author_id' => $author->id,
            'deadline' => $tomorrow->toDateString(),
        ],
        default => throw new InvalidArgumentException("Unknown reactionable type [{$type}]."),
    };

    return match ($type) {
        'event' => Event::factory()->create([...$defaults, ...$attributes]),
        'todo' => Todo::factory()->create([...$defaults, ...$attributes]),
        default => throw new InvalidArgumentException("Unknown reactionable type [{$type}]."),
    };
}

function reactionRoute(string $type, string $action, Event|Todo|int $reactable, string $key): string
{
    $routeName = $type === 'event'
        ? "event.reactions.{$action}"
        : "todo.reactions.{$action}";
    $parameterName = $type === 'event' ? 'event' : 'todo';

    return route($routeName, [$parameterName => $reactable, 'reaction' => $key]);
}

function attachReactionSubscribers(Event|Todo $reactable, User ...$users): void
{
    $reactable->subscribers()->attach(array_map(
        fn (User $user): int => $user->id,
        $users,
    ));
}

function createReaction(User $user, Event|Todo $reactable, ReactionType|string $key): Reaction
{
    $key = $key instanceof ReactionType ? $key->value : $key;

    return Reaction::factory()->create([
        'user_id' => $user->id,
        'reactable_type' => $reactable->getMorphClass(),
        'reactable_id' => $reactable->getKey(),
        'key' => $key,
    ]);
}

function reactionGroup(ReactionType|string $key, User ...$users): array
{
    $key = $key instanceof ReactionType ? $key->value : $key;

    usort($users, fn (User $first, User $second): int => $first->id <=> $second->id);

    return [
        'key' => $key,
        'users' => array_map(
            fn (User $user): array => [
                'id' => $user->id,
                'name' => $user->name,
                'capybara' => $user->capybara->value,
            ],
            $users,
        ),
    ];
}

function reactionState(array ...$groups): array
{
    return ['reactions' => array_values($groups)];
}

function sendReactionRequest(mixed $testCase, string $method, string $url, array $body = []): mixed
{
    return match ($method) {
        'put' => $testCase->putJson($url, $body),
        'delete' => $testCase->deleteJson($url, $body),
        default => throw new InvalidArgumentException("Unknown reaction method [{$method}]."),
    };
}

/**
 * @param  array<int, mixed>  $queryLog
 * @return array<int, mixed>
 */
function reactionRelatedQueries(array $queryLog, int $reactionUserId): array
{
    return array_values(array_filter($queryLog, function (mixed $entry) use ($reactionUserId): bool {
        if (! is_array($entry)) {
            return false;
        }

        $query = strtolower((string) ($entry['query'] ?? ''));

        if (str_contains($query, 'reactions')) {
            return true;
        }

        if (! str_contains($query, ' from "users"') && ! str_contains($query, ' from users')) {
            return false;
        }

        $bindings = $entry['bindings'] ?? [];
        if (! is_array($bindings)) {
            return false;
        }

        foreach ($bindings as $binding) {
            if ((string) $binding === (string) $reactionUserId) {
                return true;
            }
        }

        return false;
    }));
}

it('keeps the reaction catalog order and option shape stable', function () {
    $options = collect(ReactionType::options())->values()->all();

    expect(array_map(fn (ReactionType $type): string => $type->value, ReactionType::cases()))->toBe([
        'love',
        'celebrate',
        'seen',
        'approve',
        'question',
    ]);
    expect($options)->toHaveCount(5);
    expect(array_column($options, 'key'))->toBe([
        'love',
        'celebrate',
        'seen',
        'approve',
        'question',
    ]);
    expect(array_column($options, 'emoji'))->toBe(['❤️', '🤩', '👀', '🙏', '🤔']);
    expect(array_column($options, 'label'))->toBe([
        'Líbí se mi',
        'Těším se',
        'Viděl/a jsem',
        'Díky',
        'Otázka / nejistota',
    ]);

    foreach ($options as $option) {
        expect(array_keys($option))->toBe(['key', 'emoji', 'label']);
        expect($option['emoji'])->toBeString()->not->toBeEmpty();
        expect($option['label'])->toBeString()->not->toBeEmpty();
    }
});

it('provides reaction relationships and enum casting', function (string $type) {
    $user = User::factory()->pink()->create();
    $reactable = reactionItem($type, $user);
    attachReactionSubscribers($reactable, $user);

    $reaction = createReaction($user, $reactable, ReactionType::Approve);

    expect($reactable->fresh()->reactions)->toHaveCount(1)
        ->and($user->fresh()->reactions)->toHaveCount(1)
        ->and($reaction->fresh()->key)->toBe(ReactionType::Approve);
})->with('reactionable types');

it('stores multiple user reactions in fixed catalog and user-id order', function (string $type) {
    $pinkUser = User::factory()->pink()->create(['name' => 'Pink user']);
    $blueUser = User::factory()->create(['name' => 'Blue user']);
    $reactable = reactionItem($type, $pinkUser);
    attachReactionSubscribers($reactable, $pinkUser, $blueUser);

    $this->actingAs($blueUser)
        ->putJson(reactionRoute($type, 'store', $reactable, 'approve'))
        ->assertOk();

    $this->actingAs($pinkUser)
        ->putJson(reactionRoute($type, 'store', $reactable, 'approve'))
        ->assertOk();

    $this->actingAs($pinkUser)
        ->putJson(reactionRoute($type, 'store', $reactable, 'approve'))
        ->assertOk();

    $this->actingAs($blueUser)
        ->putJson(reactionRoute($type, 'store', $reactable, 'seen'))
        ->assertOk();

    $response = $this->actingAs($pinkUser)
        ->putJson(reactionRoute($type, 'store', $reactable, 'love'))
        ->assertOk();

    $response->assertExactJson(reactionState(
        reactionGroup('love', $pinkUser),
        reactionGroup('seen', $blueUser),
        reactionGroup('approve', $pinkUser, $blueUser),
    ));

    expect(Reaction::query()->count())->toBe(4);
})->with('reactionable types');

it('deletes only the authenticated user reaction and remains idempotent', function (string $type) {
    $owner = User::factory()->pink()->create();
    $otherUser = User::factory()->create();
    $reactable = reactionItem($type, $owner);
    attachReactionSubscribers($reactable, $owner, $otherUser);

    createReaction($owner, $reactable, 'approve');
    createReaction($otherUser, $reactable, 'approve');
    createReaction($owner, $reactable, 'love');

    $firstDelete = $this->actingAs($owner)
        ->deleteJson(reactionRoute($type, 'destroy', $reactable, 'approve'))
        ->assertOk();

    $firstDelete->assertExactJson(reactionState(
        reactionGroup('love', $owner),
        reactionGroup('approve', $otherUser),
    ));

    $this->actingAs($owner)
        ->deleteJson(reactionRoute($type, 'destroy', $reactable, 'approve'))
        ->assertExactJson(reactionState(
            reactionGroup('love', $owner),
            reactionGroup('approve', $otherUser),
        ));

    $this->actingAs($owner)
        ->deleteJson(reactionRoute($type, 'destroy', $reactable, 'approve'))
        ->assertOk();

    expect(Reaction::query()
        ->where('user_id', $otherUser->id)
        ->where('reactable_id', $reactable->getKey())
        ->where('key', 'approve')
        ->exists())->toBeTrue();

    $this->actingAs($otherUser)
        ->deleteJson(reactionRoute($type, 'destroy', $reactable, 'approve'))
        ->assertExactJson(reactionState(
            reactionGroup('love', $owner),
        ));

    $this->actingAs($owner)
        ->deleteJson(reactionRoute($type, 'destroy', $reactable, 'love'))
        ->assertExactJson(reactionState());

    $this->actingAs($owner)
        ->deleteJson(reactionRoute($type, 'destroy', $reactable, 'love'))
        ->assertExactJson(reactionState());
})->with('reactionable types');

it('validates the reaction key after subscriber authorization', function (string $type, string $method) {
    $subscriber = User::factory()->create();
    $reactable = reactionItem($type, $subscriber);
    attachReactionSubscribers($reactable, $subscriber);

    sendReactionRequest(
        $this->actingAs($subscriber),
        $method,
        reactionRoute($type, $method === 'put' ? 'store' : 'destroy', $reactable, 'invalid'),
    )->assertUnprocessable();

    expect(Reaction::query()->count())->toBe(0);
})->with('reaction endpoints');

it('returns JSON authentication failures before validating an invalid key for guests', function (string $type, string $method) {
    $author = User::factory()->create();
    $reactable = reactionItem($type, $author);

    sendReactionRequest(
        $this,
        $method,
        reactionRoute($type, $method === 'put' ? 'store' : 'destroy', $reactable, 'invalid'),
    )->assertUnauthorized();
})->with('reaction endpoints');

it('returns forbidden before validating an invalid key for non-subscribers', function (string $type, string $method) {
    $subscriber = User::factory()->create();
    $nonSubscriber = User::factory()->pink()->create();
    $reactable = reactionItem($type, $subscriber);
    attachReactionSubscribers($reactable, $subscriber);

    sendReactionRequest(
        $this->actingAs($nonSubscriber),
        $method,
        reactionRoute($type, $method === 'put' ? 'store' : 'destroy', $reactable, 'invalid'),
    )->assertForbidden();
})->with('reaction endpoints');

it('ignores user, morph, and key values injected in the request body', function (string $type, string $method) {
    $owner = User::factory()->pink()->create();
    $injectedUser = User::factory()->create();
    $reactable = reactionItem($type, $owner);
    $injectedReactable = reactionItem($type, $owner);
    attachReactionSubscribers($reactable, $owner);

    $body = [
        'user_id' => $injectedUser->id,
        'reactable_type' => Todo::class,
        'reactable_id' => $injectedReactable->getKey(),
        'key' => 'love',
    ];

    if ($method === 'put') {
        $response = sendReactionRequest(
            $this->actingAs($owner),
            $method,
            reactionRoute($type, 'store', $reactable, 'approve'),
            $body,
        )->assertOk();

        $response->assertExactJson(reactionState(reactionGroup('approve', $owner)));

        expect(Reaction::query()->where('user_id', $injectedUser->id)->exists())->toBeFalse();
        expect($injectedReactable->reactions()->exists())->toBeFalse();

        return;
    }

    createReaction($owner, $reactable, 'approve');
    $injectedReaction = createReaction($injectedUser, $injectedReactable, 'love');

    $response = sendReactionRequest(
        $this->actingAs($owner),
        $method,
        reactionRoute($type, 'destroy', $reactable, 'approve'),
        $body,
    )->assertOk();

    $response->assertExactJson(reactionState());
    expect(Reaction::query()->find($injectedReaction->id))->not->toBeNull();
    expect($reactable->reactions()->where('key', 'approve')->exists())->toBeFalse();
})->with('reaction endpoints');

it('returns not found for missing and soft-deleted reactionables', function (string $type) {
    $subscriber = User::factory()->create();
    $missingId = 987654321;

    foreach (['put', 'delete'] as $method) {
        sendReactionRequest(
            $this->actingAs($subscriber),
            $method,
            reactionRoute($type, $method === 'put' ? 'store' : 'destroy', $missingId, 'approve'),
        )->assertNotFound();
    }

    $reactable = reactionItem($type, $subscriber);
    attachReactionSubscribers($reactable, $subscriber);
    createReaction($subscriber, $reactable, 'approve');
    $reactable->delete();

    foreach (['put', 'delete'] as $method) {
        sendReactionRequest(
            $this->actingAs($subscriber),
            $method,
            reactionRoute($type, $method === 'put' ? 'store' : 'destroy', $reactable->getKey(), 'approve'),
        )->assertNotFound();
    }
})->with('reactionable types');

it('includes reactions for subscribers while the view policy protects resources', function (string $type) {
    $subscriber = User::factory()->pink()->create(['name' => 'Subscriber']);
    $nonSubscriber = User::factory()->create(['name' => 'Private reader']);
    $reactable = reactionItem($type, $subscriber);
    attachReactionSubscribers($reactable, $subscriber);
    createReaction($subscriber, $reactable, 'approve');

    $this->actingAs($subscriber)
        ->get(route("{$type}.show", $reactable))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where("{$type}.reactions", [reactionGroup('approve', $subscriber)]));

    $this->actingAs($nonSubscriber)
        ->get(route("{$type}.show", $reactable))
        ->assertForbidden();
})->with('reactionable types');

it('cleans reactions on soft and force delete without restoring them', function (string $type) {
    $subscriber = User::factory()->create();
    $reactable = reactionItem($type, $subscriber);
    attachReactionSubscribers($reactable, $subscriber);
    $softDeletedReaction = createReaction($subscriber, $reactable, 'approve');

    $reactable->delete();

    $this->assertDatabaseMissing('reactions', ['id' => $softDeletedReaction->id]);

    $this->actingAs($subscriber)
        ->post(route("{$type}.restore", $reactable->getKey()))
        ->assertRedirect();

    expect($reactable->fresh()->trashed())->toBeFalse();
    $this->assertDatabaseMissing('reactions', ['id' => $softDeletedReaction->id]);

    $forceDeletedReactable = reactionItem($type, $subscriber);
    attachReactionSubscribers($forceDeletedReactable, $subscriber);
    $forceDeletedReaction = createReaction($subscriber, $forceDeletedReactable, 'seen');

    $forceDeletedReactable->forceDelete();

    $this->assertDatabaseMissing('reactions', ['id' => $forceDeletedReaction->id]);
})->with('reactionable types');

it('cascades reactions when their user is deleted', function (string $type) {
    $author = User::factory()->create();
    $reactionUser = User::factory()->pink()->create();
    $reactable = reactionItem($type, $author);
    attachReactionSubscribers($reactable, $reactionUser);
    $reaction = createReaction($reactionUser, $reactable, 'celebrate');

    $reactionUser->delete();

    $this->assertDatabaseMissing('reactions', ['id' => $reaction->id]);
})->with('reactionable types');

it('enforces reaction uniqueness at the database boundary', function (string $type) {
    $user = User::factory()->create();
    $reactable = reactionItem($type, $user);
    attachReactionSubscribers($reactable, $user);
    createReaction($user, $reactable, 'question');

    expect(fn () => createReaction($user, $reactable, 'question'))
        ->toThrow(QueryException::class);

    expect(Reaction::query()
        ->where('user_id', $user->id)
        ->where('reactable_id', $reactable->getKey())
        ->where('key', 'question')
        ->count())->toBe(1);
})->with('reactionable types');

it('keeps event and todo reactions separate when their IDs match', function () {
    $user = User::factory()->create();
    $event = reactionItem('event', $user);
    $todo = reactionItem('todo', $user, ['id' => $event->id]);
    attachReactionSubscribers($event, $user);
    attachReactionSubscribers($todo, $user);

    $this->actingAs($user)
        ->putJson(reactionRoute('event', 'store', $event, 'approve'))
        ->assertExactJson(reactionState(reactionGroup('approve', $user)));

    $this->putJson(reactionRoute('todo', 'store', $todo, 'love'))
        ->assertExactJson(reactionState(reactionGroup('love', $user)));

    $this->deleteJson(reactionRoute('event', 'destroy', $event, 'approve'))
        ->assertExactJson(reactionState());

    expect($todo->fresh()->reactions)->toHaveCount(1)
        ->and($todo->fresh()->reactions->first()->key)->toBe(ReactionType::Love);
});

it('does not send push notifications for reaction changes', function (string $type) {
    Notification::fake();

    $user = User::factory()->create();
    $reactable = reactionItem($type, $user);
    attachReactionSubscribers($reactable, $user);

    $this->actingAs($user)
        ->putJson(reactionRoute($type, 'store', $reactable, 'approve'))
        ->assertOk();

    $this->actingAs($user)
        ->deleteJson(reactionRoute($type, 'destroy', $reactable, 'approve'))
        ->assertOk();

    Notification::assertNothingSent();
})->with('reactionable types');

it('eager loads reaction users with constant reaction query count in lists', function (string $surface) {
    $viewer = User::factory()->create();
    $author = User::factory()->create(['name' => 'Author']);
    $reactionUser = User::factory()->pink()->create(['name' => 'Reaction user']);

    $createListItems = function (int $index) use ($surface, $viewer, $author, $reactionUser): void {
        $attachReaction = function (Event|Todo $reactable) use ($viewer, $reactionUser): void {
            attachReactionSubscribers($reactable, $viewer, $reactionUser);
            createReaction($reactionUser, $reactable, 'approve');
        };

        if ($surface === 'dashboard') {
            $eventDate = now()->addDays($index + 1)->setTime(10, 0);
            $event = reactionItem('event', $author, [
                'title' => "Dashboard event {$index}",
                'start_at' => $eventDate,
                'end_at' => $eventDate->copy()->addHour(),
            ]);
            $todo = reactionItem('todo', $author, [
                'title' => "Dashboard todo {$index}",
                'deadline' => $eventDate->toDateString(),
            ]);

            $attachReaction($event);
            $attachReaction($todo);

            return;
        }

        if ($surface === 'history') {
            $eventDate = now()->subDays($index + 1)->setTime(10, 0);
            $event = reactionItem('event', $author, [
                'title' => "History event {$index}",
                'start_at' => $eventDate,
                'end_at' => $eventDate->copy()->addHour(),
            ]);

            $attachReaction($event);

            return;
        }

        $todoDate = now()->addDays($index + 1)->setTime(10, 0);
        $todo = reactionItem('todo', $author, [
            'title' => "Todo list item {$index}",
            'deadline' => $todoDate->toDateString(),
        ]);

        $attachReaction($todo);
    };

    $createListItems(0);

    $listRoute = match ($surface) {
        'dashboard' => route('dashboard'),
        'history' => route('event.historyIndex'),
        'todo' => route('todo.index'),
        default => throw new InvalidArgumentException("Unknown list surface [{$surface}]."),
    };

    $connection = DB::connection();
    $connection->enableQueryLog();

    try {
        $connection->flushQueryLog();
        $singleResponse = $this->actingAs($viewer)->get($listRoute)->assertOk();
        $singleResponse->assertInertia(fn (Assert $page) => match ($surface) {
            'dashboard' => $page->has('dashboardMonths.data.0.events.0.reactions')
                ->has('dashboardMonths.data.0.todos.0.reactions'),
            'history' => $page->has('historyEvents.data.0.reactions'),
            'todo' => $page->has('unfinishedTodos.0.reactions'),
        });
        $singleQueries = reactionRelatedQueries($connection->getQueryLog(), $reactionUser->id);

        $connection->flushQueryLog();
        foreach (range(1, 4) as $index) {
            $createListItems($index);
        }
        $connection->flushQueryLog();

        $manyResponse = $this->actingAs($viewer)->get($listRoute)->assertOk();
        $manyResponse->assertInertia(fn (Assert $page) => match ($surface) {
            'dashboard' => $page->has('dashboardMonths.data.0.events.4.reactions')
                ->has('dashboardMonths.data.0.todos.4.reactions'),
            'history' => $page->has('historyEvents.data.4.reactions'),
            'todo' => $page->has('unfinishedTodos.4.reactions'),
        });
        $manyQueries = reactionRelatedQueries($connection->getQueryLog(), $reactionUser->id);
    } finally {
        $connection->disableQueryLog();
    }

    expect($singleQueries)->not->toBeEmpty();
    expect($manyQueries)->toHaveCount(count($singleQueries));
})->with([
    'dashboard' => 'dashboard',
    'history' => 'history',
    'todo list' => 'todo',
]);

it('does not query reactions for resources serialized without loaded reactions', function () {
    $user = User::factory()->create();
    $event = reactionItem('event', $user);
    attachReactionSubscribers($event, $user);
    createReaction($user, $event, 'love');

    $connection = DB::connection();
    $connection->enableQueryLog();

    try {
        $events = app(NotificationService::class)->getEventsForDate($user, now()->addDay());
        $reactionQueries = collect($connection->getQueryLog())
            ->filter(fn (array $query): bool => str_contains($query['query'], 'reactions'));
    } finally {
        $connection->disableQueryLog();
    }

    expect($events)->toHaveCount(1)
        ->and($events[0])->not->toHaveKey('reactions')
        ->and($reactionQueries)->toBeEmpty();
});
