<?php

use App\Models\User;
use Illuminate\Contracts\Validation\UncompromisedVerifier;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

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

    it('requires a password with at least twelve characters', function () {
        $this->actingAs($this->user)
            ->put(route('user-password.update'), [
                'current_password' => 'current-password',
                'password' => 'too-short',
                'password_confirmation' => 'too-short',
            ])
            ->assertSessionHasErrors('password');

        expect(Hash::check('too-short', $this->user->fresh()->password))->toBeFalse();
    });

    it('rejects a compromised password in production', function () {
        $originalEnvironment = app()->environment();
        app()->detectEnvironment(fn (): string => 'production');

        try {
            app('validator');

            $verifier = Mockery::mock(UncompromisedVerifier::class);
            $verifier->shouldReceive('verify')
                ->once()
                ->andReturnFalse();
            $this->instance(UncompromisedVerifier::class, $verifier);

            $validator = validator(
                ['password' => 'compromised-password'],
                ['password' => [Password::defaults()]],
            );

            expect($validator->fails())->toBeTrue()
                ->and($validator->errors()->has('password'))->toBeTrue();
        } finally {
            app()->detectEnvironment(fn (): string => $originalEnvironment);
        }
    });

    it('accepts a verified uncompromised password in production', function () {
        $originalEnvironment = app()->environment();
        app()->detectEnvironment(fn (): string => 'production');

        try {
            app('validator');

            $verifier = Mockery::mock(UncompromisedVerifier::class);
            $verifier->shouldReceive('verify')
                ->once()
                ->andReturnTrue();
            $this->instance(UncompromisedVerifier::class, $verifier);

            $validator = validator(
                ['password' => 'verified-password'],
                ['password' => [Password::defaults()]],
            );

            expect($validator->passes())->toBeTrue();
        } finally {
            app()->detectEnvironment(fn (): string => $originalEnvironment);
        }
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
