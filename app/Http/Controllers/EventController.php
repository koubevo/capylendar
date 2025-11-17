<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEventRequest;
use App\Http\Requests\UpdateEventRequest;
use App\Models\Event;

class EventController extends Controller
{
    public function create(): void
    {
        //
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
