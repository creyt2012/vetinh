<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class RadarSyncCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'radar:sync';
    protected $description = 'Sync latest global radar metadata from RainViewer';

    public function handle(\App\Engines\Weather\RainViewerService $rainViewer)
    {
        $this->info("Fetching latest Radar metadata from RainViewer...");

        $radarConfig = $rainViewer->getLatestRadar();

        if (!empty($radarConfig) && isset($radarConfig['timestamp'])) {
            // Store in Cache for 10 minutes (radar updates every 10 mins usually)
            \Illuminate\Support\Facades\Cache::put('radar_config_latest', $radarConfig, now()->addMinutes(10));

            $this->info("Radar sync successful. Latest timestamp: " . $radarConfig['timestamp']);

            // Log for audit
            \App\Models\ActivityLog::log(
                null,
                'RADAR_SYNC_SUCCESS',
                "Synchronized global radar metadata for timestamp: " . $radarConfig['timestamp']
            );
        } else {
            $this->error("Failed to sync Radar metadata. Config: " . json_encode($radarConfig));
        }
    }
}
