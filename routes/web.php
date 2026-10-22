<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\EventImageController;
use App\Http\Controllers\EventReactionController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\TodoController;
use App\Http\Controllers\TodoReactionController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }

    return Inertia::render('LandingPage', []);
})->name('landingPage');

Route::middleware(['auth'])->group(function () {
    Route::put('/event/{event}/reactions/{reaction}', [EventReactionController::class, 'store'])->name('event.reactions.store');
    Route::delete('/event/{event}/reactions/{reaction}', [EventReactionController::class, 'destroy'])->name('event.reactions.destroy');
    Route::put('/todo/{todo}/reactions/{reaction}', [TodoReactionController::class, 'store'])->name('todo.reactions.store');
    Route::delete('/todo/{todo}/reactions/{reaction}', [TodoReactionController::class, 'destroy'])->name('todo.reactions.destroy');

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
    Route::post('/todo/{todo}/postpone', [TodoController::class, 'postpone'])->name('todo.postpone');
    Route::post('/todo/postpone-by-date', [TodoController::class, 'postponeByDate'])->name('todo.postponeByDate');

    Route::resource('/todo', TodoController::class)
        ->only(['index', 'create', 'store', 'edit', 'update', 'destroy', 'show']);

    Route::resource('/chat', MessageController::class)->only(['index', 'store']);

    Route::resource('/document', DocumentController::class)
        ->only(['index', 'create', 'store', 'edit', 'update', 'destroy', 'show']);
});

require __DIR__.'/settings.php';
