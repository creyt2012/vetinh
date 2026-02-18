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
     * Capture orbital and environmental telemetry.
     */
    public function captureTelemetry(Satellite $satellite): array
    {
        $now = Carbon::now('UTC')->toDateTime();
        $orbital = $this->engine->propagate($satellite, $now);

        // Derive environmental metrics based on ground position
        // In a real system, this would come from sensors. Here we use our AtmosphericModel.
        // We simulate a 'brightness' and 'rain' value based on typical weather patterns or random seed.
        $seed = crc32($satellite->norad_id . $now->format('YmdH'));
        mt_srand($seed);

        $brightness = mt_rand(50, 200);
        $rain = mt_rand(0, 50);

        $env = [
            'temperature' => $this->atmosphericModel->deriveTemperature($brightness, $orbital['latitude']),
            'pressure' => $this->atmosphericModel->derivePressure($brightness / 2, $orbital['latitude']),
            'humidity' => $this->atmosphericModel->deriveHumidity($rain, 25), // Normalized base temp
            'solar_flux' => mt_rand(1350, 1370), // Typical solar constant oscillation
        ];

        return [
            'metadata' => [
                'norad_id' => $satellite->norad_id,
                'name' => $satellite->name,
                'timestamp' => $orbital['timestamp'],
                'epoch' => $now->getTimestamp(),
            ],
            'orbital' => [
                'lat' => $orbital['latitude'],
                'lng' => $orbital['longitude'],
                'alt' => $orbital['altitude'],
                'vel' => $orbital['velocity'],
                'period' => $orbital['period'],
            ],
            'environmental' => $env,
            'status' => [
                'power' => mt_rand(90, 100) . '%',
                'signal_strength' => '-' . mt_rand(40, 80) . ' dBm',
                'orientation' => 'NADIR_STABLE'
            ]
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
