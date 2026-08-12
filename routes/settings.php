<?php

use App\Http\Controllers\PushSubscriptionController;
use App\Http\Controllers\Settings\NotificationSettingsController;
use App\Http\Controllers\Settings\PasswordController;
use App\Http\Controllers\Settings\RelationshipSettingsController;
use App\Http\Controllers\Settings\WatchDeviceController;
use App\Http\Controllers\Settings\WatchPairingController;
use App\Http\Controllers\TagController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::middleware('auth')->group(function () {
    Route::get('/profile', fn () => Inertia::render('settings/Profile'))->name('profile');

    Route::get('/relationship', [RelationshipSettingsController::class, 'index'])
        ->name('relationship-settings.index');
    Route::put('/relationship', [RelationshipSettingsController::class, 'update'])
        ->name('relationship-settings.update');

    Route::get('/paired-devices', [WatchDeviceController::class, 'index'])
        ->name('watch-devices.index');

    Route::put('settings/password', [PasswordController::class, 'update'])
        ->middleware('throttle:6,1')
        ->name('user-password.update');

    Route::put('settings/notifications', [NotificationSettingsController::class, 'update'])
        ->name('user-notifications.update');

    Route::post('settings/push-subscription', [PushSubscriptionController::class, 'store'])
        ->name('push-subscription.store');
    Route::delete('settings/push-subscription', [PushSubscriptionController::class, 'destroy'])
        ->name('push-subscription.destroy');

    Route::resource('settings/tags', TagController::class)
        ->only(['index', 'store', 'destroy']);

    Route::post('settings/watch-pairings', [WatchPairingController::class, 'store'])
        ->middleware('throttle:10,1')
        ->name('watch-pairings.store');

    Route::delete('settings/watch-devices/{watchDevice}', [WatchDeviceController::class, 'destroy'])
        ->name('watch-devices.destroy');
});
