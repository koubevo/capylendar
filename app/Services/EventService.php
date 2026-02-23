<?php

namespace App\Services;

use App\Enums\EventType;
use App\Http\Requests\Event\StoreEventRequest;
use App\Http\Requests\Event\UpdateEventRequest;
use App\Http\Resources\EventResource;
use App\Models\Event;
use App\Models\Tag;
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

class EventService
{
    public function __construct(
        protected EventUserService $eventUserService,
        protected EventTagService $eventTagService,
        protected OpenGraph $openGraph
    ) {}

    private const HISTORY_EVENTS_LIMIT = 20;

    public function store(StoreEventRequest $request): ?Event
    {
        $eventData = $request->safe()->except(['is_private', 'tags', 'image', 'remove_image']);
        $isPrivateEvent = $request->boolean('is_private');
        $author = $request->user();

        if (! $author) {
            return null;
        }

        /** @var array<Tag> $tags */
        $tags = $request->input('tags', []);
        $eventData['meta'] = $this->resolveMetadata($eventData['description'] ?? null);

        $newImagePath = null;
        if ($request->hasFile('image')) {
            $newImagePath = $this->compressAndStoreImage($request->file('image'));
            $eventData['image_path'] = $newImagePath;
        }

        try {
            return DB::transaction(function () use ($author, $eventData, $isPrivateEvent, $tags) {
                $event = $author->authoredEvents()->create($eventData);

                $this->eventUserService->assignSubscribers($event, $isPrivateEvent, $author);

                $this->eventTagService->assignTags($event, $tags);

                return $event;
            });
        } catch (Exception $e) {
            if ($newImagePath) {
                Storage::disk()->delete($newImagePath);
            }

            throw $e;
        }
    }

    public function update(Event $event, UpdateEventRequest $request): ?Event
    {
        $eventData = $request->safe()->except(['is_private', 'tags', 'image', 'remove_image']);
        $isPrivateEvent = $request->boolean('is_private');
        $author = $request->user();

        if (! $author) {
            return null;
        }

        /** @var array<Tag> $tags */
        $tags = $request->input('tags', []);
        $eventData['meta'] = $this->resolveMetadata($eventData['description'] ?? null);

        $oldImagePath = $event->image_path;
        $newImagePath = null;

        if ($request->hasFile('image')) {
            $newImagePath = $this->compressAndStoreImage($request->file('image'));
            $eventData['image_path'] = $newImagePath;
        } elseif ($request->boolean('remove_image') && $oldImagePath) {
            $eventData['image_path'] = null;
        }

        try {
            $result = DB::transaction(function () use ($author, $eventData, $isPrivateEvent, $event, $tags) {
                $event->update($eventData);

                $this->eventUserService->assignSubscribers($event, $isPrivateEvent, $author);

                $this->eventTagService->assignTags($event, $tags);

                return $event;
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

    private const IMAGE_MAX_WIDTH = 1920;

    private const IMAGE_QUALITY = 80;

    /**
     * Compress, resize, and convert the uploaded image to WebP format.
     */
    private function compressAndStoreImage(UploadedFile $file): string
    {
        $image = Image::read($file)
            ->scaleDown(width: self::IMAGE_MAX_WIDTH);

        $filename = 'event-images/'.Str::uuid()->toString().'.webp';

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

    /**
     * @param  array<string, string>|null  $filters
     * @return array<EventResource>
     */
    public function getAssignedEvents(?User $user, EventType $eventType = EventType::Upcoming, ?array $filters = []): array
    {
        if (! $user) {
            return [];
        }

        $query = $user
            ->assignedEvents()
            ->with(['tags', 'author'])
            ->withCount('subscribers')
            ->where('start_at', $eventType->operator(), Carbon::now()->startOfDay());

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

        $events = $query->orderBy('start_at', $eventType->sortDirection())
            ->when($eventType === EventType::History, fn ($q) => $q->limit(self::HISTORY_EVENTS_LIMIT))
            ->get();

        return EventResource::collection($events)->resolve();
    }

    /**
     * @return array<EventResource>
     */
    public function getDeletedEvents(?User $user): array
    {
        if (! $user) {
            return [];
        }

        $query = $user
            ->assignedEvents()
            ->with(['tags', 'author'])
            ->withCount('subscribers');

        $events = $query->orderBy('deleted_at', 'desc')
            ->onlyTrashed()
            ->get();

        return EventResource::collection($events)->resolve();
    }

    public function restore(Event $event): Event
    {
        $event->restore();

        return $event;
    }
}
