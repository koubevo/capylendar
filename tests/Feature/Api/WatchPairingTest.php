<?php

use App\Models\User;
use App\Models\WatchDevice;
use App\Models\WatchPairing;

describe('watch pairing API', function () {
    it('creates a short-lived pairing without storing plaintext secrets', function () {
        $response = $this->postJson(route('watch.pairings.store'), [
            'device_name' => 'Pixel Watch 3',
        ]);

        $response
            ->assertCreated()
            ->assertJsonStructure([
                'device_code',
                'user_code',
                'verification_url',
                'expires_in',
                'interval',
            ])
            ->assertJsonPath('expires_in', 600)
            ->assertJsonPath('interval', 3);

        expect($response->json('verification_url'))->toBe(route('watch-devices.index'));

        $pairing = WatchPairing::query()->sole();
        $deviceCode = $response->json('device_code');
        $userCode = $response->json('user_code');

        expect($pairing->device_name)->toBe('Pixel Watch 3')
            ->and($pairing->device_code_hash)->toBe(hash('sha256', $deviceCode))
            ->and($pairing->device_code_hash)->not->toContain($deviceCode)
            ->and($pairing->user_code_hash)->not->toContain($userCode)
            ->and($pairing->expires_at->isFuture())->toBeTrue();
    });

    it('reports authorization pending until the user approves the code', function () {
        $pairing = $this->postJson(route('watch.pairings.store'), [
            'device_name' => 'Pixel Watch',
        ])->assertCreated();

        $this->postJson(route('watch.pairings.claim'), [
            'device_code' => $pairing->json('device_code'),
        ])
            ->assertStatus(202)
            ->assertJsonPath('status', 'authorization_pending')
            ->assertJsonPath('retry_after', 3);
    });

    it('approves a code and idempotently returns the same watch token', function () {
        $user = User::factory()->create();
        $pairing = $this->postJson(route('watch.pairings.store'), [
            'device_name' => 'Pixel Watch 3',
        ])->assertCreated();

        $this->actingAs($user)
            ->post(route('watch-pairings.store'), [
                'user_code' => strtolower($pairing->json('user_code')),
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $claim = $this->postJson(route('watch.pairings.claim'), [
            'device_code' => $pairing->json('device_code'),
        ])
            ->assertOk()
            ->assertJsonPath('token_type', 'Bearer')
            ->assertJsonPath('device.name', 'Pixel Watch 3');

        $plainTextToken = $claim->json('access_token');

        expect($plainTextToken)->toStartWith('capy_watch_');
        $this->assertDatabaseHas('watch_devices', [
            'user_id' => $user->id,
            'token_hash' => hash('sha256', $plainTextToken),
        ]);

        WatchPairing::query()->sole()->update(['expires_at' => now()->subSecond()]);

        $repeatedClaim = $this->postJson(route('watch.pairings.claim'), [
            'device_code' => $pairing->json('device_code'),
        ])
            ->assertOk()
            ->assertJsonPath('access_token', $plainTextToken)
            ->assertJsonPath('device.id', $claim->json('device.id'));

        expect($repeatedClaim->json('access_token'))->toBe($plainTextToken)
            ->and(WatchDevice::query()->count())->toBe(1)
            ->and(WatchPairing::query()->sole()->getRawOriginal('claimed_token'))
            ->not->toContain($plainTextToken);
    });

    it('does not let a second user approve an already approved pairing', function () {
        $firstUser = User::factory()->create();
        $secondUser = User::factory()->create();
        $pairing = $this->postJson(route('watch.pairings.store'), [
            'device_name' => 'Pixel Watch',
        ])->assertCreated();

        $this->actingAs($firstUser)
            ->post(route('watch-pairings.store'), [
                'user_code' => $pairing->json('user_code'),
            ])
            ->assertSessionHas('success');

        $this->actingAs($secondUser)
            ->post(route('watch-pairings.store'), [
                'user_code' => $pairing->json('user_code'),
            ])
            ->assertSessionHasErrors('user_code');

        expect(WatchPairing::query()->sole()->user_id)->toBe($firstUser->id);
    });

    it('rejects an invalid or expired user code', function () {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('watch-pairings.store'), [
                'user_code' => 'AAAA-AAAA',
            ])
            ->assertSessionHasErrors('user_code');

        expect(WatchDevice::query()->count())->toBe(0);
    });

    it('does not issue a token for an expired pairing', function () {
        $pairingResponse = $this->postJson(route('watch.pairings.store'), [
            'device_name' => 'Pixel Watch',
        ])->assertCreated();

        WatchPairing::query()->sole()->update(['expires_at' => now()->subSecond()]);

        $this->postJson(route('watch.pairings.claim'), [
            'device_code' => $pairingResponse->json('device_code'),
        ])
            ->assertGone()
            ->assertJsonPath('error', 'expired_token');
    });

    it('requires authentication to approve a pairing', function () {
        $this->post(route('watch-pairings.store'), [
            'user_code' => 'ABCD-2345',
        ])->assertRedirect(route('login'));
    });
});
