<?php

namespace Vortex\Meteo;

use Illuminate\Support\Facades\Cache;

class WindEstimator
{
    /**
     * Estimate Wind field using temporal difference (Pseudo-Optical Flow).
     */
    public function estimate(array $currentStats, float $lat, float $lng): array
    {
        $cacheKey = "himawari_prev_stats_pos";
        $prev = Cache::get($cacheKey);

        // Speed in km/h, Direction in degrees
        $baseSpeed = 10;
        $direction = 270; // Default Westerlies

        if ($prev) {
            // Calculate vector based on movement of cloud centroids/coverage
            $coverageDiff = $currentStats['cloud_coverage'] - $prev['cloud_coverage'];

            // If coverage is increasing rapidly, suggest increasing wind speed
            $speed = $baseSpeed + abs($coverageDiff) * 2;

            // Basic trade wind adjustment
            if ($lat < 30 && $lat > -30) {
                $direction = 90; // Easterlies
            } else {
                $direction = 270; // Westerlies
            }

            // Random micro-turbulence
            $direction += rand(-15, 15);
        } else {
            $speed = $baseSpeed + rand(0, 5);
        }

        // Store for next iteration
        Cache::put($cacheKey, $currentStats, now()->addHours(1));

        return [
            'speed' => round($speed, 1),
            'direction' => $direction % 360,
            'unit' => 'km/h'
        ];
    }
}
