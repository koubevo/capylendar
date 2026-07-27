<?php

use App\Models\User;
use App\Models\WatchDevice;
use App\Models\WatchPairing;
use Inertia\Testing\AssertableInertia as Assert;

describe('watch device settings', function () {
    it('shows active watch devices on the paired devices page', function () {
        $user = User::factory()->create();
        $activeDevice = WatchDevice::factory()->for($user)->create([
            'name' => 'Pixel Watch 3',
        ]);
        WatchDevice::factory()->for($user)->create([
            'revoked_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('watch-devices.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('settings/PairedDevices')
                ->has('watchDevices', 1)
                ->has('pendingPairings', 0)
                ->where('watchDevices.0.id', $activeDevice->id)
                ->where('watchDevices.0.name', 'Pixel Watch 3')
            );
    });

    it('shows approved pairings while the watch is claiming its token', function () {
        $user = User::factory()->create();
        $pendingPairing = WatchPairing::factory()->for($user)->create([
            'device_name' => 'Pixel Watch pending',
            'approved_at' => now(),
        ]);
        WatchPairing::factory()->for($user)->create([
            'approved_at' => now(),
            'claimed_at' => now(),
        ]);
        WatchPairing::factory()->for($user)->create([
            'approved_at' => now(),
            'expires_at' => now()->subSecond(),
        ]);

        $this->actingAs($user)
            ->get(route('watch-devices.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('watchDevices', 0)
                ->has('pendingPairings', 1)
                ->where('pendingPairings.0.id', $pendingPairing->id)
                ->where('pendingPairings.0.name', 'Pixel Watch pending')
            );
    });

    it('requires authentication to view paired devices', function () {
        $this->get(route('watch-devices.index'))
            ->assertRedirect(route('login'));
    });

    it('revokes an owned watch device', function () {
        $user = User::factory()->create();
        $device = WatchDevice::factory()->for($user)->create();

        $this->actingAs($user)
            ->delete(route('watch-devices.destroy', $device))
            ->assertRedirect()
            ->assertSessionHas('success');

        expect($device->refresh()->revoked_at)->not->toBeNull();
    });

    it('cannot revoke another users watch device', function () {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $device = WatchDevice::factory()->for($otherUser)->create();

        $this->actingAs($user)
            ->delete(route('watch-devices.destroy', $device))
            ->assertForbidden();

        expect($device->refresh()->revoked_at)->toBeNull();
    });

    it('requires authentication to revoke a device', function () {
        $device = WatchDevice::factory()->create();

        $this->delete(route('watch-devices.destroy', $device))
            ->assertRedirect(route('login'));
    });
});
