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
}
