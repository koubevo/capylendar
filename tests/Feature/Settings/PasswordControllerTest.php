<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

beforeEach(function () {
    $this->user = User::factory()->create([
        'password' => Hash::make('current-password'),
    ]);
});

describe('PasswordController update', function () {
    it('updates password with valid current password', function () {
        $this->actingAs($this->user)
            ->put(route('user-password.update'), [
                'current_password' => 'current-password',
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
            ])
            ->assertRedirect();

        $this->user->refresh();
        expect(Hash::check('new-password', $this->user->password))->toBeTrue();
    });

    it('fails with incorrect current password', function () {
        $this->actingAs($this->user)
            ->put(route('user-password.update'), [
                'current_password' => 'wrong-password',
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
            ])
            ->assertSessionHasErrors('current_password');
    });

    it('requires current password', function () {
        $this->actingAs($this->user)
            ->put(route('user-password.update'), [
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
            ])
            ->assertSessionHasErrors('current_password');
    });

    it('requires new password', function () {
        $this->actingAs($this->user)
            ->put(route('user-password.update'), [
                'current_password' => 'current-password',
            ])
            ->assertSessionHasErrors('password');
    });

    it('requires password confirmation', function () {
        $this->actingAs($this->user)
            ->put(route('user-password.update'), [
                'current_password' => 'current-password',
                'password' => 'new-password',
            ])
            ->assertSessionHasErrors('password');
    });

    it('fails when password confirmation does not match', function () {
        $this->actingAs($this->user)
            ->put(route('user-password.update'), [
                'current_password' => 'current-password',
                'password' => 'new-password',
                'password_confirmation' => 'different-password',
            ])
            ->assertSessionHasErrors('password');
    });

    it('requires authentication', function () {
        $this->put(route('user-password.update'), [
            'current_password' => 'password',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])->assertRedirect(route('login'));
    });

    it('is rate limited', function () {
        // Make multiple requests quickly
        for ($i = 0; $i < 6; $i++) {
            $this->actingAs($this->user)
                ->put(route('user-password.update'), [
                    'current_password' => 'wrong-password',
                    'password' => 'new-password',
                    'password_confirmation' => 'new-password',
                ]);
        }

        $this->actingAs($this->user)
            ->put(route('user-password.update'), [
                'current_password' => 'current-password',
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
            ])
            ->assertTooManyRequests();
    });
});
