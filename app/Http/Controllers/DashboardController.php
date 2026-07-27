<?php

namespace App\Http\Controllers;

use App\Enums\Capybara;
use App\Enums\EventType;
use App\Models\User;
use App\Services\DashboardService;
use App\Services\EventService;
use App\Services\TagService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(
        protected DashboardService $dashboardService,
        protected EventService $eventService,
        protected TagService $tagService,
    ) {}

    public function __invoke(Request $request): Response
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401);

        $filters = $request->only(['search', 'capybara', 'tags']);
        $scrollToDate = $request->string('scrollToDate')->toString() ?: null;
        $requestedPage = $request->has('dashboard')
            ? max($request->integer('dashboard'), 1)
            : null;

        return Inertia::render('Dashboard', [
            'dashboardMonths' => Inertia::scroll(fn () => $this->dashboardService->paginate(
                user: $user,
                filters: $filters,
                requestedPage: $requestedPage,
                scrollToDate: $scrollToDate,
                path: $request->url(),
            )),
            'eventFilters' => $filters,
            'capybaraOptions' => Capybara::options(),
            'availableTags' => $this->tagService->getAvailableTags(),
            'scrollToDate' => $scrollToDate,
            'highlightEvent' => $request->query('highlightEvent') ? (int) $request->query('highlightEvent') : null,
            'highlightTodo' => $request->query('highlightTodo') ? (int) $request->query('highlightTodo') : null,
        ]);
    }

    public function historyIndex(Request $request): Response
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401);

        $filters = $request->only(['search', 'capybara', 'tags']);

        return Inertia::render('events/EventHistoryIndex', [
            'historyEvents' => Inertia::scroll(fn () => $this->eventService->paginateAssignedEvents($user, EventType::History, $filters)),
            'eventFilters' => $filters,
            'capybaraOptions' => Capybara::options(),
            'availableTags' => $this->tagService->getAvailableTags(),
        ]);
    }
}
