<?php

use App\Http\Controllers\Api\SensorReadingController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function (): void {
    Route::post('/sensor', [SensorReadingController::class, 'store']);
    Route::get('/readings', [SensorReadingController::class, 'index']);
});
