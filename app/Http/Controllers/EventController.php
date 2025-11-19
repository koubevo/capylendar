<?php

namespace App\Http\Controllers;

use App\Enums\Capybara;
use App\Http\Requests\StoreEventRequest;
use App\Http\Requests\UpdateEventRequest;
use App\Models\Event;
use Inertia\Inertia;
use Inertia\Response;

class EventController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('events/EventCreate', [
            'capybaraOptions' => Capybara::options(),
        ]);
    }

    public function store(StoreEventRequest $request): void
    {
        //
    }

    public function edit(Event $event): void
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
