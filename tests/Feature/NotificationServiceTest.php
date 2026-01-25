<?php

use App\Models\Event;
use App\Models\User;
use App\services\NotificationService;
use Carbon\Carbon;

beforeEach(function () {
    $this->service = new NotificationService;
});

describe('NotificationService', function () {
    describe('getEventsForDate', function () {
        it('returns events for a specific date', function () {
            $user = User::factory()->create();
            $tomorrow = Carbon::tomorrow()->startOfDay();

            $event = Event::factory()->create([
                'author_id' => $user->id,
                'start_at' => $tomorrow->copy()->setHour(10),
            ]);
            $user->assignedEvents()->attach($event->id);

            $events = $this->service->getEventsForDate($user, $tomorrow);

            expect($events)->toHaveCount(1);
            expect($events[0]['title'])->toBe($event->title);
        });

        it('returns empty array when no events for date', function () {
            $user = User::factory()->create();
            $tomorrow = Carbon::tomorrow()->startOfDay();

            $events = $this->service->getEventsForDate($user, $tomorrow);

            expect($events)->toBeEmpty();
        });

        it('only returns events the user is assigned to', function () {
            $user = User::factory()->create();
            $otherUser = User::factory()->pink()->create();
            $tomorrow = Carbon::tomorrow()->startOfDay();

            // Event for other user
            $otherEvent = Event::factory()->create([
                'author_id' => $otherUser->id,
                'start_at' => $tomorrow->copy()->setHour(10),
            ]);
            $otherUser->assignedEvents()->attach($otherEvent->id);

            $events = $this->service->getEventsForDate($user, $tomorrow);

            expect($events)->toBeEmpty();
        });
    });

    describe('getNextDayWithEvents', function () {
        it('returns the next day with events', function () {
            $user = User::factory()->create();
            $tomorrow = Carbon::tomorrow()->startOfDay();
            $inThreeDays = Carbon::tomorrow()->addDays(2)->setHour(14);

            $event = Event::factory()->create([
                'author_id' => $user->id,
                'start_at' => $inThreeDays,
            ]);
            $user->assignedEvents()->attach($event->id);

            $result = $this->service->getNextDayWithEvents($user, $tomorrow);

            expect($result)->not->toBeNull();
            expect($result['date']->toDateString())->toBe($inThreeDays->toDateString());
            expect($result['event']->id)->toBe($event->id);
        });

        it('returns null when no future events', function () {
            $user = User::factory()->create();
            $tomorrow = Carbon::tomorrow()->startOfDay();

            $result = $this->service->getNextDayWithEvents($user, $tomorrow);

            expect($result)->toBeNull();
        });
    });

    describe('getNotificationContent', function () {
        it('returns tomorrow notification when events exist', function () {
            $user = User::factory()->create();
            $tomorrow = Carbon::tomorrow()->startOfDay();

            $event = Event::factory()->create([
                'author_id' => $user->id,
                'title' => 'Test Event',
                'start_at' => $tomorrow->copy()->setHour(10),
            ]);
            $user->assignedEvents()->attach($event->id);

            $content = $this->service->getNotificationContent($user);

            expect($content)->not->toBeNull();
            expect($content['title'])->toContain('Zítra');
            expect($content['body'])->toContain('Test Event');
        });

        it('returns no events tomorrow notification when only future events exist', function () {
            $user = User::factory()->create();
            $inThreeDays = Carbon::tomorrow()->addDays(2)->setHour(14);

            $event = Event::factory()->create([
                'author_id' => $user->id,
                'title' => 'Future Event',
                'start_at' => $inThreeDays,
            ]);
            $user->assignedEvents()->attach($event->id);

            $content = $this->service->getNotificationContent($user);

            expect($content)->not->toBeNull();
            expect($content['title'])->toBe('Zítra nemáte žádné eventy');
        });

        it('returns null when no upcoming events', function () {
            $user = User::factory()->create();

            $content = $this->service->getNotificationContent($user);

            expect($content)->toBeNull();
        });
    });
});
