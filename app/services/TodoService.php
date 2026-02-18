<?php

namespace App\services;

use App\Http\Requests\Todo\StoreTodoRequest;
use App\Http\Requests\Todo\UpdateTodoRequest;
use App\Http\Resources\TodoResource;
use App\Models\Tag;
use App\Models\Todo;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Laravel\Facades\Image;
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
        $todoData = $request->safe()->except(['is_private', 'tags', 'image', 'remove_image']);
        $isPrivateTodo = $request->boolean('is_private');
        $author = $request->user();

        if (! $author) {
            return null;
        }

        /** @var array<Tag> $tags */
        $tags = $request->input('tags', []);
        $todoData['meta'] = $this->resolveMetadata($todoData['description'] ?? null);

        $newImagePath = null;
        if ($request->hasFile('image')) {
            $newImagePath = $this->compressAndStoreImage($request->file('image'));
            $todoData['image_path'] = $newImagePath;
        }

        try {
            return DB::transaction(function () use ($author, $todoData, $isPrivateTodo, $tags) {
                $todo = $author->authoredTodos()->create($todoData);

                $this->todoUserService->assignSubscribers($todo, $isPrivateTodo, $author);

                $this->todoTagService->assignTags($todo, $tags);

                return $todo;
            });
        } catch (Exception $e) {
            if ($newImagePath) {
                Storage::disk()->delete($newImagePath);
            }

            throw $e;
        }
    }

    public function update(Todo $todo, UpdateTodoRequest $request): ?Todo
    {
        $todoData = $request->safe()->except(['is_private', 'tags', 'image', 'remove_image']);
        $isPrivateTodo = $request->boolean('is_private');
        $author = $request->user();

        if (! $author) {
            return null;
        }

        /** @var array<Tag> $tags */
        $tags = $request->input('tags', []);
        $todoData['meta'] = $this->resolveMetadata($todoData['description'] ?? null);

        $oldImagePath = $todo->image_path;
        $newImagePath = null;

        if ($request->hasFile('image')) {
            $newImagePath = $this->compressAndStoreImage($request->file('image'));
            $todoData['image_path'] = $newImagePath;
        } elseif ($request->boolean('remove_image') && $oldImagePath) {
            $todoData['image_path'] = null;
        }

        try {
            $result = DB::transaction(function () use ($author, $todoData, $isPrivateTodo, $todo, $tags) {
                $todo->update($todoData);

                $this->todoUserService->assignSubscribers($todo, $isPrivateTodo, $author);

                $this->todoTagService->assignTags($todo, $tags);

                return $todo;
            });

            // Delete old image only after transaction succeeds
            if ($oldImagePath && ($request->hasFile('image') || $request->boolean('remove_image'))) {
                Storage::disk()->delete($oldImagePath);
            }

            return $result;
        } catch (Exception $e) {
            if ($newImagePath) {
                Storage::disk()->delete($newImagePath);
            }

            throw $e;
        }
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

        $events = $query->orderBy('deadline', $finished ? 'desc' : 'asc')
            ->get();

        return TodoResource::collection($events)->resolve();
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
            ->with(['tags', 'author']);

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

    private const IMAGE_MAX_WIDTH = 1920;

    private const IMAGE_QUALITY = 80;

    /**
     * Compress, resize, and convert the uploaded image to WebP format.
     */
    private function compressAndStoreImage(UploadedFile $file): string
    {
        $image = Image::read($file)
            ->scaleDown(width: self::IMAGE_MAX_WIDTH);

        $filename = 'todo-images/'.Str::uuid()->toString().'.webp';

        Storage::disk()->put(
            $filename,
            $image->toWebp(quality: self::IMAGE_QUALITY)->toString()
        );

        return $filename;
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
