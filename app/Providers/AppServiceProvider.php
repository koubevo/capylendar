<?php

namespace App\Providers;

use App\Models\User;
use App\Models\WatchDevice;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('watch-pairing-claim', function (Request $request): array {
            $deviceCode = $request->string('device_code')->toString();

            return [
                Limit::perMinute(30)->by(hash('sha256', $deviceCode)),
                Limit::perMinute(120)->by($request->ip()),
            ];
        });

        Auth::viaRequest('watch-token', function (Request $request): ?User {
            $plainTextToken = $request->bearerToken();

            if (! $plainTextToken || ! str_starts_with($plainTextToken, 'capy_watch_')) {
                return null;
            }

            $watchDevice = WatchDevice::query()
                ->with('user')
                ->where('token_hash', hash('sha256', $plainTextToken))
                ->whereNull('revoked_at')
                ->first();

            if (! $watchDevice) {
                return null;
            }

            if (! $watchDevice->last_used_at || $watchDevice->last_used_at->isBefore(now()->subHour())) {
                $watchDevice->forceFill(['last_used_at' => now()])->save();
            }

            $request->attributes->set('watch_device', $watchDevice);

            return $watchDevice->user;
        });
    }
}
