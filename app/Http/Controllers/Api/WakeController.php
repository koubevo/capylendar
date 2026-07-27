<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SendWakeNotificationRequest;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Throwable;

class WakeController extends Controller
{
    public function __construct(protected NotificationService $notificationService) {}

    public function __invoke(SendWakeNotificationRequest $request): JsonResponse
    {
        $token = $request->bearerToken();
        $expectedToken = config('services.notifications.wake_token');

        if (! is_string($expectedToken) || $expectedToken === '' || ! hash_equals($expectedToken, (string) $token)) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $type = $request->string('type', 'evening')->toString();
        $cacheKey = sprintf('notifications:wake:%s:%s', $type, now()->toDateString());

        if (! Cache::add($cacheKey, true, now()->endOfDay())) {
            return response()->json([
                'message' => 'Notifications already sent',
                'type' => $type,
                'users_notified' => 0,
                'errors' => 0,
                'already_sent' => true,
            ]);
        }

        try {
            $result = match ($type) {
                'morning' => $this->notificationService->sendMorningNotifications(),
                default => $this->notificationService->sendEveningNotifications(),
            };
        } catch (Throwable $exception) {
            Cache::forget($cacheKey);

            throw $exception;
        }

        Cache::put($cacheKey, true, now()->endOfDay());

        return response()->json([
            'message' => 'Notifications sent',
            'type' => $type,
            'users_notified' => $result['users_notified'],
            'errors' => $result['errors'],
            'already_sent' => false,
        ]);
    }
}
