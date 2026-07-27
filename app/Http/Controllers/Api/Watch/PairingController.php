<?php

namespace App\Http\Controllers\Api\Watch;

use App\Http\Controllers\Controller;
use App\Http\Requests\Watch\ClaimWatchPairingRequest;
use App\Http\Requests\Watch\StoreWatchPairingRequest;
use App\Services\WatchPairingService;
use Illuminate\Http\JsonResponse;

class PairingController extends Controller
{
    public function __construct(protected WatchPairingService $watchPairingService) {}

    public function store(StoreWatchPairingRequest $request): JsonResponse
    {
        $result = $this->watchPairingService->create($request->string('device_name')->toString());

        return response()->json([
            'device_code' => $result['device_code'],
            'user_code' => $result['user_code'],
            'verification_url' => route('watch-devices.index'),
            'expires_in' => 600,
            'interval' => 3,
        ], 201);
    }

    public function claim(ClaimWatchPairingRequest $request): JsonResponse
    {
        $result = $this->watchPairingService->claim($request->string('device_code')->toString());

        return match ($result['status']) {
            'invalid' => response()->json([
                'error' => 'invalid_device_code',
            ], 404),
            'expired' => response()->json([
                'error' => 'expired_token',
            ], 410),
            'pending' => response()->json([
                'status' => 'authorization_pending',
                'retry_after' => 3,
            ], 202),
            'claimed' => response()->json([
                'error' => 'token_already_claimed',
            ], 409),
            'authorized' => response()->json([
                'access_token' => $result['token'],
                'token_type' => 'Bearer',
                'device' => [
                    'id' => $result['device']->id,
                    'name' => $result['device']->name,
                ],
            ]),
        };
    }
}
