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

    public function __construct(RiskEngine $riskEngine, \App\Repositories\StateRepository $stateRepo)
    {
        $this->riskEngine = $riskEngine;
        $this->stateRepo = $stateRepo;
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
}
