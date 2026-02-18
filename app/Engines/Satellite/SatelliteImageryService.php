<?php

namespace App\Engines\Satellite;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SatelliteImageryService
{
    /**
     * Generate a tactical nadir view URL for a given position.
     * We use a hybrid approach: High-res public tiles for the 'Tactical' view.
     */
    public function getNadirViewUrl(float $lat, float $lng, int $zoom = 13): string
    {
        // Using ESRI World Imagery for high-resolution 'tactical' snapshots
        // This provides better zoom levels than most scientific APIs
        return "https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{$this->latLngToTile($lat, $lng, $zoom)}";
    }

    /**
     * Map Lat/Lng/Zoom to XYZ tile coordinates (Slippy Map).
     */
    private function latLngToTile(float $lat, float $lng, int $z): string
    {
        $x = floor((($lng + 180) / 360) * pow(2, $z));
        $y = floor((1 - log(tan(deg2rad($lat)) + 1 / cos(deg2rad($lat))) / M_PI) / 2 * pow(2, $z));

        return "{$z}/{$y}/{$x}";
    }

    /**
     * Calculate visibility status for a ground station.
     */
    public function getGroundStationVisibility(float $satLat, float $satLng, float $satAlt): array
    {
        // Ground Station: Hanoi HQ
        $gsLat = 21.0285;
        $gsLng = 105.8542;

        $distance = $this->calculateDistance($satLat, $satLng, $gsLat, $gsLng);

        // Simulating access based on horizon and range
        $maxRange = sqrt(pow($satAlt + 6371, 2) - pow(6371, 2));

        return [
            'is_in_range' => $distance < $maxRange,
            'distance_km' => round($distance, 2)
        ];
    }

    private function calculateDistance($lat1, $lon1, $lat2, $lon2)
    {
        $earthRadius = 6371;
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat / 2) * sin($dLat / 2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) * sin($dLon / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        return $earthRadius * $c;
    }
}
