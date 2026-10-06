<?php

namespace App\Services;

use App\Concerns\ResolvesOpenGraphMetadata;
use App\Enums\EventKind;
use App\Enums\EventType;
use App\Http\Requests\Event\StoreEventRequest;
use App\Http\Requests\Event\UpdateEventRequest;
use App\Http\Resources\EventResource;
use App\Models\Event;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\ImageManager;
use Throwable;

class EventService
{
    use ResolvesOpenGraphMetadata;

    public function __construct(
        protected EventUserService $eventUserService,
        protected EventTagService $eventTagService,
        protected EventSurpriseService $eventSurpriseService,
    ) {}

    private const NON_ATTRIBUTE_INPUTS = [
        'is_private',
        'tags',
        'image',
        'remove_image',
        'surprise_image',
        'surprise_password',
        'surprise_hint',
        'remove_surprise_image',
    ];

    private const IMAGE_DIRECTORY = 'event-images';

    private const SURPRISE_IMAGE_DIRECTORY = 'event-surprise-images';

    private const HISTORY_EVENTS_PER_PAGE = 20;

    public function store(StoreEventRequest $request): ?Event
    {
        $eventData = $request->safe()->except(self::NON_ATTRIBUTE_INPUTS);
        $isPrivateEvent = $request->boolean('is_private');
        $author = $request->user();

        if (! $author) {
            return null;
        }

        /** @var array<int> $tags */
        $tags = $request->input('tags', []);
        $eventData['meta'] = $this->resolveMetadata($eventData['description'] ?? null);

        $newImagePath = null;
        $newSurpriseImagePath = null;

        try {
            if ($request->hasFile('image')) {
                $newImagePath = $this->compressAndStoreImage($request->file('image'), self::IMAGE_DIRECTORY);
                $eventData['image_path'] = $newImagePath;
            }

            if ($request->hasFile('surprise_image')) {
                $newSurpriseImagePath = $this->compressAndStoreImage($request->file('surprise_image'), self::SURPRISE_IMAGE_DIRECTORY);
                $eventData = [...$eventData, ...$this->surpriseAttributes($request, $newSurpriseImagePath)];
            }

            return DB::transaction(function () use ($author, $eventData, $isPrivateEvent, $tags) {
                $event = $author->authoredEvents()->create($eventData);

                $this->eventUserService->assignSubscribers($event, $isPrivateEvent, $author);

                $this->eventTagService->assignTags($event, $tags);

                return $event;
            });
        } catch (Throwable $e) {
            $this->deleteImages($newImagePath, $newSurpriseImagePath);

            throw $e;
        }
    }

    public function update(Event $event, UpdateEventRequest $request): ?Event
    {
        $eventData = $request->safe()->except(self::NON_ATTRIBUTE_INPUTS);
        $isPrivateEvent = $request->boolean('is_private');
        $author = $request->user();

        if (! $author) {
            return null;
        }

        /** @var array<int> $tags */
        $tags = $request->input('tags', []);
        $eventData['meta'] = $this->resolveMetadata($eventData['description'] ?? null);

        $oldImagePath = $event->image_path;
        $newImagePath = null;
        $oldSurpriseImagePath = $event->surprise_image_path;
        $newSurpriseImagePath = null;
        $allowsSurprise = EventKind::from($eventData['kind'])->allowsSurprise();

        try {
            if ($request->hasFile('image')) {
                $newImagePath = $this->compressAndStoreImage($request->file('image'), self::IMAGE_DIRECTORY);
                $eventData['image_path'] = $newImagePath;
            } elseif ($request->boolean('remove_image') && $oldImagePath) {
                $eventData['image_path'] = null;
            }

            if ($request->hasFile('surprise_image')) {
                $newSurpriseImagePath = $this->compressAndStoreImage($request->file('surprise_image'), self::SURPRISE_IMAGE_DIRECTORY);
                $eventData = [...$eventData, ...$this->surpriseAttributes($request, $newSurpriseImagePath)];
            } elseif (! $allowsSurprise || ! $oldSurpriseImagePath || $request->boolean('remove_surprise_image')) {
                $eventData = [...$eventData, 'surprise_image_path' => null, 'surprise_password' => null, 'surprise_hint' => null];
            } elseif ($request->has('surprise_hint')) {
                $eventData['surprise_hint'] = $request->input('surprise_hint');
            }

            $result = DB::transaction(function () use ($author, $eventData, $isPrivateEvent, $event, $tags) {
                $event->update($eventData);

                $this->eventUserService->assignSubscribers($event, $isPrivateEvent, $author);

                $this->eventTagService->assignTags($event, $tags);

                return $event;
            });

        } catch (Throwable $e) {
            $this->deleteImages($newImagePath, $newSurpriseImagePath);

            throw $e;
        }

        if ($oldImagePath && ($request->hasFile('image') || $request->boolean('remove_image'))) {
            Storage::disk()->delete($oldImagePath);
        }

        if ($oldSurpriseImagePath && $event->surprise_image_path !== $oldSurpriseImagePath) {
            Storage::disk()->delete($oldSurpriseImagePath);
        }

        return $result;
    }

    /**
     * @return array{surprise_image_path: string, surprise_password: string, surprise_hint: string|null}
     */
    private function surpriseAttributes(StoreEventRequest|UpdateEventRequest $request, string $surpriseImagePath): array
    {
        $hint = $request->input('surprise_hint');

        return [
            'surprise_image_path' => $surpriseImagePath,
            'surprise_password' => $this->eventSurpriseService->hashPassword($request->string('surprise_password')->toString()),
            'surprise_hint' => is_string($hint) && $hint !== '' ? $hint : null,
        ];
    }

    private function deleteImages(?string ...$paths): void
    {
        foreach (array_filter($paths) as $path) {
            Storage::disk()->delete($path);
        }
    }

    private const IMAGE_MAX_WIDTH = 1920;

    private const IMAGE_QUALITY = 80;

    /**
     * Compress, resize, and convert the uploaded image to WebP format.
     */
    private function compressAndStoreImage(UploadedFile $file, string $directory): string
    {
        $manager = new ImageManager(Config::string('image.driver'), ...Config::array('image.options', []));
        $image = $manager->read($file)
            ->scaleDown(width: self::IMAGE_MAX_WIDTH);

        $filename = $directory.'/'.Str::uuid()->toString().'.webp';

        Storage::disk()->put(
            $filename,
            $image->toWebp(quality: self::IMAGE_QUALITY)->toString()
        );

        return $filename;
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
            ->orderBy('is_all_day', 'desc')
            ->orderBy('title', 'asc')
            ->when($eventType === EventType::History, fn ($q) => $q->limit(self::HISTORY_EVENTS_PER_PAGE))
            ->get();

        return EventResource::collection($events)->resolve();
    }

    /**
     * @param  array<string, mixed>|null  $filters
     */
    public function paginateAssignedEvents(
        User $user,
        EventType $eventType = EventType::History,
        ?array $filters = [],
    ): AnonymousResourceCollection {
        $query = $user
            ->assignedEvents()
            ->with(['tags', 'author'])
            ->withCount('subscribers')
            ->where('start_at', $eventType->operator(), Carbon::now()->startOfDay());

        if ($filters) {
            $search = $filters['search'] ?? null;
            if (is_string($search) && $search !== '') {
                $operator = DB::connection()->getDriverName() === 'sqlite' ? 'like' : 'ilike';
                $query->where(function (Builder $query) use ($search, $operator) {
                    $query->where('title', $operator, "%{$search}%")
                        ->orWhere('description', $operator, "%{$search}%");
                });
            }

            if (! empty($filters['capybara'])) {
                $query->where('capybara', $filters['capybara']);
            }

            if (! empty($filters['tags'])) {
                $query->whereHas('tags', function (Builder $query) use ($filters) {
                    $query->whereIn('tags.id', $filters['tags']);
                });
            }
        }

        $events = $query->orderBy('start_at', $eventType->sortDirection())
            ->orderBy('is_all_day', 'desc')
            ->orderBy('title', 'asc')
            ->paginate(self::HISTORY_EVENTS_PER_PAGE)
            ->withQueryString();

        return EventResource::collection($events);
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
