<?php

namespace App\Notifications;

use App\Models\Message;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

class ChatMessageNotification extends Notification
{
    public function __construct(
        protected Message $message
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
        $senderName = $this->message->user->name;
        $content = $this->message->content;

        if (strlen($content) > 100) {
            $content = substr($content, 0, 97).'...';
        }

        return (new WebPushMessage)
            ->title("Nová zpráva od {$senderName}")
            ->icon('/capicon.png')
            ->body($content)
            ->action('Zobrazit', 'view')
            ->options([
                'TTL' => 86400,
                'urgency' => 'normal',
            ])
            ->data([
                'url' => (function () {
                    /** @var string $url */
                    $url = config('app.url');

                    return $url.'/chat';
                })(),
            ]);
    }
}
