<?php

use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create([
        'notifications_enabled' => false,
    ]);
});

describe('NotificationSettingsController update', function () {
    it('enables notifications', function () {
        $this->actingAs($this->user)
            ->put(route('user-notifications.update'), [
                'notifications_enabled' => true,
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->user->refresh();
        expect($this->user->notifications_enabled)->toBeTrue();
    });

    it('disables notifications', function () {
        $this->user->update(['notifications_enabled' => true]);

        $this->actingAs($this->user)
            ->put(route('user-notifications.update'), [
                'notifications_enabled' => false,
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->user->refresh();
        expect($this->user->notifications_enabled)->toBeFalse();
    });

    it('deletes push subscriptions when disabling notifications', function () {
        $this->user->update(['notifications_enabled' => true]);

        // Create push subscription using the proper User method
        $this->user->updatePushSubscription(
            'https://fcm.googleapis.com/fcm/send/test-endpoint',
            'test-public-key',
            'test-auth-token'
        );

        expect($this->user->pushSubscriptions()->count())->toBe(1);

        $this->actingAs($this->user)
            ->put(route('user-notifications.update'), [
                'notifications_enabled' => false,
            ])
            ->assertRedirect();

        expect($this->user->pushSubscriptions()->count())->toBe(0);
    });

    it('does not delete push subscriptions when enabling notifications', function () {
        // Create push subscription using the proper User method
        $this->user->updatePushSubscription(
            'https://fcm.googleapis.com/fcm/send/test-endpoint',
            'test-public-key',
            'test-auth-token'
        );

        $this->actingAs($this->user)
            ->put(route('user-notifications.update'), [
                'notifications_enabled' => true,
            ])
            ->assertRedirect();

        expect($this->user->pushSubscriptions()->count())->toBe(1);
    });

    it('requires notifications_enabled field', function () {
        $this->actingAs($this->user)
            ->put(route('user-notifications.update'), [])
            ->assertSessionHasErrors('notifications_enabled');
    });

    it('requires notifications_enabled to be boolean', function () {
        $this->actingAs($this->user)
            ->put(route('user-notifications.update'), [
                'notifications_enabled' => 'not-a-boolean',
            ])
            ->assertSessionHasErrors('notifications_enabled');
    });

    it('requires authentication', function () {
        $this->put(route('user-notifications.update'), [
            'notifications_enabled' => true,
        ])->assertRedirect(route('login'));
    });
});
