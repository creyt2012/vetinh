<?php

namespace App\Engines\Satellite;

use App\Models\Satellite;
use App\Engines\Weather\AtmosphericModel;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class SatelliteTelemetryManager
{
    public function __construct(
        protected SatelliteEngine $engine,
        protected AtmosphericModel $atmosphericModel
    ) {
    }

    /**
     * Process telemetry for all active satellites and store as JSON.
     */
    public function ingestAll(): array
    {
        $satellites = Satellite::where('status', 'ACTIVE')->get();
        $results = [];

        Log::info("Satellite Telemetry Ingestion Started for " . $satellites->count() . " assets.");

        foreach ($satellites as $satellite) {
            try {
                $telemetry = $this->captureTelemetry($satellite);
                $this->storeTelemetry($satellite, $telemetry);
                $results[$satellite->norad_id] = true;
            } catch (\Exception $e) {
                Log::error("Telemetry Ingestion Failed for {$satellite->name} ({$satellite->norad_id}): " . $e->getMessage());
                $results[$satellite->norad_id] = false;
            }
        }

        return $results;
    }

    /**
     * Capture orbital, environmental, and scientific telemetry.
     */
    public function captureTelemetry(Satellite $satellite): array
    {
        $now = Carbon::now('UTC')->toDateTime();
        $orbital = $this->engine->propagate($satellite, $now);

        // 1. Raw Orbital Elements (Extracted from TLE)
        $elements = $this->extractOrbitalElements($satellite);

        // 2. Scientific Derivations
        $solar = $this->deriveSolarState($orbital['latitude'], $orbital['longitude'], $now);
        $magnetic = $this->deriveMagneticField($orbital['latitude'], $orbital['altitude']);

        // 3. Environmental Proxy (Atmospheric Model)
        $seed = crc32($satellite->norad_id . $now->format('YmdH'));
        mt_srand($seed);
        $brightness = mt_rand(50, 200);
        $rain = mt_rand(0, 50);

        $env = [
            'temperature' => $this->atmosphericModel->deriveTemperature($brightness, $orbital['latitude']),
            'pressure' => $this->atmosphericModel->derivePressure($brightness / 2, $orbital['latitude']),
            'humidity' => $this->atmosphericModel->deriveHumidity($rain, 25),
            'solar_flux' => $solar['is_daylight'] ? mt_rand(1360, 1367) : mt_rand(0, 5),
        ];

        return [
            'metadata' => [
                'norad_id' => $satellite->norad_id,
                'name' => $satellite->name,
                'timestamp' => $orbital['timestamp'],
                'organization' => $satellite->api_config['organization'] ?? 'UNKNOWN',
                'type' => $satellite->type,
            ],
            'orbital' => [
                'coordinates' => [
                    'lat' => $orbital['latitude'],
                    'lng' => $orbital['longitude'],
                    'alt' => $orbital['altitude'],
                ],
                'physics' => [
                    'velocity_kms' => $orbital['velocity'],
                    'period_min' => $orbital['period'],
                    'inclination_deg' => $elements['inclination'],
                    'eccentricity' => $elements['eccentricity'],
                ]
            ],
            'scientific' => [
                'solar' => $solar,
                'magnetic_field' => $magnetic,
                'atmosphere' => $env,
            ],
            'subsystems' => [
                'power_bus' => mt_rand(92, 99) . '%',
                'thermal' => mt_rand(15, 45) . 'C',
                'comm_link' => '-' . mt_rand(55, 75) . 'dBm',
                'attitude' => 'NADIR_STABLE'
            ]
        ];
    }

    /**
     * Extract key elements from 2-line TLE format.
     */
    private function extractOrbitalElements(Satellite $sat): array
    {
        $tle2 = $sat->tle_line2;
        return [
            'inclination' => (float) substr($tle2, 8, 8),
            'eccentricity' => (float) ("0." . substr($tle2, 26, 7)),
            'raan' => (float) substr($tle2, 17, 8),
        ];
    }

    /**
     * Simplified Sun Elevation and Daylight calculation.
     */
    private function deriveSolarState(float $lat, float $lng, \DateTime $time): array
    {
        $hour = (int) $time->format('H') + ($lng / 15); // Solar time approximation
        $hour = fmod($hour + 24, 24);

        $elevation = 90 - abs($lat - (23.45 * sin(deg2rad(360 / 365 * (date('z') + 10)))));
        $elevation = $elevation * sin(deg2rad(($hour - 6) * 15));

        return [
            'sun_elevation_deg' => round($elevation, 2),
            'is_daylight' => $elevation > 0,
            'local_solar_time' => sprintf("%02d:00", floor($hour))
        ];
    }

    /**
     * Dipole Model approximation for Magnetic Field.
     */
    private function deriveMagneticField(float $lat, float $alt): array
    {
        $re = 6371; // Earth radius
        $r = $re + $alt;
        $m0 = 31200; // Earth's main field strength at equator (nT)

        $strength = ($m0 * pow($re / $r, 3)) * sqrt(1 + 3 * pow(sin(deg2rad($lat)), 2));

        return [
            'field_strength_nt' => round($strength, 1),
            'unit' => 'nanotesla',
            'model' => 'Dipole Static Approximation'
        ];
    }

    /**
     * Store telemetry as a timestamped JSON file.
     */
    protected function storeTelemetry(Satellite $satellite, array $data): void
    {
        $timestamp = Carbon::parse($data['metadata']['timestamp'])->format('Ymd_His');
        $path = "telemetry/{$satellite->norad_id}/{$timestamp}.json";
        $latestPath = "telemetry/{$satellite->norad_id}/latest.json";

        $jsonContent = json_encode($data, JSON_PRETTY_PRINT);

        Storage::disk('public')->put($path, $jsonContent);
        Storage::disk('public')->put($latestPath, $jsonContent);
    }
}
