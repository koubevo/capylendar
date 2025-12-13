<?php

namespace App\Http\Controllers;

use App\Enums\Capybara;
use App\Enums\EventType;
use App\services\EventService;
use App\services\TagService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(protected EventService $eventService, protected TagService $tagService) {}

    public function __invoke(Request $request): Response
    {
        $user = auth()->user();

        $filters = $request->only(['search', 'capybara', 'tags']);

        return Inertia::render('Dashboard', [
            'upcomingEvents' => $this->eventService->getAssignedEvents($user, EventType::Upcoming, $filters),
            'historyEvents' => $this->eventService->getAssignedEvents($user, EventType::History, $filters),
            'filters' => $filters,
            'capybaraOptions' => Capybara::options(),
            'availableTags' => $this->tagService->getAvailableTags(),
        ]);
    }
}
