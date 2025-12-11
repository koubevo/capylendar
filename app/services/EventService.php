<?php

namespace App\services;

use App\Enums\EventType;
use App\Http\Requests\Event\StoreEventRequest;
use App\Http\Requests\Event\UpdateEventRequest;
use App\Http\Resources\EventResource;
use App\Models\Event;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use shweshi\OpenGraph\OpenGraph;

class EventService
{
    public function __construct(protected EventUserService $eventUserService) {}

    private const HISTORY_EVENTS_LIMIT = 20;

    public function store(StoreEventRequest $request): ?Event
    {
        $eventData = $request->safe()->except(['is_private']);
        $isPrivateEvent = $request->boolean('is_private');
        $author = $request->user();

        if (! $author) {
            return null;
        }

        $eventData['meta'] = $this->resolveMetadata($eventData['description']);

        return DB::transaction(function () use ($author, $eventData, $isPrivateEvent) {
            $event = $author->authoredEvents()->create($eventData);

            $this->eventUserService->assignSubscribers($event, $isPrivateEvent, $author);

            return $event;
        });
    }

    public function update(Event $event, UpdateEventRequest $request): ?Event
    {
        $eventData = $request->safe()->except(['is_private']);
        $isPrivateEvent = $request->boolean('is_private');
        $author = $request->user();

        if (! $author) {
            return null;
        }

        $eventData['meta'] = $this->resolveMetadata($eventData['description']);

        return DB::transaction(function () use ($author, $eventData, $isPrivateEvent, $event) {
            $event->update($eventData);

            $this->eventUserService->assignSubscribers($event, $isPrivateEvent, $author);

            return $event;
        });
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
            $meta['map_preview'] = $mapPreview;
        }

        return $meta ?? null;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function resolveMapPreview(string $description): ?array
    {
        $pattern = '/https?:\/\/(?:www\.)?(?:google\.(?:com|cz)\/maps\/[^\s]+|maps\.app\.goo\.gl\/[^\s]+)/';

        if (! preg_match($pattern, $description, $matches)) {
            return null;
        }

        $mapUrl = $matches[0];

        try {
            $og = new OpenGraph;
            $data = $og->fetch($mapUrl);

            $result = [];
            $result['title'] = $data['title'];
            $result['image'] = $data['image'];

            if (! $result['title'] || ! $result['image']) {
                return null;
            }

            $result['url'] = $mapUrl;

            return $result;
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * @return array<EventResource>
     */
    public function getAssignedEvents(?User $user, EventType $eventType = EventType::Upcoming): array
    {
        if (! $user) {
            return [];
        }

        $events = $user
            ->assignedEvents()
            ->where('start_at', $eventType->operator(), Carbon::now()->startOfDay())
            ->orderBy('start_at', $eventType->sortDirection())
            ->when($eventType === EventType::History, fn ($q) => $q->limit(self::HISTORY_EVENTS_LIMIT))
            ->get();

        return EventResource::collection($events)->resolve();
    }
}
