<?php

namespace App\Http\Controllers;

use App\Enums\Capybara;
use App\Enums\Priority;
use App\Http\Requests\Todo\StoreTodoRequest;
use App\Http\Requests\Todo\UpdateTodoRequest;
use App\Http\Resources\TodoResource;
use App\Models\Todo;
use App\Services\TagService;
use App\Services\TodoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class TodoController extends Controller
{
    public function __construct(protected TodoService $todoService, protected TagService $tagService) {}

    public function index(): Response
    {
        $user = auth()->user();

        return Inertia::render('todos/TodoIndex', [
            'unfinishedTodos' => $this->todoService->getAssignedTodos($user, finished: false),
            'finishedTodos' => $this->todoService->getAssignedTodos($user, finished: true),
        ]);
    }

    public function show(Todo $todo): Response
    {
        Gate::authorize('view', $todo);

        $todo->loadMissing(['author', 'tags'])->loadCount('subscribers');

        return Inertia::render('todos/TodoShow', [
            'todo' => TodoResource::make($todo)->resolve(),
        ]);
    }

    public function create(Request $request): Response
    {
        $todo = null;

        if ($request->has('duplicate_todo_id')) {
            $foundTodo = Todo::findOrFail($request->input('duplicate_todo_id'));
            Gate::authorize('view', $foundTodo);
            $todo = new TodoResource($foundTodo);
        }

        return Inertia::render('todos/TodoCreate', [
            'capybaraOptions' => Capybara::options(),
            'priorityOptions' => Priority::options(),
            'todo' => $todo?->resolve(),
            'availableTags' => $this->tagService->getAvailableTags(),
        ]);
    }

    public function store(StoreTodoRequest $request): RedirectResponse
    {
        $todo = $this->todoService->store($request);

        if (! $todo) {
            return back()->withErrors(['error' => 'Nepodařilo se vytvořit todo']);
        }

        return to_route('dashboard')->with('success', 'Todo úspěšně přidáno');
    }

    public function edit(Todo $todo): Response
    {
        Gate::authorize('update', $todo);

        $todo->loadMissing(['author', 'tags'])->loadCount('subscribers');

        return Inertia::render('todos/TodoEdit', [
            'capybaraOptions' => Capybara::options(),
            'priorityOptions' => Priority::options(),
            'todo' => TodoResource::make($todo)->resolve(),
            'availableTags' => $this->tagService->getAvailableTags(),
        ]);
    }

    public function update(Todo $todo, UpdateTodoRequest $request): RedirectResponse
    {
        Gate::authorize('update', $todo);

        $this->todoService->update($todo, $request);

        return to_route('todo.show', $todo)->with('success', 'Todo úspěšně aktualizováno');
    }

    public function destroy(Todo $todo): RedirectResponse
    {
        Gate::authorize('delete', $todo);

        $todo->delete();

        return to_route('todo.index')->with('success', 'Todo úspěšně smazáno');
    }

    public function finish(Todo $todo): RedirectResponse
    {
        Gate::authorize('finish', $todo);

        if ($todo->is_finished) {
            $this->todoService->unfinish($todo);

            return back()->with('success', 'Todo označeno jako nesplněné');
        }

        $this->todoService->finish($todo);

        return back()->with('success', 'Todo splněno!');
    }

    public function deletedIndex(): Response
    {
        $user = auth()->user();
        $deletedTodos = $this->todoService->getDeletedTodos($user);

        return Inertia::render('todos/TodoDeletedIndex', [
            'deletedTodos' => $deletedTodos,
        ]);
    }

    public function restore(Todo $todo): RedirectResponse
    {
        Gate::authorize('restore', $todo);

        $this->todoService->restore($todo);

        return to_route('todo.deletedIndex')->with('success', 'Todo úspěšně obnoveno');
    }
}
