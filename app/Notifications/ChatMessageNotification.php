<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

class ChatMessageNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected string $senderName,
        protected string $content
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
        $content = $this->content;

        if (strlen($content) > 100) {
            $content = substr($content, 0, 97).'...';
        }

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
                'url' => (function () {
                    /** @var string $url */
                    $url = config('app.url');

                    return $url.'/chat';
                })(),
            ]);
    }
}
