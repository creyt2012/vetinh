<?php

namespace App\Services\Orbital;

use App\Models\Satellite;
use App\Models\GroundStation;
use Vortex\Aerospace\SatelliteEngine;

class OrbitalService
{
    protected SatelliteEngine $engine;

    public function __construct(SatelliteEngine $engine)
    {
        $this->engine = $engine;
    }

    /**
     * Calculate visibility window for a satellite over a ground station.
     */
    public function calculateVisibility(Satellite $satellite, GroundStation $station): array
    {
        $prop = $this->engine->propagate($satellite);

        // Simple Euclidean distance for visibility proxy (3000km range)
        $distance = $this->calculateDistance(
            $prop['latitude'],
            $prop['longitude'],
            $station->latitude,
            $station->longitude
        );

        return [
            'is_visible' => $distance < 3000,
            'distance_km' => round($distance, 2),
            'elevation_proxy' => 90 - ($distance / 3000 * 90),
            'timestamp' => $prop['timestamp']
        ];
    }

    private function calculateDistance($lat1, $lon1, $lat2, $lon2): float
    {
        $R = 6371;
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLon / 2) * sin($dLon / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        return $R * $c;
    }
}
