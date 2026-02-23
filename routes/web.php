<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\EventImageController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\TodoController;
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

    Route::get('/event/history', [DashboardController::class, 'historyIndex'])->name('event.historyIndex');

    Route::get('/event/deleted', [EventController::class, 'deletedIndex'])->name('event.deletedIndex');
    Route::post('/event/{event}/restore', [EventController::class, 'restore'])
        ->name('event.restore')
        ->withTrashed();

    Route::get('/event/{event}/image', [EventImageController::class, 'show'])->name('event.image.show');

    Route::resource('/event', EventController::class)
        ->only(['create', 'store', 'edit', 'update', 'destroy', 'show']);

    Route::get('/todo/deleted', [TodoController::class, 'deletedIndex'])->name('todo.deletedIndex');
    Route::post('/todo/{todo}/restore', [TodoController::class, 'restore'])
        ->name('todo.restore')
        ->withTrashed();
    Route::post('/todo/{todo}/finish', [TodoController::class, 'finish'])->name('todo.finish');

    Route::resource('/todo', TodoController::class)
        ->only(['index', 'create', 'store', 'edit', 'update', 'destroy', 'show']);

    Route::resource('/chat', MessageController::class)->only(['index', 'store']);
});

require __DIR__.'/settings.php';
