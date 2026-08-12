<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

class RelationshipMilestoneNotification extends Notification
{
    /**
     * @param  list<string>  $milestones
     */
    public function __construct(
        private readonly string $title,
        private readonly array $milestones,
    ) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return [WebPushChannel::class];
    }

    public function toWebPush(object $notifiable, Notification $notification): WebPushMessage
    {
        return (new WebPushMessage)
            ->title($this->title)
            ->icon('/capicon.png')
            ->body(implode(' | ', $this->milestones))
            ->action('Zobrazit', 'view')
            ->options([
                'TTL' => 86400,
                'urgency' => 'normal',
            ])
            ->data([
                'url' => route('relationship-settings.index'),
            ]);
    }
}
