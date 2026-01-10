<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EventController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }

    return Inertia::render('LandingPage', []);
})->name('landingPage');

Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', DashboardController::class)
        ->name('dashboard');

    Route::resource('/event', EventController::class)
        ->only(['create', 'store', 'edit', 'update', 'destroy', 'show']);
    Route::get('/event', [EventController::class, 'deletedIndex'])->name('event.deletedIndex');
    Route::post('/event/restore', [EventController::class, 'restore'])->name('event.restore');
});

require __DIR__.'/settings.php';
