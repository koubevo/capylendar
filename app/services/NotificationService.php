<?php

namespace App\services;

use App\Http\Resources\EventResource;
use App\Models\Event;
use App\Models\User;
use App\Notifications\DailyEventsNotification;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Throwable;

class NotificationService
{
    /**
     * Send evening notifications (tomorrow's events).
     *
     * @return array{users_notified: int, errors: int}
     */
    public function sendEveningNotifications(): array
    {
        return $this->sendNotifications(fn (User $user) => $this->getEveningNotificationContent($user));
    }

    /**
     * Send morning notifications (today's events).
     *
     * @return array{users_notified: int, errors: int}
     */
    public function sendMorningNotifications(): array
    {
        return $this->sendNotifications(fn (User $user) => $this->getMorningNotificationContent($user));
    }

    /**
     * @deprecated Use sendEveningNotifications() instead
     *
     * @return array{users_notified: int, errors: int}
     */
    public function sendDailyNotifications(): array
    {
        return $this->sendEveningNotifications();
    }

    /**
     * @param  callable(User): ?array  $contentResolver
     * @return array{users_notified: int, errors: int}
     */
    private function sendNotifications(callable $contentResolver): array
    {
        $users = User::query()
            ->where('notifications_enabled', true)
            ->whereHas('pushSubscriptions')
            ->get();

        $usersNotified = 0;
        $errors = 0;

        foreach ($users as $user) {
            try {
                $notificationData = $contentResolver($user);

                if ($notificationData) {
                    $user->notify(new DailyEventsNotification(
                        events: $notificationData['events'],
                        title: $notificationData['title'],
                        body: $notificationData['body'],
                        actionUrl: $notificationData['actionUrl'] ?? null
                    ));
                    $usersNotified++;
                }
            } catch (Throwable $e) {
                Log::error('Failed to send notification to user', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);
                $errors++;
            }
        }

        return [
            'users_notified' => $usersNotified,
            'errors' => $errors,
        ];
    }

    /**
     * Get evening notification content (tomorrow's events).
     *
     * @return array{events: array<EventResource>, title: string, body: string, actionUrl: string|null}|null
     */
    public function getEveningNotificationContent(User $user): ?array
    {
        $tomorrow = Carbon::tomorrow()->startOfDay();
        $tomorrowEvents = $this->getEventsForDate($user, $tomorrow);

        if (count($tomorrowEvents) > 0) {
            return $this->buildTomorrowNotification($tomorrowEvents);
        }

        $nextEventDay = $this->getNextDayWithEvents($user, $tomorrow);

        if ($nextEventDay) {
            $nextEvents = $this->getEventsForDate($user, $nextEventDay['date']);

            return $this->buildNoEventsTomorrowNotification($nextEvents, $nextEventDay['date']);
        }

        return null;
    }

    /**
     * Get morning notification content (today's events).
     *
     * @return array{events: array<EventResource>, title: string, body: string, actionUrl: string|null}|null
     */
    public function getMorningNotificationContent(User $user): ?array
    {
        $today = Carbon::today()->startOfDay();
        $todayEvents = $this->getEventsForDate($user, $today);

        if (empty($todayEvents)) {
            return null;
        }

        return $this->buildTodayNotification($todayEvents);
    }

    /**
     * @deprecated Use getEveningNotificationContent() instead
     */
    public function getNotificationContent(User $user): ?array
    {
        return $this->getEveningNotificationContent($user);
    }

    /**
     * @return array<EventResource>
     */
    public function getEventsForDate(User $user, Carbon $date): array
    {
        $events = $user
            ->assignedEvents()
            ->with(['tags', 'author'])
            ->whereDate('start_at', $date->toDateString())
            ->orderBy('start_at')
            ->get();

        return EventResource::collection($events)->resolve();
    }

    /**
     * Find the closest future date with events.
     *
     * @return array{date: Carbon, event: Event}|null
     */
    public function getNextDayWithEvents(User $user, Carbon $afterDate): ?array
    {
        $event = $user
            ->assignedEvents()
            ->where('start_at', '>', $afterDate->endOfDay())
            ->orderBy('start_at')
            ->first();

        if (! $event) {
            return null;
        }

        return [
            'date' => $event->start_at->startOfDay(),
            'event' => $event,
        ];
    }

    private function buildTodayNotification(array $events): array
    {
        return $this->buildEventListNotification($events, 'Dnes: ');
    }

    /**
     * @param  array<EventResource>  $events
     * @param  string  $titlePrefix
     * @return array{events: array<EventResource>, title: string, body: string, actionUrl: string|null}
     */
    private function buildEventListNotification(array $events, string $titlePrefix): array
    {
        $eventCount = count($events);
        $title = $titlePrefix.$eventCount.' '.trans_choice('event|eventy|eventů', $eventCount);

        $eventTitles = array_map(fn ($event) => $event['title'], array_slice($events, 0, 3));
        $body = implode(', ', $eventTitles);

        if ($eventCount > 3) {
            $body .= ' a další...';
        }

        return [
            'events' => $events,
            'title' => $title,
            'body' => $body,
            'actionUrl' => null,
        ];
    }

    /**
     * @param  array<EventResource>  $events
     * @return array{events: array<EventResource>, title: string, body: string, actionUrl: string|null}
     */
    private function buildTomorrowNotification(array $events): array
    {
        return $this->buildEventListNotification($events, 'Zítra: ');
    }

    /**
     * @param  array<EventResource>  $events
     * @return array{events: array<EventResource>, title: string, body: string, actionUrl: string|null}
     */
    private function buildNoEventsTomorrowNotification(array $events, Carbon $nextDate): array
    {
        $daysUntil = Carbon::tomorrow()->diffInDays($nextDate);
        $dateFormatted = $nextDate->translatedFormat('l j. F');

        $title = 'Zítra nemáte žádné eventy';
        $body = "Další event: {$dateFormatted}";

        if ($daysUntil <= 7) {
            $body = "Další event za {$daysUntil} ".$this->pluralizeDays($daysUntil).': '.$events[0]['title'];
        }

        return [
            'events' => $events,
            'title' => $title,
            'body' => $body,
            'actionUrl' => null,
        ];
    }

    private function pluralizeDays(int $count): string
    {
        if ($count === 1) {
            return 'den';
        }
        if ($count >= 2 && $count <= 4) {
            return 'dny';
        }

        return 'dní';
    }
}
