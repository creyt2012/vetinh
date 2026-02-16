<?php

namespace App\Repositories;

use App\Models\WeatherMetric;
use App\Models\SatelliteTrack;
use Illuminate\Support\Facades\Redis;

class StateRepository
{
    private const CACHE_PREFIX = 'vetinh_state:';

    /**
     * Cache the latest weather metrics for L1 retrieval.
     */
    public function setLatestWeather(WeatherMetric $metric): void
    {
        Redis::set(self::CACHE_PREFIX . 'weather_latest', $metric->toJson(), 'EX', 3600);
    }

    /**
     * Get the latest weather metrics from L1 cache or DB fallback.
     */
    public function getLatestWeather(): ?WeatherMetric
    {
        $cached = Redis::get(self::CACHE_PREFIX . 'weather_latest');

        if ($cached) {
            return new WeatherMetric(json_decode($cached, true));
        }

        return WeatherMetric::orderBy('captured_at', 'desc')->first();
    }

    /**
     * Cache the latest track for a satellite.
     */
    public function setSatelliteState(int $satelliteId, array $trackData): void
    {
        Redis::hset(self::CACHE_PREFIX . 'satellites', (string) $satelliteId, json_encode($trackData));
    }

    /**
     * Get all satellite states from L1 cache.
     */
    public function getSatelliteStates(): array
    {
        $states = Redis::hgetall(self::CACHE_PREFIX . 'satellites');
        return array_map(fn($s) => json_decode($s, true), $states);
    }
}
