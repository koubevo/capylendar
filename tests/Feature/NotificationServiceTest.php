<?php

use App\Models\Event;
use App\Models\User;
use App\services\NotificationService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Notification;

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

        it('handles more than 3 events for tomorrow', function () {
            $user = User::factory()->create();
            $tomorrow = Carbon::tomorrow()->startOfDay();

            $events = Event::factory()->count(4)->create([
                'author_id' => $user->id,
                'start_at' => $tomorrow->copy()->setHour(10),
            ]);
            $user->assignedEvents()->attach($events->pluck('id'));

            $content = $this->service->getNotificationContent($user);

            expect($content['body'])->toContain('a další...');
        });

        it('formats plural logic correctly 1 day', function () {
            $user = User::factory()->create();
            // Tomorrow is 0 days away. We need event in 2 days from tomorrow -> 1 day gap
            $targetDate = Carbon::tomorrow()->addDays(1)->setHour(10); // 1 day from tomorrow

            $event = Event::factory()->create([
                'author_id' => $user->id,
                'start_at' => $targetDate,
            ]);
            $user->assignedEvents()->attach($event->id);

            $content = $this->service->getNotificationContent($user);
            // "Další event za 1 den"
            expect($content['body'])->toContain('1 den');
        });

        it('formats plural logic correctly 2 days', function () {
            $user = User::factory()->create();
            $targetDate = Carbon::tomorrow()->addDays(2)->setHour(10); // 2 days from tomorrow

            $event = Event::factory()->create([
                'author_id' => $user->id,
                'start_at' => $targetDate,
            ]);
            $user->assignedEvents()->attach($event->id);

            $content = $this->service->getNotificationContent($user);
            expect($content['body'])->toContain('2 dny');
        });

        it('formats plural logic correctly 5 days', function () {
            $user = User::factory()->create();
            $targetDate = Carbon::tomorrow()->addDays(5)->setHour(10); // 5 days from tomorrow

            $event = Event::factory()->create([
                'author_id' => $user->id,
                'start_at' => $targetDate,
            ]);
            $user->assignedEvents()->attach($event->id);

            $content = $this->service->getNotificationContent($user);
            expect($content['body'])->toContain('5 dní');
        });
    });

    describe('sendEveningNotifications', function () {
        it('sends notifications to subscribed users', function () {
            $user = User::factory()->create(['notifications_enabled' => true]);
            $user->updatePushSubscription('http://endpoint', 'key', 'auth');
            
            // Add event tomorrow
            $tomorrow = Carbon::tomorrow()->startOfDay();
            $event = Event::factory()->create([
                'author_id' => $user->id,
                'start_at' => $tomorrow->copy()->setHour(10),
            ]);
            $user->assignedEvents()->attach($event->id);
            
            Notification::fake();
            
            $this->service->sendEveningNotifications();
            
            Notification::assertSentTo($user, App\Notifications\DailyEventsNotification::class);
        });
    });

    describe('getMorningNotificationContent', function () {
        it('returns content for today events', function () {
            $user = User::factory()->create();
            $today = Carbon::today()->startOfDay();

            $event = Event::factory()->create([
                'author_id' => $user->id,
                'title' => 'Today Event',
                'start_at' => $today->copy()->setHour(10),
            ]);
            $user->assignedEvents()->attach($event->id);
            
            $content = $this->service->getMorningNotificationContent($user);
            
            expect($content)->not->toBeNull();
            expect($content['title'])->toContain('Dnes:');
            expect($content['body'])->toContain('Today Event');
        });

        it('returns null if no events today', function () {
            $user = User::factory()->create();
            
            $content = $this->service->getMorningNotificationContent($user);
            
            expect($content)->toBeNull();
        });
    });

    describe('sendMorningNotifications', function () {
        it('sends notifications to subscribed users', function () {
            $user = User::factory()->create(['notifications_enabled' => true]);
            $user->updatePushSubscription('http://endpoint', 'key', 'auth');
            
            // Add event today
            $today = Carbon::today()->startOfDay();
            $event = Event::factory()->create([
                'author_id' => $user->id,
                'start_at' => $today->copy()->setHour(10),
            ]);
            $user->assignedEvents()->attach($event->id);
            
            Notification::fake();
            
            $this->service->sendMorningNotifications();
            
            Notification::assertSentTo($user, App\Notifications\DailyEventsNotification::class);
        });
    });

    describe('sendDailyNotifications', function () {
        it('sends notifications (deprecated alias)', function () {
            $user = User::factory()->create(['notifications_enabled' => true]);
            $user->updatePushSubscription('http://endpoint', 'key', 'auth');
            $tomorrow = Carbon::tomorrow()->startOfDay();
            $event = Event::factory()->create([
                'author_id' => $user->id,
                'start_at' => $tomorrow->copy()->setHour(10),
            ]);
            $user->assignedEvents()->attach($event->id);
            
            Notification::fake();
            
            $this->service->sendDailyNotifications();
            
            Notification::assertSentTo($user, App\Notifications\DailyEventsNotification::class);
        });
    });

    it('catches exceptions during notification sending', function () {
        $user = User::factory()->create(['notifications_enabled' => true]);
        $user->updatePushSubscription('http://endpoint', 'key', 'auth');
        
        $method = new ReflectionMethod(NotificationService::class, 'sendNotifications');
        $method->setAccessible(true);
        
        Log::shouldReceive('error')->once();
        
        $result = $method->invoke($this->service, function ($u) {
            throw new Exception("Fail");
        });
        
        expect($result['errors'])->toBe(1);
        expect($result['users_notified'])->toBe(0);
    });
});
