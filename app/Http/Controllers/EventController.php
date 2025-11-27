<?php

namespace App\Http\Controllers;

use App\Enums\Capybara;
use App\Http\Requests\Event\StoreEventRequest;
use App\Http\Requests\Event\UpdateEventRequest;
use App\Models\Event;
use App\services\EventService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class EventController extends Controller
{
    public function __construct(protected EventService $eventService) {}

    public function create(): Response
    {
        return Inertia::render('events/EventCreate', [
            'capybaraOptions' => Capybara::options(),
        ]);
    }

    public function store(StoreEventRequest $request): RedirectResponse
    {
        $event = $this->eventService->store($request);

        return redirect('dashboard');
    }

    public function edit(UpdateEventRequest $event): void
    {
        //
    }

    public function update(UpdateEventRequest $request, Event $event): void
    {
        //
    }

    public function destroy(Event $event): void
    {
        //
    }
}
