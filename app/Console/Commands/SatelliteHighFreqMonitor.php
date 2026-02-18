<?php

namespace App\Console\Commands;

use App\Engines\Satellite\SatelliteTelemetryManager;
use App\Models\Satellite;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class SatelliteHighFreqMonitor extends Command
{
    protected $signature = 'satellite:monitor {--interval=1 : Update interval in seconds}';
    protected $description = 'High-frequency satellite telemetry monitor (1Hz resolution)';

    public function handle(SatelliteTelemetryManager $manager)
    {
        $this->info("Satellite High-Frequency Monitor [1Hz] Initiated.");
        $this->info("Tracking 109 strategic assets in real-time...");

        $interval = (int) $this->option('interval');
        $satellites = Satellite::where('status', 'ACTIVE')->get();

        while (true) {
            $startTime = microtime(true);
            $aggregateState = [];

            foreach ($satellites as $satellite) {
                try {
                    // Capture volatile state (No historical disk write per second)
                    $telemetry = $manager->captureTelemetry($satellite);

                    // Update the individual 'latest.json' (Fast IO)
                    $this->updateLatestJson($satellite, $telemetry);

                    // Add to aggregate for frontend optimization
                    $aggregateState[$satellite->norad_id] = [
                        'n' => $satellite->name,
                        'lat' => $telemetry['orbital']['coordinates']['lat'],
                        'lng' => $telemetry['orbital']['coordinates']['lng'],
                        'alt' => $telemetry['orbital']['coordinates']['alt'],
                        'v' => $telemetry['orbital']['physics']['velocity_kms'],
                        'h' => $telemetry['intel']['heading_deg'],
                        'gs' => $telemetry['intel']['link_specs']['is_visible_to_hanoi']
                    ];

                } catch (\Exception $e) {
                    // Fail silently in high-speed loop to maintain rhythm
                }
            }

            // Store aggregate state for fast global map updates
            Storage::disk('public')->put('telemetry/constellation_state.json', json_encode([
                'timestamp' => now()->toDateTimeString(),
                'count' => count($aggregateState),
                'satellites' => $aggregateState
            ]));

            $endTime = microtime(true);
            $executionTime = $endTime - $startTime;
            $sleepTime = max(0, $interval - $executionTime);

            if ($sleepTime > 0) {
                usleep($sleepTime * 1000000);
            }
        }
    }

    private function updateLatestJson(Satellite $satellite, array $data): void
    {
        $path = "telemetry/{$satellite->norad_id}/latest.json";
        Storage::disk('public')->put($path, json_encode($data));
    }
}
