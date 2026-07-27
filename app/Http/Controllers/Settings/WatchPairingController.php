<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Watch\ApproveWatchPairingRequest;
use App\Services\WatchPairingService;
use Illuminate\Http\RedirectResponse;

class WatchPairingController extends Controller
{
    public function __construct(protected WatchPairingService $watchPairingService) {}

    public function store(ApproveWatchPairingRequest $request): RedirectResponse
    {
        $user = $request->user();

        if (! $user) {
            abort(401);
        }

        $pairing = $this->watchPairingService->approve(
            $user,
            $request->string('user_code')->toString(),
        );

        if (! $pairing) {
            return back()->withErrors([
                'user_code' => 'Kód není platný, už byl použit nebo vypršel.',
            ]);
        }

        return back()->with('success', "Hodinky {$pairing->device_name} byly schváleny.");
    }
}
