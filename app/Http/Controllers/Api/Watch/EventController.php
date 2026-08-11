<?php

namespace App\Http\Controllers\Api\Watch;

use App\Concerns\FormatsHumanDates;
use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EventController extends Controller
{
    use FormatsHumanDates;

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            abort(401);
        }

        $events = $user->assignedEvents()
            ->where(function (Builder $query): void {
                $query->where('start_at', '>=', now()->startOfDay())
                    ->orWhere('end_at', '>=', now());
            })
            ->orderBy('start_at')
            ->orderByDesc('is_all_day')
            ->limit(20)
            ->get()
            ->map(fn (Event $event): array => [
                'id' => $event->id,
                'title' => $event->title,
                'date' => [
                    'key' => $event->start_at->format('Y-m-d'),
                    'label' => $this->humanDateLabel($event->start_at),
                    'start_time' => $event->is_all_day ? '' : $event->start_at->format('H:i'),
                    'end_time' => $event->is_all_day ? '' : ($event->end_at?->format('H:i') ?? ''),
                    'is_all_day' => $event->is_all_day,
                ],
                'capybara' => [
                    'value' => $event->capybara->value,
                    'label' => $event->capybara->info()['label'],
                ],
            ])
            ->values();

        return response()->json(['data' => $events]);
    }
}
