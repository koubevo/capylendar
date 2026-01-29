<?php

use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create([
        'notifications_enabled' => false,
    ]);
});

describe('PushSubscriptionController store', function () {
    it('stores a push subscription', function () {
        $this->actingAs($this->user)
            ->post(route('push-subscription.store'), [
                'endpoint' => 'https://fcm.googleapis.com/fcm/send/test-endpoint',
                'keys' => [
                    'p256dh' => 'test-p256dh-key',
                    'auth' => 'test-auth-key',
                ],
            ])
            ->assertOk()
            ->assertJson(['message' => 'Subscription saved']);

        expect($this->user->pushSubscriptions()->count())->toBe(1);
    });

    it('enables notifications when storing subscription', function () {
        expect($this->user->notifications_enabled)->toBeFalse();

        $this->actingAs($this->user)
            ->post(route('push-subscription.store'), [
                'endpoint' => 'https://fcm.googleapis.com/fcm/send/test-endpoint',
                'keys' => [
                    'p256dh' => 'test-p256dh-key',
                    'auth' => 'test-auth-key',
                ],
            ])
            ->assertOk();

        $this->user->refresh();
        expect($this->user->notifications_enabled)->toBeTrue();
    });

    it('updates existing subscription with same endpoint', function () {
        // Create initial subscription via the API
        $this->actingAs($this->user)
            ->post(route('push-subscription.store'), [
                'endpoint' => 'https://fcm.googleapis.com/fcm/send/test-endpoint',
                'keys' => [
                    'p256dh' => 'old-p256dh-key',
                    'auth' => 'old-auth-key',
                ],
            ])
            ->assertOk();

        // Update with new keys
        $this->actingAs($this->user)
            ->post(route('push-subscription.store'), [
                'endpoint' => 'https://fcm.googleapis.com/fcm/send/test-endpoint',
                'keys' => [
                    'p256dh' => 'new-p256dh-key',
                    'auth' => 'new-auth-key',
                ],
            ])
            ->assertOk();

        expect($this->user->pushSubscriptions()->count())->toBe(1);
        expect($this->user->pushSubscriptions()->first()->public_key)->toBe('new-p256dh-key');
    });

    it('requires endpoint', function () {
        $this->actingAs($this->user)
            ->postJson(route('push-subscription.store'), [
                'keys' => [
                    'p256dh' => 'test-p256dh-key',
                    'auth' => 'test-auth-key',
                ],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('endpoint');
    });

    it('requires endpoint to be valid URL', function () {
        $this->actingAs($this->user)
            ->postJson(route('push-subscription.store'), [
                'endpoint' => 'not-a-valid-url',
                'keys' => [
                    'p256dh' => 'test-p256dh-key',
                    'auth' => 'test-auth-key',
                ],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('endpoint');
    });

    it('requires keys.auth', function () {
        $this->actingAs($this->user)
            ->postJson(route('push-subscription.store'), [
                'endpoint' => 'https://fcm.googleapis.com/fcm/send/test-endpoint',
                'keys' => [
                    'p256dh' => 'test-p256dh-key',
                ],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('keys.auth');
    });

    it('requires keys.p256dh', function () {
        $this->actingAs($this->user)
            ->postJson(route('push-subscription.store'), [
                'endpoint' => 'https://fcm.googleapis.com/fcm/send/test-endpoint',
                'keys' => [
                    'auth' => 'test-auth-key',
                ],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('keys.p256dh');
    });

    it('returns 401 for unauthenticated request', function () {
        $this->postJson(route('push-subscription.store'), [
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/test-endpoint',
            'keys' => [
                'p256dh' => 'test-p256dh-key',
                'auth' => 'test-auth-key',
            ],
        ])->assertUnauthorized();
    });
});

describe('PushSubscriptionController destroy', function () {
    beforeEach(function () {
        // Create a push subscription for the user
        $this->user->updatePushSubscription(
            'https://fcm.googleapis.com/fcm/send/test-endpoint',
            'test-p256dh-key',
            'test-auth-key'
        );
    });

    it('removes a push subscription', function () {
        $this->actingAs($this->user)
            ->delete(route('push-subscription.destroy'), [
                'endpoint' => 'https://fcm.googleapis.com/fcm/send/test-endpoint',
            ])
            ->assertOk()
            ->assertJson(['message' => 'Subscription removed']);

        expect($this->user->pushSubscriptions()->count())->toBe(0);
    });

    it('disables notifications when last subscription is removed', function () {
        $this->user->update(['notifications_enabled' => true]);

        $this->actingAs($this->user)
            ->delete(route('push-subscription.destroy'), [
                'endpoint' => 'https://fcm.googleapis.com/fcm/send/test-endpoint',
            ])
            ->assertOk();

        $this->user->refresh();
        expect($this->user->notifications_enabled)->toBeFalse();
    });

    it('does not disable notifications when other subscriptions exist', function () {
        $this->user->update(['notifications_enabled' => true]);

        // Add second subscription
        $this->user->updatePushSubscription(
            'https://fcm.googleapis.com/fcm/send/endpoint-2',
            'test-p256dh-key-2',
            'test-auth-key-2'
        );

        $this->actingAs($this->user)
            ->delete(route('push-subscription.destroy'), [
                'endpoint' => 'https://fcm.googleapis.com/fcm/send/test-endpoint',
            ])
            ->assertOk();

        $this->user->refresh();
        expect($this->user->notifications_enabled)->toBeTrue();
        expect($this->user->pushSubscriptions()->count())->toBe(1);
    });

    it('requires endpoint', function () {
        $this->actingAs($this->user)
            ->deleteJson(route('push-subscription.destroy'), [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('endpoint');
    });

    it('requires endpoint to be valid URL', function () {
        $this->actingAs($this->user)
            ->deleteJson(route('push-subscription.destroy'), [
                'endpoint' => 'not-a-valid-url',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('endpoint');
    });

    it('returns 401 for unauthenticated request', function () {
        $this->deleteJson(route('push-subscription.destroy'), [
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/test-endpoint',
        ])->assertUnauthorized();
    });
});
