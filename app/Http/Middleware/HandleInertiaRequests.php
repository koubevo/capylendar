<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Inspiring;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        /** @phpstan-ignore-next-line */
        [$message, $author] = str(Inspiring::quotes()->random())->explode('-');

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'version' => config('app.version'),
            /** @phpstan-ignore-next-line */
            'quote' => ['message' => trim($message), 'author' => trim($author)],
            'auth' => [
                /**
                 * @return array{id: int, name: string, email: string, capybara: mixed, notifications_enabled: bool, has_push_subscriptions: bool}|null
                 */
                'user' => function () use ($request): ?array {
                    $user = $request->user();

                    if (! $user) {
                        return null;
                    }

                    return [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'capybara' => $user->capybara,
                        'notifications_enabled' => $user->notifications_enabled,
                        'has_push_subscriptions' => $user->pushSubscriptions()->exists(),
                    ];
                },
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
            'vapidPublicKey' => config('webpush.vapid.public_key'),
        ];
    }
}
