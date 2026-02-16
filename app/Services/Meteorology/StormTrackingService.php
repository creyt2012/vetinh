<?php

namespace App\Services\Meteorology;

use App\Models\Storm;
use App\Models\WeatherMetric;
use Illuminate\Support\Facades\Log;

class StormTrackingService
{
    /**
     * Identify potential storms from recent metrics.
     */
    public function scanForVortices(): void
    {
        // Find areas with low pressure (< 1000 hPa) and high wind (> 60 km/h)
        $candidates = WeatherMetric::where('captured_at', '>', now()->subHours(1))
            ->where('pressure', '<', 1000)
            ->where('wind_speed', '>', 60)
            ->get();

        foreach ($candidates as $candidate) {
            $this->processCandidate($candidate);
        }
    }

    protected function processCandidate($metric): void
    {
        // Try to find an existing storm near this location (within 2 degrees)
        $storm = Storm::where('status', 'active')
            ->whereBetween('latitude', [$metric->latitude - 2, $metric->latitude + 2])
            ->whereBetween('longitude', [$metric->longitude - 2, $metric->longitude + 2])
            ->first();

        if ($storm) {
            $this->updateStorm($storm, $metric);
        } else {
            $this->initiateStorm($metric);
        }
    }

    protected function initiateStorm(WeatherMetric $metric): void
    {
        $name = 'STORM-' . strtoupper(bin2hex(random_bytes(2)));

        Storm::create([
            'name' => $name,
            'status' => 'active',
            'latitude' => $metric->latitude,
            'longitude' => $metric->longitude,
            'max_wind_speed' => $metric->wind_speed,
            'min_pressure' => $metric->pressure,
            'path_history' => [['lat' => $metric->latitude, 'lng' => $metric->longitude, 'time' => now()]],
            'last_updated_at' => now()
        ]);

        Log::info("NEW STORM DETECTED: {$name} at {$metric->latitude}, {$metric->longitude}");
    }

    protected function updateStorm(Storm $storm, WeatherMetric $metric): void
    {
        $history = $storm->path_history ?? [];
        $history[] = ['lat' => $metric->latitude, 'lng' => $metric->longitude, 'time' => now()];

        $storm->update([
            'latitude' => $metric->latitude,
            'longitude' => $metric->longitude,
            'max_wind_speed' => max($storm->max_wind_speed, $metric->wind_speed),
            'min_pressure' => min($storm->min_pressure, $metric->pressure),
            'path_history' => $history,
            'predicted_path' => $this->predictPath($history),
            'last_updated_at' => now()
        ]);
    }

    protected function predictPath(array $history): array
    {
        if (count($history) < 2)
            return [];

        // Basic linear extrapolation for demonstration
        $p1 = end($history);
        $p2 = prev($history);

        $dLat = $p1['lat'] - $p2['lat'];
        $dLng = $p1['lng'] - $p2['lng'];

        $predicted = [];
        for ($i = 1; $i <= 5; $i++) {
            $predicted[] = [
                'lat' => $p1['lat'] + ($dLat * $i),
                'lng' => $p1['lng'] + ($dLng * $i),
                'step' => $i * 6 // 6-hour intervals
            ];
        }

        return $predicted;
    }
}
