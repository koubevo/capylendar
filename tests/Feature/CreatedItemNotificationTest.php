<?php

use App\Enums\Capybara;
use App\Enums\Priority;
use App\Http\Resources\EventResource;
use App\Models\Document;
use App\Models\Event;
use App\Models\Todo;
use App\Models\User;
use App\Notifications\CreatedItemNotification;
use App\Services\EventTagService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

function notificationRecipient(Capybara $capybara = Capybara::Blue, bool $enabled = true): User
{
    $recipient = User::factory()->create([
        'capybara' => $capybara,
        'notifications_enabled' => $enabled,
    ]);
    $recipient->updatePushSubscription(
        'https://fcm.googleapis.com/fcm/send/created-item-recipient',
        'recipient-public-key',
        'recipient-auth-token',
    );

    return $recipient;
}

it('notifies non-author subscribers when a timed event is created', function () {
    $this->withoutDefer();
    Notification::fake();

    $author = User::factory()->pink()->create(['name' => 'Stacy']);
    $recipient = notificationRecipient();
    $date = now()->addDay()->format('Y-m-d');

    $this->actingAs($author)->post(route('event.store'), [
        'title' => 'Večeře',
        'date' => $date,
        'start_at' => '19:00',
        'is_all_day' => false,
        'capybara' => Capybara::Blue->value,
        'is_private' => false,
    ])->assertRedirect();

    $event = Event::query()->where('title', 'Večeře')->firstOrFail();

    Notification::assertSentTo(
        $recipient,
        CreatedItemNotification::class,
        function (CreatedItemNotification $notification) use ($recipient, $event, $date): bool {
            $message = $notification->toWebPush($recipient, $notification);
            $payload = $message->toArray();

            return $payload['title'] === 'Nový event od Stacy'
                && $payload['body'] === 'Večeře — zítra v 19:00'
                && $payload['data']['url'] === route('dashboard', [
                    'scrollToDate' => $date,
                    'highlightEvent' => $event->id,
                ])
                && $message->getOptions()['TTL'] === 86400
                && $message->getOptions()['urgency'] === 'normal';
        },
    );
    Notification::assertNotSentTo($author, CreatedItemNotification::class);
});

it('formats an all-day event notification without a time', function () {
    $this->withoutDefer();
    Notification::fake();

    $author = User::factory()->create(['name' => 'John']);
    $recipient = notificationRecipient(Capybara::Pink);

    $this->actingAs($author)->post(route('event.store'), [
        'title' => 'Výlet',
        'date' => now()->addDay()->format('Y-m-d'),
        'start_at' => '',
        'is_all_day' => true,
        'capybara' => Capybara::Pink->value,
        'is_private' => false,
    ])->assertRedirect();

    Notification::assertSentTo(
        $recipient,
        CreatedItemNotification::class,
        function (CreatedItemNotification $notification) use ($recipient): bool {
            $payload = $notification->toWebPush($recipient, $notification)->toArray();

            return $payload['body'] === 'Výlet — zítra';
        },
    );
});

it('notifies non-author subscribers when a todo is created', function () {
    $this->withoutDefer();
    Notification::fake();

    $author = User::factory()->pink()->create(['name' => 'John']);
    $recipient = notificationRecipient();
    $deadline = now()->addDay()->format('Y-m-d');

    $this->actingAs($author)->post(route('todo.store'), [
        'title' => 'Koupit letenky',
        'capybara' => Capybara::Blue->value,
        'deadline' => $deadline,
        'priority' => Priority::High->value,
        'is_private' => false,
    ])->assertRedirect();

    $todo = Todo::query()->where('title', 'Koupit letenky')->firstOrFail();

    Notification::assertSentTo(
        $recipient,
        CreatedItemNotification::class,
        function (CreatedItemNotification $notification) use ($recipient, $todo, $deadline): bool {
            $payload = $notification->toWebPush($recipient, $notification)->toArray();

            return $payload['title'] === 'Nové todo od John'
                && $payload['body'] === 'Koupit letenky — deadline zítra'
                && $payload['data']['url'] === route('dashboard', [
                    'scrollToDate' => $deadline,
                    'highlightTodo' => $todo->id,
                ]);
        },
    );
    Notification::assertNotSentTo($author, CreatedItemNotification::class);
});

it('does not notify another user about private event or todo creation', function () {
    $this->withoutDefer();
    Notification::fake();

    $author = User::factory()->create();
    notificationRecipient();
    $date = now()->addDay()->format('Y-m-d');

    $this->actingAs($author)->post(route('event.store'), [
        'title' => 'Soukromý event',
        'date' => $date,
        'start_at' => '10:00',
        'is_all_day' => false,
        'capybara' => Capybara::Blue->value,
        'is_private' => true,
    ])->assertRedirect();

    $this->actingAs($author)->post(route('todo.store'), [
        'title' => 'Soukromé todo',
        'capybara' => Capybara::Blue->value,
        'deadline' => $date,
        'priority' => Priority::Medium->value,
        'is_private' => true,
    ])->assertRedirect();

    Notification::assertNothingSent();
});

it('respects disabled notifications for created shared items', function () {
    $this->withoutDefer();
    Notification::fake();

    $author = User::factory()->create();
    notificationRecipient(enabled: false);

    $this->actingAs($author)->post(route('event.store'), [
        'title' => 'Společný event',
        'date' => now()->addDay()->format('Y-m-d'),
        'start_at' => '10:00',
        'is_all_day' => false,
        'capybara' => Capybara::Blue->value,
        'is_private' => false,
    ])->assertRedirect();

    Notification::assertNothingSent();
});

it('notifies the other user when a document is created', function () {
    $this->withoutDefer();
    Notification::fake();

    $author = User::factory()->create(['name' => 'Stacy']);
    $recipient = notificationRecipient();

    $this->actingAs($author)->post(route('document.store'), [
        'title' => 'Cestovní dokumenty',
        'body' => 'Obsah zůstává soukromý v aplikaci.',
    ])->assertRedirect();

    $document = Document::query()->where('title', 'Cestovní dokumenty')->firstOrFail();

    Notification::assertSentTo(
        $recipient,
        CreatedItemNotification::class,
        function (CreatedItemNotification $notification) use ($recipient, $document): bool {
            $payload = $notification->toWebPush($recipient, $notification)->toArray();

            return $payload['title'] === 'Nový dokument od Stacy'
                && $payload['body'] === 'Cestovní dokumenty'
                && $payload['data']['url'] === route('document.show', $document);
        },
    );
    Notification::assertNotSentTo($author, CreatedItemNotification::class);
});

it('does not send created-item notifications for later mutations', function () {
    $this->withoutDefer();
    Notification::fake();

    $author = User::factory()->create();
    notificationRecipient();
    $event = Event::factory()->create(['author_id' => $author->id]);
    $event->subscribers()->attach(User::all());

    $this->actingAs($author)->delete(route('event.destroy', $event))->assertRedirect();

    Notification::assertNothingSent();
});

it('does not schedule a notification when the creation transaction rolls back', function () {
    $this->withoutDefer();
    $this->withoutExceptionHandling();
    Notification::fake();

    $author = User::factory()->create();
    notificationRecipient();

    $this->mock(EventTagService::class, function ($mock): void {
        $mock->shouldReceive('assignTags')
            ->once()
            ->andThrow(new RuntimeException('Tag assignment failed'));
    });

    expect(fn () => $this->actingAs($author)->post(route('event.store'), [
        'title' => 'Rolled back event',
        'date' => now()->addDay()->format('Y-m-d'),
        'start_at' => '10:00',
        'is_all_day' => false,
        'capybara' => Capybara::Blue->value,
        'is_private' => false,
    ]))->toThrow(RuntimeException::class, 'Tag assignment failed');

    $this->assertDatabaseMissing('events', [
        'title' => 'Rolled back event',
    ]);
    Notification::assertNothingSent();
});

it('does not notify blue when pink creates a pink event', function () {
    $this->withoutDefer();
    Notification::fake();

    $author = User::factory()->pink()->create();
    notificationRecipient();

    $this->actingAs($author)->post(route('event.store'), [
        'title' => 'Pink assignment',
        'date' => now()->addDay()->format('Y-m-d'),
        'start_at' => '10:00',
        'is_all_day' => false,
        'capybara' => Capybara::Pink->value,
        'is_private' => false,
    ])->assertRedirect();

    Notification::assertNothingSent();
});

it('notifies blue when pink creates a yellow event', function () {
    $this->withoutDefer();
    Notification::fake();

    $author = User::factory()->pink()->create();
    $recipient = notificationRecipient();

    $this->actingAs($author)->post(route('event.store'), [
        'title' => 'Shared assignment',
        'date' => now()->addDay()->format('Y-m-d'),
        'start_at' => '10:00',
        'is_all_day' => false,
        'capybara' => Capybara::Yellow->value,
        'is_private' => false,
    ])->assertRedirect();

    Notification::assertSentTo($recipient, CreatedItemNotification::class);
    Notification::assertNotSentTo($author, CreatedItemNotification::class);
});

it('does not notify pink when blue creates a blue todo', function () {
    $this->withoutDefer();
    Notification::fake();

    $author = User::factory()->create();
    notificationRecipient(Capybara::Pink);

    $this->actingAs($author)->post(route('todo.store'), [
        'title' => 'Blue assignment',
        'capybara' => Capybara::Blue->value,
        'deadline' => now()->addDay()->format('Y-m-d'),
        'priority' => Priority::Medium->value,
        'is_private' => false,
    ])->assertRedirect();

    Notification::assertNothingSent();
});

it('capitalizes a non-ASCII Czech weekday in shared date labels', function () {
    $previousLocale = Carbon::getLocale();
    Carbon::setLocale('cs');
    Carbon::setTestNow('2026-08-10 12:00:00');

    try {
        $event = Event::factory()->create([
            'start_at' => Carbon::parse('2026-08-18 10:00:00'),
        ]);

        $resource = EventResource::make($event)->resolve();

        expect($resource['date']['label'])->toBe('Úterý 18.08.26');
    } finally {
        Carbon::setTestNow();
        Carbon::setLocale($previousLocale);
    }
});
