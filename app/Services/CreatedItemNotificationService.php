<?php

namespace App\Services;

use App\Concerns\FormatsHumanDates;
use App\Enums\Capybara;
use App\Models\Document;
use App\Models\Event;
use App\Models\Todo;
use App\Models\User;
use App\Notifications\CreatedItemNotification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\LazyCollection;
use Throwable;

use function Illuminate\Support\defer;

class CreatedItemNotificationService
{
    use FormatsHumanDates;

    public function deferEventCreated(Event $event): void
    {
        defer(function () use ($event): void {
            $event->loadMissing('author');

            $this->eventRecipients($event)->each(function (User $recipient) use ($event): void {
                $this->notify($recipient, new CreatedItemNotification(
                    title: "Nový event od {$event->author->name}",
                    body: $event->title.' — '.$this->eventDateLabel($event),
                    actionUrl: route('dashboard', [
                        'scrollToDate' => $event->start_at->format('Y-m-d'),
                        'highlightEvent' => $event->id,
                    ]),
                ));
            });
        }, "event-created-notification:{$event->id}");
    }

    public function deferTodoCreated(Todo $todo): void
    {
        defer(function () use ($todo): void {
            $todo->loadMissing('author');

            $this->todoRecipients($todo)->each(function (User $recipient) use ($todo): void {
                $this->notify($recipient, new CreatedItemNotification(
                    title: "Nové todo od {$todo->author->name}",
                    body: $todo->title.' — deadline '.$this->humanDateLabel($todo->deadline, 'l j. n. Y', capitalize: false),
                    actionUrl: route('dashboard', [
                        'scrollToDate' => $todo->deadline->format('Y-m-d'),
                        'highlightTodo' => $todo->id,
                    ]),
                ));
            });
        }, "todo-created-notification:{$todo->id}");
    }

    public function deferDocumentCreated(Document $document): void
    {
        defer(function () use ($document): void {
            $document->loadMissing('author');

            $this->enabledRecipients()
                ->whereKeyNot($document->author_id)
                ->cursor()
                ->each(function (User $recipient) use ($document): void {
                    $this->notify($recipient, new CreatedItemNotification(
                        title: "Nový dokument od {$document->author->name}",
                        body: $document->title,
                        actionUrl: route('document.show', $document),
                    ));
                });
        }, "document-created-notification:{$document->id}");
    }

    /** @return LazyCollection<int, User> */
    private function eventRecipients(Event $event): LazyCollection
    {
        return $event->subscribers()
            ->whereKeyNot($event->author_id)
            ->when(
                $event->capybara !== Capybara::Yellow,
                fn (Builder $query) => $query->where('capybara', $event->capybara),
            )
            ->whereIn('users.id', $this->enabledRecipients()->select('users.id'))
            ->cursor();
    }

    /** @return LazyCollection<int, User> */
    private function todoRecipients(Todo $todo): LazyCollection
    {
        return $todo->subscribers()
            ->whereKeyNot($todo->author_id)
            ->when(
                $todo->capybara !== Capybara::Yellow,
                fn (Builder $query) => $query->where('capybara', $todo->capybara),
            )
            ->whereIn('users.id', $this->enabledRecipients()->select('users.id'))
            ->cursor();
    }

    /** @return Builder<User> */
    private function enabledRecipients(): Builder
    {
        return User::query()
            ->where('notifications_enabled', true)
            ->whereHas('pushSubscriptions');
    }

    private function notify(User $recipient, CreatedItemNotification $notification): void
    {
        try {
            $recipient->notify($notification);
        } catch (Throwable $exception) {
            Log::error('Failed to send created item notification', [
                'user_id' => $recipient->id,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    private function eventDateLabel(Event $event): string
    {
        $date = $this->humanDateLabel($event->start_at, 'l j. n. Y', capitalize: false);

        return $event->is_all_day ? $date : $date.' v '.$event->start_at->format('H:i');
    }
}
