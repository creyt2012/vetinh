<?php

namespace Vortex\Aerospace;

use App\Models\Satellite;
use App\Models\Conjunction;
use App\Services\Notifications\NotificationService;
use Illuminate\Support\Facades\Log;

class ConjunctionEngine
{
    private const WGS84_A = 6378.137;
    private const WGS84_E2 = 0.00669437999014;

    /**
     * Check for conjunctions among a set of satellites.
     * 
     * @param array $states Array of satellite state data (id, lat, lng, alt)
     */
    public function analyze(array $states): void
    {
        $count = count($states);
        if ($count < 2)
            return;

        // Convert all to ECEF for fast distance calculation
        $positions = [];
        foreach ($states as $state) {
            $positions[$state['id']] = $this->geodeticToEcef(
                $state['latitude'],
                $state['longitude'],
                $state['altitude']
            );
        }

        $threshold = 50.0; // 50km threshold for warning

        for ($i = 0; $i < $count; $i++) {
            for ($j = $i + 1; $j < $count; $j++) {
                $satA = $states[$i];
                $satB = $states[$j];

                $dist = $this->calculateDistance($positions[$satA['id']], $positions[$satB['id']]);

                if ($dist < $threshold) {
                    $this->registerConjunction($satA['id'], $satB['id'], $dist);
                }
            }
        }
    }

    /**
     * Convert Geodetic (Lat/Lng/Alt) to ECEF (X/Y/Z)
     */
    private function geodeticToEcef(float $lat, float $lng, float $alt): array
    {
        $latRad = deg2rad($lat);
        $lngRad = deg2rad($lng);

        $N = self::WGS84_A / sqrt(1 - self::WGS84_E2 * pow(sin($latRad), 2));

        $x = ($N + $alt) * cos($latRad) * cos($lngRad);
        $y = ($N + $alt) * cos($latRad) * sin($lngRad);
        $z = ($N * (1 - self::WGS84_E2) + $alt) * sin($latRad);

        return ['x' => $x, 'y' => $y, 'z' => $z];
    }

    private function calculateDistance(array $posA, array $posB): float
    {
        return sqrt(
            pow($posA['x'] - $posB['x'], 2) +
            pow($posA['y'] - $posB['y'], 2) +
            pow($posA['z'] - $posB['z'], 2)
        );
    }

    private function registerConjunction(int $idA, int $idB, float $dist): void
    {
        $status = $dist < 10 ? 'CRITICAL' : 'WARNING';

        // Update existing or create new
        $conjunction = Conjunction::updateOrCreate(
            [
                'satellite_a_id' => min($idA, $idB),
                'satellite_b_id' => max($idA, $idB)
            ],
            [
                'distance' => $dist,
                'status' => $status,
                'tca' => now(), // Simplified TCA to 'now' for real-time detection
                'probability' => 1 - ($dist / 100) // Rough placeholder for probability
            ]
        );

        if ($status === 'CRITICAL' && $conjunction->wasRecentlyCreated) {
            Log::alert("CRITICAL CONJUNCTION DETECTED: SATS {$idA} & {$idB} | DIST: {$dist}km");
            // Trigger multi-channel notification
            app(NotificationService::class)->broadcastAlert(
                'ORBITAL_CONJUNCTION',
                "Potential collision detected between objects {$idA} and {$idB}. Min distance: {$dist}km",
                'CRITICAL'
            );
        }
    }
}
