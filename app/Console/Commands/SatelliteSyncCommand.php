<?php

namespace App\Console\Commands;

use App\Jobs\SatelliteSyncJob;
use Illuminate\Console\Command;

class SatelliteSyncCommand extends Command
{
    protected $signature = 'satellite:sync';
    protected $description = 'Manually synchronize all satellites with CelesTrak TLE data';

    public function handle()
    {
        $this->info("Dispatching SatelliteSyncJob...");
        dispatch_sync(new SatelliteSyncJob());
        $this->info("Sync completed successfully.");
    }
}
