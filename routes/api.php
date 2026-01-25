<?php

use App\Http\Controllers\Api\WakeController;
use Illuminate\Support\Facades\Route;

Route::post('/wake', WakeController::class)->name('wake');
