<?php

namespace App\Jobs;

use App\Models\WeatherMetric;
use App\Engines\Weather\HimawariService;
use App\Engines\Weather\HimawariProcessor;
use App\Engines\Analytics\RiskEngine;
use App\Events\WeatherMetricsUpdated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class HimawariIngestJob implements ShouldQueue
{
    use Queueable;

    public function handle(
        HimawariService $service,
        HimawariProcessor $processor,
        \App\Services\AI\AICoreClient $aiClient,
        RiskEngine $riskEngine,
        \App\Repositories\StateRepository $stateRepo
    ): void {
        try {
            // 1. Fetch latest imagery
            $imagePath = $service->downloadLatest();

            // 2. Process imagery through AI Core Microservice (Python/OpenCV)
            $lat = 16.0;
            $lng = 108.0;

            $aiResults = $aiClient->analyzeImagery($imagePath, $lat, $lng);

            if (!$aiResults || $aiResults['status'] !== 'success') {
                throw new \Exception("AI Core Analysis Failed or returned invalid status.");
            }

            // 3. Save Metrics from AI Core Data
            $metric = WeatherMetric::create([
                'source' => 'Himawari-9 (AI Enhanced)',
                'captured_at' => now(),
                'latitude' => $lat,
                'longitude' => $lng,
                'cloud_coverage' => $aiResults['cloud_coverage_pct'],
                'cloud_density' => $aiResults['cloud_coverage_pct'] * 0.9,
                'rain_intensity' => max(0, ($aiResults['cloud_coverage_pct'] - 50) / 2), // Example proxy
                'pressure' => $aiResults['pressure_hpa'],
                'data_sources' => ['JMA', 'Himawari-9', 'StarWeather AI Core'],
                'provenance' => [
                    'sensor' => 'AHI',
                    'engine' => $aiResults['metadata']['engine'],
                    'temp_derived' => $aiResults['temperature_c'],
                    'wind_speed' => $aiResults['wind_speed_kmh'],
                    'wind_direction' => $aiResults['wind_direction_deg'],
                    'timestamp' => $aiResults['timestamp']
                ]
            ]);

            // 4. Compute Risk & Confidence
            $previous = WeatherMetric::where('id', '!=', $metric->id)->latest('captured_at')->first();
            $risk = $riskEngine->computeRiskScore($metric, $previous);

            // 5. Update Record & L1 Cache
            $metric->update([
                'risk_score' => $risk['score'],
                'risk_level' => $risk['level'],
                'confidence_score' => $risk['confidence'],
                'cloud_growth_rate' => $risk['breakdown']['growth']
            ]);

            $stateRepo->setLatestWeather($metric);

            event(new WeatherMetricsUpdated($metric, $risk));

            Log::info("Enterprise Ingestion Complete. Risk: {$risk['score']}, Conf: {$risk['confidence']}");

        } catch (\Exception $e) {
            Log::error("Himawari Ingestion Failed: " . $e->getMessage());
        }
    }
}
