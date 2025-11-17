<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EventController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('LandingPage', []);
})->name('landingPage');

// TODO: auth
Route::get('/dashboard', DashboardController::class)
    ->name('dashboard');

// TODO: auth
Route::resource('/event', EventController::class)
    ->only(['create', 'store', 'edit', 'update', 'destroy']);

require __DIR__.'/settings.php';
