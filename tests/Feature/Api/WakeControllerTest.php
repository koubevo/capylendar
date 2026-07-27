<?php

use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    Cache::flush();
});

describe('WakeController', function () {
    it('rejects requests without bearer token', function () {
        config(['services.notifications.wake_token' => 'valid-token']);

        $response = $this->postJson('/api/wake');

        $response->assertStatus(401);
        $response->assertJson(['message' => 'Unauthorized']);
    });

    it('rejects requests with invalid bearer token', function () {
        config(['services.notifications.wake_token' => 'valid-token']);

        $response = $this->postJson('/api/wake', [], [
            'Authorization' => 'Bearer invalid-token',
        ]);

        $response->assertStatus(401);
        $response->assertJson(['message' => 'Unauthorized']);
    });

    it('accepts requests with valid bearer token', function () {
        config(['services.notifications.wake_token' => 'valid-token']);

        $response = $this->postJson('/api/wake', [], [
            'Authorization' => 'Bearer valid-token',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure(['message', 'users_notified', 'errors']);
    });

    it('rejects requests when no wake token is configured', function () {
        config(['services.notifications.wake_token' => null]);

        $response = $this->postJson('/api/wake', [], [
            'Authorization' => 'Bearer any-token',
        ]);

        $response->assertStatus(401);
    });

    it('sends notifications to users with notifications enabled', function () {
        config(['services.notifications.wake_token' => 'valid-token']);

        // Create user with notifications enabled (no push subscriptions though)
        $user = User::factory()->create([
            'notifications_enabled' => true,
        ]);

        $response = $this->postJson('/api/wake', [], [
            'Authorization' => 'Bearer valid-token',
        ]);

        $response->assertStatus(200);
        // No notifications sent because no push subscriptions
        $response->assertJson(['users_notified' => 0]);
    });

    it('sends each notification type only once per day', function () {
        config(['services.notifications.wake_token' => 'valid-token']);

        $service = $this->mock(NotificationService::class);
        $service->shouldReceive('sendEveningNotifications')
            ->once()
            ->andReturn(['users_notified' => 2, 'errors' => 0]);

        $headers = [
            'Authorization' => 'Bearer valid-token',
        ];

        $this->postJson('/api/wake', [], $headers)
            ->assertOk()
            ->assertJsonPath('already_sent', false);

        $this->postJson('/api/wake', [], $headers)
            ->assertOk()
            ->assertJsonPath('already_sent', true);
    });

    it('rejects an invalid notification type', function () {
        config(['services.notifications.wake_token' => 'valid-token']);

        $this->postJson('/api/wake', [
            'type' => 'weekly',
        ], [
            'Authorization' => 'Bearer valid-token',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('type');
    });

});
