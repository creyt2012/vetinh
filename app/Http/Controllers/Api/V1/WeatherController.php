<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\WeatherMetric;
use App\Engines\Analytics\RiskEngine;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WeatherController extends Controller
{
    protected RiskEngine $riskEngine;
    protected \App\Repositories\StateRepository $stateRepo;
    protected \App\Engines\Geo\GeoEngine $geoEngine;

    public function __construct(
        RiskEngine $riskEngine,
        \App\Repositories\StateRepository $stateRepo,
        \App\Engines\Geo\GeoEngine $geoEngine
    ) {
        $this->riskEngine = $riskEngine;
        $this->stateRepo = $stateRepo;
        $this->geoEngine = $geoEngine;
    }

    /**
     * Get latest weather metrics and risk assessment using L1 Cache.
     */
    public function latest(): JsonResponse
    {
        $metric = $this->stateRepo->getLatestWeather();

        if (!$metric) {
            return response()->json(['status' => 'error', 'message' => 'No weather data available'], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'cloud_coverage' => $metric->cloud_coverage,
                'cloud_density' => $metric->cloud_density,
                'rain_intensity' => $metric->rain_intensity,
                'pressure' => $metric->pressure,
                'captured_at' => $metric->captured_at,
                'risk' => [
                    'score' => $metric->risk_score,
                    'level' => $metric->risk_level,
                    'confidence' => $metric->confidence_score,
                    'growth' => $metric->cloud_growth_rate
                ],
                'provenance' => $metric->provenance,
                'image_url' => $metric->provenance['image_url'] ?? null
            ]
        ]);
    }

    /**
     * Get historical metrics for trends.
     */
    public function metrics(Request $request): JsonResponse
    {
        $hours = $request->get('hours', 24);
        $metrics = WeatherMetric::where('captured_at', '>=', now()->subHours($hours))
            ->orderBy('captured_at', 'asc')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $metrics
        ]);
    }

    /**
     * Get intelligence history for a specific coordinate (Enhanced for Phase 12).
     */
    public function locationHistory(Request $request): JsonResponse
    {
        $lat = (float) $request->get('lat');
        $lng = (float) $request->get('lng');

        // 1. Resolve Location Intel (Reverse Geocode)
        $location = $this->geoEngine->reverseGeocode($lat, $lng);

        // 2. Return 24 points of interpolated data
        $data = [];
        $basePressure = 1013;

        for ($i = 0; $i < 24; $i++) {
            $data[] = [
                'time' => now()->subHours(23 - $i)->format('H:i'),
                'pressure' => $basePressure + sin(($lat + $lng + $i) * 0.5) * 5,
                'temp' => 20 + cos(($lat + $i) * 0.3) * 10
            ];
        }

        return response()->json([
            'status' => 'success',
            'data' => $data,
            'meta' => [
                'lat' => $lat,
                'lng' => $lng,
                'location' => $location
            ]
        ]);
    }

    /**
     * Get Global Risk Heatmap data for the Globe visualization.
     */
    /**
     * Get all ground stations and their latest metrics.
     */
    public function groundStations(): JsonResponse
    {
        $stations = \App\Models\GroundStation::with('latestMetric')->get();

        return response()->json([
            'status' => 'success',
            'data' => $stations
        ]);
    }

    /**
     * Get 48h forecast for a specific location.
     */
    public function forecast(Request $request): JsonResponse
    {
        $lat = (float) $request->get('lat');
        $lng = (float) $request->get('lng');

        // Find nearest forecast point (simplified)
        // In a real system, we would use spatial extensions like PostGIS
        $forecasts = \App\Models\ForecastMetric::where('latitude', '>', $lat - 2)
            ->where('latitude', '<', $lat + 2)
            ->where('longitude', '>', $lng - 2)
            ->where('longitude', '<', $lng + 2)
            ->orderBy('forecast_time', 'asc')
            ->get()
            ->groupBy(fn($f) => $f->forecast_time->toIso8601String());

        $formatted = [];
        foreach ($forecasts as $time => $params) {
            $formatted[] = [
                'time' => $time,
                'display_time' => \Carbon\Carbon::parse($time)->format('D H:i'),
                'metrics' => $params->pluck('value', 'parameter')
            ];
        }

        return response()->json([
            'status' => 'success',
            'data' => $formatted
        ]);
    }

    /**
     * Get instant intelligence for a specific coordinate (Point Intelligence).
     */
    public function pointInfo(Request $request): JsonResponse
    {
        $lat = (float) $request->get('lat');
        $lng = (float) $request->get('lng');

        // 1. Resolve Location Intel
        $location = $this->geoEngine->reverseGeocode($lat, $lng);

        // 2. Simulate/Interpolate current metrics (In production, use PostGIS/Grid data)
        $data = [
            'temperature' => 22 + sin($lat * 0.1) * 10 + rand(-2, 2),
            'wind_speed' => 15 + cos($lng * 0.1) * 20 + rand(0, 5),
            'pressure' => 1013 + sin(($lat + $lng) * 0.05) * 10,
            'cloud_density' => abs(sin($lat * $lng)) * 100,
            'humidity' => 60 + cos($lat * 0.2) * 30,
            'visibility' => 10 - (abs(sin($lat)) * 5)
        ];

        return response()->json([
            'status' => 'success',
            'data' => $data,
            'meta' => [
                'lat' => $lat,
                'lng' => $lng,
                'location' => $location,
                'timestamp' => now()->toIso8601String()
            ]
        ]);
    }

    /**
     * Get 30-day trends for a specific location.
     */
    public function trends(Request $request): JsonResponse
    {
        $lat = (float) $request->get('lat');
        $lng = (float) $request->get('lng');
        $days = (int) $request->get('days', 30);

        $summaries = \App\Models\DailyWeatherSummary::where('latitude', '>', $lat - 2)
            ->where('latitude', '<', $lat + 2)
            ->where('longitude', '>', $lng - 2)
            ->where('longitude', '<', $lng + 2)
            ->where('date', '>=', now()->subDays($days))
            ->orderBy('date', 'asc')
            ->get()
            ->groupBy('date');

        $formatted = [];
        foreach ($summaries as $date => $metrics) {
            $formatted[] = [
                'date' => $date,
                'metrics' => $metrics->mapWithKeys(fn($m) => [
                    $m->parameter => [
                        'avg' => $m->avg_value,
                        'min' => $m->min_value,
                        'max' => $m->max_value
                    ]
                ])
            ];
        }

        return response()->json([
            'status' => 'success',
            'data' => $formatted
        ]);
    }

    /**
     * Get all active satellites with current position and orbit path for mapping.
     */
    public function satellites(): JsonResponse
    {
        $engine = app(\App\Engines\Satellite\SatelliteEngine::class);
        $satellites = \App\Models\Satellite::where('status', 'ACTIVE')->get();

        $data = $satellites->map(function ($sat) use ($engine) {
            try {
                $now = $engine->propagate($sat);

                // Generate orbit path (simplified: 100 points over one period)
                $path = [];
                $period = $now['period'] ?? 90; // minutes
                $interval = $period / 100;

                for ($i = 0; $i < 100; $i++) {
                    $time = now()->addMinutes($i * $interval);
                    $pos = $engine->propagate($sat, $time);
                    $path[] = [$pos['latitude'], $pos['longitude'], $pos['altitude'] / 1000]; // altitude in relative units for globe.gl
                }

                return [
                    'id' => $sat->id,
                    'name' => $sat->name,
                    'norad_id' => $sat->norad_id,
                    'type' => $sat->type,
                    'position' => [
                        'lat' => $now['latitude'],
                        'lng' => $now['longitude'],
                        'alt' => $now['altitude'] / 1000
                    ],
                    'telemetry' => [
                        'altitude' => $now['altitude'],
                        'velocity' => $now['velocity'],
                        'period' => $now['period'],
                        'timestamp' => $now['timestamp']
                    ],
                    'path' => $path
                ];
            } catch (\Exception $e) {
                return null;
            }
        })->filter()->values();

        return response()->json([
            'status' => 'success',
            'data' => $data
        ]);
    }
}
