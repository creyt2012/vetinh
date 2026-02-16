<?php

use Illuminate\Support\Facades\Schedule;
use App\Jobs\HimawariIngestJob;
use App\Jobs\SatellitePropagateJob;

use App\Jobs\GroundIngestJob;

Schedule::job(new HimawariIngestJob)->everyTenMinutes();
Schedule::job(new SatellitePropagateJob)->everyFiveSeconds();
Schedule::job(new GroundIngestJob)->everyMinute();
