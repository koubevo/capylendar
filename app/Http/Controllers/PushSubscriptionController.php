<?php

namespace App\Http\Controllers;

use App\Http\Requests\DestroyPushSubscriptionRequest;
use App\Http\Requests\StorePushSubscriptionRequest;
use Illuminate\Http\JsonResponse;

class PushSubscriptionController extends Controller
{
    public function store(StorePushSubscriptionRequest $request): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $user->updatePushSubscription(
            $request->string('endpoint')->toString(),
            $request->string('keys.p256dh')->toString(),
            $request->string('keys.auth')->toString(),
        );

        $user->update(['notifications_enabled' => true]);

        return response()->json(['message' => 'Subscription saved']);
    }

    public function destroy(DestroyPushSubscriptionRequest $request): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $user->deletePushSubscription($request->string('endpoint')->toString());

        if ($user->pushSubscriptions()->count() === 0) {
            $user->update(['notifications_enabled' => false]);
        }

        return response()->json(['message' => 'Subscription removed']);
    }
}
