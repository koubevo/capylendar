<?php

use App\Http\Controllers\Settings\NotificationSettingsController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

describe('NotificationSettingsController Coverage', function () {
    it('returns error if user is not found in update', function () {
        $controller = new NotificationSettingsController;

        $request = Request::create('/update', 'PUT', [
            'notifications_enabled' => true,
        ]);
        $request->setUserResolver(fn () => null);

        // Bind request to container for back() helper
        app()->instance('request', $request);

        // Session is needed for back()->withErrors()
        $session = new \Illuminate\Session\Store(
            'test',
            new \Illuminate\Session\NullSessionHandler
        );
        $request->setLaravelSession($session);
        app()->instance('session.store', $session);

        $response = $controller->update($request);

        expect($response->isRedirect())->toBeTrue();
        expect($session->get('errors')->first('error'))->toBe('Unauthenticated');
    });
});
