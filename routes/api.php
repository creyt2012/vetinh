<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\LiveStateController;
use App\Http\Controllers\Api\V1\SatelliteController;
use App\Http\Controllers\Api\V1\WeatherController;

Route::middleware(['auth.api_key', \App\Http\Middleware\CheckApiKeyLimits::class])->prefix('v1')->group(function () {
    Route::get('/live/state', [LiveStateController::class, 'index']);

    // Satellites
    Route::get('/satellites/live', [SatelliteController::class, 'index']);
    Route::get('/health', [\App\Http\Controllers\Api\V1\HealthController::class, 'check']);
    Route::get('/plans', [\App\Http\Controllers\Api\V1\PaymentController::class, 'plans']);

    // Payments
    Route::post('/payments/checkout', [\App\Http\Controllers\Api\V1\PaymentController::class, 'checkout']);
    Route::post('/payments/webhook/{gateway}', [\App\Http\Controllers\Api\V1\PaymentController::class, 'webhook']);

    // Weather
    Route::get('/weather/latest', [WeatherController::class, 'latest']);
    Route::get('/weather/metrics', [WeatherController::class, 'metrics']);
    Route::get('/weather/ground-stations', [WeatherController::class, 'groundStations']);
    Route::get('/weather/history', [WeatherController::class, 'locationHistory']);
    Route::get('/weather/heatmap', [WeatherController::class, 'heatmap']);
});
