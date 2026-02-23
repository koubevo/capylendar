<?php

namespace App\Services;

use App\Http\Requests\Todo\StoreTodoRequest;
use App\Http\Requests\Todo\UpdateTodoRequest;
use App\Http\Resources\TodoResource;
use App\Models\Tag;
use App\Models\Todo;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use shweshi\OpenGraph\OpenGraph;

class TodoService
{
    public function __construct(
        protected TodoUserService $todoUserService,
        protected TodoTagService $todoTagService,
        protected OpenGraph $openGraph
    ) {}

    public function store(StoreTodoRequest $request): ?Todo
    {
        $todoData = $request->safe()->except(['is_private', 'tags']);
        $isPrivateTodo = $request->boolean('is_private');
        $author = $request->user();

        if (! $author) {
            return null;
        }

        /** @var array<Tag> $tags */
        $tags = $request->input('tags', []);
        $todoData['meta'] = $this->resolveMetadata($todoData['description'] ?? null);

        return DB::transaction(function () use ($author, $todoData, $isPrivateTodo, $tags) {
            $todo = $author->authoredTodos()->create($todoData);

            $this->todoUserService->assignSubscribers($todo, $isPrivateTodo, $author);

            $this->todoTagService->assignTags($todo, $tags);

            return $todo;
        });
    }

    public function update(Todo $todo, UpdateTodoRequest $request): ?Todo
    {
        $todoData = $request->safe()->except(['is_private', 'tags']);
        $isPrivateTodo = $request->boolean('is_private');
        $author = $request->user();

        if (! $author) {
            return null;
        }

        /** @var array<Tag> $tags */
        $tags = $request->input('tags', []);
        $todoData['meta'] = $this->resolveMetadata($todoData['description'] ?? null);

        return DB::transaction(function () use ($author, $todoData, $isPrivateTodo, $todo, $tags) {
            $todo->update($todoData);

            $this->todoUserService->assignSubscribers($todo, $isPrivateTodo, $author);

            $this->todoTagService->assignTags($todo, $tags);

            return $todo;
        });
    }

    public function finish(Todo $todo): Todo
    {
        $todo->update(['finished_at' => Carbon::now()]);

        return $todo;
    }

    public function unfinish(Todo $todo): Todo
    {
        $todo->update(['finished_at' => null]);

        return $todo;
    }

    /**
     * @param  array<string, string>|null  $filters
     * @return array<TodoResource>
     */
    public function getAssignedTodos(?User $user, bool $finished = false, ?array $filters = []): array
    {
        if (! $user) {
            return [];
        }

        $query = $user
            ->assignedTodos()
            ->with(['tags', 'author'])
            ->withCount('subscribers');

        if ($finished) {
            $query->whereNotNull('finished_at');
        } else {
            $query->whereNull('finished_at');
        }

        if ($filters) {
            if (! empty($filters['search'])) {
                $operator = DB::connection()->getDriverName() === 'sqlite' ? 'like' : 'ilike';
                $query->where(function (Builder $query) use ($filters, $operator) {
                    $query->where('title', $operator, "%{$filters['search']}%")
                        ->orWhere('description', $operator, "%{$filters['search']}%");
                });
            }

            if (! empty($filters['capybara'])) {
                $query->where('capybara', $filters['capybara']);
            }

            if (! empty($filters['tags'])) {
                $query->whereHas('tags', function (Builder $q) use ($filters) {
                    $q->whereIn('tags.id', $filters['tags']);
                });
            }
        }

        $todos = $query->orderBy('deadline', $finished ? 'desc' : 'asc')
            ->get();

        return TodoResource::collection($todos)->resolve();
    }

    /**
     * @return array<TodoResource>
     */
    public function getDeletedTodos(?User $user): array
    {
        if (! $user) {
            return [];
        }

        $query = $user
            ->assignedTodos()
            ->with(['tags', 'author'])
            ->withCount('subscribers');

        $todos = $query->orderBy('deleted_at', 'desc')
            ->onlyTrashed()
            ->get();

        return TodoResource::collection($todos)->resolve();
    }

    public function restore(Todo $todo): Todo
    {
        $todo->restore();

        return $todo;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function resolveMetadata(?string $description): ?array
    {
        if (! $description) {
            return null;
        }

        if ($mapPreview = $this->resolveMapPreview($description)) {
            return ['map_preview' => $mapPreview];
        }

        return null;
    }

    /**
     * @return array{title: string, image: string, url: string}|null
     */
    private function resolveMapPreview(string $description): ?array
    {
        $pattern = '/https?:\/\/(?:www\.)?(?:google\.(?:com|cz)\/maps\/[^\s]+|maps\.app\.goo\.gl\/[^\s]+)/';

        if (! preg_match($pattern, $description, $matches)) {
            return null;
        }

        $mapUrl = $matches[0];

        try {
            $data = $this->openGraph->fetch($mapUrl);

            $title = $data['title'] ?? null;
            $image = $data['image'] ?? null;

            if (! $title || ! $image) {
                return null;
            }

            return [
                'title' => $title,
                'image' => $image,
                'url' => $mapUrl,
            ];
        } catch (Exception $e) {
            Log::error('Failed to fetch OpenGraph data for map preview.', ['exception' => $e]);

            return null;
        }
    }
}
