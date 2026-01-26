<?php

namespace App\Notifications;

use App\Http\Resources\EventResource;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

class DailyEventsNotification extends Notification
{
    /**
     * @param  array<EventResource>  $events
     */
    public function __construct(
        protected array $events,
        protected string $title,
        protected string $body,
        protected ?string $actionUrl = null
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return [WebPushChannel::class];
    }

    public function toWebPush(object $notifiable, Notification $notification): WebPushMessage
    {
        return (new WebPushMessage)
            ->title($this->title)
            ->icon('/capicon.png')
            ->body($this->body)
            ->action('Zobrazit', 'view')
            ->options([
                'TTL' => 86400,
                'urgency' => 'normal',
            ])
            ->data([
                'url' => $this->actionUrl ?? (function () {
                    /** @var string $url */
                    $url = config('app.url');

                    return $url.'/dashboard';
                })(),
            ]);
    }
}
