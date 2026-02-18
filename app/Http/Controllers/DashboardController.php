<?php

namespace App\Http\Controllers;

use App\Enums\Capybara;
use App\Enums\EventType;
use App\services\EventService;
use App\services\TagService;
use App\services\TodoService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(protected EventService $eventService, protected TodoService $todoService, protected TagService $tagService) {}

    public function __invoke(Request $request): Response
    {
        $user = auth()->user();

        $filters = $request->only(['search', 'capybara', 'tags']);

        return Inertia::render('Dashboard', [
            'upcomingEvents' => $this->eventService->getAssignedEvents($user, EventType::Upcoming, $filters),
            'unfinishedTodos' => $this->todoService->getAssignedTodos($user, finished: false, filters: $filters),
            'historyEvents' => $this->eventService->getAssignedEvents($user, EventType::History, $filters),
            'eventFilters' => $filters,
            'capybaraOptions' => Capybara::options(),
            'availableTags' => $this->tagService->getAvailableTags(),
            'scrollToDate' => $request->query('scrollToDate'),
        ]);
    }
}
