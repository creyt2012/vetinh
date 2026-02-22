<?php

namespace App\Jobs;

use App\Models\Satellite;
use App\Models\SatelliteTrack;
use Vortex\Aerospace\SatelliteEngine;
use App\Events\SatelliteUpdated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SatellitePropagateJob implements ShouldQueue
{
    use Queueable;

    public function handle(
        SatelliteEngine $engine,
        \Vortex\Aerospace\ConjunctionEngine $conjunctionEngine,
        \App\Repositories\StateRepository $stateRepo
    ): void {
        $satellites = Satellite::where('status', 'ACTIVE')->get();
        $batchData = [];

        /** @var \App\Models\Satellite $satellite */
        foreach ($satellites as $satellite) {
            try {
                $trackData = $engine->propagate($satellite);
                $path = $engine->getOrbitPath($satellite);

                $stateData = array_merge([
                    'id' => $satellite->id,
                    'name' => $satellite->name,
                    'norad_id' => $satellite->norad_id,
                    'type' => $satellite->type,
                    'velocity' => $trackData['velocity'] ?? 0,
                    'altitude' => $trackData['altitude'] ?? 0,
                    'period' => $trackData['period'] ?? 0,
                    'path' => $path,
                    'telemetry' => [
                        'period' => $trackData['period'] ?? 0,
                        'velocity' => $trackData['velocity'] ?? 0,
                    ]
                ], $trackData);

                // 1. Update L1 Cache
                $stateRepo->setSatelliteState($satellite->id, $stateData);

                // 2. Add to batch for broadcast
                $batchData[] = $stateData;

                // 3. Throttle Persistence: Only save to DB every 5 minutes
                // Check a small TTL-based lock in cache
                $lockKey = "sat_persist_lock:{$satellite->id}";
                if (!\Illuminate\Support\Facades\Cache::has($lockKey)) {
                    SatelliteTrack::create(array_merge([
                        'satellite_id' => $satellite->id,
                        'tracked_at' => now(),
                    ], $trackData));
                    \Illuminate\Support\Facades\Cache::put($lockKey, true, now()->addMinutes(5));
                }

            } catch (\Exception $e) {
                Log::error("Failed to propagate satellite {$satellite->name}: " . $e->getMessage());
            }
        }

        // 4. Batch Broadcast for high performance
        if (!empty($batchData)) {
            event(new \App\Events\SatelliteBatchUpdated($batchData));

            // 5. Orbital Conjunction Analysis
            $conjunctionEngine->analyze($batchData);
        }
    }
}
