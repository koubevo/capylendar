<?php

namespace App\Http\Controllers;

use App\Enums\EventType;
use App\services\EventService;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(protected EventService $eventService) {}

    public function __invoke(): Response
    {
        return Inertia::render('Dashboard', [
            'upcomingEvents' => $this->eventService->getAssignedEvents(auth()->user(), EventType::Upcoming),
            'historyEvents' => $this->eventService->getAssignedEvents(auth()->user(), EventType::History),
        ]);
    }
}
