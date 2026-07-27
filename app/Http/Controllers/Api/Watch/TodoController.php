<?php

namespace App\Http\Controllers\Api\Watch;

use App\Http\Controllers\Controller;
use App\Http\Requests\Watch\UpdateWatchTodoCompletionRequest;
use App\Http\Resources\TodoResource;
use App\Models\Todo;
use App\Services\TodoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class TodoController extends Controller
{
    public function __construct(protected TodoService $todoService) {}

    public function index(Request $request): JsonResponse
    {
        $todos = array_map(
            fn (array $todo): array => $this->toWatchTodo($todo),
            $this->todoService->getAssignedTodos($request->user(), finished: false),
        );

        return response()->json(['data' => $todos]);
    }

    public function updateCompletion(Todo $todo, UpdateWatchTodoCompletionRequest $request): JsonResponse
    {
        Gate::authorize('finish', $todo);

        $shouldBeFinished = $request->boolean('finished');

        if ($shouldBeFinished && ! $todo->is_finished) {
            $this->todoService->finish($todo);
        }

        if (! $shouldBeFinished && $todo->is_finished) {
            $this->todoService->unfinish($todo);
        }

        $todo->refresh()->loadMissing(['author', 'tags'])->loadCount('subscribers');
        $resolvedTodo = TodoResource::make($todo)->resolve();

        return response()->json(['data' => $this->toWatchTodo($resolvedTodo)]);
    }

    /**
     * @param  array<string, mixed>  $todo
     * @return array<string, mixed>
     */
    private function toWatchTodo(array $todo): array
    {
        $priority = $todo['priority'] ?? null;
        $capybara = $todo['capybara'] ?? null;

        if (! is_array($priority) || ! is_array($capybara)) {
            throw new \UnexpectedValueException('The todo resource has an invalid watch representation.');
        }

        return [
            'id' => $todo['id'],
            'title' => $todo['title'],
            'deadline' => $todo['deadline'],
            'priority' => [
                'value' => $priority['value'],
                'label' => $priority['label'],
            ],
            'capybara' => [
                'value' => $capybara['value'],
                'label' => $capybara['label'],
            ],
            'is_finished' => $todo['is_finished'],
        ];
    }
}
