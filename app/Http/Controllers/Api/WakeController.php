<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SendWakeNotificationRequest;
use App\Services\NotificationService;
use App\Services\RelationshipMilestoneNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Throwable;

class WakeController extends Controller
{
    public function __construct(
        protected NotificationService $notificationService,
        protected RelationshipMilestoneNotificationService $relationshipNotifications,
    ) {}

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
                'morning' => $this->morningResult(),
                default => $this->notificationService->sendEveningNotifications(),
            };
        } catch (Throwable $exception) {
            Cache::forget($cacheKey);

            throw $exception;
        }

        if ($result['errors'] > 0) {
            Cache::forget($cacheKey);
        } else {
            Cache::put($cacheKey, true, now()->endOfDay());
        }

        return response()->json([
            'message' => 'Notifications sent',
            'type' => $type,
            'users_notified' => $result['users_notified'],
            'errors' => $result['errors'],
            'already_sent' => false,
        ]);
    }

    /** @return array{users_notified: int, errors: int} */
    private function morningResult(): array
    {
        $events = $this->notificationService->sendMorningNotifications();
        $relationship = $this->relationshipNotifications->sendMorningNotifications();

        return [
            'users_notified' => $events['users_notified'] + $relationship['users_notified'],
            'errors' => $events['errors'] + $relationship['errors'],
        ];
    }
}
