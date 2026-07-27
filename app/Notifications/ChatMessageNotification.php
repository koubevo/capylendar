<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

class ChatMessageNotification extends Notification
{
    public function __construct(
        protected string $senderName,
        protected string $content,
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
        $content = Str::limit($this->content, 100);

        return (new WebPushMessage)
            ->title("Nová zpráva od {$this->senderName}")
            ->icon('/capicon.png')
            ->body($content)
            ->action('Zobrazit', 'view')
            ->options([
                'TTL' => 86400,
                'urgency' => 'normal',
            ])
            ->data([
                'url' => route('chat.index'),
            ]);
    }
}
