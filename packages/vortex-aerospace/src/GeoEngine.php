<?php

namespace Vortex\Aerospace;

use App\Models\WeatherMetric;
use App\Engines\Analytics\RiskEngine;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class GeoEngine
{
    protected RiskEngine $riskEngine;

    public function __construct(RiskEngine $riskEngine)
    {
        $this->riskEngine = $riskEngine;
    }

    /**
     * Resolve latitude and longitude to Vietnamese administrative levels.
     * For demo purposes, we use a sophisticated mock based on approximate bounding boxes.
     */
    public function reverseGeocode(float $lat, float $lng): array
    {
        // Vietnam Bounding Box (Approximate)
        if ($lat < 8.1 || $lat > 23.4 || $lng < 102.1 || $lng > 109.5) {
            return [
                'area' => 'International Waters / Territory',
                'province' => 'Global Sector',
                'district' => 'Maritime Zone',
                'commune' => 'Outer Sector'
            ];
        }

        // High-fidelity Mock for Demo Regions
        if ($lat > 20.5)
            return $this->mockNorth($lat, $lng);
        if ($lat > 14.0)
            return $this->mockCentral($lat, $lng);
        return $this->mockSouth($lat, $lng);
    }

    /**
     * Resolve global coordinates to a readable address string.
     * Uses OpenStreetMap Nominatim with heavy caching to prevent rate limits.
     */
    public function getGlobalLocation(float $lat, float $lng): string
    {
        // Accuracy: 0.1 deg (~11km) is enough for city/province naming
        $cacheKey = "geo_loc_" . round($lat, 1) . "_" . round($lng, 1);

        return Cache::remember($cacheKey, now()->addDays(7), function () use ($lat, $lng) {
            try {
                $response = Http::withHeaders([
                    'User-Agent' => 'StarWeather_Mission_Control_v2'
                ])->get('https://nominatim.openstreetmap.org/reverse', [
                            'lat' => $lat,
                            'lon' => $lng,
                            'format' => 'json',
                            'zoom' => 10, // City level
                            'addressdetails' => 1
                        ]);

                if ($response->successful()) {
                    $data = $response->json();
                    $address = $data['address'] ?? [];

                    $city = $address['city'] ?? $address['town'] ?? $address['village'] ?? $address['state'] ?? 'International Waters';
                    $country = $address['country'] ?? '';

                    return $country ? "{$city}, {$country}" : $city;
                }
            } catch (\Exception $e) {
                // Fail gracefully to ocean mapping
            }

            return $this->guessOcean($lat, $lng);
        });
    }

    /**
     * Fallback for maritime or failed API calls.
     */
    private function guessOcean(float $lat, float $lng): string
    {
        if ($lng > -30 && $lng < 40)
            return 'Atlantic Ocean';
        if ($lng >= 40 && $lng < 100)
            return 'Indian Ocean';
        if ($lng >= 100 || $lng <= -120)
            return 'Pacific Ocean';
        if ($lat > 60)
            return 'Arctic Ocean';
        if ($lat < -60)
            return 'Southern Ocean';

        return 'International Waters';
    }

    /**
     * Generate a global risk heatmap for the globe texture.
     * Returns an array of coordinates with risk scores.
     */
    public function getRiskHeatmap(): array
    {
        $heatmap = [];
        // Generate a grid of points with simulated high-risk zones (e.g., Coastal Vietnam)
        for ($lat = 8; $lat <= 23; $lat += 0.5) {
            for ($lng = 102; $lng <= 110; $lng += 0.5) {
                // Base risk + proximity to coast + random atmospheric noise
                $baseRisk = 30;
                $coastalBonus = ($lng > 107) ? 40 : 10;
                $stormNoise = sin($lat * 0.5) * cos($lng * 0.3) * 20;

                $score = min(100, max(0, $baseRisk + $coastalBonus + $stormNoise));

                $heatmap[] = [
                    'lat' => $lat,
                    'lng' => $lng,
                    'score' => $score,
                    'level' => $score > 70 ? 'CRITICAL' : ($score > 40 ? 'WARNING' : 'STABLE')
                ];
            }
        }
        return $heatmap;
    }

    private function mockNorth($lat, $lng): array
    {
        return [
            'area' => 'Red River Delta',
            'province' => 'Hà Nội',
            'district' => 'Hoàn Kiếm',
            'commune' => 'Tràng Tiền'
        ];
    }

    private function mockCentral($lat, $lng): array
    {
        return [
            'area' => 'Central Coast',
            'province' => 'Đà Nẵng',
            'district' => 'Hải Châu',
            'commune' => 'Thạch Thang'
        ];
    }

    private function mockSouth($lat, $lng): array
    {
        return [
            'area' => 'Mekong Delta',
            'province' => 'Hồ Chí Minh',
            'district' => 'Quận 1',
            'commune' => 'Bến Nghé'
        ];
    }
}
