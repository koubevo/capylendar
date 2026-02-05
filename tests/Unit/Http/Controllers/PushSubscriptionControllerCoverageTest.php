<?php

use App\Http\Controllers\PushSubscriptionController;
use Illuminate\Http\Request;

describe('PushSubscriptionController Coverage', function () {
    it('returns 401 if user is not found in store', function () {
        $controller = new PushSubscriptionController;

        $request = Request::create('/store', 'POST', [
            'endpoint' => 'https://example.com',
            'keys' => ['auth' => 'foo', 'p256dh' => 'bar'],
        ]);
        $request->setUserResolver(fn () => null);

        $response = $controller->store($request);

        expect($response->status())->toBe(401);
        expect($response->getData(true))->toBe(['message' => 'Unauthenticated']);
    });

    it('returns 401 if user is not found in destroy', function () {
        $controller = new PushSubscriptionController;

        $request = Request::create('/destroy', 'DELETE', [
            'endpoint' => 'https://example.com',
        ]);
        $request->setUserResolver(fn () => null);

        $response = $controller->destroy($request);

        expect($response->status())->toBe(401);
        expect($response->getData(true))->toBe(['message' => 'Unauthenticated']);
    });
});
