<?php

use App\Enums\Capybara;
use App\Enums\Priority;
use App\Models\Tag;
use App\Models\Todo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// Helper to create a user with a todo
function createUserWithTodo(?array $todoAttributes = []): array
{
    $user = User::factory()->create();
    $todo = Todo::factory()->create(['author_id' => $user->id, ...$todoAttributes]);
    $todo->subscribers()->attach($user);

    return [$user, $todo];
}

describe('TodoController index', function () {
    it('renders the todo index page', function () {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('todo.index'))
            ->assertOk()
            ->assertInertia(
                fn ($page) => $page->component('todos/TodoIndex')
                    ->has('unfinishedTodos')
                    ->has('finishedTodos')
            );
    });

    it('requires authentication to access index', function () {
        $this->get(route('todo.index'))
            ->assertRedirect(route('login'));
    });
});

describe('TodoController create', function () {
    it('renders the todo create page', function () {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('todo.create'))
            ->assertOk()
            ->assertInertia(
                fn ($page) => $page->component('todos/TodoCreate')
                    ->has('capybaraOptions')
                    ->has('priorityOptions')
                    ->has('availableTags')
            );
    });

    it('requires authentication to access create', function () {
        $this->get(route('todo.create'))
            ->assertRedirect(route('login'));
    });
});

describe('TodoController store', function () {
    it('stores a new todo successfully', function () {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('todo.store'), [
                'title' => 'Test Todo',
                'capybara' => Capybara::Blue->value,
                'deadline' => '2026-03-01',
                'priority' => Priority::Medium->value,
                'is_private' => false,
                'description' => 'Todo description',
            ])
            ->assertRedirect(route('dashboard'));

        $this->assertDatabaseHas('todos', [
            'title' => 'Test Todo',
            'capybara' => Capybara::Blue->value,
            'priority' => Priority::Medium->value,
            'author_id' => $user->id,
        ]);
    });

    it('stores a private todo for only the author', function () {
        $user = User::factory()->create();
        User::factory()->create(); // another user

        $this->actingAs($user)
            ->post(route('todo.store'), [
                'title' => 'Private Todo',
                'capybara' => Capybara::Pink->value,
                'deadline' => '2026-03-01',
                'priority' => Priority::High->value,
                'is_private' => true,
            ]);

        $todo = Todo::first();
        expect($todo->subscribers)->toHaveCount(1);
        expect($todo->subscribers->first()->id)->toBe($user->id);
    });

    it('stores a todo with tags', function () {
        $user = User::factory()->create();
        $tags = Tag::factory()->count(2)->create();

        $this->actingAs($user)
            ->post(route('todo.store'), [
                'title' => 'Tagged Todo',
                'capybara' => Capybara::Blue->value,
                'deadline' => '2026-03-01',
                'priority' => Priority::Low->value,
                'tags' => $tags->pluck('id')->toArray(),
            ]);

        $todo = Todo::first();
        expect($todo->tags)->toHaveCount(2);
    });

    it('requires authentication to store todo', function () {
        $this->post(route('todo.store'), [
            'title' => 'Test',
            'capybara' => Capybara::Blue->value,
            'deadline' => '2026-03-01',
            'priority' => Priority::Medium->value,
        ])->assertRedirect(route('login'));
    });
});

describe('TodoController store validation', function () {
    it('requires title', function () {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('todo.store'), [
                'capybara' => Capybara::Blue->value,
                'deadline' => '2026-03-01',
                'priority' => Priority::Medium->value,
            ])
            ->assertSessionHasErrors('title');
    });

    it('requires deadline', function () {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('todo.store'), [
                'title' => 'Test',
                'capybara' => Capybara::Blue->value,
                'priority' => Priority::Medium->value,
            ])
            ->assertSessionHasErrors('deadline');
    });

    it('requires valid capybara value', function () {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('todo.store'), [
                'title' => 'Test',
                'capybara' => 'invalid',
                'deadline' => '2026-03-01',
                'priority' => Priority::Medium->value,
            ])
            ->assertSessionHasErrors('capybara');
    });

    it('requires valid priority value', function () {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('todo.store'), [
                'title' => 'Test',
                'capybara' => Capybara::Blue->value,
                'deadline' => '2026-03-01',
                'priority' => 'invalid',
            ])
            ->assertSessionHasErrors('priority');
    });

    it('validates title max length', function () {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('todo.store'), [
                'title' => str_repeat('a', 256),
                'capybara' => Capybara::Blue->value,
                'deadline' => '2026-03-01',
                'priority' => Priority::Medium->value,
            ])
            ->assertSessionHasErrors('title');
    });

    it('validates tags exist in database', function () {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('todo.store'), [
                'title' => 'Test',
                'capybara' => Capybara::Blue->value,
                'deadline' => '2026-03-01',
                'priority' => Priority::Medium->value,
                'tags' => [9999],
            ])
            ->assertSessionHasErrors('tags.0');
    });
});

describe('TodoController show', function () {
    it('shows todo detail page for subscriber', function () {
        [$user, $todo] = createUserWithTodo();

        $this->actingAs($user)
            ->get(route('todo.show', $todo))
            ->assertOk()
            ->assertInertia(
                fn ($page) => $page->component('todos/TodoShow')
                    ->has('todo')
            );
    });

    it('forbids non-subscriber from viewing', function () {
        [, $todo] = createUserWithTodo();
        $otherUser = User::factory()->create();

        $this->actingAs($otherUser)
            ->get(route('todo.show', $todo))
            ->assertForbidden();
    });
});

describe('TodoController edit', function () {
    it('renders edit form for subscriber', function () {
        [$user, $todo] = createUserWithTodo();

        $this->actingAs($user)
            ->get(route('todo.edit', $todo))
            ->assertOk()
            ->assertInertia(
                fn ($page) => $page->component('todos/TodoEdit')
                    ->has('todo')
                    ->has('capybaraOptions')
                    ->has('priorityOptions')
                    ->has('availableTags')
            );
    });
});

describe('TodoController update', function () {
    it('updates a todo successfully', function () {
        [$user, $todo] = createUserWithTodo();

        $this->actingAs($user)
            ->put(route('todo.update', $todo), [
                'title' => 'Updated Title',
                'capybara' => Capybara::Pink->value,
                'deadline' => '2026-04-01',
                'priority' => Priority::High->value,
                'is_private' => false,
            ])
            ->assertRedirect(route('todo.show', $todo));

        $todo->refresh();
        expect($todo->title)->toBe('Updated Title');
        expect($todo->capybara)->toBe(Capybara::Pink);
        expect($todo->priority)->toBe(Priority::High);
    });

    it('updates todo tags', function () {
        [$user, $todo] = createUserWithTodo();
        $tags = Tag::factory()->count(2)->create();

        $this->actingAs($user)
            ->put(route('todo.update', $todo), [
                'title' => 'Updated',
                'capybara' => Capybara::Blue->value,
                'deadline' => '2026-04-01',
                'priority' => Priority::Medium->value,
                'tags' => $tags->pluck('id')->toArray(),
            ]);

        $todo->refresh();
        expect($todo->tags)->toHaveCount(2);
    });
});

describe('TodoController destroy', function () {
    it('soft deletes a todo', function () {
        [$user, $todo] = createUserWithTodo();

        $this->actingAs($user)
            ->delete(route('todo.destroy', $todo))
            ->assertRedirect(route('todo.index'));

        $this->assertSoftDeleted('todos', ['id' => $todo->id]);
    });
});

describe('TodoController finish', function () {
    it('finishes an unfinished todo', function () {
        [$user, $todo] = createUserWithTodo();

        $this->actingAs($user)
            ->post(route('todo.finish', $todo))
            ->assertRedirect();

        $todo->refresh();
        expect($todo->is_finished)->toBeTrue();
    });

    it('unfinishes a finished todo', function () {
        [$user, $todo] = createUserWithTodo(['finished_at' => now()]);

        $this->actingAs($user)
            ->post(route('todo.finish', $todo))
            ->assertRedirect();

        $todo->refresh();
        expect($todo->is_finished)->toBeFalse();
    });

    it('forbids non-subscriber from finishing', function () {
        [, $todo] = createUserWithTodo();
        $otherUser = User::factory()->create();

        $this->actingAs($otherUser)
            ->post(route('todo.finish', $todo))
            ->assertForbidden();
    });
});

describe('TodoController deletedIndex', function () {
    it('shows deleted todos index page', function () {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('todo.deletedIndex'))
            ->assertOk()
            ->assertInertia(
                fn ($page) => $page->component('todos/TodoDeletedIndex')
                    ->has('deletedTodos')
            );
    });

    it('requires authentication', function () {
        $this->get(route('todo.deletedIndex'))
            ->assertRedirect(route('login'));
    });
});

describe('TodoController restore', function () {
    it('restores a deleted todo', function () {
        [$user, $todo] = createUserWithTodo();
        $todo->delete();

        $this->actingAs($user)
            ->post(route('todo.restore', $todo))
            ->assertRedirect(route('todo.deletedIndex'));

        expect(Todo::find($todo->id))->not->toBeNull();
    });
});
