<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('LandingPage', []);
})->name('landingPage');

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard', []);
})->name('dashboard');

require __DIR__.'/settings.php';
