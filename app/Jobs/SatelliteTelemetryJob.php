<?php

namespace App\Jobs;

use App\Engines\Satellite\SatelliteTelemetryManager;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SatelliteTelemetryJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(SatelliteTelemetryManager $manager): void
    {
        try {
            $manager->ingestAll();
            Log::info("Satellite Telemetry Scheduled Ingestion Successful.");
        } catch (\Exception $e) {
            Log::error("Satellite Telemetry Job Failed: " . $e->getMessage());
        }
    }
}
