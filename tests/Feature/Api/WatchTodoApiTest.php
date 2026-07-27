<?php

use App\Models\Todo;
use App\Models\User;
use App\Models\WatchDevice;
use Illuminate\Support\Str;

function createWatchTokenFor(User $user, bool $revoked = false): string
{
    $plainTextToken = 'capy_watch_'.Str::random(64);

    WatchDevice::factory()->for($user)->create([
        'token_hash' => hash('sha256', $plainTextToken),
        'revoked_at' => $revoked ? now() : null,
    ]);

    return $plainTextToken;
}

function assignWatchTodoTo(Todo $todo, User $user): void
{
    $todo->subscribers()->attach($user);
}

describe('watch todo API', function () {
    it('requires a valid watch token', function () {
        $this->getJson(route('watch.todos.index'))->assertUnauthorized();
    });

    it('lists only unfinished todos assigned to the paired user', function () {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $token = createWatchTokenFor($user);

        $assignedTodo = Todo::factory()->for($user, 'author')->create([
            'title' => 'Koupit mléko',
            'deadline' => today(),
        ]);
        assignWatchTodoTo($assignedTodo, $user);

        $finishedTodo = Todo::factory()->for($user, 'author')->finished()->create();
        assignWatchTodoTo($finishedTodo, $user);

        $otherTodo = Todo::factory()->for($otherUser, 'author')->create();
        assignWatchTodoTo($otherTodo, $otherUser);

        $this->withToken($token)
            ->getJson(route('watch.todos.index'))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $assignedTodo->id)
            ->assertJsonPath('data.0.title', 'Koupit mléko')
            ->assertJsonPath('data.0.deadline.key', today()->format('Y-m-d'))
            ->assertJsonMissing(['id' => $finishedTodo->id])
            ->assertJsonMissing(['id' => $otherTodo->id]);
    });

    it('finishes and unfinishes an assigned todo idempotently', function () {
        $user = User::factory()->create();
        $token = createWatchTokenFor($user);
        $todo = Todo::factory()->for($user, 'author')->create();
        assignWatchTodoTo($todo, $user);

        $this->withToken($token)
            ->patchJson(route('watch.todos.completion.update', $todo), [
                'finished' => true,
            ])
            ->assertOk()
            ->assertJsonPath('data.is_finished', true);

        $finishedAt = $todo->refresh()->finished_at;
        expect($finishedAt)->not->toBeNull();

        $this->withToken($token)
            ->patchJson(route('watch.todos.completion.update', $todo), [
                'finished' => true,
            ])
            ->assertOk();

        expect($todo->refresh()->finished_at->equalTo($finishedAt))->toBeTrue();

        $this->withToken($token)
            ->patchJson(route('watch.todos.completion.update', $todo), [
                'finished' => false,
            ])
            ->assertOk()
            ->assertJsonPath('data.is_finished', false);

        expect($todo->refresh()->finished_at)->toBeNull();
    });

    it('forbids completing a todo assigned to another user', function () {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $token = createWatchTokenFor($user);
        $todo = Todo::factory()->for($otherUser, 'author')->create();
        assignWatchTodoTo($todo, $otherUser);

        $this->withToken($token)
            ->patchJson(route('watch.todos.completion.update', $todo), [
                'finished' => true,
            ])
            ->assertForbidden();

        expect($todo->refresh()->finished_at)->toBeNull();
    });

    it('rejects revoked watch tokens', function () {
        $user = User::factory()->create();
        $token = createWatchTokenFor($user, revoked: true);

        $this->withToken($token)
            ->getJson(route('watch.todos.index'))
            ->assertUnauthorized();
    });

    it('validates the requested completion state', function () {
        $user = User::factory()->create();
        $token = createWatchTokenFor($user);
        $todo = Todo::factory()->for($user, 'author')->create();
        assignWatchTodoTo($todo, $user);

        $this->withToken($token)
            ->patchJson(route('watch.todos.completion.update', $todo), [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('finished');
    });
});
