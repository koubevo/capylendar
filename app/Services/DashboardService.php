<?php

namespace App\Services;

use App\Http\Resources\EventResource;
use App\Http\Resources\TodoResource;
use App\Models\Event;
use App\Models\Todo;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Throwable;

class DashboardService
{
    private const PAGE_NAME = 'dashboard';

    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, array{
     *     key: string,
     *     label: string,
     *     events: array<int, array<string, mixed>>,
     *     todos: array<int, array<string, mixed>>
     * }>
     */
    public function paginate(
        User $user,
        array $filters,
        ?int $requestedPage,
        ?string $scrollToDate,
        string $path,
    ): LengthAwarePaginator {
        $today = Carbon::today();
        $firstMonth = $today->copy()->startOfMonth();
        $lastMonth = $this->resolveLastMonth($user, $filters, $firstMonth);
        $totalMonths = max(1, (int) $firstMonth->diffInMonths($lastMonth) + 1);
        $currentPage = $this->resolveCurrentPage(
            $requestedPage,
            $scrollToDate,
            $firstMonth,
            $totalMonths,
        );

        $monthStart = $firstMonth->copy()->addMonths($currentPage - 1);
        $periodStart = $currentPage === 1
            ? $today->copy()->startOfDay()
            : $monthStart->copy()->startOfMonth();
        $periodEnd = $monthStart->copy()->endOfMonth();

        $month = [
            'key' => $monthStart->format('Y-m'),
            'label' => ucfirst($monthStart->translatedFormat('F Y')),
            'events' => $this->eventsForPeriod($user, $filters, $periodStart, $periodEnd),
            'todos' => $this->todosForPeriod(
                $user,
                $filters,
                $currentPage === 1 ? null : $periodStart,
                $periodEnd,
            ),
        ];

        return (new LengthAwarePaginator(
            items: [$month],
            total: $totalMonths,
            perPage: 1,
            currentPage: $currentPage,
            options: [
                'path' => $path,
                'pageName' => self::PAGE_NAME,
            ],
        ))->withQueryString();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function resolveLastMonth(User $user, array $filters, Carbon $firstMonth): Carbon
    {
        $latestEventDate = $this->eventQuery($user, $filters)
            ->where('start_at', '>=', Carbon::today())
            ->max('start_at');
        $latestTodoDate = $this->todoQuery($user, $filters)
            ->whereNull('finished_at')
            ->max('deadline');

        $lastDate = $firstMonth->copy();

        foreach ([$latestEventDate, $latestTodoDate] as $date) {
            if (! is_string($date)) {
                continue;
            }

            $candidate = Carbon::parse($date);
            if ($candidate->greaterThan($lastDate)) {
                $lastDate = $candidate;
            }
        }

        return $lastDate->startOfMonth();
    }

    private function resolveCurrentPage(
        ?int $requestedPage,
        ?string $scrollToDate,
        Carbon $firstMonth,
        int $totalMonths,
    ): int {
        if ($requestedPage !== null) {
            return min(max($requestedPage, 1), $totalMonths);
        }

        if ($scrollToDate === null || $scrollToDate === '') {
            return 1;
        }

        try {
            $targetMonth = Carbon::parse($scrollToDate)->startOfMonth();
        } catch (Throwable) {
            return 1;
        }

        if ($targetMonth->lessThanOrEqualTo($firstMonth)) {
            return 1;
        }

        return min(
            (int) $firstMonth->diffInMonths($targetMonth) + 1,
            $totalMonths,
        );
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<int, array<string, mixed>>
     */
    private function eventsForPeriod(
        User $user,
        array $filters,
        Carbon $periodStart,
        Carbon $periodEnd,
    ): array {
        $events = $this->eventQuery($user, $filters)
            ->with(['tags', 'author'])
            ->withCount('subscribers')
            ->whereBetween('start_at', [$periodStart, $periodEnd])
            ->orderBy('start_at')
            ->orderBy('is_all_day', 'desc')
            ->orderBy('title')
            ->get();

        return EventResource::collection($events)->resolve();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<int, array<string, mixed>>
     */
    private function todosForPeriod(
        User $user,
        array $filters,
        ?Carbon $periodStart,
        Carbon $periodEnd,
    ): array {
        $query = $this->todoQuery($user, $filters)
            ->with(['tags', 'author'])
            ->withCount('subscribers')
            ->whereNull('finished_at')
            ->where('deadline', '<=', $periodEnd);

        if ($periodStart !== null) {
            $query->where('deadline', '>=', $periodStart);
        }

        $todos = $query->get()
            ->sort(function (Todo $first, Todo $second): int {
                $deadlineComparison = $first->deadline->getTimestamp()
                    <=> $second->deadline->getTimestamp();

                if ($deadlineComparison !== 0) {
                    return $deadlineComparison;
                }

                $priorityComparison = $first->priority->sortWeight()
                    <=> $second->priority->sortWeight();

                if ($priorityComparison !== 0) {
                    return $priorityComparison;
                }

                return strnatcasecmp($first->title, $second->title);
            })
            ->values();

        return TodoResource::collection($todos)->resolve();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return BelongsToMany<Event, User>
     */
    private function eventQuery(User $user, array $filters): BelongsToMany
    {
        $query = $user->assignedEvents();
        $this->applyFilters($query, $filters);

        return $query;
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return BelongsToMany<Todo, User>
     */
    private function todoQuery(User $user, array $filters): BelongsToMany
    {
        $query = $user->assignedTodos();
        $this->applyFilters($query, $filters);

        return $query;
    }

    /**
     * @param  Builder<Event>|Builder<Todo>|BelongsToMany<Event, User>|BelongsToMany<Todo, User>  $query
     * @param  array<string, mixed>  $filters
     */
    private function applyFilters(Builder|BelongsToMany $query, array $filters): void
    {
        $search = $filters['search'] ?? null;
        if (is_string($search) && $search !== '') {
            $operator = DB::connection()->getDriverName() === 'sqlite' ? 'like' : 'ilike';
            $query->where(function (Builder $query) use ($search, $operator): void {
                $query->where('title', $operator, "%{$search}%")
                    ->orWhere('description', $operator, "%{$search}%");
            });
        }

        $capybara = $filters['capybara'] ?? null;
        if (is_string($capybara) && $capybara !== '') {
            $query->where('capybara', $capybara);
        }

        $tags = $filters['tags'] ?? null;
        if (is_array($tags) && $tags !== []) {
            $query->whereHas('tags', function (Builder $query) use ($tags): void {
                $query->whereIn('tags.id', $tags);
            });
        }
    }
}
