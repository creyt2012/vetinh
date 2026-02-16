<?php

namespace App\Jobs;

use App\Models\Satellite;
use App\Models\SatelliteTrack;
use App\Engines\Satellite\SatelliteEngine;
use App\Events\SatelliteUpdated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SatellitePropagateJob implements ShouldQueue
{
    use Queueable;

    public function handle(SatelliteEngine $engine, \App\Repositories\StateRepository $stateRepo): void
    {
        $satellites = Satellite::where('status', 'ACTIVE')->get();

        /** @var \App\Models\Satellite $satellite */
        foreach ($satellites as $satellite) {
            try {
                $trackData = $engine->propagate($satellite);

                SatelliteTrack::create(array_merge([
                    'satellite_id' => $satellite->id,
                    'tracked_at' => now(),
                ], $trackData));

                // 2. Update L1 Cache
                $stateRepo->setSatelliteState($satellite->id, array_merge([
                    'id' => $satellite->id,
                    'name' => $satellite->name,
                    'type' => $satellite->type,
                    'velocity' => $trackData['velocity'] ?? 0,
                    'altitude' => $trackData['altitude'] ?? 0,
                    'period' => $trackData['period'] ?? 0,
                ], $trackData));

                // 3. Broadcast live update
                event(new SatelliteUpdated($satellite, $trackData));

            } catch (\Exception $e) {
                Log::error("Failed to propagate satellite {$satellite->name}: " . $e->getMessage());
            }
        }
    }
}
