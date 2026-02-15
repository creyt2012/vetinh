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

    public function handle(SatelliteEngine $engine): void
    {
        $satellites = Satellite::where('status', 'ACTIVE')->get();

        foreach ($satellites as $satellite) {
            try {
                $trackData = $engine->propagate($satellite);

                SatelliteTrack::create(array_merge([
                    'satellite_id' => $satellite->id,
                    'tracked_at' => now(),
                ], $trackData));

                // Broadcast live update
                event(new SatelliteUpdated($satellite, $trackData));

            } catch (\Exception $e) {
                Log::error("Failed to propagate satellite {$satellite->name}: " . $e->getMessage());
            }
        }
    }
}
