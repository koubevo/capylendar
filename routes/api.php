<?php

use App\Http\Controllers\Api\RelationshipSummaryController;
use App\Http\Controllers\Api\WakeController;
use App\Http\Controllers\Api\Watch\EventController;
use App\Http\Controllers\Api\Watch\PairingController;
use App\Http\Controllers\Api\Watch\TodoController;
use Illuminate\Support\Facades\Route;

Route::post('/wake', WakeController::class)
    ->middleware('throttle:6,1')
    ->name('wake');

Route::prefix('watch')->name('watch.')->group(function () {
    Route::post('/pairings', [PairingController::class, 'store'])
        ->middleware('throttle:10,1')
        ->name('pairings.store');
    Route::post('/pairings/token', [PairingController::class, 'claim'])
        ->middleware('throttle:watch-pairing-claim')
        ->name('pairings.claim');

    Route::middleware(['auth:watch', 'throttle:60,1'])->group(function () {
        Route::get('/events', [EventController::class, 'index'])->name('events.index');
        Route::get('/todos', [TodoController::class, 'index'])->name('todos.index');
        Route::patch('/todos/{todo}/completion', [TodoController::class, 'updateCompletion'])->name('todos.completion.update');
        Route::get('/relationship-summary', RelationshipSummaryController::class)->name('relationship.summary');
    });
});
