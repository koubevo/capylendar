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

        if (! $expectedToken || $token !== $expectedToken) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $result = $this->notificationService->sendDailyNotifications();

        return response()->json([
            'message' => 'Notifications sent',
            'users_notified' => $result['users_notified'],
            'errors' => $result['errors'],
        ]);
    }
}
