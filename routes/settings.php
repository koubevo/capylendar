<?php

use App\Http\Controllers\Settings\PasswordController;
// use App\Http\Controllers\Settings\ProfileController;
// use App\Http\Controllers\Settings\TwoFactorAuthenticationController;
use App\Http\Controllers\TagController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::middleware('auth')->group(function () {
    Route::get('/profile', function () {
        return Inertia::render('settings/Profile', []);
    })->name('profile');

    Route::put('settings/password', [PasswordController::class, 'update'])
        ->middleware('throttle:6,1')
        ->name('user-password.update');

    Route::resource('settings/tags', TagController::class)
        ->only(['index', 'store', 'destroy']);

    //    Route::get('settings/two-factor', [TwoFactorAuthenticationController::class, 'show'])
    //        ->name('two-factor.show');
});
