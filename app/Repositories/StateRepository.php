<?php

namespace App\Repositories;

use App\Models\WeatherMetric;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class StateRepository
{
    private const CACHE_PREFIX = 'vetinh_state:';

    /**
     * Cache the latest weather metrics for L1 retrieval.
     */
    public function setLatestWeather(WeatherMetric $metric): void
    {
        try {
            Cache::put(self::CACHE_PREFIX . 'weather_latest', $metric->toArray(), now()->addHour());
        } catch (\Exception $e) {
            Log::warning("Cache failure in setLatestWeather: " . $e->getMessage());
        }
    }

    /**
     * Get the latest weather metrics from cache or DB fallback.
     */
    public function getLatestWeather(): ?WeatherMetric
    {
        try {
            $cached = Cache::get(self::CACHE_PREFIX . 'weather_latest');
            if ($cached) {
                return new WeatherMetric($cached);
            }
        } catch (\Exception $e) {
            Log::warning("Cache failure in getLatestWeather: " . $e->getMessage());
        }

        return WeatherMetric::orderBy('captured_at', 'desc')->first();
    }

    /**
     * Cache the latest track for a satellite.
     */
    public function setSatelliteState(int $satelliteId, array $trackData): void
    {
        try {
            $states = Cache::get(self::CACHE_PREFIX . 'satellites', []);
            $states[$satelliteId] = $trackData;
            Cache::put(self::CACHE_PREFIX . 'satellites', $states, now()->addMinutes(10));
        } catch (\Exception $e) {
            Log::warning("Cache failure in setSatelliteState: " . $e->getMessage());
        }
    }

    /**
     * Get all satellite states from cache.
     */
    public function getSatelliteStates(): array
    {
        try {
            return Cache::get(self::CACHE_PREFIX . 'satellites', []);
        } catch (\Exception $e) {
            Log::warning("Cache failure in getSatelliteStates: " . $e->getMessage());
            return [];
        }
    }
}
