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
        $cases = Capybara::cases();

        $capybaraOptions = collect($cases)->map(function ($case) {
            return [
                'value' => $case->value,
                'label' => $case->getLabel(),
                'avatar' => $case->getAvatar(),
            ];
        });

        return Inertia::render('events/EventCreate', [
            'capybaraOptions' => $capybaraOptions,
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
