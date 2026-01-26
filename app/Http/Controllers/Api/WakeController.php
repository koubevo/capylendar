<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WakeController extends Controller
{
    public function __construct(protected NotificationService $notificationService) {}

    public function __invoke(Request $request): JsonResponse
    {
        $token = $request->bearerToken();
        $expectedToken = config('services.notifications.wake_token');

        if (! is_string($expectedToken) || $expectedToken === '' || ! hash_equals($expectedToken, (string) $token)) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $validated = $request->validate([
            'type' => 'sometimes|in:morning,evening',
        ]);

        $type = $validated['type'] ?? 'evening';

        $result = match ($type) {
            'morning' => $this->notificationService->sendMorningNotifications(),
            default => $this->notificationService->sendEveningNotifications(),
        };

        return response()->json([
            'message' => 'Notifications sent',
            'type' => $type,
            'users_notified' => $result['users_notified'],
            'errors' => $result['errors'],
        ]);
    }
}
