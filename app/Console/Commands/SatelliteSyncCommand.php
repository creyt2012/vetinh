<?php

namespace App\Console\Commands;

use App\Engines\Satellite\CelesTrakService;
use App\Models\Satellite;
use Illuminate\Console\Command;

class SatelliteSyncCommand extends Command
{
    protected $signature = 'satellite:sync';
    protected $description = 'Sync real-time TLE data for strategic satellites from CelesTrak';

    public function handle(CelesTrakService $celesTrak)
    {
        $this->info("Initiating Satellite Strategic Synchronizations...");

        // Strategy: Focus on key assets for the demonstration
        $groups = ['STATIONS', 'WEATHER', 'GPS'];

        foreach ($groups as $group) {
            $this->info("Fetching group: {$group}");
            $satellites = $celesTrak->fetchGroup($group);

            foreach ($satellites as $noradId => $data) {
                // Update or Create the satellite in our db
                Satellite::updateOrCreate(
                    ['norad_id' => $noradId],
                    [
                        'name' => $data['name'],
                        'tle_line1' => $data['tle_line1'],
                        'tle_line2' => $data['tle_line2'],
                        'status' => 'ACTIVE',
                        'data_source' => 'CELESTRAK',
                        'source_url' => 'https://celestrak.org',
                        'type' => $group === 'WEATHER' ? 'METEOROLOGICAL' : ($group === 'STATIONS' ? 'STATION' : 'NAVIGATION')
                    ]
                );
            }
        }

        $this->info("Synchronization Complete. Strategic Assets Updated.");
    }
}
