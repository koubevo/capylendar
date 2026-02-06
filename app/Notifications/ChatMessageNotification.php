<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

class ChatMessageNotification extends Notification
{
    public function __construct(
        protected string $senderName,
        protected string $content
    ) {
        \Log::info('ChatMessageNotification created', [
            'sender' => $this->senderName,
            'content_length' => strlen($this->content),
        ]);
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return [WebPushChannel::class];
    }

    public function toWebPush(object $notifiable, Notification $notification): WebPushMessage
    {
        \Log::info('toWebPush called', [
            'notifiable_id' => $notifiable->id ?? 'unknown',
            'sender' => $this->senderName,
        ]);

        $content = $this->content;

        if (strlen($content) > 100) {
            $content = substr($content, 0, 97).'...';
        }

        $message = (new WebPushMessage)
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

        \Log::info('WebPush message created successfully');

        return $message;
    }
}
