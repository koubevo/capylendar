<?php

use App\Notifications\DailyEventsNotification;
use App\Models\User;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

describe('DailyEventsNotification', function () {
    it('uses WebPushChannel', function () {
        $notification = new DailyEventsNotification([], 'Title', 'Body');
        $notifiable = User::factory()->make();

        expect($notification->via($notifiable))->toBe([WebPushChannel::class]);
    });

    it('formats WebPushMessage correctly', function () {
        $notification = new DailyEventsNotification([], 'Test Title', 'Test Body', 'https://example.com');
        $notifiable = User::factory()->make();

        // Pass the notification itself as the second argument if required by the signature
        // The signature in the class is toWebPush($notifiable, $notification)
        // But usually Laravel calls logic differently. 
        // Wait, the signature in the class is `toWebPush(object $notifiable, Notification $notification)`.
        // This is weird. Usually it's just `toWebPush($notifiable)`.
        // Let's check the code: public function toWebPush(object $notifiable, Notification $notification): WebPushMessage
        
        $message = $notification->toWebPush($notifiable, $notification);

        expect($message)->toBeInstanceOf(WebPushMessage::class);
        expect($message->toArray()['title'])->toBe('Test Title');
        expect($message->toArray()['body'])->toBe('Test Body');
        expect($message->toArray()['icon'])->toBe('/capicon.png');
        expect($message->toArray()['data']['url'])->toBe('https://example.com');
    });

    it('uses default action url if not provided', function () {
        $notification = new DailyEventsNotification([], 'Title', 'Body');
        $notifiable = User::factory()->make();
        
        $message = $notification->toWebPush($notifiable, $notification);
        
        $expectedUrl = config('app.url') . '/dashboard';
        expect($message->toArray()['data']['url'])->toBe($expectedUrl);
    });
});
