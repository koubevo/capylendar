<?php

namespace App\Http\Controllers;

use App\Enums\Capybara;
use App\Http\Requests\Event\StoreEventRequest;
use App\Http\Requests\Event\UpdateEventRequest;
use App\Http\Resources\EventResource;
use App\Models\Event;
use App\services\EventService;
use App\services\EventTagService;
use App\services\TagService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class EventController extends Controller
{
    public function __construct(protected EventService $eventService, protected EventTagService $eventTagService, protected TagService $tagService) {}

    public function show(Event $event): Response
    {
        Gate::authorize('view', $event);

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
                Gate::authorize('view', $foundEvent);
                $event = new EventResource($foundEvent);
            }
        }

        return Inertia::render('events/EventCreate', [
            'capybaraOptions' => Capybara::options(),
            'event' => $event?->resolve(),
            'availableTags' => $this->tagService->getAvailableTags(),
        ]);
    }

    public function store(StoreEventRequest $request): RedirectResponse
    {
        $event = $this->eventService->store($request);

        return to_route('dashboard', ['scrollToDate' => $event->start_at->format('Y-m-d')])
            ->with('success', 'Event úspěšně přidán');
    }

    public function edit(Event $event): Response
    {
        Gate::authorize('update', $event);

        return Inertia::render('events/EventEdit', [
            'capybaraOptions' => Capybara::options(),
            'event' => EventResource::make($event)->resolve(),
            'availableTags' => $this->tagService->getAvailableTags(),
        ]);
    }

    public function update(Event $event, UpdateEventRequest $request): RedirectResponse
    {
        Gate::authorize('update', $event);

        $this->eventService->update($event, $request);

        return to_route('event.show', $event)->with('success', 'Event úspěšně aktualizován');
    }

    public function destroy(Event $event): RedirectResponse
    {
        Gate::authorize('delete', $event);

        $event->delete();

        return to_route('dashboard')->with('success', 'Event úspěšně smazán');
    }

    public function deletedIndex(): Response
    {
        $user = auth()->user();
        $deletedEvents = $this->eventService->getDeletedEvents($user);

        return Inertia::render('events/EventDeletedIndex', [
            'deletedEvents' => $deletedEvents,
        ]);
    }

    public function restore(Event $event): RedirectResponse
    {
        Gate::authorize('restore', $event);

        $this->eventService->restore($event);

        return to_route('event.deletedIndex')->with('success', 'Event úspěšně obnoven');
    }
}
