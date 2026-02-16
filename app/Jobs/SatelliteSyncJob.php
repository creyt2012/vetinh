<?php

namespace App\Jobs;

use App\Engines\Satellite\CelesTrakService;
use App\Models\Satellite;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SatelliteSyncJob implements ShouldQueue
{
    use Queueable;

    public function handle(CelesTrakService $service): void
    {
        Log::info("Starting global satellite TLE synchronization...");

        // Fetch groups
        $groups = ['STATIONS', 'WEATHER', 'GPS', 'ACTIVE'];
        $count = 0;

        foreach ($groups as $group) {
            $tles = $service->fetchGroup($group);

            foreach ($tles as $noradId => $data) {
                $satellite = Satellite::where('norad_id', $noradId)->first();

                if ($satellite) {
                    $satellite->update([
                        'tle_line1' => $data['tle_line1'],
                        'tle_line2' => $data['tle_line2'],
                        'status' => 'ACTIVE'
                    ]);
                    $count++;
                }
            }
        }

        Log::info("Synchronization complete. Updated {$count} orbital assets.");
    }
}
