<?php

namespace App\Jobs;

use App\Engines\Weather\SatelliteImageryManager;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class GlobalImagerySyncJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
    }

    /**
     * Execute the job.
     */
    public function handle(SatelliteImageryManager $manager): void
    {
        try {
            $manager->syncAll();
            Log::info("Scheduled Global Imagery Sync successful.");
        } catch (\Exception $e) {
            Log::error("Global Imagery Sync Job Failed: " . $e->getMessage());
        }
    }
}
