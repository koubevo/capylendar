<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

class CreatedItemNotification extends Notification
{
    public function __construct(
        protected string $title,
        protected string $body,
        protected string $actionUrl,
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
            ->options(['TTL' => 86400, 'urgency' => 'normal'])
            ->data(['url' => $this->actionUrl]);
    }
}
