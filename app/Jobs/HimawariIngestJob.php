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
        RiskEngine $riskEngine
    ): void {
        try {
            // 1. Fetch latest imagery
            $imagePath = $service->downloadLatest();

            // 2. Process imagery (Crop VN, Get stats)
            $stats = $processor->processImage($imagePath);

            // 3. Save Metrics
            $metric = WeatherMetric::create([
                'source' => 'Himawari-9',
                'captured_at' => now(),
                'cloud_coverage' => $stats['cloud_coverage'],
                'rain_intensity' => $stats['rain_estimation'],
                'metadata' => [
                    'image_url' => $stats['image_url'],
                    'provider' => 'JMA'
                ]
            ]);

            // 4. Compute Risk & Broadcast
            $previous = WeatherMetric::where('id', '!=', $metric->id)->latest('captured_at')->first();
            $risk = $riskEngine->computeRiskScore($metric, $previous);

            event(new WeatherMetricsUpdated($metric, $risk));

            Log::info("Himawari ingestion complete. Risk Score: {$risk['score']}");

        } catch (\Exception $e) {
            Log::error("Himawari Ingestion Failed: " . $e->getMessage());
        }
    }
}
