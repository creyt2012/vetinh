<?php

namespace App\Services\Intelligence;

use App\Models\WeatherMetric;
use App\Models\SatelliteTrack;
use Illuminate\Support\Collection;

class IntelligenceService
{
    /**
     * Correlate weather metrics with the nearest satellite track for provenance.
     */
    public function correlateMetricWithTrack(WeatherMetric $metric): ?SatelliteTrack
    {
        // Find tracks within 5 minutes of capture
        $startTime = $metric->captured_at->subMinutes(5);
        $endTime = $metric->captured_at->addMinutes(5);

        return SatelliteTrack::whereBetween('tracked_at', [$startTime, $endTime])
            ->orderByRaw("ABS(latitude - ?) + ABS(longitude - ?)", [$metric->latitude, $metric->longitude])
            ->first();
    }

    /**
     * Detect environmental anomalies in a specific region.
     */
    public function detectRegionalAnomalies(float $lat, float $lng, float $radius = 2.0): Collection
    {
        $metrics = WeatherMetric::whereBetween('latitude', [$lat - $radius, $lat + $radius])
            ->whereBetween('longitude', [$lng - $radius, $lng + $radius])
            ->where('captured_at', '>=', now()->subHours(6))
            ->get();

        return $metrics->filter(function ($m) {
            // Logic for anomaly detection
            $temp = $m->provenance['temp_derived'] ?? 0;
            $isTempAnomaly = $temp > 45 || $temp < -20;
            $isPressureAnomaly = $m->pressure > 1040 || $m->pressure < 950;

            return $isTempAnomaly || $isPressureAnomaly;
        });
    }
}
