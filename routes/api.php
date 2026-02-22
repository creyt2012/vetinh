<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\LiveStateController;
use App\Http\Controllers\Api\V1\SatelliteController;
use App\Http\Controllers\Api\V1\WeatherController;
use App\Http\Controllers\Api\V1\MissionControlController;
use Illuminate\Http\Request;

Route::middleware(['auth.api_key', \App\Http\Middleware\CheckApiKeyLimits::class])->prefix('v1')->group(function () {
    Route::get('/live/state', [LiveStateController::class, 'index']);

    // Satellites & Orbital Safety
    Route::get('/satellites/live', [SatelliteController::class, 'index']);
    Route::get('/satellites/conjunctions', [\App\Http\Controllers\Api\V1\ConjunctionController::class, 'index']);
    Route::get('/satellites/{satellite}/telemetry', [SatelliteController::class, 'telemetry']);
    Route::get('/satellites/imagery-history', [SatelliteController::class, 'imageryHistory']);
    Route::get('/satellites/{satellite}/tle', [SatelliteController::class, 'tle']);

    Route::get('/health', [\App\Http\Controllers\Api\V1\HealthController::class, 'check']);
    Route::get('/health/system', [\App\Http\Controllers\Api\V1\HealthController::class, 'systemMetrics']);

    Route::get('/plans', [\App\Http\Controllers\Api\V1\PaymentController::class, 'plans']);

    // Payments
    Route::post('/payments/checkout', [\App\Http\Controllers\Api\V1\PaymentController::class, 'checkout']);
    Route::post('/payments/webhook/{gateway}', [\App\Http\Controllers\Api\V1\PaymentController::class, 'webhook']);

    // Weather, Storms & Tactical Zones
    Route::get('/weather/latest', [WeatherController::class, 'latest']);
    Route::get('/weather/metrics', [WeatherController::class, 'metrics']);
    Route::apiResource('/weather/ground-stations', \App\Http\Controllers\Api\V1\GroundStationController::class);
    Route::get('/weather/history', [WeatherController::class, 'locationHistory']);
    Route::get('/weather/heatmap', [WeatherController::class, 'heatmap']);
    Route::get('/weather/forecast', [WeatherController::class, 'forecast']);
    Route::get('/weather/point-info', [WeatherController::class, 'pointInfo']);
    Route::get('/weather/storms', [\App\Http\Controllers\Api\V1\StormController::class, 'index']);
    Route::get('/weather/storms/{storm}', [\App\Http\Controllers\Api\V1\StormController::class, 'show']);
    Route::get('/weather/storms/{storm}/vortex', [\App\Http\Controllers\Api\V1\StormController::class, 'vortex']);
    Route::get('/weather/risk-areas', [\App\Http\Controllers\Api\V1\RiskAreaController::class, 'index']);

    Route::get('/weather/trends', function () {
        return \App\Models\DailyWeatherSummary::latest()->limit(30)->get();
    });

    // Alerting & History
    Route::apiResource('/alerts/rules', \App\Http\Controllers\Api\V1\AlertRuleController::class);
    Route::get('/alerts/history', [\App\Http\Controllers\Api\V1\AlertHistoryController::class, 'index']);

    // Marine Tracking
    Route::get('/marine/vessels', [\App\Http\Controllers\Api\V1\MarineController::class, 'index']);

    // Mission Control & Reports
    Route::get('/mission-control/files', [MissionControlController::class, 'index']);
    Route::post('/mission-control/upload', [MissionControlController::class, 'store']);
    Route::get('/mission-control/files/{missionFile}', [MissionControlController::class, 'show']);
    Route::get('/reports', [\App\Http\Controllers\Api\V1\ReportController::class, 'index']);
    Route::get('/reports/{file}/download', [\App\Http\Controllers\Api\V1\ReportController::class, 'download']);

    Route::post('/internal/transmit', [WeatherController::class, 'transmit']);

    // STAC API Foundation
    Route::get('/stac', [\App\Http\Controllers\Api\V1\StacController::class, 'index']);
    Route::get('/stac/collections', [\App\Http\Controllers\Api\V1\StacController::class, 'collections']);
    Route::get('/stac/search', [\App\Http\Controllers\Api\V1\StacController::class, 'search']);
});
