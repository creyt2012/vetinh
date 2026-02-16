<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\LiveStateController;
use App\Http\Controllers\Api\V1\SatelliteController;
use App\Http\Controllers\Api\V1\WeatherController;

Route::middleware(['auth.api_key', \App\Http\Middleware\CheckApiKeyLimits::class])->prefix('v1')->group(function () {
    Route::get('/live/state', [LiveStateController::class, 'index']);

    // Satellites
    Route::get('/satellites/live', [SatelliteController::class, 'index']);

    // Weather
    Route::get('/weather/latest', [WeatherController::class, 'latest']);
    Route::get('/weather/metrics', [WeatherController::class, 'metrics']);
    Route::get('/weather/history', [WeatherController::class, 'locationHistory']);
    Route::get('/weather/heatmap', [WeatherController::class, 'heatmap']);
});
