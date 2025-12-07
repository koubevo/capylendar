<?php

namespace App\Http\Controllers;

use App\Enums\Capybara;
use App\Http\Requests\Event\StoreEventRequest;
use App\Http\Requests\Event\UpdateEventRequest;
use App\Http\Resources\EventResource;
use App\Models\Event;
use App\services\EventService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EventController extends Controller
{
    public function __construct(protected EventService $eventService) {}

    public function show(Event $event): Response
    {
        return Inertia::render('events/EventShow', [
            'event' => EventResource::make($event)->resolve(),
        ]);
    }

    public function create(Request $request): Response
    {
        $event = null;

        if ($request->has('duplicate_event_id')) {
            $foundEvent = Event::findOrFail($request->input('duplicate_event_id'));
            if ($foundEvent instanceof Event) {
                $event = new EventResource($foundEvent);
            }
        }

        return Inertia::render('events/EventCreate', [
            'capybaraOptions' => Capybara::options(),
            'event' => $event?->resolve(),
        ]);
    }

    public function store(StoreEventRequest $request): RedirectResponse
    {
        $event = $this->eventService->store($request);

        return redirect('dashboard');
    }

    public function edit(Event $event): Response
    {
        return Inertia::render('events/EventEdit', [
            'capybaraOptions' => Capybara::options(),
            'event' => EventResource::make($event)->resolve(),
        ]);
    }

    public function update(Event $event, UpdateEventRequest $request): RedirectResponse
    {
        $this->eventService->update($event, $request);

        return to_route('event.show', $event);
    }

    public function destroy(Event $event): RedirectResponse
    {
        $event->delete();

        return to_route('dashboard');
    }
}
