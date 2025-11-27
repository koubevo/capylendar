<?php

namespace App\services;

use App\Http\Requests\Event\StoreEventRequest;
use App\Models\Event;
use Illuminate\Support\Facades\DB;

class EventService
{
    public function __construct(protected EventUserService $eventUserService) {}

    public function store(StoreEventRequest $request): Event
    {
        $eventData = $request->safe()->except(['is_private']);
        $isPrivateEvent = $request->boolean('is_private');
        $author = $request->user();

        return DB::transaction(function () use ($author, $eventData, $isPrivateEvent) {
            $event = $author->authoredEvents()->create($eventData);

            $this->eventUserService->assignSubscribers($event, $isPrivateEvent, $author);

            return $event;
        });
    }
}
