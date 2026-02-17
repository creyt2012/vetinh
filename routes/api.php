<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\LiveStateController;
use App\Http\Controllers\Api\V1\SatelliteController;
use App\Http\Controllers\Api\V1\WeatherController;
use App\Http\Controllers\Api\V1\MissionControlController;
use Illuminate\Http\Request;

// Internal Map Data APIs (Accessible via Session OR API Key)
Route::middleware(['web', 'auth'])->prefix('v1/map')->group(function () {
    Route::get('/satellites', [WeatherController::class, 'satellites']);
    Route::get('/storms', function () {
        return \App\Models\Storm::where('status', 'active')->get();
    });
    Route::get('/radar-config', function () {
        return response()->json([
            'status' => 'success',
            'data' => \Illuminate\Support\Facades\Cache::get('radar_config_latest')
        ]);
    });
});

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
    Route::get('/weather/forecast', [WeatherController::class, 'forecast']);
    Route::get('/weather/point-info', [WeatherController::class, 'pointInfo']);
    Route::get('/weather/trends', function () {
        return \App\Models\DailyWeatherSummary::latest()->limit(30)->get();
    });

    // Mission Control
    Route::get('/mission-control/files', [MissionControlController::class, 'index']);
    Route::post('/mission-control/upload', [MissionControlController::class, 'store']);
    Route::get('/mission-control/files/{missionFile}', [MissionControlController::class, 'show']);
});
