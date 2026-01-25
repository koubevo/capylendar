<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class NotificationSettingsController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'notifications_enabled' => 'required|boolean',
        ]);

        $user = $request->user();

        if (! $user) {
            return back()->withErrors(['error' => 'Unauthenticated']);
        }

        $user->update([
            'notifications_enabled' => $validated['notifications_enabled'],
        ]);

        if (! $validated['notifications_enabled']) {
            $user->pushSubscriptions()->delete();
        }

        return back()->with('success', 'Nastavení notifikací bylo aktualizováno');
    }
}
