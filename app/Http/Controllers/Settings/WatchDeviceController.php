<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\WatchDevice;
use App\Models\WatchPairing;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class WatchDeviceController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        $watchDevices = $user
            ?->watchDevices()
            ->whereNull('revoked_at')
            ->latest()
            ->get()
            ->map(fn (WatchDevice $device): array => [
                'id' => $device->id,
                'name' => $device->name,
                'last_used_at' => $device->last_used_at?->diffForHumans(),
            ])
            ->values()
            ->all() ?? [];

        $pendingPairings = $user
            ?->watchPairings()
            ->whereNotNull('approved_at')
            ->whereNull('claimed_at')
            ->where('expires_at', '>', now())
            ->latest('approved_at')
            ->get()
            ->map(fn (WatchPairing $pairing): array => [
                'id' => $pairing->id,
                'name' => $pairing->device_name,
                'approved_at' => $pairing->approved_at?->diffForHumans(),
            ])
            ->values()
            ->all() ?? [];

        return Inertia::render('settings/PairedDevices', [
            'watchDevices' => $watchDevices,
            'pendingPairings' => $pendingPairings,
        ]);
    }

    public function destroy(Request $request, WatchDevice $watchDevice): RedirectResponse
    {
        $user = $request->user();

        if (! $user || $watchDevice->user_id !== $user->id) {
            abort(403);
        }

        $watchDevice->update(['revoked_at' => now()]);

        return back()->with('success', 'Hodinky byly odpojeny.');
    }
}
