<?php

use Illuminate\Support\Facades\Schedule;
use App\Jobs\HimawariIngestJob;
use App\Jobs\SatellitePropagateJob;

use App\Jobs\GroundIngestJob;

Schedule::job(new HimawariIngestJob)->everyTenMinutes();
Schedule::job(new SatellitePropagateJob)->everyFiveSeconds();
Schedule::job(new GroundIngestJob)->everyMinute();
Schedule::job(new \App\Jobs\HealthCheckJob)->everyMinute();
Schedule::job(new \App\Jobs\WeatherAggregationJob)->dailyAt('00:05');
Schedule::job(new \App\Jobs\AlertMonitorJob)->everyMinute();
Schedule::job(new \App\Jobs\RadarIngestJob)->everyMinute();
Schedule::job(new \App\Jobs\GribIngestionJob)->hourly();
Schedule::job(new \App\Jobs\GlobalImagerySyncJob)->twiceDaily(0, 12);
Schedule::job(new \App\Jobs\SatelliteTelemetryJob)->hourly();
Schedule::job(new \App\Jobs\CelestrakSyncJob)->daily();
